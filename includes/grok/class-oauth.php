<?php
/**
 * Browser sign-in with xAI, and token refresh.
 *
 * @package WebberZone\AI_Account_Connector\Grok
 */

namespace WebberZone\AI_Account_Connector\Grok;

use RuntimeException;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Authorization Code + PKCE sign-in against auth.x.ai, and token refresh.
 *
 * This uses the public Grok CLI client. xAI only allows the loopback redirect
 * http://127.0.0.1:56121/callback, which a WordPress site cannot receive, so the
 * consent screen shows a code and the site exchanges that code with the PKCE verifier.
 *
 * @since 1.0.0
 */
class OAuth extends OAuth_Client {


	/**
	 * The xAI auth issuer.
	 *
	 * @var string
	 */
	const ISSUER = 'https://auth.x.ai';

	/**
	 * Public OAuth client ID used by Grok CLI-style agents.
	 *
	 * @var string
	 */
	const CLIENT_ID = 'b1a00492-073a-47ea-816f-4c329264a828';

	/**
	 * Requested scopes.
	 *
	 * @var string
	 */
	const SCOPE = 'openid profile email offline_access grok-cli:access api:access';

	/**
	 * Loopback redirect registered for the public client. Not received by this site.
	 *
	 * @var string
	 */
	const REDIRECT_URI = 'http://127.0.0.1:56121/callback';

	/**
	 * How long a sign-in attempt can be finished, in seconds.
	 *
	 * @var int
	 */
	const SIGN_IN_TTL = 300;

	/**
	 * Refresh access tokens this many seconds before they expire (they last about six hours).
	 *
	 * @var int
	 */
	const REFRESH_MARGIN = 3600;

	/**
	 * Starts a browser sign-in and returns the xAI consent URL.
	 *
	 * The PKCE verifier stays on the server. The browser only receives the authorize URL.
	 *
	 * @since 1.0.0
	 *
	 * @return array{authorize_url: string, expires_at: int}
	 * @throws RuntimeException When the pending sign-in cannot be stored.
	 */
	public static function start_sign_in() {
		return static::with_lock(
			static function () {
				$verifier  = self::pkce_verifier();
				$challenge = self::pkce_challenge( $verifier );
				$state     = self::pkce_verifier();
				$ttl       = self::SIGN_IN_TTL;
				$flow      = array(
					'flow_id'       => wp_generate_uuid4(),
					'code_verifier' => $verifier,
					'expires_at'    => time() + $ttl,
				);
				static::save_flow( $flow, $ttl );

				return array(
					'authorize_url' => self::authorize_url( $challenge, $state ),
					'expires_at'    => $flow['expires_at'],
				);
			}
		);
	}

	/**
	 * Exchanges the code shown on the xAI consent screen for tokens.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $code Authorization code copied from the consent screen.
	 * @return string 'connected'.
	 * @throws RuntimeException When the attempt expired, the code was rejected, or the response was incomplete.
	 */
	public static function exchange_authorization_code( $code ) {
		$code = preg_replace( '/\s+/', '', (string) $code );
		if ( ! is_string( $code ) || '' === $code || strlen( $code ) > 2048 ) {
			throw new RuntimeException( esc_html__( 'Paste the code shown on the xAI sign-in page.', 'webberzone-ai-account-connector' ) );
		}

		$flow = static::with_lock(
			static function () {
				$flow = static::fresh_flow();
				if ( ! is_array( $flow ) || empty( $flow['code_verifier'] ) || $flow['expires_at'] < time() ) {
					delete_transient( static::flow_key() );
					throw new RuntimeException( esc_html__( 'The sign-in expired. Start again and approve access once more.', 'webberzone-ai-account-connector' ) );
				}
				return $flow;
			}
		);

		$response = static::post_form(
			self::ISSUER . '/oauth2/token',
			array(
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => self::REDIRECT_URI,
				'client_id'     => self::CLIENT_ID,
				'code_verifier' => $flow['code_verifier'],
			)
		);
		$status   = (int) wp_remote_retrieve_response_code( $response );
		$error    = static::error_code( json_decode( (string) wp_remote_retrieve_body( $response ), true ) );
		if ( 'invalid_grant' === $error ) {
			throw new RuntimeException( esc_html__( 'That code was not accepted. Start again, approve access, and paste the new code.', 'webberzone-ai-account-connector' ) );
		}

		$tokens = static::decode( $response, $status );
		static::with_lock(
			static function () use ( $flow, $tokens ) {
				if ( ! static::flow_is_current( $flow ) ) {
					throw new RuntimeException( esc_html__( 'The sign-in was cancelled or replaced.', 'webberzone-ai-account-connector' ) );
				}
				delete_transient( static::flow_key() );
				static::store_tokens( $tokens );
			}
		);
		return 'connected';
	}

