# Policy forms & show page — UX revision (APPROVED plan)

> **Status: approved by the owner on 2026-10-06.** This plan is the source of truth for the
> `plan-it` pass. Implementation details still need verifying against the codebase when individual
> issues are built.
>
> Re-synthesized from the 2026-10-03 `lab-it` investigation and the owner's locked decisions of
> 2026-10-05 (three rounds; the third settles form preservation on Inertia's remember mechanism and
> the migration approach) and 2026-10-06 (approves the form-preservation resolutions B1, B2 and
> B3, corrects the class/carrier reset rules, and approves the plan). Verified against `main` @
> `6d0acbd` and the installed Inertia (`@inertiajs/vue3` / `core` 3.7.0, `inertiajs/inertia-laravel`
> ^3.0). Claims are labelled **[Fact]** (verified current state), **[Locked]** (owner-approved),
> **[Derived]** (follows from facts + locked decisions; premises stated), **[Open]**
> (implementation choice; every option preserves the locked outcomes) and **[Assumption]** (relied
> on by the design, must be confirmed in a real browser). §10 records the approved
> form-preservation resolutions.

## What we're doing

**Creating a policy** takes two steps.

- **Step 1** asks who and what: the class (Medical unless the link says otherwise), type, client,
  carrier, agent and lead source.
- **Step 2** asks for everything else. At the top it shows a short read-only summary of the step-1
  choices, with a Back link to change them. If one of those choices has a problem (for example,
  the client was archived in the meantime), the error shows next to the summary.
- **Back keeps your work.** Go back to step 1, change something, continue, and step 2 is filled in
  again:
  - same class: everything comes back;
  - a different class: the shared fields (policy number, dates, premium, discount…) come back,
    and the fields that belong only to the old class are thrown away;
  - a different carrier: the issuing branch is thrown away, since branches belong to a carrier;
  - thrown-away fields stay gone. Switching back to the old class or carrier, or using the
    browser's Back and Forward buttons to reach an older page, doesn't bring them back.
  - Medical Single and Group keep both sets of entries while you switch, but only the chosen one
    is saved.
- **Cancel or a successful save ends the flow.** The browser's Back button then can't bring the
  old entries back, and the next "New policy" starts empty.
- **Refreshing the page loses unfinished entries.** The step-1 choices survive (they're in the
  link), but anything typed and not yet saved is gone. Nothing is saved as a draft.

**Editing a policy** shows every field except status.

**Status.** Nobody picks a status in a form any more. New policies are saved as Active, and editing
never changes the saved status. What people see everywhere (lists, policy pages, a client's
policies, the Excel export and all six PDFs, plus the list filter) is a status worked out from the
dates: **Upcoming**, **In force** or **Expired**, or **Cancelled** / **Frozen** when the policy has
been cancelled or frozen. The buttons to cancel or freeze a policy come later, in separate work.

**Organization timezone.** Organization Settings gets an optional timezone, picked from a
searchable list. If none is set, UTC is used. "Today" for the status, the filter, exports, PDFs and
the agent page's "renewing soon" list follows that timezone. Policy dates are calendar dates and
never shift when the timezone changes.

**Finding a client.** The client field becomes a search box: type at least 2 letters and up to 10
matching active clients appear. The chosen client shows with their avatar and name and a button to
clear it. The forms no longer load every client up front. Carrier and agent stay as dropdowns.

**Policy pages.** On all six policy types, the money figures move to the top as a compact strip
without a heading, followed by the detail card with a bordered heading. The sidebar stays as it
is. The carrier page's stats card also loses its heading.

**Small fixes.** The discount rule (no more than the premium; a net premium of 0 is allowed)
gets its missing test for editing. The covered-member "relationship" field stays free text and
gets the hint "e.g. Employee, Spouse, Child".

**Not in this work:** the Cancel and Freeze buttons, a fixed list of relationships, search boxes
for carriers and agents, saved drafts, new scheduled jobs, and wider changes to how times are
shown.

---

## 1. Create flow

### Current state — forms and navigation
- [Fact] `resources/js/pages/Policies/Create.vue` (step 1) starts with `class: props.selected.class ?? ''`,
  collects type, class, client, carrier, agent, **status** and source, and continues with
  `router.visit(classCreateRoute.url({ query }))`. That's a **forward visit**.
- [Fact] Step 2 (`PolicyMedical/Create.vue` and its five siblings) reads its defaults from
  `window.location.search` in the browser. The class controllers' `create()` take no request and
  resolve nothing (`PoliciesMedicalController::create`). Each `Policy*Form.vue` is shared by Create
  and Edit. It renders type, parties (`PolicyPartiesSection`), status and source as editable fields,
  and holds each field in its own `ref` inside an uncontrolled Inertia `<Form>`.
- [Fact] The in-page Back (`Policies/partials/BackToPolicyEntryButton.vue`) reads the step-1 keys from
  the step-2 `<form>` and calls `router.visit(policies.create.url({ query }))`, another **forward
  visit**. `PoliciesController::create` re-resolves the query safely: enum values via `tryFrom`,
  scalar-only ids, and active, organization-scoped parties. `CreateTest` covers it.
- [Fact] Step 1 already uses `useRemember(reactive({...}), 'Policies/Create')`. Step 2 remembers
  nothing, so anything typed there is lost on Back.
