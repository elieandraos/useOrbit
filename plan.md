# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

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
