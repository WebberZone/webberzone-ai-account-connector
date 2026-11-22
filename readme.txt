=== WebberZone AI Account Connector ===
Contributors: ajay
Tags: ai, connectors, chatgpt, grok
Requires at least: 7.0
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your ChatGPT and Grok accounts to the WordPress AI Client from Settings → Connectors.

== Description ==

Link ChatGPT, Grok, or both from the WordPress Connectors screen, then use those accounts for text and image generation through the WordPress AI Client. No API key is needed; you sign in with your existing account.

* Sign in from Settings → Connectors with a device code. Each provider has its own card and connection.
* Tokens are stored encrypted and refreshed automatically.
* Disconnect at any time. The plugin attempts to revoke the sign-in with the provider.
* On multisite, each site keeps its own connection.

**Read this first.** A provider may change or block this at any time. Using a subscription outside the provider's own apps may conflict with its terms and could put your account at risk. Anyone with administrator access to your site, or any code running on it, can use your plan while you are signed in, so use the plugin on sites you control.

ChatGPT also requires a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/). Grok works independently.

**Not affiliated.** This plugin is an independent project by WebberZone. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI. Grok is a trademark of xAI. The names are used only to describe which accounts the plugin works with.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`, or install it from the Plugins screen, and activate it.
2. Go to Settings → Connectors and choose **Sign in with ChatGPT** or **Sign in with xAI** on the matching card.
3. Open the sign-in page shown, enter the code, and approve the request. The card shows Connected once approved.

== Frequently Asked Questions ==

= Do I need an API key? =

No. The plugin uses your ChatGPT or Grok account sign-in instead. Usage counts against that account's plan.

= What does my account need before I sign in? =

For ChatGPT, device code login must be enabled under ChatGPT Settings → Security. For Grok, use an xAI account with SuperGrok or X Premium; some plans can sign in but are refused by the API.

= Where are my credentials stored? =

In your site's database, encrypted with a key derived from your WordPress authentication salts. Changing the salts in `wp-config.php` disconnects the accounts.

= Does it work on multisite? =

Yes. Each site connects its own accounts.

= Is this an official OpenAI or xAI plugin? =

No. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI.

== External services ==

The plugin contacts the corresponding service when you use its connector. Starting sign-in sends the service's OAuth client identifier and requests a device code. Polling exchanges the approved code for tokens. Whenever the AI Client uses a connected account, the plugin may refresh the saved tokens and fetch the list of available models in the background (the ChatGPT Codex backend `/models` endpoint and the xAI `/models` endpoint). Uninstalling the plugin attempts remote revocation for every connected account on every site. The plugin encrypts saved tokens using WordPress authentication salts and uses them to authenticate requests and refresh the connection. Generation requests can include prompts, images, and generation settings from the WordPress feature making the request. Disconnecting attempts remote revocation where supported.

How sign-in works: WebberZone has no application registered with OpenAI or xAI, and this plugin is not an official integration. It reuses the device-code sign-in flow of the providers' own command-line tools, with those tools' public OAuth client identifiers, and ChatGPT requests identify themselves as the Codex command-line client. This is what lets the plugin use your existing account without an API key. Sign-in and requests go directly between your site and the provider; WebberZone does not receive your tokens or prompts. Use of the plugin is subject to each provider's terms, so check that it complies with yours.

* ChatGPT uses OpenAI's sign-in service at auth.openai.com and the ChatGPT Codex backend at chatgpt.com. See the [OpenAI Terms of Use](https://openai.com/policies/terms-of-use/) and [Privacy Policy](https://openai.com/policies/privacy-policy/).
* Grok uses xAI's sign-in service at auth.x.ai and the Grok API at api.x.ai. See the [xAI Terms of Service](https://x.ai/legal/terms-of-service) and [Privacy Policy](https://x.ai/legal/privacy-policy).

== Changelog ==

= 1.0.0 =
* Initial release: connect ChatGPT and Grok accounts to the WordPress AI Client from Settings → Connectors.
