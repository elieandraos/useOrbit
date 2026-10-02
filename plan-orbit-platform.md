# Platform Provisioning API & orbitPlatform Desktop App

> **Source of truth** for the subsequent `plan-it` pass on this initiative, once approved.
> Implementation details still need verifying against the codebase when individual issues are
> built.
>
> **Status: DRAFT v4, presented for final review.** Every material decision is recorded as
> **[LOCKED]**, and no open owner questions remain. Approval of this version makes it canonical
> for `plan-it`. This file is deliberately separate from `plan.md`, which holds the Phase 29 plan.

## Summary

You onboard every organization yourself, and today that means running the
`organizations:provision` artisan command on the server. This initiative replaces the command
with a small, authenticated **platform API** in useOrbit (`/api/platform/...`). A separate,
local-only **orbitPlatform** desktop app calls it. orbitPlatform is its own repository, built with
Laravel 13 + Inertia + Vue + NativePHP.

- **useOrbit stays the authority.** It owns data, validation, authorization, domain actions and
  invitation email. The desktop app holds no business rules, no database credentials and no
  offline mode.
- **Platform identity.**
  - A `platform_users` table, starting with one account created by an artisan command.
  - Password plus mandatory TOTP 2FA, enrolled on first login.
  - Sanctum bearer tokens, kept fully apart from tenant authentication. Each token lasts a fixed
    12 hours, is stored encrypted on your machine and survives app restarts.
  - Logout revokes the token on the server. Clearing the local copy alone doesn't invalidate it.
- **Capabilities.**
  - List organizations with Owner status.
  - Create an organization and invite its Owner.
  - Resend an unaccepted invitation: a fresh link, and the old one stops working. This works even
    after expiry, because expired Owner invitations are no longer pruned.
  - Revoke an unaccepted invitation. If it's the last Owner invitation, the organization is
    deleted too, but only when it was never activated and holds no tenant data. Otherwise revoke
    is blocked. Resend and revoke never affect an accepted Owner.
- **Activation becomes a stored fact.** `organizations.activated_at` is set once, when the Owner
  accepts, and never cleared.
- **Invitation links.** The plaintext link is returned only when the useOrbit **server** runs in
  `local`. Production only emails it.
- **Environments.**
  - Local and Production servers, chosen at login.
  - A LOCAL / PRODUCTION indicator is always visible.
  - Switching tries to revoke the token on the server, clears local data and requires a fresh
    login.
  - Production actions need explicit confirmation.
- **Concurrency.** Accept, resend and revoke all lock the organization first, then the Owner
  row(s). This is verified against a disposable **MySQL** database, which is the engine useOrbit
  uses locally and in Production.
- **Retirement.** `organizations:provision` is removed once the API and the desktop workflow are
  verified, without waiting for a Production deployment. `ProvisionOrganizationAction` stays.

**What this means in practice:** each organization has at most one Owner today, and an Owner
invitation exists only until it's accepted.

- The "remove only this invitation" branch can't occur under today's code.
- A revoke therefore either deletes the organization, when the server confirms it is eligible, or
  is blocked.
- Under today's code paths, an Invited Owner is expected to mean the organization is eligible.
  Even so, every revoke checks eligibility under lock and never assumes it.

**Out of scope:** billing, organization suspension or general deletion, tenant impersonation, Owner
2FA setup or recovery, logos, broad platform administration, any repair or "Invite Owner" action,
and any tenant API.

### Legend

| Tag | Meaning |
|---|---|
| **[FACT]** | Verified against the codebase or first-party sources |
| **[LOCKED]** | Approved by you (in the brief, the Q-1 to Q-7 answers, or the R-1 to R-3 answers) |
| **[DERIVED]** | Follows from facts and locked decisions (premise given) |
| **[REC]** | Recommendation carried into planning; changeable without changing a locked outcome |
| **[OPEN]** | Implementation choice; every option keeps the locked behavior |

---

## 1. Current state

### Provisioning

- **[FACT]** `organizations:provision {organization} {owner-name} {owner-email}`
  (`app/Console/Commands/ProvisionOrganization.php`):
  - validates `organization` as required|string|max:255, and the Owner with `profileRules()`
    (`app/Concerns/ProfileValidationRules.php`);
  - calls `ProvisionOrganizationAction`;
  - prints `route('invitations.show', $token)`.
- **[FACT]** `ProvisionOrganizationAction::handle()` makes a 40-character random token. Inside one
  `DB::transaction` it creates the `Organization`, then the Owner `User` with these values:
  - `password` null, `role` Owner, `status` Invited, `invited_by` null;
  - `invitation_token` set to the sha256 of the token, `invitation_expires_at` set to now + 7 days.

  It then queues `OrganizationInvitationNotification` with `->afterCommit()`.
- **[FACT]** `users.email` is globally unique (in both the migration and `Rule::unique`). A
  concurrent duplicate fails on the unique index, and the transaction rolls back its organization
  (`ProvisionOrganizationActionTest::rolls back the organization when the owner insert fails`).
  Organization names are not unique.
- **[FACT]** The `organizations` columns are `id`, `name`, `two_factor_required` and timestamps.
  There is no lifecycle column.
- **[FACT]** `Organization` and `User` have no global tenant scope. `CurrentOrganizationScope`
  comes only from `BelongsToCurrentOrganization`, which neither model uses.
  `OrganizationContext::id()` throws when it is unset, and only `EnsureOrganizationContext` sets
  it.

### The Owner lifecycle

- **[FACT] Only provisioning creates Owners.**
  - `OrganizationRole::invitableOptions()` returns only Admin and Member, and the invite and
    change-role Form Requests validate against it.
  - `OrganizationMemberPolicy::changeRole` and `remove` refuse Owner targets.
  - So each organization has **at most one Owner row**, created once.
- **[FACT] An Owner row never goes back to Invited.** Acceptance moves it Invited → Active and
  sets `joined_at`. Nothing sets status back to Invited.
- **[FACT] An Owner can delete their own account.**
  - `ProfileController::destroy` has no Owner guard; `ProfileDeleteRequest` checks only the
    current password.
  - The delete fails if the Owner authored rows that use `restrictOnDelete` (`created_by` /
    `uploaded_by` on clients, carriers, agents, policies, documents, notes and tags).
- **[FACT] Other members.** Members are invited only by an active privileged member, through
  `InviteOrganizationMemberAction` under `OrganizationContext`.
- **[FACT] Tables holding organization data.** These have a `restrictOnDelete` `organization_id`:
  `users`, `clients`, `carriers`, `agents`, `policies`, `documents`, `notes` and `tags`.
  - `Client`, `Carrier`, `Agent` and `Policy` use `SoftDeletes`.
  - Child rows (carrier branches, policy details and insureds, document_tag) hang off those
    parents.
  - Database `notifications` reference users only, by morph.
