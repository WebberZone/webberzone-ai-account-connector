<?php
/**
 * Provider-specific wording used by the shared admin and Connectors code.
 *
 * @package WebberZone\AI_Account_Connector\Grok
 */

namespace WebberZone\AI_Account_Connector\Grok;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Holds everything the shared files need to know about this provider.
 *
 * The provider-independent classes (Admin, Connectors, OAuth_Client, Token_Store, Availability) and
 * connectors.js read everything provider-specific from here.
 *
 * @since 1.0.0
 */
class Config {


	/**
	 * Name of the service, used in error messages.
	 *
	 * @var string
	 */
	const VENDOR = 'xAI';

	/**
	 * Provider label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function label() {
		return __( 'Grok Account', 'webberzone-ai-account-connector' );
	}

	/**
	 * Sign-in button label and modal title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function sign_in_label() {
		return __( 'Sign in with xAI', 'webberzone-ai-account-connector' );
	}

	/**
	 * Which sign-in UI the Connectors card should show.
	 *
	 * @since 1.0.1
	 *
	 * @return string 'device' or 'browser'.
	 */
	public static function sign_in_flow() {
		return 'browser';
	}

	/**
	 * First browser sign-in step. Text inside <a></a> is linked to the consent screen.
	 *
	 * @since 1.0.1
	 *
	 * @return string
	 */
	public static function browser_step() {
		return __( '1. Approve access on <a>the xAI sign-in page</a>. Sign in with your X or xAI account if asked.', 'webberzone-ai-account-connector' );
	}

	/**
	 * Explains why a code has to be pasted back.
	 *
	 * @since 1.0.1
	 *
	 * @return string
	 */
	public static function browser_paste() {
		return __( '2. That page cannot return here, so it shows a one-time code (it may say to copy it into Grok Build). Paste the code below. It is valid for 5 minutes.', 'webberzone-ai-account-connector' );
	}

	/**
	 * Note shown before signing in. Text inside <a></a> is linked to the URL, when there is one.
	 *
	 * @since 1.0.0
	 *
	 * @return array{text: string, url: string}
	 */
	public static function note() {
		return array(
			'text' => __( 'Sign in with an xAI account that has SuperGrok or X Premium. Some plans can sign in but are refused by the API; if so, the error is shown when you generate content.', 'webberzone-ai-account-connector' ),
			'url'  => '',
		);
	}

	/**
	 * First sign-in step. Text inside <a></a> is linked to the device sign-in page.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function device_step() {
		return __( '1. Open <a>the xAI device sign-in page</a> and sign in.', 'webberzone-ai-account-connector' );
	}

	/**
	 * One-line account summary shown on the Connectors card.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $tokens Token data.
	 * @return string
	 */
	public static function account_summary( array $tokens ) {
		$email = (string) ( $tokens['email'] ?? '' );
		$name  = (string) ( $tokens['name'] ?? '' );
		if ( '' === $email ) {
			return '';
		}
		return '' !== $name
		/* translators: 1: account name, 2: account email. */
		? sprintf( __( 'Signed in as %1$s (%2$s).', 'webberzone-ai-account-connector' ), $name, $email )
		/* translators: %s: account email. */
		: sprintf( __( 'Signed in as %s.', 'webberzone-ai-account-connector' ), $email );
	}
}
