# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Authentication / Invitation / 2FA — Approved Target Architecture

> This approved target architecture is the source of truth for planning the Authentication / Invitation / 2FA milestone. Final implementation details must still be verified against the codebase when individual issues are implemented.

This section consolidates a current-state investigation (reconstructed from `main` via the `architecture-laboratory` methodology) and the resulting target-architecture proposal, now approved with the locked decisions listed below. It is the input to the `my-feature-planning` pass that follows — not an implementation checklist itself.

### 1. Current-state investigation

#### 1.1 Current account lifecycle

Two ways a `User` row is created today, both writing directly into the `users` table (there is no separate `Membership`/pivot model — membership fields `organization_id`, `role`, `status` live directly on `User`):

- **Public self-registration** — `GET/POST /register` (Fortify default, `Features::registration()` enabled in `config/fortify.php:151`) → `App\Actions\Fortify\CreateNewUser::create()` (`app/Actions/Fortify/CreateNewUser.php:29-56`). In one DB transaction: creates an `Organization`, then a `User` with `role=Owner`, `status=Active`, `joined_at=now()`. Unrestricted — no gate, no invite requirement. View wired in `FortifyServiceProvider::configureViews()` (`app/Providers/FortifyServiceProvider.php:68-70`) → `resources/js/pages/auth/Register.vue`.
- **Invitation** — an existing privileged member (`OrganizationRole::isPrivileged()`, i.e. Owner/Admin) invites via `InviteOrganizationMemberAction::handle()` (`app/Actions/OrganizationMembers/InviteOrganizationMemberAction.php:23-49`), which creates a `User` row with `status=Invited`, `password=null`, `invited_by=<inviter id>`, a SHA-256-hashed `invitation_token`, and `invitation_expires_at = now()->addDays(7)`. `OrganizationInvitationNotification` (mail-only, queued) is sent with the raw token.

No third path in production — `database/seeders/UserSeeder.php` creates a demo Owner but is gated to `local` env only (`database/seeders/DatabaseSeeder.php:24`), and there is no Artisan command today.

#### 1.2 Current registration ↔ Organization/first-Owner coupling

Org creation and first-Owner creation are **not separable today** — the only code path that creates an `Organization` row is `CreateNewUser::create()`. This is the seam the target design must cut.

#### 1.3 Current invitation lifecycle

- `FindPendingOrganizationInvitationAction::handle(string $token)` (`app/Actions/OrganizationMembers/FindPendingOrganizationInvitationAction.php`) looks up a `User` via the `pendingInvitation` scope (`app/Models/User.php:83-89` — `status=Invited` AND `invitation_token` present AND `invitation_expires_at > now()`), hashing the incoming token before comparison.
- `AcceptOrganizationInvitationController` (`app/Http/Controllers/OrganizationInvitations/AcceptOrganizationInvitationController.php`): `show()` renders `auth/AcceptInvitation.vue` or `auth/InvitationInvalid.vue`; `store()` calls `AcceptOrganizationInvitationAction::handle()`, then `Auth::login($accepted)` + session regenerate, then redirects to `dashboard`.
- `AcceptOrganizationInvitationAction::handle()` (`app/Actions/OrganizationMembers/AcceptOrganizationInvitationAction.php:19-53`): row-locks (`lockForUpdate`) and re-validates via `pendingInvitation` scope inside a transaction, sets `password`, `status=Active`, `joined_at=now()`, clears `invitation_token`/`invitation_expires_at`; notifies the org owner via `MemberJoinedNotification`.
- Routes are `guest`-only (`routes/organization-invitations.php:8-11`).
- No role-specific branching anywhere in this path — it works identically regardless of the invited `role`, which is why it can transparently serve a provisioned first Owner (see §2.2).
- Revocation: `RevokeOrganizationInvitationAction::handle()` simply deletes the pending `User` row (`app/Actions/OrganizationMembers/RevokeOrganizationInvitationAction.php`).
- Expired/abandoned invitations are cleaned up via `User::prunable()` (`app/Models/User.php:110-117` — `password IS NULL AND status=Invited AND invitation_expires_at < now()`).