- **[FACT] Before this initiative, activation leaves no durable record.** If an Owner accepts and
  later deletes their own account, nothing in the data shows the organization was ever used.
  **[LOCKED]** `activated_at` closes this gap (Section 6).

### Invitations

- **[FACT] Lookup.** `FindPendingOrganizationInvitationAction` matches on the token hash plus
  `pendingInvitation`: Invited, token present, and `invitation_expires_at > now()`. Expired links
  are therefore already unusable.
- **[FACT] Accept.** `AcceptOrganizationInvitationAction`:
  - in a transaction, re-reads the row with
    `whereKey(id)->pendingInvitation()->lockForUpdate()`, **without re-checking the token hash**;
  - **locks only the `users` row**, with no organization lock;
  - updates password, status, `joined_at`, token and expiry;
  - notifies the Owner after commit.
- **[FACT] Tenant revoke.** `RevokeOrganizationInvitationAction` deletes the invited row with no
  locked re-check. The policy allows only privileged members, so it can't reach an Owner
  invitation, whose organization has no active members. This is a pre-existing gap, out of scope.
- **[FACT] Resend.** No resend capability exists.
- **[FACT] Pruning.** `routes/console.php` runs `model:prune` for `User` daily, and
  `User::prunable()` deletes users with a null password who are Invited and expired, whatever
  their role.
- **[FACT] Email.**
  - The notification is `ShouldQueue` and mail-only, on the `database` queue; the local mailer
    is `log`.
  - Laravel's `SendQueuedNotifications` defaults to `deleteWhenMissingModels = false`.

### Authentication, routing, errors and databases

- **[FACT] Auth setup.**
  - One `web` session guard on the `users` provider.
  - Fortify 1.39, bound to `web`, with views, resetPasswords and 2FA (confirm, confirmPassword).
  - `login` limiter set to 5 per minute per email + IP.
  - **No Sanctum and no API routes.**
