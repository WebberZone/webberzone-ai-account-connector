<?php
/**
 * Clean up connections on plugin deletion.
 *
 * @package WebberZone\AI_Account_Connector
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/webberzone-ai-account-connector.php';

( static function () {
	$sites = is_multisite() ? get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) : array( get_current_blog_id() );
	foreach ( $sites as $site_id ) {
		$switched = is_multisite() && (int) get_current_blog_id() !== (int) $site_id;
		if ( $switched ) {
			switch_to_blog( (int) $site_id );
		}
		try {
			foreach ( \WebberZone\AI_Account_Connector\providers() as $provider ) {
				$oauth = $provider . '\\OAuth';
				$store = $provider . '\\Token_Store';
				if ( $store::is_connected() ) {
					try {
						$oauth::disconnect();
					} catch ( \RuntimeException $error ) {
						unset( $error );
					}
				}
				$store::clear();
			}
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}
} )();
