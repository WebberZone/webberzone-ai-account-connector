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

## Provider IDs

| Provider | AI Client provider ID | Namespace |
|---|---|---|
| ChatGPT | `chatgpt-account` | `WebberZone\AI_Account_Connector\ChatGPT` |
| Grok | `grok-account` | `WebberZone\AI_Account_Connector\Grok` |

The provider ID also drives the connector slug and setting names in WordPress core.

## Filters

### `wzap_chatgpt_min_timeout`
### `wzap_grok_min_timeout`

Filters the minimum request timeout in seconds.

```php
apply_filters( 'wzap_chatgpt_min_timeout', float $minimum, string $model_id );
```

The default is `0` for ChatGPT text models and for Grok non-reasoning models, otherwise `180`. Text models keep a caller's explicit timeout when it is above the minimum, so a plugin can fall back to another provider sooner. Requests without a timeout, or with the AI Client's default, get at least 180 seconds.

### `wzap_chatgpt_codex_client_version`

Filters the Codex CLI version string reported to the ChatGPT backend.

```php
apply_filters( 'wzap_chatgpt_codex_client_version', string $version );
```

### `wzap_chatgpt_fallback_models`
### `wzap_grok_fallback_models`

Filter the models used when the live model list cannot be fetched.

```php
apply_filters( 'wzap_chatgpt_fallback_models', array $models ); // [ 'model-id' => 'Label' ]
apply_filters( 'wzap_grok_fallback_models', array $models );    // [ 'model-id' => 'text' | 'image' ]
```

## Filters the Plugin Hooks Into

For each connected provider the plugin returns `true` from `wpai_has_ai_credentials` and `wpai_has_image_generation_support`, so the WordPress AI plugin knows credentials are available.

## Storage

| Item | ChatGPT | Grok |
|---|---|---|
| Encrypted tokens (option, autoload off) | `wzap_chatgpt_tokens` | `wzap_grok_tokens` |
| Model list cache (transient) | `wzap_chatgpt_models` | `wzap_grok_models` |
| Model list failure marker (transient) | `wzap_chatgpt_models_failed` | `wzap_grok_models_failed` |
| Refresh lock (option) | `wzap_chatgpt_refresh_lock` | `wzap_grok_refresh_lock` |
| Pending sign-in (transient prefix) | `wzap_chatgpt_flow_` | `wzap_grok_flow_` |

Tokens are encrypted with libsodium. The key is derived from `AUTH_KEY` and `AUTH_SALT` plus a per-provider context.

## AJAX Actions

Each provider registers `wp_ajax_{prefix}_start`, `_poll`, `_cancel` and `_disconnect`, where the prefix is `wzap_chatgpt` or `wzap_grok`. Every action checks a nonce (`{prefix}_admin`) and the `manage_options` capability.

## Layout

| Path | Purpose |
|---|---|
| `includes/` | Provider-independent classes: `Admin`, `Connectors`, `OAuth_Client`, `Token_Store`, `Availability` |
| `includes/chatgpt/`, `includes/grok/` | Provider-specific `Config`, `OAuth`, models, model directory and authentication |
| `assets/js/connectors.js` | Connector cards on Settings → Connectors, loaded as a script module (`.min.js` unless `SCRIPT_DEBUG`) |
