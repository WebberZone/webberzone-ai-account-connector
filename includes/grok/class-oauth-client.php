<?php
/**
 * Grok oauth client.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\Grok;

defined( 'ABSPATH' ) || exit;

/** Grok oauth client. */
abstract class OAuth_Client extends \WebberZone\AI_Account_Connector\OAuth_Client {
	const TOKEN_STORE = Token_Store::class;
	const CONFIG      = Config::class;
	const FLOW_PREFIX = 'wzap_grok_flow_';
	const LOCK_OPTION = 'wzap_grok_refresh_lock';
}
