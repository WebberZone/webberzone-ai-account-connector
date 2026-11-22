<?php
/**
 * Text generation model.
 *
 * @package WebberZone\AI_Account_Connector\ChatGPT
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Generates text through the OpenAI Responses API with a ChatGPT plan sign-in, which requires streaming.
 *
 * @since 1.0.0
 */
class Text_Generation_Model extends AbstractApiBasedModel implements TextGenerationModelInterface {


	/**
	 * Request timeout in seconds when the caller sets none; streamed replies can be slow.
	 *
	 * @var float
	 */
	const MIN_TIMEOUT = 180.0;

	/**
	 * Instructions sent when the prompt has no system instruction.
	 *
	 * @var string
	 */
	const DEFAULT_INSTRUCTIONS = 'You are a helpful assistant.';

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array $prompt List of messages.
	 */
	public function generateTextResult( array $prompt ): GenerativeAiResult {
		$bridge = new OpenAI_Bridge( $this->metadata(), $this->providerMetadata() );
		$bridge->setConfig( $this->getConfig() );

		$params   = $this->adapt_params( $bridge->build_params( $prompt ) );
		$response = $this->send( $params, true );

		return $bridge->parse_result( $this->parse_stream( $response ) );
	}

	/**
	 * Adjusts OpenAI Platform parameters to the ones a ChatGPT plan sign-in accepts.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $params Responses API parameters.
	 * @return array
	 */
	private function adapt_params( array $params ) {
		foreach ( array( 'background', 'conversation', 'max_output_tokens', 'max_tool_calls', 'metadata', 'moderation', 'multi_agent', 'previous_response_id', 'prompt', 'prompt_cache_retention', 'safety_identifier', 'temperature', 'top_logprobs', 'top_p', 'truncation', 'user' ) as $unsupported ) {
			unset( $params[ $unsupported ] );
		}

		$include   = array_diff( (array) ( $params['include'] ?? array() ), array( 'message.output_text.logprobs' ) );
		$include[] = 'reasoning.encrypted_content';

		$params['include']      = array_values( array_unique( $include ) );
		$params['stream']       = true;
		$params['store']        = false;
		$params['instructions'] = ( $params['instructions'] ?? '' ) !== '' ? $params['instructions'] : self::DEFAULT_INSTRUCTIONS;
		$params['tools']        = $params['tools'] ?? array();
		$params['tool_choice']  = $params['tool_choice'] ?? 'auto';

		// With store=false, reasoning items can only be replayed when their encrypted content travels with them.
		$params['input'] = array_values(
			array_filter(
				(array) $params['input'],
				static function ( $item ) {
					return ! is_array( $item ) || 'reasoning' !== ( $item['type'] ?? '' ) || ! empty( $item['encrypted_content'] );
				}
			)
		);

		return $params;
	}

	/**
	 * Sends the request, refreshing the token once on a 401.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $params      Request parameters.
	 * @param  bool  $allow_retry Whether a 401 may trigger a refresh and retry.
	 * @return Response
	 * @throws RuntimeException On HTTP errors.
	 */
	private function send( array $params, $allow_retry ) {
		$request  = new Request(
			HttpMethodEnum::POST(),
			Provider::url( 'responses' ),
			array(
				'Content-Type' => 'application/json',
				'Accept'       => 'text/event-stream',
			),
			$params,
			self::request_options( $this->getRequestOptions(), $this->metadata()->getId() )
		);
		$tokens   = OAuth::get_valid_tokens();
		$request  = Account_Authentication::apply_headers( $request, $tokens );
		$response = $this->getHttpTransporter()->send( $request );

		if ( 401 === $response->getStatusCode() && $allow_retry ) {
			OAuth::refresh( true, $tokens['access_token'] );
			return $this->send( $params, false );
		}
		if ( ! $response->isSuccessful() ) {
			/* translators: 1: HTTP status code, 2: error detail. */
			throw new RuntimeException( esc_html( sprintf( __( 'ChatGPT returned HTTP %1$d: %2$s', 'webberzone-ai-account-connector' ), $response->getStatusCode(), self::error_detail( (string) $response->getBody() ) ) ) );
		}
		return $response;
	}