- **[FACT] Fortify's 2FA pieces are model-agnostic.** `TwoFactorAuthenticatable`,
  `TwoFactorAuthenticationProvider::verify()` and the Enable/Confirm/GenerateNewRecoveryCodes
  actions accept any user object. Fortify's own login and challenge routes use one guard and the
  session (https://laravel.com/docs/12.x/fortify).
- **[FACT] Fortify 2FA concurrency.**
  - `TwoFactorAuthenticationProvider::verify()` blocks reuse of a TOTP code through a cache entry
    keyed on `md5($code)`, using `verifyKeyNewer` with a get-then-put on the cache. That check and
    the write are **not atomic**.
  - `TwoFactorAuthenticatable::replaceRecoveryCode()` decrypts the codes, replaces one and saves
    them back, with **no lock**.
  - So neither is safe against concurrent requests on its own.
  - The default cache store is `database`.
- **[FACT] Login listener.** `UpdateLastLoginTimestamp` handles every `Login` event and assumes the
  user is a `User`.
- **[FACT] Error handling.**
  - `bootstrap/app.php` already has `shouldRenderJsonWhen($request->is('api/*'))`.
  - `AppServiceProvider::configureExceptionHandling()` registers `Inertia::handleExceptionsUsing`.
    It hooks `respondUsing`, so it runs for **all** requests and, with debug off, renders the
    Inertia `ErrorPage` for 403/404/500/503, with no exclusion for `api/*`.
- **[FACT] Environments.**
  - Local `.env` uses `DB_CONNECTION=mysql`.
  - `.env.example` and the `config/database.php` default use `sqlite`.
  - `phpunit.xml` sets `APP_ENV=testing`, `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
  - `tests/Pest.php` applies `RefreshDatabase` to feature tests, which wraps each test in one
    transaction on one connection.
- **[LOCKED]** useOrbit uses MySQL locally and in Production.
- **[FACT]** Laravel's SQLite grammar compiles no row locks, so `lockForUpdate()` does nothing
  there. The existing suite therefore can't show how MySQL locking behaves.

### First-party external sources (checked 2026-10-01)

- **[FACT] Sanctum** (https://laravel.com/docs/12.x/sanctum):
  - `HasApiTokens` works on any model.
  - Expiry can be set per token with `createToken($name, $abilities, $expiresAt)`.
  - Abilities are enforced with the `abilities` / `ability` middleware.
  - `sanctum:prune-expired` deletes expired tokens.
  - `config('sanctum.guard')`, default `['web']`, lists the session guards tried before the bearer
    token.
- **[FACT] NativePHP desktop v2** (current release 2.3.1):
  - Electron runtime. Install with `composer require nativephp/desktop`; commands are
    `native:install`, `native:run` and `native:build mac`.
  - Laravel support:
    - The docs' support policy lists Laravel 11.x and 12.x.
    - The package's `composer.json` requires `illuminate/contracts ^10.0|^11.0|^12.0|^13.0` and
      PHP `^8.3`.
    - Its `run-tests.yml` CI matrix tests Laravel 11, 12 and 13 on PHP 8.3 to 8.5, including
      `macos-latest`.

  Sources: https://nativephp.com/docs/desktop/2/getting-started/support-policy and
  github.com/NativePHP/desktop.
- **[FACT] Laravel support policy** (https://laravel.com/docs/13.x/releases):
  - Laravel 12 bug fixes ended 2026-08-13; security fixes run until 2027-02-24.
  - Laravel 13 needs PHP 8.3 or later, with security fixes until 2028-03-17.
- **[FACT] NativePHP storage** (https://nativephp.com/docs/desktop/2/the-basics/system,
  https://nativephp.com/docs/desktop/2/digging-deeper/security):
  - `System::encrypt()`, `decrypt()` and `canEncrypt()` use device-bound keys.
  - `Settings` and the app's SQLite file are stored unencrypted in appdata.
  - The docs advise encrypting stored API credentials, HTTPS, and tokens that expire in under
    48 hours.
- **[FACT] NativePHP builds** (https://nativephp.com/docs/desktop/2/getting-started/env-files):
  - The whole app is bundled, **including `.env`**; `cleanup_env_keys` strips keys at build time.
  - An unnotarized macOS build runs only on the machine that built it.
- **[FACT] Laravel HTTP client.** `PendingRequest` provides `withoutRedirecting()` and
  `maxRedirects()` (`vendor/laravel/framework/src/Illuminate/Http/Client/PendingRequest.php`).
- **[FACT] NativePHP UI:**
  - the window title, `Menu` and `MenuBar` label can be updated at runtime;
  - `Alert::new()->type('warning')->buttons([...])->show()` returns the index of the button
    clicked.

---

## 2. Cross-cutting decisions

- **[LOCKED]** You are the only operator. There is no public signup and no public platform
  registration. The scale is about 50 organizations at most.
- **[LOCKED]** useOrbit stays authoritative for data, validation, authorization, domain actions and
  invitation delivery. orbitPlatform reaches it only through authenticated APIs, never through the
  database. It has no offline provisioning and duplicates no business rules.
- **[LOCKED]** Platform APIs live under `/api/platform/...` with their own route definitions.
  `/api/...` stays free for future tenant APIs, and none are built now.
- **[LOCKED]** Owners set up their own 2FA in useOrbit. orbitPlatform has no Owner 2FA features,
  and `organizations:reset-owner-two-factor` stays.
- **[LOCKED]** Existing domain logic is reused, and no provisioning logic moves into the desktop
  app. No platform roles and no permission-management UI.

---

## 3. Platform identity

- **[LOCKED]** A `platform_users` table, starting with one account. The account is created by an
  artisan command. No real credentials go in seed files or Git.
- **[REC]** A `PlatformUser` model with `HasApiTokens` and `TwoFactorAuthenticatable`.
  - Columns: `name`, `email` (unique), hashed `password`, `two_factor_secret`,
    `two_factor_recovery_codes`, `two_factor_confirmed_at`, and timestamps.
  - No organization, role or status.
- **[REC]** One server-side artisan command that prompts for the password with hidden input,
  never as an argument. It covers:
  - create;
  - reset password;
  - reset two-factor;
  - revoke all tokens.
- **[LOCKED]** A password reset and a 2FA reset both invalidate **all** of that platform user's
  tokens, full and intermediate. **[DERIVED]** The credential change and the token deletion happen
  in one transaction, under the platform user row lock (Section 4, "Atomic completion"). A
  challenge or enrollment already in progress then can't finish against the old credentials.
- **[DERIVED]** Tenant `User` never gets `HasApiTokens`. *Premise:* platform and tenant
  authentication are kept apart, and no tenant API is built.
- **[DERIVED]** Platform login must not dispatch `Illuminate\Auth\Events\Login` with a
  `PlatformUser`, or `UpdateLastLoginTimestamp` must ignore events that aren't for a `User`.
  *Premise:* the listener fact above.
- **[REC]** No seeded platform users in any environment. Local accounts are also made with the
  artisan command. Factories are for tests only.

## 4. Authentication

- **[LOCKED]**
  - Platform authentication uses Sanctum bearer tokens, kept apart from tenant authentication.
  - Platform 2FA is mandatory and enrolled on first login.
  - Tokens have a **fixed 12-hour lifetime**.
  - Logout revokes the token on the server.
- **[DERIVED] Isolation settings.** *Premise:* the Sanctum `guard` fallback fact plus the locked
  isolation.
  - `sanctum.guard => []`, so a tenant `web` session can never authenticate a platform request.
  - Platform routes accept only `PlatformUser` tokenables, enforced through
    `auth.guards.sanctum.provider` set to a `platform_users` provider, or an explicit
    `instanceof` middleware.
  - **[OPEN]** Which of the two enforces it.
- **[REC] 2FA without Fortify's routes.** Reuse Fortify's model-agnostic 2FA pieces, so TOTP and
  the encryption of secrets and codes match the tenant side. Fortify's own routes stay
  tenant-only.

### Flow

1. **[REC] Password step.** `POST /auth/login` with email, password and device_name returns an
   intermediate token that can do nothing else:
   - a `platform:two-factor-challenge` token valid for 5 minutes when 2FA is confirmed;
   - otherwise a `platform:two-factor-enrollment` token valid for 10 minutes.
2. **[REC] Enrollment.**
   - The client fetches the secret, the `otpauth://` URL and a QR SVG.
   - Confirming a code sets `two_factor_confirmed_at`, returns the recovery codes **once**,
     consumes the intermediate token and issues the full token.
3. **[REC] Challenge.** Accepts `code` or `recovery_code`; a recovery code is used up. Success
   consumes the intermediate token and issues the full token.
4. **Full token.**
   - **[LOCKED]** It expires 12 hours after it is issued, with no refresh and no sliding window.
   - **[REC]** It carries the single ability `platform`, required by every business route.
   - **[REC]** Issuing one revokes every other full token of that platform user. This is one way a
     token whose earlier revocation failed gets invalidated (Section 8).
5. **[LOCKED]** `DELETE /auth/token` revokes the current token.
6. **[REC]** Schedule `sanctum:prune-expired` daily.

### Atomic completion and single consumption

- **[LOCKED]**
  - Completing enrollment or a challenge is atomic.
  - An intermediate token can be consumed **at most once**, and so can each recovery code.
  - Concurrent enrollment requests are handled safely.
  - Password and 2FA resets invalidate full and intermediate tokens.
- **[DERIVED] One lock order for platform authentication.** *Premise:* the Fortify 2FA concurrency
  facts; neither the replay check nor the recovery-code replacement is safe under concurrency on
  its own.
  1. **`platform_users` row:** `SELECT ... FOR UPDATE`.
  2. **The presented intermediate token row** in `personal_access_tokens`.
  3. Then the checks and writes.

  The same order is used by enrollment GET, enrollment confirm, challenge, password reset, 2FA
  reset and "revoke all tokens".
- **[DERIVED] Completing enrollment or a challenge.** In one transaction:
  1. Lock the platform user.
  2. Consume the intermediate token with a conditional delete: `DELETE ... WHERE id = ? AND
     abilities = <expected> AND (expires_at IS NULL OR expires_at > now)`. Continue **only if
     exactly one row was deleted**.
  3. Verify the TOTP code or recovery code against the locked row's current secret and codes.
  4. Write the 2FA changes:
     - enrollment confirm sets `two_factor_confirmed_at`;
     - a recovery code is replaced through `replaceRecoveryCode`, now running under the lock.
  5. Revoke all other full tokens, issue the new full token, write the audit event, and commit.

  If any step fails, everything rolls back and no full token is issued.
  - A second concurrent request with the same intermediate token finds zero rows to delete, and
    gets 401.
  - A recovery code used twice concurrently succeeds only once, because the second request
    re-reads the codes after the first commit.
  - **[REC]** A failed code verification is a normal 422 that keeps the intermediate token, within
    the throttle budget. Only success consumes it.
- **[DERIVED] Concurrent enrollment.**
  - **Enrollment GET**, under the platform user lock:
    - generates a secret only when none is pending: `two_factor_secret` null and
      `two_factor_confirmed_at` null;
    - otherwise returns the pending secret.

    Repeated or parallel GETs therefore return the same secret, and a code from one QR display
    stays valid.
  - **Enrollment confirm**, under the same lock, requires `two_factor_confirmed_at` to still be
    null. Once one request has confirmed, any concurrent confirm finds the account already enrolled
    and fails with 409, issuing nothing. Its token is either already consumed or gets rejected.
  - **Enrollment tokens are rejected** for an account whose 2FA is already confirmed, and challenge
    tokens are rejected for an account with none confirmed. This covers a 2FA reset during a flow.
- **[DERIVED] Resets invalidate every token.** Password reset, 2FA reset and "revoke all tokens"
  delete **all** of the user's `personal_access_tokens` rows (full and intermediate) in the same
  transaction as the credential change, under the platform user lock.
  - So a flow that was in progress can't consume a token after the reset commits.
  - A flow that commits first has its new full token deleted by the reset.
- **[DERIVED] TOTP replay.** The serialized lock closes the cache race in the TOTP replay check for
  this account. The replay cache key is the code only. With a single platform user, sharing that
  cache with the tenant side could only cause a rare false "code already used". **[OPEN]** Whether
  to namespace the platform verification's cache entry.

### Recovery and rate limits

- **[REC] Recovery, in escalating steps:**
  1. a recovery code;
  2. the artisan reset-two-factor, which forces re-enrollment at the next login;
  3. the artisan reset-password.

  No email-based platform password reset.
- **[LOCKED]** 2FA throttling is account-level as well as per token, so getting a new intermediate
  token does not reset the attempt budget.
- **[REC] Rate limits:**
  - login: 5 per minute per email + IP;
  - 2FA, per token: 5 failed challenge or enrollment-confirm attempts per intermediate token;
    reaching the limit deletes that token;
  - **2FA, per account:** 10 failed 2FA attempts per platform user per 15 minutes, keyed by
    platform user id and **not** by token or IP;
    - it counts failures across every intermediate token, so logging in again with the password
      does not restore attempts;
    - while it is exhausted, challenge and enrollment confirm return 429 even with a fresh token,
      and no new intermediate tokens are issued;
  - business API: 60 per minute per token;
  - resend: about 3 per 10 minutes per organization.
- **[DERIVED]** The per-account counter goes up for failed TOTP and recovery codes. It clears only
  when the window expires or the account authenticates successfully. *Premise:* the locked
  requirement that a new intermediate token doesn't reset the budget.

## 5. Authorization and isolation

- **[LOCKED]** Tenant credentials can't reach platform endpoints, and platform credentials grant no
  tenant access. Tenant policies get no blanket bypass.
- **[DERIVED] Tenant routes reject platform tokens.** Tenant routes use `auth` (the `web` session
  guard, `users` provider) plus the `organization` group.
  - *Premise:* `PlatformUser` has no session login and isn't in the `users` provider.
  - *Consequence:* platform tokens never authenticate tenant routes, and tenant policies never see
    a platform user.
- **[DERIVED] Platform routes reject tenant credentials.** Tenant sessions and cookies get 401.
  *Premise:* `sanctum.guard => []`, and tenant users hold no tokens.
- **[REC] Platform authorization is its own policy.** A platform policy, or named gates,
  registered separately from the tenant `User` and `Organization` policies, with:
  - `viewAny`, `create`, `resendOwnerInvitation` and `revokeOwnerInvitation`, each requiring a
    `PlatformUser`;
  - state conflicts returned as **409** with a machine-readable reason, not 403.
- **[DERIVED] Working outside `OrganizationContext`.** *Premise:* the tenancy facts above.
  - Platform routes never use the `organization` group.
  - Platform actions take the `Organization` explicitly and query by `organization_id`.
  - Platform code never calls context-dependent tenant actions. If it did, they would throw
    `LogicException` rather than leak data.
  - Tenant-data checks use explicit `organization_id` predicates.
- **[DERIVED] API errors stay JSON.** *Premise:* the exception-handler fact. The Inertia
  `ErrorPage` handler must return `null` for `api/*` (or for `expectsJson()`).

---

## 6. Platform operations

### Organization activation

- **[LOCKED]**
  - Add `organizations.activated_at` (nullable timestamp).
  - Set it **once**, when the Owner accepts, in the same transaction as the acceptance.
  - Never clear it, including when users are later removed or deleted.
  - Deleting an organization during revocation requires `activated_at` to be null, in addition to
    the other agreed checks.
- **[DERIVED]** Only the Owner's acceptance sets it, and only while it is still null. Admin and
  Member acceptances don't touch it. *Premise:* only the Owner's acceptance activates an
  organization, and Admins and Members can only exist after that.
- **[DERIVED]** No backfill. *Premise:* Phase 29's locked "pre-production: the database can be
  refreshed; no backfill" still holds when this ships. If real organizations exist in Production
  by then, a backfill becomes necessary: `activated_at` = the Owner's `joined_at`, or the earliest
  member `joined_at`, for every organization with any Active user or tenant data. That would need
  a decision before this initiative ships.

### Owner status in the API

**[REC]** One derived status per organization:

| Status | Condition | Resend | Revoke |
|---|---|---|---|
| `pending` | Owner is Invited and the expiry is in the future | yes | yes |
| `expired` | Owner is Invited and the expiry has passed (retained) | yes | yes |
| `active` | Owner is Active | 409 | 409 |
| `no_owner` | No Owner row, e.g. an accepted Owner who deleted their own account | 409 | 409 |

The resource also includes `activated_at`, `invited_at`, `expires_at`, `joined_at` and
`revoke_outcome`. Status means what useOrbit knows, not whether the email was delivered.

### Create organization and invite Owner

- **[LOCKED]** Reuse `ProvisionOrganizationAction`. `activated_at` starts null.
- **[DERIVED]** Move the command's validation into a Form Request: `organization` max:255 plus
  `profileRules()`.
- **[REC] Duplicates and retries.**
  - Global email uniqueness, the unique index and the rollback make a retry after a lost response
    return 422 "email taken" without creating anything.
  - A unique-violation `QueryException` maps to the same 422.
  - The desktop disables submit while a request is in flight, and refreshes the list after a
    network error or that 422.
  - No idempotency keys.
- **[LOCKED]** The response includes `invitation_url` **only when the useOrbit server has
  `app()->isLocal()`**. Otherwise the field is absent and the email is the only delivery. No
  client input can turn it on.
  - **[DERIVED]** Production must never run with `APP_ENV=local`.
  - **[DERIVED]** Tests need to switch the app environment to cover both cases.
- **[DERIVED]** Each create writes one audit event in the same transaction. **[OPEN]** Whether the
  action accepts an audit hook or a platform action wraps it.

### Retain expired Owner invitations

- **[LOCKED]**
  - Expired, unaccepted Owner records are kept, not pruned.
  - Expired links stay unusable, which the existing `pendingInvitation` check already ensures.
  - Platform resend issues a fresh invitation and invalidates the old link.
  - Pruning of other invitations is unchanged.
- **[DERIVED]** `User::prunable()` adds `role != owner`.

### Resend

- **[LOCKED]** Resend sends a fresh link and the old link stops working. It has no effect on an
  accepted Owner.
- **[REC]** In a transaction, using the lock order in "Locking order" below:
  1. Lock the organization, then the Owner row.
  2. Require Invited (pending or expired).
  3. Store a new token hash and a new expiry of now + 7 days.
  4. Write an audit event.
  5. After commit, queue the same `OrganizationInvitationNotification` with inviter null.

  An Active Owner gets 409. **[LOCKED]** In Local the response carries `invitation_url`.
- **[DERIVED] Stale links.** Accept's locked re-read must also match
  `invitation_token = hash(submitted token)`, so a link replaced by a resend can't be accepted.
  *Premise:* today's re-read skips the hash.
- **[DERIVED]** An email queued before a resend still goes out with the old link, which now opens
  the invalid page. That's acceptable, because only the newest link works.

### Revoke

- **[LOCKED] The rules:**
  - If another invited or accepted Owner exists, remove only the selected unaccepted invitation.
  - If it is the last Owner invitation, delete the invitation and the organization together, but
    only when the organization has never been activated (`activated_at` null) and has no tenant
    data.
  - Otherwise block the operation. Never delete a previously used organization or leave it
    without an Owner.
  - Show explicit confirmation whenever revocation also deletes the organization.
  - No "Invite Owner" repair action.
- **[DERIVED] Which outcome applies.** It is computed under lock.
  - **`removes_invitation`**: the target Owner row is Invited and another Owner row (Invited or
    Active) exists in the organization. This **can't happen today**, since there is at most one
    Owner; it is kept as a safeguard.
  - **`deletes_organization`**: all of these hold:
    - the target is the only Owner row, and it is Invited with `joined_at` null;
    - `organizations.activated_at` is null;
    - no other `users` row exists for the organization;
    - there are no tenant rows, trashed included, in clients, carriers, agents, policies,
      documents, notes or tags.

    Under today's code paths an Invited Owner is expected to satisfy these conditions. The outcome
    is still decided per request from the locked checks, and is never assumed.
  - **`blocked`**: any other case → 409 with the reason (`organization_activated` or
    `organization_has_data`). Nothing changes.
- **[REC] The confirmation is enforced on the server too.**
  - The organization resource exposes `revoke_outcome`.
  - `DELETE .../owner-invitation` requires `expected_outcome` in the body.
  - The server recomputes the outcome under lock. If it differs from `expected_outcome`, the server
    returns 409 `outcome_changed` and changes nothing.

  A stale desktop view can then never delete an organization you didn't confirm deleting.
- **[DERIVED] Deletion order.** Delete the Owner row before the organization, because
  `users.organization_id` is `restrictOnDelete`, then write the audit event. If a tenant row
  somehow exists, the `restrictOnDelete` foreign keys make the organization delete fail and roll
  the whole transaction back. That is a second safeguard after the explicit check.
- **[DERIVED] Queued email for a deleted organization.** An invitation email still queued for a
  deleted organization is dropped, not failed: `DeleteWhenMissingModels` on
  `OrganizationInvitationNotification`. *Premise:* the `deleteWhenMissingModels = false` default.

### Locking order (accept, resend, revoke)

- **[FACT] Today's order.** Accept locks only `users`. Once it also writes `activated_at` it would
  touch `users` first and `organizations` second. Revoke must lock `organizations` to delete it.
  That is opposite orders, and so a deadlock risk.
- **[DERIVED] One canonical order for every write that touches an organization's Owner lifecycle.**
  1. **`organizations` row:** `SELECT ... FOR UPDATE` by id.
  2. **The organization's Owner `users` row(s):** `FOR UPDATE`, in ascending id order.
  3. Then the reads, checks and writes.

  | Operation | Step 1 | Step 2 | Then |
  |---|---|---|---|
  | **Accept** | lock the invitation's organization | lock the invitee row (`whereKey` + `pendingInvitation` + token-hash match) | update the user; if Owner and `activated_at` is null, set `activated_at` |
  | **Resend** | lock the organization | lock the Owner row(s) | require Invited; new hash and expiry |
  | **Revoke** | lock the organization | lock the Owner row(s) | compute the outcome; delete the target row, and the organization when eligible |
  | **Provision** | inserts a new organization + Owner | — | no existing rows are locked, so it can't conflict |

  - *Premise:* these are the only writes to an Owner row or to `activated_at`.
  - **[DERIVED]** Accept takes the organization lock for Admin and Member acceptances too, so
    there's one order everywhere. It costs one more indexed lock at this scale.
- **[DERIVED] Tenant inserts don't break the order.** *Premise:* on MySQL InnoDB, inserting a child
  row with a foreign key takes a shared lock on the parent row.
  - So a tenant insert into an organization waits for a revoke that holds that organization's
    exclusive lock.
  - Tenant writes can only exist after an Active member exists, which means after an accept that
    already passed through the organization lock.
- **[DERIVED] Outcomes of each race on MySQL:**
  - **accept vs. resend:**
    - accept first: resend sees Active → 409;
    - resend first: accept fails the hash match → invalid page.
  - **accept vs. revoke:**
    - accept first: `activated_at` is set and the Owner is Active → `blocked` / 409;
    - revoke first: the rows are gone → invalid page.
  - **resend vs. revoke:** both serialize on the organization lock; the second sees the first's
    result (a new hash, or no row or organization → 404 or 409).
  - **Deadlocks:** InnoDB still detects and rolls back an unexpected one. Surface it as a
    retryable 409 or 503, never as a partial change.

### Audit

- **[LOCKED]** Provisioning and invitation actions are audited.
- **[REC]** An append-only `platform_audit_events` table.
  - Columns: `platform_user_id`, `action`, `organization_id` **without a cascading foreign key**,
    `organization_name` and `subject_email` snapshots, `metadata` (json), `ip_address`,
    `user_agent` and `created_at`.
  - It is written in the action's transaction.
  - **[REC] Event count: exactly one event per successful state-changing operation.**

    | Operation | Event | Notes |
    |---|---|---|
    | Create | `organization.provisioned` | |
    | Resend | `owner_invitation.resent` | |
    | Revoke, invitation only | `owner_invitation.revoked` | `metadata.outcome = "removes_invitation"` |
    | Revoke + organization deletion | `owner_invitation.revoked` | `metadata.outcome = "deletes_organization"`, plus the organization snapshot; **no separate deletion event** |
    | Blocked or `outcome_changed` revoke, any 4xx | none | nothing changed |
    | Auth | `auth.login_succeeded`, `auth.login_failed`, `auth.two_factor_enrolled`, `auth.recovery_code_used`, `auth.token_revoked`, `auth.tokens_revoked_by_reset` | one per occurrence; `auth.login_failed` is written outside any rolled-back transaction |
  - **[DERIVED]** The organization reference must survive the organization's deletion.
  - It is not shown in the desktop UI for now.

---

## 7. API contract

All routes are under `/api/platform` and JSON only, with HTTPS required for the Production server.
They are registered from a dedicated route file with the `api` middleware group. **[OPEN]** The
file name and the registration hook (e.g. `withRouting(then: ...)`).

| Method & path | Ability | Request | Success | Errors |
|---|---|---|---|---|
| `POST /auth/login` | — | `email`, `password`, `device_name` | 200 `{status: "two_factor_challenge" \| "two_factor_enrollment", token, expires_at}` | 422, 429 |
| `GET /auth/two-factor/enrollment` | `platform:two-factor-enrollment` | — | 200 `{secret, otpauth_url, qr_svg}` | 401 |
| `POST /auth/two-factor/enrollment` | `platform:two-factor-enrollment` | `code` | 200 `{token, expires_at, recovery_codes[]}` | 422, 429 |
| `POST /auth/two-factor/challenge` | `platform:two-factor-challenge` | `code` \| `recovery_code` | 200 `{token, expires_at}` | 422, 429 |
| `GET /me` | `platform` | — | 200 `{name, email, token_expires_at, server_environment}` | 401 |
| `DELETE /auth/token` | any platform token | — | 204 | 401 |
| `GET /organizations` | `platform` | — | 200 `{data: [Organization]}`, no pagination at ~50 | 401 |
| `POST /organizations` | `platform` | `organization`, `owner_name`, `owner_email` | 201 `Organization` (+ `invitation_url` in Local) | 422, 429 |
| `POST /organizations/{id}/owner-invitation/resend` | `platform` | — | 200 `Organization` (+ `invitation_url` in Local) | 404, 409, 429 |
| `DELETE /organizations/{id}/owner-invitation` | `platform` | `expected_outcome` | 200 `{outcome, organization: Organization \| null}` | 404, 409 (`blocked` reason, `outcome_changed`) |

`Organization` = `{id, name, created_at, activated_at, owner: {name, email, status, invited_at,
expires_at, joined_at} | null, revoke_outcome}`. It is an API Resource without wrapping
(`JsonResource::withoutWrapping()` is a fact).

**[REC]** `server_environment` lets the desktop warn when a server it calls "Production" reports
`local`, or the reverse. It is informational only; the server alone decides whether to expose
`invitation_url`.

---

## 8. orbitPlatform desktop app

- **[LOCKED]** Its own repository, built with Laravel + Inertia + Vue + NativePHP, with your
  Agentic Engineering skills and the Laravel stack companion installed.
- **[LOCKED]** **Laravel 13**, provided an initial NativePHP build-and-launch check passes
  (`native:install`, `native:run`, and `native:build mac` with a successful launch). Laravel 12 is
  the fallback. This approval does **not** authorize creating the project yet.
- **[DERIVED] No local business logic.** No domain models, no tenant data persistence, and no
  copies of validation rules. The server's 422 messages are shown as they are. Local storage holds
  only encrypted tokens and per-environment session metadata.

### Screens

1. **Sign in.** Environment choice (Local / Production), then email and password.
2. **Two-factor.** Either enrollment (QR code and secret, confirm a code, recovery codes shown once
   with an acknowledgement) or a challenge (a code, or a recovery code).
3. **Organizations.**
   - A table of name, Owner name and email, status badge, invited, expires and joined.
   - Resend and Revoke appear for `pending` and `expired` only.
   - Refresh and Sign out.
   - In Local, a returned invitation link is shown with a copy button.
4. **Create organization.** Organization name, Owner name and Owner email, with server validation
   errors shown inline.

### Confirmations

- **[LOCKED]** Explicit confirmation before provisioning in Production.
- **[LOCKED]** Explicit confirmation whenever a revoke also deletes the organization, in any
  environment. It names the organization and states that the deletion is permanent.
- **[REC]** Production resend and revoke also get a native warning `Alert`.

### Environments and token handling

- **[LOCKED]**
  - Local and Production API addresses are configured.
  - You choose the environment at login.
  - Credentials and tokens are isolated per environment and never sent to another server.
  - A LOCAL / PRODUCTION indicator is always visible.
  - The app holds no hosted database credentials.
  - Tokens are encrypted locally and kept across app restarts.
  - Logout revokes the token.
  - Switching clears local credentials and data and requires a fresh login.
- **[REC] Configuration.** Base URLs live in a committed config file. They are not secrets. The
  Production URL must be `https://`, and the client refuses plain HTTP for it. `.env` holds no
  platform secrets, with `cleanup_env_keys` as a backstop.
- **[LOCKED] The API client keeps the server boundary.** Authenticated API calls never follow
  redirects, and the configured server boundary is preserved. **[DERIVED]** How, from the HTTP
  client fact:
  - Every call goes through one client built for the active environment, with
    `withoutRedirecting()` (or `maxRedirects(0)`).
  - Any 3xx response is treated as an error and shown as "unexpected response from server". It is
    never followed, so a token can't be carried to a `Location` on another host, scheme or port.
  - Request URLs are built only by appending a fixed relative API path to the configured base URL.
    The client refuses absolute URLs, and anything else that would change the scheme, host or
    port, including any URL supplied in a server response.
  - Unauthenticated calls (`/auth/login`) follow the same rule, so credentials are never
    redirected either.
- **[REC] Storage.**
  - Encrypt with `System::encrypt()` and store the token under a key for its environment.
  - Store the base URL it was issued for alongside it.
  - Before every request, check that the stored URL equals the active environment's configured
    URL, or refuse to send the token.
  - Decrypt only to build a request, and never log the token.
- **[DERIVED]** If `System::canEncrypt()` is false, the token isn't persisted, and that session
  lasts only while the app runs. *Premise:* tokens must be stored encrypted.
- **[REC] Indicator.** Window title `orbitPlatform — PRODUCTION`, a `MenuBar` label, and a
  full-width banner on every screen: neutral for LOCAL, red for PRODUCTION.

### Local clearing vs. server revocation

- **[DERIVED] What each one does.**
  - **Server revocation** (`DELETE /auth/token`) is the only thing that invalidates a token before
    it expires. It needs the issuing server to be reachable.
  - **Local clearing** deletes this machine's encrypted copy, environment metadata and loaded
    data. It always succeeds, but **it does not invalidate the token**. Any copy of the token that
    exists elsewhere, for example one extracted from memory, a backup or a log, stays usable
    against its server.
- **[LOCKED]/[REC] Logout and switching:**
  1. Call `DELETE /auth/token` against the **same** server that issued the token, with a short
     timeout (about 5 seconds).
  2. Whatever the result, clear locally.
  3. Only then allow login against the newly chosen environment.
- **[DERIVED] When revocation fails** (the server is unreachable, times out or errors), the token
  **stays valid on its server until whichever comes first:**
  - its fixed expiry, at most 12 hours after issue;
  - a successful revocation: the next full login to that environment revokes all other full tokens
    (Section 4), or the server-side artisan "revoke all tokens" command.
- **[REC] The desktop says so plainly:**
  - "Production token could not be revoked. It remains valid on the server until HH:MM, or until
    you next sign in to Production."
  - It never retries the token against the other environment's server.
  - It doesn't keep the token in order to retry later, because switching requires clearing local
    credentials.
- **[REC]** Quitting the app keeps the token. An expired or revoked token found at launch, or a 401
  at any time, triggers the local clear and sends you back to Sign in.

### Switching with requests in flight

- **[LOCKED]** During an environment switch, outstanding requests and responses are cancelled or
  discarded. A stale response can never change the new environment's UI.
- **[DERIVED] Session epoch.** *Premise:* the desktop's Laravel backend and its Inertia window can
  each have requests in flight at the moment of a switch. There are two parts to the mechanism.
  - **The epoch itself.** A local **session epoch** (an increasing counter plus the environment
    key) is stored beside the token. Logout, switching, and the clear triggered by a 401 or
    expiry each bump it **before** anything else happens.
  - **Backend, compare-and-set.** Every backend operation captures the epoch when it starts, then
    re-checks it **atomically at the moment of any local write**. Local writes are: storing a
    token, storing environment metadata, or caching loaded data.
    - The re-check is a conditional update in the local SQLite store.
    - On a mismatch, the result is discarded, nothing is written, and the request returns a
      redirect to Sign in.
    - So a login that completes against Production after you switched to Local can't store its
      token or data.
  - **Frontend.** When you start a switch:
    - the window calls Inertia's `router.cancelAll()`;
    - it then shows a blocking "Switching environment" state until the revoke and clear steps
      finish.

    Every Inertia response carries the epoch as a shared prop. The client ignores, and reloads to
    Sign in, any response whose epoch doesn't match the current one.
- **[DERIVED]** Loaded data (organization lists, form state) is keyed by environment + epoch and
  cleared when the epoch changes, so nothing from the old environment can show under the new
  indicator.
- **[DERIVED]** A stale **server-side** effect can still complete. For example, a Production create
  whose response arrives after the switch has really happened on the server. Only its display is
  discarded. The next Production session shows it in the list. This is acceptable because the
  server stays the authority, and the create was already confirmed before it was sent.
- **[OPEN]** Whether NativePHP's PHP server handles requests concurrently or one at a time. The
  compare-and-set doesn't depend on the answer.

### Desktop-specific skill guidance (canonical skills unchanged)

- **[FACT]** The `laravel-inertia-stack` companion covers controllers, Form Request → Action,
  policies, Resources for Inertia, filters and sorts, enums, migrations, factories and seeders,
  Inertia forms and pages, and Pest. It has no NativePHP, desktop or remote-API-client guidance.
  No NativePHP skill is installed in this workspace.
- **[DERIVED]** These parts of the companion apply to orbitPlatform:
  - Inertia pages and forms (`<Form>`);
  - Form Requests for local input shape;
  - Pest structure;
  - PHP conventions.

  These have little to apply to, because orbitPlatform has no domain models and authorization
  lives on the server: Eloquent scopes, filters and sorts, migrations, enums, factories, policies,
  and Resources (which present remote JSON).
- **[REC]** Record the desktop-specific guidance in orbitPlatform's own `.ai/rules` (via
  `record-rule`) once the repo exists, not in the canonical skills:
  - an API client per environment, with base-URL pinning;
  - token storage through `System::encrypt`;
  - `Http::fake()` in tests;
  - showing server 422 errors as they are;
  - the NativePHP window, menu and alert APIs;
  - `cleanup_env_keys`;
  - manual verification through `native:run`.

  If the Boost installation in that repo offers NativePHP guidelines, use them.
