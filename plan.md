# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Document Uploads (future, not built now): client-scoped, polymorphic-ready, async with notifications + Reverb

### Context

`plan.md` already sketched the async-processing + notifications half of this feature (queued job, Reverb, notification bell) but left the actual data model, storage, and UI undesigned — and nothing in the app implements file uploads at all yet (the `clients.photo` column is vestigial, never wired to real storage). The "Documents" tab already exists as a dead `href="#"` placeholder in `Clients/Show.vue`, next to a similarly-stubbed "Policies" tab. This expands the draft into a concrete, buildable feature: real `documents` table, upload/download/delete flow, and the Documents tab UI — scoped to Clients only for now, reusing this app's established Actions-pattern/org-scoping/route conventions rather than inventing new ones.

Per-file processing (OCR, virus scanning, external API calls) is I/O-bound and can take real seconds-to-minutes independent of how many clients or policies exist — unlike the Clients export, this doesn't get faster by chunking a query. That's the actual justification for async processing + live notifications here.

### Scope decisions

- **Client-only for now.** There is no `policies` table/model in this app yet (only static JSX mockups in `_design/`). The `Document` model is built polymorphic-ready (`documentable_type`/`documentable_id`) so wiring in Policy later is additive, not a rearchitecture — but only `Client` gets the real relation/routes/UI today.
- **No categorization/tagging in v1.** The design mockups show conflicting approaches (fixed filter chips on one page, free-form tags on another) — skipped entirely for now, to be designed properly later.
- **Document delete is owner-role-only**, matching `ClientPolicy::delete`/`archive`'s existing restriction — deleting an uploaded client record is treated as sensitive as deleting/archiving the client itself.
- **Hard delete, no soft-deletes.** Row + file are removed together immediately. If a real regulatory retention requirement surfaces later, that becomes a dedicated feature — not speculative scope now.
- The async-processing job is an **honest placeholder**: no OCR/virus-scan vendor is integrated, but it flips `pending → processing → completed/failed` after a short delay so the notification plumbing below has something real to trigger against later.

### Data model

Status lives on the `documents` table itself, not a separate `uploads` table — a document is already a real domain entity (owner, associated client/policy, storage path); processing status is just additional columns on it. No wrapper table needed for individual files; multiple concurrent uploads are naturally independent, one row and one notification per file — no batching for v1 unless "this batch of files is done" becomes an actual requirement later.

`documents` table:

```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->restrictOnDelete();
    $table->morphs('documentable'); // documentable_type + documentable_id, indexed; no FK (polymorphic)
    $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
    $table->string('original_filename', 255);
    $table->string('disk', 50)->default('local');
    $table->string('path', 500);
    $table->string('mime_type', 100);
    $table->unsignedBigInteger('size'); // bytes
    $table->string('status', 20)->default('pending'); // pending|processing|completed|failed
    $table->timestamp('processed_at')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['organization_id', 'status']);
});
```

- `organization_id` — required by the `BelongsToCurrentOrganization` trait (mirrors `clients.organization_id`).
- `documentable_type`/`documentable_id` via `morphs()` — polymorphic-ready; no FK since the target type varies.
- `uploaded_by` — FK to `users`; this is the notification recipient below.
- `disk` stored per-row so switching the default disk later doesn't break resolution of historical rows.
- No `SoftDeletes` — per the hard-delete decision above.

`Client` model gets a `documents(): MorphMany` relation. New `config/documents.php` centralizes disk name, max size (25 MB), and allowed mimes (`pdf, jpg, jpeg, png, doc, docx, xls, xlsx`) so validation and storage don't hardcode these twice, and swapping to S3 later is an env var change (`config/filesystems.php` already has an `s3` disk templated but inactive).

New `App\Models\Document` (`final class`, `BelongsToCurrentOrganization` + `HasFactory`), `App\Enums\DocumentStatus` (Pending/Processing/Completed/Failed, mirrors `ClientStatus`/`Gender` shape), and a `DocumentFactory`.

### Backend

