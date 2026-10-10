# Policy forms: single-page Create wizard

> **Source of truth for the next `plan-it` pass.** This section records the verified current state and the
> decisions the owner approved on 2026-10-10. Implementation details still need verifying against the codebase
> when each issue is built.

## Summary

New policies are created in a **single-page wizard** with a horizontal stepper and Back / Next in a sticky footer:

1. **Policy details**
   - **Policy** card: type, class, subclass, policy number.
   - **Parties** card, in a 2×2 grid: client and agent, then insurance company and issuing branch.
   - **Term & financials** card: effective and expiry dates, currency, premium, discount.
2. **Coverage**: the class's coverage details, plus lead source in its own **Origin** section.
3. **People**, named per class: Insured / Members (Medical), Insured person (Expat), Beneficiaries (Life), Travelers
   (Travel). Automotive and Fire skip this step and have 3 steps.
4. **Review**: read-only, with an Edit link per section. Errors from the final save send the user to the step that
   holds the field.

Each Next checks that step against the same server rules as the final save, using Laravel Precognition, which is
built into Inertia 3's `<Form>` (no new dependency). Every step is a browser history entry, so browser Back/Forward
moves between steps. Every step shows the **current** values: an older history entry never brings back a value
that a class or carrier change discarded. Within the page that holds because the wizard keeps one copy of the
entries. A page the browser restores from its cache (bfcache) holds its own copy, so it first checks that it
belongs to the tab's current, active flow, and starts empty if not. The #417 hand-over and discard counters go only
after this is verified in Chrome and Safari. Cancel and a successful create end the flow, as today. A full refresh
starts over at step 1.

**Edit** stays a single page per class. It has the same cards in the same order, with the stepper as anchor links
instead of a gated sequence.

Precognition sends the form's data to the server **only to validate it**. The controller never runs, so nothing is
saved and no draft exists, and the usual login, organization and permission checks still apply. A failed check must
answer 422 JSON. Today it would redirect and flash the errors to the session, so that's fixed as part of the work.
Changing a value on a step un-completes that step and every later step; going Back without changing anything keeps
them completed. A validation answer that arrives after the values changed is ignored completely: it neither advances
nor shows errors. Only the selected class, and for Medical the selected type, submits its own fields.

**Unchanged:** the stored data, the six store/update actions and their validation rules (the store routes only gain
Precognition support, with failed checks answering JSON), history encryption, the Edit form's Medical type-change confirmation, and what Show pages
display.

---

## Current state (verified)