- [Fact] Every class form has the same common fields:
  - policy number;
  - issuing branch;
  - effective and expiry dates;
  - currency, premium and discount (`PolicyFinancialsSection`).

  Class-specific fields are the subclass plus each class's own sections. Medical switches insured
  profile vs covered members with `isGroup` (`v-if`), so the hidden section isn't submitted.
  Medical step 2 holds health data (`medical.insured_medical_history`,
  `insureds[i][medical_notes]`).
- [Fact] The issuing branch's options come from the selected carrier in the `carriers` prop, and it
  resets when the carrier changes.
- [Fact] Cancel on step 2 is a `<Link>` to the policies index. A successful store redirects to the
  class Show page.

### Current state — installed Inertia remember and history behaviour (3.7.0, verified in `node_modules/@inertiajs/core/dist/index.js` and `vue3/dist/index.js`)
- [Fact] **Remembered state belongs to one browser history entry.**
  - `useRemember(data, key)` restores via `router.restore(key)` at component setup, then writes every
    change with `router.remember()` → `history.replaceState` into the **current** entry
    (`rememberedState[key]`).
  - Every forward visit pushes a **new** entry with `rememberedState ??= {}`, i.e. empty.
- [Fact] **When remembered state comes back:**
  1. **Browser Back/Forward** (`popstate` → `page.setQuietly(data, { preserveState: false })`): the
     page remounts from that entry's stored state, remembered values included.
  2. **Same-component responses with `preserveState`** (`setRememberedState`: only when
     `pageResponse.component === page.get().component`): the `router.post/put/patch` defaults use
     `preserveState: true`. That's why a step-2 validation error, which redirects back to the same
     component, keeps the user's entries today.
- [Fact] **When it doesn't:**
  - across a forward visit to a different component, or to a fresh entry (the existing Back
    button and Continue are both forward visits);
  - after a page reload (`InitialVisit.clearRememberedStateOnReload` deletes it).
- [Fact] The `<Form>` component has no `remember` prop (props: `action`, `method`, … `resetOnError`,
  `resetOnSuccess`, …). Remembering a `<Form>` page means wrapping its field state in
  `useRemember`.
- [Fact] **History entries are stored in plain text and stay revivable.**
  - `config/inertia.php` `history.encrypt` defaults to `false` (`INERTIA_ENCRYPT_HISTORY`), and no
    route uses the `inertia.encrypt` middleware.
  - After Cancel or a successful create, browser Back re-renders the old step-2 entry with its
    remembered entries, so the user could resubmit.
- [Fact] **Inertia's built-in invalidation needs encrypted entries.**
  - With history encryption, entries are AES-GCM encrypted with a key held in `sessionStorage`.
  - `Inertia::clearHistory()` (server) or `router.clearHistory()` (client) removes that key.
  - Afterwards, `popstate`, or a `pageshow` from the bfcache, fails to decrypt →
    `onMissingHistoryItem` → `page.clear()` + `router.visit(location, { replace: true })`. That's a
    fresh server render, with no remembered state carried over, because `page.clear()` empties the
    component the carry-over check compares against.
  - It needs `crypto.subtle`, i.e. a secure context. Without one it warns and stores plain text.
    The local `APP_URL` is `https://useorbit.test`.

### Locked decisions
- [Locked] Step 1 keeps class, type, client, carrier, agent and lead source.
- [Locked] Class defaults to Medical unless the URL supplies another class.
- [Locked] Step 2 contains the remaining policy details and does not repeat step-1 fields.
- [Locked] Edit keeps all relevant fields available except status.
- [Locked] **Step-2 summary:**
  - a compact read-only summary of the step-1 choices, with a Back link to change them;
  - validation errors for those hidden fields shown alongside the summary;
  - entered work preserved while correcting those errors.
- [Locked] **Form preservation:**
  - **Mechanism:** Inertia's existing remember mechanism (`useRemember`) for both Create steps,
    alongside a small in-memory hand-over between the steps (§10 B1). No separate browser storage,
    no database draft.
  - **Scope:** all form entries, including Medical history and members' notes.
  - **Back and error correction:** both restore the current flow's entries.
  - **Class change:** keeps the common fields and **discards** the class-specific entries.
  - **Carrier change:** **discards** the issuing branch.
  - **No resurrection:** switching back to the earlier class or carrier doesn't bring discarded
    values back, including when an older page of the flow is restored through browser
    Back/Forward (§10 B3).
  - **Medical Single ↔ Group:** preserves both sets of entries, but submits only the selected
    type's.
  - **Ending:** Cancel or a successful create ends and clears the flow. A fresh "New policy" starts
    empty.
  - **History:** the policy Create flow's history is encrypted and invalidated on Cancel or a
    successful create, so returning through browser history can't revive an ended flow (§10 B2).
  - **Accepted limitation:** a full browser refresh loses unfinished entries.

### Derived constraints
- [Derived] **Step 2 Create submits the step-1 values as hidden inputs**, and the forms get a Create
  mode (summary + hidden inputs) and an Edit mode (visible fields), driven by the `policy` prop's
  presence. *Premise:* Store validates `type`, `client_id`, `carrier_id`, `agent_id` and `source`.
- [Derived] **The step-1 values are resolved on the server for step 2.** The class `create()`
  actions resolve the query the way `PoliciesController::create` does (shared, not duplicated).
  - That gives the summary trusted labels and detects missing or invalid values.
  - When a required step-1 value can't be resolved, the user goes back to step 1 with the valid
    values kept, and the work is carried under the same rules as Back.
  - Store-time validation stays the authority for hidden inputs.
