<?php
/**
 * ChatGPT sign-in and token refresh.
 *
 * @package WebberZone\AI_Account_Connector\ChatGPT
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

use RuntimeException;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Implements OpenAI's Sign in with ChatGPT flow for open-source apps: authorization code with PKCE,
 * dynamic client registration and a loopback redirect, plus token refresh.
 *
 * The loopback address cannot be reached from a remote site, so the administrator pastes the address
 * the browser ends up on back into WordPress, which completes the code exchange.
 *
 * @since 1.0.0
 */
class OAuth extends OAuth_Client {


	/**
	 * OpenAI auth issuer.
	 *
	 * @var string
	 */
	const ISSUER = 'https://auth.openai.com';

	/**
	 * Authorization endpoint.
	 *
	 * @var string
	 */
	const AUTHORIZE_URL = 'https://auth.openai.com/api/accounts/authorize';

	/**
	 * Token endpoint.
	 *
	 * @var string
	 */
	const TOKEN_URL = 'https://auth.openai.com/api/accounts/oauth/token';

	/**
	 * Revocation endpoint.
	 *
	 * @var string
	 */
	const REVOKE_URL = 'https://auth.openai.com/api/accounts/oauth/revoke';

	/**
	 * Placeholder client ID that makes OpenAI register this installation during the first sign-in.
	 *
	 * @var string
	 */
	const REGISTRATION_CLIENT_ID = 'dynamic_agent_client';

	/**
	 * App name shown to the user on OpenAI's consent screen.
	 *
	 * @var string
	 */
	const AGENT_NAME = 'WebberZone AI Account Connector';

	/**
	 * Loopback redirect URI. Nothing needs to listen on it.
	 *
	 * @var string
	 */
	const REDIRECT_URI = 'http://127.0.0.1:1455/auth/callback';

	/**
	 * API the tokens are issued for.
	 *
	 * @var string
	 */
	const RESOURCE = 'https://api.openai.com/v1'; // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- OAuth resource identifier, not an API call.

	/**
	 * Requested scopes.
	 *
	 * @var string
	 */
	const SCOPE = 'openid profile email offline_access resource.invoke chatgpt.tokens.use.direct';

	/**
	 * Scope that allows inference against the user's ChatGPT plan.
	 *
	 * @var string
	 */
	const REQUIRED_SCOPE = 'chatgpt.tokens.use.direct';

	/**
	 * Option holding this site's stable host ID.
	 *
	 * @var string
	 */
	const HOST_OPTION = 'wzap_chatgpt_host_id';

	/**
	 * Option holding the issued client ID, kept across disconnects so a reconnect reuses it.
	 *
	 * @var string
	 */
	const CLIENT_OPTION = 'wzap_chatgpt_client';