- **[DERIVED]** `ship-it` release steps assume a web deployment. What a desktop "release" means is
  decided when that repo is planned. It doesn't affect useOrbit.

---

## 9. Must remain unchanged

- **[DERIVED]** These stay as they are:
  - tenant login, Fortify routes, tenant 2FA, roles and `OrganizationMemberPolicy`;
  - tenant invite and revoke;
  - the acceptance UX;
  - pruning of Admin and Member invitations.

  The accept transaction gains only the organization lock, the hash re-check and the one-time
  `activated_at` write, none of which users can see.
- **[DERIVED]** Provisioning and resend send the same notification class.
- **[LOCKED]** `ProvisionOrganizationAction` stays, and so does `organizations:reset-owner-two-factor`.
- **[LOCKED]** `activated_at` is never cleared.

## 10. Rollout, acceptance and verification

### Rollout

1. **useOrbit platform API.**
   - The platform identity and the auth API.
   - `activated_at` and the accept changes: the lock order, the hash re-check and the activation
     write.
   - The prune change, resend, revoke and audit.
   - The JSON error fix.
2. **orbitPlatform repo, once creating it is separately authorized:** the Laravel 13
   build-and-launch check first, then the screens against Local useOrbit.
3. **Retirement [LOCKED]:** once the API and the desktop workflow are verified, remove
   `organizations:provision` and its console test
   (`tests/Feature/Console/ProvisionOrganizationTest.php`), with no wait for a Production
   deployment. The action and its tests stay.
   - **[DERIVED]** Your approval of the retirement covers deleting that command's test file.

