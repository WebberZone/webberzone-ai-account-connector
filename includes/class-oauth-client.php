<?php
/**
 * Shared OAuth plumbing: token refresh with locking, pending sign-ins and HTTP helpers.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

use RuntimeException;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Base class for the provider's OAuth implementation.
 *
 * Subclasses implement the provider-specific sign-in, the refresh request and how token
 * responses are stored. Everything else, including the refresh lock, lives here.
 *
 * @since 1.0.0
 */
abstract class OAuth_Client {

	const TOKEN_STORE = null;
	const CONFIG      = null;
	const FLOW_PREFIX = '';

	/**
	 * Fallback lifetime of a pending sign-in, in seconds.
	 *
	 * @var int
	 */
	const FLOW_TTL = 900;

	/**
	 * Option used as a refresh lock.
	 *
	 * @var string
	 */
	const LOCK_OPTION = '';

	/**
	 * Refresh access tokens this many seconds before they expire. Subclasses override this.
	 *
	 * @var int
	 */
	const REFRESH_MARGIN = 300;

	/**
	 * Seconds to wait before retrying a failed refresh while the access token is still valid.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	const REFRESH_BACKOFF = 60;

	/**
	 * Starts a browser sign-in and stores the pending flow for the current user.
	 *
	 * @since 1.0.0
	 *
	 * @return array Authorization URL and expiry timestamp.
	 * @throws RuntimeException When the pending sign-in cannot be stored.
	 */
	abstract public static function start_sign_in();

	/**
	 * Finishes a sign-in from what the user pasted back from the provider, and stores the tokens.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $input Pasted code or address.
	 * @return string 'connected'.
	 * @throws RuntimeException When the sign-in expired, was denied, or the provider returned an error.
	 */
	abstract public static function exchange_authorization_code( $input );

	/**
	 * Checks that the provider still honours the stored sign-in, at most every ten minutes.
	 *
	 * A forced refresh clears the tokens when the provider says the grant was revoked, so the Connectors screen stops showing a dead connection as connected. Network errors leave the tokens alone.
	 *
	 * @since 1.0.0
	 */
	public static function verify_connection() {
		$token_store = static::TOKEN_STORE;
		$key         = static::LOCK_OPTION . '_checked';
		if ( ! $token_store::is_connected() || get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, 10 * MINUTE_IN_SECONDS );
		try {
			static::refresh( true );
		} catch ( RuntimeException $e ) {
			unset( $e );
		}
	}

	/**
	 * Sends the refresh-token request.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $tokens Current token data.
	 * @return array HTTP response.
	 * @throws RuntimeException On transport errors.
	 */
	abstract protected static function request_refresh( array $tokens );

	/**
	 * Sends the request that revokes the refresh token.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $tokens Current token data.
	 * @return array HTTP response.
	 * @throws RuntimeException On transport errors.
	 */
	abstract protected static function request_revoke( array $tokens );

	/**
	 * Saves a token response.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $body     Token endpoint response.
	 * @param  array $previous Previously stored tokens, used for fields the response omits.
	 * @return array Stored token data.
	 * @throws RuntimeException When required fields are missing.
	 */
	abstract protected static function store_tokens( array $body, array $previous = array() );

	/**
	 * Abandons the current user's pending sign-in.
	 *
	 * @since 1.0.0
	 */
	public static function cancel_sign_in() {
		static::with_lock(
			static function () {
				delete_transient( static::flow_key() );
			}
		);
	}

	/**
	 * Disconnects the account, abandons the pending flow and revokes the refresh token with the provider.
	 *
	 * The local tokens are deleted first, so the site is disconnected even when revocation fails.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the refresh token was revoked or there was none to revoke.
	 */
	public static function disconnect() {
		$tokens = static::with_lock(
			static function () {
				$token_store = static::TOKEN_STORE;
				$tokens      = static::fresh_tokens();
				delete_transient( static::flow_key() );
				$token_store::clear();
				return $tokens;
			}
		);
		return null === $tokens || static::revoke( $tokens );
	}

	/**
	 * Revokes a refresh token with the provider.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $tokens Token data.
	 * @return bool True if the provider accepted the revocation or there was no refresh token.
	 */
	public static function revoke( array $tokens ) {
		if ( empty( $tokens['refresh_token'] ) ) {
			return true;
		}
		try {
			$status = (int) wp_remote_retrieve_response_code( static::request_revoke( $tokens ) );
		} catch ( RuntimeException $e ) {
			return false;
		}
		return $status >= 200 && $status < 300;
	}