	/**
	 * Returns request options with at least the minimum timeout.
	 *
	 * A caller's timeout is kept for known text models so it can fall back to another provider.
	 *
	 * @since 1.0.0
	 *
	 * @param  RequestOptions|null $options  Existing options.
	 * @param  string              $model_id Model ID, or an empty string when unknown.
	 * @return RequestOptions
	 */
	public static function request_options( $options, string $model_id = '' ) {
		$options = $options ? clone $options : new RequestOptions();
		$minimum = '' === $model_id ? self::MIN_TIMEOUT : 0.0;

		/**
		 * Filters the minimum request timeout in seconds.
		 *
		 * @since 1.0.0
		 *
		 * @param float  $minimum  Minimum timeout. 0 for known models, otherwise 180.
		 * @param string $model_id Model ID, or an empty string when unknown.
		 */
		$minimum = (float) apply_filters( 'wzap_chatgpt_min_timeout', $minimum, $model_id );

		$timeout = $options->getTimeout();
		if ( null === $timeout || self::default_timeout() === $timeout ) {
			$options->setTimeout( max( self::MIN_TIMEOUT, $minimum ) );
		} elseif ( $timeout < $minimum ) {
			$options->setTimeout( $minimum );
		}
		return $options;
	}

	/**
	 * Returns the default timeout the WordPress AI Client sets on every prompt.
	 *
	 * @since 1.0.0
	 *
	 * @return float
	 */
	private static function default_timeout() {
		// Core always sets this default, so matching it is the only way to tell that the caller chose no timeout.
		$timeout = apply_filters( 'wp_ai_client_default_request_timeout', 30.0 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return is_numeric( $timeout ) && (float) $timeout >= 0.0 ? (float) $timeout : 30.0;
	}

	/**
	 * Collapses the server-sent event stream into a single Responses API JSON response.
	 *
	 * @since 1.0.0
	 *
	 * @param  Response $response Streamed response.
	 * @return Response
	 * @throws RuntimeException When the stream reports an error or never completes.
	 */
	private function parse_stream( Response $response ) {
		$final = null;
		$items = array();

		foreach ( (array) preg_split( "/\r?\n\r?\n/", (string) $response->getBody() ) as $event ) {
			$data = array();
			foreach ( (array) preg_split( "/\r?\n/", (string) $event ) as $line ) {
				if ( 0 === strncmp( (string) $line, 'data:', 5 ) ) {
					$data[] = ltrim( substr( (string) $line, 5 ), ' ' );
				}
			}
			$payload = json_decode( implode( "\n", $data ), true );
			if ( ! is_array( $payload ) ) {
				continue;
			}
			$type = $payload['type'] ?? '';

			if ( 'response.output_item.done' === $type && is_array( $payload['item'] ?? null ) ) {
				$items[] = $payload['item'];
			} elseif ( in_array( $type, array( 'response.completed', 'response.incomplete' ), true ) && is_array( $payload['response'] ?? null ) ) {
				$final = $payload['response'];
			} elseif ( in_array( $type, array( 'response.failed', 'error' ), true ) ) {
				$error = $payload['response']['error'] ?? ( $payload['error'] ?? $payload );
				$code  = is_array( $error ) ? (string) ( $error['code'] ?? '' ) : '';
				if ( 'subscription_sharing_usage_limit_exceeded' === $code ) {
					throw new RuntimeException( esc_html__( 'Your ChatGPT plan usage limit has been reached. Wait for it to reset, or check your usage in ChatGPT settings.', 'webberzone-ai-account-connector' ) );
				}
				/* translators: 1: error code, 2: error message. */
				throw new RuntimeException( esc_html( sprintf( __( 'ChatGPT error (%1$s): %2$s', 'webberzone-ai-account-connector' ), '' !== $code ? $code : 'unknown', is_array( $error ) ? ( $error['message'] ?? wp_json_encode( $error ) ) : (string) $error ) ) );
			}
		}

		if ( null === $final ) {
			throw new RuntimeException( esc_html__( 'ChatGPT stream ended without a completed response.', 'webberzone-ai-account-connector' ) );
		}
		// Output items stream individually and response.completed's output may be empty.
		if ( empty( $final['output'] ) && $items ) {
			$final['output'] = $items;
		}

		return new Response( 200, array( 'Content-Type' => array( 'application/json' ) ), (string) wp_json_encode( $final ) );
	}

	/**
	 * Extracts a readable error message from an error response body.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $body Response body.
	 * @return string
	 */
	public static function error_detail( $body ) {
		$data = json_decode( $body, true );
		if ( is_array( $data ) ) {
			$message = $data['detail'] ?? ( $data['error']['message'] ?? ( $data['error'] ?? null ) );
			if ( is_string( $message ) ) {
				return $message;
			}
		}
		return substr( wp_strip_all_tags( $body ), 0, 300 );
	}
}