- **Upload**: `app/Http/Requests/Documents/UploadDocumentRequest.php` validates the file against `config('documents.*')`. `app/Actions/Documents/UploadDocumentAction.php` (`final class`, follows the same shape as `CreateClientAction`) stores the file via `$file->store($directory, $disk)` under `documents/{organization_id}/clients/{client_id}/` — never trusts the client-supplied filename as a path component, keeps `original_filename` separately for display/download — then creates the `Document` row and dispatches `ProcessDocumentJob::dispatch($document)->afterCommit()`. Guards against the `local` disk's `'throw' => false` behavior by explicitly checking `$path === false`.
- **Processing**: `app/Jobs/ProcessDocumentJob.php` (queued Job) sets `processing`, does the placeholder delay, then `completed` + `processed_at` (or `failed` + `error_message` via the job's `failed()` hook). This is the trigger point for the notification below — left as a commented integration point until the notifications infra is installed.
- **Delete**: `app/Actions/Documents/DeleteDocumentAction.php` deletes the DB row first, then the file from disk (an orphaned file is cheap to clean up later; a DB row pointing at a missing file breaks downloads immediately).
- **Controllers/routes**: flat controllers matching the `ClientsController`/`ClientsArchiveController` convention — `ClientDocumentsController@index/store` nested under `clients/{client:slug}/documents`, plus single-action `DocumentsDownloadController` and `DocumentsDestroyController`. New `routes/documents.php`, required from `routes/web.php`. Org-scoping is automatic via `BelongsToCurrentOrganization`'s global scope on route-model binding (cross-org `{document}` 404s, same as `{client:slug}` today).
- **Authorization**: new `app/Policies/DocumentPolicy.php` (`viewAny`/`create` take `Client $client` as context; `delete` additionally checks `$user->organizationRole() === 'owner'`), wired via the `#[Authorize]` attribute's array form — e.g. `#[Authorize('create', [Document::class, 'client'])]` — verified against `Illuminate\Routing\Attributes\Controllers\Authorize`, which spreads `$models` into `AuthorizeMiddleware::using($ability, ...$models)` (equivalent to `can:create,App\Models\Document,client`).
- `notifications` table (Laravel's built-in `notifications:table` migration) + one `App\Notifications\DocumentProcessed` class implementing `via() => ['database', 'broadcast']` — the database row and the live push come from a single `$user->notify(...)` call, no separate systems to keep in sync.
- Reverb, scaffolded via `php artisan install:broadcasting` (package, `config/broadcasting.php`, `routes/channels.php`, `REVERB_*` `.env` vars, `resources/js/echo.ts` — mostly generated, not hand-wired). Delivery over the default per-user private channel (`App.Models.User.{id}`), authorized by the standard generated `routes/channels.php` stub.
- Recipients default to the uploader only (`$user->notify(...)`). Broadening to additional recipients later (assigned agent, org admins) is `Notification::send($recipients, ...)` — a one-line change when there's an actual second recipient to design for, not a rearchitecture. A true org-wide activity feed (a shared channel many users subscribe to, rather than personal notifications) is a distinct pattern with its own questions (does everyone see everything, separate read-state?) — don't build it speculatively.

### Frontend

- **Shared shell**: `Clients/Show.vue` is currently the only consumer of the header+tabs markup. Extract `resources/js/pages/Clients/partials/ClientDetailShell.vue` (owns `<Head>`, `setLayoutProps` breadcrumbs, `ClientShowHeader`, the `<Tabs>` row) with a default slot, so Documents becomes the second real consumer instead of hand-copying the shell again.
- **Documents tab**: `resources/js/pages/Clients/Documents.vue` (same shell) with a search input, `DocumentUploadDropzone.vue` (drag/drop + hidden `<input type="file" multiple>`, copy: "Drag & drop files here — or browse" / "PDF, JPG, PNG, DOC, XLS · Max 25 MB per file", matching the `_design/` mockups), and `DocumentList.vue` (renders in-flight upload progress rows + real documents via `DocumentRow.vue` — colored extension badge, filename, size, uploader, date, status badge shown only for pending/processing/failed, download link, owner-gated delete button). Client-side search filter only — no pagination, since per-client counts are expected to stay small.
- Upload is driven imperatively via `router.post()` per file (not the usual `<Form>` component) since a drag-and-drop queue of independently-progressing files doesn't fit `<Form>`'s single-submission model; `<Form>` is still used for `DeleteDocumentModal.vue` (mirrors `DeleteClientModal.vue`).
- `resources/js/composables/useNotifications.ts`: module-scoped `reactive({ items, unreadCount })` singleton, shared by both the bell and the full page so marking read in one place updates the other without a refetch. `unreadCount` bootstrapped via a lightweight Inertia shared prop; the list itself fetched lazily (Inertia v3's `useHttp` hook) on bell-open or page-load.
- One Echo listener, registered once at a persistent point (`app.ts` boot, or `AppLayout.vue`'s `onMounted` — both stay mounted across every Inertia navigation): `Echo.private('App.Models.User.' + user.id).notification(n => { ... })`, using Echo's built-in `.notification()` helper. Pushes into the composable and fires a `vue-sonner` toast alongside the durable row — a live nudge plus a persistent record.
- Bell (`AppTopNav.vue`, already persistent): unread badge + recent-items dropdown + "View all" link.
- `resources/js/pages/Notifications/Index.vue`: full paginated list via `$user->notifications()->paginate()`, following existing index-page conventions (no breadcrumbs, bordered page header).
- Per-document status in the Documents tab reads from a keyed collection (by document id) fed by the same mechanism — multiple simultaneous uploads show independent per-row status without extra plumbing.
- No Pinia — one cohesive resource (notifications: list + count) read from two places that need to agree, which a single composable singleton handles without a new dependency.

### Tests

Mirrors `tests/Feature/Http/Clients/*` + `tests/Unit/Actions/Clients/*` + `tests/Unit/Policies/ClientPolicyTest.php`: `UploadDocumentActionTest`/`DeleteDocumentActionTest` (`Storage::fake()`), `ProcessDocumentJobTest` (status transitions), `DocumentPolicyTest` (org-scoped + owner-vs-non-owner for delete), and `tests/Feature/Http/Documents/{Index,Store,Download,Destroy}Test.php` (guest redirects, happy paths, oversized/disallowed-mime rejection, cross-org 403/404).

### Explicitly not built until there's a concrete need

Policy attachment (until a real `policies` table exists), document categorization/tagging, soft-delete/retention, org-wide/shared-channel broadcasting, multi-recipient notifications, resumable/chunked uploads, notification batching/grouping, server-side search/pagination on the documents tab, and filtering on the notifications page.

---
