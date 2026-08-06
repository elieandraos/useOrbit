# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Code Review: Organization Invitation Flow (2026-08-06) — resolved

A 14-point review of the invite/accept flow came in while building it. Some
concerns assumed members can belong to multiple organizations — they can't,
by product design, and that's not changing soon. Reconciled the review
against that constraint; implemented below.

**Product decision, made explicit at the schema level:** a member belongs to
exactly one organization. This was previously true only as a side effect of
`InviteOrganizationMemberRequest` rejecting invites to existing emails —
nothing enforced it in the schema or in the accept flow itself. Added
`unique('user_id')` to `organization_user` so it's a real constraint, not an
accident. Trade-off: supporting multi-org membership later means dropping
this constraint and re-deriving the invite/accept flow's org-scoping logic —
a conscious, visible change, not a silent one.

**Fixed regardless of multi-org (real bugs):**
- `AcceptOrganizationInvitationAction` had no concurrency guard — two
  concurrent `store()` calls (double submit, two tabs) could both pass the
  token check before either committed, causing last-write-wins on the
  password and a duplicate `MemberJoinedNotification`. Fixed by re-selecting
  the `OrganizationMember` row with `->lockForUpdate()` + a `pendingInvitation()`
  re-check inside the transaction, mirroring `StoreDocumentJob::claim()`
  (`app/Jobs/StoreDocumentJob.php:88-101`) — the codebase's existing
  claim-and-bail pattern.
- `FindPendingOrganizationInvitationAction` discarded the matched
  `OrganizationMember` row and returned a bare `User`, forcing both the
  action and controller to re-derive "the organization" via `->first()`
  behind a lying `@var` PHPDoc, plus an extra manual query for the inviter.
  Now returns `?OrganizationMember` with `user`/`organization`/`inviter`
  eager-loaded; added the missing `organization()`/`inviter()` relations to
  `OrganizationMember`.

**Not done — false alarms or accepted tradeoffs:**
- Token hashing, `afterCommit()` notification timing, redirect-loop
  behavior, session/auth handling, and the `Pivot` `$incrementing = false`
  read-only quirk were all confirmed correct as-is.
- Password-overwrite guard on re-inviting an existing user: moot now that
  `unique('user_id')` makes a second invite for an existing member
  impossible at the DB level.
- Composite indexes on `organization_user`: premature at current scale.

**Tests added:** double-accept reentrancy, DB-level unique-membership
constraint, revoked invitation, deleted inviter — alongside updates to the
existing suite for the new `?OrganizationMember` return types. Full suite
passing (692 tests).
