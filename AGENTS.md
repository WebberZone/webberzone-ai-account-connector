# AGENTS.md

## Commits and pull requests

- Never add `Co-Authored-By` trailers, Claude session links, "Generated with Claude Code" lines or any other attribution to commit messages, PR titles or PR bodies.
- Author every commit as `Ajay D'Souza <ajaydsouza@users.noreply.github.com>`. If `git config user.name` is anything else, pass `-c user.name="Ajay D'Souza" -c user.email="ajaydsouza@users.noreply.github.com"` to `git commit`.
- Do not use `git commit --trailer` or any other commit message trailer.
- Use British English in commit messages.

## Providers

Preserve the ChatGPT and Grok provider IDs, legacy encryption contexts, and OAuth client configuration. Keep provider-independent code in includes and provider-specific code under includes/chatgpt and includes/grok.

Run composer test and pnpm run build:assets before packaging.
