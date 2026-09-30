# Policies polish pass — Phase 26 follow-up

> **Handoff:** this section is the source of truth for the subsequent `plan-it` pass on the Policies
> polish work. It consolidates a completed Policies product/UI/domain audit and the owner's final
> decisions from the 13-outcome review. Implementation details must still be verified against the
> codebase when individual issues are built.

## Summary

Phase 26 (Policies HTTP & Frontend) is implemented, and the owner has manually reviewed and validated it.
An independent audit, followed by an owner decision review, settled a set of corrections and small
domain rules. None of them redesigns Policies. The approved outcomes:

1. Policy dates can be set for real coverage periods (years in the future).
2. Policy detail pages share one class-aware shell and header, so Documents and Notes work for every
   class.
3. Policy numbers are user-entered, unique per organization; automatic generation is removed.
4. Policy values are consistent with the policy's own dates and premium.
5. User-facing "Amount" means net premium on the index filter and the export.
6. Class form limits and server rules agree.
7. Policy countries are limited to configured operating markets.
8. Policy forms offer, and the server accepts, only active parties (an existing archived party may be
   kept on Edit), ordered alphabetically.
9. A Medical type change that discards data asks for confirmation, and `member_code` is removed.
10. The New Policy flow keeps the user's choices when going back.
11. *(Dropped — gender defaults and covered-member gender behavior stay as they are.)*
12. Validation messages use readable field names.
13. The client's pages reflect that client's policies.

The current, manually validated Policies UI is the baseline. Older design files don't justify changing
it (see "Evidence precedence").

**Claim labels used throughout:**
- **Fact:** verified current state.
- **Locked:** approved by the owner.
- **Derived:** follows necessarily from the stated premises.
- **Open:** an implementation choice left for build time, where every option preserves the locked outcome.

---

## Evidence precedence (Locked)

For this polish pass, sources rank as follows:

1. The owner's explicit decisions and manually validated current behavior.
2. Locked product/domain decisions from earlier approved Policies plans and issues, unless this plan
   explicitly supersedes them (outcomes 3 and 9 do).
3. Current implementation and tests.
4. `_design/` files, only as supporting evidence where intended behavior is still genuinely unresolved.

Where the current UI differs from `_design/` and the owner has validated the current behavior, keep the
current behavior, except for an independent defect, domain inconsistency, broken interaction, or an
explicit decision to change it.

**Derived** (premise: the rule above):
- Validated index, filter and show-page presentation isn't redesigned to match `_design/`.
- Validated form controls aren't swapped for the design's controls.
- `_design/` is used as evidence only for the client overview Policies card (outcome 13), whose current
  implementation is incomplete.

## Domain terminology (Locked)

- **Premium** (`premium_amount`): the original, gross monetary amount of the policy.
- **Discount** (`discount_amount`): an absolute monetary amount, not a percentage. A null discount counts
  as zero.
- **Net premium**: Premium minus Discount; the amount actually paid. It is derived, not stored.
  `PolicyResource` already exposes it as `net_premium` (**Fact**).
- Discount can't exceed Premium, so net premium is never negative (outcome 4).

## Fixture and seed data (Locked)

When this pass tightens validation, pre-production factories, seeders, test payload builders
(`tests/Support/PolicyPayload.php`) and local data are inspected, and invalid generated data is
corrected. No compatibility exceptions are added to rules to accommodate bad fixtures.

---

## Outcomes

### 1. Policy dates can be set for real coverage periods

- **Fact:** `resources/js/components/ui/date-input/DateInput.vue` builds its year list from `startYear`
  (1920) to `endYear ?? currentYear`, and no policy page passes `endYear`. The year list therefore
  stops at the current year for:
  - effective and expiry dates on Create and Edit in all six classes;
  - Travel trip start and end;
  - Expat visa expiry;
  - the Policies index filter drawer's effective-date inputs.

  A stored date with a later year has no matching option on Edit, so it looks incomplete, although its
  hidden value is still submitted.
- **Fact:** the server doesn't cap years. `app/Concerns/PolicyValidationRules.php` has
  `effective_date: required|date` and `expiry_date: required|date|after_or_equal:effective_date`.
