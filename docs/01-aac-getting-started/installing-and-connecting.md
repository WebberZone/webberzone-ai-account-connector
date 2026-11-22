---
slug: installing-and-connecting
title: "Installing and Connecting Your Accounts"
products: [webberzone-ai-account-connector]
sections: ["01-aac-getting-started"]
tags: [ai-account-connector, installation, chatgpt, grok]
status: publish
order: 0
toc: true
---

[toc]

WebberZone AI Account Connector lets you sign in with your existing ChatGPT and Grok accounts from **Settings → Connectors**, so the WordPress AI Client can use them for text generation (ChatGPT) and text and image generation (Grok). No API key is needed.

> **Not affiliated.** This is an independent plugin. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI. Grok is a trademark of xAI. The names are used only to describe which accounts the plugin works with.

---

## Requirements

- WordPress 7.0 or later and PHP 7.4 or later
- **ChatGPT:** a ChatGPT Plus or Pro plan and a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/)
- **Grok:** an xAI account with SuperGrok or X Premium

Grok works on its own; ChatGPT needs the AI Provider for OpenAI plugin because the connector builds on its request and response handling.

---

## Install the Plugin

1. Download the latest release ZIP.
2. Go to **Plugins → Add New → Upload Plugin**, upload the ZIP, and activate it.
3. If you want ChatGPT, install and activate **AI Provider for OpenAI** as well.

---

## Before You Connect

Every connect dialog opens with a warning that you must accept before anything starts. It says that this plugin is independent and not an official OpenAI or xAI integration, that connecting this way may break the provider's terms and the provider may restrict or suspend your account, and that other code running on your site, such as plugins and themes, can still use the token while it is connected. Connect only if you are the administrator who will use it, on a site you control.

## Connect ChatGPT

1. Go to **Settings → Connectors** and choose **Sign in with ChatGPT** on the ChatGPT Account card.
2. Read the warning and tick the box to continue.
3. Approve access on the ChatGPT sign-in page that opens, and sign in if asked.
4. Your browser is then sent to an address starting with `http://127.0.0.1` that cannot load. That is expected: the address contains the authorization code for your site.
5. Copy the full address from the browser's address bar, paste it into the dialog, and choose **Connect**. The card shows **Connected** with the account email.

The sign-in is valid for up to 15 minutes. The ChatGPT connector supports text generation only.

## Connect Grok

1. Make sure your xAI account has SuperGrok or X Premium.
2. Go to **Settings → Connectors** and choose **Sign in with xAI** on the Grok Account card.
3. Read the warning and tick the box to continue.
4. Approve access on the xAI sign-in page that opens, and sign in with your X or xAI account if asked.
5. xAI cannot return to WordPress, so its page shows a one-time code. Paste it into the dialog and choose **Connect**. The code is valid for 5 minutes.

Some plans can sign in but are refused by the xAI API. If that happens, the error appears when you generate content.

---

## Allow Other Plugins to Use the Account

If the WordPress AI plugin shows a connector approval notice, approve **ChatGPT Account** or **Grok Account** for the plugins that should use it.

## Disconnect

Choose **Disconnect** on the provider's card. The plugin removes the stored sign-in from your site first, then asks the provider to revoke it. If the provider does not confirm the revocation, a notice recommends signing out of all sessions in your account settings.

## Multisite

Each site keeps its own connections. Sign in on every site that should use an account.