	/**
	 * Runs an account state change while holding the shared lock.
	 *
	 * @since 1.0.0
	 *
	 * @param  callable $callback State change to perform.
	 * @return mixed Callback result.
	 * @throws RuntimeException When the lock cannot be acquired.
	 */
	public static function with_lock( callable $callback ) {
		$owner = null;
		for ( $i = 0; $i < 120 && null === $owner; $i++ ) {
			$owner = static::acquire_lock();
			if ( null === $owner ) {
				usleep( 250000 );
			}
		}
		if ( null === $owner ) {
			throw new RuntimeException( esc_html__( 'Timed out waiting for account access.', 'webberzone-ai-account-connector' ), 503 );
		}
		try {
			return $callback();
		} finally {
			static::release_lock( $owner );
		}
	}

	/**
	 * Reads the flow without a stale per-request cache entry.
	 *
	 * @since 1.0.0
	 *
	 * @return array|null Pending flow.
	 */
	protected static function fresh_flow() {
		$token_store = static::TOKEN_STORE;
		$key         = static::flow_key();
		if ( ! wp_using_ext_object_cache() ) {
			wp_cache_delete( $key, 'transient' );
			wp_cache_delete( '_transient_' . $key, 'options' );
		}
		return $token_store::decrypt( get_transient( $key ) );
	}

	/**
	 * Checks that a response belongs to the active flow.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $flow Flow captured before the provider request.
	 * @return bool
	 */
	protected static function flow_is_current( array $flow ) {
		$current = static::fresh_flow();
		return is_array( $current ) && isset( $flow['flow_id'], $current['flow_id'] ) && $flow['flow_id'] === $current['flow_id'];
	}

	/**
	 * Discards a flow only if it is still active.
	 *
	 * @since 1.0.0
	 *
	 * @param array $flow Flow captured before the provider request.
	 */
	protected static function discard_flow( array $flow ) {
		static::with_lock(
			static function () use ( $flow ) {
				if ( static::flow_is_current( $flow ) ) {
					delete_transient( static::flow_key() );
				}
			}
		);
	}

	/**
	 * Returns the OAuth error code from a decoded response body.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $body Decoded response body.
	 * @return string Error code, or an empty string.
	 */
	protected static function error_code( $body ) {
		$error = is_array( $body ) ? ( $body['error'] ?? '' ) : '';
		if ( is_array( $error ) ) {
			$error = $error['code'] ?? '';
		}
		return is_string( $error ) ? $error : '';
	}

	/**
	 * Stores the pending flow, encrypted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $flow Pending flow.
	 * @param int   $ttl  Lifetime in seconds.
	 */
	protected static function save_flow( array $flow, $ttl ) {
		$token_store = static::TOKEN_STORE;
		set_transient( static::flow_key(), $token_store::encrypt( $flow ), $ttl );
	}

