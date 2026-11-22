---
title: WebberZone AI Account Connector
description: Use your ChatGPT and Grok accounts in the WordPress AI Client. Sign in from Settings → Connectors, no API key needed.
permalink: /
---

<div class="hero">
  <div class="eyebrow">Free &middot; Open Source &middot; No API Key</div>
  <h1>Use your <em>ChatGPT</em> and <em>Grok</em> accounts in the WordPress AI Client</h1>
  <p class="lead">WebberZone AI Account Connector adds <strong>ChatGPT Account</strong> and <strong>Grok Account</strong> providers to the WordPress AI Client. Sign in from Settings&nbsp;→&nbsp;Connectors, and generation runs against your existing plan instead of a separately billed API key: text with ChatGPT, text and images with Grok.</p>
  <div class="hero-ctas">
    <a href="#installation" class="btn-primary">Installation</a>
    <a href="{{ '/docs/' | relative_url }}" class="btn-outline">Documentation</a>
    <a href="https://github.com/WebberZone/webberzone-ai-account-connector/releases" target="_blank" class="btn-outline">View Releases</a>
    <a href="https://github.com/WebberZone/webberzone-ai-account-connector" target="_blank" class="btn-outline">View on GitHub</a>
  </div>
</div>

<div class="home-section">
  <div class="eyebrow">Overview</div>
  <h2 class="section-title" style="margin-bottom:8px;">Sign in once, use it everywhere</h2>
  <p style="color:var(--wz-warm-grey); max-width:64ch;">The providers plug into the AI Client that ships with WordPress 7.0, so any feature built on it, including the WordPress AI plugin, can use your accounts. Link ChatGPT, Grok, or both; each has its own card and connection.</p>

  <div class="feature-grid">
    <div class="feature-card">
      <h3>Sign in once</h3>
      <p>Approve access on the provider's page, then, for ChatGPT, paste back the address your browser ends up on. For Grok, check that the code on xAI's page matches the dialog and approve; WordPress connects on its own. Every connect dialog starts with a warning you must accept. Tokens are stored encrypted and refreshed automatically.</p>
    </div>
    <div class="feature-card">
      <h3>Text generation</h3>
      <p>The models available to your plan, from ChatGPT and Grok, through the standard AI Client.</p>
    </div>
    <div class="feature-card">
      <h3>Image generation</h3>
      <p>Grok Imagine with Grok. A ChatGPT plan sign-in does not support image generation.</p>
    </div>
  </div>

  <img class="screenshot" src="{{ '/site-assets/img/screenshot-connector.png' | relative_url }}" alt="The ChatGPT Account and Grok Account cards on the WordPress Settings → Connectors screen, both connected with a Disconnect button" width="649" height="286">
</div>

<div class="home-section" style="padding-top:0;">
  <div class="eyebrow">Read this first</div>
  <h2 class="section-title" style="margin-bottom:8px;">How it works, and the risk</h2>
  <div class="callout">
    <h3>Not an official OpenAI or xAI integration</h3>
    <p>This plugin is independent and is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI and Grok is a trademark of xAI; the names only describe which accounts it works with. ChatGPT uses OpenAI's official <a href="https://openai.com/policies/sign-in-with-chatgpt-terms/" target="_blank">Sign in with ChatGPT</a> for open-source apps and OpenAI's public Responses API. That program is a preview and its terms may not cover every kind of hosted site. xAI has no equivalent program, so Grok reuses the public OAuth client identifier of xAI's Grok command-line tools and signs in through xAI's consent screen via the device authorization flow. Nothing is pasted back into WordPress for Grok. WebberZone has no application of its own registered with either provider (with ChatGPT, each site registers itself with OpenAI as WebberZone AI Account Connector). Sign-in and requests go directly between your site and the provider; WebberZone receives no tokens or prompts.</p>
    <ul>
      <li>A provider may change or block this at any time. xAI has no official program for this, so Grok is entirely at your own risk.</li>
      <li>Using a subscription outside the provider's own apps may conflict with its terms and could put your account at risk. Check the terms yourself.</li>
      <li>Anyone with administrator access to your site, or any code running on it, can use your plan while you are signed in. Use it on sites you control, and do not use one person's subscription for other people's requests, which OpenAI's terms forbid.</li>
    </ul>
  </div>
