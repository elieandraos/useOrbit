# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Document Uploads — known limitations & production readiness (assessed 2026-07-24)

### Context

Document Uploads is built and live: client-scoped for now (`Client` is the only `Documentable` implementer), a two-phase upload flow (stage, then finalize), async processing via `StoreDocumentJob`, and completion notifications delivered over Reverb (see the as-built walkthrough below for the full flow). A published architecture writeup of the pipeline was reviewed against the real codebase — models, actions, jobs, policies, controllers, tests, frontend — to judge production-readiness and rank what's left before it should handle real client data at scale.

### Verdict

Solid core, not production-solid yet. The architecture holds up well under review — durable DB row before the queue runs, real org/uploader scoping at every layer, content-sniffed MIME validation (not just extension checks), deterministic non-user-controlled storage paths, and a strong backend test suite (14 test files: staging, listing, download, delete, the full policy matrix, job retry/idempotency, pruning, notification payloads). What's missing is the stuff that only bites in production.

### High priority — fix before production

1. **S3 storage is broken / effectively dead code.** `UploadDocumentAction` always stages to the hardcoded `'local'` disk, but `StoreDocumentJob` moves the file using `config('documents.disk')` as *both* source and destination. If `DOCUMENTS_DISK=s3` is ever set, the job looks for the staged file on S3, where it never was, and fails. `document.disk` is also never updated after the move, so `DownloadDocumentAction`'s S3 branch (5-minute signed `temporaryUrl()`) is unreachable in practice — only exercised by a test that mocks `temporaryUrl()` directly, not a real end-to-end S3 flow. This is a correctness bug for any deployment that needs to run off something other than local disk (any real multi-server setup or Laravel Cloud).
2. **No virus/malware scanning anywhere** — confirmed via grep across the app. Files are trusted after MIME-sniffing + size + extension checks only. Given this is an insurance-broker app handling client PII (and the design mockups reference documents like "HIPAA authorization — signed.pdf"), accepting arbitrary uploads with no scanning is a real compliance exposure, not just defense-in-depth polish.
3. **`DocumentsUploadBatchController` has no `#[Authorize]` attribute** — the one document endpoint that breaks this codebase's otherwise-universal "every controller declares its policy via `#[Authorize]`" convention. Currently safe only because `FinalizeDocumentsUploadBatchAction` manually re-scopes submitted IDs by `organization_id` + `uploaded_by`, but there's no policy layer to catch a future refactor that weakens or drops that scoping.
4. **View/download authorization only checks organization membership, not role.** Any org member can view/download/upload documents for any client in that org — only delete has the owner-or-uploader restriction. May be the intended access model for a small agency, but it should be a deliberate call, not an artifact of delete getting a stricter policy than view.
5. **Orphan-file / orphan-row gaps at failure boundaries, with no cleanup.** A DB insert failure right after a successful staging write orphans the file (no try/catch). `DeleteDocumentAction` deletes the DB row before the file with no surrounding transaction — a failed file-delete afterward leaves a permanently orphaned file no code will ever find again.

### Medium priority — should land soon after

- **No explicit `processing` status.** The job claims a row via `lockForUpdate()` without writing an intermediate state, leaving a narrow but real window where two workers could both consider a `pending` row theirs.
- **No content-integrity check backing the idempotency guard.** The `exists()` check treats "destination file is present" as proof of a correct prior write — a partial write from a crashed earlier attempt would pass silently. No checksum/hash comparison anywhere.
- **Raw exception messages reach the browser** via `DocumentResource::error_message` on page load/reload (not through the broadcast, which carries no error text) — should route through a safe, user-facing message instead.
- **No manual retry for failed documents.** Once a job exhausts its 3 tries, the only recovery is re-uploading from scratch.
- **Silent ID-dropping on finalize.** A submitted document ID that fails re-scoping (wrong org/uploader/status) is silently excluded with no accepted/rejected feedback to the browser.

### Lower priority / acceptable to defer

Only `Client` implements `Documentable` today — the interface/trait/notification/composable are already built generically, Policy/Company aren't wired up yet. No file preview (download-only). No dedup by content hash, no configurable per-type size/mime limits, no batch progress updates mid-upload, no `beforeunload` protection, no direct-to-S3 presigned multipart upload — all fine to leave as-is for now.

### Already solid — don't touch

Deterministic, server-generated storage paths (no path-traversal risk from a hostile filename); MIME validation enforced twice server-side via content-sniffing, not client `accept=`/filename trust; real org + uploader re-scoping at every step touching a document ID, including the finalize batch endpoint; genuinely async architecture with no polling and a scoped on-mount reconciliation reload as an Echo-outage fallback; strong backend test coverage across the full matrix above.

---

## Document Uploads — end-to-end walkthrough (as-built, for interview prep / onboarding)

A `Document` belongs polymorphically to a "documentable" (only `Client` today, but the morph map means `Policy` or anything else can plug in later without touching this code). Every document has a `status`: `pending` → `completed` or `failed`. The whole feature is really a state machine around that one column.

### Step 1 — Frontend, before any request

