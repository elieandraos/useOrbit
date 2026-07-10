# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Clients Export: Excel (index) + PDF (show)

### Context

The Clients index page already has a placeholder "Export" button with no handler (`resources/js/pages/Clients/Index.vue`). We're wiring it up to export the filtered/sorted client list as an Excel spreadsheet, and adding a PDF export on the Client show page for a single client's profile. Architectural decisions made before planning:

- **Index → Excel**, via `maatwebsite/excel` — chunked `FromQuery` reads, native `->download()` streaming, no physical storage.
- **Show → PDF**, via `barryvdh/laravel-dompdf` — pure PHP rendering (dompdf engine), no headless-browser binary needed on the server, which keeps Forge deploys simple. Renders a Blade view to PDF and streams it directly (`Pdf::view(...)->download(...)`), no disk writes.
- **Delivery**: synchronous streamed download for both, no physical storage. Confirmed against real data scale rather than assumed: a user has at most ~1000 clients and ~2750 policies, well within what `maatwebsite/excel`'s chunked `FromQuery` reads produce synchronously in under a second. A queue, status table, and live notifications would add real infrastructure (a new table, a queued job, Reverb, a notifications system) for a wait time that's already imperceptible — not worth it here. That async pattern is documented separately under "Document Uploads" below, where it's actually justified.
- **Controllers stay thin & CRUDdy**: single-action invokable controllers, with the actual export logic in `app/Actions/Clients/`, matching the existing Actions pattern (`CreateClientAction`, `UpdateClientAction` — see `app/Actions/Clients/CreateClientAction.php`) and per my-laravel-patterns conventions (FormRequest + Action + thin controller).
- **PDF scope**: the Client model has no related child data yet — Policies/Quick Stats/Next Renewal/Recent Activities on the show page are all placeholder empty-states (confirmed by reading their partials, e.g. `resources/js/pages/Clients/partials/ClientPoliciesCard.vue`). So the PDF export is scoped to the client's own profile fields (same shape as `ClientResource`), structured so more sections can be appended once those features get real data.

### Backend

**Add dependencies**: `composer require maatwebsite/excel barryvdh/laravel-dompdf`.

**Actions** (new, `app/Actions/Clients/`):
- `ExportClientsToExcelAction.php` — `handle(array $filters, ?string $sortColumn, string $sortDirection): \Symfony\Component\HttpFoundation\BinaryFileResponse` (or the Excel response type). Builds the query exactly like `ClientsController::index()` (`app/Http/Controllers/ClientsController.php:38-42`) minus `->paginate()`:
  ```php
  Client::query()
      ->filter(new ClientFilter($filters))
      ->sort(new ClientSort($sortColumn, $sortDirection));
  ```
  Delegates to a `app/Exports/ClientsExport.php` class (implements `FromQuery`, `WithHeadings`, `WithMapping`) mapping the same fields `ClientResource` exposes (name, email, phone, gender, dob/age, address, enrollment date, lead source, status), then returns `Excel::download(new ClientsExport(...), 'clients.xlsx')`.
- `ExportClientToPdfAction.php` — `handle(Client $client): \Illuminate\Http\Response`. Renders a new Blade view (e.g. `resources/views/exports/client-profile.blade.php`) covering personal info, contact, address, enrollment, and emergency contact — the same data already surfaced by `PersonalInformationCard.vue`, `ContactCard.vue`, `EnrollmentCard.vue`, `EmergencyContactCard.vue`. Returns `Pdf::view('exports.client-profile', ['client' => $client])->download("{$client->slug}.pdf")`.

**Controllers** (new, single-action invokable, following existing `#[Authorize]` attribute convention):
- `app/Http/Controllers/ClientsExcelExportController.php` — `__invoke(IndexClientRequest $request, ExportClientsToExcelAction $action)`. Reuses `IndexClientRequest` as-is (already validates `search`, `gender`, `enrolled_from/to`, `age_min/max`, `archived`, `sort`, `direction` — `app/Http/Requests/Clients/IndexClientRequest.php`). Same `#[Authorize('viewAny', Client::class)]` as `index()`.
- `app/Http/Controllers/ClientsPdfExportController.php` — `__invoke(Client $client, ExportClientToPdfAction $action)`. Same `#[Authorize('view', 'client')]` as `show()`.

**Routes**: add two GET routes alongside the existing `clients` routes.
- `GET /clients/export` → `clients.export` (Excel, index-level) — **must be registered before** the resource's `clients/{client}` show route, since `Client` uses slug-based route binding (`HasSlug` trait) and `export` would otherwise be captured as a `{client}` slug.
- `GET /clients/{client}/export` → `clients.export-pdf` (PDF, per-client) — safe to register after the resource routes (distinct path shape).

**Wayfinder**: after adding routes, regenerate so `@/routes/clients` exposes `export` / `exportPdf` actions for the frontend.

### Frontend

