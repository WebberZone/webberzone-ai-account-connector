<?php
/**
 * Grok provider.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\Grok;

defined( 'ABSPATH' ) || exit;

use WordPress\AiClient\AiClient;

/**
 * AI Client provider ID. Also drives the connector slug and setting names in core.
 *
 * @since 1.0.0
 */
const PROVIDER_ID = 'grok-account';

/**
 * Checks that the WordPress AI Client and the base classes this plugin extends are available.
 *
 * The models subclass the AI Client's OpenAI-compatible base classes, so a missing class, or a hook
 * method that was renamed or made final, would be an uncatchable fatal error when our classes load.
 * Everything that touches those subclasses is gated behind this check.
 *
 * @since 1.0.0
 *
 * @return bool True if all dependencies are available and compatible.
 */
function is_ready() {
	static $ready = null;
	if ( null !== $ready ) {
		return $ready;
	}

	$base     = 'WordPress\\AiClient\\Providers\\OpenAiCompatibleImplementation\\';
	$required = array(
		$base . 'AbstractOpenAiCompatibleTextGenerationModel'  => array( 'createRequest', 'prepareGenerateTextParams', 'prepareResponseFormatParam' ),
		$base . 'AbstractOpenAiCompatibleImageGenerationModel' => array( 'createRequest', 'prepareGenerateImageParams', 'parseResponseToGenerativeAiResult' ),
	);

	$ready = class_exists( AiClient::class );
	foreach ( $required as $class_name => $methods ) {
		if ( ! $ready || ! class_exists( $class_name ) ) {
			$ready = false;
			break;
		}
		foreach ( $methods as $method ) {
			if ( ! method_exists( $class_name, $method ) || ( new \ReflectionMethod( $class_name, $method ) )->isFinal() ) {
				$ready = false;
				break 2;
			}
		}
	}

	return $ready;
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
 * Attaches the Grok account authentication to the provider.
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
 * Tells the AI plugin that credentials are available when a Grok account is connected.
 *
 * @since 1.0.0
 *
 * @param  bool $has_support Whether credentials or image support are already available.
 * @return bool
 */
function report_connected( $has_support ) {
	return $has_support || ( is_ready() && Token_Store::is_connected() );
}
