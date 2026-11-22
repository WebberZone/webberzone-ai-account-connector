---
slug: how-sign-in-works
title: "How Sign-In and Storage Work"
products: [webberzone-ai-account-connector]
sections: ["02-aac-advanced"]
tags: [ai-account-connector, security, oauth]
status: publish
order: 0
toc: true
---

[toc]

## Sign-in Flow

ChatGPT uses a device code. Starting a sign-in requests a code from OpenAI and shows it with a link to the device page. Your site then polls until you approve the request, and exchanges the approved code for tokens.

Grok uses the same browser consent screen as Warp's SuperGrok connection: OAuth authorization code with PKCE against `auth.x.ai`, using the public Grok CLI client. xAI only accepts the loopback redirect `http://127.0.0.1:56121/callback` for that client, and a WordPress site cannot receive a redirect to your computer, so the consent screen shows a code. The site exchanges that code, together with the PKCE verifier it stored when the attempt started. The verifier never goes to the browser.

WebberZone has no application registered with OpenAI or xAI, and this plugin is not an official integration. It reuses the sign-in flow of the providers' own command-line tools with their public OAuth client identifiers, and ChatGPT requests identify themselves as the Codex command-line client. Sign-in and requests go directly between your site and the provider. WebberZone does not receive your tokens or prompts.

Use of the plugin is subject to each provider's terms. A provider may change or block this at any time, and using a subscription outside the provider's own apps may put your account at risk.

## Where Tokens Are Stored

Tokens are saved in one option per provider, with autoload off, encrypted with libsodium. The key is derived from your WordPress `AUTH_KEY` and `AUTH_SALT`.

- Define the salts in `wp-config.php` so the key is not stored in the database next to the tokens. The plugin warns on Settings → Connectors if they live in the database.
- Changing the salts disconnects the accounts. Sign in again afterwards.

## Refreshing

Access tokens are refreshed automatically shortly before they expire (5 minutes for ChatGPT, 1 hour for Grok). A Grok sign-in attempt itself lasts 5 minutes; a ChatGPT device code lasts up to 15 minutes. Refresh tokens can rotate, so only one request refreshes at a time and others wait for its result. If a refresh fails while the current token is still valid, the plugin keeps using it and pauses refreshing for a minute.

If the provider reports that the session expired or was revoked, the plugin clears the stored sign-in and asks you to sign in again.

## Disconnecting and Uninstalling

Disconnecting removes the stored tokens and attempts to revoke the sign-in with the provider for that site. Deleting the plugin does the same for every site in a multisite network.

## External Services

| Provider | Hosts | Terms |
|---|---|---|
| ChatGPT | auth.openai.com, chatgpt.com | [Terms of Use](https://openai.com/policies/terms-of-use/), [Privacy Policy](https://openai.com/policies/privacy-policy/) |
| Grok | auth.x.ai, api.x.ai | [Terms of Service](https://x.ai/legal/terms-of-service), [Privacy Policy](https://x.ai/legal/privacy-policy) |

Generation requests can include prompts, images, and generation settings from the WordPress feature making the request.
