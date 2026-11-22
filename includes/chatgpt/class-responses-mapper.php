<?php
/**
 * Responses API mapping adapted from AI Provider for OpenAI 1.3.0 by the WordPress AI Team.
 *
 * @see https://github.com/WordPress/ai-provider-for-openai/blob/3379333d7d2450b54d1d2c765f6beb36b420e2b2/src/Models/OpenAiTextGenerationModel.php
 * @license GPL-2.0-or-later
 * @package WebberZone\AI_Account_Connector\ChatGPT
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Common\Exception\TokenLimitReachedException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\WebSearch;

defined( 'ABSPATH' ) || exit;

/**
 * Maps WordPress AI Client messages and results to and from the Responses API.
 *
 * @since 1.0.1
 */
class Responses_Mapper extends AbstractApiBasedModel {
	/**
	 * Maps OpenAI-safe function names back to the original client tool names.
	 *
	 * @var array<string, string>
	 */
	private array $open_ai_function_name_map = array();

	/**
	 * Maps original client tool names to OpenAI-safe function names.
	 *
	 * @var array<string, string>
	 */
	private array $client_function_name_map = array();

	private const OPENAI_FUNCTION_NAME_MAX_LENGTH  = 64;
	private const OPENAI_FUNCTION_NAME_HASH_LENGTH = 8;

	// Exception messages are application errors; callers escape them when rendering.
	// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

	/**
	 * Builds the Responses API parameters.
	 *
	 * @param array $prompt The messages to send.
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException If request or response data is invalid.
	 */
	public function build_params( array $prompt ): array {
		$config = $this->getConfig();

		$params = array(
			'model' => $this->metadata()->getId(),
			'input' => $this->prepare_input_param( $prompt ),
		);

		$system_instruction = $config->getSystemInstruction();
		if ( $system_instruction ) {
			$params['instructions'] = $system_instruction;
		}

		$max_tokens = $config->getMaxTokens();
		if ( null !== $max_tokens ) {
			$params['max_output_tokens'] = $max_tokens;
		}

		$temperature = $config->getTemperature();
		if ( null !== $temperature ) {
			$params['temperature'] = $temperature;
		}

		$top_p = $config->getTopP();
		if ( null !== $top_p ) {
			$params['top_p'] = $top_p;
		}

		$logprobs = $config->getLogprobs();
		if ( true === $logprobs ) {
			$params['include'] = array( 'message.output_text.logprobs' );
		}

		$top_logprobs = $config->getTopLogprobs();
		if ( null !== $top_logprobs ) {
			$params['top_logprobs'] = $top_logprobs;
		}

		// Note: OpenAI does not support top_k parameter.

		$output_mime_type = $config->getOutputMimeType();
		$output_schema    = $config->getOutputSchema();
		if ( 'application/json' === $output_mime_type && $output_schema ) {
			$params['text'] = array(
				'format' => array(
					'type'   => 'json_schema',
					'name'   => 'response_schema',
					'schema' => $output_schema,
					'strict' => true,
				),
			);
		}

		$function_declarations = $config->getFunctionDeclarations();
		$web_search            = $config->getWebSearch();
		$custom_options        = $config->getCustomOptions();

		if ( is_array( $function_declarations ) || $web_search ) {
			$params['tools'] = $this->prepare_tools_param(
				$function_declarations,
				$web_search
			);
		}

		/*
		 * Any custom options are added to the parameters as well.
		 * This allows developers to pass other options that may be more niche or not yet supported by the SDK.
		 */
		foreach ( $custom_options as $key => $value ) {
			if ( isset( $params[ $key ] ) ) {
				throw new InvalidArgumentException(
					sprintf(
						'The custom option "%s" conflicts with an existing parameter.',
						$key
					)
				);
			}
			$params[ $key ] = $value;
		}

		$this->validate_sampling_params_for_reasoning_effort( $params );

		return $params;
	}