- **Clients Index** (`resources/js/pages/Clients/Index.vue`): wire the existing placeholder Export button to `clients.export`, building its query from the same current `filters`/`sort` state already used by `FiltersDrawer.vue` and `ClientsTable.vue`. Render as a native `<a :href="...">` (or set `window.location.href` on click) rather than `router.get(...)` — this must be a real browser navigation so the browser handles the `Content-Disposition` download instead of Inertia intercepting it as an XHR visit.
- **Client Show** (`resources/js/pages/Clients/Show.vue` / `ClientHeader.vue`): add an Export action pointing at `clients.export-pdf` for the current client, same native-link approach.

### Testing

- Feature test for `ClientsExcelExportController`: seed clients with varying attributes, apply filters/sort via query params, assert the response is a file download (`Excel::fake()` + `Excel::assertDownloaded('clients.xlsx', ...)`), and verify the exported rows only include the matching/sorted clients — mirroring how filter/sort behavior is already tested for the index endpoint.
- Feature test for `ClientsPdfExportController`: assert a successful PDF response (content-type `application/pdf`, correct `Content-Disposition` filename) for an authorized user and a 403 for an unauthorized one, matching the `show` policy tests.
- Run via `php artisan test --compact --filter=Export` after writing.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes.

### Verification

- Manually exercise both export buttons in the browser: apply Clients index filters/sort, click Export, confirm the downloaded `.xlsx` matches the filtered/sorted rows on screen (including a drilled-down filter producing exactly that many rows). Open a client's show page, click Export, confirm the downloaded `.pdf` has correct profile data.
- Run the new feature tests plus the existing Clients index tests to confirm filter/sort behavior wasn't disturbed.

---

## Document Uploads (future, not built now): async with notifications + Reverb

### Context

When document uploads are built, per-file processing (OCR, virus scanning, external API calls) is I/O-bound and can take real seconds-to-minutes independent of how many clients or policies exist — unlike the Clients export above, this doesn't get faster by chunking a query. That's the actual justification for async processing + live notifications here, where it wasn't for the export.

### Data model

Status lives on the `documents` table itself, not a separate `uploads` table — a document is already a real domain entity (owner, associated client/policy, storage path); processing status is just additional columns on it (`status`: pending/processing/completed/failed, `processed_at`, an error message column for failures). No wrapper table needed for individual files; multiple concurrent uploads are naturally independent, one row and one notification per file — no batching for v1 unless "this batch of files is done" becomes an actual requirement later.

### Backend

- A queued Job processes each uploaded file and updates its `documents` row on completion/failure.
- `notifications` table (Laravel's built-in `notifications:table` migration) + one `App\Notifications\DocumentProcessed` class implementing `via() => ['database', 'broadcast']` — the database row and the live push come from a single `$user->notify(...)` call, no separate systems to keep in sync.
- Reverb, scaffolded via `php artisan install:broadcasting` (package, `config/broadcasting.php`, `routes/channels.php`, `REVERB_*` `.env` vars, `resources/js/echo.ts` — mostly generated, not hand-wired). Delivery over the default per-user private channel (`App.Models.User.{id}`), authorized by the standard generated `routes/channels.php` stub.
- Recipients default to the uploader only (`$user->notify(...)`). Broadening to additional recipients later (assigned agent, org admins) is `Notification::send($recipients, ...)` — a one-line change when there's an actual second recipient to design for, not a rearchitecture. A true org-wide activity feed (a shared channel many users subscribe to, rather than personal notifications) is a distinct pattern with its own questions (does everyone see everything, separate read-state?) — don't build it speculatively.

### Frontend

- `resources/js/composables/useNotifications.ts`: module-scoped `reactive({ items, unreadCount })` singleton, shared by both the bell and the full page so marking read in one place updates the other without a refetch. `unreadCount` bootstrapped via a lightweight Inertia shared prop; the list itself fetched lazily (Inertia v3's `useHttp` hook) on bell-open or page-load.
- One Echo listener, registered once at a persistent point (`app.ts` boot, or `AppLayout.vue`'s `onMounted` — both stay mounted across every Inertia navigation): `Echo.private('App.Models.User.' + user.id).notification(n => { ... })`, using Echo's built-in `.notification()` helper. Pushes into the composable and fires a `vue-sonner` toast alongside the durable row — a live nudge plus a persistent record.
- Bell (`AppTopNav.vue`, already persistent): unread badge + recent-items dropdown + "View all" link.
- `resources/js/pages/Notifications/Index.vue`: full paginated list via `$user->notifications()->paginate()`, following existing index-page conventions (no breadcrumbs, bordered page header).
- Per-document status in whatever upload UI gets built reads from a keyed collection (by document id) fed by the same mechanism — multiple simultaneous uploads show independent per-row status without extra plumbing.
- No Pinia — one cohesive resource (notifications: list + count) read from two places that need to agree, which a single composable singleton handles without a new dependency.

### Explicitly not built until there's a concrete need

Org-wide/shared-channel broadcasting, multi-recipient notifications, resumable/chunked uploads, notification batching/grouping, and filtering on the notifications page.

---
