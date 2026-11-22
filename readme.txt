=== WebberZone AI Account Connector ===
Contributors: ajay
Tags: ai, connectors, chatgpt, grok
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your ChatGPT and Grok accounts to the WordPress AI Client from Settings → Connectors.

== Description ==

Link ChatGPT, Grok, or both from the WordPress Connectors screen. Each provider uses its own sign-in and connection settings. ChatGPT also requires a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/). Grok works independently.

== Migrating from the standalone plugins ==

1. Install and activate WebberZone AI Account Connector on each site that uses either standalone plugin. For a network-wide installation, activate it in Network Admin.
2. Deactivate WebberZone ChatGPT Account and WebberZone Grok Account wherever they are active. Keep them installed while you verify the migration.
3. Open Settings → Connectors. An existing valid connection is transferred on the same site, including its refresh token. Keep the same WordPress authentication salts during migration.
4. After both connections are verified on every affected site, delete the standalone plugins.

Leave the standalone plugins installed if the connection fails to transfer or if a site still uses one of them.

== External services ==

The plugin contacts the corresponding service when you use its connector. Starting sign-in sends the service's OAuth client identifier and requests a device code. Polling exchanges the approved code for tokens. The plugin encrypts saved tokens using WordPress authentication salts and uses them to authenticate requests and refresh the connection. Generation requests can include prompts, images, and generation settings from the WordPress feature making the request. Disconnecting attempts remote revocation where supported.

* ChatGPT uses OpenAI's sign-in service at auth.openai.com and the ChatGPT Codex backend at chatgpt.com. See the [OpenAI Terms of Use](https://openai.com/policies/terms-of-use/) and [Privacy Policy](https://openai.com/policies/privacy-policy/).
* Grok uses xAI's sign-in service at auth.x.ai and the Grok API at api.x.ai. See the [xAI Terms of Service](https://x.ai/legal/terms-of-service) and [Privacy Policy](https://x.ai/legal/privacy-policy).

== Changelog ==

= 1.0.0 =
* Combine the ChatGPT Account and Grok Account connectors and transfer existing connections.
