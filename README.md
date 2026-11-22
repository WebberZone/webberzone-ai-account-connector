# WebberZone AI Account Connector

Connect your ChatGPT and Grok accounts to the WordPress AI Client from **Settings → Connectors**. Once linked, those accounts can power text generation (ChatGPT) and text and image generation (Grok). No API key is needed.

> **Read this first.** A provider may change or block this at any time. Using a subscription outside the provider's own apps may conflict with its terms and could put your account at risk. Anyone with administrator access to your site, or any code running on it, can use your plan while you are signed in, so use the plugin on sites you control. Grok has no official program for this, so use it entirely at your own risk. Do not use one person's subscription for other people's requests; OpenAI's terms forbid it.

> **Not affiliated.** This is an independent project by WebberZone. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI. Grok is a trademark of xAI. The names are used only to describe which accounts the plugin works with.

## Requirements

- WordPress 7.0 or later, PHP 7.4 or later
- ChatGPT: a ChatGPT Plus or Pro plan
- Grok: an xAI account with SuperGrok or X Premium

## Installation

1. Download `webberzone-ai-account-connector.zip` from [GitHub Releases](https://github.com/WebberZone/webberzone-ai-account-connector/releases), upload it under Plugins → Add New → Upload Plugin, and activate it. Published releases also appear as updates on the Plugins screen.
2. Go to Settings → Connectors and choose **Sign in with ChatGPT** or **Sign in with xAI** on the matching card.
3. Read the warning and tick the box. Approve access on the provider's page. For Grok, check that the code on xAI's page matches the one shown, and the dialog connects on its own. For ChatGPT, paste back the address your browser is sent to (it starts with `http://127.0.0.1` and will not load).

## Features

- Sign in from Settings → Connectors with a separate card and connection for each provider. The connect dialog opens with a warning you must accept
- Tokens stored encrypted (key derived from your WordPress authentication salts) and refreshed automatically
- Disconnect at any time; the plugin attempts to revoke the sign-in with the provider
- Per-site connections on multisite

## Documentation

- [Installing and connecting your accounts](docs/01-aac-getting-started/installing-and-connecting.md)
- [How sign-in and storage work](docs/02-aac-advanced/how-sign-in-works.md)
- [Troubleshooting](docs/02-aac-advanced/troubleshooting.md)
- [Hooks and internals reference](docs/03-aac-developer-docs/hooks-reference.md)

## External services

The plugin contacts the provider when you use its connector: sign-in (registration or consent request, then token exchange), token refresh, a background fetch of the available model list, generation requests (which can include prompts, images and settings from the requesting WordPress feature), and revocation on disconnect or uninstall.

**How sign-in works:** ChatGPT uses OpenAI's official Sign in with ChatGPT for open-source apps. Your site registers itself with OpenAI during your first sign-in, you approve access on OpenAI's page, and requests go through OpenAI's public Responses API. The program is a preview and OpenAI's terms may not cover every kind of hosted site, so check the [Sign in with ChatGPT terms](https://openai.com/policies/sign-in-with-chatgpt-terms/). xAI has no equivalent program for third-party apps, so Grok reuses the public OAuth client identifier of xAI's Grok command-line tools and signs in through xAI's consent screen via the device authorization flow. You match the code on xAI's page to the dialog; nothing is pasted back into WordPress. WebberZone has no application of its own registered with either provider (with ChatGPT, each site registers itself with OpenAI as WebberZone AI Account Connector), and this plugin is not an official integration. Sign-in and requests go directly between your site and the provider; WebberZone receives no tokens or prompts. A provider may change or block this at any time, so check that your use complies with its terms.

- ChatGPT: auth.openai.com and api.openai.com. [Sign in with ChatGPT terms](https://openai.com/policies/sign-in-with-chatgpt-terms/), [Terms of Use](https://openai.com/policies/terms-of-use/), [Privacy](https://openai.com/policies/privacy-policy/)
- Grok: auth.x.ai and api.x.ai. [Terms](https://x.ai/legal/terms-of-service), [Privacy](https://x.ai/legal/privacy-policy)
- GitHub: api.github.com checks for plugin updates from GitHub releases. The plugin does not send site content or credentials; WordPress may include your site URL in the HTTP User-Agent. Return false from the `wzaiac_enable_update_checks` filter to disable update checks.

## Development

```bash
composer test           # phpcs, PHP compatibility, phpstan
pnpm run build:assets   # minify JS
composer zip            # build the distribution ZIP
```

## License

GPL-2.0-or-later