</div>

<div class="home-section" id="installation" style="padding-top:0;">
  <div class="eyebrow">Get started</div>
  <h2 class="section-title" style="margin-bottom:8px;">Installation</h2>

  <ol class="step-list">
    <li>
      <h3>Install this plugin</h3>
      <p>When a release is available, download <code>webberzone-ai-account-connector.zip</code> from <a href="https://github.com/WebberZone/webberzone-ai-account-connector/releases" target="_blank">GitHub Releases</a>, then upload and activate it under Plugins → Add New → Upload Plugin. Newer releases also appear on the Plugins screen when GitHub publishes an update.</p>
    </li>
    <li>
      <h3>Sign in</h3>
      <p>Go to <strong>Settings → Connectors</strong>, click <strong>Sign in with ChatGPT</strong> or <strong>Sign in with xAI</strong> on the matching card, and follow the prompts. For Grok, check the code on xAI's page matches the dialog and approve; it connects on its own. For ChatGPT, approve access, then paste the address your browser is sent to (it starts with <code>http://127.0.0.1</code> and will not load). If the AI plugin shows a connector approval notice, approve the account for the plugins that should use it.</p>
    </li>
  </ol>
</div>

<div class="home-section" id="documentation" style="padding-top:0;">
  <div class="eyebrow">Documentation</div>
  <h2 class="section-title" style="margin-bottom:8px;">Guides and reference</h2>
  <div class="feature-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
    <div class="feature-card">
      <h3><a href="{{ '/docs/01-aac-getting-started/installing-and-connecting/' | relative_url }}">Installing and connecting</a></h3>
      <p>Requirements, installing the plugin, and signing in with ChatGPT and Grok.</p>
    </div>
    <div class="feature-card">
      <h3><a href="{{ '/docs/02-aac-advanced/how-sign-in-works/' | relative_url }}">How sign-in works</a></h3>
      <p>The sign-in flows, token storage and encryption, refreshing, and the external services used.</p>
    </div>
    <div class="feature-card">
      <h3><a href="{{ '/docs/02-aac-advanced/troubleshooting/' | relative_url }}">Troubleshooting</a></h3>
      <p>Common messages and how to fix them.</p>
    </div>
    <div class="feature-card">
      <h3><a href="{{ '/docs/03-aac-developer-docs/hooks-reference/' | relative_url }}">Hooks and internals</a></h3>
      <p>Provider IDs, filters, storage keys and AJAX actions for developers.</p>
    </div>
  </div>
</div>

<div class="home-section" style="padding-top:0;">
  <div class="eyebrow">Limits</div>
  <h2 class="section-title" style="margin-bottom:8px;">What it doesn't do</h2>
  <p style="color:var(--wz-warm-grey); max-width:64ch;">Embeddings and text-to-speech aren't available through an account sign-in; use the provider's API key for those. A ChatGPT plan sign-in supports text generation only: no image generation, and sampling options such as temperature are accepted but ignored because OpenAI rejects them. ChatGPT usage counts against your plan's limits.</p>
</div>

<div class="home-section" style="padding-top:0;">
  <div class="eyebrow">Requirements</div>
  <h2 class="section-title" style="margin-bottom:8px;">What you need</h2>
  <p style="color:var(--wz-warm-grey); max-width:64ch;">WordPress 7.0+, PHP 7.4+, and a ChatGPT Plus or Pro plan and/or an xAI account with SuperGrok or X Premium. See the <a href="https://github.com/WebberZone/webberzone-ai-account-connector/releases" target="_blank">releases</a> page for the changelog.</p>
</div>
