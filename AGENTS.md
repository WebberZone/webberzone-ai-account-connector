# AGENTS.md

## Commits and pull requests

- Never add `Co-Authored-By` trailers, Claude session links, "Generated with Claude Code" lines or any other attribution to commit messages, PR titles or PR bodies.
- Author every commit as `Ajay D'Souza <ajaydsouza@users.noreply.github.com>`. If `git config user.name` is anything else, pass `-c user.name="Ajay D'Souza" -c user.email="ajaydsouza@users.noreply.github.com"` to `git commit`.
- Do not use `git commit --trailer` or any other commit message trailer.
- Use British English in commit messages.

## Providers

Keep the ChatGPT and Grok provider IDs and the OAuth client configuration stable. Keep provider-independent code in `includes` and provider-specific code under `includes/chatgpt` and `includes/grok`. The provider-independent classes (`Admin`, `Connectors`, `OAuth_Client`, `Token_Store`, `Availability`) and `assets/js/connectors.js` read everything provider-specific from each provider's `Config`.

## Docs and site

- `docs/*.md` are edited directly, in the folders `01-aac-getting-started`, `02-aac-advanced` and `03-aac-developer-docs`.
- The GitHub Pages homepage is `index.md` with `_config.yml`, `_layouts`, `_includes` and `site-assets`. Pages excludes `docs/`.

## Asset builds

Run `pnpm install`, then `pnpm run build:assets` after changing JavaScript assets. The build generates `.min.js` siblings using Terser; CSS and RTL processing are disabled because this plugin has no runtime CSS. Commit the regenerated minified files. The Connectors page loads the minified module unless `SCRIPT_DEBUG` is enabled.

Run `composer test` and `pnpm run build:assets` before packaging with `composer zip`.