#### 1.4 Current middleware/guard chain

- `EnsureOrganizationContext` (`app/Http/Middleware/EnsureOrganizationContext.php`), aliased `organization` (`bootstrap/app.php`, prepended to the priority list before `SubstituteBindings`): requires `$user->status === Active`, else redirects to `home` with a flash error; on success sets `OrganizationContext` (`app/Support/Tenancy/OrganizationContext.php`) for the request, which `CurrentOrganizationScope` (`app/Models/Scopes/CurrentOrganizationScope.php`) uses to scope tenant-owned models. **`User` itself deliberately does not use the `BelongsToCurrentOrganization` trait** other models use (`app/Models/Concerns/BelongsToCurrentOrganization.php`) — pre-membership rows (pending invitations, and formerly the registering user) don't have a resolved org context yet.
- `verified` middleware is applied on `dashboard` (`routes/web.php:9`), `organization-members.*` (`routes/organization-members.php:10`), and part of `settings.*` (`routes/settings.php:17`) — but `User` has `MustVerifyEmail` **commented out** (`app/Models/User.php:7`), so Laravel's `EnsureEmailIsVerified` is currently a no-op pass-through. `Features::emailVerification()` was never added to `config/fortify.php` either — verification was never actually wired past this dead interface reference.
- `RequirePassword` already gates `security.edit` (`routes/settings.php:20-21`) — a working confirm-password boundary that predates any 2FA work.
- Email is globally unique (`users.email` unique index, `database/migrations/0001_01_01_000000_create_users_table.php:20`); enforced further by `InviteOrganizationMemberRequest::after()` (`app/Http/Requests/OrganizationMembers/InviteOrganizationMemberRequest.php:28-48`), which does an *unscoped* email lookup and blocks inviting an email already registered anywhere in the app (not just the current org).

#### 1.5 2FA — current state

Not implemented, but partially scaffolded:
- No `two_factor_*` columns in the `users` migration; `User` does not use `TwoFactorAuthenticatable`.
- `Features::twoFactorAuthentication()` absent from `config/fortify.php`.
- `App\Http\Requests\Settings\TwoFactorAuthenticationRequest` exists, uses Fortify's real `InteractsWithTwoFactorState` trait, and is already injected into `SecurityController::edit()` (`app/Http/Controllers/Settings/SecurityController.php:20`) — but `rules()` is empty and nothing in `resources/js/pages/settings/Security.vue` renders any 2FA UI yet. It's a dead shell, not working code.
- `UserFactory::withTwoFactor()` (`database/factories/UserFactory.php:75-78`) returns an empty state array — a stub.
- `tests/Feature/Http/Auth/AuthenticationTest.php:28-46` contains a **skipped** test (`skipUnlessFortifyHas(Features::twoFactorAuthentication())`) that already documents the expected login-challenge contract (`two-factor.login` route, `login.id` session key, guest until challenge passes).

#### 1.6 Other relevant existing pieces

- `Organization` model (`app/Models/Organization.php`) is currently minimal: `name` + `users()` HasMany + `owner()` helper (`where('role', Owner)->first()`). No uniqueness constraint enforces exactly one Owner row per org at the DB level — it's a convention, not a guarantee.
- `OrganizationRole` enum (`app/Enums/OrganizationRole.php`): `Owner | Admin | Member`; `isPrivileged()` is `Owner || Admin`; `invitableOptions()` deliberately excludes `Owner` (existing members can never invite a new Owner via the UI).
- `OrganizationMemberStatus` enum (`app/Enums/OrganizationMemberStatus.php`): `Active | Invited | Suspended`.
- `OrganizationMemberPolicy` (`app/Policies/OrganizationMemberPolicy.php`): `changeRole` and `remove` both explicitly exclude the target being `role === Owner` — an established convention this design should mirror for 2FA reset authorization.
- `Actions` pattern is the established convention app-wide: thin controllers, `FormRequest` validation, `final [readonly] class ...Action` with a `handle()`/`create()` method, DB transactions for multi-write operations.
- `app/Console/Commands/` does not currently exist — the provisioning command will be the first Artisan command in this app.