	/**
	 * Validates that sampling parameters are not combined with an explicitly enabled reasoning effort.
	 *
	 * The model metadata advertises support for `temperature`, `top_p`, `logprobs`, and
	 * `top_logprobs` for reasoning models that run with `reasoning.effort` set to `none` by
	 * default (e.g. `gpt-5.2` and `gpt-5.4`), because the options apply in that default mode.
	 * The OpenAI API rejects these parameters as soon as reasoning is enabled, which the
	 * metadata cannot express conditionally. Since a reasoning effort can only be requested
	 * via the `reasoning` custom option, that combination is caught here before the API request
	 * is sent.
	 *
	 * @since 1.0.1
	 *
	 * @param array<string, mixed> $params The prepared parameters for the API request.
	 * @return void
	 * @throws InvalidArgumentException If sampling parameters are combined with an enabled reasoning effort.
	 */
	protected function validate_sampling_params_for_reasoning_effort( array $params ): void {
		$reasoning = $params['reasoning'] ?? null;
		if ( ! is_array( $reasoning ) ) {
			return;
		}

		$effort = $reasoning['effort'] ?? null;
		if ( ! is_string( $effort ) || 'none' === $effort ) {
			return;
		}

		$sampling_params = array_values(
			array_intersect( array( 'temperature', 'top_p', 'top_logprobs' ), array_keys( $params ) )
		);
		$include         = $params['include'] ?? null;
		if (
			is_array( $include )
			&& in_array( 'message.output_text.logprobs', $include, true )
		) {
			$sampling_params[] = 'logprobs';
		}
		if ( ! $sampling_params ) {
			return;
		}

		throw new InvalidArgumentException(
			sprintf(
				'The parameter(s) "%s" cannot be combined with reasoning effort "%s" for model "%s". '
					. 'OpenAI Responses only support these sampling options when the reasoning effort is "none".',
				implode( '", "', $sampling_params ),
				$effort,
				$this->metadata()->getId()
			)
		);
	}

	/**
	 * Prepares the input parameter for the API request.
	 *
	 * @since 1.0.1
	 *
	 * @param array $messages The messages to prepare.
	 * @return list<array<string, mixed>> The prepared input parameter.
	 */
	protected function prepare_input_param( array $messages ): array {
		$this->validate_messages( $messages );

		$input = array();
		foreach ( $messages as $message ) {
			foreach ( $this->get_reasoning_input_items( $message ) as $reasoning_item ) {
				$input[] = $reasoning_item;
			}
			$input_item = $this->get_message_input_item( $message );
			if ( null !== $input_item ) {
				$input[] = $input_item;
			}
		}
		return $input;
	}

