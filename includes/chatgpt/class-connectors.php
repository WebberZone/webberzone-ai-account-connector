<?php
/**
 * ChatGPT connectors.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

defined( 'ABSPATH' ) || exit;

/** ChatGPT connectors. */
class Connectors extends \WebberZone\AI_Account_Connector\Connectors {
	const PROVIDER_ID = PROVIDER_ID;
	const TOKEN_STORE = Token_Store::class;
	const CONFIG      = Config::class;
	const ADMIN       = Admin::class;
	const PREFIX      = 'wzaiac_chatgpt';
}
