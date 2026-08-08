# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Organization-scoped shared-database tenancy with explicit runtime context

Status: proposed, not implemented. Reviewed against the codebase as of `eb498e0`; refined in a second review pass with each point verified against the actual codebase and Laravel 13 behavior rather than accepted on faith (see "Review round 2" at the end for the concrete verifications, including one that changes the design: the assumed middleware-vs-route-model-binding order was wrong and required a real fix, not just a documentation update).

### Architectural verdict

Agreed with the proposed direction almost entirely. The database already enforces "one User, one Organization" (`organization_user.user_id` is unique), so the `belongsToMany` modeling is fiction — it buys nothing and costs a join, a pivot model, and a second source of truth (`current_organization_id`) that has to stay in sync with the pivot by convention rather than by construction. Collapsing to `User belongsTo Organization` removes an entire class of "which one is authoritative" bugs and turns an invariant that's currently enforced by a unique index into one enforced by the shape of the schema itself.

The one place I'm pushing back/refining is item 4 (fail-closed scope) and item 8 (jobs): fail-closed is the right default, but two concrete places in the current codebase will break in non-obvious ways if it's flipped on without adjustment — `Document`/`User` pruning (runs from the scheduler, no HTTP context, currently silently unscoped) and `StoreDocumentJob` (relies on Laravel re-hydrating the `Document` model during queue unserialization, which runs *before* `handle()` gets a chance to establish context). Both are called out below with a specific fix, not just a warning.

### Current problems / mismatches

1. **Two sources of truth for "which org."** `organization_user` (pivot) says a User belongs to an Organization; `users.current_organization_id` says which one is "current." Nothing keeps them in sync except application code discipline — `AcceptOrganizationInvitationAction` writes both, `CreateNewUser` writes both. A future write path that forgets to set `current_organization_id` produces a User with a pivot row but no usable session.
2. **`current_organization_id` has no FK.** Per the brief, this is deliberate today — it exists to dodge migration ordering (the `users` table migration timestamp predates the `organizations` table migration). It means the column can point at nothing and nothing enforces it.
3. **Tenancy is read from `auth()` inside a global scope.** `CurrentOrganizationScope::apply()` calls `auth()->user()` directly. Any code path without an authenticated user — queue workers, Artisan commands, the scheduler — silently gets an *unscoped* query instead of an error. This is already live and unnoticed: `Document::prunable()` and `User::prunable()` run under `php artisan model:prune` from the scheduler, with no authenticated user, so they already scan every organization. It works today only because both prunable queries happen to filter by status/expiry in a way that's safe to run cross-tenant — but that safety is accidental, not designed.
4. **`EnsureOrganizationContext` re-derives membership from the pivot on every request** (`$user->organizations()->wherePivot(...)->first()`) purely to read `status`, which is redundant once status lives directly on the User row.
5. **The pivot carries invitation lifecycle state that has nothing to do with a many-to-many relationship** — `token`, `expires_at`, `invited_by`, `joined_at` describe *one User's relationship to the one Organization they're in*, not a generic association. Modeling it as pivot data is the many-to-many assumption leaking into fields that are really just User columns.

### Final target data model

```
organizations
  id
  name
  timestamps

users
  id
  organization_id       -- FK -> organizations.id, NOT NULL, restrictOnDelete
  name
  email                 -- unique, unchanged
  password              -- nullable, unchanged (null while invited)
  role                  -- string, cast to OrganizationRole, NOT NULL
  status                -- string, cast to OrganizationMemberStatus, NOT NULL
  invited_by            -- FK -> users.id, nullable, nullOnDelete
  joined_at             -- nullable timestamp
  invitation_token      -- nullable, unique
  invitation_expires_at -- nullable timestamp
  email_verified_at, last_login_at, country_id, remember_token, timestamps  -- unchanged
```

`organization_user` is deleted outright, along with the `OrganizationMember` pivot model. No replacement table.

### User / Organization relationships

- `Organization::users(): HasMany` (was `BelongsToMany` via `OrganizationMember`).
- `Organization::owner(): ?User` becomes `$this->users()->where('role', OrganizationRole::Owner)->first()` — same shape, no pivot query.
- `User::organization(): BelongsTo` (was `belongsToMany` + separate `currentOrganization(): BelongsTo`). One relationship replaces two.
- `User::organizations()`, `User::currentOrganization()`, `User::organizationRole()` are deleted. Callers read `$user->organization_id`, `$user->role`, `$user->status` directly — they're plain columns now, not derived from a pivot lookup.