- **Locked:** these policy date fields offer years up to the **current year + 10**, and **always include
  the year of an already-stored value**, even outside that range.
- **Locked:** the index filter drawer's effective-date **From and To** both get this range.
- **Locked:** this is a UI affordance only. It adds no server-side maximum.
- **Unchanged:** date-of-birth inputs keep their current range (future dates of birth are rejected by
  outcome 4); the `DateInput` default used by other pages (Clients, Agents).
- **Tests:** manual UI only; there's no server-side behavior to test.

### 2. Shared, class-aware Policy detail shell and header

- **Fact:** the six `Policy*DetailShell.vue` and six `Policy*ShowHeader.vue` components (under
  `resources/js/pages/Policy{Class}/partials/`) are line-for-line identical except for:
  - the class-specific Wayfinder routes they import: `show` (Overview tab), `edit` and `exportPdf`
    (header actions);
  - the header each shell imports;
  - Medical's shell only: a Members tab rendered when `policy.type === 'group'`, without checking the
    class.

  All six headers show the same fields for every class (number, status, type, "class · subclass",
  client, carrier). All six Wayfinder class modules export `show`, `edit` and `exportPdf` with the same
  shape.
- **Fact:** `PolicyDocuments/Index.vue`, `PolicyNotes/Index.vue` and `PolicyMembers/Index.vue` always
  render `PolicyMedicalDetailShell`. On a non-Medical policy, Documents and Notes therefore link
  Overview, Edit and Export to Medical routes, which return 404 via `EnsurePolicyClass`; a Group policy
  of another class also shows a Members tab whose route (`policy-class:medical`) returns 404.
- **Fact:** `PolicyDocumentsController` serializes every policy with `PolicyMedicalResource`;
  `PolicyNotesController` uses the base `PolicyResource`. The base resource, with the `client`,
  `carrier` and `agent` relations both controllers already load, carries every field the shell,
  header and Documents page read.
- **Locked:**
  - the twelve duplicated components are consolidated into one shared class-aware detail shell and one
    shared header, backed by a centralized class → route configuration (show, edit, export);
  - class-specific page content (each class's Show page body) stays separate and is passed into the
    shared shell;
  - only Medical **Group** policies show the Members tab;
  - Documents, Notes and Members render the shared shell; Documents serializes the policy with the base
    `PolicyResource`;
  - existing routes and class guards (`EnsurePolicyClass`, `policy-class:medical` on Members) are
    unchanged;
  - the pass doesn't migrate unrelated existing class maps (`Policies/partials/PoliciesTable.vue`,
    `Agents/partials/PoliciesRenewalCard.vue`, `Policies/Create.vue`) onto the new configuration unless
    an approved consumer requires it. Outcome 13 is an approved consumer.
- **Unchanged:** the tab set; the Members page resource; the Settlements placeholder (deferred).
- **Open:** where the class route configuration lives and how it's typed (`PolicyResource.class` is
  currently typed as `string` in `resources/js/types/policy.ts`).
- **Tests:** an HTTP test that the Documents and Notes pages for a non-Medical policy expose that
  policy's class and no Medical-only data. No PHP test references the shell or header components
  (**Fact**); navigation is checked manually.

### 3. Policy numbers are user-entered and unique per organization

- **Fact:** `policies.policy_number` is a non-nullable `string(50)` with
  `unique(['organization_id','policy_number'])`, a constraint that includes soft-deleted rows. The
  shared rule is `policy_number: nullable|string|max:50`, so a duplicate raises a database error (500)
  on Store or Update in any class.
- **Fact:** `CreatePolicyAction` generates the next `POL-####` when the number is blank, and all six
  class forms show the helper "Auto-generated if left blank." `CreatePolicyActionTest` covers
  generation; `PolicyPayload` sends `policy_number: null`.
- **Locked:** automatic policy-number generation is removed. The policy number is ordinary
  user-entered text, unique within an organization. The same number in another organization is valid.
- **Locked:** Form Request validation surfaces predictable uniqueness conflicts as a field error; the
  database constraint remains the authoritative guarantee.
- **Locked:** the policy number is required on Store and Update.
- **Locked:** uniqueness validation includes soft-deleted policies, so it predicts the existing database
  constraint; Update excludes the current policy.
