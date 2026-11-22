<?php
/**
 * Plugin Name: WebberZone AI Account Connector
 * Description: Connect ChatGPT and Grok accounts to the WordPress AI Client from Settings → Connectors.
 * Version: 1.0.0
 * Author: WebberZone
 * Author URI: https://webberzone.com
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * Text Domain: webberzone-ai-account-connector
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.0';
const MODULE  = 'webberzone-ai-account-connector';

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$parts = explode( '\\', strtolower( substr( $class_name, strlen( $prefix ) ) ) );
		$file  = 'class-' . str_replace( '_', '-', array_pop( $parts ) ) . '.php';
		$path  = __DIR__ . '/includes/' . implode( '/', array_merge( $parts, array( $file ) ) );
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

require_once __DIR__ . '/includes/chatgpt/provider.php';
require_once __DIR__ . '/includes/grok/provider.php';

/**
 * Maps the standalone plugin files to their provider namespaces.
 */
function providers() {
	return array(
		'webberzone-chatgpt-account/webberzone-chatgpt-account.php' => __NAMESPACE__ . '\\ChatGPT',
		'webberzone-grok-account/webberzone-grok-account.php'       => __NAMESPACE__ . '\\Grok',
	);
}

/**
 * Checks site and network activation.
 *
 * @param string $plugin Plugin basename.
 */
function plugin_active( $plugin ) {
	return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true )
		|| ( is_multisite() && isset( get_site_option( 'active_sitewide_plugins', array() )[ $plugin ] ) );
}

/**
 * Transfers an inactive standalone provider's connection.
 *
 * @param string $plugin Standalone plugin basename.
 * @param string $provider Provider namespace.
 */
function prepare_provider( $plugin, $provider ) {
	if ( plugin_active( $plugin ) ) {
		return false;
	}
	try {
		return ( $provider . '\\OAuth' )::migrate();
	} catch ( \RuntimeException $e ) {
		return false;
	}
}

/**
 * Registers the providers after the plugins have loaded.
 */
function start() {
	$connectors = array();
	foreach ( providers() as $plugin => $provider ) {
		if ( ! prepare_provider( $plugin, $provider ) ) {
			continue;
		}
		if ( ! ( $provider . '\\is_ready' )() ) {
			continue;
		}
		add_action( 'init', $provider . '\\register_provider', 6 );
		add_action( 'init', $provider . '\\attach_authentication', 30 );
		add_filter( 'wpai_has_ai_credentials', $provider . '\\report_connected' );
		add_filter( 'wpai_has_image_generation_support', $provider . '\\report_connected' );
		if ( is_admin() ) {
			( $provider . '\\Admin' )::init();
			$connectors[] = $provider . '\\Connectors';
		}
	}

	if ( $connectors ) {
		add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );
		add_filter(
			'script_module_data_' . MODULE,
			static function ( $data ) use ( $connectors ) {
				$data['providers'] = array_map(
					static function ( $connector ) {
						return $connector::module_data( array() );
					},
					$connectors
				);
				return $data;
			}
		);
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\start', 20 );

/**
 * Loads the AI account cards on the Connectors screen.
 */
function enqueue() {
	$screen = get_current_screen();
	if ( $screen && 'options-connectors' === $screen->id && current_user_can( 'manage_options' ) ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script_module(
			MODULE,
			plugins_url( 'assets/js/connectors' . $suffix . '.js', __FILE__ ),
			array(
				array(
					'id'     => '@wordpress/connectors',
					'import' => 'static',
				),
			),
			VERSION
		);
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( Admin::class, 'action_links' ) );

/**
 * Transfers pending connections before the legacy uninstaller can revoke them.
 *
 * @param string $plugin Plugin being deleted.
 */
function protect_before_legacy_uninstall( $plugin ) {
	$provider = providers()[ $plugin ] ?? null;
	if ( null === $provider ) {
		return;
	}

	$site_ids = is_multisite() ? get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	) : array( get_current_blog_id() );
	foreach ( $site_ids as $site_id ) {
		$switched = is_multisite() && (int) get_current_blog_id() !== (int) $site_id;
		if ( $switched ) {
			switch_to_blog( (int) $site_id );
		}
		try {
			$replacement_active = plugin_active( plugin_basename( __FILE__ ) );
			$store              = $provider . '\\Token_Store';
			$has_legacy_tokens  = (bool) get_option( $store::LEGACY_OPTION );
			$success            = ! $has_legacy_tokens || ( $replacement_active && prepare_provider( $plugin, $provider ) );
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
		if ( ! $success ) {
			wp_die( esc_html__( 'The existing account could not be transferred. Keep the standalone plugin installed and verify the connection in Settings → Connectors before trying again.', 'webberzone-ai-account-connector' ) );
		}
	}
}
add_action( 'pre_uninstall_plugin', __NAMESPACE__ . '\\protect_before_legacy_uninstall' );

/**
 * Shows the next migration step on the relevant admin screens.
 */
function migration_notice() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network', 'options-connectors' ), true ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	foreach ( providers() as $plugin => $provider ) {
		$config = $provider . '\\Config';
		$store  = $provider . '\\Token_Store';
		if ( plugin_active( $plugin ) ) {
			/* translators: %s: provider name. */
			$message = sprintf( __( 'Deactivate the standalone %s plugin, then verify the existing connection in Settings → Connectors before deleting it.', 'webberzone-ai-account-connector' ), $config::label() );
		} elseif ( get_option( $store::LEGACY_OPTION ) ) {
			/* translators: %s: provider name. */
			$message = sprintf( __( 'The existing %s connection could not be transferred. Keep the standalone plugin installed until you can verify the connection in Settings → Connectors.', 'webberzone-ai-account-connector' ), $config::label() );
		} elseif ( __NAMESPACE__ . '\\ChatGPT' === $provider && ! ChatGPT\is_ready() ) {
			$message = __( 'To use the ChatGPT connector, activate a compatible version of AI Provider for OpenAI.', 'webberzone-ai-account-connector' );
		} else {
			continue;
		}
		printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $message ) );
	}
}
add_action( 'admin_notices', __NAMESPACE__ . '\\migration_notice' );
add_action( 'network_admin_notices', __NAMESPACE__ . '\\migration_notice' );