---

### 2. Approved target architecture (LOCKED)

The following product decisions are **LOCKED** and must be treated as fixed constraints by the subsequent feature-planning pass:

1. Public signup is removed. No public `/register`. Accounts enter only through controlled provisioning/invitation.
2. New organizations + first Owner are provisioned through an Artisan command. Business logic belongs in an Action, not the command. The first Owner is created as `Invited` and completes the same invitation/account-setup lifecycle as every other member.
3. Existing Owner/Admin member invitations continue using the current invitation lifecycle, unchanged.
4. Preserve the current identity model: one User belongs to one Organization; membership remains directly on `User`; email remains globally unique.
5. Separate email verification is not part of the target lifecycle. Remove the currently decorative/no-op `verified` route middleware/scaffolding where appropriate. Invitation acceptance is the controlled email-ownership/account-setup path.
6. Add Fortify-native 2FA.
7. 2FA is recommended by default but an Organization can require it.
8. The organization-wide 2FA requirement can be enabled/disabled by the Owner only (not Admin).
9. When an Organization requires 2FA, enforcement is immediate per request: an Active authenticated user who has not enrolled is redirected to 2FA enrollment; no grace-period state; no forced logout required; normal application access resumes immediately after enrollment.
10. Keep these concepts independent — do NOT introduce a new `OrganizationMemberStatus` for 2FA:
    - membership state → `User.status`
    - authentication/session state → Laravel auth/session
    - 2FA enrollment state → Fortify/User credential state
    - 2FA requirement → Organization-level policy
11. 2FA reset/recovery: an Admin may never reset an Owner's 2FA; an Owner's 2FA may only be reset by another Owner; privileged management may reset eligible non-Owner users, subject to the existing role/authorization conventions (mirrors `changeRole`/`remove` in `OrganizationMemberPolicy`); Fortify recovery codes remain the normal self-service recovery path.
12. Keep the existing invitation acceptance flow shared between provisioned first Owners and ordinary invited members — no special first-Owner authentication/onboarding path.

#### 2.1 Target account lifecycle

```
[new tenant]                              [existing tenant]
Artisan command                           Owner/Admin, in-app
      │                                          │
ProvisionOrganizationAction                InviteOrganizationMemberAction
(creates Organization +                    (creates User row,
 User row: role=Owner,                      status=Invited, in
 status=Invited, invited_by=null)           existing org — unchanged)
      │                                          │
      └──────────────┬───────────────────────────┘
                      ▼
        User opens /invitations/{token}
                      ▼
        AcceptOrganizationInvitationAction   (unchanged)
     (sets password, status→Active, joined_at)
                      ▼
              Auth::login() → session
                      ▼
     [if org requires 2FA and not enrolled]
        redirected into 2FA enrollment (per-request, no grace period)
                      ▼
        normal authenticated app access
     (2FA challenge on future logins, Fortify-native,
      only if the user has since enrolled)
```

#### 2.2 Target first-Owner provisioning flow

- New Action: `App\Actions\Organizations\ProvisionOrganizationAction` (new `Organizations` actions namespace, sibling to the existing `OrganizationMembers` namespace). In one DB transaction:
  1. Create the `Organization` row.
  2. Create the owner's `User` row directly with `role=Owner`, `status=Invited`, `password=null`, `invited_by=null`, hashed `invitation_token`, `invitation_expires_at` — the same row shape `InviteOrganizationMemberAction` produces, without requiring an `OrganizationContext` (none exists yet — the org was just created in this same call) or a human inviter.
  3. Send the invitation notification.