### Acceptance criteria

- **Isolation:**
  - tenant sessions and users get 401 on all `/api/platform/*`;
  - platform tokens can't use any tenant route;
  - intermediate tokens can't use business routes;
  - expired and revoked tokens are rejected;
  - a new full login revokes previous full tokens.
- **2FA:**
  - no full token before enrollment is confirmed or the challenge is passed;
  - completion is atomic: any failure rolls back token consumption, 2FA changes and full-token
    issue together;
  - an intermediate token yields at most one full token, even with concurrent submissions;
  - each recovery code works once, even with concurrent submissions;
  - concurrent enrollment GETs return the same pending secret, and concurrent confirms enroll once
    and issue one full token;
  - enrollment tokens are rejected once 2FA is confirmed, and challenge tokens when it isn't.
- **Resets:**
  - password reset, 2FA reset and "revoke all tokens" delete every full **and** intermediate token;
  - an in-flight challenge or enrollment can't complete after a reset commits.
- **2FA throttling:**
  - the per-account failure budget persists across new intermediate tokens and re-logins;
  - once it is exhausted, a fresh token still gets 429;
  - the per-token limit deletes that token.
- **Create:** atomic; a duplicate email returns 422 and creates no organization, even when two run
  concurrently; `activated_at` starts null.
- **Activation:**
  - the Owner's acceptance sets `activated_at` in the same transaction;
  - Admin and Member acceptances don't change it;
  - removing or deleting users never clears it.
