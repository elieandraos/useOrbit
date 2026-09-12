# Policies HTTP & Frontend/UI Layer

> **Status: source of truth for the subsequent `plan-it` pass.** Implementation details (exact
> validation rule arrays, filter/sort method bodies, Vue component boundaries, etc.) still need
> verifying against the codebase when individual issues are built.

## Summary (plain language)

The Policies **domain model** (schema + Eloquent layer) is already built and merged: `Policy`, one
detail model per insurance class (Medical/Automotive/Expat/Fire/Life/Travel), and `PolicyInsured`
(covered members). None of it is reachable by a user yet — there are no routes, controllers,
requests, authorization, Inertia pages, or tests for Policy. This plan wires the finished domain
model to a real HTTP + Inertia + Vue surface, following the exact conventions already established
by `Client`/`Carrier`/`Agent`, and connects it to UI that's already scaffolded elsewhere in the app
specifically waiting for Policies to exist (inert "Policies" tabs/cards/counts on the Client,
Agent, and Carrier show pages).

Settlement/payment tracking stays out of scope (per the already-approved domain-model plan); this
pass gives it an inert "coming soon" placeholder rather than building it.

## Current state (verified)

### Domain model (built, unchanged by this plan)

- `app/Models/Policy.php` + six detail models (`PolicyMedicalDetails`, `PolicyAutomotiveDetails`,
  `PolicyExpatDetails`, `PolicyFireDetails`, `PolicyLifeDetails`, `PolicyTravelDetails`) +
  `PolicyInsured`, all with factories. Enums: `PolicyClass`, `PolicyType`, `PolicyStatus`,
  `PolicySource`, plus class-specific enums (`ExpatCoverageZone`, `MedicalCoverageScope`,
  `MedicalClassTier`, `Gender`).
- `Policy` already implements `Documentable` and `Notable` (`documentableKind()`/`notificationSubjectKind()`
  both return `'policy'`) and uses `HasDocuments`/`HasNotes`/`BelongsToCurrentOrganization`/`Filterable`/
  `Sortable`/`HasSlug`/`SoftDeletes` — identical trait composition to `Client`.
- `app/Providers/AppServiceProvider.php` already morph-maps `'policies' => Policy::class`
  (`registerMorphMap()`), but **`registerPolicies()` has no `Gate::policy(Policy::class, ...)`
  entry** — there is no `PolicyPolicy` class yet at all.
- **No inverse relations exist yet**: `Client`, `Carrier`, and `Agent` models have no `policies()`
  relation, even though `Policy` `belongsTo` all three. Needed to compute real policy counts/lists
  for those entities' show pages (see below) — a small additive change, not a schema change (no
  migration needed, `client_id`/`carrier_id`/`agent_id` FKs already exist on `policies`).
- Zero controllers, requests, resources, filters, sorts, policies (authz), routes, or Vue pages
  reference `Policy` anywhere in `app/Http`, `routes/`, or `resources/js`.

### Established full-stack conventions (from `Client`/`Carrier`/`Agent`)

Verified directly in `app/Http/Controllers/Clients/*`, `app/Http/Requests/Clients/*`,
`app/Policies/ClientPolicy.php`, `app/Filters/ClientFilter.php`, `app/Sorts/ClientSort.php`,
`app/Http/Resources/ClientResource.php`, `resources/js/pages/Clients/**`, and the equivalent
Carrier/Agent files:

- **Controllers** (`app/Http/Controllers/{Domain}/`): one resourceful controller
  (`{Domain}Controller` — `index/create/store/show/edit/update`) plus one single-purpose invokable
  controller per side-effect action (`{Domain}ArchiveController`, `{Domain}UnarchiveController`,
  `{Domain}ExcelExportController`, `{Domain}PdfExportController`, `Notify{Domain}Controller`).
  Every action method carries `#[Authorize('ability', Model::class|'routeParamName')]`. Actions are
  never inlined in controllers — they call a dedicated `App\Actions\{Domain}\...Action::handle()`.