User drags files onto the dropzone on a Client's Documents tab. `DocumentUploadDropzone.vue` validates client-side first — file count vs `max_files_per_batch`, extension against an allow-list, size against a max — all pulled from `config('documents.php')` and passed down as Inertia props, so the limits live in one place on the server and the frontend just mirrors them. This is a UX nicety, not a security boundary — everything gets re-validated server-side.

### Step 2 — Phase 1: staging (one request per file)

For each accepted file, the frontend fires an independent `useHttp` POST to `clients/{slug}/documents` (`ClientDocumentsController@store`), all in parallel, each with its own progress callback for that file's progress bar.

Server side per request:
- `#[Authorize('create', [Document::class, 'client'])]` → `DocumentPolicy::create` checks the client belongs to the user's current org.
- `UploadDocumentRequest` validates size + mime.
- `UploadDocumentAction` moves the file to `documents-staging/{uuid}` on the local disk and inserts a `Document` row with `status = pending`.

This is a synchronous write, not queued — it's just a local disk move, and the frontend needs the new document's ID back immediately to hand it to phase 2. No transaction here; it's a single insert, already atomic.

At this point the file exists on disk and in the DB, but nobody's told it's "safe" yet — it's just staged.

### Step 3 — Phase 2: finalize (one request for the whole batch)

Once every staged upload in this drop has settled (`Promise.allSettled`), the frontend collects whichever IDs actually succeeded and fires **one** more request: `POST /documents/batch` → `DocumentsUploadBatchController` → `FinalizeDocumentsUploadBatchAction`.

This action re-queries those IDs with three filters stacked on top of each other:
- `organization_id = current org`
- `uploaded_by = current user`
- `status = pending`

That combination is deliberate: it closes an IDOR (someone else's guessable-but-not-yours pending document can't be swept into your batch) and it makes a duplicate/retried finalize request a safe no-op (a second click can't re-flip an already-`completed` document back through the pipeline).

It then builds one `StoreDocumentJob` per surviving document and dispatches them as a single `Bus::batch([...])->finally($callback)->dispatch()`. The controller returns immediately — the user isn't blocked waiting for files to actually be processed.

### Step 4 — The queue does the real work

Laravel's job batching writes bookkeeping to a `job_batches` table (total jobs, how many are still pending, how many failed) and each `StoreDocumentJob` lands on the `jobs` table for a worker to pick up (`QUEUE_CONNECTION=database` in this app).

Each job, when a worker runs it:

1. Bails out early if the batch was cancelled.
2. **"Claims" the document** inside `DB::transaction(...)` using `lockForUpdate()` (`SELECT ... FOR UPDATE`) — this is the one real transaction in the whole flow. It re-checks `status === pending` while holding the row lock, and no-ops if not. This is the concurrency guard: two workers can never race on the same document, and a retried attempt after a partial failure won't redo work that already finished.
3. Moves the file from staging to its permanent path — `organizations/{org_id}/{documentable_type}/{documentable_id}/{document_id}.{ext}` — skipping the move if the destination already exists (covers "job died after moving the file but before updating the row; retry just needs to finish the DB update").
4. On success: `status = completed`, `path` updated, `stored_at` stamped.
5. On failure: `tries = 3` with backoff `[10, 30, 60]` seconds handles transient blips. Once retries are exhausted, Laravel calls the job's `failed()` hook, which sets `status = failed` and stores the exception message — this is the **only** place a document becomes `failed`.

### Step 5 — Once the whole batch is done: the notification

`->finally()` only fires once **every** job in the batch has finished — success or failure, all retries exhausted — not per-file. Inside it:

- `CountDocumentsUploadBatchOutcomeAction` re-queries the real `documents.status` column for completed/failed counts — deliberately **not** trusting `$batch->failedJobs`, because a job can technically "succeed" from the queue's point of view while the document itself ended up `failed` via a caught exception inside `handle()`. The domain data (the column) is the source of truth, not the queue's bookkeeping.
- A second small query grabs `id + status` for every document in the batch.
- `$user->notify(new DocumentsUploadBatchProcessed($outcome, $documents, $client))` — one notification per batch, sent to the uploader only.

The notification has two channels: `database` (writes a row into the stock `notifications` table — uuid, `type` = the FQCN, JSON `data`, `read_at`) and `broadcast` (same payload pushed to the user's private channel — the actual Reverb transport is a separate, not-yet-built piece; today this channel is effectively inert). Payload: total/completed/failed counts, per-document id+status, the client's slug+name (for the "back to Documents tab" link), and a plain-English summary line.

### Failure/edge cases worth naming out loud

- **Abandoned uploads** — phase 1 succeeds, phase 2 never arrives (tab closed, crash). `documents:prune-stale` (scheduled hourly) deletes any `pending` document past `prune_after_hours`, plus its staging file. Silent — the user never got confirmation the file was received, so there's nothing to walk back.
- **Delete guard** — `DocumentPolicy::delete` refuses to delete a `pending` document (it might have a job in flight for it) and otherwise only the uploader or an org owner can delete.
- **Multi-tenancy** — `organization_id` is checked at nearly every layer (policy, action query filters), so nothing ever crosses org boundaries even if an ID is guessed.
- **Retry vs permanent failure** — backoff handles a flaky disk write; three strikes and it's a real, user-visible failure reflected in the notification's `failed` count.

---
