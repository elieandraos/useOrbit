# Policy forms & show page — UX feedback investigation (DRAFT, for review)

> **Status: draft for owner review — NOT approved, NOT yet the source of truth for `plan-it`.**
> It records a `lab-it` investigation of current `main` (as of 2026-10-03) plus recommendations
> and the decisions still needed. Once the decisions below are answered, this section gets
> re-synthesized into an approved plan with locked decisions before `plan-it` runs.

## Summary

UX feedback on the new-policy flow, the class forms and the policy Show page, plus four domain
questions. In short:

- **Typeahead:** a generic `Typeahead` already exists. It needs a selected-state slot and a clear
  action, and clients need a server-side search endpoint. Loading every client into the browser
  doesn't scale, and that is how all policy forms work today.
- **Status:** a stored, hand-set enum (Active / Cancelled / Frozen) with no link to the dates. A
  future-dated or long-expired policy both show "Active". Recommendation: keep the stored status
  for human decisions only, drop it from create, and derive the term state (Upcoming / In force /
  Expired) from the dates when read.
- **Discount > premium:** already rejected server-side and tested. It's only missing a browser
  hint and an Update-path test.
- **Coverage period status:** the same field as step 1's status, asked again. It appears on all six
  class forms, not just Group Medical.
- **Show page reorder / strip / bordered headers and the relationship placeholder:** coherent with
  the profile-page patterns.

---

## 1. Observed current behaviour (current-state facts)

### 1.1 New policy — step 1 (`resources/js/pages/policies/Create.vue`)
- Insurance class starts empty: `class: props.selected.class ?? ''`. It's only pre-filled from `?class=`.
- Step 1 collects type, class, client, carrier, agent, status and source, then visits the class
  Create route with them as query params. Step 2 (each `Policy*Form.vue`, which is also the Edit
  form) shows type, parties, status and source again as editable fields, pre-filled from those params.

### 1.2 Typeahead primitive
- `resources/js/components/ui/typeahead/Typeahead.vue` already exists and is generic
  (`TypeaheadOption { value, label }`). It has two modes:
  - static `options`, filtered in the browser;
  - async `search(query) => Promise<TypeaheadOption[]>`, debounced, discarding out-of-order
    responses through `requestId`.

  It also has `initialLabel` (to show a saved value), `loading`, `name` (hidden input), `size`
  and `disabled`.
- **Async mode is not used anywhere in the app yet.** Static mode is used for countries and states
  (`ClientForm`, `AgentForm`, `CarrierForm`, `BranchModal`, `settings/Organization`, `PolicyFireForm`,
  `PolicyExpatForm`). Async appears only in `design-foundation/typeahead`.
- Gaps compared with the requested UX:
  - the selected value shows only as plain text in the input, with no avatar-and-name presentation;
  - there's no clear action, so the value can't go back to `null`. Typing over a selection
    restores the old label on close;
  - result rows can only show `label`.

### 1.3 How party options load
- `App\Support\Policies\PolicyFormOptions::shared()` sends **every** active client (plus the one
  already on an edited policy) to step 1, all six class Create pages and all six class Edit pages.
  Carriers and agents load the same way. Carriers also include their branches.
- Clients are rendered in a native `Select`, both in step 1 and in the shared
  `resources/js/pages/Policies/partials/PolicyPartiesSection.vue`.
- Server validation is already correct for search-based selection. `PolicyValidationRules::assignablePartyRule`
  requires the client to be in the user's organization and either active or the one already on the policy.
- Pieces that can be reused:
  - `App\Filters\ClientFilter::search()` (first, middle and last name, company name, phone, email);
  - the `App\Http\Controllers\World\StatesController` JSON pattern, `{data:[{id,name}]}` with a
    FormRequest. Its `RanksSearchResults::rankAndCap()` caps results in PHP after loading every
    match. Fine for states, not suitable for clients.
- `Client` is org-scoped through `BelongsToCurrentOrganization`. `ClientPolicy::viewAny` and
  `PolicyPolicy::create` both just require an organization.
- Arriving from a client page passes `?client_id=`. `PoliciesController::create` resolves only the
  **id** (`selected.client_id`), never the name.

