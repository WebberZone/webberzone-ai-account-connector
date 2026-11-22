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

WebberZone AI Account Connector lets you sign in with your existing ChatGPT and Grok accounts from **Settings → Connectors**, so the WordPress AI Client can use them for text and image generation. No API key is needed.

> **Not affiliated.** This is an independent plugin. It is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI. Grok is a trademark of xAI. The names are used only to describe which accounts the plugin works with.

---

## Requirements

- WordPress 7.0 or later and PHP 7.4 or later
- **ChatGPT:** a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/), and device code login enabled in your ChatGPT account
- **Grok:** an xAI account with SuperGrok or X Premium

Grok works on its own; ChatGPT needs the AI Provider for OpenAI plugin because the connector builds on its request and response handling.

---

## Install the Plugin

1. Download the latest release ZIP.
2. Go to **Plugins → Add New → Upload Plugin**, upload the ZIP, and activate it.
3. If you want ChatGPT, install and activate **AI Provider for OpenAI** as well.

---

## Connect ChatGPT

1. In ChatGPT, open **Settings → Security** and enable device code login for Codex.
2. In WordPress, go to **Settings → Connectors** and choose **Sign in with ChatGPT** on the ChatGPT Account card.
3. Open the ChatGPT device sign-in page shown in the dialog, sign in, and enter the code displayed.
4. Approve the request. The dialog waits for you (the code is valid for up to 15 minutes) and the card then shows **Connected** with the account email and plan.

## Connect Grok

1. Make sure your xAI account has SuperGrok or X Premium.
2. Go to **Settings → Connectors** and choose **Sign in with xAI** on the Grok Account card.
3. Approve access on the xAI page that opens. Sign in with your X or xAI account if asked.
4. That page cannot send you back to WordPress, so it shows a one-time code (it may say to copy it into Grok Build). Paste the code into the dialog and choose **Connect**.

The code is valid for 5 minutes. Some plans can sign in but are refused by the xAI API. If that happens, the error appears when you generate content.

---

## Allow Other Plugins to Use the Account

If the WordPress AI plugin shows a connector approval notice, approve **ChatGPT Account** or **Grok Account** for the plugins that should use it.

## Disconnect

Choose **Disconnect** on the provider's card. The plugin removes the stored sign-in from your site first, then asks the provider to revoke it. If the provider does not confirm the revocation, a notice recommends signing out of all sessions in your account settings.

## Multisite

Each site keeps its own connections. Sign in on every site that should use an account.
