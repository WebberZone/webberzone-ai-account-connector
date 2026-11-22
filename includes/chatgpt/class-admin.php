<?php
/**
 * ChatGPT admin.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

defined( 'ABSPATH' ) || exit;

/** ChatGPT admin. */
class Admin extends \WebberZone\AI_Account_Connector\Admin {
	const PREFIX        = 'wzaiac_chatgpt';
	const OAUTH         = OAuth::class;
	const CONFIG        = Config::class;
	const NONCE         = 'wzaiac_chatgpt_admin';
	const REVOKE_FAILED = 'wzaiac_chatgpt_revoke_failed_';
}
