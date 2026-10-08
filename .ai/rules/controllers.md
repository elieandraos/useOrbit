---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## JSON lookup endpoints outside api/* redirect on validation errors
bootstrap/app.php renders exceptions as JSON only for `api/*` (`shouldRenderJsonWhen`). A fetch-style JSON endpoint under a web path (e.g. `clients/search`, `world/...`) answers a FormRequest failure with a 302 back plus session errors, not a 422, even for `getJson`. Tests assert `assertRedirect()` + `assertSessionHasErrors()`; the frontend fetch treats `!response.ok || response.redirected` as no results.
