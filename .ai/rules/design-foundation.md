---
paths:
  - 'resources/js/pages/design-foundation/**'
---

# Design Foundation

## Design-foundation pages can't import Wayfinder routes
The design-foundation routes in routes/dev.php are registered only when APP_ENV=local. CI's lint job (.github/workflows/lint.yml) runs `php artisan wayfinder:generate --with-form` with no .env, so APP_ENV falls back to production and `@/routes/design-foundation` is never generated. Importing it breaks `npm run types:check` in CI and production builds. Design-foundation pages and snippets use hardcoded `/design-foundation/...` URLs, like DesignFoundationLayout.vue.
