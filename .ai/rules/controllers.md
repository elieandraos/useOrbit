---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## JSON lookup endpoints outside api/* redirect on validation errors
bootstrap/app.php renders exceptions as JSON only for `api/*` (`shouldRenderJsonWhen`). A fetch-style JSON endpoint under a web path (e.g. `clients/search`, `world/...`) answers a FormRequest failure with a 302 back plus session errors, not a 422, even for `getJson`. Tests assert `assertRedirect()` + `assertSessionHasErrors()`; the frontend fetch treats `!response.ok || response.redirected` as no results.

## Precognitive validation failures redirect on web routes
bootstrap/app.php renders JSON only for `api/*` (`shouldRenderJsonWhen`). A Precognition request (`HandlePrecognitiveRequests` on a web route such as `policies.{class}.store`) that fails validation therefore gets a 302 back and **flashes the errors to the session**, instead of the 422 JSON the Precognition client expects. Verified 2026-10-10 against `policies.medical.store`. Before a web route uses Precognition, make failed precognitive requests render as JSON (e.g. `|| $request->isAttemptingPrecognition()`), and test it with a 422 and no session errors. Successful precognitive requests answer 204 `Precognition-Success: true` and never run the controller body.
