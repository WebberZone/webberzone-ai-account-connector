<?php
/**
 * Provider-specific wording used by the shared admin and Connectors code.
 *
 * @package WebberZone\AI_Account_Connector\ChatGPT
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

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
	const VENDOR = 'OpenAI';

	/**
	 * Provider label.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function label() {
		return __( 'ChatGPT Account', 'webberzone-ai-account-connector' );
	}

	/**
	 * Sign-in button label and modal title.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function sign_in_label() {
		return __( 'Sign in with ChatGPT', 'webberzone-ai-account-connector' );
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
			__( 'This plugin is independent and is not affiliated with, endorsed by or sponsored by OpenAI. It uses OpenAI\'s Sign in with ChatGPT programme for open-source apps, which is a preview.', 'webberzone-ai-account-connector' ),
			__( 'OpenAI\'s terms may not cover every kind of hosted site. Using your ChatGPT plan this way may break them, and OpenAI may restrict or suspend your account. Use it only for your own requests, never for other people\'s.', 'webberzone-ai-account-connector' ),
			__( 'Connect only if you are the administrator who will use it, on a site you control. The token is stored on this site, and other code running here, such as plugins and themes, can still use it while it is connected.', 'webberzone-ai-account-connector' ),
		);
	}

	/**
	 * First sign-in step. Text inside <a></a> is linked to the consent screen.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function browser_step() {
		return __( '1. Approve access on <a>the ChatGPT sign-in page</a>. Sign in with your ChatGPT account if asked.', 'webberzone-ai-account-connector' );
	}

	/**
	 * Explains why an address has to be pasted back.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function browser_paste() {
		return __( '2. After you approve, your browser shows an error page ("This site can\'t be reached") at an address starting with http://127.0.0.1:1455. That is expected: the address holds your sign-in. Copy the whole address from the address bar and paste it below. It is valid for 15 minutes.', 'webberzone-ai-account-connector' );
	}

	/**
	 * Label for the field the redirect address is pasted into.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function paste_label() {
		return __( 'Full address from the error page (starts with http://127.0.0.1)', 'webberzone-ai-account-connector' );
	}

	/**
	 * Where the user can see and remove this site's access, in ChatGPT settings.
	 *
	 * @since 1.0.0
	 *
	 * @return string URL.
	 */
	public static function manage_url() {
		$saved = get_option( OAuth::CLIENT_OPTION );
		$args  = array( 'view' => 'linked-apps' );
		if ( is_array( $saved ) && ! empty( $saved['client_id'] ) ) {
			$args['client_id'] = $saved['client_id'];
		}
		return add_query_arg( $args, 'https://chatgpt.com/settings/security' );
	}

	/**
	 * Label for the manage-access link.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function manage_label() {
		return __( 'Manage access in ChatGPT settings', 'webberzone-ai-account-connector' );
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
			'text' => __( 'Works with ChatGPT Plus and Pro plans. Usage counts against your plan.', 'webberzone-ai-account-connector' ),
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
		if ( '' === $email ) {
			return '';
		}
		/* translators: %s: account email. */
		return sprintf( __( 'Signed in as %s.', 'webberzone-ai-account-connector' ), $email );
	}
}
