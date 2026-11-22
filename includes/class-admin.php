<?php
/**
 * AJAX handlers for the Connectors card.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * AJAX endpoints used by the Connectors card, plus the Plugins screen link.
 *
 * @since 1.0.0
 */
abstract class Admin {

	const PREFIX = '';
	const OAUTH  = null;
	const CONFIG = null;



	/**
	 * Nonce action shared by all AJAX requests.
	 *
	 * @var string
	 */
	const NONCE = '';

	/**
	 * Transient prefix, followed by the user ID, flagging that the last disconnect could not revoke the sign-in.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const REVOKE_FAILED = '';

	/**
	 * Whether the salt notice was already printed, so it shows once for both providers.
	 *
	 * @var bool
	 */
	private static $salt_notice_shown = false;

	/**
	 * Registers the hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'wp_ajax_' . static::PREFIX . '_start', array( static::class, 'ajax_start' ) );
		add_action( 'wp_ajax_' . static::PREFIX . '_poll', array( static::class, 'ajax_poll' ) );
		add_action( 'wp_ajax_' . static::PREFIX . '_exchange', array( static::class, 'ajax_exchange' ) );
		add_action( 'wp_ajax_' . static::PREFIX . '_cancel', array( static::class, 'ajax_cancel' ) );
		add_action( 'wp_ajax_' . static::PREFIX . '_disconnect', array( static::class, 'ajax_disconnect' ) );
		add_action( 'admin_notices', array( static::class, 'salt_notice' ) );
		add_action( 'admin_notices', array( static::class, 'revoke_notice' ) );
	}

	/**
	 * Adds a Settings link to Settings → Connectors on the Plugins screen.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $links Action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-connectors.php' ) ) . '">' . esc_html__( 'Settings', 'webberzone-ai-account-connector' ) . '</a>' );
		return $links;
	}

	/**
	 * AJAX: starts sign-in. ChatGPT requests a device code; Grok returns the xAI consent URL.
	 *
	 * @since 1.0.0
	 */
	public static function ajax_start() {
		$oauth = static::OAUTH;
		self::verify();
		try {
			wp_send_json_success( $oauth::start_device_flow() );
		} catch ( \Exception $e ) {
			wp_send_json_error( html_entity_decode( $e->getMessage(), ENT_QUOTES ) );
		}
	}

	/**
	 * AJAX: checks whether the device code was approved.
	 *
	 * @since 1.0.0
	 */
	public static function ajax_poll() {
		$oauth = static::OAUTH;
		self::verify();
		try {
			wp_send_json_success( $oauth::poll_device_flow() );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message'   => html_entity_decode( $e->getMessage(), ENT_QUOTES ),
					'retryable' => in_array( $e->getCode(), array( 408, 429 ), true ) || $e->getCode() >= 500,
				)
			);
		}
	}

	/**
	 * AJAX: exchanges a code pasted from the xAI consent screen.
	 *
	 * ChatGPT does not use this action.
	 *
	 * @since 1.0.1
	 */
	public static function ajax_exchange() {
		$oauth = static::OAUTH;
		check_ajax_referer( static::NONCE );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to do that.', 'webberzone-ai-account-connector' ), 403 );
		}
		if ( ! method_exists( $oauth, 'exchange_authorization_code' ) ) {
			wp_send_json_error( __( 'This account does not use a pasted sign-in code.', 'webberzone-ai-account-connector' ) );
		}
		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		try {
			wp_send_json_success( $oauth::exchange_authorization_code( $code ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( html_entity_decode( $e->getMessage(), ENT_QUOTES ) );
		}
	}

	/**
	 * AJAX: abandons the pending device code.
	 *
	 * @since 1.0.0
	 */
	public static function ajax_cancel() {
		$oauth = static::OAUTH;
		self::verify();
		try {
			$oauth::cancel_device_flow();
			wp_send_json_success();
		} catch ( \Exception $e ) {
			wp_send_json_error( html_entity_decode( $e->getMessage(), ENT_QUOTES ) );
		}
	}

	/**
	 * AJAX: disconnects the account.
	 *
	 * @since 1.0.0
	 */
	public static function ajax_disconnect() {
		$oauth = static::OAUTH;
		self::verify();
		try {
			if ( ! $oauth::disconnect() ) {
				set_transient( static::REVOKE_FAILED . get_current_user_id(), 1, 5 * MINUTE_IN_SECONDS );
			}
			wp_send_json_success();
		} catch ( \Exception $e ) {
			wp_send_json_error( html_entity_decode( $e->getMessage(), ENT_QUOTES ) );
		}
	}

	/**
	 * Verifies the AJAX nonce and capability.
	 *
	 * @since 1.0.0
	 */
	private static function verify() {
		check_ajax_referer( static::NONCE );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Sorry, you are not allowed to do that.', 'webberzone-ai-account-connector' ), 403 );
		}
	}

	/**
	 * Warns on Settings → Connectors when the authentication salts live in the database rather than wp-config.php.
	 *
	 * @since 1.0.0
	 */
	public static function salt_notice() {
		if ( self::$salt_notice_shown || ! self::on_connectors_screen() || self::salts_in_config() ) {
			return;
		}
		self::$salt_notice_shown = true;
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'The key that encrypts the stored ChatGPT and Grok sign-ins is derived from AUTH_KEY and AUTH_SALT. Define them in wp-config.php so the key is not stored in the database alongside the tokens. Changing them disconnects the accounts, so sign in again afterwards.', 'webberzone-ai-account-connector' )
		);
	}

	/**
	 * Warns on Settings → Connectors, once, when the last disconnect could not revoke the sign-in with the provider.
	 *
	 * @since 1.0.0
	 */
	public static function revoke_notice() {
		$config = static::CONFIG;
		$key    = static::REVOKE_FAILED . get_current_user_id();
		if ( ! self::on_connectors_screen() || ! get_transient( $key ) ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: service name. */
					__( 'The account was disconnected from this site, but %s did not confirm that the sign-in was revoked. To make sure it can no longer be used, sign out of all sessions in your account settings.', 'webberzone-ai-account-connector' ),
					$config::VENDOR
				)
			)
		);
	}

	/**
	 * Whether the current screen is Settings → Connectors and the user can manage it.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private static function on_connectors_screen() {
		$screen = get_current_screen();
		return $screen && 'options-connectors' === $screen->id && current_user_can( 'manage_options' );
	}

	/**
	 * Whether wp_salt( 'auth' ) is backed by constants in wp-config.php.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private static function salts_in_config() {
		$salt = wp_salt( 'auth' );
		foreach ( array( 'auth_key', 'auth_salt' ) as $option ) {
			$value = get_site_option( $option );
			if ( is_string( $value ) && '' !== $value && false !== strpos( $salt, $value ) ) {
				return false;
			}
		}
		return true;
	}
}