- [Derived] **Hidden-field errors render on the summary.** The keys are `type`, `client_id`,
  `carrier_id`, `agent_id`, `source` and `class`. The summary's Back link is the correction path, so
  it carries the step-2 entries exactly like Back.
- [Derived] **Each step's field state lives in `useRemember`.** Step 1 already does this. Step 2
  holds all fields, including both Medical type sections and the members rows, in one remembered,
  reactive object per class form, under a key that includes the class.
  - That alone delivers: browser Back/Forward within an unfinished flow, and step-2 validation
    errors (same component + `preserveState`).
  - Back → Continue, the summary's correction path and the invalid-entry redirect are forward
    visits that create fresh, empty entries. The in-memory hand-over (§10 B1) carries the work
    across them.
- [Derived] **The carried work is the step-2 entries plus the class and carrier they belong to.**
  Step 1 seeds its own remembered state with the carried work it receives, so browser Back/Forward
  to step 1 keeps it, and hands it on at Continue.
- [Derived] **Class-change and carrier-change rules discard, they don't hide.** The discarded values
  are removed from the carried work itself, so nothing later can restore them:
  - when the class selected on step 1 differs from the carried work's class, the class-specific
    entries (including both Medical type sections and the members rows) are dropped and only the
    common fields remain, now belonging to the new class;
  - when the carrier selected on step 1 differs from the carried work's carrier, the issuing branch
    is dropped and the work now belongs to the new carrier;
  - the rules apply as soon as the step-1 selection changes, and again when step 2 seeds its
    remembered object (a guard for any path that skips step 1's change handling);
  - they also apply when an older page of the flow is restored from browser history (§10 B3).

  Switching back (e.g. Medical → Fire → Medical, or carrier A → B → A) therefore starts the
  class-specific fields, or the branch, empty.
- [Derived] **Comparing the restored entry's class or carrier with the current one can't enforce
  this.** After Medical → Fire → Medical, an old Medical entry matches the current class but still
  holds the discarded Medical entries. Restoration has to know whether a discard happened *since*
  the entry was written, not what the current selection is (§10 B3).
- [Derived] **Common fields survive any number of class changes**, and the carrier rule only ever
  touches the issuing branch. Medical Single ↔ Group is a step-2 toggle within one class, so it
  discards nothing; both type sections stay in the remembered object, and the `v-if` keeps the
  hidden one from being submitted.
- [Derived] **Ending the flow (Cancel, successful create) empties the hand-over and clears the
  encrypted history** (§10 B2). A fresh "New policy" is a forward visit to step 1 with no carried
  work, so it starts empty without further measures.
- [Derived] A **full page reload** of step 1 or step 2 drops remembered entries (Inertia behaviour)
  and the in-memory hand-over. The step-1 choices still come back from the URL query. This is the
  owner-accepted limitation, not a requirement to work around.

### Open details
- [Open] How the shared step-1 resolution is extracted, and whether invalid step-2 entry redirects or
  renders step 1 directly.
- [Open] Remember key shapes; where the hand-over module lives and its API. Whether the summary's
  Back link replaces or reuses `BackToPolicyEntryButton`. Copy for step 1's former "Status &
  origin" section.


## 2. Policy status

### Current state
- [Fact] `App\Enums\PolicyStatus` (Active / Cancelled / Frozen) is a stored column, cast on `Policy`.
  It has no relationship to `effective_date` or `expiry_date`, which are both `date` columns.
- [Fact] Every Store **and Update** request sets `'status' => $this->input('status') ?? Active` in
  `prepareForValidation()` (e.g. `UpdatePolicyMedicalRequest`), and `UpdatePolicyAction` writes
  `$attributes['status']`. **Removing the field from Edit without changing this would silently reset
  a Cancelled or Frozen policy to Active.**
- [Fact] `effective_date` is `required|date`, and `expiry_date` is
  `required|date|after_or_equal:effective_date`. A single-day term is valid.
- [Fact] **Every user-facing occurrence of the policy status** (audit by grepping `status_label`,
  `policyStatusTone`, `->status->label()` and the `statuses` prop):

  | Surface | Where |
  | --- | --- |
  | List column | `Policies/partials/PoliciesTable.vue` |
  | List, card layout | `Policies/partials/PolicyCard.vue` |
  | Show header (mobile and desktop badges, shared by Show, Members, Documents and Notes via `PolicyDetailShell`) | `Policies/partials/PolicyShowHeader.vue` |
  | Related-policy badges | `Clients/partials/ClientPoliciesCard.vue` |
  | Status filter | `FiltersDrawer.vue` ← `statuses` prop from `PoliciesController::index`; `IndexPolicyRequest` (`Enum(PolicyStatus)`); `PolicyFilter::status()` |
  | Excel export (filter + "Status" column) | `ExportPoliciesToExcelAction` (shares `IndexPolicyRequest`/`PolicyFilter`); `Exports/PoliciesExport.php` |
  | PDFs | `resources/views/exports/policy-{medical,automotive,expat,fire,life,travel}-profile.blade.php` (`$policy->status->label()`) |
  | Forms (removed in this scope) | step 1 `Create.vue`; the six `Policy*Form.vue` |
  | TS types | `types/policy.ts` and the six `Policy*/partials/policy.ts` (`status`, `status_label`) |

  Not affected:
  - `Agents/partials/PoliciesRenewalCard.vue` shows a fixed "Renewing" badge, not the status;
  - client, agent and carrier PDFs show those entities' own statuses;
  - `design-foundation/card` uses hard-coded mock data.
- [Fact] Tests asserting today's behaviour that will change:
  - `IndexTest`, *"a status filter narrows … exact matching status"* and *"an invalid status is
    rejected"*;
  - `CreateTest`, the status preselection cases;
  - Store and Update tests posting `status`;
  - `ExcelExportTest` and the six `*PdfExportTest` files, where they assert the status.

### Locked decisions
- [Locked] No status control in either Create step or any Edit form.
- [Locked] Create always stores Active. Update preserves the existing stored status.
- [Locked] Cancel and Freeze actions are out of scope (separate UI later).
- [Locked] One computed `display_status` with five values: Upcoming / In force / Expired / Cancelled /
  Frozen.
  - A stored Cancelled or Frozen takes precedence.
  - For a stored Active:
    - Upcoming when today < `effective_date`;
    - In force when `effective_date` ≤ today ≤ `expiry_date`;
    - Expired when today > `expiry_date`.
  - Both dates are inclusive. The date-derived value is never stored.
- [Locked] **The stored status is internal.** The display status is used everywhere users see or
  filter a policy status: lists, Show pages, related-policy badges, Excel exports and all six PDFs.
  No user-facing surface still presents the internal "Active".
- [Locked] "Today" comes from the organization's effective timezone (§3).

### Derived constraints
- [Derived] **Create ignores any submitted `status`** and stores Active, so a crafted POST can't set
  it. **Update neither validates, defaults nor writes `status`**, because the Update
  `prepareForValidation` default plus the action's write would overwrite Cancelled or Frozen.
- [Derived] **One rule, two paths, one "today".** The PHP computation (resource, PDFs, Excel rows) and
  the SQL filter use identical boundaries from the same organization-local date (§3):
  - Cancelled: `status = cancelled`;
  - Frozen: `status = frozen`;
  - Upcoming: `status = active AND effective_date > today`;
  - In force: `status = active AND effective_date <= today AND expiry_date >= today`;
  - Expired: `status = active AND expiry_date < today`.

  Because `after_or_equal` guarantees effective ≤ expiry, these five are exhaustive and disjoint.
- [Derived] **The filter accepts only display values.** `IndexPolicyRequest` validates against the
  display set, and the index passes display options instead of `PolicyStatus::all()`. The Excel export
  shares the request and filter, so it filters identically to the index.
- [Derived] **Every surface in the audit table switches together.** The user-facing `status` /
  `status_label` pair is replaced or shadowed by the display value. Keeping the raw value in the
  resource is acceptable only if no surface renders it. The Excel column and PDFs read the same PHP
  computation as the resource.
- [Derived] `PolicyStatus` stays the stored enum. The forms no longer need `statuses` from
  `PolicyFormOptions::shared()`.

### Open details
- [Open] Where the rule lives: for example, a `PolicyDisplayStatus` enum with a resolver plus a
  matching query scope or filter method. Resource key names. Whether the raw `status` stays in the
  resource for internal use.
- [Open] Badge tones for the five values (extend `policyStatusTone` or add a sibling map).

## 3. Organization timezone (added scope)

### Current state
- [Fact] `config/app.php` `timezone` is `UTC`. There's no timezone on organizations or users
  anywhere (no column, no config, no UI).
- [Fact] **Storage:** `organizations` has `name`, `two_factor_required`, `default_country_id` and
  `default_currency_id` (`Organization` `#[Fillable]`).
  - The organization defaults were added by **editing the create migration**
    (`0000_01_01_000003_create_organizations_table.php`, commit `eb19f14`).
  - `create_policies_table` was likewise edited three times. No `Schema::table` alter migration
    exists in the repo.
- [Fact] **Settings:** `routes/settings.php` has `settings/organization` (`OrganizationController@edit`,
  which also renders `defaultCountryId`, `defaultCurrencyId`, `countries` and `currencies`).
  - The details form posts to `PATCH settings/organization/details` →
    `OrganizationDetailsController` → `OrganizationDetailsUpdateRequest` (`name` required; defaults
    `present|nullable|…`) → `UpdateOrganizationDetailsAction` (`$organization->update($attributes)`).
  - Authorization: `#[Authorize('update', Organization::class)]`. Only the Owner can view it
    (`tests/Feature/Http/Settings/OrganizationTest.php`).
- [Fact] **Picker convention:** `settings/Organization.vue` picks the default country with the static
  `Typeahead`, with a leading `{ value: '', label: 'No default' }` option, an `optional` `FormField`
  and a helper text ("Changing it never changes existing records").
- [Fact] **Provisioning and defaults:** `ProvisionOrganizationAction` creates an organization with
  only `name`. `OrganizationFactory` sets only `name`. Both would leave a new column null.
- [Fact] **Organization context:** the `organization` middleware (`EnsureOrganizationContext`)
  stores only the organization **id** in the scoped `OrganizationContext`. Jobs and scheduled
  commands get no context: `routes/console.php` schedules only `model:prune`, and
  `StoreDocumentJob` is the only job. Excel and PDF exports run synchronously in the request.
- [Fact] **Date calculations relative to "today":**
  - agent "renewing soon" (`AgentsController::show`: stored Active and `expiry_date` within
    [`now()->toDateString()`, +30 days]), tested in `tests/Feature/Http/Agents/ShowTest.php`,
    including both window edges;
  - otherwise only non-policy uses: the `ClientFilter` age filters, the automotive/fire year
    bounds, and browser-side `new Date()` in `DateInput`/`policyDateEndYear`.
- [Fact] Tests use `$this->freezeTime()` (e.g. `MedicalStoreTest`).

### Locked decisions
- [Locked] Organization Settings gets an **optional** timezone setting. Values are valid IANA
  identifiers, chosen with a searchable picker following existing UI conventions.
- [Locked] An unset organization timezone falls back to UTC.
- [Locked] "Today" for `display_status` comes from that effective timezone. The same
  organization-local date is used for status filters, exports, PDFs and existing related-policy date
  calculations, including "renewing soon".
- [Locked] Policy `effective_date` and `expiry_date` remain calendar dates. Changing the timezone
  never converts stored dates.
- [Locked] A small shared way to resolve the organization's timezone and date, reusable by future
  scheduled jobs.
- [Locked] No new cron jobs, no scheduling framework, no broad timestamp-display changes.
- [Locked] **Migration:** the project is still pre-production and databases can be rebuilt, so the
  optional column goes into the original `0000_01_01_000003_create_organizations_table.php`,
  following the existing approach.

### Derived constraints
- [Derived] **Storage:** a nullable `timezone` string column on `organizations` (null = UTC), added to
  `#[Fillable]` and the model's `@property` docblock. It's added to the create migration (locked
  above). Local and test databases need a `migrate:fresh`.
- [Derived] **Validation:** `OrganizationDetailsUpdateRequest` adds
  `timezone => ['present', 'nullable', 'timezone:all']`, matching the existing `present|nullable`
  defaults. The picker's options come from the same identifier list the rule accepts
  (`DateTimeZone::listIdentifiers(DateTimeZone::ALL)`), so every offered value validates and nothing
  else does. Authorization is unchanged (Owner only via `update`). The action's array-shape docblock
  gains `timezone`.
- [Derived] **Picker:** the details form uses the static `Typeahead`, like the default country, with a
  leading "UTC (default)"-style empty option, `optional`, and a helper saying stored policy dates
  aren't converted. The controller passes the current value and the identifier list.
- [Derived] **Provisioning and factory need no change:** null is the UTC fallback. Tests create
  organizations with a timezone explicitly when they need one.
- [Derived] **One shared resolver**, e.g. in `App\Support\Tenancy`:
  - it resolves an organization's effective timezone (stored or UTC) and its local date
    (`today`);
  - it takes the organization explicitly, so a future job can call it without request context;
  - it also has a request-path convenience over `OrganizationContext`;
  - it's used by the display-status computation, `PolicyFilter`, the Excel export, the PDFs and
    `AgentsController` renewing soon.

  `config('app.timezone')` and stored timestamps stay UTC; only the derived calendar "today"
  changes.
- [Derived] **Renewing soon** keeps its semantics (stored Active, expiry within [today, today+30]) with
  the organization-local today. Its stored-Active check is internal, not user-facing, so it stays.
- [Derived] **Per-policy "today" follows the policy's own organization.** In a request that's the
  current organization. A future job would resolve each policy's `organization_id` through the
  resolver.

### Open details
- [Open] Resolver class and method names; per-request memoization of the organization lookup.
- [Open] Picker labels (bare identifier, or with the current UTC offset or a city name), as long as
  the submitted value is the identifier.

## 4. Client typeahead

### Current state
- [Fact] `resources/js/components/ui/typeahead/Typeahead.vue` is generic (`TypeaheadOption { value, label }`).
  - It has static `options` or async `search(query)` (debounced, with stale responses discarded via
    `requestId`), plus `initialLabel`, `loading`, `name` (hidden input), `size` and `disabled`.
  - Gaps: async mode searches on focus even with an empty query, and there's no minimum length.
    There's no selected-state presentation and no clear action. Rows render `label` only. And the
    consumer isn't told the chosen option's label.
- [Fact] Async mode is used only in `design-foundation/typeahead` (`snippets/async.md`), against an
  in-memory stand-in. The JSON lookup pattern is `World\StatesController` (`{data:[{id,name}]}`,
  FormRequest, `auth` + `organization`), consumed with `fetch` and a Wayfinder URL
  (`composables/useWorldLocations.ts`).
- [Fact] `PolicyFormOptions::shared()` sends every active client (plus the kept one on Edit) to step 1,
  all six class Create pages and all six Edit pages. Clients render in native `Select`s in `Create.vue`
  and `PolicyPartiesSection.vue`.
- [Fact] `ClientFilter::search()` matches `LIKE %term%` on first, middle and last name, company name,
  phone and email. `Client::full_name` is the company name for companies, otherwise first + last.
  `Client` is organization-scoped (`BelongsToCurrentOrganization`). `ClientPolicy::viewAny` requires
  an organization. `PolicyFormOptions::clients()` orders by displayed name.
- [Fact] `PolicyValidationRules::assignablePartyRule` accepts a client in the user's organization
  that's either active or the edited policy's current client (the kept-party rule).
- [Fact] Entry from a client page is `ClientPoliciesCard` → `policies.create?client_id=`.
  `PoliciesController::create` returns only the id.
- [Fact] `PolicyResource` exposes `client {id, slug, full_name}`. `Avatar` renders initials from a
  `name`.
- [Fact] `routes/clients.php` has `clients/{client:slug}`. Design-foundation routes (`routes/dev.php`)
  are local-only and sit outside the `auth`/`organization` group.

### Locked decisions
- [Locked] Reuse and extend the generic `Typeahead`. Client rendering stays in the consumer, through
  generic slots.
- [Locked] Server-side, organization-scoped search over active clients. No results before 2
  characters. Searches are debounced while the user types. At most 10 results, capped in SQL. Reuse
  the existing client search semantics where appropriate.
- [Locked] Stop sending full client lists to policy forms.
- [Locked] The selected client shows as avatar/initials + name with a clear button.
- [Locked] Preserve the selected client's identity and label through step transitions, Back,
  validation errors, client-page entry and Edit.
- [Locked] Keep the kept-party rule for an archived client already on an edited policy.
- [Locked] Carriers and agents remain selects.
- [Locked] Add an endpoint-backed Typeahead example to the design docs. Clients are its first real
  use case.

### Derived constraints
- [Derived] **New JSON endpoint**, following the `StatesController` shape:
  - FormRequest-validated `search` with the 2-character minimum enforced server-side too, authorized
    as `viewAny` on `Client`, under `auth` + `organization`;
  - active clients only, `ClientFilter::search` semantics, `limit(10)` in the query, ordered by
    displayed name;
  - returns `{data:[{id, full_name}]}`.

  `RanksSearchResults::rankAndCap()` is not reused (it caps in PHP). A route under `clients/…` must
  be registered before `clients/{client:slug}`.
- [Derived] **Typeahead primitive additions, all generic:**
  - a minimum query length below which there's no request and no results;
  - a selected-state slot plus a clear action that sets `null`;
  - an option-row slot;
  - a way for the consumer to learn the chosen option's label.

  Static-mode consumers (countries, states, and the new timezone picker) keep working unchanged.
- [Derived] **The label always comes from the server, never from the URL.**
  - Step 1 (`PoliciesController::create`) returns `selected.client` as `{id, full_name}`, which
    covers client-page entry and Back.
  - Step 2's server resolution (§1) supplies it for the summary.
  - Edit uses `policy.client`.
  - Step 1's `useRemember` state holds the label alongside the id.
- [Derived] With `clients` removed from `PolicyFormOptions::shared()`, its consumers drop the prop:
  step 1, the six Create and six Edit pages, and `PolicyPartiesSection`. The `CreateTest` client
  ordering and active-only tests move to the endpoint's tests.
- [Derived] Edit with an archived kept client: it displays from `policy.client` and validates under
  the kept-party rule. Once cleared, search can't find it again (active-only).
- [Derived] The design-foundation pages are outside the auth group, while the client endpoint
  requires `auth` + `organization`. The design-docs example must work under that constraint.

### Open details
- [Open] Endpoint URL and controller name, and the prop and slot names on `Typeahead`.
- [Open] How the design-docs example reaches an endpoint: the real client search (works when logged
  in locally), or a dev-only demo endpoint next to the design-foundation routes.
- [Open] Whether prefix matches rank before contains matches within the 10.

## 5. Discount

- [Fact] `'discount_amount' => ['nullable', ...policyAmountRules(), 'lte:premium_amount']` is in the
  shared `PolicyValidationRules` (every class, store and update). The discount input has
  `min="0"` and no `max`.
- [Fact] `MedicalStoreTest` covers *"a discount greater than the premium is rejected"* and *"a
  discount up to the premium is accepted"*, where equality gives a net of 0. **Confirmed: no Update
  test asserts the rule.**
- [Locked] Server-side validation only. Discount ≤ premium; equality and a net of 0 are allowed. No
  new browser validation. Add the missing Update regression test.
- [Derived] One Update test (any class) covers the shared rule. It mirrors the Store pair.

## 6. Show layouts

- [Fact] All six `Policy*/Show.vue` pages: the main column is `<ClassDetailCard>` then
  `PolicyFinancialsCard`; the sidebar is `PolicyPartiesCard` then `PolicyTermCard`.
  - `PolicyFinancialsCard` has `CardHeader bordered` ("Financials") and three `StatCell`s.
  - The six `*DetailCard`s use plain `CardHeader`.
  - `Agents/partials/QuickStatsCard.vue` is headerless. `Carriers/partials/QuickStatsCard.vue` has a
    bordered header.
- [Locked] In all six classes:
  - Financials moves above the detail card, with its header removed (a compact stat strip);
  - the main detail cards get bordered headers;
  - sidebar headers stay unchanged;
  - the Carrier stats card loses its header.
- Nothing open.

## 7. Covered-member relationship

- [Fact] A free-text `Input` in `PolicyMedicalForm.vue` (`insureds[i][relationship]`) with no
  placeholder, validated `string|max:20`.
- [Locked] Keep it free text. Add the placeholder "e.g. Employee, Spouse, Child". No enum in this
  scope.

---

## 8. What must remain true

- Store-time validation stays the authority for every step-1 value, hidden or not: organization
  scoping, active-or-kept parties, and enum membership.
- A stored Cancelled or Frozen status never changes through Create or Edit.
- The display status is never persisted. The PHP and SQL paths agree on every boundary day, for
  every organization timezone.
- Stored policy dates are never converted when the timezone changes. `app.timezone` and stored
  timestamps stay UTC.
- No user-facing surface presents the internal "Active".
- Preserved Create work lives only in Inertia's remembered state for the current flow and, briefly,
  in the in-memory hand-over between steps. It never reaches the server as a draft, never pre-fills
  an Edit form or a fresh "New policy", and can't be revived from history after Cancel or a
  successful create.
- Values discarded by a class or carrier change are never restored, neither by switching back nor
  by browser Back/Forward to an older page of the flow.
- Step-2 validation errors keep the entries, as they do today (same component + `preserveState`).
- Static-mode `Typeahead` consumers behave exactly as today.
- Carrier and agent selects, the carrier → branch dependency, and the Edit type-change discard
  confirmation are unchanged.
- Net premium semantics (premium − discount, computed when read) are unchanged.

## 9. Dependencies between the changes

These are technical dependencies only. Issue decomposition and sequencing belong to `plan-it`.

- **Organization timezone** (column, settings field, validation, picker, shared resolver) →
  **display status rule** (backend). The rule needs the resolver's local "today".
- **Display status rule** → every surface in the §2 audit table (list column, `PolicyCard`, Show
  header, `ClientPoliciesCard`, filter UI and request, Excel, PDFs).
- **Resolver** → the "renewing soon" switch to the organization-local date. That switch is
  independent of the display status work.
- **Status removal from forms** is independent of the display status, but shares the Store and
  Update requests, actions and `Policy*Form.vue` files with the Create-flow restructure. Do it
  before, or together with, that restructure.
- **Typeahead primitive** and **client search endpoint** are independent of each other. Both precede
  the **client typeahead wiring** and the **design-docs example**.
- **Server-side step-1 resolution** (labels + validity) precedes the **step-2 summary**, and the
  client typeahead wiring depends on it for the client label. **Dropping `clients` from
  `PolicyFormOptions`** comes last.
- **Create-flow restructure** (Medical default, step 2 Create mode with summary and hidden inputs,
  Back) shares `Create.vue`, `PolicyPartiesSection`, the six `Policy*Form.vue` files and
  `BackToPolicyEntryButton` with the typeahead wiring. Coordinate them to avoid parallel edits.
- **Form preservation** depends on:
  - the Create-mode restructure (which fields exist on step 2);
  - the common/class-specific field split;
  - the in-memory hand-over (§10 B1), the Create-flow history encryption (§10 B2) and the
    history-restore discard check (§10 B3, built with B1 since it extends the same module). B2 is
    independent of the rest and can land first.

  Moving each class form's refs into one remembered object touches the same six form files as the
  status removal and the typeahead wiring.
- **Discount test**, **Show layouts** and **relationship placeholder** are independent.

## 10. Approved form-preservation resolutions

All three close limits of the installed Inertia 3.7 behaviour in §1: remember works per history
entry, so it covers browser Back/Forward and validation errors, but it can neither move work
between steps, expire entries, nor apply later discards to entries written earlier. The owner
approved all three on 2026-10-06, including that a full browser refresh loses unfinished
entries.

- [Locked] **B1 — in-memory hand-over between steps.** A module-level variable, not browser
  storage, used alongside `useRemember` on **both** steps:
  1. Immediately before a flow navigation (Continue, the in-page Back, the summary's correction
     link, the invalid-entry redirect), the leaving page puts the carried work there.
  2. The arriving page consumes it once at setup, applies the class/carrier discard rules (§1), and
     seeds its `useRemember` object, so the work then lives in that entry's remembered state.
  3. Cancel and a successful create empty it. A fresh "New policy" finds nothing.

  *Why:* every flow navigation is a forward visit with empty remembered state, and
  `history.back()` can't replace them (Continue after a step-1 change needs a new step-2 URL).
  *Limitation (accepted):* a full browser refresh loses the unfinished entries.
- [Locked] **B2 — encrypted, invalidated Create-flow history.** Inertia's built-in history
  encryption on the policy Create flow routes only (the `inertia.encrypt` middleware on
  `policies.create` and the six class `create` routes), invalidated when the flow ends:
  - `Inertia::clearHistory()` on the successful store response;
  - `router.clearHistory()` on Cancel.

  Ended-flow entries then fail to decrypt, and Inertia re-fetches those URLs fresh, with no
  remembered work. Encrypted entries also keep health data off disk in plain text.
- [Derived] **B2 consequences, documented:**
  - **Clearing history invalidates every encrypted entry in the tab**, not just the ended flow's:
    `clearHistory` removes the tab's single `sessionStorage` key. Today only the Create-flow pages
    are encrypted, so the effect is limited to them (e.g. an earlier, abandoned Create flow in the
    same tab is also re-fetched empty). Any route encrypted later is affected the same way, and
    must be checked against this.
  - A re-fetched step-2 URL still shows the summary for the step-1 choices in its query, but with
    empty fields.
  - It requires a secure context (HTTPS; local is `https://useorbit.test`). Without one Inertia
    warns and stores plain text.
- [Locked] **B3 — discard rules enforced when history is restored.** Older history entries of
  an unfinished flow keep their own remembered state, so without this, browser Back to a step-1 or
  step-2 page from before a class or carrier change would bring discarded values back.
- [Derived] **Smallest mechanism: two discard counters in the B1 module, stamped on every entry.**
  - The hand-over module also holds the current flow's record: a flow id plus a **class-discard
    count** and a **branch-discard count**. Each count only ever increases, by one per discard
    (class change, carrier change).
  - Every remembered snapshot on both steps carries the flow id and the two counts it was
    written under.
  - When a page restores a snapshot (browser Back/Forward, a bfcache restore):
    - different flow id, or no record in memory → the snapshot's carried work is not restored
      (the page starts as a fresh visit would);
    - snapshot's class count < current → drop its class-specific entries;
    - snapshot's branch count < current → drop its issuing branch;
    - then re-stamp it with the current counts and write it back, so the check runs once per
      entry.
  - Common fields, the step-1 choices in the entry and anything not discarded since restore as
    written.

  Medical → Fire → Medical makes the class count 2. A Medical entry written at 0 loses its
  Medical entries, although its class matches the current one. Carrier A → B → A works the same
  way for the branch.
- [Derived] **Why counters, not alternatives:**
  - comparing current class/carrier fails the switch-back sequences (§1);
  - calling `clearHistory` on each discard would also wipe the older entries' common fields and
    the current flow's Back/Forward, which the locked rules keep.
- [Derived] **Edges:**
  - After a full refresh the module is empty, so older entries of that flow restore without their
    carried work. This extends the accepted refresh limitation; it can't resurrect anything.
  - Starting a new flow (fresh "New policy", client-page entry) replaces the record, so entries of
    an earlier, abandoned flow in the tab restore without their carried work.
  - [Assumption] A bfcache restore brings back the JavaScript memory together with the page, so
    the record and the entry stay consistent. This must be confirmed in real browsers (at least
    Chrome and Safari) during implementation; if it doesn't hold, the restored page must run the
    same check as a Back/Forward restore.
  - Medical Single ↔ Group discards nothing and leaves the counts unchanged.

## 11. Verification

- **Organization timezone:**
  - settings accept a valid IANA id, accept null (clears it), and reject an invalid id or a
    non-identifier offset;
  - only the Owner can change it;
  - the page receives the current value and the identifier list;
  - a provisioned organization has no timezone and resolves to UTC.
- **Resolver:** an unset timezone gives the UTC date. An override gives the local date. At a frozen
  instant just after local midnight but before UTC midnight (e.g. 22:30 UTC with `Asia/Beirut`),
  "today" is the local next day; the reverse applies west of UTC. Stored policy dates are unchanged
  after a timezone change.
- **Display status:** with frozen time, test the boundary days (effective = today, expiry = today,
  effective = tomorrow, expiry = yesterday, a single-day term) for the resource value **and** each
  filter value, under both UTC and an override. Include the local-midnight instant, where UTC and
  local dates differ and both paths must flip together. Stored Cancelled/Frozen win. Invalid filter
  values are rejected.
- **Display status surfaces:** the index, Show, client policies card, Excel (column and filter
  matching the index) and all six PDFs render the display label. No surface renders "Active".
- **Renewing soon:** the existing edge tests pass under an organization timezone, plus a
  local-midnight edge case.
- **Status preservation:** Store ignores a posted `status` and stores Active. Update on a Cancelled or
  Frozen policy keeps it, including when `status` is posted.
- **Store response:** a successful class store response carries Inertia's `clearHistory` flag. The
  `policies.create` and class `create` responses are history-encrypted; other routes aren't.
- **Create flow (server):**
  - the class Create page resolves the carried-over values and labels;
  - it ignores tampered, other-organization or archived values;
  - it sends the user back to step 1 when a required one is missing;
  - step 1 defaults the class to Medical;
  - `CreateTest` is updated.
- **Client search:** organization scoping, active only, nothing under 2 characters, at most 10
  results, `ClientFilter` fields matched, guests and authorization rejected. Edit keeps an archived
  assigned client valid.
- **Discount:** the new Update regression test (above the premium rejected, equal accepted).
- **Frontend (manual, by the owner):**
  - step 1 → step 2 → Back → Continue with the same class (all entries back, Medical health
    fields included) and with a different class (common fields back, class-specific discarded);
  - switching back (Medical → Fire → Medical) keeps the common fields but leaves the Medical
    fields empty;
  - a carrier change discards the branch, and switching back to the first carrier leaves it empty;
  - Medical Single ↔ Group keeps both sections, and only the selected one is submitted;
  - a step-2 validation error keeps the entries;
  - a hidden-field error (e.g. archive the client between steps) shows on the summary, and the
    correction keeps the work;
  - browser Back/Forward within an unfinished flow, with no class or carrier change, restores
    every entry;
  - Medical → Fire → Medical, then browser Back through the older Medical step-2 and step-1
    pages and Forward again: the Medical-specific entries stay empty on every page, and the common
    fields are as each page had them;
  - carrier A → B → A, then browser Back/Forward through the older pages: the issuing branch stays
    empty, and every other entry is kept;
  - a class change alone keeps an older page's branch; a carrier change alone keeps its
    class-specific entries;
  - after Cancel, and after a successful create, browser Back (including a bfcache restore) shows
    no previous entries;
  - a bfcache restore (e.g. leave the flow for another site, then press Back) mid-flow after a
    class or carrier change shows no discarded values, in Chrome and Safari (§10 B3 assumption);
  - a full refresh mid-flow keeps the step-1 choices (URL) but loses unfinished entries;
  - entry from a client page;
  - Edit with an archived client, and clearing the client;
  - the typeahead's 2-character gate, debounce and clear button;
  - the design-docs example;
  - the timezone picker;
  - Show layouts for all six classes and the Carrier stats card;
  - the relationship placeholder.
- Run `vendor/bin/pint --dirty`, the affected Pest files, then the full suite.
