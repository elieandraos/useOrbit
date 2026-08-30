# Corrected audit — useOrbit `tests/` (reconciliation pass)

Read-only throughout: zero `Write`/`Edit`/`NotebookEdit` calls this session. `testing-best-practices` (Boost) is now loaded alongside `my-laravel-stack`, per the companion requirement. `my-laravel-patterns` was never opened.

**Baselines confirmed:**

| | Expected | Actual |
|---|---|---|
| `useOrbit@main` | `68241fda696def6e9ca5723cb981533465c643ec` | ✅ match |
| `agentic-engineering@main` | `b361f50eb53cef40fd4064fca9e2a6c418dc1dc7` | ✅ match |
| `my-laravel-stack` provenance | `b361f50eb53cef40fd4064fca9e2a6c418dc1dc7` | ✅ match (`UPSTREAM_PROVENANCE.md`) |

Working-tree state, untouched by me: `useOrbit` has one pre-existing unstaged diff — `plan.md` — which already contains the verbatim, uncorrected text of my first audit pass (wrong "31 files" count, the `Pest.php` contradiction). I made no edits to it; it will need a manual rewrite once this corrected proposal is reviewed. `agentic-engineering` has one pre-existing untracked `.idea/` directory (IDE noise). Neither relates to `tests/`.

---

## 1. Corrected inventory

| Metric | Your figure | Verified |
|---|---|---|
| Total files under `tests/` | 156 | **156** ✅ |
| `tests/Feature/` | 77 | **77** ✅ |
| `tests/Unit/` | 77 | **77** ✅ |
| Root files | 2 | **2** (`Pest.php`, `TestCase.php`) ✅ |
| `tests/Unit/Actions/` | 33 | **33** ✅ |
| Genuinely isolated Unit tests | 3 | **3** (`Enums/NotificationReasonTest.php`, `Enums/OrganizationRoleTest.php`, `Support/Tenancy/OrganizationContextTest.php`) ✅ |
| Unit files requiring a non-Unit disposition | 74 | **74** (77 − 3) ✅ |

**Exact discrepancy in my first pass:** I under-counted `tests/Unit/Actions/` as 31 — a plain manual-enumeration miss of `CreateCarrierBranchActionTest.php` and `DeleteCarrierBranchActionTest.php`, not a different classification. Every other figure was already correct.

Isolation basis for the 3 survivors, verified by direct read: `tests/TestCase.php` is a thin `Illuminate\Foundation\Testing\TestCase` subclass whose only helper (`skipUnlessFortifyHas`) is used exclusively by 3 `Feature/Http/Auth|Settings` files — never by the 3 candidates. `app/Support/Tenancy/OrganizationContext.php` is a `final class` with zero framework imports. Grepped every caller of the two container/DB-dependent global Pest helpers (`setOrganizationContext`, `createNotificationFor`): every caller is among the 74 migrating files or already in `Feature` — none among the 3 survivors.

---

## 2. Skill-routing evidence

**`my-laravel-stack` files loaded/re-read this pass:** `SKILL.md`, `blueprints/pest-testing.md`, `rules/test-ownership.md`.

**Boost `testing-best-practices` — loaded this pass:** `SKILL.md`, `rules/isolation.md`, `rules/review.md`, `rules/naming.md`, `rules/endpoint-tests.md`, `rules/test-data.md`. Not opened (not material here): `finding-features.md`, `assertions.md`, `security.md`, `performance.md`.