- **Derived** (premise: the fixture rule): generation tests, the forms' auto-generation helper text and
  the payload builders' null number no longer apply.
- **Tests:** HTTP: duplicates rejected on Store and Update, including one held by a soft-deleted
  policy; the same number in another organization accepted; keeping a policy's own number accepted; a
  missing number rejected.

### 4. Policy values are consistent with the policy's own dates and premium

- **Fact:** `discount_amount` is `nullable|numeric|min:0` with no upper bound, so net premium can go
  negative.
- **Fact:** Medical `insured_date_of_birth`, `insureds.*.date_of_birth` and Expat `date_of_birth` accept
  future dates. Travel `trip_start_date`/`trip_end_date` aren't tied to the policy's effective/expiry
  dates.
- **Fact:** `PolicyTravelDetailsFactory` generates trips 1 week to ~3½ months from now, independent of
  the policy's dates; `PolicyFactory` sets effective dates within the past year and expiry one year
  later, so a generated trip can fall outside coverage.
- **Locked**, in every class on both Store and Update:
  - Discount ≤ Premium (see "Domain terminology");
  - dates of birth (Medical single insured, Medical covered members, Expat insured) can't be in the
    future;
  - a Travel trip starts on or after `effective_date` and ends on or before `expiry_date`.
- **Locked, no rule added:** the Life term stays independent of the policy dates; Expat visa expiry
  isn't tied to anything; `effective_date == expiry_date` stays valid.
- **Derived** (premises: forms bind errors by field key via `FormField :error`): each new error shows
  under its own field.
- **Derived** (premise: the fixture rule): generated Travel trips fall within the generated policy's
  coverage.
- **Tests:** HTTP per rule with boundary cases (discount equal to premium, date of birth today, trip on
  the boundary dates).

### 5. "Amount" means net premium

- **Fact:** the index Amount column (`Policies/partials/PoliciesTable.vue`) shows `net_premium`, while
  `PolicyFilter::amountMin/amountMax` and `PolicySort::amount` compare gross `premium_amount`.
- **Fact:** `PolicySort` is applied only by `PoliciesExport`; the index doesn't apply it and orders by
  `latest('effective_date')->orderBy('id')`. The export's default sort is `effective_date desc` with no
  tie-breaker, although `Policies/Index.vue` states the export matches the screen order.
- **Locked:**
  - user-facing Amount means net premium;
  - amount filtering (index and export) and the export's amount sorting use the same derived net value,
    with a null discount counting as zero;
  - the export's default order gets the same deterministic tie-breaker as the index.
- **Locked, not required:** index sort controls; a stored net-premium column.
- **Unchanged:** the Amount column display; search behavior.
- **Tests:** HTTP: filter by net on the index and the export; export amount sort by net; same-date
  policies export in index order.

### 6. Class form limits and server rules agree

- **Fact:**
  - Travel coverage tier is hard-coded to `Basic`, `Standard`, `Premium` in `PolicyTravelForm.vue`,
    while the server accepts any `string|max:20`;
  - Automotive `year` has server `max: now()->year + 1` but no form maximum;
  - Fire `year_built` has server `max: now()->year` but no form maximum.
- **Locked:**
  - Travel coverage tier is the closed set Basic/Standard/Premium, shared as one source between server
    and UI;
  - the Automotive year and Fire year-built inputs expose their existing server bounds.
- **Locked, unchanged:** Expat nationality stays free text; the Expat country stays optional and
  labelled "Country"; Expat travel scope stays required for In-Out and free text; Fire property type
  stays free text.
- **Open:** a `TravelCoverageTier` enum (matching the `MedicalClassTier` convention) or an allowed-values
  rule.
- **Tests:** HTTP: a tier outside the set is rejected and each tier is accepted. No backend tests are
  added for the unchanged year rules.

### 7. Policy countries are limited to configured operating markets

- **Fact:** `countries` has unique `iso2` and `iso3` codes. `PoliciesFireController` and
  `PoliciesExpatController` pass every country, ordered by name, to their forms. `fire.country_id` is
  `required|exists:countries,id`; `expat.country_id` is `nullable|exists:countries,id`.
