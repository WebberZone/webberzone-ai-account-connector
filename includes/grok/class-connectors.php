<?php
/**
 * Grok connectors.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\Grok;

defined( 'ABSPATH' ) || exit;

/** Grok connectors. */
class Connectors extends \WebberZone\AI_Account_Connector\Connectors {
	const PROVIDER_ID = PROVIDER_ID;
	const TOKEN_STORE = Token_Store::class;
	const CONFIG      = Config::class;
	const ADMIN       = Admin::class;
	const PREFIX      = 'wzap_grok';
}
