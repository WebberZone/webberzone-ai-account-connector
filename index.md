---
title: WebberZone AI Account Connector
description: Use your ChatGPT and Grok accounts for text and image generation in the WordPress AI Client. Sign in with a device code, no API key needed.
permalink: /
---

<div class="hero">
  <div class="eyebrow">Free &middot; Open Source &middot; No API Key</div>
  <h1>Use your <em>ChatGPT</em> and <em>Grok</em> accounts in the WordPress AI Client</h1>
  <p class="lead">WebberZone AI Account Connector adds <strong>ChatGPT Account</strong> and <strong>Grok Account</strong> providers to the WordPress AI Client. Sign in from Settings&nbsp;→&nbsp;Connectors, and text and image generation run against your existing plan instead of a separately billed API key.</p>
  <div class="hero-ctas">
    <a href="#installation" class="btn-primary">Installation</a>
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
      <h3>Device-code sign-in</h3>
      <p>Click Sign in on the Connectors screen, enter the one-time code with the provider, and you're connected. Tokens are stored encrypted and refreshed automatically.</p>
    </div>
    <div class="feature-card">
      <h3>Text generation</h3>
      <p>The models available to your plan, from ChatGPT and Grok, through the standard AI Client.</p>
    </div>
    <div class="feature-card">
      <h3>Image generation</h3>
      <p>GPT Image 2 with ChatGPT, and the Grok Imagine image model with Grok.</p>
    </div>
  </div>

  <img class="screenshot" src="{{ '/site-assets/img/screenshot-connector.png' | relative_url }}" alt="The ChatGPT Account and Grok Account cards on the WordPress Settings → Connectors screen, both connected with a Disconnect button" width="652" height="291">
</div>

<div class="home-section" style="padding-top:0;">
  <div class="eyebrow">Read this first</div>
  <h2 class="section-title" style="margin-bottom:8px;">How it works, and the risk</h2>
  <div class="callout">
    <h3>Not an official OpenAI or xAI integration</h3>
    <p>This plugin is independent and is not affiliated with, endorsed by, or sponsored by OpenAI or xAI. ChatGPT is a trademark of OpenAI and Grok is a trademark of xAI; the names only describe which accounts it works with. WebberZone has no application registered with either provider. It reuses the device-code sign-in flow of the providers' own command-line tools, with their public OAuth client identifiers, and calls the same backends those tools use. ChatGPT requests identify themselves as the Codex command-line client. Sign-in and requests go directly between your site and the provider; WebberZone receives no tokens or prompts.</p>
    <ul>
      <li>A provider may change or block this at any time.</li>
      <li>Using a subscription outside the provider's own apps may conflict with its terms and could put your account at risk. Check the terms yourself.</li>
      <li>Anyone with administrator access to your site, or any code running on it, can use your plan while you are signed in. Use it on sites you control.</li>
    </ul>
  </div>
</div>

<div class="home-section" id="installation" style="padding-top:0;">
  <div class="eyebrow">Get started</div>
  <h2 class="section-title" style="margin-bottom:8px;">Installation</h2>

  <ol class="step-list">
    <li>
      <h3>Install this plugin</h3>
      <p>When a release is available, download the plugin ZIP from <a href="https://github.com/WebberZone/webberzone-ai-account-connector/releases" target="_blank">GitHub Releases</a>, then upload and activate it under Plugins → Add New → Upload Plugin.</p>
    </li>
    <li>
      <h3>For ChatGPT: add AI Provider for OpenAI and allow device login</h3>
      <p>Install and activate <a href="https://wordpress.org/plugins/ai-provider-for-openai/" target="_blank">AI Provider for OpenAI</a>, then enable device code login for Codex under <a href="https://chatgpt.com/#settings/Security" target="_blank">ChatGPT Settings → Security</a>. Grok needs neither.</p>
    </li>
    <li>
      <h3>Sign in</h3>
      <p>Go to <strong>Settings → Connectors</strong>, click <strong>Sign in with ChatGPT</strong> or <strong>Sign in with xAI</strong> on the matching card, and follow the prompts. If the AI plugin shows a connector approval notice, approve the account for the plugins that should use it.</p>
    </li>
  </ol>
</div>

<div class="home-section" style="padding-top:0;">
  <div class="eyebrow">Limits</div>
  <h2 class="section-title" style="margin-bottom:8px;">What it doesn't do</h2>
  <p style="color:var(--wz-warm-grey); max-width:64ch;">Embeddings and text-to-speech aren't available through an account sign-in; use the provider's API key for those. With ChatGPT, image editing isn't supported yet and sampling options such as temperature are accepted but ignored, because the ChatGPT backend rejects them.</p>
</div>

<div class="home-section" style="padding-top:0;">
  <div class="eyebrow">Requirements</div>
  <h2 class="section-title" style="margin-bottom:8px;">What you need</h2>
  <p style="color:var(--wz-warm-grey); max-width:64ch;">WordPress 7.0+, PHP 7.4+, and a ChatGPT account (with AI Provider for OpenAI) and/or an xAI account with SuperGrok or X Premium. See the <a href="https://github.com/WebberZone/webberzone-ai-account-connector/releases" target="_blank">releases</a> page for the changelog.</p>
</div>