- **Fact:** `fire.state_id` is required and must belong to `fire.country_id`; the form labels it
  "Governorate" and loads options from the `states.index` route. In local reference data, 50 of 250
  countries have no states.
- **Fact:** `PolicyFireDetailsFactory` and `PolicyExpatDetailsFactory` hard-code Lebanon (`LB`), and the
  Fire factory a "Beirut" state; local reference data has 8 states for `LB`.
- **Locked:**
  - pre-production operating markets are configured by ISO country codes through application config
    backed by the `MARKET_COUNTRIES` environment variable; application code reads the config, never
    `env()` directly;
  - policy country choices expose only configured markets, and server validation enforces the same
    boundary;
  - Fire keeps requiring Governorate/state, so every configured Fire market needs its state reference
    data;
  - markets are never inferred from countries having state rows;
  - this is a temporary source: future organization-level market provisioning replaces it;
  - `MARKET_COUNTRIES` holds uppercase ISO2 codes, comma-separated (e.g. `LB,AE,SA`), matching
    `countries.iso2`. The application config may normalize whitespace and casing; no other code format
    is supported;
  - `MARKET_COUNTRIES` is required configuration for any environment using Policies. An empty or
    unconfigured market set never falls back to exposing all countries;
  - existing pre-production Policy data outside the configured markets isn't grandfathered: invalid
    fixtures and local data are corrected or reset instead.
- **Locked, outside Phase 26:** whether Clients, Agents, Carriers or Users are constrained by markets.
- **Derived** (premise: the only policy country fields are `fire.country_id` and `expat.country_id`):
  both are limited to configured markets; the Expat country stays optional.
- **Derived** (premise: the fixture rule): tests exercising Fire or Expat country validation run with the
  fixtures' country configured as a market.
- **Fact:** no existing config file establishes a convention for failing on missing required
  configuration.
- **Open:** the config file and key; how the value is parsed; how a missing or empty market set fails or
  is reported (it must not expose all countries).
- **Tests:** HTTP: a configured market is accepted and a non-market country is rejected for Fire and
  Expat on Store and Update; option lists contain only configured markets.

### 8. Policy forms offer, and the server accepts, only active parties

- **Fact:** `app/Support/Policies/PolicyFormOptions.php::shared()` returns every client, carrier and
  agent, including `Archived` ones (clients and agents ordered by `id`, carriers by name). The options
  feed the entry screen and every class Create/Edit. Validation checks organization scoping only.
  `ClientStatus`, `CarrierStatus` and `AgentStatus` each have only `Active` and `Archived`.
- **Fact:** clients have `first_name`, `last_name` and a nullable `company_name`.
- **Locked:**
  - new policies (the entry screen and class Create) may use only active clients, carriers and agents;
  - Edit may keep its currently assigned archived party but can't switch to a different archived party;
  - the server and the option lists both enforce this;
  - party choices are ordered alphabetically by their user-visible identity.
- **Locked, unchanged:** agent assignment stays optional and independent of lead source; searchable party
  pickers stay deferred.
- **Derived** (premise: preselection comes from the offered options): an archived party passed through
  the entry screen's query string isn't preselected.
- **Open:** how each party's user-visible identity maps to a sort key (for example, company versus
  personal names for clients).
- **Coordination, not a dependency:** issue #366 decides what happens to policies when a client or
  carrier is *deleted*, a separate question from archiving.
- **Tests:** HTTP: option lists exclude archived parties on Create and include the current one on Edit;
  Store rejects an archived client, carrier and agent; Update accepts the policy's own archived party
  and rejects switching to another archived one; an agent from another organization is rejected on
  each class's Store.

### 9. Medical type-change confirmation, and removing `member_code`

- **Fact:** on a Medical Edit, `UpdatePolicyMedicalAction` discards data when the type changes:
  Group → Single deletes every covered member (`SyncPolicyInsuredsAction` with an empty list); Single →
  Group saves the insured-profile fields as null (`prohibited_unless:type,single`). The form gives no
  warning.
