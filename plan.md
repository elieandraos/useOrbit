# Policies HTTP & Frontend/UI Layer

> **Status: source of truth for the subsequent `plan-it` pass.** This is a **revision** of the
> previously-approved plan (git history `a529ff6`), triggered by a deliberate architectural pivot
> after #322–#325 shipped. Section markers below (**CONFIRMED** / **RECOMMENDATION** /
> **DECIDED IN THIS REVISION** / **STILL OPEN**) distinguish implementation fact from judgment
> calls — read them, don't skim past them. Implementation details not marked open still need
> verifying against the codebase when individual issues are built.

## Why this plan changed

The original plan (and #322–#325, built from it) used **one shared `PoliciesController` /
`StorePolicyRequest` / `UpdatePolicyRequest` / `CreatePolicyAction` / `UpdatePolicyAction` /
`PolicyResource`**, with per-class behavior as conditional branches inside those shared files
(`Rule::in`, `class === PolicyClass::X`, one private method per class). #322 (index) and #324
(show) shipped as a single unified list/detail across all 6 classes; #323 (create) and #325 (edit)
shipped Medical-only, with #326–330 ("support Automotive/Expat/Fire/Life/Travel") queued to extend
the same shared files with one branch each.

**The user has explicitly decided to abandon that shared-file trajectory in favor of a flat,
type-oriented split**: separate controller/request/action/resource per policy class for the
create/show/edit/update lifecycle (`PoliciesMedicalController`, `StorePolicyMedicalRequest`,
`CreatePolicyMedicalAction`, `PolicyMedicalResource`, etc., × 6), matching this app's plural
controller-naming convention (`NotesController`, `AgentsController`).

**This is confirmed as a deliberate choice, not a finding this investigation arrived at
independently.** The implementation evidence from #322–325, read on its own, argues for
*continuing* the shared-file approach (see "What the evidence shows" below) — the codebase's own
closest precedent (`Client`'s `client_type`-driven branching), the already-shipped unified
index/show endpoints and tests, and all 23 remaining open issues assumed one shared stack. The
user weighed that evidence and chose the split anyway, accepting that it reworks merged code and
reshapes most of the open backlog. This plan documents the resulting target architecture; it does
not re-litigate the choice.

## Summary (plain language)

The Policies **domain model** (schema + Eloquent layer) is already built and merged, unchanged by
this plan: `Policy`, one detail model per insurance class (Medical/Automotive/Expat/Fire/Life/
Travel), and `PolicyInsured` (covered members) — one shared `Policy` table/model underneath
everything below. Phase 26 wires that domain model to a real HTTP + Inertia + Vue surface.

Four HTTP endpoints already exist (index, create/store, show, update) built as one shared
`PoliciesController`, working end-to-end for the **Medical** class only (Automotive/Expat/Fire/
Life/Travel are rejected by validation today). This revision keeps the **index** (browse/list)
endpoint shared — it's read-only and class-agnostic by design (the list never shows class-specific
fields) — but splits **create/store/show/edit/update** into one full stack per policy class, so
that class-specific validation, persistence, and formatting each live in their own class-named
files instead of accumulating as conditional branches in shared ones.

## What the evidence shows (CONFIRMED)

### Domain model (built, unchanged by this plan)

- `app/Models/Policy.php` + six detail models (`PolicyMedicalDetails`, `PolicyAutomotiveDetails`,
  `PolicyExpatDetails`, `PolicyFireDetails`, `PolicyLifeDetails`, `PolicyTravelDetails`) +
  `PolicyInsured`, all with factories. `PolicyClass` enum has a `detailsRelation(): string` method
  (`medicalDetails`, `automotiveDetails`, ...) — the domain layer already exposes a
  class→relation dispatch helper; this plan's split happens one layer above it (HTTP/action/
  resource), not in the model.
- `Policy` implements `Documentable`/`Notable`, uses `HasDocuments`/`HasNotes`/
  `BelongsToCurrentOrganization`/`Filterable`/`Sortable`/`HasSlug`/`SoftDeletes` — unchanged by this
  plan.

