<?php
/**
 * Grok token store.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\Grok;

defined( 'ABSPATH' ) || exit;

/** Grok token store. */
class Token_Store extends \WebberZone\AI_Account_Connector\Token_Store {
	const KEY_CONTEXT       = 'webberzone-grok-account';
	const OPTION            = 'wzap_grok_tokens';
	const LEGACY_OPTION     = 'wzgka_tokens';
	const MODELS_KEY        = 'wzap_grok_models';
	const MODELS_FAILED_KEY = 'wzap_grok_models_failed';
}