- **Fact:** `policy_insureds.member_code` (`string(20)`, `unique(['policy_id','member_code'])`) is
  generated as `MBR-###` by `SyncPolicyInsuredsAction`. Its meaning was never specified: the original
  domain plan took it from `_design/policy-detail.jsx` sample data, and server generation was inferred
  from the create form having no such field. It's never user-entered and appears only as a read-only
  column and search target on the Members tab (`PolicyMembers/Index.vue`). It's also exposed by
  `PolicyInsuredResource`, typed in `PolicyMedical/partials/policy.ts`, generated randomly by
  `PolicyInsuredFactory`, and asserted in `SyncPolicyInsuredsActionTest`, `CreatePolicyMedicalActionTest`,
  `UpdatePolicyMedicalActionTest` and `PolicyInsuredTest`. Nothing else references it, and Members are
  already ordered by `full_name`.
- **Locked:** when a Medical Edit changes Single/Group and saving would discard persisted type-specific
  data, the user first sees a confirmation modal stating what will be lost (the covered members, or the
  insured profile) and can cancel.
- **Locked:** the persisted Single/Group shapes and the server's discard behavior are unchanged.
- **Locked:** `member_code` is removed entirely (schema, generation, resource and type, Members column and
  search, factory and seeder, tests) and isn't replaced by another identifier. This supersedes the
  earlier locked member-code requirements (#319, #323, #325, #358).
- **Derived** (premise: the confirmation is about discarded data): there's no modal when nothing would
  be discarded, and Create is unaffected.
- **Derived** (premise: the column is removed): Members search matches on the remaining fields.
- **Open:** how the schema change is made (a new migration, or amending the pre-production migration);
  the modal component.
- **Locked:** tests whose only responsibility is `member_code` are deleted. Other insured-behavior tests
  are kept, with only their obsolete member-code assertions removed.
- **Tests:** the confirmation is checked manually.

### 10. The New Policy flow keeps the user's choices

- **Fact:** New Policy is two steps: the entry screen `Policies/Create.vue`, then the class form, which
  repeats type, parties, status and source as editable fields.
  `Policies/partials/BackToPolicyEntryButton.vue` rebuilds the entry screen from
  `window.location.search`, so changes made in the class form are lost. The entry screen's selections
  are local `ref`s, so browser Back shows defaults.
- **Locked:**
  - in-app Back returns to the entry screen with the class form's **current** shared values (type,
    client, carrier, agent, status, source) plus the selected class; the class-specific section is
    discarded without a warning;
  - browser Back preserves the entry selections when that's achievable through existing Inertia or
    browser-state mechanisms, without introducing bespoke state infrastructure.
- **Locked, unchanged:** Back's label and header placement; Cancel still goes to the Policies index;
  Continue.
- **Open:** which existing mechanism preserves entry selections on browser Back.
- **Tests:** manual only.

### 11. *(Dropped)*

- **Locked:** no change. The current insured-gender defaults on Create and the current covered-member
  gender control (`RadioChips`) stay as they are.

### 12. Validation messages use readable field names

- **Fact:** no policy Form Request defines `attributes()`, and the project has no `lang/` directory.
  Current messages expose raw keys, e.g. "The medical.insured full name field is required when type is
  single.", "The fire.state id field is required." (labelled "Governorate"), "The
  insureds.0.full_name field is required." Errors already show at the correct field.
- **Locked:**
  - messages use each field's human-readable visible name, in all six classes and for covered-member
    rows;
  - Form Request `attributes()` is preferred; focused custom messages are used where the default wording
    remains awkward;
  - no `lang/` architecture is introduced for this work;
  - rule behavior doesn't change.
- **Derived** (premise: outcomes 3, 4, 6, 7 and 8 change rules on existing fields): every field touched by
  those outcomes has a readable name.
- **Open:** where shared versus class-specific names are defined; the wording for member rows.
- **Tests:** one HTTP assertion that a nested class-field message uses its label; the rest is reviewed
  manually.

### 13. The client's pages reflect that client's policies

- **Fact:** `Clients/partials/ClientPoliciesCard.vue` on the client overview always shows "No policies
  yet."; `Clients/Show.vue` receives only `policiesCount`. Its Add policy link already preselects the
  client.
