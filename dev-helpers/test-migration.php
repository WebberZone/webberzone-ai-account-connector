<?php
/**
 * Run on a disposable local WordPress CLI session using wp eval-file.
 */

( defined( 'WP_CLI' ) && WP_CLI && ! is_multisite() && ! wp_using_ext_object_cache() ) || exit;

global $wpdb;

$source_root = dirname( __DIR__, 2 );
require_once dirname( __DIR__ ) . '/webberzone-ai-account-connector.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$original_table  = $wpdb->options;
$temporary_table = 'wzap_test_' . str_replace( '-', '', wp_generate_uuid4() );
if ( false === $wpdb->query( $wpdb->prepare( 'CREATE TEMPORARY TABLE %i LIKE %i', $temporary_table, $original_table ) ) ) {
	throw new RuntimeException( 'Could not create the temporary options table.' );
}

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

wp_salt( 'auth' );
$wpdb->options = $temporary_table;
wp_cache_init();

try {
	$providers = \WebberZone\AI_Account_Connector\providers();
	$plugin    = plugin_basename( dirname( __DIR__ ) . '/webberzone-ai-account-connector.php' );
	add_option( 'active_plugins', array( $plugin, ...array_keys( $providers ) ) );
	foreach ( $providers as $legacy_plugin => $provider ) {
		$legacy_namespace = false !== strpos( $provider, 'ChatGPT' ) ? 'ChatGPT_Account' : 'Grok_Account';
		$legacy_path      = $source_root . '/' . dirname( $legacy_plugin );
		require_once $legacy_path . '/includes/class-token-store.php';
		$legacy_store = 'WebberZone\\' . $legacy_namespace . '\\Token_Store';
		$legacy_store::save(
			array(
				'access_token'  => 'synthetic-access',
				'refresh_token' => 'synthetic-refresh',
				'account_id'    => 'synthetic-account',
				'id_token'      => 'synthetic-id',
			)
		);
		$assert( ! \WebberZone\AI_Account_Connector\prepare_provider( $legacy_plugin, $provider ), 'An active legacy plugin must keep ownership.' );
	}

	update_option( 'active_plugins', array( $plugin ) );
	$revocations = 0;
	add_filter(
		'pre_http_request',
		static function ( $preempt, $args, $url ) use ( &$revocations ) {
			if ( in_array( $url, array( 'https://auth.openai.com/oauth/token', 'https://auth.x.ai/oauth2/token' ), true ) ) {
				$body = is_array( $args['body'] ) ? $args['body'] : json_decode( $args['body'], true );
				if ( 'synthetic-refresh' === ( $body['refresh_token'] ?? '' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode( array( 'access_token' => 'rotated-access', 'refresh_token' => 'rotated-refresh', 'expires_in' => 3600 ) ),
					);
				}
			}
			++$revocations;
			return new WP_Error( 'test_http', 'External requests are blocked during this test.' );
		},
		10,
		3
	);

	define( 'WP_UNINSTALL_PLUGIN', 'migration-fixture' );
	foreach ( $providers as $legacy_plugin => $provider ) {
		$store  = $provider . '\\Token_Store';
		$before = get_option( $store::LEGACY_OPTION );

		\WebberZone\AI_Account_Connector\protect_before_legacy_uninstall( $legacy_plugin );
		$assert( $before === get_option( $store::OPTION ), 'Encrypted token data must transfer intact.' );
		$assert( false === get_option( $store::LEGACY_OPTION ), 'The old option must no longer hold a grant.' );
		require $source_root . '/' . dirname( $legacy_plugin ) . '/uninstall.php';
		$assert( 0 === $revocations, 'Legacy uninstall must not revoke the transferred grant.' );
		$assert( 'synthetic-refresh' === $store::get()['refresh_token'], 'The current plugin must retain the refresh token.' );
		$assert( \WebberZone\AI_Account_Connector\prepare_provider( $legacy_plugin, $provider ), 'Repeating the migration must succeed.' );
		$oauth = $provider . '\\OAuth';
		$assert( 'rotated-access' === $oauth::refresh( true )['access_token'], 'The migrated connection must refresh using the original grant.' );
		$assert( 'rotated-refresh' === $store::get()['refresh_token'], 'The replacement option must store the rotated grant.' );
		$store::clear();
		$assert( \WebberZone\AI_Account_Connector\prepare_provider( $legacy_plugin, $provider ) && ! $store::is_connected(), 'Disconnect must not restore the migrated connection.' );

		add_option( $store::LEGACY_OPTION, 'invalid-ciphertext', '', false );
		$assert( ! \WebberZone\AI_Account_Connector\prepare_provider( $legacy_plugin, $provider ) && 'invalid-ciphertext' === get_option( $store::LEGACY_OPTION ), 'Invalid ciphertext must remain in the original option.' );
		delete_option( $store::LEGACY_OPTION );
	}
} finally {
	$wpdb->options = $original_table;
	wp_cache_init();
	$wpdb->query( $wpdb->prepare( 'DROP TEMPORARY TABLE %i', $temporary_table ) );
}

WP_CLI::success( 'Both encrypted connections survived migration and legacy uninstall, and refreshed without reconnecting.' );