| Area | Fact | Evidence |
| --- | --- | --- |
| Flow shape | Create is two separate Inertia pages. Step 1 (class, type, client, carrier, agent, source) is `Policies/Create`, served by `PoliciesController@create`. Continue visits the class's own create route with the choices in the query string. | `resources/js/pages/Policies/Create.vue` (`continueToClass`); `routes/policies.php` (`policies.{class}.create`) |
| Step-2 gate | Each class's `create` action re-resolves the step-1 choices on the server through `PolicyEntrySelection::summary()`. If any are missing or no longer valid, it redirects to step 1 with a warning toast (`backToEntryQuery`). | `app/Support/Policies/PolicyEntrySelection.php`; `PoliciesMedicalController::create` (same in the other five) |
| Step-2 page | The `Policy{Class}/Create` pages render the shared class form (`Policy{Class}Form.vue`) with an `entry` prop. `PolicyEntrySummary` shows the step-1 choices read-only, with a Back / correction link. | `resources/js/pages/Policy*/Create.vue`; `resources/js/pages/Policies/partials/PolicyEntrySummary.vue` |
| Field placement | Step 2 holds policy number, subclass, issuing branch, dates, currency, premium, discount and the class sections. Lead source is on step 1. | `Policy*Form.vue` sections "Coverage", "{Class} coverage", "Coverage period"; `PolicyPartiesSection.vue`; `PolicyFinancialsSection.vue` |
| Person / beneficiary fields | Medical: insured profile (`medical.insured_*`) or `insureds[]` members for Group. Expat: `expat.full_name`, `date_of_birth`, `gender`, `nationality`, `phone`, `visa_expiry_date`. Life: `life.beneficiaries` (text) and `life.smoker`. Travel: `travel.travelers` (text). Automotive and Fire: none. | `app/Http/Requests/Policies/StorePolicy*Request.php` |
| Typed-work preservation (#417) | Each page remembers its entries in its own history entry (`useRemember`). A module-level hand-over carries them across flow navigations. A flow record with `classDiscards` / `branchDiscards` counters is stamped on every snapshot so that a restored older entry drops values discarded since. | `resources/js/lib/policyCreateFlow.ts`; `resources/js/composables/usePolicyFormEntries.ts`; issue #417 |
| Ending the flow | The create GET routes use the `inertia.encrypt` middleware. Cancel calls `endPolicyCreateFlow()` (forgets memory, `router.clearHistory()`). The six store actions call `Inertia::clearHistory()`. | `routes/policies.php`; `policyCreateFlow.ts`; `Policies*Controller::store` |
| Validation | One `StorePolicy{Class}Request` / `UpdatePolicy{Class}Request` per class, built on `PolicyValidationRules::policyRules()`. `class` is pinned to the controller's class (`Rule::in([$policyClass->value])`), so a submission must go to the selected class's store route. | `app/Concerns/PolicyValidationRules.php` |
| Form options | `PolicyFormOptions::shared()` supplies carriers (with branches), agents, types, sources, currencies and the default currency. Each class controller adds its own small enum options (`subclasses`, e.g. `coverageScopes`, `classTiers`, `genders`). | `app/Support/Policies/PolicyFormOptions.php`; `Policies{Class}Controller::formOptions` |
| Entry points | Client pages link to `policies.create?client_id=…`. | `Clients/partials/ClientPoliciesCard.vue`; `ClientPolicies/Index.vue` |
| Precognition | `@inertiajs/vue3` 3.7.0 depends on `laravel-precognition`. Its `<Form>` exposes `validate({ only, onSuccess, onValidationError })`, which the Inertia docs present for wizard steps. Laravel ships the server side (`HandlePrecognitiveRequests`). The app doesn't use it yet. | `node_modules/@inertiajs/vue3/package.json`; Inertia v3 docs "Forms → Precognition" |
| Precognition on the server | `#[Authorize]` is controller middleware, so it runs before dispatch, along with the route's `auth` and `organization` middleware. A precognitive request then goes through `PrecognitionControllerDispatcher::dispatch()`. That resolves the method's parameters, which runs the form request's `authorize()` and `rules()`, then aborts with `204 Precognition-Success`. **The controller method body never runs**, so no action `handle()`, flash or `clearHistory`. The `Create*Action` is constructed but never called. | `vendor/laravel/framework/src/Illuminate/Routing/Attributes/Controllers/Authorize.php`; `…/Foundation/Routing/PrecognitionControllerDispatcher.php` |
| Precognition on the client | `laravel-precognition` aborts an in-flight validation only when a new one with the **same fingerprint** (method + URL) starts. A value change without a new request, or a request to a different store URL after a class change, doesn't cancel the earlier one. | `node_modules/laravel-precognition/dist/client.js` (`abortMatchingRequests`) |
| Tenant scoping in rules | `policy_number` is unique within `organization_id`, and parties are checked through `assignablePartyRule(…, $organizationId, …)`. Validation only reads from the database. | `app/Concerns/PolicyValidationRules.php` |
| Rules across steps | Some later-step rules read step-1 fields. Travel trip dates use `effective_date` / `expiry_date`. Automotive valuation fields use `subclass`. Medical insured fields and `insureds` use `type`. | `StorePolicyTravelRequest`, `StorePolicyAutomotiveRequest`, `StorePolicyMedicalRequest` |
| Precognition `only` with members | Checked against the real `policies.medical.store` route in a throwaway test, with `HandlePrecognitiveRequests` added and the test then deleted. Laravel filters rules **after** expanding wildcards (`FormRequest` → `getRulesWithoutPlaceholders()` → `filterPrecognitiveRules`), so wildcard keys work.<br>- `only` = `insureds,insureds.*.full_name,…` with an invalid second member and step-1 errors in the payload: only `insureds.1.full_name` and `insureds.1.date_of_birth` came back; step-1 errors were filtered out.<br>- Expanded indexes (`insureds.1.full_name`) behave the same.<br>- An empty `insureds` array needs `insureds` itself in `only` to report "Add at least one covered member".<br>- A valid step answered `204 Precognition-Success: true`, with no policy created and no session errors.<br>- A user from another organization got `client_id` / `carrier_id` errors. | `vendor/laravel/framework/src/Illuminate/Http/Concerns/CanBePrecognitive.php`; `…/Foundation/Http/FormRequest.php` |
| Precognition failures on web routes | `bootstrap/app.php` renders JSON only for `api/*` (`shouldRenderJsonWhen`). In the same check, every **failed** precognitive request answered **302 with the errors flashed to the session**, not the 422 JSON the client expects. Making precognitive requests render JSON (`$request->isAttemptingPrecognition()`) turned them into 422 JSON with no session errors. Recorded in `.ai/rules/controllers.md`. | `bootstrap/app.php` (`withExceptions`); `.ai/rules/controllers.md` |
| History encryption | Inertia stores the page object (URL, props, remembered state) in `history.state`, encrypted with a key and IV kept in the tab's **sessionStorage** (`historyKey`, `historyIv`). `clearHistory()` drops them, so older entries can't be decrypted and are re-fetched. On a bfcache restore (`pageshow` with `persisted`), Inertia re-decrypts and reloads if that fails. | `node_modules/@inertiajs/core/dist/index.js` (`handlePageshowEvent`, `clearHistory`) |
| Refresh | A full reload loses the remembered step-2 entries. The step-1 choices come back from the query string. A remembered snapshot is only restored while the in-memory flow record exists (`checkPolicyCreateSnapshot` returns null without it), so after a reload the history copies are already ignored today. | #417 "Accepted limitation"; `policyCreateFlow.ts` |
| Tests | `CreateTest` covers step-1 preselection, encryption and options. The `{Class}CreateTest` files cover the step-2 summary, the redirect to step 1 and the archived-party summary. Store/update tests cover validation. | `tests/Feature/Http/Policies/*CreateTest.php` |

## Locked decisions (approved by the owner)

1. **Stepper layout.** The create form opens with a horizontal stepper:
   - step 1 **Policy details**: Policy card (type, class, subclass, policy number), Parties card (client and agent on
     row 1, insurance company and issuing branch on row 2), **Term & financials** card (effective and expiry dates,
     currency, premium, discount);
   - step 2 **Coverage**: class coverage details, plus lead source in a separate **Origin** section;
   - step 3: the people step;
   - step 4 **Review**.
2. **Step 3 per class.** Medical: **Insured** (Single) or **Members** (Group). Expat: **Insured person** (the
   covered-person fields move out of coverage). Life: **Beneficiaries** (`life.smoker` stays in Coverage). Travel:
   **Travelers**. Automotive and Fire skip step 3 and have 3 steps. The stepper adapts as soon as the class is
   chosen.
3. **Review step.** Read-only, with no inputs. Each section has an Edit link that jumps to its step.
4. **Navigation.**
   - The footer is sticky: Cancel on the left, "Step x of n" in the middle, Back / Next on the right. On the last
     step Next becomes **Create policy**.
   - Completed steps are clickable; future steps aren't. A step with errors is marked.
   - The step-1 summary and Back link on step 2 go away.
5. **Single page.** The wizard is one page. Steps switch on the client.
6. **Per-step validation** uses Laravel Precognition through `<Form>`'s `validate({ only: [step fields] })` on each
   Next, against the same server rules as the final save.
7. **Final-save errors** mark the step that holds each field, and the user is taken to the first such step. This
   replaces the summary's correction link and the step-2 → step-1 redirect.
8. **Browser history.** Each step is a browser history entry, so Back/Forward moves between steps. **Every entry
   shows the flow's current values.** Back shows the latest values, not a copy from when that step was last shown.
   This deliberately changes #417 scenario 9 ("common fields as each page had them").
9. **Behaviour kept from #417**, implementation free to change:
   - typed work survives moving between steps and browser Back/Forward;
   - a class change drops the class-specific entries;
   - a carrier change drops the issuing branch;
   - discarded values never come back, whether by switching back, by Back/Forward, or by a bfcache restore;
   - Medical Single ↔ Group keeps both sets of entries while switching, and only the selected type's are
     submitted;
   - a validation error keeps the entries;
   - Cancel and a successful create end the flow, and browser history then shows nothing that was entered;
   - a fresh "New policy" starts empty;
   - nothing reaches the server as a draft;
   - Edit is never pre-filled from create work.
10. **Refresh** mid-flow starts over at step 1. The only prefill is the one from the entry link (e.g. `client_id`
    from a client page). Nothing is persisted to browser storage, because of the Medical health data.
11. **Edit** stays a single page per class, with the same cards in the same order. The stepper appears as anchor
    links that scroll to each section, not a gated wizard. There is one Save.

### Refinements approved on 2026-10-10

12. **No resurrection, verified before removal.** Browser Back/Forward and bfcache restores must never bring back
    entries discarded by a class or carrier change. The #417 discard machinery (`policyCreateFlow.ts`'s hand-over
    and counters, `usePolicyFormEntries.ts`) is removed only after the replacement has been verified to preserve
    this.