	/**
	 * Builds the consent-screen URL.
	 *
	 * `plan=generic` is required for this public client. The referrer is only an attribution label.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $challenge PKCE S256 challenge.
	 * @param  string $state     OAuth state. Not checked on the paste path; PKCE binds the code.
	 * @return string
	 */
	private static function authorize_url( $challenge, $state ) {
		return add_query_arg(
			array(
				'response_type'         => 'code',
				'client_id'             => self::CLIENT_ID,
				'redirect_uri'          => self::REDIRECT_URI,
				'scope'                 => self::SCOPE,
				'code_challenge'        => $challenge,
				'code_challenge_method' => 'S256',
				'state'                 => $state,
				'plan'                  => 'generic',
				'referrer'              => 'webberzone',
			),
			self::ISSUER . '/oauth2/authorize'
		);
	}

	/**
	 * A PKCE verifier: 32 random bytes, base64url, no padding (43 characters).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function pkce_verifier() {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE encoding (RFC 7636), not obfuscation.
		return rtrim( strtr( base64_encode( random_bytes( 32 ) ), '+/', '-_' ), '=' );
	}

	/**
	 * S256 challenge for a PKCE verifier.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $verifier PKCE verifier.
	 * @return string
	 */
	private static function pkce_challenge( $verifier ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE encoding (RFC 7636), not obfuscation.
		return rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array $tokens Current token data.
	 */
	protected static function request_refresh( array $tokens ) {
		return static::post_form(
			self::ISSUER . '/oauth2/token',
			array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $tokens['refresh_token'],
				'client_id'     => self::CLIENT_ID,
			)
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array $tokens Current token data.
	 */
	protected static function request_revoke( array $tokens ) {
		return static::post_form(
			self::ISSUER . '/oauth2/revoke',
			array(
				'token'           => $tokens['refresh_token'],
				'token_type_hint' => 'refresh_token',
				'client_id'       => self::CLIENT_ID,
			)
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param  array $body     Token endpoint response.
	 * @param  array $previous Previously stored tokens, used for fields the response omits.
	 * @throws RuntimeException When required fields are missing.
	 */
	protected static function store_tokens( array $body, array $previous = array() ) {
		if ( empty( $body['access_token'] ) ) {
			throw new RuntimeException( esc_html__( 'The token response was missing an access token.', 'webberzone-ai-account-connector' ) );
		}
		$id_token = (string) ( $body['id_token'] ?? ( $previous['id_token'] ?? '' ) );
		$claims   = static::jwt_claims( $id_token );

		$tokens = array(
			'access_token'  => (string) $body['access_token'],
			'refresh_token' => (string) ( $body['refresh_token'] ?? ( $previous['refresh_token'] ?? '' ) ),
			'id_token'      => $id_token,
			'expires_at'    => isset( $body['expires_in'] ) ? time() + (int) $body['expires_in'] : (int) ( static::jwt_claims( $body['access_token'] )['exp'] ?? 0 ),
			'email'         => (string) ( $claims['email'] ?? ( $previous['email'] ?? '' ) ),
			'name'          => (string) ( $claims['name'] ?? ( $previous['name'] ?? '' ) ),
		);
		if ( '' === $tokens['refresh_token'] ) {
			throw new RuntimeException( esc_html__( 'The token response did not include a refresh token.', 'webberzone-ai-account-connector' ) );
		}
		Token_Store::save( $tokens );
		return Token_Store::get() ?? $tokens;
	}
}
