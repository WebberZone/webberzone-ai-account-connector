<?php
/**
 * Provider availability.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Reports the provider as configured when an account is connected.
 *
 * @since 1.0.0
 */
abstract class Availability implements ProviderAvailabilityInterface {

	const TOKEN_STORE = null;



	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	public function isConfigured(): bool {
		$token_store = static::TOKEN_STORE;
		return $token_store::is_connected();
	}
}