- **Invitation link:** `invitation_url` appears when the server is `local`, and never otherwise,
  whatever the request contains.
- **Pruning:** expired Owner invitations survive `model:prune`; expired Admin and Member
  invitations are still pruned; an expired link stays invalid until a resend.
- **Resend:** the old link becomes invalid and the new one works; an Active Owner gets 409.
- **Revoke:**
  - `deletes_organization` removes the Owner row and the organization atomically, only with
    `activated_at` null, no other users and no tenant rows (trashed included);
  - any failing condition gives 409 with nothing changed;
  - a mismatched `expected_outcome` returns 409 `outcome_changed`;
  - after revoke, the link shows the invalid page;
  - a queued email for a deleted organization is dropped without failing.
- **Concurrency (MySQL):** accept vs. resend, accept vs. revoke and resend vs. revoke each end in
  exactly one of the outcomes listed under "Locking order". No stale-token acceptance, no deleted
  activated organization, no partial write, and no deadlock under the canonical order.
- **Audit:**
  - exactly one event per successful state-changing operation, as in the Audit table;
  - a revoke that deletes the organization writes **one** `owner_invitation.revoked` event with
    `outcome = deletes_organization`, and no separate deletion event;
  - blocked and `outcome_changed` revokes write none;
  - every event is rolled back with its action, and stays readable after the organization is
    deleted.