13. **Completion follows the current selections.**
    - Changing the class resets the completion and errors of the affected steps.
    - A Medical Single ↔ Group change also changes which steps need validating (Insured vs Members).
    - A step shown as completed must be valid for the current selections and values.
14. **No stale advance, no side effects.**
    - A Precognition response must not advance the wizard if the selections or values it validated have changed
      since the request was sent.
    - Validation keeps authorization and tenant isolation.
    - Validation saves no draft and has no other side effects.
15. **Only the selected class and Medical type submit class-specific fields**, in both step validation and the final
    save. The selected class's hidden steps can stay mounted. Inactive classes' fields are never submitted.

### Review corrections approved on 2026-10-10

16. **Completion rule:** changing a value on a step un-completes that step and every later step, and clears their
    errors. Going Back without changing anything keeps completion. Class and Medical type live on step 1, so
    changing either un-completes every step. This replaces a per-field dependency map; a little repeated validation
    is accepted.
17. **Stale responses change nothing.** A validation response that no longer matches the current state is
    discarded entirely: no advance, no error update, no completion update. For example, an old Medical failure
    arriving after a switch to Life shows no errors.
18. **Explicit check when a cached page returns.** A page restored from bfcache must check that it belongs to the
    current, still-active flow before showing any entries. If it doesn't, it starts empty. Clearing the history key
    isn't relied on as proof. Verified manually after Cancel, after a successful create, and after starting another
    policy (including in another document of the same tab).
