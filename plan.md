# Policies Domain Model & Database Design

> **Status: source of truth for the subsequent `plan-it` pass.** Implementation details (exact
> column widths, index tuning, factory shapes, etc.) still need verifying against the codebase
> when individual issues are built.

## Summary (plain language)

Orbit is an insurance-broker SaaS with `Client`, `Carrier`, `CarrierBranch`, and `Agent` already
built. Policies — the thing a broker actually sells — don't exist yet as a domain concept in the
codebase, only as inert "no policies yet" UI placeholders. This plan establishes the foundation:
one shared `policies` table holding everything true of every policy (who's insured, which
carrier, dates, premium, status), plus one dedicated detail table per insurance class (Medical,
Automotive, Expat, Fire, Life, Travel) holding only what's specific to that class. A generic
`policy_insureds` table captures covered dependents/members for group policies, starting with
Medical.

Explicitly **out of scope** for this pass: payment/settlement tracking, renewals, commissions,
reminders. The schema leaves room for these later but does not model them now.

This is a **schema and domain-model foundation only** — no controllers, requests, actions, Vue
pages, or tests are part of this plan. That work is scoped and sequenced by `plan-it` afterward.

## Current state (verified)

- No `Policy` model, migration, enum, filter, or sort exists anywhere in the codebase. The only
  references to "policies" are inert UI placeholders: `resources/js/pages/Clients/partials/ClientPoliciesCard.vue`
  and `resources/js/pages/Agents/partials/PoliciesRenewalCard.vue` (both render "No policies yet").
- Existing entity conventions, established by `Client` (`app/Models/Client.php`), `Carrier`
  (`app/Models/Carrier.php`), `CarrierBranch` (`app/Models/CarrierBranch.php`), and `Agent`
  (`app/Models/Agent.php`):
  - `final class` models with PHPDoc `@property` blocks and a `#[Fillable([...])]` attribute
    (not `$fillable`).
  - Org-scoped models use `BelongsToCurrentOrganization` (adds a global scope + `organization()`
    relation) and are unique per `(organization_id, slug)`; `HasSlug` makes `slug` the route key.
  - `Filterable` + `Sortable` traits delegate to dedicated `App\Filters\{Model}Filter` and
    `App\Sorts\{Model}Sort` classes (see `app/Filters/*.php`, `app/Sorts/*.php`).
  - Status/type/enum-like columns are plain strings in the DB, cast to backed string PHP enums in
    `app/Enums/*.php` (e.g. `CarrierStatus`, `ClientType`, `LeadSource`).
  - `created_by`/`updated_by` (`restrictOnDelete`/`nullOnDelete` to `users`), `timestamps()`,
    `softDeletes()` are standard on primary org-scoped entities (`clients`, `carriers`, `agents`).
  - Cross-cutting concerns are polymorphic and reusable: `Document` (`documentable_type`/`_id`,
    via `HasDocuments`/`Documentable`) and `Note` (`notable_type`/`_id`, via `HasNotes`/`Notable`)
    already attach to `Client`; both are ready to attach to any new model that implements the
    contract.
  - `CarrierBranch` is the one existing 1:many "detail" child table in the app (`carrier_id` FK,
    `cascadeOnDelete()`), naming pattern `{parent}_{children}`.
- Design files at `_design/` (gitignored, downloaded from Claude Design; per-user convention
  documented in `_design/CLAUDE.md`) define the actual Policies UI:
  - `_design/policies-screen.jsx` — list page. Columns: Policy (id + class tile) / Type · Class /
    Client / Effective → Expiry / Amount / Status. Filters: search, status, type (Single/Group),
    class (multi), company, lead source, effective-date range, amount range.
  - `_design/new-policy.jsx` — create flow. A class picker (`POLICY_CLASSES` map: Medical,
    Automotive, Expat, Life, Fire, Travel, each with its own `subs` list) followed by one form per
    class, each built from shared sections (Coverage header, Parties, Coverage period, Financials,
    Status & origin) plus a class-specific section.
  - `_design/policy-detail.jsx` — show page. Tabs: Overview, Members (group policies only),
    Settlements, Documents, Notes.
  - `resources/js/pages/design-foundation/card/snippets/policies.md` — a "Policies" card snippet
    mixing lines of business (Medicare Advantage, Auto, Homeowners), confirming policies span both
    health/life-style and P&C-style insurance under one list.

## Approved target architecture

### 1. Table structure: shared table + one detail table per class (locked decision)

