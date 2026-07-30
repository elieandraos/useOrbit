# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Issue #181 — Audit: unhandled failures surface silently instead of showing user feedback

GitHub: https://github.com/elieandraos/useOrbit/issues/181

Nightwatch (production-only, first-party Laravel observability) covers finding #2 and the last
checklist item — see the issue comment. It does **not** cover client-side JS errors or in-app user
feedback, so the 4 items below still stand. Scope agreed with the user: implement all 4.

### 1. Inertia-aware error page for 403/404/500/503

**Backend** — `app/Providers/AppServiceProvider.php`: add a `configureExceptionHandling()` method
(called from `boot()`, alongside `configureDefaults()`/`registerPolicies()`), using Inertia v3's
built-in hook:

```php
use Inertia\ExceptionResponse;
use Inertia\Inertia;

protected function configureExceptionHandling(): void
{
    Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
        if (config('app.debug')) {
            return null;
        }

        if (in_array($response->statusCode(), [403, 404, 500, 503], true)) {
            return $response->render('ErrorPage', [
                'status' => $response->statusCode(),
            ])->withSharedData();
        }

        return null;
    });
}
```

Gating on `! config('app.debug')` (not environment name) preserves Inertia's existing modal-driven
debug experience locally (see "Development" in Inertia's error-handling docs) while still being
testable in Pest by toggling `config(['app.debug' => false])` inside a test. `withSharedData()` is
required because 404s happen before the Inertia middleware runs, so shared props (`auth.user`, etc.)
aren't available otherwise.

**Frontend** — new standalone page `resources/js/pages/ErrorPage.vue` (no `AppLayout`, same as
`Welcome.vue` / `design-foundation/*`), styled like the existing `Clients/partials/EmptyState.vue`
card pattern (rounded card, icon in a tinted box, title, description, single CTA button):

- Icon/title/description per status: 403 → `ShieldAlert`, 404 → `FileQuestion`, 500 → `ServerCrash`,
  503 → `Construction` (all from `@lucide/vue`), with a generic fallback for any other status.
- CTA: `Link` + `Button` to `dashboard()` if `page.props.auth.user` is set, else `login()`.

Update the layout switch in `resources/js/app.ts` to return `null` for `name === 'ErrorPage'`
(same branch as `Welcome`).

**Tests** — new `tests/Feature/ErrorPageTest.php`:
- 403 case: reuse the existing "non-owner member is forbidden from archiving a client" setup
  (`ArchiveTest.php`), but with `config(['app.debug' => false])`, and assert
  `assertInertia(fn ($page) => $page->component('ErrorPage')->where('status', 403))`.
- 404 case: authenticated user (via `User::factory()->withOrganization()->create()` so the
  `organization` middleware passes) hits `route('clients.show', 'no-such-slug')`, same assertion
  with status 404.
- Debug-mode passthrough case: same 403 request but with `config(['app.debug' => true])`, assert the
  response is **not** an Inertia response for `ErrorPage` (i.e. the hook returns `null` and falls
  through to Laravel's default handling) — proves the local-dev gate works.

**Manual repro (after implementing, for a visual check in-browser):**
- **404** — visit any bogus URL, e.g. `/clients/does-not-exist`.
- **403** — log in as a non-owner org member, open a client that belongs to their org, click
  "Archive". The button isn't role-gated client-side, so the backend policy genuinely rejects it
  (confirmed by the existing `ArchiveTest.php` non-owner test).
- **503** — run `php artisan down` (maintenance mode returns a real 503), visit any page, then
  `php artisan up` to restore.
- **500** — no safe built-in trigger. Easiest options: temporarily add `abort(500);` to any
  controller action and remove it after checking, or just trust the Pest test since a genuine
  server error can't be manually triggered without breaking something on purpose.
- For all of the above, temporarily set `APP_DEBUG=false` in `.env` (and restart `composer run dev`
  / whatever serves the app) — otherwise Inertia's local debug modal takes over and you won't see
  the new `ErrorPage`. Remember to flip it back to `true` afterwards.

### 2. Toast severity for upload-batch notifications

`resources/js/composables/useNotificationsListener.ts` — currently always calls
`toast.success(data.summary)`. `data.meta.failed` (from `DocumentsUploadMeta`) tells us the outcome:

```ts
const { failed, completed } = data.meta;

if (failed > 0 && completed === 0) {
    toast.error(data.summary);
} else if (failed > 0) {
    toast.warning(data.summary);
} else {
    toast.success(data.summary);
}
```

**Manual repro:** upload a batch of documents where at least one fails — e.g. include a file type
`StoreDocumentJob`/validation rejects, or a corrupted file — alongside at least one valid file to
see both the all-failed (error) and partial-failure (warning) toast variants.

### 3. Toast feedback for silent `onError` paths in `useNotifications.ts`

Add a `toast.error(...)` call (import `toast` from `vue-sonner`, same as `flashToast.ts`) to the
existing `onError` handlers, in addition to the current rollback logic — don't remove the rollback:

- `fetchItems()` → e.g. `"Couldn't load notifications."`
- `loadMore()` → e.g. `"Couldn't load more notifications."`
- `markAsRead()` → e.g. `"Couldn't mark notification as read."`
- `markAllAsRead()` → e.g. `"Couldn't mark all notifications as read."`

**Manual repro:** open the notifications dropdown, then go offline (turn off wifi, or Chrome
DevTools → Network tab → throttling dropdown → "Offline") and trigger each action (scroll to load
more, click a notification, click "mark all as read") to force the XHR calls to fail and confirm
each now shows a toast instead of failing silently.

### 4. Global Vue error handler

`resources/js/app.ts` — per user decision, **toast + console.error only, no backend reporting**.
Use the `withApp` callback (Inertia v3) to get the Vue app instance since the `@inertiajs/vite`
plugin owns `createApp`/`mount`:

```ts
import { toast } from 'vue-sonner';

createInertiaApp({
    // ...existing options...
    withApp(app) {
        app.config.errorHandler = (err, instance, info) => {
            console.error(err, info);
            toast.error('Something went wrong.');
        };
    },
});

window.addEventListener('unhandledrejection', (event) => {
    console.error(event.reason);
    toast.error('Something went wrong.');
});
```

`window.onerror` is intentionally skipped — `unhandledrejection` plus Vue's `errorHandler` cover the
realistic cases (component errors, unhandled promise rejections) without double-reporting; a bare
`window.onerror` mostly duplicates what already surfaces via one of the two.

**Manual repro:** easiest is a throwaway temporary bug — e.g. in any page's `<script setup>`, add
something like `onMounted(() => { throw new Error('test') })` or call an undefined function, reload
the page, and confirm the toast appears and the error is still visible in the console. Remove the
temporary code afterwards. For the promise-rejection path, `Promise.reject('test')` in the browser
devtools console should trigger the toast without any code changes.

### Notes / open decisions for integration
- No JS unit-test runner (Vitest/Jest) exists in this repo, and `tests/Browser` doesn't exist yet
  either — items 2-4 are UI/composable behavior with no automated test coverage plan; verification
  is manual (steps above). Only item 1 gets a Pest feature test, since it's server-side and
  URL/status-code driven.
- Run `vendor/bin/pint --dirty --format agent` after the `AppServiceProvider.php` change.
- Run `npm run lint` / `vue-tsc --noEmit` after the frontend changes (new `ErrorPage.vue`, edits to
  `app.ts`, `useNotifications.ts`, `useNotificationsListener.ts`).

