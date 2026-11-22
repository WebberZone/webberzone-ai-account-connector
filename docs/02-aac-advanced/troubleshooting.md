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

| Symptom | Cause and fix |
|---|---|
| No ChatGPT card, or a notice about AI Provider for OpenAI | The ChatGPT connector needs a compatible version of [AI Provider for OpenAI](https://wordpress.org/plugins/ai-provider-for-openai/). Install and activate it. |
| "Device code login is turned off for this ChatGPT account" | Enable device code login for Codex under ChatGPT **Settings → Security**, then try again. |
| "The sign-in code expired. Start again." | The code is valid for up to 15 minutes. Choose **Try again** to request a new one. |
| "Your … session has expired or was revoked" | The provider rejected the stored sign-in. Sign in again from Settings → Connectors. |
| Account shows as disconnected after changing `wp-config.php` | The encryption key comes from `AUTH_KEY` and `AUTH_SALT`. If they changed, the stored tokens can no longer be read. Sign in again. |
| Grok signs in but generation fails | Some plans can sign in but are refused by the xAI API. Use an account with SuperGrok or X Premium. |
| "Timed out waiting for a token refresh" | Another request held the refresh lock for too long. Try again. |
| Disconnect notice says the provider did not confirm revocation | The site is disconnected. Sign out of all sessions in your provider account settings to be sure the sign-in cannot be used. |

## Still Stuck?

Open an issue on [GitHub](https://github.com/WebberZone/webberzone-ai-account-connector/issues) with your WordPress and PHP versions and the message you see.