19. **Members validation** uses wildcard `only` keys (`insureds`, `insureds.*.{field}`), as verified above.
20. **Failed precognitive checks: the narrower handler.** `shouldRenderJsonWhen` in `bootstrap/app.php` adds
    `$request->isAttemptingPrecognition() && $e instanceof ValidationException`, so only validation failures of
    quiet checks become 422 JSON. Every other response, including the `204 Precognition-Success`, is unchanged.
    Laravel passes the exception as the callback's second argument (`Foundation/Exceptions/Handler.php`).
21. **Stale responses: a fingerprint.** Next records a fingerprint of the class, the Medical type, the step, and the
    values of that step and every step before it. A response is applied only if the fingerprint taken when it
    arrives matches. `<Form>` writes returned errors itself, so the wizard also has to stop a stale failure's errors
    from showing: either it shows errors from its own list, or it clears what `<Form>` wrote. Which of the two is
    open.

## Derived constraints

| Constraint | Premises |
| --- | --- |
| Create becomes **one generic page** (`Policies/Create`) that renders every class's sections. The per-class create GET routes and pages (`policies.{class}.create`, `Policy{Class}/Create.vue`) are removed. | Decision 5 + the class is chosen on step 1 alongside fields that depend on it (decision 1). |
| The server-side step-2 gate is removed: `PolicyEntrySelection::summary()` / `backToEntryQuery()` and `PolicyEntrySummary`. `selected()` (entry-link prefill) stays, reduced to what decision 10 still needs. | Decisions 5, 7, 10 + current-state "Step-2 gate". |
| The create page carries **every class's own options**: subclasses, plus each class's coverage enums. | Single generic page + `formOptions` lives per class controller today. |
| The form's submit action **follows the selected class** (that class's store route), and so does the Precognition target. | `policyRules()` pins `class` to the controller's class. |
| **Only the selected class's sections are rendered** (`v-if` on the class). Within that class, the steps stay mounted (`v-show`) so `<Form>` serializes them. The Medical Single/Group sections keep a `v-if` on the type. Inactive classes and the unselected Medical type therefore never reach the DOM, so they're never submitted or validated. Their entries live only in memory. | `<Form>` serializes the inputs present in the DOM + decisions 9, 15. |
| The flow holds **one live copy of its entries**, in memory, and **no entries in history state**. Discards remove values from that copy. With no older copy anywhere, nothing can be resurrected, so the #417 hand-over and counters become unnecessary. They're removed only once that's verified (decision 12). See "Where state lives". | Decisions 8, 9, 12 + #417's counters existed only because each history entry held its own snapshot + current-state "Refresh" (history copies are already ignored without memory). |
| The live entries live **outside the page component instance** (module scope, or an equivalent that survives a remount). A history restore within the app may remount the page component, and the entries must survive that. | Decisions 8, 9 + Inertia restores pages from history state, and whether it remounts on a same-component pop isn't something to rely on. |
| **The decision-16 rule covers the rules that cross steps.** Every cross-step rule found reads a step-1 field (`type`, `subclass`, `effective_date`, `expiry_date`), so "a change on step k un-completes k and every later step" already invalidates every dependent step. Steps that aren't completed can't be clicked in the stepper. | Decisions 4, 13, 16 + current-state "Rules across steps". |
| **Every step validation is checked against what it sent.** Each Next records the step and the values it sent. A response is applied only if it still matches the current state: same class (so same store URL), same Medical type, same step values. Applying it means advancing, setting errors and marking completion. A stale response does none of these, so `<Form>`'s built-in error handling can't be left to write a stale failure's errors directly. The client library's same-URL abort isn't relied on. | Decisions 14, 17 + current-state "Precognition on the client". |
| **Step validation goes to the selected class's store route** with `HandlePrecognitiveRequests` added. The `auth`, `organization` and `#[Authorize('create', Policy::class)]` checks and the form request's `authorize()` all still run, and the rules keep their organization scoping. Precognition **sends the form's data to the server only to validate it**. The controller body never runs, so nothing is saved, no draft is kept, and there's no flash and no history change. **Failed precognitive requests must render as 422 JSON.** Today they would 302 and flash the errors to the session. | Decisions 6, 14 + current-state "Precognition failures on web routes",  current-state "Precognition on the server", "Tenant scoping in rules". |
| **Final-save errors map to steps** through each class's field → step mapping. The same mapping gives each step's `only` list. | Decisions 6, 7. |
| Class and carrier discards happen within the visible step-1 cards. Subclass resets when the class changes and branch resets when the carrier changes, in front of the user. | Decision 1 (class/subclass and carrier/branch on the same step). |
| History encryption (`inertia.encrypt`) and `clearHistory` on Cancel / successful store still apply to the single create route and the six store actions. | Decision 9 (ended flows leave nothing in history) + current "Ending the flow". |
| Create and Edit **share the card components**. Edit composes them on one page, without the wizard state or history entries. | Decision 11 + `Policy{Class}Form.vue` is shared by Create and Edit today. |
| Feature tests for the removed step-2 pages (summary, redirect to step 1, archived-party summary) are replaced. The store/update validation tests stay as they are. New tests cover:<br>- the per-class options on the single create page;<br>- Precognition step validation with per-step `only` subsets;<br>- an unauthorized user being refused;<br>- another organization's party or policy number failing validation;<br>- a successful validation creating no policy and no flash;<br>- a failed validation answering 422 JSON with no session errors;<br>- Members validation with wildcard `only`. | Removed routes + decisions 6, 14. Approval is needed before deleting tests, per project rules. |
| **A cross-document flow marker is needed for decision 18.** A bfcache-restored document has its own memory and doesn't know that another document of the same tab started or ended a flow. Only sessionStorage, which is shared per tab, can tell it. The marker holds an **opaque flow id, never entries**. Decision 10's "nothing persisted to browser storage" is about entries. Starting a flow sets the marker, ending one clears it, and a restored page whose flow id doesn't match starts empty. | Decision 18 + bfcache restores a whole document as it was + current-state "History encryption" (sessionStorage is per tab). |
| **Removing the old machinery is gated on manual verification.** The #417 scenario list, adapted to one page, is checked in Chrome and Safari before the removal lands. It covers discards across browser Back/Forward and bfcache, ended flows, refresh, and the decision-18 returns after Cancel, after a successful create, and after starting another policy. These are browser behaviours that feature tests don't cover. | Decision 12 + #417 "Tests" (manual). |

