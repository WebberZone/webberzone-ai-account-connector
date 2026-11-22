<?php
/**
 * ChatGPT token store.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

defined( 'ABSPATH' ) || exit;

/** ChatGPT token store. */
class Token_Store extends \WebberZone\AI_Account_Connector\Token_Store {
	const KEY_CONTEXT       = 'webberzone-chatgpt-account';
	const OPTION            = 'wzap_chatgpt_tokens';
	const LEGACY_OPTION     = 'wzcga_tokens';
	const MODELS_KEY        = 'wzap_chatgpt_models';
	const MODELS_FAILED_KEY = 'wzap_chatgpt_models_failed';
}
