<?php
/**
 * Settings → Connectors integration.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Replaces core's API-key card for this provider on Settings → Connectors with a sign-in card.
 *
 * @since 1.0.0
 */
abstract class Connectors {

	const PROVIDER_ID = '';
	const TOKEN_STORE = null;
	const CONFIG      = null;
	const ADMIN       = null;
	const PREFIX      = '';


	/**
	 * Data passed to the script module, including all of its (translated) strings.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data Existing data.
	 * @return array
	 */
	public static function module_data( $data ) {
		$token_store = static::TOKEN_STORE;
		$config      = static::CONFIG;
		$admin       = static::ADMIN;
		$tokens      = $token_store::get();
		$note        = $config::note();
		$flow        = $config::sign_in_flow();
		return array_merge(
			(array) $data,
			array(
				'prefix'     => static::PREFIX,
				'slug'       => static::PROVIDER_ID,
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( $admin::NONCE ),
				'connected'  => null !== $tokens,
				'account'    => null !== $tokens ? $config::account_summary( $tokens ) : '',
				'note'       => $note['text'],
				'noteUrl'    => $note['url'],
				'signInFlow' => $flow,
				'strings'    => array(
					'signIn'        => $config::sign_in_label(),
					'deviceStep'    => $config::device_step(),
					'browserStep'   => 'browser' === $flow ? $config::browser_step() : '',
					'browserPaste'  => 'browser' === $flow ? $config::browser_paste() : '',
					'enterCode'     => __( '2. Enter this code:', 'webberzone-ai-account-connector' ),
					'requesting'    => __( 'Requesting a sign-in code…', 'webberzone-ai-account-connector' ),
					'opening'       => __( 'Opening the sign-in page…', 'webberzone-ai-account-connector' ),
					'waiting'       => __( 'Waiting for you to approve the sign-in. The code expires after up to 15 minutes.', 'webberzone-ai-account-connector' ),
					'popupBlocked'  => __( 'Your browser blocked the sign-in page. Use the link above.', 'webberzone-ai-account-connector' ),
					'pasteLabel'    => __( 'Sign-in code', 'webberzone-ai-account-connector' ),
					'connect'       => __( 'Connect', 'webberzone-ai-account-connector' ),
					'copy'          => __( 'Copy', 'webberzone-ai-account-connector' ),
					'copied'        => __( 'Copied', 'webberzone-ai-account-connector' ),
					'tryAgain'      => __( 'Try again', 'webberzone-ai-account-connector' ),
					'cancel'        => __( 'Cancel', 'webberzone-ai-account-connector' ),
					'connected'     => __( 'Connected', 'webberzone-ai-account-connector' ),
					'disconnect'    => __( 'Disconnect', 'webberzone-ai-account-connector' ),
					'requestFailed' => __( 'Request failed.', 'webberzone-ai-account-connector' ),
				),
			)
		);
	}
}