### What #322–#325 actually built

- **One shared `PoliciesController`** (`app/Http/Controllers/Policies/PoliciesController.php`) —
  `index`, `store`, `show`, `update` all on one class, all `#[Authorize(...)]`-guarded against
  `Policy::class`/`'policy'`. No `create`/`edit` (GET form) methods exist yet — those Vue pages
  (#336/#338) haven't landed.
- **One route file** (`routes/policies.php`) — `policies.index/store/show/update`, one route per
  verb, no per-class path segment.
- **Class-specific logic today is Medical-only and hardcoded, not a generic dispatcher**:
  - `StorePolicyRequest`/`UpdatePolicyRequest`: `'class' => ['required', Rule::in([PolicyClass::Medical->value])]`
    — every other class is currently rejected by validation.
  - `CreatePolicyAction`/`UpdatePolicyAction`: a private `createMedicalDetails()`/
    `updateMedicalDetails()` method; no dispatch-by-class mechanism exists.
  - `PolicyResource`: one `$this->when($this->class === PolicyClass::Medical, ...)` branch wrapping
    `PolicyMedicalDetailsResource`.
  - `PolicyPolicy` (`viewAny`/`view`/`create`/`update`) is **class-independent** — it only checks
    organization ownership, never `$policy->class`. No reason for this to be split per class.
- **`resources/js/pages/Policies/Index.vue` and `Show.vue` are stubs** (14 and 13 lines,
  `<div class="flex flex-1 flex-col" />`) — the real UI (table, tabs, cards) is entirely still
  ahead of us (#335, #337, etc.), so no Vue rework is needed for what's merged — only the plan for
  what's not yet built changes.
- **No `Filter`/`Sort` classes exist yet** (`app/Filters/Policy*`, `app/Sorts/Policy*` — none) —
  #332/#333 are still fully open, nothing to rework there.
- Issue #323 itself was already scoped as **"create a new Medical policy"**, not a generic
  create — the issue decomposition already proceeded one class at a time even under the old
  shared-file plan. That precedent transfers cleanly to the new one; only the *destination* files
  for that per-class work change (dedicated classes instead of branches).
- Issue #326 (Automotive, still open) explicitly documents the shared-file growth path the old
  plan intended: *"extends `StorePolicyRequest`, `UpdatePolicyRequest`, `CreatePolicyAction`/
  `UpdatePolicyAction`, and `PolicyResource`'s conditional `details` key to cover Automotive."*
  #327–330 mirror this for Expat/Fire/Life/Travel. **All five now need re-scoping** (see
  "Remaining Phase 26 work" below).
- The remaining 23 open issues (as of this revision) assumed one shared `Policies/` Vue folder,
  one `PolicyFilter`/`PolicySort`, and one `Create.vue`/`Show.vue`/`Edit.vue` — several of these
  (create/show/edit pages, per-tab issues, export) need re-scoping into per-class stacks; the
  index/browse-related issues (#331, #332, #333, #335, #344) stay materially as scoped, now
  explicitly serving the shared cross-class browse layer rather than "the" Policies UI.
- In-app precedent for the *shared* approach: `Client`'s `client_type`-driven conditional
  validation (`StoreClientRequest`, 46 lines, 2 branches) is the closest existing analogue, and is
  smaller in both branch count (2 vs 6) and per-branch field count than Policy's detail blocks —
  this precedent supported the original plan, not this revision; it's recorded here because it's
  why the original plan chose that shape, not because it settles this one.

## Decided in this revision (explicit, user-approved)

These are judgment calls the user made explicitly in this conversation, after seeing their
consequences — not derived from the evidence above, which on its own pointed the other way.

1. **Full per-class split for create/store/show/edit/update.** One controller, one pair of
   requests, one pair of actions, one resource per policy class — not conditional branches in
   shared files. Accepted consequence: #322–325's merged `PoliciesController` store/show/update
   methods get extracted out into per-class controllers (rework of shipped code, not just future
   work); most of the remaining open issues (#326–330, #336–348) get reshaped, not just continued.
2. **The cross-class browse layer stays shared.** A Client, Agent, or Carrier's policies span all
   6 classes at once (single `Policy` table/model, unchanged domain design) — `PoliciesController`
   keeps a single, read-only `index` (the "all policies" list, matching `_design/policies-screen.jsx`'s
   one-list-with-a-class-column layout) and the three cross-entity `{Client,Agent,Carrier}PoliciesController@index`
   endpoints stay shared and read-only across all classes. Clicking a row in any of these routes
   into that policy's own class-specific `show` (e.g. `policies.medical.show`,
   `policies.automotive.show`). Nothing about **create/edit/update** is shared — only listing/
   browsing is.
3. **Controller naming**: plural, matching `NotesController`/`AgentsController` — `PoliciesMedicalController`,
   `PoliciesAutomotiveController`, `PoliciesExpatController`, `PoliciesFireController`,
   `PoliciesLifeController`, `PoliciesTravelController` (not `PolicyMedicalController`). No
   `delete`/`destroy` method on any of them — locked decision 1 (below) is unchanged by the split:
   Policy has no delete action anywhere. Each per-class controller holds exactly `create`, `store`,
   `show`, `edit`, `update` — no `index` (browsing is exclusively the shared layer's job, decision 2).
4. **"New Policy" entry point**: `PoliciesController` also gets a `create` method (new — #322–325
   never built one), `GET policies/create`, rendering a **shared** `Policies/Create.vue` — step 1
   only (type toggle, insurance-class grid, parties, status/source per `_design/new-policy.jsx`'s
   `NewPolicyClassPicker`), fed the client/carrier/agent option lists. This has to be shared because
   the class isn't known yet. Picking a class and continuing does a real Inertia visit to that
   class's own `policies.{class}.create` (e.g. `policies.medical.create`), carrying the step-1
   values along as real, visible, editable pre-filled fields on that page — **not hidden
   passthroughs** — because `Policy{Class}/Create.vue` must contain every field
   `StorePolicy{Class}Request` validates (parties/status/period/financials + the class section),
   not just the class-specific section. This matters for validation: `store`/`update` POSTs
   originate from `Policy{Class}/Create.vue`/`Edit.vue` themselves, so a failed `FormRequest`
   re-renders that same page with its `errors` prop (standard Inertia behavior) — it never bounces
   back to the shared `PoliciesController@create` picker. That only works if every validatable
   field actually has a visible input on that page; the original single-page client-side-step
   wizard didn't have this constraint, but splitting create into two separate pages/routes
   introduces it. **The shared step-1 picker is Create-only.** A policy's `class` is
   fixed/non-editable once created, so `Policies{Class}Controller@edit` goes straight to that
   class's own pre-filled `Policy{Class}/Edit.vue` — no step-1 hop, no shared `PoliciesController`
   involvement at all for edit/update.
5. **Composed actions, not flat/independent ones.** `CreatePolicyAction`/`UpdatePolicyAction`
   (already built by #323/#325) are **kept**, but narrowed to only the fields every class shares —
   the base `Policy` row (policy/slug number generation, `client_id`/`carrier_id`/`agent_id`,
   dates, amounts, `status`/`source`) — no detail-table or `insureds` writes. Each
   `CreatePolicy{Class}Action`/`UpdatePolicy{Class}Action` is injected with, and calls, the shared
   action to get the persisted/updated `Policy`, then writes its own detail row (and, where that
   class supports covered members, syncs `PolicyInsured` the way `CreatePolicyAction`/
   `UpdatePolicyAction` do today for Medical) on top. Controllers depend on
   `CreatePolicy{Class}Action`/`UpdatePolicy{Class}Action` only, never the shared action directly.
   This resolves the Recommendation below for **Actions specifically** — the shared-object shape
   (option 2 there), not a trait/abstract-class base. **Requests are explicitly not decided yet** —
   whether `StorePolicy{Class}Request`/`UpdatePolicy{Class}Request` get a similar shared/composed
   base is deferred until the composed-action shape has been built at least once (Medical) and its
   fit assessed; each stays a single flat `FormRequest` per class in the meantime, matching what
   already exists.
6. **Filters and exports split by whether they operate on one policy or the cross-class list.**
   `PolicyFilter`/`PolicySort` and the **Excel** export stay shared (`PoliciesExcelExportController`,
   mirroring `PoliciesController`) — all three operate on the cross-class browse list, which never
   needs class-specific fields. The **PDF** export is per-class (mirroring `show()`, which already
   needs the class-specific detail data to render) — `Policies{Class}Controller` or a dedicated
   `Policies{Class}PdfExportController` per class, not a shared `PoliciesPdfExportController`.

## Recommendation (not yet locked — flag for review, doesn't block starting #326-class work)

**Actions are resolved** (decision 5 above: shared `CreatePolicyAction`/`UpdatePolicyAction`,
composed into by each `CreatePolicy{Class}Action`/`UpdatePolicy{Class}Action`) — the duplication
concern that motivated this section for Actions no longer applies.

**Requests are explicitly deferred, not decided** (decision 5 above): whether
`StorePolicy{Class}Request`/`UpdatePolicy{Class}Request` get an equivalent shared/composed base for
their ~20 common fields (`policy_number`, `class`, `type`, `client_id`, `carrier_id`, `agent_id`,
`effective_date`, `expiry_date`, `premium_amount`, `discount_amount`, `status`, `source`, plus the
`insureds` rules for Group policies), or stay fully independent per class, is left until the
composed-action shape has been built at least once and its fit assessed. Revisit when the second
class (first post-Medical) request is written.

**Resources stay independent per class, not composed** — `Policy{Class}Resource` (used only by
that class's own `show`) was not raised as part of this composition question; each still declares
its own full field mapping (see Target architecture §3). If the same duplication concern comes up
here once a second class ships, it's open to revisit then — not decided either way now.

Why this matters at all: unlike `Note` vs `Agent` (fully independent models), all six policy
classes sit on top of the *same* `Policy` row — the base fields are identical, not just
similarly-shaped. Six independent copies of the same ~20-field rule set is real duplication risk
that plain per-class separation (as in the Notes/Agents precedent) doesn't usually create — hence
resolving it for Actions now, and flagging it (without deciding) for Requests and Resources.

**`PolicyPolicy` stays a single shared class** (`viewAny`/`view`/`create`/`update`), used by all
six controllers — its rules never depend on `class`, so there's no `PolicyMedicalPolicy` etc.

`PolicyFilter`/`PolicySort` being shared (decision 6 above) means there is no per-class index to
filter/sort — this directly narrows the original proposal's `PolicyMedicalFilter`/
`PolicyMedicalSorter` idea, which has no controller to attach to under decision 2.

## Still open (needs a decision before or during the relevant issue, not now)

- **Route path shape** for the per-class stacks — proposed default: `policies/medical`,
  `policies/medical/create`, `policies/medical/{policy:slug}`, `policies/medical/{policy:slug}/edit`,
  named `policies.medical.create/store/show/edit/update` (mirrored ×6). Nothing else in this app
  nests a type segment under a plural resource path this way, so treat this as a proposal to
  confirm, not an established convention.
- **Request composition shape** (see Recommendation above) — pick if/when it's revisited, not
  before.
- **Resource composition** (see Recommendation above) — not raised, not decided; revisit only if
  duplication becomes a real problem once a second class ships.
- **Whether the *existing* Medical stack (#322-325's `store`/`show`/`update`) gets extracted into
  `PoliciesMedicalController` etc. as its own issue, or as part of whichever issue replaces #326** —
  this is a `plan-it`/issue-decomposition question, not resolved here.

## Established full-stack conventions this plan still mirrors (from `Client`/`Carrier`/`Agent`)

Unchanged from the original plan — still the conventions each per-class stack (and the shared
browse layer) follows:

- **Requests**: one `FormRequest` per write/read action, `prepareForValidation()` normalizes
  booleans/defaults, `Rule::requiredIf()`/`prohibitedIf()` for conditional fields.
- **Authorization**: `#[Authorize('ability', Model::class|'routeParamName')]` on every action
  method; policy classes live in `app/Policies/`, registered in
  `AppServiceProvider::registerPolicies()`.
- **Resources**: flat `JsonResource`, raw + `_formatted`/`_label` derived fields, relations via
  `whenLoaded()`.
- **Routes**: one file per domain under `routes/`, `Route::middleware(['auth', 'organization'])`,
  route-model-bound by `slug`.
- **Vue**: `Index.vue` (table + `FiltersDrawer.vue` + pagination footer), `Create.vue`/`Edit.vue`
  sharing a form partial, `Show.vue` + detail-shell tabs, Wayfinder-generated route helpers.
- **Testing**: `tests/Feature/Http/{Domain}/{Action}Test.php`, `tests/Feature/Actions/{Domain}/{Action}ActionTest.php`,
  `tests/Feature/Policies/{Model}PolicyTest.php`, `tests/Feature/Filters/{Model}FilterTest.php`,
  `tests/Feature/Sorts/{Model}SortTest.php`.

## Target architecture

### 1. Authorization — unchanged, shared

`PolicyPolicy` (already built, `viewAny`/`view`/`create`/`update`, organization-scoped only) is
used by the shared browse controllers and all six per-class controllers alike. No per-class
policy classes.

### 2. Shared cross-class browse layer

- `PoliciesController@index` — the "all policies" list across all 6 classes (already built,
  currently a stub Vue page). Stays shared. Links each row to that policy's own
  `policies.{class}.show` route.
- `PoliciesController@create` — new method (didn't exist before this revision): the shared "pick a
  class" entry point for the "New Policy" button (see Decision 4 above). Renders step 1 only;
  forking to a per-class `create` happens via a real navigation, not client-side-only state.
- `ClientPoliciesController@index`, `AgentPoliciesController@index`, `CarrierPoliciesController@index` —
  read-only, cross-class, scoped by the parent's FK (as originally planned in §9 of the prior
  version) — unchanged by this revision.
- `PolicyResource` — stays the shared, class-agnostic resource for **list rows only** (the design's
  list columns need no class-specific fields). No longer used for `show`.
- `PolicyFilter`/`PolicySort` — shared, used only by the browse layer.
- `PoliciesExcelExportController` — shared (invokable), exports the cross-class list. Mirrors
  `ClientsExcelExportController`.
- `CreatePolicyAction`/`UpdatePolicyAction` — **kept, narrowed**: still the two action classes
  #323/#325 built, but scoped down to only the base `Policy` row (policy/slug number generation +
  the ~12 shared fields). No longer write any detail table or `PolicyInsured` rows themselves —
  each `CreatePolicy{Class}Action`/`UpdatePolicy{Class}Action` below injects and calls one of
  these to get the persisted/updated `Policy`, then does its own class-specific writes on top
  (decision 5).

### 3. Per-class stacks (× Medical, Automotive, Expat, Fire, Life, Travel)

For each policy class:

- `app/Http/Controllers/Policies/Policies{Class}Controller.php` — `create/store/show/edit/update`
  (no `index`, no `delete`/`destroy` — browsing is exclusively the shared layer's job, and Policy
  has no delete action at all, decision 3). `#[Authorize]` against `Policy::class`/`'policy'` via
  the shared `PolicyPolicy`.
- `app/Http/Requests/Policies/StorePolicy{Class}Request.php` / `UpdatePolicy{Class}Request.php` —
  one flat `FormRequest` per class (composition deferred, see Recommendation/Still-open above),
  validating both the shared base fields and this class's own detail-block fields, migrated
  verbatim from the corresponding `medical.*` block already in `StorePolicyRequest`/
  `UpdatePolicyRequest` for Medical.
- `app/Actions/Policies/CreatePolicy{Class}Action.php` / `UpdatePolicy{Class}Action.php` —
  **composed**, not flat (decision 5): each injects the shared `CreatePolicyAction`/
  `UpdatePolicyAction`, calls it to persist/update the base `Policy` row, then writes its own
  detail row + syncs `PolicyInsured` rows when that class supports covered members (only
  Medical/Group does today) — same transaction shape already proven by the current
  `CreatePolicyAction`/`UpdatePolicyAction`, just split across two composed classes instead of one.
- `app/Http/Resources/Policy{Class}Resource.php` — used only by that class's own `show` response;
  replaces `PolicyResource`'s conditional `details` key + `PolicyMedicalDetailsResource` etc. for
  the show page specifically. Independent per class (not composed — see Recommendation above).
- `Policies{Class}PdfExportController` — per class (invokable), mirrors `ClientsPdfExportController`;
  needed because a policy's PDF renders its class-specific detail data, same reasoning as `show`.
- `resources/js/pages/Policy{Class}/{Show,Edit}.vue` (+ shared/duplicated `Create.vue` step-2
  section per class, per #336) — per-class show/edit pages; `partials/Policy{Class}DetailShell.vue`
  for the class's own tab bar (Overview / Members [Group-only] / Settlements-placeholder /
  Documents / Notes), mirroring `PolicyDetailShell.vue`'s originally-planned shape.

### 4. Routes

Shared (unchanged from the original plan):

```php
Route::get('policies', [PoliciesController::class, 'index'])->name('policies.index');
Route::get('policies/create', [PoliciesController::class, 'create'])->name('policies.create');
Route::get('policies/export', PoliciesExcelExportController::class)->name('policies.export');
Route::get('clients/{client:slug}/policies', [ClientPoliciesController::class, 'index'])->name('clients.policies.index');
Route::get('agents/{agent:slug}/policies', [AgentPoliciesController::class, 'index'])->name('agents.policies.index');
Route::get('carriers/{carrier:slug}/policies', [CarrierPoliciesController::class, 'index'])->name('carriers.policies.index');
```

Per class (× 6, proposed shape — see "Still open"):

```php
Route::get('policies/medical/create', [PoliciesMedicalController::class, 'create'])->name('policies.medical.create');
Route::post('policies/medical', [PoliciesMedicalController::class, 'store'])->name('policies.medical.store');
Route::get('policies/medical/{policy:slug}', [PoliciesMedicalController::class, 'show'])->name('policies.medical.show');
Route::get('policies/medical/{policy:slug}/edit', [PoliciesMedicalController::class, 'edit'])->name('policies.medical.edit');
Route::patch('policies/medical/{policy:slug}', [PoliciesMedicalController::class, 'update'])->name('policies.medical.update');
Route::get('policies/medical/{policy:slug}/export', PoliciesMedicalPdfExportController::class)->name('policies.medical.export-pdf');
```

`PolicyDocumentsController`/`PolicyNotesController` (documents/notes tabs) stay shared and
class-agnostic — `Documentable`/`Notable` never depended on class.

## Locked decisions carried over from the prior plan (still apply)

1. **No delete action for Policy.** List/show expose only Edit. `soft_deletes` stays for
   data-retention only. (If a delete/archive action is ever added later, it belongs on
   `Policies{Class}Controller` — e.g. `@destroy` or a dedicated `Policies{Class}ArchiveController`
   — never on the shared `PoliciesController`, since deleting always acts on a policy whose class is
   already known, same reasoning as show/edit/update. Not built now — recorded for when/if it comes
   up.)
2. **No Freeze/Cancel/Reactivate lifecycle actions.** `status` is set via the regular create/update
   form field only.
3. **Settlements/payment-tracking UI is a "coming soon" placeholder**, out of scope for this pass
   (per the already-approved domain-model plan).
4. **Covered members (`policy_insureds`) are captured at creation and editable afterward** via the
   Edit form (add/update/remove); the Show page's Members tab stays read-only.

## What must remain true / unchanged

- No changes to the `policies`, `policy_*_details`, or `policy_insureds` schema.
- No changes to `Client`/`Carrier`/`Agent` migrations — only the already-added `policies()`
  relations.
- `Policy`'s trait/contract composition is reused as-is.
- No payment/settlement, renewal, or commission functionality is built.

## Explicitly out of scope for this pass

Settlement/payment recording, policy lifecycle status-transition actions as dedicated endpoints,
hard delete of policies, Notify-on-policy, renewal workflows, commission tracking.

## Remaining Phase 26 work — how it reshapes (for the next `plan-it` pass; no issues modified yet)

This is guidance for re-scoping, not a done reshape — actual issue edits are `plan-it`'s job.

| Issue | Old scope (shared-file plan) | New scope under the split |
|---|---|---|
| #322–325 (closed) | Shared `PoliciesController` index/store/show/update, Medical-only | **Evidence, not reopened** — but store/show/update methods will need extracting into `PoliciesMedicalController` as part of the work below |
| #326–330 ("support {Class}") | Add one branch to each shared file | Becomes "build the full `Policies{Class}Controller`/Request/Action/Resource stack for {Class}", including migrating Medical's own store/show/update out of the shared controller as part of (or just before) #326 |
| #331, #332, #333, #335, #344 (index, filters, sorting, empty state, filters drawer) | One shared Policies index | **Materially unchanged** — these already describe the shared cross-class browse layer this revision keeps |
| #334 (top nav link) | Link to shared index | Unchanged |
| #336 (create page) | One `Create.vue`, one `policies.store` | Shared `Policies/Create.vue` (step 1: class/type/parties/status, served by new `PoliciesController@create`) navigates to `policies.{class}.create`; per-class `Policy{Class}/Create.vue` (step 2) submits to `policies.{class}.store` |
| #337, #345–348 (show page + tabs) | One `Show.vue`/`PolicyDetailShell.vue` | Per-class `Show.vue`/`Policy{Class}DetailShell.vue` (× 6), same tab set each |
| #338 (edit page) | One `Edit.vue` | Per-class `Edit.vue` (× 6) |
| #339, #340 (export) | Shared export actions | Excel (list) export stays shared (`PoliciesExcelExportController`); PDF (single-record) export becomes per-class (`Policies{Class}PdfExportController` × 6) |
| #341–343 (wire Client/Agent/Carrier Policies tabs) | Link to shared index, scoped by parent | Unchanged — these already point at the shared cross-class browse layer |

## Evidence index

- `app/Http/Controllers/Policies/PoliciesController.php`, `app/Http/Requests/Policies/{Store,Update}PolicyRequest.php`,
  `app/Actions/Policies/{Create,Update}PolicyAction.php`, `app/Http/Resources/PolicyResource.php`,
  `app/Policies/PolicyPolicy.php` — current shared implementation, confirming Medical-only,
  hardcoded (no dispatcher) class handling
- `app/Enums/PolicyClass.php` (`detailsRelation()`), `app/Models/Policy.php` (six detail
  relations) — domain-layer dispatch helper this plan's split sits above, not inside
  `resources/js/pages/Policies/{Index,Show}.vue` — confirmed stubs, no rework needed for what's
  merged on the frontend
- `git log` (`0899bb7`, `b2075ca`, `6b34720`, `946e538`) — the four merged commits behind #322–325
- GitHub issues #322–348 (`gh issue list --milestone "Phase 26 — Policies HTTP & Frontend"`) —
  current open/closed state and issue bodies (#326, #336–338, #344, #345 read directly) informing
  the reshape table above
- `app/Http/Requests/Clients/StoreClientRequest.php`, `app/Actions/Clients/CreateClientAction.php`,
  `app/Http/Resources/ClientResource.php` — the in-app precedent for the shared-branching approach
  the user explicitly moved away from in this revision
- Prior plan version (git history `a529ff6`) — the shared-file architecture this revision
  supersedes for create/show/edit/update, while keeping its index/browse and cross-entity-tab
  decisions intact
- Prior approved domain-model plan (git history `c258d30`) — schema/domain-model decisions treated
  as settled context, not re-litigated