- **Errors:** API errors in production mode are JSON.
- **orbitPlatform:**
  - a token is never sent to the other base URL;
  - authenticated and login calls never follow a 3xx: a redirect to another host, or to the same
    host, is reported as an error and the token is never sent to the `Location`;
  - absolute or response-supplied URLs are refused;
  - a response that arrives after a switch, whether a login, list, create, resend or revoke
    result, writes no token or data and doesn't render under the new environment;
  - `router.cancelAll()` runs when a switch starts;
  - logout and switching attempt revocation, then clear, then show the login screen;
  - when revocation fails, the warning shows the expiry time and says the token stays valid;
  - the Production create confirmation and the delete-organization confirmation always appear;
  - the indicator is on every screen;
  - the token persists across a restart and is cleared at expiry or on a 401.

### Verification

- **useOrbit, default suite** (SQLite `:memory:` with `RefreshDatabase`): Pest feature tests at the
  HTTP boundary (`withToken`), and action-level tests for:
  - outcome computation and eligibility checks;
  - the hash re-check;
  - setting `activated_at` once;
  - prune scope, isolation, audit and JSON errors.

  These prove logic, **not locking**.
- **useOrbit, MySQL concurrency verification [LOCKED].**
  - Accept/resend/revoke concurrency is verified against a **disposable MySQL test database**,
    never the development or Production database.
  - **[DERIVED]** It can't use `RefreshDatabase`'s single wrapping transaction. Both sides of a
    race need separate connections or processes working on committed data, with the database
    migrated fresh and discarded afterwards. *Premise:* the `RefreshDatabase` fact; SQLite has no
    row locks.
  - **[OPEN]** The mechanism: an opt-in Pest group run with a MySQL `DB_*` override, or a
    dedicated script/command that forks two workers with a barrier. Also open is whether it runs
    in CI or as a documented manual gate.
  - **[DERIVED] Its scope also covers the platform-auth races**, which use the same harness and
    need the same MySQL locks:
    - two concurrent challenge completions with one intermediate token;
    - two concurrent uses of one recovery code;
    - two concurrent enrollment confirms;
    - a challenge racing a password reset or 2FA reset.

    Each must produce exactly one success, or none after a reset.
