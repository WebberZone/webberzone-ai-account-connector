<?php
/**
 * ChatGPT Account AI Client provider.
 *
 * @package WebberZone\AI_Account_Connector\ChatGPT
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Provider for the OpenAI Responses API, authenticated with a ChatGPT account.
 *
 * @since 1.0.0
 */
class Provider extends AbstractApiProvider {


	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function baseUrl(): string {
		return 'https://api.openai.com/v1'; // phpcs:ignore PluginCheck.CodeAnalysis.AIProvider.DirectIntegration -- Provider registered with the WordPress AI Client.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param  ModelMetadata    $model_metadata    Model metadata.
	 * @param  ProviderMetadata $provider_metadata Provider metadata.
	 * @return ModelInterface
	 * @throws RuntimeException When the model has no supported capability.
	 */
	protected static function createModel( ModelMetadata $model_metadata, ProviderMetadata $provider_metadata ): ModelInterface {
		foreach ( $model_metadata->getSupportedCapabilities() as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new Text_Generation_Model( $model_metadata, $provider_metadata );
			}
		}
		/* translators: %s: model ID. */
		throw new RuntimeException( esc_html( sprintf( __( 'Unsupported ChatGPT Account model: %s', 'webberzone-ai-account-connector' ), $model_metadata->getId() ) ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		return new ProviderMetadata(
			PROVIDER_ID,
			__( 'ChatGPT Account', 'webberzone-ai-account-connector' ),
			ProviderTypeEnum::cloud(),
			admin_url( 'options-connectors.php' ),
			RequestAuthenticationMethod::apiKey(),
			__( 'Text generation using your ChatGPT subscription. Sign in with ChatGPT; no API key needed.', 'webberzone-ai-account-connector' )
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new Availability();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new Model_Directory();
	}
}
