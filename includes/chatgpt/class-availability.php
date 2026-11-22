<?php
/**
 * ChatGPT availability.
 *
 * @package WebberZone\AI_Account_Connector
 */

namespace WebberZone\AI_Account_Connector\ChatGPT;

defined( 'ABSPATH' ) || exit;

/** ChatGPT availability. */
class Availability extends \WebberZone\AI_Account_Connector\Availability {
	const TOKEN_STORE = Token_Store::class;
}