### 1.4 Policy status
- `App\Enums\PolicyStatus`: `Active`, `Cancelled`, `Frozen`. It's a stored column, cast on `Policy`.
- Validation: `required` + enum. Every Store/Update request defaults it to Active when it's missing
  (`prepareForValidation`).
- Status has **no** relationship to `effective_date` or `expiry_date`. Nothing in validation,
  scheduling or display connects them. A future-dated policy and one that expired last year both show "Active".
- Shown as a badge on the index table and the Show header (`policyStatusTone`), and used as an index filter.
- The only date-based concept is on the Agent overview: `AgentsController` "renewing soon" is
  `status = Active` and `expiry_date` within 30 days. Its docblock says it is *"a read-time concept
  derived from `expiry_date`, never stored."*
- `bound_at` exists on `policies` (nullable date) and is set by the factory, but no form, action or
  view uses it.

### 1.5 Status in "Coverage period & status"
- It's the same `policies.status` field, re-asked in step 2 and pre-filled from step 1's choice.
- The card exists on all six class forms (Medical, Automotive, Expat, Fire, Life, Travel). It isn't
  specific to Group Medical. Lead source is asked twice the same way.

### 1.6 Discount vs premium
- Server: `'discount_amount' => ['nullable', ...policyAmountRules(), 'lte:premium_amount']` in the
  shared `App\Concerns\PolicyValidationRules`, so it covers every class, on both store and update.
- Tests (`tests/Feature/Http/Policies/MedicalStoreTest.php`):
  - *"a discount greater than the premium is rejected"* (1200.01 against 1200);
  - *"a discount up to the premium is accepted"*. A discount equal to the premium, giving a net
    premium of 0, is allowed on purpose.

  There's no Update-path test.
- Browser: the discount `Input` in `PolicyFinancialsSection.vue` has `min="0"` and no `max`. The
  error only appears after submitting.
- Storage: `decimal(15,2) default 0`, with no DB check constraint. The Create and Update actions
  store `?? 0`.
- Net premium = `premium − discount` is always computed when read: in `PolicyResource::net_premium`,
  `PolicyFilter::NET_PREMIUM`, the Excel export and the PDFs. Given validation, it can't go negative
  through the app.

### 1.7 Policy Show page
- All six `Policy*/Show.vue` pages:
  - main column: `<ClassDetailCard>` then `PolicyFinancialsCard`;
  - sidebar: `PolicyPartiesCard` then `PolicyTermCard`.
- `PolicyFinancialsCard` has `CardHeader bordered` with the title "Financials" and three `StatCell`s.
  The six `*DetailCard`s use plain (unbordered) `CardHeader`s.
- The profile-page patterns are:
  - main-column cards use bordered headers (Client and Agent `PersonalInformationCard` and
    `ContactCard`, Carrier `CarrierInformationCard`, `BranchesCard`);
  - sidebar cards use plain headers (`EnrollmentCard`, `PoliciesRenewalCard`);
  - `Agents/partials/QuickStatsCard.vue` is a **headerless stat strip**, but
    `Carriers/partials/QuickStatsCard.vue` has a bordered header. The two are already inconsistent.

### 1.8 Group Medical covered-member relationship
- A free-text `Input` with no placeholder (`PolicyMedicalForm.vue`), validated as `string|max:20`.
- `EmergencyContactRelationship` (spouse, parent, child, sibling, friend, other) exists for client
  emergency contacts. It doesn't fit insurance cover: no Employee, and Friend and Sibling don't apply.

---

## 2. UX / product findings
- Defaulting the class to Medical is low-risk, because step 2 is clearly class-specific. The
  step-1 "Choose an insurance class" error then becomes effectively unreachable.
- Asking for status on create is the real problem, more than the duplicate field itself. When
  creating a policy, the user picks a status that the dates may already contradict.
- The requested Show layout matches the profile pages: a headerless stat strip on top, then
  main-column detail cards with bordered headers. Sidebar cards stay plain.
- "Husband" is the same as "spouse". A better placeholder is `e.g. Employee, Spouse, Child`.

## 3. Domain findings
- **Status and the term are separate concepts.**
  - Cancelled and Frozen are human decisions that dates can't produce.
  - Upcoming, in force and expired come purely from `effective_date` and `expiry_date`.
  - Storing a time-based value (for example adding `Expired` to the enum) would need a scheduled
    job and create a second source of truth. That contradicts the existing "computed when read,
    never stored" precedent from the agents' "renewing soon".
