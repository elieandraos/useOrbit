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
- **Delivery**: synchronous streamed download for both, no physical storage. The app currently has *no* queued jobs, no broadcasting, and no Pinia store despite a queue worker being wired into local dev tooling. Building a Forge-style persistent async indicator (job + status tracking + polling/websocket + global frontend state) is real, separate scope with no current need (today's dataset sizes don't warrant it) — documented below as a future upgrade, not built now.
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

### Documented future upgrade (not built now)

If exports later need to handle large datasets or long-running generation, the Forge-style pattern would require: an `exports` DB table (status: pending/processing/completed/failed + a temp file path, since a background job can't stream to a request that's already returned — this reintroduces physical storage, cleaned up after download or on a schedule), a queued `Job` class, a status endpoint, a Pinia store (not currently installed) to persist state across Inertia navigations, and a status indicator in `AppTopNav.vue`. Existing pieces that would help: `vue-sonner` (already wired for toasts via `resources/js/lib/flashToast.ts`), and the queue connection (`database`) already configured in `.env`.

### Verification

- Manually exercise both export buttons in the browser: apply Clients index filters/sort, click Export, confirm the downloaded `.xlsx` matches the filtered/sorted rows on screen (including a drilled-down filter producing exactly that many rows). Open a client's show page, click Export, confirm the downloaded `.pdf` has correct profile data.
- Run the new feature tests plus the existing Clients index tests to confirm filter/sort behavior wasn't disturbed.

---
