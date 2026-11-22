<?php
/**
 * ChatGPT oauth client.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

defined( 'ABSPATH' ) || exit;

/** ChatGPT oauth client. */
abstract class OAuth_Client extends \WebberZone\AI_Account_Connector\OAuth_Client {
	const TOKEN_STORE = Token_Store::class;
	const CONFIG      = Config::class;
	const FLOW_PREFIX = 'wzap_chatgpt_flow_';
	const LOCK_OPTION = 'wzap_chatgpt_refresh_lock';
}