	/**
	 * Refresh access tokens this many seconds before they expire.
	 *
	 * @var int
	 */
	const REFRESH_MARGIN = 300;

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @return array Authorization URL, redirect URI and expiry timestamp.
	 */
	public static function start_sign_in() {
		return static::with_lock(
			static function () {
				$saved = get_option( self::CLIENT_OPTION );
				$saved = is_array( $saved ) && ! empty( $saved['client_id'] ) ? $saved : array();
				if ( ! empty( $saved['pending'] ) ) {
					// The previous attempt with this client never completed, so register afresh.
					delete_option( self::CLIENT_OPTION );
					$saved = array();
				}
				if ( $saved ) {
					update_option( self::CLIENT_OPTION, $saved + array( 'pending' => true ), false );
				}

				$verifier = self::random_token( 48 );
				$flow     = array(
					'flow_id'       => wp_generate_uuid4(),
					'state'         => self::random_token( 24 ),
					'nonce'         => self::random_token( 24 ),
					'code_verifier' => $verifier,
					'client_id'     => $saved['client_id'] ?? '',
					'expires_at'    => time() + static::FLOW_TTL,
				);
				static::save_flow( $flow, static::FLOW_TTL );

				$query = array(
					'response_type'         => 'code',
					'client_id'             => $saved ? $saved['client_id'] : self::REGISTRATION_CLIENT_ID,
					'ext_agent_host_id'     => self::host_id(),
					'redirect_uri'          => self::REDIRECT_URI,
					'scope'                 => self::SCOPE,
					'resource'              => self::RESOURCE,
					'state'                 => $flow['state'],
					'nonce'                 => $flow['nonce'],
					'code_challenge_method' => 'S256',
					'code_challenge'        => self::base64url( hash( 'sha256', $verifier, true ) ),
				);
				if ( $saved ) {
					if ( ! empty( $saved['email'] ) ) {
						$query['login_hint'] = $saved['email'];
					}
				} else {
					$query['agent_name_hint'] = self::AGENT_NAME;
				}

				return array(
					'authorize_url' => self::AUTHORIZE_URL . '?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ),
					'redirect_uri'  => self::REDIRECT_URI,
					'expires_at'    => $flow['expires_at'],
					'interval'      => 0,
				);
			}
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param  string $input Address, or query string, the browser was redirected to.
	 * @return string 'connected'.
	 * @throws RuntimeException When the flow expired, was denied, or OpenAI returned an error.
	 */
	public static function exchange_authorization_code( $input ) {
		$params = self::parse_callback( (string) $input );
		$flow   = static::with_lock(
			static function () {
				$flow = static::fresh_flow();
				if ( ! is_array( $flow ) || ( $flow['expires_at'] ?? 0 ) < time() ) {
					delete_transient( static::flow_key() );
					throw new RuntimeException( esc_html__( 'The sign-in expired. Start again.', 'webberzone-ai-account-connector' ), 410 );
				}
				return $flow;
			}
		);

		if ( empty( $params['state'] ) || ! hash_equals( (string) $flow['state'], (string) $params['state'] ) ) {
			throw new RuntimeException( esc_html__( 'That address does not belong to this sign-in. Start again.', 'webberzone-ai-account-connector' ), 0 );
		}
		if ( ! empty( $params['error'] ) ) {
			static::discard_flow( $flow );
			throw new RuntimeException(
				esc_html(
					sprintf(
						/* translators: %s: error code. */
						__( 'OpenAI did not grant access (%s).', 'webberzone-ai-account-connector' ),
						$params['error']
					)
				),
				410
			);
		}
		$client_id = (string) ( $params['client_id'] ?? ( $flow['client_id'] ?? '' ) );
		if ( empty( $params['code'] ) || '' === $client_id || self::REGISTRATION_CLIENT_ID === $client_id || ( ! empty( $flow['client_id'] ) && $flow['client_id'] !== $client_id ) ) {
			throw new RuntimeException( esc_html__( 'The address is missing the authorization code. Paste the full address from your browser.', 'webberzone-ai-account-connector' ), 0 );
		}
		self::require_scope( (string) ( $params['scope'] ?? self::REQUIRED_SCOPE ) );

		try {
			$response = static::post_form(
				self::TOKEN_URL,
				array(
					'grant_type'    => 'authorization_code',
					'client_id'     => $client_id,
					'code'          => $params['code'],
					'code_verifier' => $flow['code_verifier'],
					'redirect_uri'  => self::REDIRECT_URI,
					'resource'      => self::RESOURCE,
				)
			);
			$tokens   = static::decode( $response );
			self::require_scope( (string) ( $tokens['scope'] ?? '' ) );
			$claims = static::jwt_claims( $tokens['id_token'] ?? '' );
			// The tokens come straight from the token endpoint over TLS, so the ID token signature is not re-verified.
			if ( self::ISSUER !== ( $claims['iss'] ?? '' ) || (int) ( $claims['exp'] ?? 0 ) < time() || ! hash_equals( (string) $flow['nonce'], (string) ( $claims['nonce'] ?? '' ) ) || ! in_array( $client_id, (array) ( $claims['aud'] ?? array() ), true ) ) {
				throw new RuntimeException( esc_html__( 'The sign-in response could not be verified. Start again.', 'webberzone-ai-account-connector' ), 0 );
			}
			$tokens['client_id'] = $client_id;
			static::with_lock(
				static function () use ( $flow, $tokens, $client_id ) {
					if ( ! static::flow_is_current( $flow ) ) {
						throw new RuntimeException( esc_html__( 'The sign-in was cancelled or replaced.', 'webberzone-ai-account-connector' ), 0 );
					}
					delete_transient( static::flow_key() );
					$stored = static::store_tokens( $tokens );
					update_option(
						self::CLIENT_OPTION,
						array(
							'client_id' => $client_id,
							'email'     => (string) ( $stored['email'] ?? '' ),
						),
						false
					);
				}
			);
		} catch ( RuntimeException $e ) {
			// Authorization codes are single-use, so a failed exchange cannot be retried.
			static::discard_flow( $flow );
			if ( ! empty( $flow['client_id'] ) ) {
				delete_option( self::CLIENT_OPTION );
			}
			throw new RuntimeException( $e->getMessage(), 410, $e ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Message is already escaped; preserve the original exception.
		}

		return 'connected';
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
			self::TOKEN_URL,
			array(
				'grant_type'    => 'refresh_token',
				'client_id'     => $tokens['client_id'],
				'refresh_token' => $tokens['refresh_token'],
				'resource'      => self::RESOURCE,
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
			self::REVOKE_URL,
			array(
				'token'           => $tokens['refresh_token'],
				'token_type_hint' => 'refresh_token',
				'client_id'       => $tokens['client_id'] ?? '',
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
		$client_id = $body['client_id'] ?? ( $previous['client_id'] ?? '' );
		if ( empty( $body['access_token'] ) || '' === $client_id ) {
			throw new RuntimeException( esc_html__( 'The token response was missing required fields.', 'webberzone-ai-account-connector' ) );
		}
		$id_token = $body['id_token'] ?? ( $previous['id_token'] ?? '' );
		$claims   = static::jwt_claims( $id_token );

		$tokens = array(
			'access_token'  => $body['access_token'],
			'refresh_token' => $body['refresh_token'] ?? ( $previous['refresh_token'] ?? '' ),
			'id_token'      => $id_token,
			'client_id'     => $client_id,
			'expires_at'    => isset( $body['expires_in'] ) ? time() + (int) $body['expires_in'] : (int) ( static::jwt_claims( $body['access_token'] )['exp'] ?? 0 ),
			'email'         => $claims['email'] ?? ( $previous['email'] ?? '' ),
		);
		if ( '' === $tokens['refresh_token'] ) {
			throw new RuntimeException( esc_html__( 'The token response did not include a refresh token.', 'webberzone-ai-account-connector' ) );
		}
		Token_Store::save( $tokens );
		return Token_Store::get() ?? $tokens;
	}

	/**
	 * Splits the pasted redirect address, or query string, into its parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $input Pasted text.
	 * @return array<string, string>
	 * @throws RuntimeException When nothing usable was pasted.
	 */
	private static function parse_callback( $input ) {
		$input = trim( $input, " \n\r\t\v\0" );
		if ( false !== strpos( $input, '?' ) ) {
			$query = (string) wp_parse_url( $input, PHP_URL_QUERY );
		} elseif ( false !== strpos( $input, '=' ) ) {
			$query = ltrim( $input, '?' );
		} else {
			throw new RuntimeException( esc_html__( 'Paste the full address from your browser, starting with http://127.0.0.1.', 'webberzone-ai-account-connector' ), 0 );
		}
		parse_str( $query, $params );
		return array_map( 'strval', array_filter( $params, 'is_scalar' ) );
	}

	/**
	 * Checks that the granted scopes allow inference against the ChatGPT plan.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $scopes Space-separated scopes.
	 * @throws RuntimeException When plan usage was not granted.
	 */
	private static function require_scope( $scopes ) {
		if ( ! in_array( self::REQUIRED_SCOPE, preg_split( '/\s+/', trim( $scopes, " \n\r\t\v\0" ) ), true ) ) {
			throw new RuntimeException( esc_html__( 'OpenAI did not grant ChatGPT plan usage. Your plan may not support it.', 'webberzone-ai-account-connector' ), 0 );
		}
	}

	/**
	 * Returns this site's stable host ID, creating it on first use.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function host_id() {
		$id = get_option( self::HOST_OPTION );
		if ( ! is_string( $id ) || '' === $id ) {
			$id = 'urn:uuid:' . wp_generate_uuid4();
			update_option( self::HOST_OPTION, $id, false );
		}
		return $id;
	}

	/**
	 * Returns random bytes as unpadded base64url.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $length Number of random bytes.
	 * @return string
	 */
	private static function random_token( $length ) {
		return self::base64url( random_bytes( $length ) );
	}

	/**
	 * Encodes bytes as unpadded base64url.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $bytes Raw bytes.
	 * @return string
	 */
	private static function base64url( $bytes ) {
		return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE encoding.
	}
}