- **Requests**: one `FormRequest` per write/read action (`Store{Model}Request`,
  `Update{Model}Request`, `Index{Model}Request`). `Index{Model}Request::prepareForValidation()`
  normalizes booleans/sort defaults. Conditional-field validation uses `Rule::requiredIf()` /
  `Rule::prohibitedIf()` keyed off another submitted field (see `StoreClientRequest`'s
  `client_type`-driven company/individual branching) — the pattern this plan reuses for
  class-driven policy fields.
- **Authorization** (`app/Policies/{Model}Policy.php`, registered in
  `AppServiceProvider::registerPolicies()`): `viewAny`/`create` check `$user->organization_id !== null`;
  `view`/`update` check `$model->organization_id === $user->organization_id`; `delete`/`archive`/
  `unarchive` additionally require `$user->role->isPrivileged()`. Cross-cutting `DocumentPolicy`/
  `NotePolicy` take the polymorphic parent as a second argument (`#[Authorize('viewAny', [Document::class, 'client'])]`).
- **Resources** (`app/Http/Resources/`): flat `JsonResource` per model, both raw and `_formatted`/
  `_label` derived fields computed server-side (e.g. `date_of_birth` + `date_of_birth_formatted`,
  `gender` + `gender_label`), relations exposed via `whenLoaded()`.
- **Filters/Sorts** (`app/Filters/`, `app/Sorts/`): `{Model}Filter extends QueryFilter`,
  `{Model}Sort extends Sort`; `QueryFilter::apply()` dynamically calls a camelCase method per
  non-empty filter key.
- **Routes**: one file per domain under `routes/`, required from `routes/web.php`,
  `Route::middleware(['auth', 'organization'])`, route-model-bound by `slug`
  (`{client:slug}`), RESTful path shape (`clients`, `clients/create`, `clients/{client:slug}`,
  `clients/{client:slug}/edit`).
- **Documents/Notes** (cross-cutting, already generic): `Client{Documents,Notes}Controller` are the
  exact pattern to replicate — `index` returns an Inertia page with the parent resource + the
  polymorphic collection; `store` returns a plain Resource (used for an Inertia-partial-style
  in-page append, not a redirect). Routed as **separate pages** (`clients/{client:slug}/documents`,
  `clients/{client:slug}/notes`), reached via `href`-based tabs from the show page, not client-side
  tab state.
- **Vue**: `Index.vue` (table + `FiltersDrawer.vue` + pagination footer per the house convention:
  `Showing 1–N of N · Previous · 1 2 3 · Next`), `Create.vue` + `Edit.vue` sharing one
  `partials/{Model}Form.vue`, `Show.vue` + `partials/{Model}DetailShell.vue` (tabs) +
  `partials/{Model}ShowHeader.vue` + per-section card partials, `partials/{model}.ts` (a hand-picked
  subset of the Resource shape used by the frontend). Routing uses Wayfinder-generated helpers
  (`@/routes/{model}`, `.form()`), never hand-written URLs.
