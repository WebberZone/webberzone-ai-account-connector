---
slug: installing-and-connecting
title: "Installing and Connecting Your Accounts"
products: [webberzone-ai-account-connector]
sections: ["01-aac-getting-started"]
tags: [ai-account-connector, chatgpt, grok, installation]
status: publish
order: 0
---

[kbtoc]

[WebberZone AI Account Connector](https://github.com/WebberZone/webberzone-ai-account-connector) lets you sign in with your existing ChatGPT and Grok accounts from **Settings → Connectors**, so the WordPress AI Client can use them for text generation (ChatGPT) and text and image generation (Grok). No API key is needed.

The plugin is independent and is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI, and Grok is a trademark of xAI. The names describe which accounts the plugin works with.

## Requirements

- WordPress 7.0 or later and PHP 7.4 or later
- **ChatGPT:** a ChatGPT Plus or Pro plan
- **Grok:** an xAI account with SuperGrok or X Premium

## Install the Plugin

1. Download `webberzone-ai-account-connector.zip` from the [releases page](https://github.com/WebberZone/webberzone-ai-account-connector/releases) on GitHub.
2. Go to **Plugins → Add New → Upload Plugin**, upload the ZIP, and activate it.

The plugin is not on WordPress.org. GitHub releases are the supported distribution path.

ChatGPT and Grok both register through this plugin alone. You do not need to install [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/) for ChatGPT.

## Upgrading from 1.0.0

Version 1.0.1 removes the ChatGPT dependency on AI Provider for OpenAI. Update the connector from **Plugins → Installed Plugins** or by uploading a newer ZIP. Your existing ChatGPT and Grok connections stay in place. You may uninstall AI Provider for OpenAI if you installed it only for this connector.

## Updates from GitHub

When a newer published release appears on GitHub, WordPress lists it on **Plugins → Installed Plugins** like any other update. Choose **Update now** to install it.

The update check contacts GitHub only. It does not send site content or account credentials. WordPress may include your site URL in the HTTP User-Agent. To turn checks off, return false from the `wzaiac_enable_update_checks` filter. See [Hooks and internals]({{ '/docs/03-aac-developer-docs/hooks-reference/' | relative_url }}) for an example.

## What the Connectors Support

- Embeddings and text-to-speech are not available through an account sign-in. Use the provider's API key for those features.
- ChatGPT supports **text generation only**. See **Generating images** below for what to use when you need pictures.
- Grok supports text and image generation. Images from Grok Imagine are JPEG files produced from a text prompt.
- ChatGPT usage counts against your ChatGPT plan limits, which are shared with your other apps.

## Generating images

Sign in with ChatGPT is for **text** through the Responses API. OpenAI's [Sign in with ChatGPT preview limitations](https://developers.openai.com/siwc/token-sharing-open-source/preview-limitations) list image generation as an unsupported tool on that path. Calls such as the Images API return an authorization error, so this connector does not advertise image models for ChatGPT.

| What you need | What to set up |
|---|---|
| Images from your xAI plan, no API key | Connect **Grok Account** and choose a Grok image model in the AI Client. |
| Images from OpenAI billing (DALL·E, GPT Image, etc.) | Install a provider that uses an **OpenAI API key**, such as [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/). That is separate from ChatGPT Account. |
| ChatGPT plan text in WordPress | Connect **ChatGPT Account** only. Keep Grok or an API key provider for images on the same site if you need both. |

You can connect ChatGPT and Grok together. Each provider has its own card on **Settings → Connectors**. Approve each account for the plugins that should use it.

## Before You Connect

Every connect dialog opens with a warning that you must accept before anything starts. It says that this plugin is independent and not an official OpenAI or xAI integration, that connecting this way may break the provider's terms and the provider may restrict or suspend your account, and that other code running on your site, such as plugins and themes, can still use the token while it is connected. Connect only if you are the administrator who will use it, on a site you control. Grok in particular has no official xAI program, so it is entirely at your own risk.

## Connect ChatGPT

1. Go to **Settings → Connectors** and choose **Sign in with ChatGPT** on the ChatGPT Account card.
2. Read the warning and the note about what to expect, then tick the box to continue. The note tells you that your browser may show "This site can't be reached" and that the address to copy starts with `http://127.0.0.1`. The dialog stays open with the paste field ready while you approve.
3. Approve access on the ChatGPT sign-in page that opens, and sign in if asked.
4. Your browser then shows an error page ("This site can't be reached") at an address starting with `http://127.0.0.1:1455`. That is expected: OpenAI can only redirect to your own computer, and the address contains the sign-in for your site.
5. Copy the full address from the browser's address bar, paste it into the dialog, and choose **Connect**. The card shows **Connected** with the account email.

The sign-in is valid for up to 15 minutes. The ChatGPT connector supports text generation only.

## Connect Grok

1. Make sure your xAI account has SuperGrok or X Premium.
2. Go to **Settings → Connectors** and choose **Sign in with xAI** on the Grok Account card.
3. Read the warning and tick the box to continue.
4. Open the xAI sign-in page from the dialog, and sign in with your X or xAI account if asked. The page says "Sign in to Grok Build", because the plugin uses xAI's public Grok Build login rather than an app registered for WebberZone.
5. Check that the code on xAI's page matches the one in the dialog, then approve. The dialog connects on its own, so there is nothing to copy. The code is valid for 30 minutes.

Some plans can sign in but are refused by the xAI API. If that happens, the error appears when you generate content.

## Allow Other Plugins to Use the Account

If the WordPress AI plugin shows a connector approval notice, approve **ChatGPT Account** or **Grok Account** for the plugins that should use it.

## Disconnect

Choose **Disconnect** on the provider's card. For ChatGPT, disconnecting revokes the sign-in and this site can no longer use your plan. ChatGPT may still list the app afterwards, with Identity access only (your name, email and profile picture) and no plan access. The **Manage access in ChatGPT settings** link on the card stays after you disconnect so you can check it. While the plan is connected, that page also has a **Remove access** button. The plugin removes the stored sign-in from your site first, then asks the provider to revoke it. If the provider does not confirm the revocation, a notice recommends removing this site's access in your account settings, with a direct link for ChatGPT.

For Grok, disconnecting revokes the refresh token, so this site can no longer renew its sign-in. xAI may keep accepting the access token already issued until it expires, which is up to about six hours. To check or remove the access yourself, open **Connected apps** under the security settings of your xAI account at `accounts.x.ai/account#security`, where the entry is named "Grok Build".

## If Access Is Revoked Elsewhere

If you remove the app's access in ChatGPT settings, requests fail at once with a message to sign in again, and the Connectors screen checks the connection when you open it (at most every ten minutes) and shows the account as not connected.

## Multisite

Each site keeps its own connections. Sign in on every site that should use an account.