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
	 * First browser sign-in step. Text inside <a></a> is linked to the consent screen.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function browser_step() {
		return __( '1. Approve access on <a>the xAI sign-in page</a>. Sign in with your X or xAI account if asked.', 'webberzone-ai-account-connector' );
	}

	/**
	 * Explains why a code has to be pasted back.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function browser_paste() {
		return __( '2. That page cannot return here, so it shows a one-time code (it may say to copy it into Grok Build). Paste the code below. It is valid for 5 minutes.', 'webberzone-ai-account-connector' );
	}

	/**
	 * Warning shown on the connect dialog; the administrator must accept it before signing in.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] Paragraphs.
	 */
	public static function warning() {
		return array(
			__( 'This is not an official xAI integration. This plugin is independent and is not affiliated with, endorsed by or sponsored by xAI.', 'webberzone-ai-account-connector' ),
			__( 'xAI has no programme for third-party apps. This signs in the way xAI\'s own command-line tools do, using their public client. It may break xAI\'s terms, xAI may suspend or limit your account, and xAI may change or block it at any time. You use it entirely at your own risk.', 'webberzone-ai-account-connector' ),
			__( 'Connect only if you are the administrator who will use it, on a site you control. The token is stored on this site, and other code running here, such as plugins and themes, can still use it while it is connected.', 'webberzone-ai-account-connector' ),
		);
	}

	/**
	 * Label for the field the sign-in code is pasted into.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function paste_label() {
		return __( 'Sign-in code', 'webberzone-ai-account-connector' );
	}

	/**
	 * Where the user can see and remove this site's access. xAI has no such page to link to.
	 *
	 * @since 1.0.0
	 *
	 * @return string URL, or an empty string.
	 */
	public static function manage_url() {
		return '';
	}

	/**
	 * Label for the manage-access link.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function manage_label() {
		return '';
	}

	/**
	 * What to expect after approving, shown before the sign-in page opens.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function expect() {
		return __( 'After approving access, xAI shows a one-time code. Copy it, return here, and paste it below.', 'webberzone-ai-account-connector' );
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
