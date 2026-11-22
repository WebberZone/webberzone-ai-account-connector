---
slug: hooks-reference
title: "Hooks and Internals Reference"
products: [webberzone-ai-account-connector]
sections: ["03-aac-developer-docs"]
tags: [ai-account-connector, developer, hooks]
status: publish
order: 0
toc: true
---

[toc]

[WebberZone AI Account Connector](https://github.com/WebberZone/webberzone-ai-account-connector) registers two providers with the WordPress AI Client and exposes a small set of filters, options and AJAX actions.

## Provider IDs

| Provider | AI Client provider ID | Namespace |
|---|---|---|
| ChatGPT | `chatgpt-account` | `WebberZone\AI_Account_Connector\ChatGPT` |
| Grok | `grok-account` | `WebberZone\AI_Account_Connector\Grok` |

The provider ID also drives the connector slug and setting names in WordPress core.

## Filter Hooks

### `wzap_chatgpt_min_timeout( $minimum, $model_id )`

Filters the minimum request timeout for ChatGPT, in seconds.

```php
add_filter(
	'wzap_chatgpt_min_timeout',
	function ( $minimum, $model_id ) {
		return 60.0;
	},
	10,
	2
);
```

**Parameters:**

- `$minimum` *(float)*: Minimum timeout. `0` for known models, otherwise `180`.
- `$model_id` *(string)*: Model ID, or an empty string when unknown.

**Returns:** `float`: The minimum timeout.

Text models keep a caller's explicit timeout when it is above the minimum, so a plugin can fall back to another provider sooner. Requests with no timeout, or with the AI Client's default, get at least 180 seconds.

### `wzap_grok_min_timeout( $minimum, $model_id )`

Filters the minimum request timeout for Grok, in seconds.

**Parameters:**

- `$minimum` *(float)*: Minimum timeout. `0` for non-reasoning models, otherwise `180`.
- `$model_id` *(string)*: Model ID, or an empty string when unknown.

**Returns:** `float`: The minimum timeout.

### `wzap_chatgpt_fallback_models( $models )`

Filters the ChatGPT models offered when the live model list cannot be fetched.

```php
add_filter(
	'wzap_chatgpt_fallback_models',
	function ( $models ) {
		$models['gpt-example'] = 'GPT Example';
		return $models;
	}
);
```

**Parameters:**

- `$models` *(array)*: Model ID mapped to display name.

**Returns:** `array`: The models.

### `wzap_grok_fallback_models( $models )`

Filters the Grok models offered when the live model list cannot be fetched.

**Parameters:**

- `$models` *(array)*: Model ID mapped to a type, either `text` or `image`.

**Returns:** `array`: The models.

### `wzap_enable_update_checks( $enabled )`

Filters whether the plugin checks GitHub for updates from published releases.

```php
add_filter(
	'wzap_enable_update_checks',
	'__return_false'
);
```

**Parameters:**

- `$enabled` *(bool)*: Whether update checks run. Default `true`.

**Returns:** `bool`: Whether to check for updates.

When enabled, the site contacts api.github.com. It does not send site content or credentials; WordPress may include your site URL in the HTTP User-Agent.

## Filters the Plugin Hooks Into

For each connected provider the plugin returns `true` from `wpai_has_ai_credentials`. For Grok it also returns `true` from `wpai_has_image_generation_support`, because ChatGPT does not support image generation. The WordPress AI plugin uses these to know what is available.

## Storage

| Item | ChatGPT | Grok |
|---|---|---|
| Encrypted tokens (option, autoload off) | `wzap_chatgpt_tokens` | `wzap_grok_tokens` |
| Model list cache (transient) | `wzap_chatgpt_models` | `wzap_grok_models` |
| Model list failure marker (transient) | `wzap_chatgpt_models_failed` | `wzap_grok_models_failed` |
| Refresh lock (option) | `wzap_chatgpt_refresh_lock` | `wzap_grok_refresh_lock` |
| Site host ID sent to OpenAI (option) | `wzap_chatgpt_host_id` | n/a |
| Issued OpenAI client ID, kept across disconnects (option) | `wzap_chatgpt_client` | n/a |
| Pending sign-in (transient prefix) | `wzap_chatgpt_flow_` | `wzap_grok_flow_` |

Tokens are encrypted with libsodium. The key is derived from `AUTH_KEY` and `AUTH_SALT` plus a per-provider context.

## AJAX Actions

Each provider registers five `wp_ajax_` actions, where the prefix is `wzap_chatgpt` or `wzap_grok`.

| Action | Purpose |
|---|---|
| `{prefix}_start` | Starts a sign-in and returns the sign-in URL, plus the code to enter for Grok. Requires `acknowledged=1`, which the connect dialog sends after the warning is accepted. |
| `{prefix}_exchange` | ChatGPT only. Finishes the sign-in from the pasted `code`, the redirect address. |
| `{prefix}_poll` | Grok only. Checks once whether the sign-in code was approved and returns `pending` or `connected`. |
| `{prefix}_cancel` | Abandons the pending sign-in. |
| `{prefix}_disconnect` | Removes the stored tokens and asks the provider to revoke the sign-in. |

Every action checks a nonce (`{prefix}_admin`) and the `manage_options` capability.

## Layout

| Path | Purpose |
|---|---|
| `includes/` | Provider-independent classes: `Admin`, `Connectors`, `OAuth_Client`, `Token_Store`, `Availability` |
| `includes/chatgpt/`, `includes/grok/` | Provider-specific `Config`, `OAuth`, models, model directory and authentication |
| `assets/js/connectors.js` | Connector cards on Settings → Connectors, loaded as a script module (`.min.js` unless `SCRIPT_DEBUG`) |