- New Artisan command (first in `app/Console/Commands/`) does only: accept/validate input, call the Action, print the result. No business logic in the command — it is an operational entry point only, per LOCKED decision #2.
- The provisioned Owner then goes through the *identical* `AcceptOrganizationInvitationAction` / `AcceptOrganizationInvitationController` / `auth/AcceptInvitation.vue` path as any invited Member — zero new acceptance-side code, per LOCKED decision #12. Nothing in the accept path branches on `role`, so this falls out for free (see §1.3).
- `AcceptOrganizationInvitationController` already null-safely handles a missing inviter (`$invitation->inviter?->name`, line 30, exercised by the existing test "renders the accept-invitation page with no inviter once the inviter has been deleted") — direct evidence the domain already tolerates an invitation with no traceable human inviter, which is exactly the first-Owner case (`invited_by=null`).
- **OPEN (not locked):** the exact reuse boundary for creating/sending the first-Owner invitation — i.e. whether `ProvisionOrganizationAction` calls a small extracted/shared piece of `InviteOrganizationMemberAction`'s row-creation logic, or duplicates the handful of lines outright, and whether `OrganizationInvitationNotification` is widened to accept a nullable `$invitedBy` or a distinct notification is introduced. Left for the feature-planning/implementation pass to resolve against actual code shape at build time.
- **OPEN (not locked):** exact Artisan command argument/prompt UX (required flags vs. interactive prompts; whether it also prints the raw invitation URL as a delivery fallback).

#### 2.3 What disappears with public registration

| Piece | Disappears | Moves |
|---|---|---|
| `Features::registration()` in `config/fortify.php` | ✓ | |
| `Fortify::registerView()` closure in `FortifyServiceProvider` | ✓ | |
| `resources/js/pages/auth/Register.vue` | ✓ | |
| `tests/Feature/Http/Auth/RegistrationTest.php` | ✓ (delete, not skip) | |
| `/register` route + its `guest` group membership | ✓ | |
| "Validate name/org/email/password and create a User" | | → `ProvisionOrganizationAction` (input now from an operator via Artisan, not the public) |
| "Create an Organization" | | → `ProvisionOrganizationAction` |
| `App\Actions\Fortify\CreateNewUser` | ✓ (class removed) | its two responsibilities are redistributed, not preserved as one class |

`login`, `logout`, `forgot-password`, `reset-password`, `confirm-password` are all identity-agnostic to how the account was created — none of this changes.

#### 2.4 What existing invitation/authentication machinery remains unchanged

- `AcceptOrganizationInvitationAction`, `AcceptOrganizationInvitationController`, `FindPendingOrganizationInvitationAction`, `RevokeOrganizationInvitationAction`, `InviteOrganizationMemberAction`, `ChangeOrganizationMemberRoleAction`, `RemoveOrganizationMemberAction`
- `EnsureOrganizationContext`, `OrganizationContext`, `CurrentOrganizationScope`
- `OrganizationMemberPolicy`'s existing privileged-role gating pattern (extended for 2FA reset, not replaced)
- All of Fortify's login/logout/password-reset/confirm-password machinery
- `auth/AcceptInvitation.vue`, `auth/InvitationInvalid.vue`
- `User::pendingInvitation()` scope, `invitation_token`/`invitation_expires_at` columns, `Prunable::prunable()` cleanup

#### 2.5 2FA architecture

**Enrollment.** Enable Fortify's `twoFactorAuthentication()` feature directly — no custom auth machinery. Adds `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` to `users` via Fortify's own migration, plus `TwoFactorAuthenticatable` on `User`. Use Fortify's own `Enable/Confirm/DisableTwoFactorAuthentication` and `GenerateNewRecoveryCodes` actions unmodified (`vendor/laravel/fortify/src/Actions/`). Recommended config: `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])` — `confirm` prevents enabling 2FA without proving one valid code first; `confirmPassword` reuses the confirm-password boundary that already exists on `security.edit` via `RequirePassword` (`routes/settings.php:20-21`). The existing dead shell (`TwoFactorAuthenticationRequest`, already injected into `SecurityController::edit`) is the seam to finish wiring, not new territory.