	/**
	 * Returns tokens that are valid for a while longer, refreshing if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return array Token data.
	 * @throws RuntimeException When not connected or the refresh fails.
	 */
	public static function get_valid_tokens() {
		$token_store = static::TOKEN_STORE;
		$config      = static::CONFIG;
		$tokens      = $token_store::get();
		if ( null === $tokens ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
					/* translators: %s: provider label. */
						__( '%s is not connected. Sign in under Settings → Connectors.', 'webberzone-ai-account-connector' ),
						$config::label()
					)
				)
			);
		}
		if ( static::is_expiring( $tokens ) && ! static::in_backoff( $tokens ) ) {
			$tokens = static::refresh( false );
		}
		return $tokens;
	}

	/**
	 * Refreshes the access token.
	 *
	 * Refresh tokens may rotate, so only one request may refresh at a time; others wait for its result.
	 * If an unforced refresh fails while the access token is still valid, that token is returned and
	 * refreshing pauses for REFRESH_BACKOFF seconds.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool        $force        Refresh even if the current token is not about to expire.
	 * @param  string|null $access_token Access token rejected by the provider.
	 * @return array Token data.
	 * @throws RuntimeException When not connected or the refresh fails.
	 */
	public static function refresh( $force, $access_token = null ) {
		$token_store = static::TOKEN_STORE;
		$config      = static::CONFIG;
		$before      = static::fresh_tokens();
		if ( null === $before ) {
			throw new RuntimeException( esc_html( self::not_connected_message() ) );
		}

		if ( null !== $access_token && $before['access_token'] !== $access_token ) {
			return $before;
		}

		$owner = static::acquire_lock();
		if ( null === $owner ) {
			for ( $i = 0; $i < 120; $i++ ) {
				usleep( 250000 );
				$current = static::fresh_tokens();
				if ( null !== $current && $current['access_token'] !== $before['access_token'] ) {
					return $current;
				}
				$owner = static::acquire_lock();
				if ( null !== $owner ) {
					break;
				}
			}
			if ( null === $owner ) {
				throw new RuntimeException( esc_html__( 'Timed out waiting for a token refresh.', 'webberzone-ai-account-connector' ) );
			}
		}

		try {
			$tokens = static::fresh_tokens();
			if ( null === $tokens ) {
				throw new RuntimeException( esc_html( self::not_connected_message() ) );
			}
			if ( $tokens['access_token'] !== $before['access_token'] || ( ! $force && ( ! static::is_expiring( $tokens ) || static::in_backoff( $tokens ) ) ) ) {
				return $tokens;
			}

			try {
				$response = static::request_refresh( $tokens );
			} catch ( RuntimeException $e ) {
				return static::refresh_failed( $tokens, $force, $e );
			}
			$status = (int) wp_remote_retrieve_response_code( $response );
			$error  = static::error_code( json_decode( (string) wp_remote_retrieve_body( $response ), true ) );
			if ( 401 === $status || ( 400 === $status && ( in_array( $error, array( 'invalid_grant', 'invalid_refresh_token', 'token_expired' ), true ) || 0 === strpos( $error, 'refresh_token_' ) ) ) ) {
				$token_store::clear();
				throw new RuntimeException(
					esc_html(
						sprintf(
						/* translators: %s: provider label. */
							__( 'Your %s session has expired or was revoked. Sign in again under Settings → Connectors.', 'webberzone-ai-account-connector' ),
							$config::label()
						)
					)
				);
			}
			try {
				return static::store_tokens( static::decode( $response, $status ), $tokens );
			} catch ( RuntimeException $e ) {
				return static::refresh_failed( $tokens, $force, $e );
			}
		} finally {
			static::release_lock( $owner );
		}
	}

	/**
	 * Handles a failed refresh: keeps using a still-valid access token, or rethrows.
	 *
	 * Must be called while holding the lock.
	 *
	 * @since 1.0.0
	 *
	 * @param  array            $tokens Current token data.
	 * @param  bool             $force  Whether the refresh was forced because the provider rejected the access token.
	 * @param  RuntimeException $e      The failure.
	 * @return array Current token data, marked to pause refreshing.
	 * @throws RuntimeException When the refresh was forced or the access token has expired.
	 */
	protected static function refresh_failed( array $tokens, $force, RuntimeException $e ) {
		$token_store = static::TOKEN_STORE;
		if ( $force || static::is_expiring( $tokens, 0 ) ) {
			throw $e;
		}
		$tokens['retry_after'] = time() + static::REFRESH_BACKOFF;
		$token_store::save( $tokens );
		return $tokens;
	}

	/**
	 * Whether refreshing is paused after a failure and the access token is still valid.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $tokens Token data.
	 * @return bool
	 */
	protected static function in_backoff( array $tokens ) {
		return (int) ( $tokens['retry_after'] ?? 0 ) > time() && ! static::is_expiring( $tokens, 0 );
	}

	/**
	 * Decodes the claims of a JWT without verifying it.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $jwt JSON Web Token.
	 * @return array Claims.
	 */
	public static function jwt_claims( $jwt ) {
		$parts = explode( '.', (string) $jwt );
		if ( count( $parts ) < 2 ) {
			return array();
		}
		$json   = base64_decode( strtr( $parts[1], '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $parts[1] ) % 4 ) % 4 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- JWT payload.
		$claims = json_decode( (string) $json, true );
		return is_array( $claims ) ? $claims : array();
	}

	/**
	 * Whether the access token expires within the refresh margin.
	 *
	 * Uses the stored expiry when there is one, otherwise the access token's own exp claim.
	 *
	 * @since 1.0.0
	 *
	 * @param  array    $tokens Token data.
	 * @param  int|null $margin Seconds before expiry that count as expiring; REFRESH_MARGIN when null, 0 for expired.
	 * @return bool
	 */
	protected static function is_expiring( array $tokens, $margin = null ) {
		$exp = (int) ( $tokens['expires_at'] ?? 0 );
		if ( $exp <= 0 ) {
			$exp = (int) ( static::jwt_claims( $tokens['access_token'] )['exp'] ?? 0 );
		}
		return $exp > 0 && $exp - ( null === $margin ? static::REFRESH_MARGIN : (int) $margin ) <= time();
	}

	/**
	 * Takes the refresh lock.
	 *
	 * Uses INSERT IGNORE directly: add_option() upserts and trusts the per-request option cache, so it is not atomic.
	 * An expired lock is removed first, so a crashed request cannot hold it for more than 60 seconds.
	 *
	 * @since 1.0.0
	 *
	 * @phpstan-impure
	 *
	 * @return string|null Lock owner when acquired.
	 */
	protected static function acquire_lock() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND CAST(SUBSTRING_INDEX(option_value, ':', 1) AS UNSIGNED) < %d", static::LOCK_OPTION, time() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$owner    = (string) ( time() + 60 ) . ':' . wp_generate_uuid4();
		$acquired = (bool) $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')", static::LOCK_OPTION, $owner ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( static::LOCK_OPTION, 'options' );
		return $acquired ? $owner : null;
	}

	/**
	 * Releases the refresh lock.
	 *
	 * @since 1.0.0
	 *
	 * @param string $owner Lock owner.
	 */
	protected static function release_lock( $owner ) {
		global $wpdb;
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Atomic lock release.
			$wpdb->options,
			array(
				'option_name'  => static::LOCK_OPTION,
				'option_value' => $owner,
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( static::LOCK_OPTION, 'options' );
	}

	/**
	 * Reads the tokens from the database, bypassing this request's option cache, which another request may have made stale.
	 *
	 * @since 1.0.0
	 *
	 * @return array|null
	 */
	protected static function fresh_tokens() {
		$token_store = static::TOKEN_STORE;
		wp_cache_delete( $token_store::OPTION, 'options' );
		return $token_store::get();
	}

	/**
	 * Transient key for the current user's pending sign-in.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected static function flow_key() {
		return static::FLOW_PREFIX . get_current_user_id();
	}

	/**
	 * Sends a form-encoded POST request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url  URL.
	 * @param  array  $data Form fields.
	 * @return array HTTP response.
	 * @throws RuntimeException On transport errors.
	 */
	protected static function post_form( $url, array $data ) {
		return static::post( $url, array( 'Accept' => 'application/json' ), $data );
	}

	/**
	 * Sends a POST request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string       $url     URL.
	 * @param  array        $headers Request headers.
	 * @param  array|string $body    Request body.
	 * @return array HTTP response.
	 * @throws RuntimeException On transport errors.
	 */
	protected static function post( $url, array $headers, $body ) {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => $headers,
				'body'    => $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( esc_html( $response->get_error_message() ), 503 );
		}
		return $response;
	}

	/**
	 * Decodes a JSON response, turning HTTP errors into exceptions.
	 *
	 * @since 1.0.0
	 *
	 * @param  array    $response HTTP response.
	 * @param  int|null $status   HTTP status code; read from the response when null.
	 * @return array Decoded body.
	 * @throws RuntimeException On HTTP or decoding errors.
	 */
	protected static function decode( $response, $status = null ) {
		$config = static::CONFIG;
		$status = null === $status ? (int) wp_remote_retrieve_response_code( $response ) : $status;
		$raw    = (string) wp_remote_retrieve_body( $response );
		$body   = json_decode( $raw, true );
		if ( $status < 200 || $status >= 300 ) {
			$code   = static::error_code( $body );
			$desc   = is_array( $body ) ? (string) ( $body['error_description'] ?? ( $body['error']['message'] ?? '' ) ) : '';
			$detail = trim( $code . ' ' . $desc, " \n\r\t\v\0" );
			throw new RuntimeException(
				esc_html(
					sprintf(
					/* translators: 1: service name, 2: HTTP status code, 3: error detail. */
						__( '%1$s returned HTTP %2$d. %3$s', 'webberzone-ai-account-connector' ),
						$config::VENDOR,
						$status,
						'' !== $detail ? $detail : substr( wp_strip_all_tags( $raw ), 0, 200 )
					)
				),
				$status // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Numeric exception code.
			);
		}
		if ( ! is_array( $body ) ) {
			throw new RuntimeException(
				esc_html(
					sprintf(
					/* translators: %s: service name. */
						__( '%s returned an invalid response.', 'webberzone-ai-account-connector' ),
						$config::VENDOR
					)
				)
			);
		}
		return $body;
	}

	/**
	 * Message for when no account is connected.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function not_connected_message() {
		$config = static::CONFIG;
		/* translators: %s: provider label. */
		return sprintf( __( '%s is not connected.', 'webberzone-ai-account-connector' ), $config::label() );
	}
}