### Where role/status/invitation fields live

Directly on `users`, as columns with enum casts — not on a pivot, not split across two tables. `OrganizationRole` and `OrganizationMemberStatus` enums are unchanged; only where they're stored moves.

### OrganizationContext: responsibility and lifecycle

New class, `app/Support/Tenancy/OrganizationContext.php` (matches the app's existing flat `app/{Domain}` layout; no new top-level folder). Registered with `$this->app->scoped(...)` in `AppServiceProvider`, not `singleton()` — verified below.

Responsibility: hold the active organization ID for the current execution context, and **own the missing-context failure itself**. Nothing else — no user lookups, no role logic, no caching layer.

```php
final class OrganizationContext
{
    private ?int $organizationId = null;

    public function set(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function id(): int
    {
        return $this->organizationId
            ?? throw new LogicException('No organization context has been established.');
    }
}
```

`has()` is dropped. There is no valid reason for a caller to ask "is there a context?" and branch on it — every caller of a tenant-scoped model needs an organization ID or needs to explicitly opt out via `withoutGlobalScope()`; there's no legitimate third path. Moving the throw into `id()` means `CurrentOrganizationScope` can no longer forget to check — it physically cannot obtain a null/missing ID to build a silently-unscoped-equivalent query with. A plain `LogicException` is used rather than a dedicated `UnscopedTenantQueryException` class: nothing in the plan needs to catch this exception specifically (it's a programming-error signal, meant to surface in logs/Sentry during development and CI, not to be caught and handled) — a dedicated class would be a name with no behavior attached to it. Revisit only if something concrete needs to catch it.

Lifecycle per execution context:
- **HTTP request** — `EnsureOrganizationContext` middleware calls `OrganizationContext::set($user->organization_id)` after verifying `status === Active`.
- **Queue job** — the job establishes context explicitly as the first line of `handle()`, from a plain `organization_id` it carries (not from a re-queried model — see the `StoreDocumentJob` note below).
- **Console command** — no ambient context. Commands that operate per-organization set it explicitly per iteration; commands that operate system-wide never set it and rely on scoped models simply not being queried, or use `withoutGlobalScope()` explicitly where they legitimately need to touch tenant tables cross-org.
- **Seeder / scheduler / system operation** — no ambient context; call sites that need to touch tenant-scoped tables cross-org do so with an explicit `withoutGlobalScope(CurrentOrganizationScope::class)`, not a special "system mode." This is the "clearly named escape hatch" from item 4 of the brief — Eloquent already has it, so nothing new needs to be built.

### Middleware flow

`EnsureOrganizationContext` shrinks from "look up a pivot row matching `current_organization_id`" to:

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if ($user->status !== OrganizationMemberStatus::Active) {
        return redirect()->route('home')->with('error', 'Your membership in this organization is not active.');
    }

    app(OrganizationContext::class)->set($user->organization_id);

    return $next($request);
}
```

No membership re-query, no pivot join. `organization_id` is `NOT NULL` on `users`, so the "user has no organization" branch that exists today becomes structurally impossible and is dropped — the only thing left to check is whether their membership is `Active` (still relevant: `Suspended` isn't written by any code path today, but the enum case and the check should stay since nothing currently prevents it from being introduced, and it's cheap).

### CurrentOrganizationScope behavior

Reads from `OrganizationContext` instead of `auth()`. Fails closed by construction — the scope itself has nothing to check, because `OrganizationContext::id()` is the thing that throws:

```php
final class CurrentOrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(
            $model->qualifyColumn('organization_id'),
            app(OrganizationContext::class)->id(),
        );
    }
}
```

This is a real behavior change, not a refactor, and it has two concrete casualties in the current codebase that need to be fixed as part of the same change, not discovered later:

1. **`Document::prunable()` / `User::prunable()`** — run from `php artisan model:prune` via the scheduler, with no HTTP request and no `OrganizationContext` set. Today this "works" by accident (unscoped = cross-tenant, which happens to be what pruning wants). Under fail-closed, both must explicitly opt out: `self::query()->withoutGlobalScope(CurrentOrganizationScope::class)->where(...)`. This turns an accidental cross-tenant scan into a deliberate, documented one — which is exactly the point of the exercise.
2. **`StoreDocumentJob`** — see below.

### Policy role

Unchanged in spirit — policies stay as defense-in-depth on top of the structural scope, re-checking `organization_id` equality explicitly rather than trusting the scope alone. `OrganizationMemberPolicy` simplifies because it no longer needs `$member->organizations()->wherePivot(...)->first()->pivot` to read role/status — it reads `$member->role` / `$member->status` directly.

### Action / write behavior

Unchanged principle: Actions derive `organization_id` from the authenticated User, never from request input, and stamp it explicitly on create (`CreateClientAction`, `CreateCarrierAction`, etc. are already correct and don't need to change). The only Actions that change are the ones currently written against the pivot:

- `InviteOrganizationMemberAction` — creates the placeholder `User` with `organization_id`, `role`, `status: Invited`, `invited_by`, `invitation_token`, `invitation_expires_at` set directly on the insert, instead of `create()` + `organizations()->attach()`.
- `AcceptOrganizationInvitationAction` — locks and updates the `User` row directly (`whereKey($invitation->id)`-style, now querying `User` instead of `OrganizationMember`) instead of a pivot row plus a separate `$current->user->update()`.
- `ChangeOrganizationMemberRoleAction` — `$member->update(['role' => ...])` instead of `updateExistingPivot()`.
- `RevokeOrganizationInvitationAction` — unchanged in effect (still hard-deletes the placeholder User); no longer needs the comment about the pivot cascading, since there's no pivot to cascade.
- `RemoveOrganizationMemberAction` — unchanged in effect; `$user->current_organization_id` becomes `$user->organization_id`.
- `FindPendingOrganizationInvitationAction` — queries `User::query()->where('status', Invited)->whereNotNull('invitation_token')->where('invitation_expires_at', '>', now())->where('invitation_token', hash(...))` instead of `OrganizationMember::pendingInvitation()`. The `pendingInvitation` scope moves from `OrganizationMember` to `User` (as a `#[Scope]` method).

