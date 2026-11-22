<?php
/**
 * Encrypted storage for the OAuth tokens.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Stores the OAuth tokens in a single autoload-off option, encrypted with libsodium.
 *
 * The key is derived from the site's auth salt, so changing the salts in wp-config.php disconnects the account.
 *
 * @since 1.0.0
 */
abstract class Token_Store {

	const KEY_CONTEXT   = '';
	const LEGACY_OPTION = '';



	/** Atomically transfers a valid legacy grant. */
	public static function move_legacy_option() {
		global $wpdb;

		$legacy = static::LEGACY_OPTION;
		$target = static::OPTION;
		wp_cache_delete( $legacy, 'options' );
		wp_cache_delete( $target, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		$encrypted = get_option( $legacy, '' );
		if ( '' === $encrypted ) {
			return true;
		}
		if ( false !== get_option( $target ) ) {
			return false;
		}
		if ( empty( static::decrypt( $encrypted )['access_token'] ) ) {
			return false;
		}

		// Rename the row in place so an older uninstaller cannot revoke a duplicate grant.
		$moved = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- wp_options requires a single atomic rename.
			$wpdb->options,
			array(
				'option_name' => $target,
				'autoload'    => 'off',
			),
			array(
				'option_name'  => $legacy,
				'option_value' => $encrypted,
			)
		);
		wp_cache_delete( $legacy, 'options' );
		wp_cache_delete( $target, 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		return 1 === $moved;
	}

	/**
	 * Option that holds the encrypted token payload.
	 *
	 * @var string
	 */
	const OPTION = 'wzcga_tokens';

	/**
	 * Transient that caches the live model list.
	 *
	 * @since 1.0.3
	 *
	 * @var string
	 */
	const MODELS_KEY = 'wzcga_models';

	/**
	 * Transient set when the live model list could not be fetched.
	 *
	 * @since 1.0.3
	 *
	 * @var string
	 */
	const MODELS_FAILED_KEY = 'wzcga_models_failed';

	/**
	 * Returns the decrypted token data, or null when not connected.
	 *
	 * @since 1.0.0
	 *
	 * @return array|null Token data with access_token, refresh_token, id_token, account_id, email, plan and updated_at.
	 */
	public static function get() {
		$data = static::decrypt( get_option( static::OPTION, '' ) );
		return is_array( $data ) && ! empty( $data['access_token'] ) ? $data : null;
	}

	/**
	 * Encrypts and saves the token data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Token data.
	 */
	public static function save( array $data ) {
		$data['updated_at'] = time();
		update_option( static::OPTION, static::encrypt( $data ), false );
	}

	/**
	 * Encrypts an array into a base64 envelope.
	 *
	 * @since 1.0.3
	 *
	 * @param array $data Data to encrypt.
	 * @return string
	 */
	public static function encrypt( array $data ) {
		$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		return base64_encode( $nonce . sodium_crypto_secretbox( (string) wp_json_encode( $data ), $nonce, self::key() ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Storing binary ciphertext as text.
	}

	/**
	 * Decrypts an envelope produced by encrypt().
	 *
	 * @since 1.0.3
	 *
	 * @param mixed $raw Stored value.
	 * @return array|null Null when missing, tampered or encrypted with another key.
	 */
	public static function decrypt( $raw ) {
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}
		$decoded = base64_decode( $raw, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding our own ciphertext envelope.
		if ( false === $decoded || strlen( $decoded ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return null;
		}
		$plain = sodium_crypto_secretbox_open( substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), self::key() );
		if ( false === $plain ) {
			return null;
		}
		$data = json_decode( $plain, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Deletes the stored tokens and the cached model list.
	 *
	 * @since 1.0.0
	 */
	public static function clear() {
		delete_option( static::OPTION );
		// Not via Model_Directory: loading it fatals when the AI Client is missing, and disconnect must still work.
		delete_transient( static::MODELS_KEY );
		delete_transient( static::MODELS_FAILED_KEY );
	}

	/**
	 * Whether an account is connected.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return null !== static::get();
	}

	/**
	 * Derives the encryption key from the site's auth salt.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function key() {
		return sodium_crypto_generichash( wp_salt( 'auth' ) . '|' . static::KEY_CONTEXT, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}
}