## Open implementation details

These are left to `plan-it` / implementation. Every option preserves the decisions above.

- **How steps become history entries**: e.g. `router.push` client-side visits with a `?step=` query, or another
  mechanism. If someone lands on a later step with no flow in memory (refresh, a stale entry), they go back to
  step 1.
- **How the Review step renders**: reuse the Show page cards (`PolicyPartiesCard`, `PolicyFinancialsCard`,
  `PolicyTermCard`, `{Class}DetailCard`) fed from the live entries, or a dedicated read-only layout.
- **What an older history entry from an earlier, abandoned flow shows** within the same document: empty, or the
  current flow. It must never show discarded values or an ended flow's data. A bfcache return is covered by
  decision 18.
- **How class-specific options are delivered**: all up front, or a partial reload on class change.
- **Whether the new `Stepper` gets a design-foundation docs page** (no stepper exists in `resources/js/components/ui`
  today).
- **How Edit's anchors are built** (scroll-spy or plain links).

## Where state lives

| State | Where it lives | Refresh | Cancel or successful create |
| --- | --- | --- | --- |
| **Live entries**: every field typed in the wizard, both Medical type sets, which steps are completed, the current step | **JavaScript memory only**, outside the page component. Never in history state, never in session/local storage, **never saved as a draft**. They reach the server only when sent for validation or the final save. | Lost. The page starts over at step 1 with only the entry-link prefill. | Emptied in memory. |
| **History entries**: one per step | `history.state` holds Inertia's page object (URL with the step, page props). It's **encrypted** (`inertia.encrypt` on the create route), with the key and IV in the tab's sessionStorage. It holds **no typed entries**. | The key survives, so the entries still decrypt. They hold only props and the step, and a step past 1 with no flow in memory goes back to step 1. | `clearHistory()` (on Cancel, client side; on a successful store, `Inertia::clearHistory()`) removes the key. Older entries can't be decrypted and are re-fetched fresh. |
| **bfcache snapshot** | The browser keeps the whole document, memory included, as it was when you left: that document's latest entries, after any discard it made. **This is a second copy.** It can be stale if another document of the tab has since started or ended a flow. | n/a | On `pageshow` with `persisted`, the page checks the sessionStorage flow marker (decision 18). A missing or different marker means it starts empty. Inertia's own decryption failure after `clearHistory` is a second line of defence, not the guarantee. |
| **Flow marker** | sessionStorage, per tab: an opaque id for the active flow, **no entries**. | Survives, but entries don't. The page starts a new flow. | Cleared. |
| **Precognition request** | The form's current data is sent to the selected class's store route **only to be validated**. The server answers 204 or 422 JSON and keeps nothing: no draft, no session errors. | n/a | n/a |

