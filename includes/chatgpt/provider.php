<?php
/**
 * ChatGPT provider.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

defined( 'ABSPATH' ) || exit;

use WordPress\AiClient\AiClient;

/**
 * AI Client provider ID. Also drives the connector slug and setting names in core.
 *
 * @since 1.0.0
 */
const PROVIDER_ID = 'chatgpt-account';

/**
 * Checks that the WordPress AI Client is loaded.
 *
 * @return bool
 */
function is_ready() {
	return class_exists( AiClient::class );
}

/**
 * Registers the provider with the AI Client.
 *
 * @since 1.0.0
 */
function register_provider() {
	if ( ! is_ready() ) {
		return;
	}
	$registry = AiClient::defaultRegistry();
	if ( ! $registry->hasProvider( Provider::class ) ) {
		$registry->registerProvider( Provider::class );
	}
}

/**
 * Attaches the ChatGPT account authentication to the provider.
 *
 * Runs after core's init:20 connector wiring, which would otherwise install a plain API-key authentication.
 *
 * @since 1.0.0
 */
function attach_authentication() {
	if ( ! is_ready() ) {
		return;
	}
	try {
		AiClient::defaultRegistry()->setProviderRequestAuthentication( PROVIDER_ID, new Account_Authentication() );
	} catch ( \Exception $e ) {
		wp_trigger_error( __FUNCTION__, $e->getMessage() );
	}
}

/**
 * Tells the AI plugin that credentials are available when a ChatGPT account is connected.
 *
 * @since 1.0.0
 *
 * @param  bool $has_support Whether credentials are already available.
 * @return bool
 */
function report_connected( $has_support ) {
	return $has_support || ( is_ready() && Token_Store::is_connected() );
}