- **Fact:** the client Policies tab (`ClientPolicies/Index.vue`) reuses the organization-wide
  `Policies/partials/EmptyState.vue`, whose New Policy doesn't pass `client_id`. The Agent and Carrier
  Policies tabs use their own entity-specific empty cards.
- **Fact (`_design/`, allowed as evidence here):** `_design/client-detail.jsx` shows the overview
  Policies card as rows with the subclass, a status badge, "policy number · carrier", the premium and a
  chevron.
- **Locked:**
  - the overview card lists the client's **5 most recent policies by effective date, descending, with a
    deterministic tie-breaker**;
  - each row shows the subclass, status, policy number + carrier and **net premium**, and links to the
    policy's class-correct Show page;
  - "No policies yet." appears only when the client has none; Add policy is unchanged;
  - the full list remains on the Policies tab;
  - the Policies tab's empty state becomes client-specific, and its New Policy carries `client_id`;
  - class-correct links reuse outcome 2's class route configuration rather than a new mapping.
- **Out of scope:** summary stats, a "view all" link, billing cycle, changes to the Policies tab table.
- **Tests:** HTTP: the client overview receives at most 5 of this client's policies, newest effective
  date first with the tie-breaker applied, never another client's or organization's. The tab empty
  state is checked manually.

---

## What must remain true

- Tenancy: every option list and validation stays organization-scoped (`BelongsToCurrentOrganization`,
  and the `exists…where(organization_id)` rules).
- Class-mismatched URLs keep returning 404 via `EnsurePolicyClass`.
- Conditional fields keep their current required/prohibited behavior and are cleared by the Update
  actions: Automotive valuation, Expat travel scope, Medical co-insurance share, and Medical single vs
  group.
- A policy's class stays fixed after creation.
- No JavaScript test framework is introduced for this pass. UI-only behavior is verified manually.

## No-work, deferred and closed items (Locked)

- **Settlements placeholder tab** (which can highlight two tabs at once): deferred to the Settlements
  feature.
- **Index sorting:** unchanged; no sort controls added.
- **Index search** (policy number and subclass only): scope unchanged.
- **Type semantics:** Type stays universal across classes, with no type/subclass pairing rules; only
  Medical Group populates covered members.
- **Expat subclass and coverage zone** stay independent.
- **Life beneficiaries and Travel travelers** stay free text.
- **Gender** (former outcome 11): current defaults and covered-member behavior unchanged.

## Dependencies and coordination between outcomes

- **2 → 13 (reuse):** outcome 13's class-correct links reuse outcome 2's class route configuration, so
  building 13 after 2 avoids a temporary mapping.
- **3, 4, 6, 7, 8 → 12 (coverage):** fields whose rules those outcomes change need readable names; see
  outcome 12's derived constraint.
- **Shared test fixtures:** outcomes 3, 4, 7 and 9 each change `tests/Support/PolicyPayload.php` or the
  Policy factories under the fixture rule.
- **2 and 9 (same page):** both change `PolicyMembers/Index.vue` (the shell, and the member-code column
  and search).
- **1 → 4 (verification order only):** checking outcome 4's date rules by hand is easier after outcome 1.
  Outcome 4's HTTP tests don't depend on it.
- **External coordination:** #366 (client/carrier deletion) sits next to outcome 8 but doesn't block it.

Otherwise, outcomes 1, 5, 6 and 10 are independent.

## Open implementation details (summary)

| Outcome | Open detail | Constraint every option must preserve |
|---|---|---|
| 2 | Location and typing of the class route configuration | One shared source for show/edit/export per class; routes and guards unchanged |
| 6 | Tier enum vs allowed-values rule | Server and UI share exactly Basic/Standard/Premium |
| 7 | Config file/key, parsing, missing/empty failure mechanism | Uppercase ISO2 only; code reads config, not `env()`; options and validation use the same market set; never falls back to all countries |
| 8 | Sort key for each party's user-visible identity | Alphabetical by what the user sees |
| 9 | Schema change approach; modal component | `member_code` gone entirely, no replacement identifier |
| 10 | Mechanism for browser Back | Existing Inertia/browser-state mechanisms only; no bespoke state infrastructure |
| 12 | Placement of shared vs class names; member-row wording | Visible labels, no `lang/` architecture, rule behavior unchanged |
