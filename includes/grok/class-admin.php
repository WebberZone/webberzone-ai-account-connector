<?php
/**
 * Grok admin.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\Grok;

defined( 'ABSPATH' ) || exit;

/** Grok admin. */
class Admin extends \WebberZone\AI_Account_Connector\Admin {
	const PREFIX        = 'wzaiac_grok';
	const OAUTH         = OAuth::class;
	const CONFIG        = Config::class;
	const NONCE         = 'wzaiac_grok_admin';
	const REVOKE_FAILED = 'wzaiac_grok_revoke_failed_';
}
