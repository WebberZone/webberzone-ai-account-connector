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

Link ChatGPT, Grok, or both from the WordPress Connectors screen, then use those accounts for text generation (ChatGPT) and text and image generation (Grok) through the WordPress AI Client. No API key is needed; you sign in with your existing account.

* Sign in from Settings → Connectors. Each provider has its own card and connection, and the connect dialog opens with a warning you must accept.
* Tokens are stored encrypted and refreshed automatically.
* Disconnect at any time. The plugin attempts to revoke the sign-in with the provider.
* On multisite, each site keeps its own connection.

**Read this first.** A provider may change or block this at any time. Using a subscription outside the provider's own apps may conflict with its terms and could put your account at risk. Anyone with administrator access to your site, or any code running on it, can use your plan while you are signed in, so use the plugin on sites you control. Grok has no official programme for this, so use it entirely at your own risk. Do not use one person's subscription for other people's requests; OpenAI's terms forbid it.

ChatGPT needs a Plus or Pro plan and a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/). Grok works independently.

**Not affiliated.** This plugin is an independent project by WebberZone. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI. Grok is a trademark of xAI. The names are used only to describe which accounts the plugin works with.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/`, or install it from the Plugins screen, and activate it.
2. Go to Settings → Connectors and choose **Sign in with ChatGPT** or **Sign in with xAI** on the matching card.
3. Read the warning and tick the box. Approve access on the provider's page. For Grok, check that the code on xAI's page matches the one shown, and it connects on its own. For ChatGPT, paste back the address your browser is sent to (it starts with http://127.0.0.1 and will not load). The card shows Connected once approved.

== Frequently Asked Questions ==

= Do I need an API key? =

No. The plugin uses your ChatGPT or Grok account sign-in instead. Usage counts against that account's plan.

= What does my account need before I sign in? =

For ChatGPT, a Plus or Pro plan. For Grok, use an xAI account with SuperGrok or X Premium; some plans can sign in but are refused by the API.

= Where are my credentials stored? =

In your site's database, encrypted with a key derived from your WordPress authentication salts. Changing the salts in `wp-config.php` disconnects the accounts.

= Does it work on multisite? =

Yes. Each site connects its own accounts.

= Is this an official OpenAI or xAI plugin? =

No. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI.

== External services ==

The plugin contacts the corresponding service when you use its connector. ChatGPT sign-in sends OpenAI a registration request (the app name WebberZone AI Account Connector followed by your site's title, and a random per-site host ID) and, once you paste the address your browser was sent to, exchanges the authorization code for tokens. Grok sign-in opens xAI's consent screen and exchanges the code you paste back for tokens. Whenever the AI Client uses a connected account, the plugin may refresh the saved tokens and fetch the list of available models in the background (the OpenAI `/v1/models` endpoint and the xAI `/models` endpoint). Uninstalling the plugin attempts remote revocation for every connected account on every site. The plugin encrypts saved tokens using WordPress authentication salts and uses them to authenticate requests and refresh the connection. Generation requests can include prompts, images, and generation settings from the WordPress feature making the request. Disconnecting attempts remote revocation where supported.

How sign-in works: ChatGPT uses OpenAI's official Sign in with ChatGPT for open-source apps. Your site registers itself with OpenAI during your first sign-in, you approve access on OpenAI's page, and requests go through OpenAI's public Responses API. The programme is a preview and OpenAI's terms may not cover every kind of hosted site, so check the [Sign in with ChatGPT terms](https://openai.com/policies/sign-in-with-chatgpt-terms/). xAI has no equivalent programme for third-party apps, so Grok reuses the public OAuth client identifier of xAI's Grok command-line tools and signs in through xAI's consent screen, with a pasted code. WebberZone has no application of its own registered with either provider (with ChatGPT, each site registers itself with OpenAI as WebberZone AI Account Connector), and this plugin is not an official integration. Sign-in and requests go directly between your site and the provider; WebberZone receives no tokens or prompts. A provider may change or block this at any time, so check that your use complies with its terms.

* ChatGPT uses OpenAI's sign-in service at auth.openai.com and the OpenAI API at api.openai.com. See the [Sign in with ChatGPT terms](https://openai.com/policies/sign-in-with-chatgpt-terms/), the [OpenAI Terms of Use](https://openai.com/policies/terms-of-use/) and [Privacy Policy](https://openai.com/policies/privacy-policy/).
* Grok uses xAI's sign-in service at auth.x.ai and the Grok API at api.x.ai. See the [xAI Terms of Service](https://x.ai/legal/terms-of-service) and [Privacy Policy](https://x.ai/legal/privacy-policy).
* The plugin contacts api.github.com to check for plugin updates from GitHub releases. It does not send site content or credentials; WordPress may include your site URL in the HTTP User-Agent. Return false from the `wzap_enable_update_checks` filter to turn this off.

== Changelog ==

= 1.0.0 =
* Automatic updates from GitHub releases when a newer version is published.
* Connect ChatGPT and Grok accounts to the WordPress AI Client from Settings → Connectors.
* ChatGPT uses OpenAI's Sign in with ChatGPT for open-source apps and the public Responses API, for text generation. Paste back the address your browser is sent to.
* Grok uses xAI's device-code sign-in, for text and image generation. Enter the code on xAI's page; nothing is pasted back.
* A warning on every connect dialog that must be accepted before signing in.