**Which materially informed the audit:**
- `isolation.md` line 52 recommends `LazilyRefreshDatabase` generally; `pest-testing.md` calls that "a measured future candidate... not a requirement to adopt" for this suite — I did not propose adopting it (§5).
- `review.md` ("a duplicate shrinks at the higher layer to the one case that proves the wiring") + `endpoint-tests.md` ("assert both the response and the persisted state"; "never remove the last case") — this is the load-bearing evidence for §8's exact-minimal-assertion analysis. `test-ownership.md` says HTTP shouldn't duplicate; Boost says what floor must remain.
- `naming.md` lines 5–6 ("Name each file `{ClassName}Test.php`... same relative path as the class under test") — the actual textual basis for the `DocumentsPruningTest.php`/`UsersPruningTest.php` finding (§6), not `test-ownership.md`.
- `test-data.md` line 12 (`make()` only when DB isn't needed) corroborated but didn't change the Notifications classification — direct-read evidence (`::factory()->create()`) was already decisive.

**`my-laravel-patterns` confirmation:** never opened this session.

**Execution miss, recorded honestly:** the first pass loaded only `my-laravel-stack`'s own files and never loaded `testing-best-practices`, despite `SKILL.md` stating it is "additive only" and must load "alongside the matching Boost skill(s), never alone." That was a miss in the first pass, not prior use I'm now hiding.

---

## 3. Corrected `Pest.php` disposition

**Current:**
```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');
```

**Required change** — a config/content correction, not a file move, and only safe to apply **after** the 74-file move (§10) completes, since files still sitting in `Unit` mid-migration would otherwise lose `TestCase`/`RefreshDatabase`:

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

Dropping `'Unit'` from the chain entirely (not adding a second `->in('Unit')` without `RefreshDatabase`) is the exact fix: `Illuminate\Foundation\Testing\TestCase` boots the full application in `setUp()` regardless of `RefreshDatabase`, so binding `TestCase::class` to `Unit` at all still violates `pest-testing.md`'s "no Laravel application boot" for genuinely isolated tests. The 3 survivors need nothing from the container, so they fall back cleanly to Pest's bare `PHPUnit\Framework\TestCase`.

**Consequences checked:** `pest()->tia()->always()->locally()` is orthogonal — no change. Both global helpers (`setOrganizationContext()`, `createNotificationFor()`) are called only by files that end up in `Feature` after the move (verified by grep — zero callers among the 3 survivors). No unrelated cleanup proposed (`something()`, the `toBeOne` expectation extension are untouched — no evidence they're affected).

---

## 4. Literal 156-file disposition ledger

Legend: **stay** / **move** / **move+rename** / **merge+delete** / **config**. Finding IDs defined in §4a.

### `tests/` root (2)
| Current | Target | Disposition | Finding |
|---|---|---|---|
| `tests/Pest.php` | `tests/Pest.php` | config | F14 |
| `tests/TestCase.php` | `tests/TestCase.php` | stay | — |

### `tests/Unit/Actions/**` (33) — all **move**, identical subpath under `Feature/Actions/`
| Current | Target |
|---|---|
| `tests/Unit/Actions/Agents/CreateAgentActionTest.php` | `tests/Feature/Actions/Agents/CreateAgentActionTest.php` |
| `tests/Unit/Actions/Agents/DestroyAgentActionTest.php` | `tests/Feature/Actions/Agents/DestroyAgentActionTest.php` |
| `tests/Unit/Actions/Agents/UpdateAgentActionTest.php` | `tests/Feature/Actions/Agents/UpdateAgentActionTest.php` |
| `tests/Unit/Actions/Carriers/CreateCarrierActionTest.php` | `tests/Feature/Actions/Carriers/CreateCarrierActionTest.php` |
| `tests/Unit/Actions/Carriers/CreateCarrierBranchActionTest.php` | `tests/Feature/Actions/Carriers/CreateCarrierBranchActionTest.php` |
| `tests/Unit/Actions/Carriers/DeleteCarrierBranchActionTest.php` | `tests/Feature/Actions/Carriers/DeleteCarrierBranchActionTest.php` |
| `tests/Unit/Actions/Carriers/UpdateCarrierActionTest.php` | `tests/Feature/Actions/Carriers/UpdateCarrierActionTest.php` |
| `tests/Unit/Actions/Carriers/UpdateCarrierBranchActionTest.php` | `tests/Feature/Actions/Carriers/UpdateCarrierBranchActionTest.php` |
| `tests/Unit/Actions/Clients/CreateClientActionTest.php` | `tests/Feature/Actions/Clients/CreateClientActionTest.php` |
| `tests/Unit/Actions/Clients/UpdateClientActionTest.php` | `tests/Feature/Actions/Clients/UpdateClientActionTest.php` |
| `tests/Unit/Actions/Documents/CountDocumentsUploadBatchOutcomeActionTest.php` | `tests/Feature/Actions/Documents/CountDocumentsUploadBatchOutcomeActionTest.php` |
| `tests/Unit/Actions/Documents/DeleteDocumentActionTest.php` | `tests/Feature/Actions/Documents/DeleteDocumentActionTest.php` |
| `tests/Unit/Actions/Documents/FinalizeDocumentsUploadBatchActionTest.php` | `tests/Feature/Actions/Documents/FinalizeDocumentsUploadBatchActionTest.php` |
| `tests/Unit/Actions/Documents/UploadDocumentActionTest.php` | `tests/Feature/Actions/Documents/UploadDocumentActionTest.php` |
| `tests/Unit/Actions/Notes/CreateNoteActionTest.php` | `tests/Feature/Actions/Notes/CreateNoteActionTest.php` |
| `tests/Unit/Actions/Notes/DeleteNoteActionTest.php` | `tests/Feature/Actions/Notes/DeleteNoteActionTest.php` |
| `tests/Unit/Actions/Notes/UpdateNoteActionTest.php` | `tests/Feature/Actions/Notes/UpdateNoteActionTest.php` |
| `tests/Unit/Actions/Notifications/MarkAllNotificationsAsReadActionTest.php` | `tests/Feature/Actions/Notifications/MarkAllNotificationsAsReadActionTest.php` |
| `tests/Unit/Actions/Notifications/MarkNotificationAsReadActionTest.php` | `tests/Feature/Actions/Notifications/MarkNotificationAsReadActionTest.php` |
| `tests/Unit/Actions/Notifications/NotifyActionTest.php` | `tests/Feature/Actions/Notifications/NotifyActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/AcceptOrganizationInvitationActionTest.php` | `tests/Feature/Actions/OrganizationMembers/AcceptOrganizationInvitationActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/ChangeOrganizationMemberRoleActionTest.php` | `tests/Feature/Actions/OrganizationMembers/ChangeOrganizationMemberRoleActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/FindPendingOrganizationInvitationActionTest.php` | `tests/Feature/Actions/OrganizationMembers/FindPendingOrganizationInvitationActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/InviteOrganizationMemberActionTest.php` | `tests/Feature/Actions/OrganizationMembers/InviteOrganizationMemberActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/RemoveOrganizationMemberActionTest.php` | `tests/Feature/Actions/OrganizationMembers/RemoveOrganizationMemberActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/ResetTwoFactorAuthenticationActionTest.php` | `tests/Feature/Actions/OrganizationMembers/ResetTwoFactorAuthenticationActionTest.php` |
| `tests/Unit/Actions/OrganizationMembers/RevokeOrganizationInvitationActionTest.php` | `tests/Feature/Actions/OrganizationMembers/RevokeOrganizationInvitationActionTest.php` |
| `tests/Unit/Actions/Organizations/ProvisionOrganizationActionTest.php` | `tests/Feature/Actions/Organizations/ProvisionOrganizationActionTest.php` |
| `tests/Unit/Actions/Tags/AttachTagActionTest.php` | `tests/Feature/Actions/Tags/AttachTagActionTest.php` |
| `tests/Unit/Actions/Tags/CreateTagActionTest.php` | `tests/Feature/Actions/Tags/CreateTagActionTest.php` |
| `tests/Unit/Actions/Tags/DeleteTagActionTest.php` | `tests/Feature/Actions/Tags/DeleteTagActionTest.php` |
| `tests/Unit/Actions/Tags/DetachTagActionTest.php` | `tests/Feature/Actions/Tags/DetachTagActionTest.php` |
| `tests/Unit/Actions/Tags/UpdateTagActionTest.php` | `tests/Feature/Actions/Tags/UpdateTagActionTest.php` |

*Rationale (all 33): each `handle()`-invoking Action test uses factories/DB/container (`app()`, `setOrganizationContext()`); per `test-ownership.md`, Action tests belong under `Feature/Actions/{Domain}/`.*

### `tests/Unit/Enums/*` (2) — **stay**
| Current | Rationale |
|---|---|
| `tests/Unit/Enums/NotificationReasonTest.php` | Pure backed-enum calls, no framework |
| `tests/Unit/Enums/OrganizationRoleTest.php` | Pure backed-enum calls, no framework |

### `tests/Unit/Exports/*` (3) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Exports/AgentsExportTest.php` | `tests/Feature/Exports/AgentsExportTest.php` |
| `tests/Unit/Exports/CarriersExportTest.php` | `tests/Feature/Exports/CarriersExportTest.php` |
| `tests/Unit/Exports/ClientsExportTest.php` | `tests/Feature/Exports/ClientsExportTest.php` |

*Rationale: each uses `Model::factory()->create()` (Agent/Carrier/CarrierBranch/Client/Country/State).*

### `tests/Unit/Filters/*` (3) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Filters/AgentFilterTest.php` | `tests/Feature/Filters/AgentFilterTest.php` |
| `tests/Unit/Filters/CarrierFilterTest.php` | `tests/Feature/Filters/CarrierFilterTest.php` |
| `tests/Unit/Filters/ClientFilterTest.php` | `tests/Feature/Filters/ClientFilterTest.php` |

### `tests/Unit/Jobs/` (1) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Jobs/StoreDocumentJobTest.php` | `tests/Feature/Jobs/StoreDocumentJobTest.php` |

*Rationale: `Storage::fake()`, `User::factory()`, `Document::factory()`, real `->handle()` against DB.*

### `tests/Unit/Listeners/` (1) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Listeners/UpdateLastLoginTimestampTest.php` | `tests/Feature/Listeners/UpdateLastLoginTimestampTest.php` |

### `tests/Unit/Models/*` (10)
| Current | Target | Disposition | Finding |
|---|---|---|---|
| `tests/Unit/Models/AgentTest.php` | `tests/Feature/Models/AgentTest.php` | move | — |
| `tests/Unit/Models/CarrierTest.php` | `tests/Feature/Models/CarrierTest.php` | move | — |
| `tests/Unit/Models/ClientTest.php` | `tests/Feature/Models/ClientTest.php` | move | — |
| `tests/Unit/Models/CurrentOrganizationScopeTest.php` | `tests/Feature/Models/CurrentOrganizationScopeTest.php` | move | see §6 note |
| `tests/Unit/Models/DocumentsPruningTest.php` | `tests/Feature/Models/DocumentTest.php` | **move+rename** | F1 |
| `tests/Unit/Models/NoteTest.php` | `tests/Feature/Models/NoteTest.php` | move | — |
| `tests/Unit/Models/OrganizationTest.php` | `tests/Feature/Models/OrganizationTest.php` | move | — |
| `tests/Unit/Models/TagTest.php` | `tests/Feature/Models/TagTest.php` | move | — |
| `tests/Unit/Models/UserTest.php` | `tests/Feature/Models/UserTest.php` (absorbs content below) | move | F2 |
| `tests/Unit/Models/UsersPruningTest.php` | *(none — content merges into `UserTest.php`)* | **merge+delete** | F2 |

### `tests/Unit/Notifications/*` (9) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Notifications/DocumentsUploadBatchProcessedNotificationTest.php` | `tests/Feature/Notifications/DocumentsUploadBatchProcessedNotificationTest.php` |
| `tests/Unit/Notifications/MemberJoinedNotificationTest.php` | `tests/Feature/Notifications/MemberJoinedNotificationTest.php` |
| `tests/Unit/Notifications/MemberRemovedNotificationTest.php` | `tests/Feature/Notifications/MemberRemovedNotificationTest.php` |
| `tests/Unit/Notifications/MemberRoleChangedNotificationTest.php` | `tests/Feature/Notifications/MemberRoleChangedNotificationTest.php` |
| `tests/Unit/Notifications/ResourceArchivedNotificationTest.php` | `tests/Feature/Notifications/ResourceArchivedNotificationTest.php` |
| `tests/Unit/Notifications/ResourceMessageNotificationTest.php` | `tests/Feature/Notifications/ResourceMessageNotificationTest.php` |
| `tests/Unit/Notifications/ResourceUnarchivedNotificationTest.php` | `tests/Feature/Notifications/ResourceUnarchivedNotificationTest.php` |
| `tests/Unit/Notifications/YourRoleChangedNotificationTest.php` | `tests/Feature/Notifications/YourRoleChangedNotificationTest.php` |
| `tests/Unit/Notifications/YourTwoFactorAuthenticationWasResetNotificationTest.php` | `tests/Feature/Notifications/YourTwoFactorAuthenticationWasResetNotificationTest.php` |

*Rationale: each builds its actor/subject with `Model::factory()->create()`, not `make()`.*

### `tests/Unit/Policies/*` (9) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Policies/AgentPolicyTest.php` | `tests/Feature/Policies/AgentPolicyTest.php` |
| `tests/Unit/Policies/CarrierBranchPolicyTest.php` | `tests/Feature/Policies/CarrierBranchPolicyTest.php` |
| `tests/Unit/Policies/CarrierPolicyTest.php` | `tests/Feature/Policies/CarrierPolicyTest.php` |
| `tests/Unit/Policies/ClientPolicyTest.php` | `tests/Feature/Policies/ClientPolicyTest.php` |
| `tests/Unit/Policies/DocumentPolicyTest.php` | `tests/Feature/Policies/DocumentPolicyTest.php` |
| `tests/Unit/Policies/NotePolicyTest.php` | `tests/Feature/Policies/NotePolicyTest.php` |
| `tests/Unit/Policies/OrganizationMemberPolicyTest.php` | `tests/Feature/Policies/OrganizationMemberPolicyTest.php` |
| `tests/Unit/Policies/OrganizationPolicyTest.php` | `tests/Feature/Policies/OrganizationPolicyTest.php` |
| `tests/Unit/Policies/TagPolicyTest.php` | `tests/Feature/Policies/TagPolicyTest.php` |

### `tests/Unit/Providers/*` (2) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Providers/AppServiceProviderTest.php` | `tests/Feature/Providers/AppServiceProviderTest.php` |
| `tests/Unit/Providers/TestingServiceProviderTest.php` | `tests/Feature/Providers/TestingServiceProviderTest.php` |

*Rationale: resolve `Password::default()`/`Validator` via container, `app()->instance()`.*

### `tests/Unit/Sorts/*` (3) — **move**
| Current | Target |
|---|---|
| `tests/Unit/Sorts/AgentSortTest.php` | `tests/Feature/Sorts/AgentSortTest.php` |
| `tests/Unit/Sorts/CarrierSortTest.php` | `tests/Feature/Sorts/CarrierSortTest.php` |
| `tests/Unit/Sorts/ClientSortTest.php` | `tests/Feature/Sorts/ClientSortTest.php` |

### `tests/Unit/Support/Tenancy/` (1) — **stay**
| Current | Rationale |
|---|---|
| `tests/Unit/Support/Tenancy/OrganizationContextTest.php` | Plain final class, no framework dependency |

### `tests/Feature/Console/*` (2) — **stay**
`ProvisionOrganizationTest.php`, `ResetOwnerTwoFactorAuthenticationTest.php` — already correct.

### `tests/Feature/` root (2)
| Current | Target | Disposition | Finding |
|---|---|---|---|
| `tests/Feature/ErrorPageTest.php` | unchanged | stay | — |
| `tests/Feature/HandleInertiaRequestsTest.php` | `tests/Feature/Middlewares/HandleInertiaRequestsTest.php` | move | F13 |

### `tests/Feature/Middlewares/*` (2) — **stay**
`EnsureOrganizationContextTest.php`, `RequireTwoFactorAuthenticationTest.php` — already correct.

### `tests/Feature/Http/**` (71) — **all stay** (already correctly domain-organized); content findings noted
| Current path | Finding |
|---|---|
| `tests/Feature/Http/Agents/ArchiveTest.php` | — |
| `tests/Feature/Http/Agents/DestroyTest.php` | — |
| `tests/Feature/Http/Agents/ExcelExportTest.php` | — |
| `tests/Feature/Http/Agents/IndexTest.php` | — |
| `tests/Feature/Http/Agents/PdfExportTest.php` | — |
| `tests/Feature/Http/Agents/ShowTest.php` | — |
| `tests/Feature/Http/Agents/StoreTest.php` | **F3** |
| `tests/Feature/Http/Agents/UnarchiveTest.php` | — |
| `tests/Feature/Http/Agents/UpdateTest.php` | **F12** |
| `tests/Feature/Http/Auth/AuthenticationTest.php` | — |
| `tests/Feature/Http/Auth/PasswordConfirmationTest.php` | — |
| `tests/Feature/Http/Auth/PasswordResetTest.php` | — |
| `tests/Feature/Http/Auth/TwoFactorAuthenticationTest.php` | — |
| `tests/Feature/Http/Auth/TwoFactorChallengeTest.php` | — |
| `tests/Feature/Http/Carriers/ArchiveTest.php` | — |
| `tests/Feature/Http/Carriers/DestroyBranchTest.php` | — |
| `tests/Feature/Http/Carriers/DestroyTest.php` | — |
| `tests/Feature/Http/Carriers/ExcelExportTest.php` | — |
| `tests/Feature/Http/Carriers/IndexTest.php` | — |
| `tests/Feature/Http/Carriers/PdfExportTest.php` | — |
| `tests/Feature/Http/Carriers/ShowTest.php` | — |
| `tests/Feature/Http/Carriers/StoreBranchTest.php` | — |
| `tests/Feature/Http/Carriers/StoreTest.php` | **F4** |
| `tests/Feature/Http/Carriers/UnarchiveTest.php` | — |
| `tests/Feature/Http/Carriers/UpdateBranchTest.php` | — |
| `tests/Feature/Http/Carriers/UpdateTest.php` | **F12** |
| `tests/Feature/Http/Clients/ArchiveTest.php` | — |
| `tests/Feature/Http/Clients/DestroyTest.php` | — |
| `tests/Feature/Http/Clients/DocumentsIndexTest.php` | — |
| `tests/Feature/Http/Clients/DocumentsStoreTest.php` | **F6** |
| `tests/Feature/Http/Clients/ExcelExportTest.php` | — |
| `tests/Feature/Http/Clients/IndexTest.php` | — |
| `tests/Feature/Http/Clients/NotesIndexTest.php` | — |
| `tests/Feature/Http/Clients/NotesStoreTest.php` | **F5** |
| `tests/Feature/Http/Clients/PdfExportTest.php` | — |
| `tests/Feature/Http/Clients/ShowTest.php` | — |
| `tests/Feature/Http/Clients/StoreTest.php` | — |
| `tests/Feature/Http/Clients/UnarchiveTest.php` | — |
| `tests/Feature/Http/Clients/UpdateTest.php` | — |
| `tests/Feature/Http/DashboardTest.php` | — |
| `tests/Feature/Http/Documents/BatchTest.php` | — |
| `tests/Feature/Http/Documents/DestroyTest.php` | **F12** |
| `tests/Feature/Http/Documents/DocumentTagsDestroyTest.php` | **F8** |
| `tests/Feature/Http/Documents/DocumentTagsStoreTest.php` | **F7** |
| `tests/Feature/Http/Documents/DownloadTest.php` | — |
| `tests/Feature/Http/Notes/DestroyTest.php` | — |
| `tests/Feature/Http/Notes/UpdateTest.php` | **F9** |
| `tests/Feature/Http/Notifications/IndexTest.php` | — |
| `tests/Feature/Http/Notifications/NotifiableMembersTest.php` | — |
| `tests/Feature/Http/Notifications/NotifyAgentTest.php` | — |
| `tests/Feature/Http/Notifications/NotifyCarrierTest.php` | — |
| `tests/Feature/Http/Notifications/NotifyClientTest.php` | — |
| `tests/Feature/Http/Notifications/NotifyDocumentTest.php` | — |
| `tests/Feature/Http/Notifications/ReadAllTest.php` | **F12** |
| `tests/Feature/Http/Notifications/ReadTest.php` | **F12** |
| `tests/Feature/Http/Notifications/RecentTest.php` | — |
| `tests/Feature/Http/OrganizationInvitations/AcceptInvitationTest.php` | — |
| `tests/Feature/Http/OrganizationMembers/ChangeRoleTest.php` | **F12** |
| `tests/Feature/Http/OrganizationMembers/DestroyTest.php` | **F12** |
| `tests/Feature/Http/OrganizationMembers/IndexTest.php` | — |
| `tests/Feature/Http/OrganizationMembers/ResetTwoFactorTest.php` | — |
| `tests/Feature/Http/OrganizationMembers/RevokeInvitationTest.php` | — |
| `tests/Feature/Http/OrganizationMembers/StoreTest.php` | — |
| `tests/Feature/Http/Settings/OrganizationTest.php` | — |
| `tests/Feature/Http/Settings/ProfileUpdateTest.php` | — |
| `tests/Feature/Http/Settings/SecurityTest.php` | — |
| `tests/Feature/Http/Tags/TagsDestroyTest.php` | **F11** |
| `tests/Feature/Http/Tags/TagsIndexTest.php` | — |
| `tests/Feature/Http/Tags/TagsStoreTest.php` | **F10** |
| `tests/Feature/Http/Tags/TagsUpdateTest.php` | — |
| `tests/Feature/Http/World/StatesIndexTest.php` | — |

That's 33+2+3+3+1+1+10+9+9+2+3+1+2+2+71 = **156**, every file accounted for exactly once.

### 4a. Finding index
- **F1** — rename `DocumentsPruningTest.php` → `DocumentTest.php` (only Document model test; Boost naming rule).
- **F2** — merge `UsersPruningTest.php`'s 6 cases into `UserTest.php`; delete the file.
- **F3** — `Agents/StoreTest.php` duplicates `CreateAgentActionTest`.
- **F4** — `Carriers/StoreTest.php` duplicates `CreateCarrierActionTest` (partially — see §8).
- **F5** — `Clients/NotesStoreTest.php` duplicates `CreateNoteActionTest` (1 of 6 cases).
- **F6** — `Clients/DocumentsStoreTest.php` duplicates `UploadDocumentActionTest`.
- **F7** — `Documents/DocumentTagsStoreTest.php` duplicates `AttachTagActionTest`.
- **F8** — `Documents/DocumentTagsDestroyTest.php` duplicates `DetachTagActionTest`.
- **F9** — `Notes/UpdateTest.php` duplicates `UpdateNoteActionTest` (1 of 6 cases).
- **F10** — `Tags/TagsStoreTest.php` duplicates `CreateTagActionTest`.
- **F11** — `Tags/TagsDestroyTest.php` duplicates `DeleteTagActionTest` (near-verbatim).
- **F12** — opposite problem: 7 files' HTTP success case asserts **zero** persisted state, below `endpoint-tests.md`'s floor.
- **F13** — `HandleInertiaRequestsTest.php` → `Middlewares/` for directory-naming consistency.
- **F14** — `Pest.php` config correction (§3).

*(One internal discrepancy between my two research forks on F7/F8: one fork initially called these two "clean" without quoting code; the other quoted the literal duplicated `assertDatabaseHas(['tag_id' => ..., 'document_id' => ...])` byte-for-byte matching the paired Action test. I'm reporting the quote-verified version.)*

---

## 5. Exact target manifest (155 files, literal, one path per line)

```
tests/Pest.php
tests/TestCase.php
tests/Feature/Actions/Agents/CreateAgentActionTest.php
tests/Feature/Actions/Agents/DestroyAgentActionTest.php
tests/Feature/Actions/Agents/UpdateAgentActionTest.php
tests/Feature/Actions/Carriers/CreateCarrierActionTest.php
tests/Feature/Actions/Carriers/CreateCarrierBranchActionTest.php
tests/Feature/Actions/Carriers/DeleteCarrierBranchActionTest.php
tests/Feature/Actions/Carriers/UpdateCarrierActionTest.php
tests/Feature/Actions/Carriers/UpdateCarrierBranchActionTest.php
tests/Feature/Actions/Clients/CreateClientActionTest.php
tests/Feature/Actions/Clients/UpdateClientActionTest.php
tests/Feature/Actions/Documents/CountDocumentsUploadBatchOutcomeActionTest.php
tests/Feature/Actions/Documents/DeleteDocumentActionTest.php
tests/Feature/Actions/Documents/FinalizeDocumentsUploadBatchActionTest.php
tests/Feature/Actions/Documents/UploadDocumentActionTest.php
tests/Feature/Actions/Notes/CreateNoteActionTest.php
tests/Feature/Actions/Notes/DeleteNoteActionTest.php
tests/Feature/Actions/Notes/UpdateNoteActionTest.php
tests/Feature/Actions/Notifications/MarkAllNotificationsAsReadActionTest.php
tests/Feature/Actions/Notifications/MarkNotificationAsReadActionTest.php
tests/Feature/Actions/Notifications/NotifyActionTest.php
tests/Feature/Actions/OrganizationMembers/AcceptOrganizationInvitationActionTest.php
tests/Feature/Actions/OrganizationMembers/ChangeOrganizationMemberRoleActionTest.php
tests/Feature/Actions/OrganizationMembers/FindPendingOrganizationInvitationActionTest.php
tests/Feature/Actions/OrganizationMembers/InviteOrganizationMemberActionTest.php
tests/Feature/Actions/OrganizationMembers/RemoveOrganizationMemberActionTest.php
tests/Feature/Actions/OrganizationMembers/ResetTwoFactorAuthenticationActionTest.php
tests/Feature/Actions/OrganizationMembers/RevokeOrganizationInvitationActionTest.php
tests/Feature/Actions/Organizations/ProvisionOrganizationActionTest.php
tests/Feature/Actions/Tags/AttachTagActionTest.php
tests/Feature/Actions/Tags/CreateTagActionTest.php
tests/Feature/Actions/Tags/DeleteTagActionTest.php
tests/Feature/Actions/Tags/DetachTagActionTest.php
tests/Feature/Actions/Tags/UpdateTagActionTest.php
tests/Feature/Console/ProvisionOrganizationTest.php
tests/Feature/Console/ResetOwnerTwoFactorAuthenticationTest.php
tests/Feature/ErrorPageTest.php
tests/Feature/Exports/AgentsExportTest.php
tests/Feature/Exports/CarriersExportTest.php
tests/Feature/Exports/ClientsExportTest.php
tests/Feature/Filters/AgentFilterTest.php
tests/Feature/Filters/CarrierFilterTest.php
tests/Feature/Filters/ClientFilterTest.php
tests/Feature/Http/Agents/ArchiveTest.php
tests/Feature/Http/Agents/DestroyTest.php
tests/Feature/Http/Agents/ExcelExportTest.php
tests/Feature/Http/Agents/IndexTest.php
tests/Feature/Http/Agents/PdfExportTest.php
tests/Feature/Http/Agents/ShowTest.php
tests/Feature/Http/Agents/StoreTest.php
tests/Feature/Http/Agents/UnarchiveTest.php
tests/Feature/Http/Agents/UpdateTest.php
tests/Feature/Http/Auth/AuthenticationTest.php
tests/Feature/Http/Auth/PasswordConfirmationTest.php
tests/Feature/Http/Auth/PasswordResetTest.php
tests/Feature/Http/Auth/TwoFactorAuthenticationTest.php
tests/Feature/Http/Auth/TwoFactorChallengeTest.php
tests/Feature/Http/Carriers/ArchiveTest.php
tests/Feature/Http/Carriers/DestroyBranchTest.php
tests/Feature/Http/Carriers/DestroyTest.php
tests/Feature/Http/Carriers/ExcelExportTest.php
tests/Feature/Http/Carriers/IndexTest.php
tests/Feature/Http/Carriers/PdfExportTest.php
tests/Feature/Http/Carriers/ShowTest.php
tests/Feature/Http/Carriers/StoreBranchTest.php
tests/Feature/Http/Carriers/StoreTest.php
tests/Feature/Http/Carriers/UnarchiveTest.php
tests/Feature/Http/Carriers/UpdateBranchTest.php
tests/Feature/Http/Carriers/UpdateTest.php
tests/Feature/Http/Clients/ArchiveTest.php
tests/Feature/Http/Clients/DestroyTest.php
tests/Feature/Http/Clients/DocumentsIndexTest.php
tests/Feature/Http/Clients/DocumentsStoreTest.php
tests/Feature/Http/Clients/ExcelExportTest.php
tests/Feature/Http/Clients/IndexTest.php
tests/Feature/Http/Clients/NotesIndexTest.php
tests/Feature/Http/Clients/NotesStoreTest.php
tests/Feature/Http/Clients/PdfExportTest.php
tests/Feature/Http/Clients/ShowTest.php
tests/Feature/Http/Clients/StoreTest.php
tests/Feature/Http/Clients/UnarchiveTest.php
tests/Feature/Http/Clients/UpdateTest.php
tests/Feature/Http/DashboardTest.php
tests/Feature/Http/Documents/BatchTest.php
tests/Feature/Http/Documents/DestroyTest.php
tests/Feature/Http/Documents/DocumentTagsDestroyTest.php
tests/Feature/Http/Documents/DocumentTagsStoreTest.php
tests/Feature/Http/Documents/DownloadTest.php
tests/Feature/Http/Notes/DestroyTest.php
tests/Feature/Http/Notes/UpdateTest.php
tests/Feature/Http/Notifications/IndexTest.php
tests/Feature/Http/Notifications/NotifiableMembersTest.php
tests/Feature/Http/Notifications/NotifyAgentTest.php
tests/Feature/Http/Notifications/NotifyCarrierTest.php
tests/Feature/Http/Notifications/NotifyClientTest.php
tests/Feature/Http/Notifications/NotifyDocumentTest.php
tests/Feature/Http/Notifications/ReadAllTest.php
tests/Feature/Http/Notifications/ReadTest.php
tests/Feature/Http/Notifications/RecentTest.php
tests/Feature/Http/OrganizationInvitations/AcceptInvitationTest.php
tests/Feature/Http/OrganizationMembers/ChangeRoleTest.php
tests/Feature/Http/OrganizationMembers/DestroyTest.php
tests/Feature/Http/OrganizationMembers/IndexTest.php
tests/Feature/Http/OrganizationMembers/ResetTwoFactorTest.php
tests/Feature/Http/OrganizationMembers/RevokeInvitationTest.php
tests/Feature/Http/OrganizationMembers/StoreTest.php
tests/Feature/Http/Settings/OrganizationTest.php
tests/Feature/Http/Settings/ProfileUpdateTest.php
tests/Feature/Http/Settings/SecurityTest.php
tests/Feature/Http/Tags/TagsDestroyTest.php
tests/Feature/Http/Tags/TagsIndexTest.php
tests/Feature/Http/Tags/TagsStoreTest.php
tests/Feature/Http/Tags/TagsUpdateTest.php
tests/Feature/Http/World/StatesIndexTest.php
tests/Feature/Jobs/StoreDocumentJobTest.php
tests/Feature/Listeners/UpdateLastLoginTimestampTest.php
tests/Feature/Middlewares/EnsureOrganizationContextTest.php
tests/Feature/Middlewares/HandleInertiaRequestsTest.php
tests/Feature/Middlewares/RequireTwoFactorAuthenticationTest.php
tests/Feature/Models/AgentTest.php
tests/Feature/Models/CarrierTest.php
tests/Feature/Models/ClientTest.php
tests/Feature/Models/CurrentOrganizationScopeTest.php
tests/Feature/Models/DocumentTest.php
tests/Feature/Models/NoteTest.php
tests/Feature/Models/OrganizationTest.php
tests/Feature/Models/TagTest.php
tests/Feature/Models/UserTest.php
tests/Feature/Notifications/DocumentsUploadBatchProcessedNotificationTest.php
tests/Feature/Notifications/MemberJoinedNotificationTest.php
tests/Feature/Notifications/MemberRemovedNotificationTest.php
tests/Feature/Notifications/MemberRoleChangedNotificationTest.php
tests/Feature/Notifications/ResourceArchivedNotificationTest.php
tests/Feature/Notifications/ResourceMessageNotificationTest.php
tests/Feature/Notifications/ResourceUnarchivedNotificationTest.php
tests/Feature/Notifications/YourRoleChangedNotificationTest.php
tests/Feature/Notifications/YourTwoFactorAuthenticationWasResetNotificationTest.php
tests/Feature/Policies/AgentPolicyTest.php
tests/Feature/Policies/CarrierBranchPolicyTest.php
tests/Feature/Policies/CarrierPolicyTest.php
tests/Feature/Policies/ClientPolicyTest.php
tests/Feature/Policies/DocumentPolicyTest.php
tests/Feature/Policies/NotePolicyTest.php
tests/Feature/Policies/OrganizationMemberPolicyTest.php
tests/Feature/Policies/OrganizationPolicyTest.php
tests/Feature/Policies/TagPolicyTest.php
tests/Feature/Providers/AppServiceProviderTest.php
tests/Feature/Providers/TestingServiceProviderTest.php
tests/Feature/Sorts/AgentSortTest.php
tests/Feature/Sorts/CarrierSortTest.php
tests/Feature/Sorts/ClientSortTest.php
tests/Unit/Enums/NotificationReasonTest.php
tests/Unit/Enums/OrganizationRoleTest.php
tests/Unit/Support/Tenancy/OrganizationContextTest.php
```

**Final totals:** root = 2, `Feature` = **150**, `Unit` = **3**, complete suite = **155**. Reduction of exactly **1** file from 156, entirely attributable to the F2 merge (`UsersPruningTest.php` deleted after its content is absorbed into `UserTest.php`); F1 is a rename, not a deletion, so it doesn't change the count.

---

## 6. Model-test consolidation — corrected reasoning

**What `rules/test-ownership.md` explicitly states:** one row, one canonical path — `Model | tests/Feature/Models/{Model}Test.php | ...`. It names *a* path; it contains **no sentence prohibiting** a second file for the same model. My first pass's "violates a one-file-per-model convention" phrasing overstated the skill's own text.

**What actually backs the finding:** Boost's `testing-best-practices/rules/naming.md`, lines 5–6: *"Name each test file `{ClassName}Test.php`. Place each test file at the same relative path as the class under test."* Neither `DocumentsPruningTest.php` nor `UsersPruningTest.php` is named after a class — `DocumentsPruning`/`UsersPruning` don't exist as classes. That's the real, explicit rule they fail.

| Case | Skill-resolved? | Recommendation | Strongest alternative |
|---|---|---|---|
| `DocumentsPruningTest.php` | **Yes** — Boost naming.md is unambiguous | Rename to `DocumentTest.php`. Read directly: 7 cases, 120 lines, entirely `Document::prunable()` behavior, and it's the *only* Document model test — nothing to consolidate, just rename | None credible |
| `UsersPruningTest.php` + `UserTest.php` | **Partially** — naming.md still implies one file, but doesn't forbid a deliberate split | **Merge** the 6 cases (97 lines) into `UserTest.php` (17 cases/174 lines → ~23 cases/~270 lines combined). Read both directly: `UsersPruningTest` tests `User`'s *own* `prunable()` scope — no separate service class involved, unlike `CurrentOrganizationScopeTest.php` (which tests the shared `Scopes\CurrentOrganizationScope` class, using `Client` only as a proxy — genuinely a different subject, correctly kept standalone). Nothing else in the codebase exercises `User::prunable()` separately | Keep it standalone if a future reviewer judges ~270 combined lines too large for discoverability — defensible, but weaker here than for `CurrentOrganizationScopeTest.php` because the subject under test really is `User` itself |

**Whether a later canonical wording clarification is warranted:** yes — `test-ownership.md`'s Model row could note in one sentence that it inherits Boost's `{ClassName}Test.php` naming constraint, so a future auditor doesn't have to reach into the companion skill to justify a rename/merge finding, as I did here.

**Unresolved note on `CurrentOrganizationScopeTest.php`:** the class under test is `app/Models/Scopes/CurrentOrganizationScope.php`. Strict application of Boost's "same relative path" rule would put its test at `tests/Feature/Models/Scopes/CurrentOrganizationScopeTest.php`, not flatly in `Models/`. `test-ownership.md` has no row for global-scope classes at all. I'm leaving the target at the flat `tests/Feature/Models/CurrentOrganizationScopeTest.php` (matching its current flat placement, least churn) but flagging this as a **genuinely unresolved** naming question rather than silently picking one — worth a human call or a canonical clarification.

---

## 7. Resource-by-Resource coverage assessment (all 10 read directly)

Per `test-ownership.md`: warranted only for "non-trivial project-defined transformations and conditional-field behavior." Per `pest-testing.md`'s warning: HTTP tests using `assertHasResource`/`hasResource` (confirmed in use, e.g. `Carriers/UpdateTest.php`, `Agents/UpdateTest.php`) prove integration only and can't catch a self-consistent regression in the Resource's own logic.

| Resource | Verdict | Reasoning |
|---|---|---|
| `AgentResource` | **Warranted now** | `tenure()` (bespoke y/m diff string), `age` (derived from DOB), `full_address` (collect/filter/implode composition), `date_of_birth_formatted`/`joined_at_formatted`, a `relationLoaded()`-gated `country_name`/`state_name` — none proven outside self-referential checks. |
| `CarrierBranchResource` | Not warranted now | Straight passthrough + two ordinary `whenLoaded()` relations — no computed logic. |
| `CarrierResource` | Not warranted now | Passthrough + `whenLoaded('updatedBy')` + nested `CarrierBranchResource::collection()` — no logic of its own. |
| `ClientResource` | **Warranted now** | `full_name` branches on `client_type === Company` (company name vs. concatenated person name), four `->label()` enum calls, plus the same `full_address`/`age` pattern as Agent. |
| `CountryResource` | Not warranted now | `id`/`name` only. |
| `DocumentResource` | **Warranted now** | `download_url` conditional on `status === Completed`, `error_message` conditional on `status === Failed`, `can_delete` embeds a policy check. Proposed path: `tests/Feature/Resources/DocumentResourceTest.php`, owning: `download_url` present only when completed, `error_message` present only when failed, `can_delete` reflecting policy result for an authorized vs. unauthorized user. |
| `NoteResource` | **Uncertain** | Only logic is two thin embedded policy checks (`can_update`, `can_delete`) — real but minimal; defensible as a small Resource test or as adequately covered at HTTP level; neither currently proves either state. |
| `NotificationResource` | Not warranted now | `created_at->diffForHumans()` is Carbon's own formatting, not project logic; rest is passthrough. |
| `OrganizationMemberResource` | **Warranted now** | `is_you` (`$this->id === $request->user()?->id`) plus four embedded policy checks (`can_change_role`, `can_remove`, `can_revoke`, `can_reset_two_factor`) — the richest authorization surface of any Resource here, exercised nowhere directly. Proposed path: `tests/Feature/Resources/OrganizationMemberResourceTest.php`, owning: `is_you` true/false, each `can_*` flag per its paired policy method. |
| `TagResource` | **Uncertain, partially covered** | `usage_count` via `whenCounted('documents')` is real conditional logic, but already has non-self-referential literal-value coverage via raw `response->json('0.usage_count')` assertions in `Documents/DocumentTagsStoreTest.php`/`DocumentTagsDestroyTest.php`. The two embedded policy checks (`can_update`, `can_delete`) are untested anywhere, though. |

**Total: 4 warranted now** (Agent, Client, Document, OrganizationMember), **4 not warranted now** (CarrierBranch, Carrier, Country, Notification), **2 uncertain** (Note, Tag). These are future test *additions* only — **not** part of the move manifest in §5.

---

## 8. HTTP-versus-Action ownership pairing — complete reconciliation

**Revalidated by direct re-read, both originally-reported findings:**

- **`Agents/StoreTest.php` vs `CreateAgentActionTest.php`:** confirmed. `'store creates the agent with the submitted fields'` (asserts `first_name`/`last_name`/`email`/`city`) duplicates a subset of the Action test's `'stores the submitted attributes'`. **What remains:** nothing needs adding — a sibling case, `'store redirects to agents.show with toast on success'`, already asserts `expect(Agent::query()->count())->toBe(1)`, the one persistence-existence check needed. The duplicate case can simply be **removed**.
- **`Carriers/StoreTest.php` vs `CreateCarrierActionTest.php`:** confirmed, narrower than first reported. `'store creates the branch with the submitted branch and contact fields'` duplicates 3 of the Action test's 7 asserted branch fields (`city`, `contact_name`, `contact_email`). **Not fully redundant**, though: `expect($carrier->branches)->toHaveCount(1)` in the same case proves something no sibling test does (a sub-resource branch was created alongside the carrier). Fix: **keep** the branch-count assertion, **drop** the 3 field-value assertions.

**Complete pairing ledger** — every `Feature/Http/**` file checked against its Action counterpart:

| HTTP test | Action test | Verdict |
|---|---|---|
| `Agents/StoreTest.php` | `CreateAgentActionTest.php` | **duplication — F3** |
| `Agents/UpdateTest.php` | `UpdateAgentActionTest.php` | clean split, but zero persisted-state check on success — **F12** |
| `Agents/DestroyTest.php` | `DestroyAgentActionTest.php` | conforming (single `assertSoftDeleted`) |
| `Agents/ArchiveTest.php`, `UnarchiveTest.php`, `ExcelExportTest.php`, `PdfExportTest.php`, `IndexTest.php`, `ShowTest.php` | — | no Action test exists (Action classes exist, untested at that layer) |
| `Carriers/StoreTest.php` | `CreateCarrierActionTest.php` | **duplication — F4** |
| `Carriers/UpdateTest.php` | `UpdateCarrierActionTest.php` | clean split, zero persisted-state check — **F12** |
| `Carriers/StoreBranchTest.php` | `CreateCarrierBranchActionTest.php` | conforming (count-only) |
| `Carriers/UpdateBranchTest.php` | `UpdateCarrierBranchActionTest.php` | conforming (single-field minimal check) |
| `Carriers/DestroyBranchTest.php` | `DeleteCarrierBranchActionTest.php` | conforming |
| `Carriers/ArchiveTest.php`, `UnarchiveTest.php`, `ExcelExportTest.php`, `PdfExportTest.php`, `IndexTest.php`, `ShowTest.php`, `DestroyTest.php` | — | no counterpart |
| `Clients/StoreTest.php` | `CreateClientActionTest.php` | conforming |
| `Clients/UpdateTest.php` | `UpdateClientActionTest.php` | conforming |
| `Clients/NotesStoreTest.php` | `CreateNoteActionTest.php` | **duplication — F5** (1 of 6 cases) |
| `Clients/DocumentsStoreTest.php` | `UploadDocumentActionTest.php` | **duplication — F6** |
| `Clients/DestroyTest.php`, `ArchiveTest.php`, `UnarchiveTest.php`, `ExcelExportTest.php`, `PdfExportTest.php`, `IndexTest.php`, `ShowTest.php`, `NotesIndexTest.php`, `DocumentsIndexTest.php` | — | no counterpart (no Client-destroy Action layer exists) |
| `Notes/UpdateTest.php` | `UpdateNoteActionTest.php` | **duplication — F9** (1 of 6 cases; boundary/line-break cases untouched, conforming) |
| `Notes/DestroyTest.php` | `DeleteNoteActionTest.php` | conforming |
| `Documents/DestroyTest.php` | `DeleteDocumentActionTest.php` | conforming split, zero persisted-state check — **F12** |
| `Documents/DocumentTagsStoreTest.php` | `AttachTagActionTest.php` | **duplication — F7** |
| `Documents/DocumentTagsDestroyTest.php` | `DetachTagActionTest.php` | **duplication — F8** |
| `Documents/BatchTest.php` | `CountDocumentsUploadBatchOutcomeActionTest.php`/`FinalizeDocumentsUploadBatchActionTest.php` | no direct counterpart — proves job-dispatch wiring (`Bus::assertBatched`), a distinct concern |
| `Documents/DownloadTest.php` | — | no counterpart |
| `Tags/TagsStoreTest.php` | `CreateTagActionTest.php` | **duplication — F10** |
| `Tags/TagsUpdateTest.php` | `UpdateTagActionTest.php` | conforming (single-field minimal check) |
| `Tags/TagsDestroyTest.php` | `DeleteTagActionTest.php` | **duplication — F11** (near-verbatim reproduction of the Action test's full matrix) |
| `Tags/TagsIndexTest.php` | — | no counterpart |
| `Notifications/ReadTest.php` | `MarkNotificationAsReadActionTest.php` | conforming split, zero persisted-state check — **F12** |
| `Notifications/ReadAllTest.php` | `MarkAllNotificationsAsReadActionTest.php` | conforming split, zero persisted-state check — **F12** |
| `Notifications/NotifyAgentTest.php`/`NotifyCarrierTest.php`/`NotifyClientTest.php`/`NotifyDocumentTest.php` | `NotifyActionTest.php` | conforming — exemplary complementary split (HTTP proves envelope shape, Action proves recipient-filtering matrix) |
| `Notifications/IndexTest.php`, `NotifiableMembersTest.php`, `RecentTest.php` | — | no counterpart |
| `OrganizationMembers/StoreTest.php` | `InviteOrganizationMemberActionTest.php` | conforming — exemplary existence-vs-exact-value split |
| `OrganizationMembers/ChangeRoleTest.php` | `ChangeOrganizationMemberRoleActionTest.php` | conforming split, zero persisted-state check — **F12** |
| `OrganizationMembers/DestroyTest.php` | `RemoveOrganizationMemberActionTest.php` | conforming split, zero persisted-state check — **F12** |
| `OrganizationMembers/ResetTwoFactorTest.php` | `ResetTwoFactorAuthenticationActionTest.php` | conforming |
| `OrganizationMembers/RevokeInvitationTest.php` | `RevokeOrganizationInvitationActionTest.php` | conforming |
| `OrganizationMembers/IndexTest.php` | — | no counterpart |
| `OrganizationInvitations/AcceptInvitationTest.php` | `AcceptOrganizationInvitationActionTest.php` | conforming — exemplary (zero field overlap) |
| `Auth/*`, `Settings/*`, `World/StatesIndexTest.php`, `DashboardTest.php` | — | no `app/Actions/**` equivalent exists for these domains |

**Expanded duplication findings (F3–F11), quoted:**

3. **F3 — `Agents/StoreTest.php`:** duplicate is `expect($agent->first_name)->toBe('Mira')->and($agent->last_name)->toBe('Olsen')->and($agent->email)->toBe('mira.olsen@useorbit.com')->and($agent->city)->toBe('Beirut')` → **delete the case entirely** (a sibling case already proves `Agent::query()->count()->toBe(1)`).
4. **F4 — `Carriers/StoreTest.php`:** duplicate is `->and($branch->city)->toBe('Beirut')->and($branch->contact_name)->toBe('Lina Karam')->and($branch->contact_email)->toBe('lina.karam@bankers.com.lb')` → **trim to** `expect($carrier->branches)->toHaveCount(1);` alone.
5. **F5 — `Clients/NotesStoreTest.php`:** duplicate is `$this->assertDatabaseHas('notes', ['notable_type' => ..., 'notable_id' => ..., 'organization_id' => ..., 'created_by' => ..., 'body' => 'Called the client about renewal.'])` → keep the preceding `assertJson([...])`, **replace** with `$this->assertDatabaseCount('notes', 1);`.
6. **F6 — `Clients/DocumentsStoreTest.php`:** duplicate is `$this->assertDatabaseHas('documents', ['documentable_type' => ..., 'documentable_id' => ..., 'uploaded_by' => ..., 'status' => DocumentStatus::Pending->value])` → keep the preceding `assertJson([...])`, **replace** with `$this->assertDatabaseCount('documents', 1);`.
7. **F7 — `Documents/DocumentTagsStoreTest.php`:** duplicate is `$this->assertDatabaseHas('document_tag', ['tag_id' => $tag->id, 'document_id' => $document->id])` → **replace** with `$this->assertDatabaseCount('document_tag', 1);` (matches the file's own sibling idiom).
8. **F8 — `Documents/DocumentTagsDestroyTest.php`:** duplicate is `$this->assertDatabaseMissing('document_tag', ['tag_id' => $tag->id, 'document_id' => $document->id])` → **replace** with `$this->assertDatabaseCount('document_tag', 0);`.
9. **F9 — `Notes/UpdateTest.php`:** duplicate is `$this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Updated.', 'pinned' => true])` → keep the preceding `assertJson([...])`, **reduce** to `$this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Updated.']);` (single field).
10. **F10 — `Tags/TagsStoreTest.php`:** duplicate is `$this->assertDatabaseHas('tags', ['organization_id' => ..., 'created_by' => ..., 'name' => 'Medicare'])` → keep the preceding `assertJson(['name' => 'Medicare', 'usage_count' => 0])` (HTTP/Resource-only field), **replace** with `$this->assertDatabaseCount('tags', 1);`.
11. **F11 — `Tags/TagsDestroyTest.php`:** duplicate is the *complete* matrix — `assertModelMissing($tag)`, `assertDatabaseCount('document_tag', 0)`, and iterating `assertModelExists($document)` per tagged document — verbatim what `DeleteTagActionTest` already owns → **delete the case entirely** (a sibling case already proves `assertModelMissing($tag)`).

**F12 — the opposite gap (not duplication, reported separately per your instruction not to over-trim):** these 7 files' HTTP success case asserts **only** redirect/flash/`assertNoContent()`, with **no** persisted-state check — below `endpoint-tests.md`'s floor ("assert both the response and the persisted state"): `Agents/UpdateTest.php`, `Carriers/UpdateTest.php`, `Documents/DestroyTest.php`, `Notifications/ReadAllTest.php`, `Notifications/ReadTest.php`, `OrganizationMembers/ChangeRoleTest.php`, `OrganizationMembers/DestroyTest.php`. This is a distinct, evidence-backed finding — the fix here is *adding* one minimal assertion, not trimming.

---

## 9. Middleware directory — deliberate decision

**Candidates evaluated:** `tests/Feature/Middlewares/` (current) / `tests/Feature/Middleware/` (singular, matches `app/Http/Middleware/`) / `tests/Feature/Http/Middleware/` (full relative-path mirror per Boost `naming.md`).

**Decision: keep `tests/Feature/Middlewares/`**, just complete the consistency move (F13, moving `HandleInertiaRequestsTest.php` in).

- **Not skill-required** — neither `my-laravel-stack`'s ownership table nor its Boundary section covers middleware placement at all; it explicitly disclaims mandating an architecture beyond the rows it lists.
- **Is an established useOrbit convention** — 2 of the 4 middleware classes already have tests filed under this exact plural directory name, predating this audit.
- **Renaming would be pure churn** — these are plain procedural Pest files with no `namespace` declaration to keep in sync with a directory rename; `phpunit.xml` globs by the top-level `Feature`/`Unit` directory only, not by this subpath. Nesting under `Http/Middleware/` would also blend two different organizing schemes, since `Http/` is currently organized by controller *domain* (Agents, Carriers, …) and middleware isn't a domain.
- Per Boost's own `SKILL.md` "Consistency First" section: *"A pattern repeated throughout the project is a convention, and project conventions take precedence over this skill... An existing test that follows a project convention is not defective merely because it conflicts with this skill."* That's a direct instruction not to rename an established local convention to match a generic naming rule.

This is: **an established useOrbit convention**, protected by Boost's own consistency-first rule — not skill-required, not harmless-legacy-needing-correction, not a proposed cleanup.

---

## 10. Exact non-executed move plan

Not run.

```bash
# --- Step 1: directory-level moves (uniform child relationship verified in §5; no target pre-exists) ---
git mv tests/Unit/Actions        tests/Feature/Actions        # 33 files. Unit 77→44, Feature 77→110
git mv tests/Unit/Policies       tests/Feature/Policies       # 9 files.  Unit 44→35, Feature 110→119
git mv tests/Unit/Filters        tests/Feature/Filters        # 3 files.  Unit 35→32, Feature 119→122
git mv tests/Unit/Sorts          tests/Feature/Sorts          # 3 files.  Unit 32→29, Feature 122→125
git mv tests/Unit/Notifications  tests/Feature/Notifications  # 9 files.  Unit 29→20, Feature 125→134
git mv tests/Unit/Exports        tests/Feature/Exports        # 3 files.  Unit 20→17, Feature 134→137
git mv tests/Unit/Jobs           tests/Feature/Jobs           # 1 file.   Unit 17→16, Feature 137→138
git mv tests/Unit/Listeners      tests/Feature/Listeners      # 1 file.   Unit 16→15, Feature 138→139
git mv tests/Unit/Providers      tests/Feature/Providers      # 2 files.  Unit 15→13, Feature 139→141
git mv tests/Unit/Models         tests/Feature/Models         # 10 files. Unit 13→3,  Feature 141→151

# --- Step 2: single-file move into an existing directory ---
git mv tests/Feature/HandleInertiaRequestsTest.php tests/Feature/Middlewares/HandleInertiaRequestsTest.php
# Feature stays 151 (relocated within Feature, no net count change)

# --- Step 3: content operations requiring an edit, not a plain mv ---
git mv tests/Feature/Models/DocumentsPruningTest.php tests/Feature/Models/DocumentTest.php   # F1: rename only, no content edit
# F2: manually copy UsersPruningTest.php's 6 test() cases into tests/Feature/Models/UserTest.php, then:
git rm tests/Feature/Models/UsersPruningTest.php    # Feature 151→150

# --- Step 4: content edits inside 9 non-moving Http files (no git mv) ---
# tests/Feature/Http/Agents/StoreTest.php              — F3: delete the redundant case
# tests/Feature/Http/Carriers/StoreTest.php            — F4: drop 3 field assertions, keep branch-count
# tests/Feature/Http/Clients/NotesStoreTest.php        — F5
# tests/Feature/Http/Clients/DocumentsStoreTest.php    — F6
# tests/Feature/Http/Documents/DocumentTagsStoreTest.php   — F7
# tests/Feature/Http/Documents/DocumentTagsDestroyTest.php — F8
# tests/Feature/Http/Notes/UpdateTest.php              — F9
# tests/Feature/Http/Tags/TagsStoreTest.php            — F10
# tests/Feature/Http/Tags/TagsDestroyTest.php          — F11: delete the redundant case

# --- Step 5 (optional, separate decision): add one minimal persisted-state assertion to the 7 F12 files ---
# tests/Feature/Http/Agents/UpdateTest.php, Carriers/UpdateTest.php, Documents/DestroyTest.php,
# Notifications/ReadAllTest.php, Notifications/ReadTest.php,
# OrganizationMembers/ChangeRoleTest.php, OrganizationMembers/DestroyTest.php

# --- Step 6: config edit, LAST — only after every file has left tests/Unit except the 3 isolated ones ---
# tests/Pest.php — change ->in('Feature', 'Unit') to ->in('Feature')   (F14)
```

**Directory-level `git mv` safety, all 10 commands:** (1) complete ledger proves uniform child relationship — yes, Models' two exceptions are handled separately in Step 3, not hidden in Step 1; (2) no target pre-exists — confirmed by direct `find` (today `tests/Feature/` has only `Console`, `Http`, `Middlewares`); (3) each command is single-purpose, nothing bundled that could hide an exception; (4) before/after counts stated inline.

**Empty directories expected afterward:** none — each Step 1 command relocates its entire source directory in one Git operation, so the source path ceases to exist. `tests/Unit/` itself survives, non-empty (`Enums/`, `Support/`).

---

## 11. Affected repository references (searched, not edited)

- `phpunit.xml` — references `<directory>tests/Unit</directory>` / `<directory>tests/Feature</directory>` by top-level name only; neither is being renamed — **no edit needed**.
- `.github/workflows/tests.yml` / `lint.yml` — run `./vendor/bin/pest` / lint scripts with no path filter — **no edit needed**.
- `composer.json` (`scripts.test`, `scripts.ci:check`) — runs `@php artisan test`, no path filter — **no edit needed**.
- `content-backlog.md` — 2 references, both to `tests/Feature/Http/OrganizationMembers/{Index,ResetTwoFactor}Test.php`, neither of which moves — **no edit needed**.
- `plan.md` — contains the entire superseded first-draft audit as its current uncommitted content; will need a full rewrite once this corrected proposal is approved — **not edited now**, flagged for the authorized pass.
- No test file references another test file's path; the two global Pest helpers are declared in `Pest.php` itself and resolvable regardless of caller directory, provided that directory still binds `TestCase`/container — which `Feature` will, post-§3.
- No `phpstan.neon`/rector config exists; no `.idea` run configuration references any test path.

---

## 12. Verification plan for the authorized pass (not run now)

1. **Pre-move baseline:** `git status --short` (expect only the pre-existing `plan.md` diff), `find tests -type f | wc -l` (expect 156).
2. **Structure/count verification after Steps 1–3:** `find tests/Unit -type f | wc -l` (expect 3), `find tests/Feature -type f | wc -l` (expect 150), `find tests -type f | wc -l` (expect 155); diff the tree against §5's manifest file-by-file.
3. **Targeted tests for Steps 3–4:** `./vendor/bin/pest tests/Feature/Models/DocumentTest.php tests/Feature/Models/UserTest.php` (F1/F2), then each of the 9 F3–F11 files alongside its paired Action test, to confirm the trimmed HTTP case and the still-passing Action test together still prove the same defects.
4. **`Pest.php` edit (F14) — isolated first:** `./vendor/bin/pest tests/Unit` (expect exactly the 3 survivors passing, without booting the app) before the full suite, to catch a binding mistake early.
5. **Full suite:** `./vendor/bin/pest` (or `php artisan test --compact` per `CLAUDE.md`'s convention) — must be fully green.
6. **Formatting/static checks:** `vendor/bin/pint --dirty --format agent` (per `CLAUDE.md`'s Pint rule, since `git mv` + edits touch these files), then `composer lint:check`.
7. **Stale-path search:** re-run the §11 grep sweep against the post-move tree — expect zero hits pointing at a moved path.
8. **`git diff --check`** across every touched file for whitespace/conflict markers.
9. **Final changed-file scope:** `git status --short` should show exactly 74 renames (Steps 1–2), 1 rename (F1), 1 deletion + 1 modification (F2), 9 modifications (F3–F11), optionally 7 more modifications if F12 is also authorized, 1 modification (`Pest.php`, F14) — nothing else.

---

## 13. Genuine skill ambiguity/defect exposed this pass

1. `rules/test-ownership.md`'s Model row states a canonical path but silently relies on Boost's `naming.md` for the "one file, correctly named" constraint — worth an explicit cross-reference (§6).
2. `blueprints/pest-testing.md`'s Unit/Feature boundary check is sound methodology but stops short of stating the actual `Pest.php` fix once the taxonomy is corrected — §3's proposal is a reasonable inference from its stated "no application boot" principle, not text the skill states outright.
3. Neither skill resolves middleware test-directory naming (§9), nor does either skill have a row for testing a shared global-scope class like `CurrentOrganizationScope` (§6) — acknowledged gaps, not defects, since `my-laravel-stack`'s Boundary section explicitly disclaims mandating architecture beyond its listed rows.
4. The first pass's own errors (Actions miscount, missed `testing-best-practices` activation, the `Pest.php` contradiction) were execution misses, not defects in either skill's content.

---

## 14. Confirmation that nothing changed

Zero `Write`/`Edit`/`NotebookEdit` calls this session. Every action was `Read`, read-only `Bash` (`find`/`grep`/`wc`/`cat`/`git rev-parse`/`git status`), or research `Agent` forks. `git status --short` in both repositories shows only the two pre-existing states noted at the top (`useOrbit`: unstaged `plan.md`; `agentic-engineering`: untracked `.idea/`) — neither touched by this pass. Both repository HEADs and the skill provenance SHA match your expected values exactly.