- Free-text relationship values will drift ("Wife", "spouse", "SPOUSE"). An enum would fix that,
  but it is a separate data-model change.
- A client typeahead only solves the scale problem if the client list stops being sent to the
  page. Wrapping today's full list in a filtering typeahead would not.

---

## 4. Recommendations (proposed, not approved)

1. **Default class:** pre-select Medical on step 1 unless `?class=` says otherwise.
2. **Typeahead (generic, extend rather than replace):** add to the existing `Typeahead`:
   - an optional `#selected` slot. When a value is set, it replaces the input with consumer-rendered
     content plus a clear button. Clearing emits `null`, shows the input again and focuses it;
   - an optional `#option` slot for result rows;
   - a `clearable` prop.

   No client-specific code goes in the primitive. The avatar and name are rendered by the caller
   through the slot. Carriers and agents stay as selects; the primitive merely *could* support them later.
3. **Client search endpoint:**
   - JSON, org-scoped, active clients only, reusing the `ClientFilter::search` semantics;
   - `LIMIT ~20` in SQL, prefix matches ranked first;
   - FormRequest-validated and authorized like the other party lookups;
   - returns `{id, full_name}`.

   Then drop `clients` from `PolicyFormOptions::shared()`.
4. **Showing a selected client without the full list (derived constraints):**
   - Edit: pass `policy.client.full_name` as the label. It's already in the resource.
   - Step 1 from a client page (`?client_id=`): the server must return `{id, full_name}`, not just the id.
   - Step 2 Create pages currently read their defaults from the URL in the browser. They'd need
     the server to look up the selected client's name. **This hidden cost is the biggest part of
     the typeahead work.**
   - Browser Back (`useRemember` on step 1) must remember the label as well as the id.
   - Scope: step 1 plus the shared `PolicyPartiesSection`, which covers all 12 class Create and Edit pages.
   - An archived client on an edited policy still displays and still validates (the existing
     kept-party rule). Once cleared, it can't be found again by search, which matches today's
     "only active ones are offered" rule.
5. **Status:** keep the stored enum for human decisions only (Active / Cancelled / Frozen).
   - Remove status from both create steps. New policies are Active, which the server already defaults.
   - Keep status editable on Edit.
   - Show a date-based term state (Upcoming / In force / Expired), computed when read, next to the
     status badge.
   - Filtering the index by term state is out of scope.
6. **Discount:** server behaviour is correct and intentional (`lte`, so a net premium of 0 is
   allowed). Optionally bind the discount input's `max` to the current premium for faster
   feedback, and add an Update-path test.
7. **Show page (all six classes):**
   - put `PolicyFinancialsCard` above the detail card;
   - remove its header so it's a strip like `Agents/QuickStatsCard`;
   - give the six `*DetailCard`s `CardHeader bordered`.

   Optionally align `Carriers/QuickStatsCard` to headerless as well.
8. **Relationship:** add the placeholder `e.g. Employee, Spouse, Child` now. Defer the enum question.

---

## 5. Decisions needed from the owner

| # | Decision | Recommendation |
| --- | --- | --- |
| D1 | Status on create: drop it from **both** create steps, or only from step 2? Is there a real need to enter a policy that's already Cancelled or Frozen, for example a historical backfill? | Drop from both; Active by default; editable on Edit |
| D2 | Term state: add date-based Upcoming / In force / Expired next to the status badge, or leave the status display as it is? | Add it, computed when read and never stored |
| D3 | Typeahead with an empty query: show the first ~20 clients alphabetically on focus, or nothing until the user types (e.g. 2+ characters)? | Either keeps the scale guarantee. Owner preference |
| D4 | Step-1 duplication: reconsider separately why step 1 asks for parties and source when step 2 asks again, or only fix status this round? | Fix status only now; raise the rest as a separate question |
| D5 | Relationship: placeholder only, or plan an enum now (Employee / Spouse / Child / Parent / Other)? | Placeholder only for now |
| D6 | Carriers stat card: align it to headerless like Agents while touching the policy Financials strip? | Optional; low cost |