- **useOrbit, focused default-suite tests:**
  - **consumption:** a used or expired intermediate token returns 401, and a token of the wrong
    ability is rejected;
  - **rollback:** a forced failure after consumption leaves the token usable and issues no full
    token;
  - **recovery codes:** a used code is rejected afterwards;
  - **enrollment:** repeated GETs return the same secret; confirming when already confirmed
    returns 409;
  - **resets:** a reset deletes every token type;
  - **throttling:** the account budget survives a new login with a time-frozen limiter, and the
    per-token limit deletes the token;
  - **audit:** event counts for each revoke outcome.
- **orbitPlatform:** Pest with `Http::fake()`. It covers:
  - redirect responses, both same-host and cross-host, are treated as errors and never followed,
    with the faked requests showing no call to the `Location`;
  - URL building refuses absolute URLs;
  - epoch compare-and-set: a backend operation whose epoch changed mid-flight writes nothing and
    redirects to Sign in;
  - the epoch on Inertia shared props;
  - the clear on a 401;
  - revocation-failure messaging.

  The frontend `router.cancelAll()` call and the blocking switch state are a manual check through
  `native:run`, along with the full flow against Local useOrbit (`https://useorbit.test`). You do
  those yourself.

## 11. Remaining questions

None of the owner questions are open. One premise is worth confirming during review:

- **No `activated_at` backfill** assumes Production still holds no real organizations when this
  ships, which matches Phase 29's locked pre-production stance. If that changes, a backfill
  decision is needed first (Section 6).

## 12. Overlap with Phase 29 and the Backlog

- **[FACT] Phase 29.**
  - **#402** changes `OrganizationInvitationNotification`; this initiative adds
    `DeleteWhenMissingModels` to it and reuses it for resend.
  - **#400** lets Active Owners edit the organization name and defaults. That only happens after
    activation, so it doesn't affect revoke eligibility, and `activated_at` sits beside the new
    default columns on `organizations`.
  - `plan.md` locks "Provisioning needs no new inputs" and calls `organizations:provision`
    unchanged.
- **[DERIVED]** This initiative is compatible: create takes the same inputs. Phase 29's "unchanged
  command" statement holds only until this initiative retires it. **[REC]** Start after Phase 29
  merges, to avoid conflicting edits to the notification, `User`, and the `organizations`
  migration/model.
- **[FACT]** No overlap with the open Backlog items (#408, #407, #367, #366, #247, #81).
