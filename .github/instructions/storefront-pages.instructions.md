---
description: "Use when editing public storefront pages, catalog pages, or public templates. Covers escaping, helper reuse, and keeping controller logic small."
name: "Storefront Pages"
applyTo: "public/**/*.php"
---
# Storefront Pages Guidelines

- Prefer existing helpers from includes/bootstrap.php: e() for escaping, redirect() for redirects, and setting() for settings reads.
- Keep public-facing output escaped with e() unless raw HTML is intentionally required.
- Keep controller/page logic concise and procedural, matching the current codebase style.
- Reuse existing template includes in public/templates and templates instead of duplicating shared markup.
- Use Database helper methods with parameterized queries for all dynamic inputs.
- Preserve URL/path conventions already used in public pages and upload URLs (UPLOAD_URL).
- Treat includes/Auth.php and config/config.php as the source of truth for current auth configuration.
- For broad project context and architecture boundaries, follow .github/copilot-instructions.md.
