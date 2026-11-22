# WebberZone AI Account Connector

Connect your ChatGPT and Grok accounts to the WordPress AI Client from **Settings → Connectors**. Once linked, those accounts can power text and image generation. No API key is needed.

> **Read this first.** A provider may change or block this at any time. Using a subscription outside the provider's own apps may conflict with its terms and could put your account at risk. Anyone with administrator access to your site, or any code running on it, can use your plan while you are signed in, so use the plugin on sites you control.

> **Not affiliated.** This is an independent project by WebberZone. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI. Grok is a trademark of xAI. The names are used only to describe which accounts the plugin works with.

## Requirements

- WordPress 7.0 or later, PHP 7.4 or later
- ChatGPT: a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/), and device code login enabled under ChatGPT Settings → Security
- Grok: an xAI account with SuperGrok or X Premium

## Installation

1. Download the latest ZIP, upload it under Plugins → Add New → Upload Plugin, and activate it.
2. Go to Settings → Connectors and choose **Sign in with ChatGPT** or **Sign in with xAI** on the matching card.
3. ChatGPT: open the sign-in page, enter the code, and approve. Grok: approve access on the xAI page that opens, then paste the code that page shows back into WordPress.

## Features

- Device-code sign-in for ChatGPT, and browser sign-in for Grok (the same xAI consent screen Warp uses). Each provider has its own card and connection
- Tokens stored encrypted (key derived from your WordPress authentication salts) and refreshed automatically
- Disconnect at any time; the plugin attempts to revoke the sign-in with the provider
- Per-site connections on multisite

## Documentation

- [Installing and connecting your accounts](docs/01-aac-getting-started/installing-and-connecting.md)
- [How sign-in and storage work](docs/02-aac-advanced/how-sign-in-works.md)
- [Troubleshooting](docs/02-aac-advanced/troubleshooting.md)
- [Hooks and internals reference](docs/03-aac-developer-docs/hooks-reference.md)

## External services

The plugin contacts the provider when you use its connector: sign-in (a ChatGPT device code, or a Grok authorization-code exchange), token refresh, a background fetch of the available model list, generation requests (which can include prompts, images and settings from the requesting WordPress feature), and revocation on disconnect or uninstall.

**How sign-in works:** WebberZone has no application registered with OpenAI or xAI, and this plugin is not an official integration. ChatGPT reuses the device-code sign-in of the Codex command-line tool, and requests identify themselves as that client. Grok reuses the public Grok CLI authorization-code flow (the same consent screen Warp uses for a SuperGrok or X Premium subscription). xAI only allows that client to redirect to `http://127.0.0.1:56121/callback`, which a site cannot receive, so you paste the code the consent screen shows. This is what lets the plugin use your existing account without an API key. Sign-in and requests go directly between your site and the provider; WebberZone does not receive your tokens or prompts. Use of the plugin is subject to each provider's terms, so check that it complies with yours.

- ChatGPT: auth.openai.com and chatgpt.com. [Terms](https://openai.com/policies/terms-of-use/), [Privacy](https://openai.com/policies/privacy-policy/)
- Grok: auth.x.ai and api.x.ai. [Terms](https://x.ai/legal/terms-of-service), [Privacy](https://x.ai/legal/privacy-policy)

## Development

```bash
composer test           # phpcs, PHP compatibility, phpstan
pnpm run build:assets   # minify JS
composer zip            # build the distribution ZIP
```

## License

GPL-2.0-or-later
