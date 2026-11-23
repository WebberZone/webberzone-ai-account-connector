<?php
/**
 * Plugin Name: WebberZone AI Account Connector
 * Description: Connect ChatGPT and Grok accounts to the WordPress AI Client from Settings → Connectors.
 * Version: 1.0.3
 * Author: WebberZone
 * Author URI: https://webberzone.com
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Update URI: https://github.com/WebberZone/webberzone-ai-account-connector
 * License: GPL-2.0-or-later
 * Text Domain: webberzone-ai-account-connector
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.0.3';
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

if ( apply_filters( 'wzaiac_enable_update_checks', true ) && is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
	$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/WebberZone/webberzone-ai-account-connector',
		__FILE__,
		'webberzone-ai-account-connector'
	);
	$github_api     = $update_checker->getVcsApi();
	if ( method_exists( $github_api, 'enableReleaseAssets' ) ) {
		$github_api->enableReleaseAssets(
			'/^webberzone-ai-account-connector\.zip$/',
			\YahnisElsts\PluginUpdateChecker\v5p7\Vcs\Api::REQUIRE_RELEASE_ASSETS
		);
	}
	add_filter(
		'puc_vcs_update_detection_strategies-' . MODULE,
		static function ( $strategies ) {
			return array_intersect_key( $strategies, array( 'latest_release' => true ) );
		}
	);
}

require_once __DIR__ . '/includes/chatgpt/provider.php';
require_once __DIR__ . '/includes/grok/provider.php';

/**
 * Maps standalone provider namespaces to their combined equivalents.
 */
function providers() {
	return array(
		'WebberZone\\ChatGPT_Account' => __NAMESPACE__ . '\\ChatGPT',
		'WebberZone\\Grok_Account'    => __NAMESPACE__ . '\\Grok',
	);
}

/**
 * Registers the providers after the plugins have loaded.
 */
function start() {
	$connectors = array();
	foreach ( providers() as $standalone => $provider ) {
		if ( function_exists( $standalone . '\\register_provider' ) || ! ( $provider . '\\is_ready' )() ) {
			continue;
		}
		add_action( 'init', $provider . '\\register_provider', 6 );
		add_action( 'init', $provider . '\\attach_authentication', 30 );
		add_filter( 'wpai_has_ai_credentials', $provider . '\\report_connected' );
		if ( function_exists( $provider . '\\report_image_support' ) ) {
			add_filter( 'wpai_has_image_generation_support', $provider . '\\report_image_support' );
		}
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