`policies` holds every field that is true of every policy, regardless of class. Each of the six
classes gets its own 1:1 detail table, joined by `policy_id`, holding only that class's fields.
This was chosen over a `details` JSON column (loses typing/constraints, breaks from this
codebase's typed-column-plus-enum convention) and over one wide `policies` table with nullable
per-class columns (grows sparse and unbounded as classes/fields are added, and can't express
"required for Automotive, irrelevant for Life" with `NOT NULL`).

Naming follows the existing `{parent}_{child}` pattern (`carrier_branches`):

- `policies` (shared)
- `policy_medical_details`
- `policy_automotive_details`
- `policy_expat_details`
- `policy_fire_details`
- `policy_life_details`
- `policy_travel_details`

Each detail table: `id`, `policy_id` (unique FK, `cascadeOnDelete()` — a detail row cannot outlive
its policy), the class-specific columns, `timestamps()`. No independent `organization_id`,
`created_by`, `slug`, or soft deletes on detail tables — they are pure extensions of their policy
row and are scoped/audited/deleted through it.

At the Eloquent layer, `Policy` gets one explicit, nullable `hasOne` relation per class
(`medicalDetails()`, `automotiveDetails()`, `expatDetails()`, `fireDetails()`, `lifeDetails()`,
`travelDetails()`) — only the one matching `policies.class` is ever populated. This is plain and
explicit rather than a magic single relation, consistent with this codebase avoiding abstractions
beyond what's needed. Each detail table gets its own thin, final Eloquent model
(`PolicyMedicalDetails`, etc.) with a `belongsTo(Policy::class)` back-reference.

**Derived constraint:** adding a 7th policy class later means one new migration, one new detail
model, one new `hasOne` relation on `Policy`, and one new case in `PolicyClass` — the shared table
and every other class are untouched.

### 2. `policies` (shared) columns

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `organization_id` | FK → organizations | `restrictOnDelete()`, via `BelongsToCurrentOrganization` |
| `slug` | string | route key via `HasSlug`, unique per `(organization_id, slug)` — internal routing identifier, distinct from the human-facing policy number |
| `policy_number` | string | the carrier-facing reference shown as `POL-XXXX` in the design (`_design/new-policy.jsx`: *"Carrier-assigned reference. Auto-generated if blank."*); unique per `(organization_id, policy_number)` |
| `class` | string, cast to `PolicyClass` enum | Medical / Automotive / Expat / Fire / Life / Travel |
| `subclass` | string | plain string, not an enum column — see "Derived constraint" below |
| `type` | string, cast to `PolicyType` enum | Single / Group — cross-cuts every class (`_design/policies-screen.jsx` `POLICY_CLASSES`/`SAMPLE_POLICIES`; `_design/new-policy.jsx` `NpCoverageHeaderSection`) |
| `client_id` | FK → clients | `restrictOnDelete()` — the primary insured/policyholder. Exactly one client per policy; dependents/employees are captured in `policy_insureds`, not as additional `Client` rows (per `_design/new-policy.jsx` `NpPartiesSection`, only one client picker exists) |
| `carrier_id` | FK → carriers | `restrictOnDelete()` — the underwriting insurance company (`NpPartiesSection` "Insurance company" picker) |
| `agent_id` | FK → agents, nullable | `nullOnDelete()` — optional single "writing agent" (`NpPartiesSection`: *"Sub-producer who placed this policy"*, `optional`). No split-commission or multi-agent modeling — deferred with the rest of the commissions workflow |
| `effective_date` | date | `NpPeriodSection` |
| `expiry_date` | date | `NpPeriodSection` |
| `bound_at` | date, nullable | date the policy was bound, shown on the show page (`SAMPLE_POLICY.bound`) but not currently collected on the create form — nullable until/unless the create flow captures it explicitly |
| `premium_amount` | decimal | gross premium (`NpFinancialsSection` "Premium amount") |
| `discount_amount` | decimal, default 0 | (`NpFinancialsSection` "Discount") — net premium (`premium_amount - discount_amount`) is computed, not stored |
| `status` | string, cast to `PolicyStatus` enum | Active / Cancelled / Frozen only (typo "Freezed" in the design corrected to `Frozen`) — see decision 4 |
| `source` | string, cast to `PolicySource` enum | Owner / Client / Friend / Agent — **a new enum, distinct from the existing `App\Enums\LeadSource`** used by `Client` (that enum's values — Referral/Website/SocialMedia/Partner/WalkIn/ColdCall — describe how a *client* was acquired; `PolicySource` describes who originated a specific *sale*, `_design/new-policy.jsx` `NpStatusSection`) |
| `created_by` / `updated_by` | FK → users | matches `clients`/`carriers`/`agents` |
| `timestamps()` / `softDeletes()` | | matches `clients`/`carriers`/`agents` |

Indexes mirror `clients`/`carriers`/`agents`: unique `(organization_id, slug)`, unique
`(organization_id, policy_number)`, plus `organization_id`, `(organization_id, status)`, and
`(organization_id, class)` for the list page's class filter.

**Derived constraint (subclass modeling):** the list page filters and sorts on class + subclass
across *all* policies at once (`_design/policies-screen.jsx` `PoliciesFiltersDrawer`, "Class"
filter), which requires `subclass` to live on the shared `policies` table rather than inside a
per-class detail table — otherwise listing/filtering would require joining all six detail tables.
Because each class has its own distinct subclass vocabulary (some values collide across classes
with different meaning, e.g. "Worldwide" appears under both Expat and Travel), `subclass` is a
plain string column validated against the selected class's allowed list at the application layer
(e.g. a `PolicyClass::subclasses(): array` method), not a native DB enum or a single global PHP
enum spanning all class/subclass combinations.

### 3. Class-specific detail tables

Each holds only the fields shown in that class's `_design/new-policy.jsx` section. All are
nullable/optional exactly where the design marks them `optional`; required fields are `NOT NULL`.

- **`policy_medical_details`** (from `NpMedicalSection` + `NpInsuredProfileSection`): coverage
  scope (In / In-Out), class tier (Class A / Class B), co-insurance (bool + share % nullable),
  guaranteed renewability (bool). Single-only insured profile fields (full name, date of birth,
  gender, smoker bool, medical history nullable) live here too, populated only when `type` =
  Single; when `type` = Group these stay null and the insureds live in `policy_insureds` instead.
- **`policy_automotive_details`** (from `NpAutomotiveSection`): plate number, make, model, year,
  VIN/chassis (nullable), color (nullable), vehicle valuation (nullable, decimal) and valuation
  source (nullable) — the latter two only populated for the "All risk" subclass, per the design's
  conditional section.
- **`policy_expat_details`** (from `NpExpatSection`): coverage zone (In / In-Out), travel scope
  (nullable), expat full name, gender, nationality, date of birth, phone, country of residence,
  visa/residency expiry (nullable).
- **`policy_fire_details`** (from `NpFireSection`): property type, floor area (m²), year built
  (nullable), address (street, building/floor nullable, city, state/governorate, country — same
  shape as the existing address fields on `clients`/`carrier_branches`), sum insured (decimal).
- **`policy_life_details`** (from `NpLifeSection`): sum assured (decimal), term, smoker status,
  beneficiaries — kept as a **plain text field** for this pass (locked decision below), matching
  the current free-text UI (*"Comma-separated. Shares sum to 100%."*).
- **`policy_travel_details`** (from `NpTravelSection`): destination, trip start, trip end,
  travelers — also kept as a **plain text field** for the same reason, coverage tier
  (Basic/Standard/Premium).

**Open implementation detail (not resolved by this plan):** `_design/new-policy.jsx`'s Automotive
form shows only a single-vehicle form even when subclass is "Fleet" (a Group-type subclass) — the
design has not yet worked out multi-vehicle capture. This plan keeps `policy_automotive_details`
1:1 with `policies` as the design currently shows; if/when Fleet gets real multi-vehicle UI, that
becomes its own follow-up decision (e.g. a `policy_vehicles` child table), not resolved here since
no viable choice today would change the schema for the classes the design has actually specified.

### 4. Covered members: `policy_insureds` (locked decision)

A single generic table, not Medical-specific, so it can be adopted by other classes later without
a new table:

- `id`, `policy_id` (FK, `cascadeOnDelete()`)
- `member_code` (string, e.g. `MBR-001`, unique per `policy_id`, shown in `_design/policy-detail.jsx`
  `SAMPLE_POLICY.members`)
- `full_name`
- `relationship` (string — Employee / Spouse / Child / Parent, per
  `_design/policy-detail.jsx` `REL_COLOR_PD` and `_design/new-policy.jsx` `NpFamilySection`)
- `date_of_birth`
- `gender` (nullable — present in the create-form rows, absent from the show-page census rows)
- `medical_notes` (nullable text — the create form's "Medical history" field; generic enough to
  stay unused/null for non-medical adopters)
- `status` (string — Active / Pending, per the show-page census)
- `timestamps()`

Today only Medical/Group policies populate this table (`type` = Group). Life's beneficiaries and
Travel's travelers stay as free-text fields on their own detail tables (locked decision below) —
this table is structurally ready to receive them later without a migration, but nothing in this
pass wires that up.

### 5. Documents & Notes (derived constraint)

`Policy` implements the existing `Documentable` and `Notable` contracts and uses the existing
`HasDocuments`/`HasNotes` traits — exactly like `Client` does today. No new polymorphic
infrastructure is needed; `_design/policy-detail.jsx`'s Documents and Notes tabs are a direct
reuse of the pattern already serving `ClientDocuments`/`ClientNotes`.

## Locked decisions (recap)

All explicitly approved by the user in conversation:

1. **Divergence model:** shared `policies` table + one 1:1 detail table per class, over a wide
   nullable-column table or a JSON attributes column. (§1)
2. **Lifecycle scope:** `PolicyStatus` = Active / Cancelled / Frozen only, bound-only for this
   pass. The design's 5-stage lifecycle chip (Quoted → Bound → Active → Renewing → Renewed) is not
   stored state — Renewing/Renewed are derived from `expiry_date` proximity at read time, and
   Quoted/Bound remain a future decision if a real quoting workflow (a policy record that exists
   before it's bound) gets built later. Today's create flow always produces an already-bound,
   Active-by-default policy. (§2)
3. **Beneficiaries/travelers:** stay free text on `policy_life_details`/`policy_travel_details`
   for this pass, matching the current UI exactly, rather than becoming structured rows in
   `policy_insureds`. Nothing here blocks structuring them later. (§3)
4. **Covered members:** one generic `policy_insureds` table now, reusable beyond Medical/Group
   later. (§4)
5. **Relationships:** single optional `agent_id`, no `carrier_branch_id` — matches the create-form
   design exactly; no commission/split modeling. (§2 table)

## What must remain true / unchanged

- Every new model (`Policy`, the six detail models, `PolicyInsured`) is `final class`, uses
  `#[Fillable([...])]`, and follows the existing PHPDoc `@property` block convention.
- `Policy` is org-scoped via `BelongsToCurrentOrganization`, `Filterable`, `Sortable`, `HasSlug`,
  and `SoftDeletes` — identical trait composition to `Client`/`Carrier`/`Agent`.
- No changes to `Client`, `Carrier`, `CarrierBranch`, `Agent`, `Document`, `Note`, or their
  existing migrations/tables.
- No payment, settlement, renewal, commission, or reminder tables or columns are introduced. The
  `premium_amount`/`discount_amount` fields on `policies` represent the policy's own underwriting
  economics (what it costs), not payment/settlement tracking (whether/how it's been paid) — that
  boundary is intentional so the deferred settlements work has a clean, additive extension point
  (a future `policy_settlements` table referencing `policy_id`) without touching this schema.

## Explicitly out of scope for this pass

Payment/settlement tracking, renewal workflows, commission tracking/splits, reminders — all
deferred. This plan's tables leave room for them (e.g. `policy_id` as a stable FK target) but
define none of them.

## Evidence index

- `app/Models/Client.php`, `app/Models/Carrier.php`, `app/Models/CarrierBranch.php`,
  `app/Models/Agent.php`, `app/Models/Organization.php` — model conventions
- `app/Models/Document.php`, `app/Models/Note.php`, `app/Models/Concerns/HasDocuments.php`,
  `app/Models/Concerns/HasNotes.php`, `app/Models/Concerns/BelongsToCurrentOrganization.php`,
  `app/Models/Concerns/HasSlug.php`, `app/Models/Concerns/Filterable.php`,
  `app/Models/Concerns/Sortable.php` — reusable trait/contract conventions
- `database/migrations/2026_06_22_184351_create_clients_table.php`,
  `2026_08_01_210045_create_carriers_table.php`,
  `2026_08_01_210046_create_carrier_branches_table.php`,
  `2026_08_04_165355_create_agents_table.php` — schema conventions
- `app/Enums/CarrierStatus.php`, `app/Enums/ClientType.php`, `app/Enums/LeadSource.php` — enum
  conventions, and the basis for distinguishing the new `PolicySource` enum from the existing
  `LeadSource`
- `app/Filters/*.php`, `app/Sorts/*.php` — filter/sort conventions `Policy` will follow
- `_design/policies-screen.jsx` — list page columns, filters, `POLICY_CLASSES`/`SAMPLE_POLICIES`
- `_design/new-policy.jsx` — create-flow sections: `NpCoverageHeaderSection`,
  `NpPartiesSection`, `NpPeriodSection`, `NpFinancialsSection`, `NpStatusSection`,
  `NpMedicalSection`/`NpInsuredProfileSection`/`NpFamilySection`, `NpAutomotiveSection`,
  `NpExpatSection`, `NpFireSection`, `NpLifeSection`, `NpTravelSection`
- `_design/policy-detail.jsx` — show-page tabs, `SAMPLE_POLICY` shape, `REL_COLOR_PD`,
  `PolicyLifecycle`
- `resources/js/pages/Clients/partials/ClientPoliciesCard.vue`,
  `resources/js/pages/Agents/partials/PoliciesRenewalCard.vue` — confirm no real Policy concept
  exists in the app today
