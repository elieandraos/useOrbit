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

Document Uploads is built and live: client-scoped for now (`Client` is the only `Documentable` implementer), a two-phase upload flow (stage, then finalize), async processing via `StoreDocumentJob`, and completion notifications delivered over Reverb. A published architecture writeup of the pipeline was reviewed against the real codebase — models, actions, jobs, policies, controllers, tests, frontend — to judge production-readiness and rank what's left before it should handle real client data at scale.

### Verdict

Solid core, not production-solid yet. The architecture holds up well under review — durable DB row before the queue runs, real org/uploader scoping at every layer, content-sniffed MIME validation (not just extension checks), deterministic non-user-controlled storage paths, and a strong backend test suite (14 test files: staging, listing, download, delete, the full policy matrix, job retry/idempotency, pruning, notification payloads). What's missing is the stuff that only bites in production.

### High priority — fix before production

1. **S3 storage is broken / effectively dead code.** `UploadDocumentAction` always stages to the hardcoded `'local'` disk, but `StoreDocumentJob` moves the file using `config('documents.disk')` as *both* source and destination. If `DOCUMENTS_DISK=s3` is ever set, the job looks for the staged file on S3, where it never was, and fails. `document.disk` is also never updated after the move, so `DownloadDocumentAction`'s S3 branch (5-minute signed `temporaryUrl()`) is unreachable in practice — only exercised by a test that mocks `temporaryUrl()` directly, not a real end-to-end S3 flow. This is a correctness bug for any deployment that needs to run off something other than local disk (any real multi-server setup or Laravel Cloud).
2. **`DocumentsUploadBatchController` has no `#[Authorize]` attribute** — the one document endpoint that breaks this codebase's otherwise-universal "every controller declares its policy via `#[Authorize]`" convention. Currently safe only because `FinalizeDocumentsUploadBatchAction` manually re-scopes submitted IDs by `organization_id` + `uploaded_by`, but there's no policy layer to catch a future refactor that weakens or drops that scoping.
3. **View/download authorization only checks organization membership, not role.** Any org member can view/download/upload documents for any client in that org — only delete has the owner-or-uploader restriction. May be the intended access model for a small agency, but it should be a deliberate call, not an artifact of delete getting a stricter policy than view.
4. **Orphan-file / orphan-row gaps at failure boundaries, with no cleanup.** A DB insert failure right after a successful staging write orphans the file (no try/catch). `DeleteDocumentAction` deletes the DB row before the file with no surrounding transaction — a failed file-delete afterward leaves a permanently orphaned file no code will ever find again.

### Medium priority — should land soon after

- **No explicit `processing` status.** The job claims a row via `lockForUpdate()` without writing an intermediate state, leaving a narrow but real window where two workers could both consider a `pending` row theirs.
- **No content-integrity check backing the idempotency guard.** The `exists()` check treats "destination file is present" as proof of a correct prior write — a partial write from a crashed earlier attempt would pass silently. No checksum/hash comparison anywhere.
- **Raw exception messages reach the browser** via `DocumentResource::error_message` on page load/reload (not through the broadcast, which carries no error text) — should route through a safe, user-facing message instead.
- **No manual retry for failed documents.** Once a job exhausts its 3 tries, the only recovery is re-uploading from scratch.
- **Silent ID-dropping on finalize.** A submitted document ID that fails re-scoping (wrong org/uploader/status) is silently excluded with no accepted/rejected feedback to the browser.

