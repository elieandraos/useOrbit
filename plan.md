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

Nothing in the app implements file uploads yet — the `clients.photo` column is vestigial, never wired to real storage. The "Documents" tab already exists as a dead `href="#"` placeholder in `Clients/Show.vue`, next to a similarly-stubbed "Policies" tab. This section specs a concrete, buildable feature: real `documents` table, upload/download/delete flow, async processing + notifications over Reverb, and the Documents tab UI — scoped to Clients only for now, reusing this app's established Actions-pattern/org-scoping/route conventions rather than inventing new ones.

Content scanning (OCR, virus/malware scanning) is explicitly out of scope, not deferred — this is an internal tool for trusted org members, not a public upload surface, so there's no vendor integration to justify async on its own. The real justification is bulk load and request/tab decoupling: dropping 15 files at once queues 15 jobs behind a single worker, so later files genuinely wait their turn; and finalizing storage as a queued job means an upload isn't lost if the user navigates away mid-batch. Unlike the Clients export — which stays synchronous because its output is small and immediate — bulk document uploads are the one place in this app where "fire it off and tell me when it's done" is worth the infrastructure.

### Scope decisions

- **Client-only for now, but Policy is imminent, not hypothetical.** There is no `policies` table/model in this app yet (only static JSX mockups in `_design/`), and only `Client` gets the real relation/routes/UI today. But the `policies` table/model is expected within the next few weeks, so the polymorphic side isn't just schema-level (`documentable_type`/`documentable_id`) — `DocumentPolicy` is also written against a shared `Documentable` interface from the start (see Data model/Authorization below) rather than hardcoded to `Client`, since that near-term timeline makes it a concrete need now, not speculative design.
- **No categorization/tagging in v1.** The design mockups show conflicting approaches (fixed filter chips on one page, free-form tags on another) — skipped entirely for now, to be designed properly later.
- **Document delete: org owners can delete any document; members can only delete documents they uploaded themselves.** Unlike `ClientPolicy::delete`/`archive`'s flat owner-only restriction, a document has a natural individual owner (`uploaded_by`) that a client record doesn't — so members get self-service cleanup of their own mistakes/uploads, while an owner retains full authority over anything in the org.
- **Hard delete, no soft-deletes.** Row + file are removed together immediately. If a real regulatory retention requirement surfaces later, that becomes a dedicated feature — not speculative scope now.
- **No content scanning, ever.** OCR and virus/malware scanning are explicitly rejected, not deferred behind a placeholder — there's no vendor integration point left commented-in-waiting. This is a private, org-scoped ERP tool for trusted users, not a public upload surface, so there's no threat model that needs it.
- **The queued job does real work, not a timed placeholder.** It moves the file from a local staging copy to its final destination and finalizes the `documents` row — modest on today's `local` disk, but the same code path is what makes swapping to S3 later a config change rather than a rewrite, and batching many of these behind one worker is what the batch notification (below) is actually for.

### Data model