### Signup flow

```
create Organization
→ create User { organization_id, role: Owner, status: Active, joined_at: now() }
```

One insert instead of `User::create()` + `organizations()->attach()`. `CreateNewUser::create()` shrinks by one statement.

### Invitation flow

```
Invite:   create User { organization_id, role, status: Invited, invited_by, invitation_token, invitation_expires_at, password: null }
Accept:   verify token/expiry → set password → status: Active → joined_at: now() → clear invitation_token/invitation_expires_at
Revoke:   delete the placeholder User
```

Materially simpler: no second table, no `->pivot` indirection, no `afterCreating` two-step in tests/factories. This was the main point of the brief and it holds up — nothing in the invitation lifecycle actually needed many-to-many semantics.

### Member-removal implications at the tenancy level

No change to the reassignment-then-hard-delete behavior — that's explicitly out of scope for this pass. The only tenancy-level effect: `RemoveOrganizationMemberAction` no longer needs the "pivot cascades on delete" comment, because deleting the `User` row *is* deleting the membership now — there's nothing left to cascade. This makes the operation slightly more honest: today's comment describes an implementation detail (FK cascade) standing in for what is conceptually a single delete.

### Queue / job behavior

This is the part of the brief that most needs a concrete fix, not just a principle.

`StoreDocumentJob` currently takes `public readonly Document $document` in its constructor. Laravel's `SerializesModels` trait re-hydrates that model when the job is unserialized off the queue, **before `handle()` runs** — meaning the re-hydration query happens before the job has any chance to call `OrganizationContext::set()`. Under a fail-closed scope, that re-hydration query would throw `LogicException` (from `OrganizationContext::id()`) on every single processed job, regardless of anything done inside `handle()`.

Fix: stop passing the Eloquent model into tenant-scoped jobs. Pass primitive identifiers instead, and do the lookup explicitly after establishing context:

```php
final class StoreDocumentJob implements ShouldQueue
{
    public function __construct(
        public readonly int $documentId,
        public readonly int $organizationId,
    ) {}

    public function handle(): void
    {
        app(OrganizationContext::class)->set($this->organizationId);

        $document = $this->claim(); // now queries Document::query()->whereKey(...) safely, through the scope
        ...
    }
}
```

This is the general pattern for every future tenant-scoped job: **jobs carry `organization_id` as a plain constructor argument, establish `OrganizationContext` as the first line of `handle()`, and only then touch tenant-scoped Eloquent models.** Non-tenant-scoped jobs (nothing currently, but e.g. a job operating on a model without `BelongsToCurrentOrganization`) are unaffected. This is the concrete answer to "a future developer cannot accidentally run `Client::query()` inside a job and silently read every tenant" — it's not possible to get a working query without deliberately setting context first, and there's no model-in-constructor path that bypasses that.