**Within one document**, nothing can be resurrected: the only copy is the live one, and discards remove values from
it. **Across documents**, a bfcache-restored page holds its own copy, and the flow marker check (decision 18)
decides whether it may be shown. Both are checked in Chrome and Safari before the old machinery is removed
(decision 12).

## Proposed component shape (for review)

Legend: **[new]** is a proposed component, **[existing]** keeps its name and role, **[existing, reshaped]** keeps
its name with changed contents, and **shared** means Create and the six Edit pages both use it.

`resources/js/pages/Policies/Create.vue`, the single create page:

```vue
<template>
    <Head title="New policy" />

    <div class="flex flex-1 flex-col">
        <PageHeader title="New policy" />                        <!-- [existing] -->

        <Stepper :steps :current="step" :completed :errored      <!-- [new] components/ui/stepper, generic -->
                 @select="goToStep" />

        <Form :action="storeRoute" #default="{ validate, errors }">    <!-- action follows the selected class -->

            <!-- Step 1 · Policy details -->
            <div v-show="step === 'details'">
                <PolicyDetailsSection />      <!-- [new, shared] type, class, subclass, policy number -->
                <PolicyPartiesSection />      <!-- [existing, reshaped, shared] 2×2, now holds PolicyClientTypeahead -->
                <PolicyFinancialsSection />   <!-- [existing, reshaped, shared] "Term & financials": + effective/expiry -->
            </div>

            <!-- Steps 2–3 · only the selected class is rendered; a class change unmounts the old one -->
            <component :is="classSections[entries.class]" :step="step" />

            <!-- Last step · Review, read-only -->
            <PolicyReviewStep v-show="step === 'review'" @edit="goToStep" />   <!-- [new] Create only -->

            <!-- Sticky footer, inline: Cancel · Step x of n · Back / Next | Create policy -->
            <footer class="sticky bottom-0 …">…</footer>
        </Form>
    </div>
</template>
```