	/**
	 * Extracts top-level reasoning items from a message's thought-channel parts.
	 *
	 * @since 1.2.0
	 *
	 * @param Message $message The message to inspect.
	 * @return list<array<string, mixed>> Reasoning items to send as top-level input.
	 */
	protected function get_reasoning_input_items( Message $message ): array {
		$items = array();
		foreach ( $message->getParts() as $part ) {
			if ( ! $part->getChannel()->isThought() ) {
				continue;
			}
			$signature = $part->getThoughtSignature();
			if ( ! is_string( $signature ) || '' === $signature ) {
				continue;
			}

			$decoded = json_decode( $signature, true );
			if (
				! is_array( $decoded )
				|| ! isset( $decoded['id'] )
				|| ! is_string( $decoded['id'] )
				|| '' === $decoded['id']
			) {
				continue;
			}

			$item = array(
				'type' => 'reasoning',
				'id'   => $decoded['id'],
			);
			if ( isset( $decoded['encrypted_content'] ) && is_string( $decoded['encrypted_content'] ) ) {
				$item['encrypted_content'] = $decoded['encrypted_content'];
			}
			if ( isset( $decoded['summary'] ) && is_array( $decoded['summary'] ) ) {
				$item['summary'] = $decoded['summary'];
			} else {
				$item['summary'] = array();
			}
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * Validates that the messages are appropriate for the OpenAI Responses API.
	 *
	 * The OpenAI Responses API requires function calls and function responses to be
	 * sent as top-level input items rather than nested in message content. As such,
	 * they must be the only part in a message.
	 *
	 * Thought-channel parts are sent as separate top-level `reasoning` items
	 * (see {@see self::get_reasoning_input_items()}) and skipped here.
	 *
	 * @since 1.0.1
	 *
	 * @param array $messages The messages to validate.
	 * @return void
	 * @throws InvalidArgumentException If validation fails.
	 */
	protected function validate_messages( array $messages ): void {
		foreach ( $messages as $message ) {
			$content_parts = array();
			foreach ( $message->getParts() as $part ) {
				if ( $part->getChannel()->isThought() ) {
					continue;
				}
				$content_parts[] = $part;
			}

			if ( count( $content_parts ) <= 1 ) {
				continue;
			}

			foreach ( $content_parts as $part ) {
				$type = $part->getType();

				if ( $type->isFunctionCall() ) {
					throw new InvalidArgumentException(
						'Function call parts must be the only part in a message for the OpenAI Responses API.'
					);
				}

				if ( $type->isFunctionResponse() ) {
					throw new InvalidArgumentException(
						'Function response parts must be the only part in a message for the OpenAI Responses API.'
					);
				}
			}
		}
	}

	/**
	 * Converts a Message object to a Responses API input item.
	 *
	 * @since 1.0.1
	 *
	 * @param Message $message The message to convert.
	 * @return array<string, mixed>|null The input item, or null if the message is empty.
	 */
	protected function get_message_input_item( Message $message ): ?array {
		$parts = $message->getParts();

		if ( empty( $parts ) ) {
			return null;
		}

		$role    = $message->getRole();
		$content = array();
		foreach ( $parts as $part ) {
			if ( $part->getChannel()->isThought() ) {
				continue;
			}
			$part_data = $this->get_message_part_data( $part, $role );

			// Function calls and responses are top-level items, not wrapped in a message.
			// validate_messages() ensures these are the only part in a message.
			$part_type = $part_data['type'] ?? '';
			if ( 'function_call' === $part_type || 'function_call_output' === $part_type ) {
				return $part_data;
			}

			$content[] = $part_data;
		}

		if ( empty( $content ) ) {
			return null;
		}

		return array(
			'role'    => $this->get_message_role_string( $role ),
			'content' => $content,
		);
	}

	/**
	 * Returns the OpenAI API specific role string for the given message role.
	 *
	 * @since 1.0.1
	 *
	 * @param MessageRoleEnum $role The message role.
	 * @return string The role for the API request.
	 */
	protected function get_message_role_string( MessageRoleEnum $role ): string {
		if ( MessageRoleEnum::model() === $role ) {
			return 'assistant';
		}
		return 'user';
	}

	/**
	 * Returns the OpenAI API specific data for a message part.
	 *
	 * @since 1.0.1
	 *
	 * @param MessagePart     $part The message part to get the data for.
	 * @param MessageRoleEnum $role The role of the message containing the part.
	 * @return array<string, mixed> The data for the message part.
	 * @throws InvalidArgumentException If the message part type or data is unsupported.
	 * @throws RuntimeException If request or response data is invalid.
	 */
	protected function get_message_part_data( MessagePart $part, MessageRoleEnum $role ): array {
		$type = $part->getType();
		if ( $type->isText() ) {
			return array(
				'type' => $role->isModel() ? 'output_text' : 'input_text',
				'text' => $part->getText(),
			);
		}
		if ( $type->isFile() ) {
			$file = $part->getFile();
			if ( ! $file ) {
				// This should be impossible due to class internals, but still needs to be checked.
				throw new RuntimeException(
					'The file typed message part must contain a file.'
				);
			}
			if ( $file->isRemote() ) {
				$file_url = $file->getUrl();
				if ( ! $file_url ) {
					// This should be impossible due to class internals, but still needs to be checked.
					throw new RuntimeException(
						'The remote file must contain a URL.'
					);
				}
				if ( $file->isImage() ) {
					return array(
						'type'      => 'input_image',
						'image_url' => $file_url,
					);
				}
				// For other file types, use input_file with URL.
				return array(
					'type'     => 'input_file',
					'file_url' => $file_url,
				);
			}
			// Else, it is an inline file.
			$data_uri = $file->getDataUri();
			if ( ! $data_uri ) {
				// This should be impossible due to class internals, but still needs to be checked.
				throw new RuntimeException(
					'The inline file must contain base64 data.'
				);
			}
			if ( $file->isImage() ) {
				return array(
					'type'      => 'input_image',
					'image_url' => $data_uri,
				);
			}
			// For other file types (like PDF), use input_file.
			return array(
				'type'      => 'input_file',
				'filename'  => 'file',
				'file_data' => $data_uri,
			);
		}
		if ( $type->isFunctionCall() ) {
			$function_call = $part->getFunctionCall();
			if ( ! $function_call ) {
				throw new RuntimeException(
					'The function_call typed message part must contain a function call.'
				);
			}
			$function_name = $function_call->getName();
			if ( null === $function_name ) {
				throw new RuntimeException(
					'The function_call typed message part must contain a function name.'
				);
			}
			return array(
				'type'      => 'function_call',
				'call_id'   => $function_call->getId(),
				'name'      => $this->open_ai_function_name( $function_name ),
				'arguments' => wp_json_encode( $function_call->getArgs() ),
			);
		}
		if ( $type->isFunctionResponse() ) {
			$function_response = $part->getFunctionResponse();
			if ( ! $function_response ) {
				throw new RuntimeException(
					'The function_response typed message part must contain a function response.'
				);
			}
			return array(
				'type'    => 'function_call_output',
				'call_id' => $function_response->getId(),
				'output'  => wp_json_encode( $function_response->getResponse() ),
			);
		}
		throw new InvalidArgumentException(
			sprintf(
				'Unsupported message part type "%s".',
				$type
			)
		);
	}

	/**
	 * Prepares the tools parameter for the API request.
	 *
	 * @since 1.0.1
	 *
	 * @param list<FunctionDeclaration>|null $function_declarations The function declarations, or null if none.
	 * @param WebSearch|null                 $web_search The web search config, or null if none.
	 * @return list<array<string, mixed>> The prepared tools parameter.
	 */
	protected function prepare_tools_param(
		?array $function_declarations,
		?WebSearch $web_search
	): array {
		$tools                           = array();
		$this->open_ai_function_name_map = array();
		$this->client_function_name_map  = array();

		if ( is_array( $function_declarations ) ) {
			foreach ( $function_declarations as $function_declaration ) {
				$open_ai_name = $this->open_ai_function_name( $function_declaration->getName() );
				$tools[]      = array(
					'type'        => 'function',
					'name'        => $open_ai_name,
					'description' => $function_declaration->getDescription(),
					'parameters'  => $function_declaration->getParameters(),
				);
			}
		}

		if ( $web_search ) {
			$web_search_tool = array( 'type' => 'web_search' );
			// Note: The OpenAI Responses API web_search tool may have different filtering options.
			// For now, we use the basic form.
			$tools[] = $web_search_tool;
		}

		return $tools;
	}

	/**
	 * Parses the response from the API endpoint to a generative AI result.
	 *
	 * @since 1.0.1
	 *
	 * @param Response $response The response from the API endpoint.
	 * @return GenerativeAiResult The parsed generative AI result.
	 * @throws ResponseException If request or response data is invalid.
	 * @throws TokenLimitReachedException If request or response data is invalid.
	 */
	public function parse_result( Response $response ): GenerativeAiResult {
		$response_data = $response->getData();

		// Check for token limit before processing output. When max_output_tokens is reached, OpenAI returns
		// status 'incomplete' with incomplete_details.reason 'max_output_tokens'. The output array may be
		// empty in this case, so this check must happen before the output validation below.
		$status = $response_data['status'] ?? 'completed';
		if ( 'incomplete' === $status ) {
			$incomplete_details = $response_data['incomplete_details'] ?? null;
			$reason             = is_array( $incomplete_details ) ? ( $incomplete_details['reason'] ?? '' ) : '';
			if ( 'max_output_tokens' === $reason ) {
				$max_tokens = $this->getConfig()->getMaxTokens();
				throw new TokenLimitReachedException(
					sprintf(
						'Generation stopped due to token limit with reason "%s".',
						$reason
					),
					$max_tokens
				);
			}
		}

		if ( ! isset( $response_data['output'] ) || ! $response_data['output'] ) {
			throw ResponseException::fromMissingData( $this->providerMetadata()->getName(), 'output' );
		}
		if ( ! is_array( $response_data['output'] ) || array_values( $response_data['output'] ) !== $response_data['output'] ) {
			throw ResponseException::fromInvalidData(
				$this->providerMetadata()->getName(),
				'output',
				'The value must be an indexed array.'
			);
		}

		$candidates              = array();
		$pending_reasoning_parts = array();
		foreach ( $response_data['output'] as $index => $output_item ) {
			if ( ! is_array( $output_item ) || array_values( $output_item ) === $output_item ) {
				throw ResponseException::fromInvalidData(
					$this->providerMetadata()->getName(),
					"output[{$index}]",
					'The value must be an associative array.'
				);
			}

			if ( ( $output_item['type'] ?? '' ) === 'reasoning' ) {
				$reasoning_part = $this->parse_reasoning_output_to_part( $output_item );
				if ( null !== $reasoning_part ) {
					$pending_reasoning_parts[] = $reasoning_part;
				}
				continue;
			}

			$candidate = $this->parse_output_item_to_candidate(
				$output_item,
				$index,
				$response_data['status'] ?? 'completed',
				$pending_reasoning_parts
			);
			if ( null !== $candidate ) {
				$candidates[] = $candidate;
			}
			$pending_reasoning_parts = array();
		}

		$id = isset( $response_data['id'] ) && is_string( $response_data['id'] ) ? $response_data['id'] : '';

		if ( isset( $response_data['usage'] ) && is_array( $response_data['usage'] ) ) {
			$usage       = $response_data['usage'];
			$token_usage = $this->build_token_usage( $usage );
		} else {
			$token_usage = $this->build_token_usage( array() );
		}

		// Use any other data from the response as provider-specific response metadata.
		$additional_data = $response_data;
		unset( $additional_data['id'], $additional_data['output'], $additional_data['usage'] );

		return new GenerativeAiResult(
			$id,
			$candidates,
			$token_usage,
			$this->providerMetadata(),
			$this->metadata(),
			$additional_data
		);
	}

	/**
	 * Parses a single output item from the API response into a Candidate object.
	 *
	 * @since 1.0.1
	 *
	 * @param array  $output_item The output item data from the API response.
	 * @param int    $index The index of the output item in the output array.
	 * @param string $response_status The overall response status.
	 * @param array  $reasoning_parts Buffered thought-channel parts to attach to this candidate.
	 * @return Candidate|null The parsed candidate, or null if the output item should be skipped.
	 */
	protected function parse_output_item_to_candidate(
		array $output_item,
		int $index,
		string $response_status,
		array $reasoning_parts = array()
	): ?Candidate {
		$type = $output_item['type'] ?? '';

		// Handle message output type.
		if ( 'message' === $type ) {
			return $this->parse_message_output_to_candidate( $output_item, $index, $response_status, $reasoning_parts );
		}

		// Handle function_call output type (top-level function call).
		if ( 'function_call' === $type ) {
			return $this->parse_function_call_output_to_candidate( $output_item, $index, $reasoning_parts );
		}

		// Skip other output types for now (e.g., image_generation_call is handled in image model).
		return null;
	}

	/**
	 * Parses a reasoning output item into a thought-channel MessagePart.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, mixed> $output_item The reasoning output item from the API response.
	 * @return MessagePart|null The reasoning part, or null if there is nothing to round-trip.
	 */
	protected function parse_reasoning_output_to_part( array $output_item ): ?MessagePart {
		if (
			! isset( $output_item['id'] )
			|| ! is_string( $output_item['id'] )
			|| '' === $output_item['id']
		) {
			return null;
		}

		$summary = isset( $output_item['summary'] ) && is_array( $output_item['summary'] )
			? $output_item['summary']
			: array();

		$signature_payload = array(
			'id'      => $output_item['id'],
			'summary' => $summary,
		);
		if ( isset( $output_item['encrypted_content'] ) && is_string( $output_item['encrypted_content'] ) ) {
			$signature_payload['encrypted_content'] = $output_item['encrypted_content'];
		}

		$signature = wp_json_encode( $signature_payload );
		if ( false === $signature ) {
			return null;
		}

		$summary_texts = array();
		foreach ( $summary as $summary_item ) {
			if ( is_array( $summary_item ) && isset( $summary_item['text'] ) && is_string( $summary_item['text'] ) ) {
				$summary_texts[] = $summary_item['text'];
			}
		}

		return new MessagePart( implode( "\n", $summary_texts ), MessagePartChannelEnum::thought(), $signature );
	}

	/**
	 * Builds a TokenUsage DTO from the API usage block.
	 *
	 * @since 1.2.0
	 *
	 * @param array<string, mixed> $usage The usage block from the API response.
	 * @return TokenUsage The token usage DTO.
	 */
	protected function build_token_usage( array $usage ): TokenUsage {
		$input_tokens  = is_int( $usage['input_tokens'] ?? null ) ? $usage['input_tokens'] : 0;
		$output_tokens = is_int( $usage['output_tokens'] ?? null ) ? $usage['output_tokens'] : 0;
		$total_tokens  = is_int( $usage['total_tokens'] ?? null ) ? $usage['total_tokens'] : $input_tokens + $output_tokens;

		$thought_tokens = null;
		$details        = $usage['output_tokens_details'] ?? null;
		if ( is_array( $details ) && isset( $details['reasoning_tokens'] ) && is_int( $details['reasoning_tokens'] ) ) {
			$thought_tokens = $details['reasoning_tokens'];
		}

		return new TokenUsage( $input_tokens, $output_tokens, $total_tokens, $thought_tokens );
	}

	/**
	 * Parses a message output item into a Candidate object.
	 *
	 * @since 1.0.1
	 *
	 * @param array  $output_item The output item data.
	 * @param int    $index The index of the output item.
	 * @param string $response_status The overall response status.
	 * @param array  $reasoning_parts Buffered thought-channel parts to prepend to the candidate message.
	 * @return Candidate The parsed candidate.
	 * @throws ResponseException If request or response data is invalid.
	 */
	protected function parse_message_output_to_candidate(
		array $output_item,
		int $index,
		string $response_status,
		array $reasoning_parts = array()
	): Candidate {
		$role = isset( $output_item['role'] ) && 'user' === $output_item['role']
			? MessageRoleEnum::user()
			: MessageRoleEnum::model();

		$parts              = $reasoning_parts;
		$has_function_calls = false;

		if ( isset( $output_item['content'] ) && is_array( $output_item['content'] ) ) {
			foreach ( $output_item['content'] as $content_index => $content_item ) {
				try {
					$part = $this->parse_output_content_to_part( $content_item );
					if ( null !== $part ) {
						$parts[] = $part;
						if ( $part->getType()->isFunctionCall() ) {
							$has_function_calls = true;
						}
					}
				} catch ( InvalidArgumentException $e ) {
					throw ResponseException::fromInvalidData(
						$this->providerMetadata()->getName(),
						"output[{$index}].content[{$content_index}]",
						$e->getMessage()
					);
				}
			}
		}

		$message       = new Message( $role, $parts );
		$finish_reason = $this->parse_status_to_finish_reason( $response_status, $has_function_calls );

		return new Candidate( $message, $finish_reason );
	}

	/**
	 * Parses a function_call output item into a Candidate object.
	 *
	 * @since 1.0.1
	 *
	 * @param array $output_item The output item data.
	 * @param int   $index The index of the output item.
	 * @param array $reasoning_parts Buffered thought-channel parts to prepend to the candidate message.
	 * @return Candidate The parsed candidate.
	 * @throws ResponseException If request or response data is invalid.
	 */
	protected function parse_function_call_output_to_candidate(
		array $output_item,
		int $index,
		array $reasoning_parts = array()
	): Candidate {
		if ( ! isset( $output_item['call_id'] ) || ! is_string( $output_item['call_id'] ) ) {
			throw ResponseException::fromMissingData(
				$this->providerMetadata()->getName(),
				"output[{$index}].call_id"
			);
		}
		if ( ! isset( $output_item['name'] ) || ! is_string( $output_item['name'] ) ) {
			throw ResponseException::fromMissingData(
				$this->providerMetadata()->getName(),
				"output[{$index}].name"
			);
		}

		/*
		 * Parse and normalize function arguments.
		 * OpenAI returns arguments as a JSON string. An empty object "{}"
		 * decodes to an empty array, which semantically means "no arguments"
		 * and should be normalized to null.
		 */
		$args = null;
		if ( isset( $output_item['arguments'] ) && is_string( $output_item['arguments'] ) ) {
			$decoded = json_decode( $output_item['arguments'], true );
			if ( is_array( $decoded ) && count( $decoded ) > 0 ) {
				$args = $decoded;
			}
		}

		$function_call = new FunctionCall(
			$output_item['call_id'],
			$this->original_function_name( $output_item['name'] ),
			$args
		);

		$part    = new MessagePart( $function_call );
		$parts   = $reasoning_parts;
		$parts[] = $part;
		$message = new Message( MessageRoleEnum::model(), $parts );

		return new Candidate( $message, FinishReasonEnum::toolCalls() );
	}

	/**
	 * Converts a PHP AI Client function name to an OpenAI-safe function name.
	 *
	 * @since 1.0.1
	 *
	 * @param string $name Original function name.
	 * @return string OpenAI-safe function name.
	 */
	private function open_ai_function_name( string $name ): string {
		if ( isset( $this->client_function_name_map[ $name ] ) ) {
			return $this->client_function_name_map[ $name ];
		}

		$safe = preg_replace( '/[^a-zA-Z0-9_-]+/', '_', $name ) ?? '';
		$safe = trim( $safe, '_-' );
		if ( '' === $safe ) {
			$safe = 'tool';
		}

		if (
			$safe !== $name ||
			strlen( $safe ) > self::OPENAI_FUNCTION_NAME_MAX_LENGTH ||
			isset( $this->open_ai_function_name_map[ $safe ] )
		) {
			$prefix_length = self::OPENAI_FUNCTION_NAME_MAX_LENGTH - self::OPENAI_FUNCTION_NAME_HASH_LENGTH - 1;
			$safe          = substr( $safe, 0, $prefix_length )
				. '_'
				. substr( sha1( $name ), 0, self::OPENAI_FUNCTION_NAME_HASH_LENGTH );
		}

		$this->client_function_name_map[ $name ]  = $safe;
		$this->open_ai_function_name_map[ $safe ] = $name;

		return $safe;
	}

	/**
	 * Restores the original PHP AI Client function name from an OpenAI function call.
	 *
	 * @since 1.0.1
	 *
	 * @param string $name OpenAI function name.
	 * @return string Original function name when known.
	 */
	private function original_function_name( string $name ): string {
		return $this->open_ai_function_name_map[ $name ] ?? $name;
	}

	/**
	 * Parses an output content item into a MessagePart.
	 *
	 * @since 1.0.1
	 *
	 * @param array<string, mixed> $content_item The content item data.
	 * @return MessagePart|null The parsed message part, or null to skip.
	 * @throws InvalidArgumentException If request or response data is invalid.
	 */
	protected function parse_output_content_to_part( array $content_item ): ?MessagePart {
		$type = $content_item['type'] ?? '';

		if ( 'output_text' === $type ) {
			if ( ! isset( $content_item['text'] ) || ! is_string( $content_item['text'] ) ) {
				throw new InvalidArgumentException( 'Content has an invalid output_text shape.' );
			}
			return new MessagePart( $content_item['text'] );
		}

		if ( 'function_call' === $type ) {
			if (
				! isset( $content_item['call_id'] ) ||
				! is_string( $content_item['call_id'] ) ||
				! isset( $content_item['name'] ) ||
				! is_string( $content_item['name'] )
			) {
				throw new InvalidArgumentException( 'Content has an invalid function_call shape.' );
			}

			/*
			 * Parse and normalize function arguments.
			 * OpenAI returns arguments as a JSON string. An empty object "{}"
			 * decodes to an empty array, which semantically means "no arguments"
			 * and should be normalized to null.
			 */
			$args = null;
			if ( isset( $content_item['arguments'] ) && is_string( $content_item['arguments'] ) ) {
				$decoded = json_decode( $content_item['arguments'], true );
				if ( is_array( $decoded ) && count( $decoded ) > 0 ) {
					$args = $decoded;
				}
			}

			return new MessagePart(
				new FunctionCall(
					$content_item['call_id'],
					$this->original_function_name( $content_item['name'] ),
					$args
				)
			);
		}

		// Skip unknown content types.
		return null;
	}

	/**
	 * Parses the response status to a finish reason.
	 *
	 * @since 1.0.1
	 *
	 * @param string $status The response status.
	 * @param bool   $has_function_calls Whether the response contains function calls.
	 * @return FinishReasonEnum The finish reason.
	 */
	protected function parse_status_to_finish_reason( string $status, bool $has_function_calls ): FinishReasonEnum {
		switch ( $status ) {
			case 'completed':
				return $has_function_calls ? FinishReasonEnum::toolCalls() : FinishReasonEnum::stop();
			case 'incomplete':
				return FinishReasonEnum::length();
			case 'failed':
			case 'cancelled':
				return FinishReasonEnum::error();
			default:
				// Default to stop for unknown statuses.
				return FinishReasonEnum::stop();
		}
	}
}
