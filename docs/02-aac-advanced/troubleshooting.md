---
slug: troubleshooting
title: "Troubleshooting"
products: [webberzone-ai-account-connector]
sections: ["02-aac-advanced"]
tags: [ai-account-connector, troubleshooting]
status: publish
order: 1
toc: true
---

[toc]

Fixes for the messages [WebberZone AI Account Connector](https://github.com/WebberZone/webberzone-ai-account-connector) can show while you sign in or generate content.

| Symptom | Cause and fix |
|---|---|
| No ChatGPT card, or a notice about AI Provider for OpenAI | The ChatGPT connector needs a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/). Install and activate it. |
| "The sign-in expired. Start again." | A sign-in is valid for 15 minutes with ChatGPT and 30 minutes with Grok. Choose **Try again** to start a new one. |
| "The sign-in was declined." (Grok) | You chose to deny access on xAI's page. Choose **Try again** and approve. |
| "Paste the full address from your browser" | The ChatGPT dialog needs the whole address the browser was sent to, starting with `http://127.0.0.1`, not just part of it. |
| "That address does not belong to this sign-in" | The pasted address came from an older or different sign-in attempt. Start again and use the address from the latest one. |
| "OpenAI did not grant ChatGPT plan usage" | Your plan may not support it. The ChatGPT connector needs a Plus or Pro plan. |
| ChatGPT requests fail with a usage limit | Usage counts against your ChatGPT plan's limits, which are shared with your other apps. Wait for the limit to reset. |
| "Your … session has expired or was revoked" | The provider rejected the stored sign-in. Sign in again from Settings → Connectors. |
| Account shows as disconnected after changing `wp-config.php` | The encryption key comes from `AUTH_KEY` and `AUTH_SALT`. If they changed, the stored tokens can no longer be read. Sign in again. |
| Grok signs in but generation fails | Some plans can sign in but are refused by the xAI API. Use an account with SuperGrok or X Premium. |
| "Timed out waiting for a token refresh" | Another request held the refresh lock for too long. Try again. |
| Disconnect notice says the provider did not confirm revocation | The site is disconnected. Sign out of all sessions in your provider account settings to be sure the sign-in cannot be used. |

## Still Stuck?

Open an issue on [GitHub](https://github.com/WebberZone/webberzone-ai-account-connector/issues) with your WordPress and PHP versions and the message you see.