`classSections` maps a class to one **[new, shared]** `Policy{Class}Sections` component per class, which holds that
class's coverage and people steps. Taking Medical as an example:

```vue
<!-- PolicyMedical/partials/PolicyMedicalSections.vue -->
<template>
    <div>
        <div id="coverage" v-show="step === 'coverage' || step === 'all'">
            <FormSection title="Medical coverage">…</FormSection>   <!-- [existing] ui -->
            <PolicyOriginSection />                                 <!-- [new, shared] lead source -->
        </div>

        <div id="people" v-show="step === 'people' || step === 'all'">
            <FormSection v-if="type === 'single'" title="Insured">…</FormSection>
            <FormSection v-else title="Members">…</FormSection>     <!-- v-if: only the selected type submits -->
        </div>
    </div>
</template>
```

Automotive and Fire have only the coverage group. Expat's people group is the insured person, Life's is
beneficiaries and Travel's is travelers.

`PolicyMedical/Edit.vue` (the other five follow the same pattern), one page with no gating:

```vue
<template>
    <PageHeader />                                               <!-- [existing] -->
    <Stepper :steps mode="anchors" />                            <!-- [new] same component, scroll links -->

    <Form :action="update">
        <PolicyDetailsSection :class-locked="true" />            <!-- shared -->
        <PolicyPartiesSection />                                 <!-- shared -->
        <PolicyFinancialsSection />                              <!-- shared -->
        <PolicyMedicalSections step="all" />                     <!-- shared: coverage, origin, people -->
        <footer>Cancel · Save</footer>
        <DiscardTypeDataModal />                                 <!-- [existing] Edit-only type-change confirmation -->
    </Form>
</template>
```

Why each new component exists:

- **`Stepper`**: one generic UI component, used by Create (gated) and by the six Edit pages (anchors).
- **`PolicyDetailsSection`**: the new step-1 Policy card. Create and six Edit pages use it.
- **`Policy{Class}Sections`** (6): replaces the class-specific part of today's `Policy{Class}Form.vue`. Create shows one
  step at a time; Edit shows them all.
- **`PolicyOriginSection`**: one field, but used inside all six class sections. Its placement (end of Coverage) keeps
  Edit in the same order as Create.
- **`PolicyReviewStep`**: Create only. Whether it reuses the Show cards is open.
- **Not separate components**: the sticky footer (a few buttons, Create only, inline) and the step wrappers (plain
  `div v-show`). The state lives in a **[new]** `usePolicyWizard` composable, not a component.

**Removed:** `Policy{Class}/Create.vue` (6), `PolicyEntrySummary.vue`, the hand-over and counter parts of
`policyCreateFlow.ts` (the `clearHistory` on Cancel stays), and `usePolicyFormEntries.ts` (gated on decision 12).
`Policy{Class}Form.vue` (6) is split into the shared sections composed by each Edit page.

