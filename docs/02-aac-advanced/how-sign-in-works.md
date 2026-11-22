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

## Sign-in Flows

### ChatGPT

ChatGPT uses OpenAI's [Sign in with ChatGPT](https://openai.com/policies/sign-in-with-chatgpt-terms/) for open-source apps: OAuth authorization code with PKCE and dynamic client registration.

1. Your site creates a one-time PKCE challenge and a random per-site host ID, and shows a link to OpenAI's authorization page. On your first sign-in OpenAI registers the app (named WebberZone AI Account Connector followed by your site's title, which you can edit before approving) and issues a client ID. The plugin keeps that ID after you disconnect and reuses it when you sign in again, as OpenAI recommends, and removes it when you delete the plugin.
2. You approve access. OpenAI redirects the browser to `http://127.0.0.1:1455/auth/callback`. Nothing listens there on your computer, so the page does not load, but the address carries the authorization code.
3. You paste that address into WordPress. The plugin checks the `state`, the granted scopes and the ID token, then exchanges the code for tokens from your server. The PKCE verifier never leaves your site.

Requests go to OpenAI's public Responses API at `api.openai.com/v1` with your access token. This path supports text generation only. OpenAI's programme is a preview, and its terms may not cover every kind of hosted site, so check the terms.

### Grok

xAI has no equivalent programme for third-party apps, so Grok reuses the public OAuth client identifier of xAI's Grok command-line tools: authorization code with PKCE against `auth.x.ai`. xAI only allows a loopback redirect that a WordPress site cannot receive, so xAI's consent page shows a one-time code instead. You paste it into WordPress, which exchanges it using the PKCE verifier kept on your server. The code is valid for 5 minutes. This is not an official xAI programme, so use it entirely at your own risk.

### Both

WebberZone has no application of its own registered with either provider (with ChatGPT, each site registers itself with OpenAI as WebberZone AI Account Connector), and this plugin is not an official integration. Sign-in and requests go directly between your site and the provider. WebberZone does not receive your tokens or prompts.

Every connect dialog opens with a warning you must accept: this is not an official integration, it may break the provider's terms and the provider may restrict or suspend your account, and other code on your site can still use the token while it is connected.

Use of the plugin is subject to each provider's terms. A provider may change or block this at any time, and using a subscription outside the provider's own apps may put your account at risk.

## Where Tokens Are Stored

Tokens are saved in one option per provider, with autoload off, encrypted with libsodium. The key is derived from your WordPress `AUTH_KEY` and `AUTH_SALT`.

- Define the salts in `wp-config.php` so the key is not stored in the database next to the tokens. The plugin warns on Settings → Connectors if they live in the database.
- Changing the salts disconnects the accounts. Sign in again afterwards.

## Refreshing

Access tokens are refreshed automatically shortly before they expire (5 minutes for ChatGPT, whose access tokens last an hour and whose refresh tokens last 30 days and rotate on every refresh; 1 hour for Grok). Refresh tokens can rotate, so only one request refreshes at a time and others wait for its result. If a refresh fails while the current token is still valid, the plugin keeps using it and pauses refreshing for a minute.

If the provider reports that the session expired or was revoked, the plugin clears the stored sign-in and asks you to sign in again.

## Disconnecting and Uninstalling

Disconnecting removes the stored tokens and attempts to revoke the sign-in with the provider for that site. Deleting the plugin does the same for every site in a multisite network.

## External Services

| Provider | Hosts | Terms |
|---|---|---|
| ChatGPT | auth.openai.com, api.openai.com | [Sign in with ChatGPT terms](https://openai.com/policies/sign-in-with-chatgpt-terms/), [Terms of Use](https://openai.com/policies/terms-of-use/), [Privacy Policy](https://openai.com/policies/privacy-policy/) |
| Grok | auth.x.ai, api.x.ai | [Terms of Service](https://x.ai/legal/terms-of-service), [Privacy Policy](https://x.ai/legal/privacy-policy) |

Generation requests can include prompts, images, and generation settings from the WordPress feature making the request.