Status lives on the `documents` table itself, not a separate `uploads` table — a document is already a real domain entity (owner, associated client/policy, storage path); processing status is just additional columns on it. No wrapper table needed for batching either: grouping is handled by Laravel's own `job_batches` table (via `Bus::batch()`, see Backend below) rather than a hand-rolled `upload_batch_id` column on `documents` — a batch is a property of how a set of jobs was dispatched, not a permanent attribute of the file.

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
    $table->string('status', 20)->default('pending'); // pending|completed|failed
    $table->timestamp('stored_at')->nullable();
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['organization_id', 'status']);
});
```

- `organization_id` — required by the `BelongsToCurrentOrganization` trait (mirrors `clients.organization_id`).
- `documentable_type`/`documentable_id` via `morphs()` — polymorphic-ready; no FK since the target type varies.
- `uploaded_by` — FK to `users`; this is the notification recipient below.
- `disk` stored per-row so switching the default disk later doesn't break resolution of historical rows.
- `path` holds the file's current location — the local staging path while `status = pending`, overwritten with the final destination path once `completed`.
- No `SoftDeletes` — per the hard-delete decision above.

New `App\Models\Contracts\Documentable` interface (alongside the existing `app/Models/Concerns` and `app/Models/Scopes` subfolders), requiring `documents(): MorphMany`. `Client` implements it and gets the real `documents()` relation; `Policy` implements the same interface when it lands in the coming weeks — no `DocumentPolicy` changes needed at that point, since it's already written against the interface (see Authorization below), not the concrete `Client` class.

New `config/documents.php` centralizes disk name, max size (25 MB), and allowed mimes (`pdf, jpg, jpeg, png, doc, docx, xls, xlsx`) so validation and storage don't hardcode these twice, and swapping to S3 later is an env var change (`config/filesystems.php` already has an `s3` disk templated but inactive).

New `App\Models\Document` (`final class`, `BelongsToCurrentOrganization` + `HasFactory`), `App\Enums\DocumentStatus` (Pending/Completed/Failed, mirrors `ClientStatus`/`Gender` shape), and a `DocumentFactory`. Also requires the `job_batches` table (`php artisan queue:batches-table` — a stock Laravel migration) to back `Bus::batch()`.

### Backend

Uploading is a two-phase flow — staging is per-file and independent, batching happens once, atomically, after the client knows the full picture:

- **Phase 1 — Upload (per file, independent request)**: `app/Http/Requests/Documents/UploadDocumentRequest.php` validates the file against `config('documents.*')`. `app/Actions/Documents/UploadDocumentAction.php` (`final class`, follows the same shape as `CreateClientAction`) synchronously stashes the file to a local staging path via `$file->store('documents-staging', 'local')` — this has to happen inside the request, since the framework's temp upload file doesn't survive past it — never trusts the client-supplied filename as a path component, keeps `original_filename` separately for display/download. It then creates the `Document` row (`status = pending`, `path` = staging path) and returns its ID. No job is dispatched yet, and this file isn't part of any batch yet — a file failing validation here (e.g. over 25 MB) simply never gets a `Document` row and is excluded from phase 2, without affecting any other file in the drop.
- **Phase 2 — Finalize batch (one request per upload interaction)**: once the frontend has seen all of a drop's staging requests settle, it sends the list of successfully-staged document IDs to a single-action `DocumentsUploadBatchController`. The controller first filters that list down to documents still `status = pending` (owned by the current org) before building the batch — guards against a retried/duplicate finalize request (network retry, double-submit) re-dispatching `StoreDocumentJob` for documents an earlier batch already finished, which would otherwise let a stale request flip an already-`completed` document to `failed` when it finds the staging file already moved. `StoreDocumentJob::handle()` also no-ops if the document it's handed is no longer `pending` by the time it runs, as a second layer of the same guard against jobs racing each other. Only then is `Bus::batch($jobs)->finally(fn ($batch) => ...)->dispatch()` created — stock Laravel Job Batching, one atomic call with the complete job list, no custom locking or hand-rolled batch-tracking. `->finally()` (not `->then()`/`->catch()`) fires once every job in the batch has finished, pass or fail, and the callback queries `documents.status` for that batch's document IDs directly rather than trusting `$batch->failedJobs` — a job can complete "successfully" from Laravel's perspective while the document itself is `failed` (e.g. a caught storage error), so the notification is built from the real column, not the queue's own bookkeeping.
- **Storage finalization**: `app/Jobs/StoreDocumentJob.php` (queued Job, dispatched as part of the phase-2 batch) moves the file from its staging path to the final destination under `documents/{organization_id}/clients/{client_id}/` on `config('documents.disk')`, updates `path`, and sets `status = completed` + `stored_at` — or `status = failed` + `error_message` via the job's `failed()` hook if the move/write throws. On today's `local` disk this is a fast rename; swapping to S3 later (an env var change, per the Data model section) is what turns this into a genuine network write.
- **Delete**: `app/Actions/Documents/DeleteDocumentAction.php` deletes the DB row first, then the file from disk (an orphaned file is cheap to clean up later; a DB row pointing at a missing file breaks downloads immediately).
- **Controllers/routes**: flat controllers matching the `ClientsController`/`ClientsArchiveController` convention — `ClientDocumentsController@index/store` nested under `clients/{client:slug}/documents`, plus single-action `DocumentsUploadBatchController` (phase 2, above), `DocumentsDownloadController`, and `DocumentsDestroyController`. New `routes/documents.php`, required from `routes/web.php`. Org-scoping is automatic via `BelongsToCurrentOrganization`'s global scope on route-model binding (cross-org `{document}` 404s, same as `{client:slug}` today).
- **Authorization**: new `app/Policies/DocumentPolicy.php` (`viewAny`/`create` take `Documentable $documentable` as context — not `Client` directly, so the same methods keep working unchanged once `Policy` implements the interface; `delete($user, $document)` returns `$user->organizationRole() === 'owner' || $document->uploaded_by === $user->id`), wired via the `#[Authorize]` attribute's array form — e.g. `#[Authorize('create', [Document::class, 'client'])]` — verified against `Illuminate\Routing\Attributes\Controllers\Authorize`, which spreads `$models` into `AuthorizeMiddleware::using($ability, ...$models)` (equivalent to `can:create,App\Models\Document,client`). The route-bound `Client $client` param still satisfies the `Documentable` type-hint since `Client implements Documentable`; a future `PolicyDocumentsController` route binding a `Policy $policy` param works the same way with zero policy-class changes.
- `notifications` table (Laravel's built-in `notifications:table` migration) + one `App\Notifications\DocumentsUploadBatchProcessed` class implementing `via() => ['database', 'broadcast']` — carries the batch's counts (total/succeeded/failed) plus the affected document IDs/statuses, and links back to the client's Documents tab. The database row and the live push come from a single `$user->notify(...)` call inside the batch's `->finally()` callback, no separate systems to keep in sync. One notification per batch, not per file.
- Reverb, scaffolded via `php artisan install:broadcasting` (package, `config/broadcasting.php`, `routes/channels.php`, `REVERB_*` `.env` vars, `resources/js/echo.ts` — mostly generated, not hand-wired). Delivery over the default per-user private channel (`App.Models.User.{id}`), authorized by the standard generated `routes/channels.php` stub.
- Recipients default to the uploader only (`$user->notify(...)`). Broadening to additional recipients later (assigned agent, org admins) is `Notification::send($recipients, ...)` — a one-line change when there's an actual second recipient to design for, not a rearchitecture. A true org-wide activity feed (a shared channel many users subscribe to, rather than personal notifications) is a distinct pattern with its own questions (does everyone see everything, separate read-state?) — don't build it speculatively.

### Frontend

- **Shared shell**: `Clients/Show.vue` is currently the only consumer of the header+tabs markup. Extract `resources/js/pages/Clients/partials/ClientDetailShell.vue` (owns `<Head>`, `setLayoutProps` breadcrumbs, `ClientShowHeader`, the `<Tabs>` row) with a default slot, so Documents becomes the second real consumer instead of hand-copying the shell again.
- **Documents tab**: `resources/js/pages/Clients/Documents.vue` (same shell) with a search input, `DocumentUploadDropzone.vue` (drag/drop + hidden `<input type="file" multiple>`, copy: "Drag & drop files here — or browse" / "PDF, JPG, PNG, DOC, XLS · Max 25 MB per file", matching the `_design/` mockups), and `DocumentList.vue` (renders in-flight upload progress rows + real documents via `DocumentRow.vue` — colored extension badge, filename, size, uploader, date, status badge shown only for pending/failed, download link, delete button shown only when `DocumentPolicy::delete` allows it — org owner, or the row's own uploader). Client-side search filter only — no pagination, since per-client counts are expected to stay small.
- Upload is driven imperatively, matching the two-phase backend: one `router.post()` per file (not the usual `<Form>` component, since a drag-and-drop queue of independently-progressing files doesn't fit `<Form>`'s single-submission model) for phase-1 staging, giving each file its own progress bar and its own immediate validation feedback. Once `Promise.allSettled()` on that drop's staging requests resolves, one final `router.post()` to `DocumentsUploadBatchController` with the successfully-staged document IDs kicks off phase 2. `<Form>` is still used for `DeleteDocumentModal.vue` (mirrors `DeleteClientModal.vue`).
- `resources/js/composables/useNotifications.ts`: module-scoped `reactive({ items, unreadCount })` singleton, shared by both the bell and the full page so marking read in one place updates the other without a refetch. `unreadCount` bootstrapped via a lightweight Inertia shared prop; the list itself fetched lazily (Inertia v3's `useHttp` hook) on bell-open or page-load.
- One Echo listener, registered once at a persistent point (`app.ts` boot, or `AppLayout.vue`'s `onMounted` — both stay mounted across every Inertia navigation): `Echo.private('App.Models.User.' + user.id).notification(n => { ... })`, using Echo's built-in `.notification()` helper. Pushes into the composable and fires a `vue-sonner` toast alongside the durable row — a live nudge plus a persistent record.
- Bell (`AppTopNav.vue`, already persistent): unread badge + recent-items dropdown + "View all" link.
- `resources/js/pages/Notifications/Index.vue`: full paginated list via `$user->notifications()->paginate()`, following existing index-page conventions (no breadcrumbs, bordered page header).
- Per-document status in the Documents tab reads from a keyed collection (by document id). Rows stay `pending` until the batch's notification arrives — its payload carries each affected document's final status, so all rows in that batch update together at once rather than one-by-one as each file happens to finish; a single notification is what's designed for, so that's also the natural update boundary for the UI.
- No Pinia — one cohesive resource (notifications: list + count) read from two places that need to agree, which a single composable singleton handles without a new dependency.

### Tests

Mirrors `tests/Feature/Http/Clients/*` + `tests/Unit/Actions/Clients/*` + `tests/Unit/Policies/ClientPolicyTest.php`: `UploadDocumentActionTest`/`DeleteDocumentActionTest` (`Storage::fake()`), `StoreDocumentJobTest` (staging → final move, status transitions, failure path), a `DocumentsUploadBatchControllerTest` covering phase 2 (`Bus::fake()`/`Bus::assertBatched()` on the submitted document IDs) plus a direct test of the `->finally()` callback's status-counting logic, `DocumentPolicyTest` (org-scoped + owner-vs-non-owner for delete), and `tests/Feature/Http/Documents/{Index,Store,Download,Destroy}Test.php` (guest redirects, happy paths, oversized/disallowed-mime rejection, cross-org 403/404).

### Explicitly not built until there's a concrete need

Policy attachment (until a real `policies` table exists), document categorization/tagging, soft-delete/retention, org-wide/shared-channel broadcasting, multi-recipient notifications, resumable/chunked uploads, live per-file status updates mid-batch, server-side search/pagination on the documents tab, filtering on the notifications page, and cleanup of orphaned `pending` documents whose phase-2 finalize request never arrived (e.g. tab closed mid-batch) — a rare, low-stakes case for an internal tool; a scheduled sweep can be added if stale rows actually accumulate.

---