**Challenge (login-time).** Fully Fortify-native, zero custom code. Once `two_factor_confirmed_at` is set, Fortify's own `RedirectIfTwoFactorAuthenticatable` pipeline action intercepts the login POST before `Auth::login()` finalizes and redirects to `/two-factor-challenge`. Only addition: register `Fortify::twoFactorChallengeView()` in `FortifyServiceProvider::configureViews()` alongside the existing view closures (`app/Providers/FortifyServiceProvider.php:53-72`). The skipped test in `AuthenticationTest.php:28-46` already documents this exact contract and should un-skip itself once the feature flag is enabled.

**Enforcement (organization policy — LOCKED decision #9, immediate per-request, no grace period).** Genuinely new — Fortify has no concept of "an org requires this." New middleware shaped identically to `EnsureOrganizationContext`: checks a condition on `$request->user()`, redirects (with flash message) to the enrollment page if unmet, otherwise passes through. Runs *after* the `organization` middleware in the pipeline, so a Suspended/Invited user is already redirected away before this gate is ever reached — no double-handling required. Redirect target needs a small explicit allowlist (enrollment routes themselves, `password.confirm`, `logout`) so an unenrolled user isn't stuck in a loop.

**Recommended vs. required (LOCKED decision #7).** Two independent signals:
- *Recommended* — a UX nudge with no gating, applies to every org by default. **OPEN (not locked):** exact UX treatment (passive settings-page copy vs. dismissible banner vs. active notification via the existing `NotifyAction` pipeline, `app/Actions/Notifications/NotifyAction.php`).
- *Required* — the org-level policy below, enforced by the new middleware, toggled by the Owner only (LOCKED decision #8).

**Where the requirement is stored.** Organization-level, not User-level — `Organization` is already the tenant-policy root (`OrganizationContext`, `CurrentOrganizationScope`, `EnsureOrganizationContext` all exist because tenant-wide state belongs there), and it's currently minimal (`name` + two relations) specifically because nothing tenant-wide has needed a home yet. **OPEN (not locked):** exact DB representation — boolean `two_factor_required` vs. nullable `two_factor_required_at` timestamp (the latter would mirror the existing `_at` state-transition-timestamp convention used throughout `users`, e.g. `email_verified_at`, `joined_at`) vs. another codebase-consistent representation. Left for the feature-planning/implementation pass.

**Recovery (LOCKED decision #11).** Fortify's `/two-factor-challenge` already accepts a recovery code as an alternative to a TOTP code — no new code for that path. For lost-device-and-lost-recovery-codes, no self-service path exists (by design, matching Fortify's own model) — admin-mediated reset only:
- New `resetTwoFactor` policy method on `OrganizationMemberPolicy`, mirroring the existing `changeRole`/`remove` guard shape (`role->isPrivileged()`, target not `Owner`).
- Additional LOCKED constraint layered on top of that existing pattern: an Owner's 2FA may **only** be reset by another Owner (Admin is excluded even though Admin is otherwise "privileged" for member management) — this is stricter than `changeRole`/`remove`, which merely exclude the Owner as a *target*; here the *actor* must also be an Owner when the *target* is an Owner.
- New `ResetTwoFactorAuthenticationAction` clearing the three Fortify 2FA columns.
- Should sit behind `RequirePassword` (already available) given its sensitivity, and should notify the affected member via the existing notification pipeline (mirrors `MemberJoinedNotification`'s existing pattern of notifying on membership events).

#### 2.6 Membership / authentication / 2FA state boundaries (LOCKED decision #10)

Four axes, deliberately never collapsed into one enum or one model:

| Axis | Where it lives | What it answers |
|---|---|---|
| Membership state | `User.status` (`OrganizationMemberStatus`) | Does this row currently have standing in its org? |
| Authentication state | Laravel session (`Auth::check()`) | Is there a currently-valid logged-in session? |
| 2FA enrollment state | `User.two_factor_confirmed_at` (Fortify-native) | Has this credential configured a second factor? |
| 2FA requirement (policy) | `Organization`-level (exact column TBD, see §2.5) | Does this tenant mandate a second factor? |

Membership status governs whether `EnsureOrganizationContext` lets a request through at all; only once that's settled do enrollment and requirement get evaluated together, at the middleware layer — never at the schema layer. A Suspended user's 2FA state is irrelevant because middleware ordering (`organization` before the new 2FA-requirement check) already excludes them before the 2FA gate is reached.

#### 2.7 Email-verification cleanup (LOCKED decision #5)

Nothing to remove on the Fortify side — `Features::emailVerification()` was never added to `config/fortify.php`, so there's no active machinery there. The cleanup is purely subtractive:
- Remove `'verified'` from the three route groups that currently carry it: `routes/web.php:9`, `routes/organization-members.php:10`, `routes/settings.php:17`.
- Remove the dead `// use Illuminate\Contracts\Auth\MustVerifyEmail;` comment in `app/Models/User.php:7`.
- **OPEN (not locked):** whether the now-fully-unused `email_verified_at` column is dropped in a follow-up migration or left in place (low risk either way; `UserFactory` currently populates it at line 35).

#### 2.8 Target middleware/authorization boundaries

```
guest          → only login / forgot-password / invitations.show,store (register gone)
  ↓
auth           → session must exist (Fortify already resolved any 2FA
                  challenge before this session was ever established)
  ↓
organization   → EnsureOrganizationContext: membership must be Active,
                  resolves OrganizationContext (unchanged)
  ↓
two-factor-required → NEW: org requires 2FA AND user not enrolled →
                  redirect to enrollment (allowlist: enrollment routes,
                  password.confirm, logout). Immediate, no grace period
                  (LOCKED decision #9).
  ↓
route handler
```
`RequirePassword` continues to sit specifically on `security.edit` (`routes/settings.php:20-21`) and now also naturally covers the 2FA management endpoints living on that same page.

#### 2.9 Reusable existing components/actions

`AcceptOrganizationInvitationAction`, `FindPendingOrganizationInvitationAction`, `RevokeOrganizationInvitationAction`, `InviteOrganizationMemberAction`, `ChangeOrganizationMemberRoleAction`, `RemoveOrganizationMemberAction`, `EnsureOrganizationContext`, `OrganizationContext`, `CurrentOrganizationScope`, `OrganizationMemberPolicy`'s privileged-role gating pattern, all Fortify login/logout/password-reset/confirm-password machinery, `NotifyAction`/notification infrastructure, the Actions-pattern + thin-controller convention itself, `OrganizationInvitationNotification` / existing invitation-mail mechanism, subject to the open reuse decision in §2.2, `auth/AcceptInvitation.vue` / `auth/InvitationInvalid.vue`, the existing `TwoFactorAuthenticationRequest` shell and its wiring into `SecurityController::edit`.

#### 2.10 Genuinely new architectural pieces

- `App\Actions\Organizations\ProvisionOrganizationAction`
- First Artisan command in `app/Console/Commands/`
- Organization-level 2FA-requirement column + migration (representation open, §2.5)
- Fortify's 2FA migration + `TwoFactorAuthenticatable` trait + config flag
- `EnsureTwoFactorRequirementIsMet`-style middleware + alias, shaped like `EnsureOrganizationContext`
- `Fortify::twoFactorChallengeView()` registration
- 2FA enrollment UI on `settings/Security.vue` (extends existing page, doesn't replace)
- `OrganizationMemberPolicy::resetTwoFactor()` + `ResetTwoFactorAuthenticationAction`
- Owner-only control to toggle the org 2FA requirement (LOCKED decision #8)

#### 2.11 Tests/behavior affected

- **Delete:** `tests/Feature/Http/Auth/RegistrationTest.php` (not skip — the feature is conceptually gone).
- **Activates as-is, no rewrite:** the skipped 2FA block in `AuthenticationTest.php:28-46`.
- **Unaffected:** `AcceptInvitationTest.php`, `EnsureOrganizationContextTest.php`, all `Unit/Actions/OrganizationMembers/*` tests.
- **Reused as existing coverage evidence:** `AcceptInvitationTest.php`'s "renders the accept-invitation page with no inviter once the inviter has been deleted" case already exercises the null-inviter rendering the first-Owner path depends on.
- **New tests needed:** `ProvisionOrganizationAction` unit test; the Artisan command's feature test; `EnsureTwoFactorRequirementIsMet`-style middleware test (sibling to `EnsureOrganizationContextTest.php`); 2FA enrollment/challenge feature tests; `resetTwoFactor` policy test (extends `OrganizationMemberPolicyTest.php`), including the Owner-can-only-be-reset-by-Owner case.

#### 2.12 Security/failure/recovery cases

- **Double-run of the provisioning command / duplicate email** — must wrap in the same transactional pattern used everywhere else; a failed `User` insert must roll back the `Organization` insert, not leave an orphaned tenant.
- **Bootstrap invitation email never arrives** — a newly provisioned Organization needs a viable recovery/fallback path if the first Owner's invitation email is not delivered, because there is no existing active member who can resend it through the application (unlike peer-invites). The exact fallback/command UX remains open — see §2.2.
- **Org flips the 2FA requirement mid-session** — enforcement is per-request via middleware (LOCKED decision #9), so the next request from an unenrolled user is redirected; no forced logout.
- **Recovery-code exhaustion + lost device** — no self-service path exists by design; admin-reset action is itself sensitive and should sit behind `RequirePassword` and notify the affected member.
- **Admin resetting the Owner's 2FA** — explicitly forbidden (LOCKED decision #11); `resetTwoFactor` policy must enforce actor-is-Owner-when-target-is-Owner, stricter than the existing `changeRole`/`remove` pattern.
- **Suspended-user 2FA state** — irrelevant, falls out for free from middleware ordering (§2.8).

#### 2.13 Before → after lifecycle comparison

| | Before | After |
|---|---|---|
| New account creation | Anyone, via public `/register` | Only via invitation (peer) or Artisan provisioning (first Owner) |
| New org creation | Implicit side-effect of any public registration | Explicit, operator-run Artisan command only |
| First Owner onboarding | Registers directly with a password, instantly Active | Enters as an `Invited` row like any member, sets password via the invitation link |
| Email verification | `verified` middleware present but a no-op | Middleware removed; invitation-link click is the ownership proof |
| 2FA | Absent entirely — no columns, no Fortify feature, one dead-end request-class stub | Fortify-native enrollment + challenge; org-level requirement policy; per-user enrollment state; admin-mediated recovery with Owner-only Owner-reset |
| Membership/auth/2FA state | Only membership state formally exists | Four distinct, independently-stored axes (§2.6) |
| Peer-invite flow | Fully built, working | Unchanged |

---

### 3. Explicitly open implementation details (NOT locked — resolve during feature-planning/implementation)

- Exact DB representation of the organization 2FA requirement (`two_factor_required` boolean vs. `two_factor_required_at` nullable timestamp vs. another codebase-consistent representation) — §2.5.
- Exact internal reuse boundary for creating/sending the first-Owner invitation (shared extraction from `InviteOrganizationMemberAction` vs. duplicated logic in `ProvisionOrganizationAction`; nullable-`$invitedBy` widening of `OrganizationInvitationNotification` vs. a distinct notification class) — §2.2.
- Exact Artisan command argument/prompt UX — §2.2.
- Exact UX treatment for "2FA recommended" when not mandatory (passive copy vs. banner vs. notification) — §2.5.
- Whether the unused `email_verified_at` column is retained or removed in a later cleanup — §2.7.

---
