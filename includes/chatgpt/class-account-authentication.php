<?php
/**
 * Request authentication for the ChatGPT backend.
 *
 * @package WebberZone\AI_Account_Connector\ChatGPT
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Adds the ChatGPT access token and account headers to requests.
 *
 * Extends the API-key class because the AI Client registry only accepts authentication matching the provider's declared method.
 *
 * @since 1.0.0
 */
class Account_Authentication extends ApiKeyRequestAuthentication {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( PROVIDER_ID );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param  Request $request The request.
	 * @return Request
	 */
	public function authenticateRequest( Request $request ): Request {
		return self::apply_headers( $request, OAuth::get_valid_tokens() );
	}

	/**
	 * Adds the bearer token to a request.
	 *
	 * @since 1.0.0
	 *
	 * @param  Request $request The request.
	 * @param  array   $tokens  Token data.
	 * @return Request
	 */
	public static function apply_headers( Request $request, array $tokens ) {
		return $request->withHeader( 'Authorization', 'Bearer ' . $tokens['access_token'] );
	}
}