### Console / seeder behavior

No ambient context. Seeders (`ClientsSeeder`, `CarriersSeeder`, `NotesSeeder`, `AgentsSeeder`) that create tenant-scoped rows across multiple organizations already know which `organization_id` they're inserting per row — they don't need `OrganizationContext` at all if they set `organization_id` on `create()` directly and never run a *read* through the scoped model without first setting context. Wherever they currently do read through a scoped model (e.g. `Client::query()->where(...)` inside a seeder to find a target row), that call needs `withoutGlobalScope(CurrentOrganizationScope::class)` since seeders are inherently cross-tenant by design. Future per-tenant Artisan commands set `OrganizationContext` explicitly per organization inside a loop; nothing generic needs to be built for this now.

### Route-model-binding guarantees — verified, and the plan's original assumption was wrong

This was checked directly against the running application rather than inferred from route-group membership, because it's the one thing that would silently break every tenant-scoped page if assumed incorrectly.

**Finding:** `EnsureOrganizationContext` does **not** run before implicit route-model binding today, despite `auth`, `verified`, and `organization` all being listed together in one `Route::middleware([...])->group()` call in `routes/web.php`. Being in the same group only controls *inclusion*, not execution order — Laravel sorts the final middleware pipeline using its internal `$middlewarePriority` list (`Illuminate\Foundation\Http\Kernel::$middlewarePriority`, unmodified by this app's `bootstrap/app.php`), and `SubstituteBindings` (route-model binding) is in that list, positioned **after** `Authenticate` (`auth`) but **before** anything else. `verified` and the custom `organization` alias are *not* in that priority list at all, so — since Laravel's `SortedMiddleware` only ever repositions middleware that appear in the priority list, and leaves everything else exactly where it fell in the original concatenation — they stay wherever the `web` group + route group produced them, which is **after** `SubstituteBindings`.

Verified two ways:
1. **Source trace** — walked `Illuminate\Routing\SortedMiddleware::sortMiddleware()` against this app's actual route (`clients.show`, `{client:slug}` implicit binding) and confirmed the final order is `... → auth → SubstituteBindings → ... → verified → organization`.
2. **Empirical confirmation** — temporarily instrumented `EnsureOrganizationContext::handle()` and `CurrentOrganizationScope::apply()` with log lines, ran the existing `tests/Feature/Http/Clients/ShowTest.php` test against a real HTTP request, and read `storage/logs/laravel.log`. Result: `CurrentOrganizationScope::apply` (triggered by `{client:slug}` binding) logs **before** `EnsureOrganizationContext running`. This is today's actual behavior — it "works" only because `CurrentOrganizationScope` currently reads `auth()->user()` directly rather than depending on `EnsureOrganizationContext` having run.

**Consequence if this had gone unverified:** under the new design, `OrganizationContext::id()` would throw on every single request to a tenant-scoped implicit-bound route (`clients.show`, `carriers.show`, etc.) — the binding query would run, hit the scope, hit `OrganizationContext::id()`, and throw, because `EnsureOrganizationContext` (the only thing that calls `OrganizationContext::set()`) hasn't executed yet. Not an edge case — total breakage of the primary navigation path, on the very first request.

**Fix, verified working:** Laravel has an exact, documented mechanism for this — [Sorting Middleware](https://laravel.com/docs/13.x/middleware#sorting-middleware), specifically the "URL Defaults and Middleware Priority" pattern, which describes this precise class of problem (custom middleware that must run before `SubstituteBindings` but has no control over its position via route grouping alone). Add to `bootstrap/app.php`:

```php
$middleware->prependToPriorityList(
    before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
    prepend: \App\Http\Middleware\EnsureOrganizationContext::class,
);
```

This explicitly inserts `EnsureOrganizationContext` into the priority list ahead of `SubstituteBindings`, so `SortedMiddleware` now moves it earlier regardless of where it's declared in any route file — a structural guarantee rather than an artifact of route file ordering that a future route change could silently re-break. Re-ran the same instrumentation with this change in place: log order flipped to `EnsureOrganizationContext running` → `CurrentOrganizationScope::apply`, confirmed correct. Then ran the full suite (`php artisan test --compact`) with the change in place: 715 passed, 4 skipped, 0 failures — no other route/middleware ordering in the app depends on the previous (accidental) order.

This addition to `bootstrap/app.php` is now a required, non-optional part of the implementation — it must land in the same step that wires `EnsureOrganizationContext` to `OrganizationContext::set()`, before the fail-closed scope is ever exercised by a real request. See the updated implementation order below.

### Database constraints / indexes

- `users.organization_id`: `foreignId()->constrained()->restrictOnDelete()`, `NOT NULL`. Gets an index automatically as part of the FK. See "Organization deletion behavior" below for why this is `restrictOnDelete()` rather than `cascadeOnDelete()`.
- `users.invited_by`: self-referential FK, `foreignId()->nullable()->constrained('users')->nullOnDelete()` — unchanged from today's pivot version, just relocated to `users`.
- `users.invitation_token`: nullable, unique — unchanged in behavior, renamed from `token`.
- `users.role`, `users.status`: string columns with enum casts, `NOT NULL` — unchanged types, relocated.
- The single-column unique constraint on `organization_user.user_id` is deleted along with the table — it's obsolete because "one org per user" is now the *only* thing the schema can express, not a constraint bolted onto a many-to-many shape.
- No change to tenant-owned domain tables (`clients`, `documents`, `notes`, `tags`, `carriers`, `agents`) — `organization_id NOT NULL` with FK stays exactly as-is; that part of the design was already correct.

**Migration-ordering fix required.** `users` is created in `0001_01_01_000000_create_users_table.php`; `organizations` is created later in `2026_06_22_183508_create_organizations_table.php`. That's *why* `current_organization_id` has no FK today — a real FK on `users.organization_id → organizations.id` needs `organizations` to exist first. Since this is dev-only and migrations can be freely rewritten: rename the organizations migration to a timestamp earlier than the users migration (e.g. `0000_01_01_000000_create_organizations_table.php`), then add `organization_id`/`role`/`status`/`invited_by`/`joined_at`/`invitation_token`/`invitation_expires_at` directly into the base users migration with a real FK. Delete `2026_06_22_183719_create_organization_user_table.php` entirely.

### Organization deletion behavior

`users.organization_id` is `restrictOnDelete()`, not `cascadeOnDelete()` — reconsidered from the original proposal.

Member removal already goes through a deliberate application workflow (`RemoveOrganizationMemberAction`) precisely because hard-deleting a `User` has downstream implications — reassigning ownership of whatever that user was responsible for — that the database can't reason about. `cascadeOnDelete()` on `users.organization_id` would mean deleting one `Organization` row silently hard-deletes every `User` in it, bypassing that workflow entirely for every member at once, with no chance to reassign anything first. That's a much bigger, much less reversible version of the same problem member removal already exists to avoid.

There is no `Organization` deletion workflow today, and this plan doesn't build one (out of scope, per the brief). `restrictOnDelete()` is the correct default in that state: it makes "delete an Organization that still has Users" a database error instead of a silent mass-delete, which is the safer failure mode for a capability that doesn't exist yet. If/when an intentional Organization-deletion workflow is built, it can explicitly detach or migrate Users first (the same "explicit, application-level workflow" principle already applied to member removal) — at which point the FK constraint simply never triggers, rather than needing to be loosened. Confirmed `restrictOnDelete()` exists on `Illuminate\Database\Schema\ForeignKeyDefinition` in this Laravel version (`vendor/laravel/framework/.../ForeignKeyDefinition.php`), so this is a drop-in swap, not new capability.

### Files likely removed

- `app/Models/OrganizationMember.php`
- `database/migrations/2026_06_22_183719_create_organization_user_table.php`
- `tests/Unit/Models/OrganizationMemberTest.php` (content moves to `UserTest.php` wherever it was testing role/status/pendingInvitation behavior)

### Files likely modified

- `app/Models/User.php` — `organization(): BelongsTo`, drop `organizations()`, `currentOrganization()`, `organizationRole()`; `prunable()` simplifies (no more `whereHas`).
- `app/Models/Organization.php` — `users(): HasMany`, `owner()` without pivot query.
- `app/Models/Scopes/CurrentOrganizationScope.php` — read `OrganizationContext::id()`; no explicit throw in the scope itself, since `id()` now owns that.
- `app/Models/Concerns/BelongsToCurrentOrganization.php` — unchanged in shape, still just registers the scope.
- `app/Http/Middleware/EnsureOrganizationContext.php` — simplified as shown above.
- `bootstrap/app.php` — **new, required.** `$middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureOrganizationContext::class)`. Without this, implicit route-model binding on tenant-scoped models runs before `OrganizationContext` is set and the fail-closed scope throws on every request. See "Route-model-binding guarantees" above.
- `app/Actions/Fortify/CreateNewUser.php`
- `app/Actions/OrganizationMembers/InviteOrganizationMemberAction.php`
- `app/Actions/OrganizationMembers/AcceptOrganizationInvitationAction.php`
- `app/Actions/OrganizationMembers/ChangeOrganizationMemberRoleAction.php`
- `app/Actions/OrganizationMembers/RemoveOrganizationMemberAction.php`
- `app/Actions/OrganizationMembers/RevokeOrganizationInvitationAction.php`
- `app/Actions/OrganizationMembers/FindPendingOrganizationInvitationAction.php`
- `app/Policies/OrganizationMemberPolicy.php`
- `app/Http/Resources/OrganizationMemberResource.php`
- `app/Jobs/StoreDocumentJob.php` — constructor takes IDs, not a Model; establishes context first.
- `database/factories/UserFactory.php` — `withOrganization()`/`forOrganization()` set columns directly instead of `attach()`. **Keep both method names and signatures unchanged** — 111 files across `tests/` and `database/` call these two methods, and none of them need to change if the factory's public surface stays stable.
- `database/migrations/0001_01_01_000000_create_users_table.php`, `2026_06_22_183508_create_organizations_table.php` (renamed/reordered).
- New: `app/Support/Tenancy/OrganizationContext.php`, registered via `$this->app->scoped(...)` in `app/Providers/AppServiceProvider.php`. No dedicated exception class — `id()` throws a plain `LogicException`.

### Tests that would need to change or be added

- `tests/Feature/Middlewares/EnsureOrganizationContextTest.php` — rewrite around the simplified middleware (no more pivot status scenarios; still test `Active` vs non-`Active` status).
- `tests/Unit/Models/OrganizationMemberTest.php` — deleted; any `pendingInvitation` scope coverage moves to a `User` model test.
- `tests/Unit/Policies/OrganizationMemberPolicyTest.php` — adjust setup (direct columns instead of pivot attach), assertions unchanged.
- `tests/Unit/Actions/OrganizationMembers/AcceptOrganizationInvitationActionTest.php` — adjust to operate on `User` instead of `OrganizationMember`.
- `tests/Feature/Http/Auth/RegistrationTest.php` — adjust assertions if they inspect the pivot table directly.
- **New:** a unit test asserting `OrganizationContext::id()` throws `LogicException` when no organization has been set, and returns the set ID otherwise (the fail-closed behavior is the single most important new guarantee in this plan and needs direct coverage, not just incidental coverage via existing feature tests).
- **New:** a test for `StoreDocumentJob` covering the queue-serialization path specifically — dispatch it for real (or via `Queue::fake()` + manual `handle()` invocation) to prove the ID-based constructor doesn't hit the fail-closed scope during unserialization.
- **New:** a test asserting `Document::prunable()` / `User::prunable()` still return cross-tenant rows after adding the explicit `withoutGlobalScope()` call — this is the regression that fail-closed would otherwise introduce silently.
- **New:** a feature test hitting a tenant-scoped implicit-bound route (e.g. `clients.show`) end-to-end as the outer proof that `EnsureOrganizationContext` → `OrganizationContext::set()` → route-model-binding query happens in that order. The existing `ShowTest.php` "authenticated user can view a client from their organization" case already exercises this path; once the fail-closed scope and the `prependToPriorityList` fix both land, that existing test passing (rather than throwing `LogicException`) *is* the regression guard — no new test file needed, just confirmation it still passes.
- Any test that currently constructs an `OrganizationMember` directly (grep for `OrganizationMember::factory()` or `new OrganizationMember`) needs updating; based on the file list gathered during review, this looks contained to the files above rather than spread further.
- The 111 files using `UserFactory::withOrganization()`/`forOrganization()` should need **no changes**, per the factory-stability point above — this is worth verifying by running the full suite after the factory change, not assuming.

### Migration strategy while still in development

No production data, no backward-compatibility migration needed. Recommended sequence:
1. Rewrite `0001_01_01_000000_create_users_table.php` and reorder `create_organizations_table.php` as described.
2. Delete `create_organization_user_table.php`.
3. `php artisan migrate:fresh --seed` locally to rebuild from scratch (confirm with the user before running this against any environment that isn't a disposable local DB — it's destructive).

### Risks / edge cases

- **Fail-closed scope breaks any code path that queries a tenant-scoped model without first setting context.** The known casualties (`prunable()` queries, `StoreDocumentJob`, and — newly identified — implicit route-model binding on every tenant-scoped route) are fixed above; the residual risk is a fourth one not yet discovered — every existing feature/unit test that hits a tenant-scoped model outside of a full HTTP request (direct model queries in Unit tests, Tinker, etc.) needs `OrganizationContext` set manually or will now throw. Recommend a Pest test helper (e.g. `withOrganizationContext(Organization $org)`) to make this a one-liner in tests that need it, rather than repeating `app(OrganizationContext::class)->set(...)` everywhere.
- **Octane / persistent workers — resolved by using `scoped()`, not a residual risk.** `$this->app->scoped(OrganizationContext::class, ...)` is flushed automatically by Laravel both "when a Laravel Octane worker processes a new request or when a Laravel queue worker processes a new job" (Laravel 13 container docs, confirmed against this app's vendored framework source: `Illuminate\Queue\QueueServiceProvider::registerWorker()` calls `$app->forgetScopedInstances()` in the per-job `$resetScope` closure passed to the `Worker`). This app doesn't have `laravel/octane` installed today, but the binding is correct either way and needs no Octane-specific follow-up later — `scoped()` already does the right thing in both the "no Octane" and "Octane later" cases, so there's nothing to flag or revisit.
- **Self-referential FK (`invited_by`) cascade behavior on `users`.** `nullOnDelete()` matches today's behavior — if an inviter is later hard-deleted (e.g. removed as a member), invitations they sent keep `invited_by = null` rather than being deleted. Confirmed unchanged, just flagging it's still a choice worth being aware of now that it lives on the same table as the row it might null out.
- **`OrganizationMemberStatus::Suspended` is unused today** — no code path writes it. The plan preserves it structurally (still checked in middleware) without building anything else around it, per the brief's instruction not to build for hypothetical needs.

### Recommended implementation order

Revised from the first pass: because `OrganizationContext::id()` now throws unconditionally when unset (there's no "soft"/no-op mode possible with the fail-closed-by-construction API — unlike the earlier design where `CurrentOrganizationScope` could check `has()` and silently pass through), **`CurrentOrganizationScope` cannot be switched over until everything it depends on is already in place and verified.** The old plan's step 6 ("land as a behavioral no-op, flip fail-closed later") is no longer possible, so the switch-over and the fail-closed behavior are now the same step, moved to *after* the middleware-ordering fix and the job/pruning fixes rather than before them.

1. `OrganizationContext` class (`scoped()` registration, not `singleton()`) + provider registration — no behavior change yet, nothing reads it.
2. Migration rewrite (reorder `organizations` before `users`; add `organization_id` (`restrictOnDelete()`), `role`, `status`, `invited_by`, `joined_at`, `invitation_token`, `invitation_expires_at` to `users`; delete `organization_user` migration) + model changes (`User`, `Organization`, delete `OrganizationMember`).
3. Update `UserFactory` (`withOrganization`/`forOrganization` bodies only).
4. Update the six `OrganizationMembers` Actions + `CreateNewUser` + `OrganizationMemberPolicy` + `OrganizationMemberResource`.
5. Simplify `EnsureOrganizationContext`; wire it to call `OrganizationContext::set()`. In the **same step**, add the `bootstrap/app.php` `prependToPriorityList()` fix — the middleware is not safe to rely on for tenant-scoped binding without it, so the two ship together, not separately. Verify with the `clients.show` feature test before proceeding.
6. Fix `StoreDocumentJob` (ID-based constructor) and `prunable()` queries (`withoutGlobalScope`) — these must land *before* step 7, since step 7 makes every unscoped query start throwing immediately.
7. Switch `CurrentOrganizationScope` to read `OrganizationContext::id()`. This is inherently fail-closed the moment it lands — there is no intermediate state — which is exactly why steps 5 and 6 had to come first.
8. Full test suite pass; add the new tests listed above.
9. `migrate:fresh --seed` locally and manually verify signup, invite, accept, remove-member, and document upload end-to-end.

### Review round 2: verified conclusions

Each point below was checked against this app's actual codebase and Laravel 13 source/docs, not accepted on the brief's word alone.

1. **Organization FK delete behavior: `restrictOnDelete()`, not `cascadeOnDelete()`.** Deleting an `Organization` should not be able to silently hard-delete every `User` in it — that bypasses the same "reassign responsibilities before hard-delete" discipline that `RemoveOrganizationMemberAction` already enforces per-member, at organization scale, with no chance to intervene. `restrictOnDelete()` makes "delete an org that still has users" a DB error instead; a future explicit deletion workflow can detach/reassign users first. Confirmed `restrictOnDelete()` exists on this Laravel version's `ForeignKeyDefinition`.
2. **Final `OrganizationContext` API: `set(int): void` and `id(): int`, no `has()`.** `id()` throws `LogicException` when unset instead of returning `null`; the missing-context failure now lives on `OrganizationContext` itself rather than being `CurrentOrganizationScope`'s responsibility to check. `has()` is removed — there's no legitimate reason for a caller to branch on "is there a context" rather than either having one or explicitly opting out via `withoutGlobalScope()`. No dedicated exception class; a plain `LogicException` is enough since nothing needs to catch it specifically.
3. **`scoped()`, not `singleton()` — verified against Laravel 13's actual container and queue-worker source, not just the docs.** `$this->app->scoped(...)` is `singleton()` plus registration in `$scopedInstances`, and Laravel automatically calls `forgetScopedInstances()` at the start of every queue-processed job (`Illuminate\Queue\QueueServiceProvider::registerWorker()`) and at the start of every Octane-processed request. This eliminates the entire "singleton-reset footgun" the first pass had to document as a warning — `scoped()` already does exactly what that warning was asking for, for both queue workers (relevant now) and Octane (not installed in this app today, but handled automatically if it ever is).
4. **Final invitation column names: `invitation_token`, `invitation_expires_at`.** `token`/`expires_at` were fine on a model whose entire purpose was invitation state; on `User` they're ambiguous next to `remember_token` and email verification. Confirmed no naming collision with existing `users` columns or Fortify's separate `password_reset_tokens` table.
5. **Verified middleware / route-model-binding execution order — and the original assumption was wrong.** `EnsureOrganizationContext` does *not* run before implicit route-model binding today; `SubstituteBindings` is in Laravel's default `$middlewarePriority` list (after `auth`), while `verified` and the custom `organization` alias are not in that list at all, so they stay after `SubstituteBindings` in the sorted pipeline regardless of route-group membership. Verified by source trace through `Illuminate\Routing\SortedMiddleware` and empirically by log-instrumenting `EnsureOrganizationContext` and `CurrentOrganizationScope` and running a real HTTP request through `tests/Feature/Http/Clients/ShowTest.php` — binding fired first, confirming the bug would have been total (every tenant-scoped page throwing on first load). Fixed with `$middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsureOrganizationContext::class)` in `bootstrap/app.php`, Laravel's documented mechanism for exactly this problem class. Re-verified with the same instrumentation (order corrected) and a full suite run (715 passed, 0 failures) with the fix in place.
6. **Resulting changes to implementation order.** Because the new `OrganizationContext::id()` has no non-throwing degraded mode, `CurrentOrganizationScope` can no longer be switched over "as a no-op first, fail-closed later" the way the first pass planned — the switch-over *is* the fail-closed change now, unconditionally. The revised order moves the middleware fix (step 5) and the job/pruning fixes (step 6) *before* the `CurrentOrganizationScope` switch (step 7), so every code path fail-closed depends on is already correct before fail-closed behavior can ever be exercised by a real request.

Everything else from the brief — the queue-job convention (§6), the pruning `withoutGlobalScope()` correction (§7), and the staged rollout principle of verifying each behavioral change independently (§8) — was already correctly reflected in the first pass and needed no changes beyond the reordering in point 6 above.

### Implementation-time watch-items (not design changes — just what to check as the work lands)

- **`$organization->users` silently including non-`Active` members.** Once `Organization::users()` is a plain `HasMany`, any caller that means "active members" but writes `$organization->users` will also pick up `Invited` and `Suspended` rows — the pivot made membership status more visible at call sites than a bare relation will. Checked the current codebase: the only caller today is `OrganizationMembersController::index()` (`$user->currentOrganization->users()->orderBy(...)->get()`), and it *correctly* wants all three statuses — that's the members-management page, which must show pending invitations. No live bug to fix. But audit every caller as the refactor lands, not just this one; don't add `User::active()`/`User::invited()` scopes preemptively — only introduce them if a second caller shows up that actually needs the narrower set, per the existing "don't build for hypothetical needs" convention.
- **`OrganizationMemberPolicy` / `OrganizationMemberResource` naming.** These will outlive the `OrganizationMember` model. That's fine as long as "Organization Member" still reads as the right *domain* concept (a `User` viewed through organization-membership) rather than an artifact of the old persistence model — inspect at the point of touching each file rather than mechanically renaming to `UserPolicy`/`UserResource` up front.

Not yet implemented. Stopping here for approval before writing any code.

---