- **Testing** (`tests/Feature/`): `Http/{Domain}/{Action}Test.php` (one file per HTTP action —
  `IndexTest`, `StoreTest`, `ShowTest`, `UpdateTest`, `ArchiveTest`, `ExcelExportTest`,
  `PdfExportTest`, `DocumentsIndexTest`, `DocumentsStoreTest`, `NotesIndexTest`, `NotesStoreTest`),
  `Actions/{Domain}/{Action}ActionTest.php`, `Policies/{Model}PolicyTest.php`,
  `Filters/{Model}FilterTest.php`, `Sorts/{Model}SortTest.php`, `Exports/{Model}sExportTest.php`
  (Excel export, tested separately from the PDF export's HTTP test).

### Design export (`_design/`, the actual target UI)

- `_design/policies-screen.jsx` — index page. Columns: Policy (id + class tile) / Type · Class /
  Client / Effective→Expiry / Amount / Status. Filters drawer: search, status, type, class
  (multi-select), company (carrier), source, effective-date range, amount range
  (`PoliciesFiltersDrawer`). List-level "Export" button (`POLICY_CLASSES` map gives the six
  classes + their subclass lists). Per-row action menu (`PolRowActions`) mocks up View/Edit/Record
  payment/Freeze/Cancel — **superseded by the locked decisions below**, which reduce this to a
  single "Edit" item.
- `_design/new-policy.jsx` — create flow, two screens in sequence within one page:
  1. `NewPolicyClassPicker` — Policy type (Single/Group), an Insurance-class picker grid
     (`POLICY_CLASSES`), `NpPartiesSection` (client/carrier/agent pickers), `NpStatusSection`
     (status + source), footer "Continue · {Class} →".
  2. A class-specific screen (`NewPolicyMedicalDesktop`, `NewPolicyAutomotiveDesktop`, etc.):
     `NpCoverageHeaderSection` (type/sub-class/policy number), the class section
     (`NpMedicalSection`/`NpAutomotiveSection`/`NpExpatSection`/`NpFireSection`/`NpLifeSection`/
     `NpTravelSection`), `NpFamilySection` (Group) or `NpInsuredProfileSection` (Single, Medical
     only), `NpPeriodSection`, `NpFinancialsSection`.
  - `NpFamilySection` (covered members, Group policies) captures **name, relationship, gender,
    date of birth, medical history** per member — **no member-code field**, confirming
    `member_code` (e.g. `MBR-001`) must be server-generated, not user-entered.
  - `NpFinancialsSection` includes a "Payment method" toggle (Fully paid / Settlements +
    instalments) — settlement/payment tracking, out of scope per the domain-model plan; excluded
    from this pass's create/edit forms entirely (locked decision 3).
  - No "Delete", "Archive", or "Notify" affordance appears anywhere across all three design files.
- `_design/policy-detail.jsx` — show page. Tabs (`tabs` array, both desktop and mobile): Overview,
  **Members (`type === 'Group'` only)**, Settlements, Documents, Notes. The Members tab
  (`PdMembersTab`) is **view-only** — filter chips, search, a roster table, "Export roster" — no
  add/edit/remove affordance. Header actions are Edit + "Record payment" (the latter is
  settlements, out of scope). Settlements tab/stats/"Record payment" are the only settlement
  surfaces on this page.

### Already-scaffolded, currently-inert integration points

Verified directly in the Vue source — these are not proposed additions, they already exist waiting
for Policy to be real:

- `resources/js/pages/Clients/partials/ClientPoliciesCard.vue` — static card, disabled "Add policy"
  button, "No policies yet".
- `resources/js/pages/Agents/partials/PoliciesRenewalCard.vue` — static "No policies up for renewal
  yet" card.
- `resources/js/pages/Clients/partials/ClientDetailShell.vue` — `<Tab href="#">Policies</Tab>`
  (dead link) alongside real `href`-based Overview/Documents/Notes tabs; `policiesCount` is a
  required prop, hardcoded to `const policiesCount = 0` in `Clients/Show.vue`.
- `resources/js/pages/Agents/partials/AgentDetailShell.vue` — same dead `<Tab href="#">Policies</Tab>`;
  `policiesCount` prop declared but **never passed** from `AgentsController::show()`.
- `resources/js/pages/Carriers/partials/CarrierDetailShell.vue` — a client-side `ref` tab
  (`'overview' | 'policies'`) renders a literal **"Coming soon"** for the Policies tab;
  `policiesCount` prop declared but never passed from `CarriersController::show()`.
- All three `*ShowHeader.vue` files already render a `<Badge>{{ policiesCount }} policies</Badge>`
  bound to that same unfed prop.

## Approved target architecture

### 1. Authorization: `PolicyPolicy` (new)

Mirrors `ClientPolicy` minus the abilities this plan doesn't need:

```php
final class PolicyPolicy
{
    public function viewAny(User $user): bool { return $user->organization_id !== null; }
    public function view(User $user, Policy $policy): bool { return $policy->organization_id === $user->organization_id; }
    public function create(User $user): bool { return $user->organization_id !== null; }
    public function update(User $user, Policy $policy): bool { return $policy->organization_id === $user->organization_id; }
}
```

No `delete`/`archive`/`unarchive` methods — none of those abilities are used (locked decisions 1–2).
Register with `Gate::policy(Policy::class, PolicyPolicy::class)` in
`AppServiceProvider::registerPolicies()`. `DocumentPolicy`/`NotePolicy` need no changes — they
already accept any `Documentable`/`Notable`, and `Policy` already implements both.

### 2. Routes (new `routes/policies.php`, required from `web.php`)

```php
Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('policies', [PoliciesController::class, 'index'])->name('policies.index');
    Route::get('policies/create', [PoliciesController::class, 'create'])->name('policies.create');
    Route::post('policies', [PoliciesController::class, 'store'])->name('policies.store');
    Route::get('policies/export', PoliciesExcelExportController::class)->name('policies.export');
    Route::get('policies/{policy:slug}', [PoliciesController::class, 'show'])->name('policies.show');
    Route::get('policies/{policy:slug}/edit', [PoliciesController::class, 'edit'])->name('policies.edit');
    Route::patch('policies/{policy:slug}', [PoliciesController::class, 'update'])->name('policies.update');
    Route::get('policies/{policy:slug}/export', PoliciesPdfExportController::class)->name('policies.export-pdf');
});
```

No `archive`/`unarchive`/`destroy`/`notify` routes (locked decisions 1–2). Excel (list) and PDF
(single-record) export are included by direct convention match with Client/Carrier/Agent — the
design's list-level "Export" button and the show page's header "Download" button are the evidence
for each.

`routes/documents.php` and `routes/notes.php` each gain two lines mirroring the existing
`clients.documents.*`/`clients.notes.*` pair, using new `PolicyDocumentsController`/
`PolicyNotesController` classes:

```php
Route::get('policies/{policy:slug}/documents', [PolicyDocumentsController::class, 'index'])->name('policies.documents.index');
Route::post('policies/{policy:slug}/documents', [PolicyDocumentsController::class, 'store'])->name('policies.documents.store');
```

(same shape for notes).

`routes/clients.php`, `routes/agents.php`, `routes/carriers.php` each gain one read-only route to
feed the already-scaffolded Policies tab:

```php
Route::get('clients/{client:slug}/policies', [ClientPoliciesController::class, 'index'])->name('clients.policies.index');
Route::get('agents/{agent:slug}/policies', [AgentPoliciesController::class, 'index'])->name('agents.policies.index');
Route::get('carriers/{carrier:slug}/policies', [CarrierPoliciesController::class, 'index'])->name('carriers.policies.index');
```

### 3. Controllers (new, `app/Http/Controllers/Policies/`)

- `PoliciesController` — `index/create/store/show/edit/update`, shaped exactly like
  `ClientsController` minus `destroy`. `#[Authorize]` per action against `Policy::class`/`'policy'`.
- `PoliciesExcelExportController`, `PoliciesPdfExportController` — invokable, mirror
  `ClientsExcelExportController`/`ClientsPdfExportController`.
- `PolicyDocumentsController`, `PolicyNotesController` — mirror `ClientDocumentsController`/
  `ClientNotesController` exactly (same `#[Authorize('viewAny'|'create', [Document::class, 'policy'])]`
  shape).
- `ClientPoliciesController`, `AgentPoliciesController`, `CarrierPoliciesController` — `index`
  only, each authorizing `viewAny` on `Policy::class` and querying `Policy` filtered by the parent's
  FK (`client_id`/`agent_id`/`carrier_id`). No separate authorization check on the parent entity
  itself is needed beyond its own `{model}:slug` route binding — `BelongsToCurrentOrganization`'s
  global scope already prevents resolving another organization's `Client`/`Agent`/`Carrier`
  through route-model binding, matching how `ClientDocumentsController` behaves today.

### 4. Requests (new, `app/Http/Requests/Policies/`)

- `IndexPolicyRequest` — `search`, `status` (enum), `type` (enum), `class` (array of `PolicyClass`),
  `carrier_id`, `source` (enum), `effective_from`/`effective_to` (date range), `amount_min`/
  `amount_max` (numeric range), `sort`/`direction` — mirrors `IndexClientRequest`'s
  `prepareForValidation()` normalization pattern.
- `StorePolicyRequest` — shared fields (`policy_number` nullable — auto-generated when blank per
  the design's helper text; `class`, `type`, `client_id`, `carrier_id`, `agent_id` nullable,
  `effective_date`, `expiry_date` after-or-equal-effective, `premium_amount`, `discount_amount`
  nullable, `status` enum default Active, `source` enum) **plus** a class-dispatched block of
  detail-table fields, keyed off the submitted `class` value (same `Rule::requiredIf()`/
  `prohibitedIf()` shape `StoreClientRequest` uses for `client_type`) **plus** a conditional
  `insureds` array (`Rule::requiredIf($type === Group)`), one rule-set per member (full_name,
  relationship, date_of_birth, gender, medical_notes nullable) — **no `member_code` input field**
  (server-generated).
- `UpdatePolicyRequest` — same shape as Store, plus each `insureds.*` entry carries an optional
  `id`: present + matching an existing `PolicyInsured` → update in place; present but no longer
  submitted → removed; absent → created (gets the next sequential `member_code`). This
  create/update/delete "sync" is a new pattern for this codebase (existing sub-resources like
  `CarrierBranch` use fully separate store/update/destroy endpoints instead) — flagged because
  it's more involved than anything `UpdateClientAction`/`UpdateCarrierAction` currently do, not
  because a viable alternative remains open; locked decision 4 requires it.
- Cross-entity index routes (`ClientPoliciesController`, etc.) reuse `IndexPolicyRequest` or a
  narrower variant — left as an implementation detail.

### 5. Actions (new, `app/Actions/Policies/`)

- `CreatePolicyAction` — one DB transaction: creates the `Policy` row (auto-generates
  `policy_number` when blank), creates the one matching detail row for `$policy->class`, and — when
  `type` is Group — creates `PolicyInsured` rows with sequentially generated `member_code`s
  (`MBR-001`, `MBR-002`, …, scoped per policy). Sets `created_by`.
- `UpdatePolicyAction` — same transaction shape: updates `Policy` + its detail row, and syncs
  `PolicyInsured` rows per the create/update/delete rule above. Sets `updated_by`.
- `ExportPoliciesToExcelAction`, `ExportPolicyToPdfAction` — mirror the Client/Carrier equivalents.

### 6. Resources (new, `app/Http/Resources/`)

- `PolicyResource` — flat shared fields (mirroring `ClientResource`'s raw+formatted+label
  convention) plus: `client`/`carrier`/`agent` as minimal nested identifiers (id/slug/name),
  `net_premium` computed server-side (`premium_amount - discount_amount`, matching the domain
  plan's "computed, not stored" decision), the one populated class-detail sub-resource exposed
  conditionally on `class` (`'details' => $this->when(...)`), and
  `'insureds' => PolicyInsuredResource::collection($this->whenLoaded('insureds'))`.
- One thin resource per detail model (`PolicyMedicalDetailsResource`, etc.), flat shape mirroring
  `CarrierBranchResource`.
- `PolicyInsuredResource` — id, member_code, full_name, relationship, date_of_birth (+formatted),
  age, gender (+label), medical_notes, status.

### 7. Filters & Sorts (new)

- `PolicyFilter extends QueryFilter` — `search` (policy_number + related client/carrier name),
  `status`, `type`, `class` (whereIn, multi-select), `carrierId`, `source`, `effectiveFrom`/
  `effectiveTo`, `amountMin`/`amountMax` — one method per `PoliciesFiltersDrawer` field in the
  design. Exact `search`/company-filter join mechanics (whereHas vs a denormalized column) are an
  open implementation detail, matching how `ClientFilter::search()` was written for its own
  entity.
- `PolicySort extends Sort` — `policyNumber`, `client`, `effectiveDate`, `amount` (premium_amount),
  `status`; default sort left as an open detail (candidates: `effective_date desc` or
  `created_at desc`).

### 8. Vue frontend (new, `resources/js/pages/Policies/`)

- `Index.vue` + `partials/PoliciesTable.vue` + `partials/FiltersDrawer.vue` — columns/filters per
  `_design/policies-screen.jsx`; row action menu reduced to a single **Edit** item (locked decision
  1); row click still navigates to Show, matching the existing Clients/Carriers table convention;
  pagination footer matches the house convention exactly.
- `Create.vue` + `Edit.vue` sharing `partials/PolicyForm.vue` — a two-step flow within one page
  (client-side step state, no numbered-badge stepper UI per `_design/CLAUDE.md`'s form
  convention): step 1 is type + class picker + parties + status/origin, step 2 is the
  class-specific coverage form. The exact component boundary between the two steps (one component
  with internal state vs. two sub-components) is an open implementation detail. `Edit.vue` reuses
  the same class-specific section components (class is fixed/non-editable once created) and
  additionally exposes the covered-members section for add/update/remove (locked decision 4) — the
  Members section does **not** appear in the Show page's Members tab, which stays read-only per
  the design.
- `Show.vue` + `partials/PolicyDetailShell.vue` (tabs: Overview / Members [Group-only, read-only] /
  Settlements ["coming soon" placeholder, locked decision 3] / Documents / Notes, `href`-based like
  `ClientDetailShell`) + `partials/PolicyShowHeader.vue` (Edit button only — no Freeze/Cancel/
  Delete/Notify, locked decisions 1–2) + per-section card partials for Overview (Coverage/Parties/
  Period/Financials/Status, plus the one matching class-detail card).
- `resources/js/pages/PolicyDocuments/Index.vue`, `resources/js/pages/PolicyNotes/Index.vue` —
  mirror `ClientDocuments/Index.vue`/`ClientNotes/Index.vue` exactly.
- `partials/policy.ts` — typed interface mirroring the frontend-used subset of `PolicyResource`.

### 9. Cross-entity integration (connects this plan to already-scaffolded UI)

- Add `policies(): HasMany` to `Client`, `Carrier`, and `Agent` models (new relation methods only —
  no migration, the FKs already exist on `policies`).
- `ClientsController::show()`, `AgentsController::show()`, `CarriersController::show()` each start
  passing a real `policiesCount` (`$model->policies()->count()`) instead of a hardcoded/absent
  value.
- `ClientDetailShell.vue`: `<Tab href="#">Policies</Tab>` → `<Tab :href="clientsPoliciesIndex(client.slug)">Policies</Tab>`;
  `Clients/Show.vue` drops its hardcoded `const policiesCount = 0` in favor of the real prop.
  Same pattern for `AgentDetailShell.vue` (fixing its dead `href="#"`) and — additionally
  converting its client-side `ref` tab to a real `href` tab, matching Client/Agent — for
  `CarrierDetailShell.vue`.
- `ClientPoliciesCard.vue` (Client show page, Overview tab) starts rendering real policies (or a
  real empty state) and enables its currently-`disabled` "Add policy" button, linking to
  `policies.create` (pre-filled with the client, matching how `_design/new-policy.jsx`'s Parties
  section pre-fills a selected client).
- `PoliciesRenewalCard.vue` (Agent show page) starts rendering the agent's policies expiring soon,
  using the same "Renewing" read-time-derived concept the domain-model plan already established
  (derived from `expiry_date` proximity, not a stored state).
- The three new `{Client,Agent,Carrier}PoliciesController@index` pages reuse the same
  table/columns as `Policies/Index.vue`, scoped by the parent's FK — exact componentization
  (shared Vue partial vs. three thin per-entity pages) is an open implementation detail.

## Locked decisions (recap)

All explicitly approved by the user in conversation:

1. **No delete action for Policy in this pass.** The list row menu and the show page expose only
   **Edit** — no Delete, no Archive/Unarchive. The `soft_deletes` column on `policies` stays for
   data-retention/audit purposes only; nothing in the UI or routes exposes it.
2. **No Freeze/Cancel/Reactivate lifecycle actions in this pass**, despite the design mocking up
   "Freeze"/"Cancel policy" in the row menu. `status` is set at creation via the regular
   create/update form (`NpStatusSection`'s Active/Cancelled/Frozen field), not through dedicated
   single-purpose transition controllers — there is no Archive/Unarchive-style pair for Policy.
3. **Settlements/payment-tracking UI gets a "coming soon" placeholder, not a build-out.** The
   create/edit form never includes the design's "Payment method" field (Fully paid/Settlements +
   instalments) — that's excluded entirely, matching the already-approved domain-model scope. The
   show page keeps an inert Settlements tab (no "Record payment" button, no paid-total stats),
   following the same "coming soon" treatment `CarrierDetailShell.vue` already uses today for its
   own placeholder Policies tab.
4. **Covered members (`policy_insureds`) are captured at creation and are also editable afterward**
   via the Edit-policy form (add/update/remove), going beyond what the design's read-only Members
   tab shows. The Show page's Members tab itself stays read-only (view/filter/search/export) —
   editing happens only through Edit.

## What must remain true / unchanged

- No changes to the `policies`, `policy_*_details`, or `policy_insureds` schema — this plan is
  HTTP/Vue/authorization only, built entirely on the existing migrations and models.
- No changes to `Client`, `Carrier`, `Agent` migrations/tables — only new `policies()` relation
  methods on those models (§9).
- `Policy`'s existing trait/contract composition (`BelongsToCurrentOrganization`, `Filterable`,
  `Sortable`, `HasSlug`, `SoftDeletes`, `Documentable`, `Notable`) is reused as-is, matching how
  `Client`'s equivalents are consumed by its controller/request/policy stack.
- No payment/settlement, renewal, or commission functionality is built — only an inert placeholder
  where the design shows it (locked decision 3).

## Explicitly out of scope for this pass

Settlement/payment recording, policy lifecycle status-transition actions (Freeze/Cancel/Reactivate)
as dedicated endpoints, hard delete of policies, Notify-on-policy, renewal workflows, commission
tracking — all deferred, per the locked decisions above and the already-approved domain-model plan.

## Evidence index

- `app/Models/Policy.php` + six detail models + `PolicyInsured.php`, `app/Enums/Policy*.php` —
  domain model this plan builds on
- `app/Providers/AppServiceProvider.php` — confirms `Policy` morph-mapped but not
  `Gate::policy()`-registered
- `app/Http/Controllers/Clients/*`, `app/Http/Requests/Clients/*`, `app/Policies/ClientPolicy.php`,
  `app/Filters/ClientFilter.php`, `app/Sorts/ClientSort.php`, `app/Http/Resources/ClientResource.php`,
  `app/Http/Controllers/Carriers/CarriersBranchController.php`,
  `app/Http/Resources/CarrierBranchResource.php` — full-stack conventions this plan mirrors
- `app/Http/Controllers/Clients/ClientDocumentsController.php`,
  `app/Http/Controllers/Clients/ClientNotesController.php` — the pattern `PolicyDocumentsController`/
  `PolicyNotesController` replicate
- `resources/js/pages/Clients/{Create,Show}.vue`, `resources/js/pages/Clients/partials/{ClientForm,ClientDetailShell,client}.{vue,ts}`,
  `resources/js/pages/Carriers/partials/CarrierDetailShell.vue`,
  `resources/js/pages/Agents/partials/AgentDetailShell.vue`,
  `resources/js/pages/Clients/partials/ClientPoliciesCard.vue`,
  `resources/js/pages/Agents/partials/PoliciesRenewalCard.vue`,
  `resources/js/pages/{Clients,Agents,Carriers}/partials/*ShowHeader.vue` — Vue conventions and the
  already-scaffolded, currently-inert integration points this plan connects
- `tests/Feature/Http/{Clients,Carriers,Documents,Notes}/*.php`,
  `tests/Feature/Actions/{Clients,Carriers}/*.php`, `tests/Feature/Policies/ClientPolicyTest.php`,
  `tests/Feature/Filters/ClientFilterTest.php`, `tests/Feature/Sorts/ClientSortTest.php`,
  `tests/Feature/Exports/ClientsExportTest.php` — testing conventions this plan's issues will
  follow
- `_design/policies-screen.jsx` — list columns, filters drawer, row action menu (superseded by
  locked decisions 1–2)
- `_design/new-policy.jsx` — two-screen create flow, `NpFamilySection` (no member_code field),
  `NpFinancialsSection` (Payment method field, excluded per locked decision 3)
- `_design/policy-detail.jsx` — show page tabs (conditional Members tab, Settlements tab), header
  actions, read-only `PdMembersTab`
- Prior approved plan (git history `c258d30`, "Add Policies domain model and database design
  plan") — schema/domain-model decisions this plan treats as settled context, not re-litigated
