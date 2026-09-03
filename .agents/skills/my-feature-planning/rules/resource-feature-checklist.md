# Resource / CRUD Feature Checklist

Load this when `rules/feature-classification.md` says **A (new resource)**, or for the resource-shaped
slice of **C (extension)**.

For shape A, every track below must be explicitly resolved — applicable or not — for a new resource,
never silently assumed. Mark "not applicable" outright rather than skipping a track quietly. For a
resource-shaped C, walk only the tracks the changed slice actually affects — never replan the whole
parent resource for one changed slice.

The track structure below (A–G) is the portable part: core resource concerns, status/lifecycle, child
or related resources, filtering and sorting, export/output, user-facing surfaces and UX consistency,
and integration/tooling completeness. Every resource-shaped feature needs some answer to each of these,
in any stack, on any application. What's stack-specific — the concrete storage engine, authorization
mechanism, interface layer, UI components, and tooling — is supplied by the consuming project: its own
stack-specific skills and established conventions determine implementation shape once planning is done.
This checklist asks the questions; it does not answer them on the project's behalf, and it does not
prescribe an issue count or implementation shape.

## Track A — Core resource contract

Ask: what does this resource need — to exist, be found, be acted on, and be trusted — independent of
any particular stack? Always applies. Resolve every question below explicitly:

- What gives the resource its identity?
- What state/data and relationships must it hold?
- What ownership, tenancy, or isolation boundary applies?
- What constraints and invariants must storage preserve?
- What operations must be available?
- Who may perform each operation?
- What input validation and output/interface contracts are needed?
- What representative fixtures or development data are needed, if any?
- Where must the resource become discoverable or reachable (navigation, routing, listing)?
- What behavior needs proof (tests or another form of verification)?

The consuming stack determines the concrete artifacts that satisfy these outcomes; artifact mapping does
not belong to this checklist.

## Track B — Lifecycle and state transitions

Ask: does the resource have states or transitions beyond simple creation, update, and removal? Archive
and restore — a soft-hidden, recoverable state distinct from permanent deletion — is one common generic
example, but not every resource needs it, and it's not the only shape this track covers (a workflow
with ordered stages, a publish/unpublish toggle, and a review/approval status are all the same kind of
question).

If yes, resolve:
- What each state means, and what triggers each transition.
- Who is authorized to trigger each transition.
- How visibility and default-query behavior change by state (see Track D — don't ship a new state
  without also planning how records in that state get filtered or surfaced).
- Whether each transition is reversible.
- Whether removal is destructive or recoverable, and who can do which.
- What user-facing controls and feedback the transition needs.
- What downstream effects a transition has on related data or other capabilities.
- What behavior needs proof.

Treat user-facing wiring for a transition as a separate concern from the backend endpoint that performs
it — a backend transition endpoint shipping without a way to trigger it from the interface is a common
gap. Don't assume the interface wiring rides along automatically with the backend issue; plan it
explicitly.

## Track C — Child or related resources

Ask: does a related entity have an independently meaningful lifecycle or management surface of its own
(e.g. an order and its line items, a project and its members)? Not every resource has this — don't
assume it away, and don't assume it's needed either.

If yes, resolve:
- The relationship and its ownership (which side controls creation/removal of the other).
- Whether the child actually warrants independent identity and its own operations, versus being plain
  embedded data on the parent.
- What authorization boundaries apply to the child, and whether they differ from the parent's (a
  common nuance: creation may be authorized differently from update/delete).
- Creation, update, and removal behavior for the child.
- Whether removing the parent cascades to the child or the child is preserved.
- Where the child is managed (its own surface, embedded in the parent's, both).
- Whether the parent's initial creation captures one instance of the child differently from how the
  child is maintained afterward — and if so, whether that shortcut persists or is later replaced by the
  child's own full management surface.
- What dependencies and proof requirements apply.

The child may need the same kind of core-contract questions as Track A, but only to the extent its
actual shape calls for them — this is not a mandate to replan a full Track A artifact-by-artifact for
every child entity regardless of size.

## Track D — Querying, filtering, and sorting

Ask: what list/query behavior do users or consumers actually need? At minimum, consider search and any
state-based visibility toggle (see Track B). Additional filters and sort fields are feature-specific —
don't assume the previous resource's filter set applies here.

Resolve:
- What's searchable, and over which fields or behaviors.
- What's filterable.
- What's sortable, and what the default sort is.
- What the default filter state is, especially its interaction with lifecycle/state (Track B) — a
  common default is excluding non-default-state records unless explicitly requested.
- Whether query state (filters, sort, pagination) needs to be preserved across navigation.
- How this is expressed at both the backend/interface boundary and to the user.
- Performance constraints — only when there's actual evidence they matter, not by default.
- What behavior needs proof.

## Track E — Export and other output forms

Ask which output shapes apply. Treat bulk/collection output and single-record output as separate
planning questions with their own answers below — not automatically as separate GitHub issues, and not
as one undifferentiated "export feature" either.

For each applicable output, resolve:
- Its intended audience and purpose.
- Its format (e.g. a spreadsheet, a document, a structured data file).
- Which fields are included, and what sensitive-data boundaries apply.
- Who is authorized to produce it.
- Whether collection output respects the caller's current filter/sort state (Track D).
- How it's delivered to the requester.
- Scale or asynchronous-processing concerns, when the data volume or generation cost actually warrants
  them.
- Failure and empty-state behavior.
- What behavior needs proof.

Route or handler precedence between an export endpoint and a wildcard record-lookup endpoint is a
general integration concern, not specific to export — see Track G.

## Track F — User-facing surfaces and UX consistency

Ask: what must a user actually see, do, and be told across this resource's surfaces, and does it match
how the rest of the product already behaves?

Resolve:
- Which user-facing surfaces are actually needed (list, detail, create, edit, or others).
- Navigation and discoverability — how a user gets to each surface.
- The interactions each surface supports.
- Empty, loading, error, and permission-denied states for each surface.
- Responsive behavior across device/viewport sizes the product supports.
- Accessibility.
- How lifecycle/status (Track B) is represented to the user.
- What actions are available on each surface, and their ordering and visibility — including that an
  action for a capability that doesn't exist yet shouldn't ship as a disabled or stubbed control; leave
  it out until it's real.
- Consistency with the consuming project's established design system and already-shipped patterns for
  comparable surfaces.

If frontend/UI surfaces are in scope, run `rules/design-reconciliation.md` — this is not discretionary
once that condition is met. If the resource has no frontend/UI scope, this track may be explicitly
marked not applicable.

This checklist asks which of the consuming project's conventions apply to each surface; it does not
carry those conventions itself — the concrete answers (breakpoints, component choices, status-tone
palette, exact wording) live in the consuming project's design system and existing shipped pages.

## Track G — Integration and tooling completeness

Ask: what non-obvious registration, generation, precedence, build, or tooling steps are required for
the resource to work correctly and remain maintainable? These are categories to check against the
consuming project's actual stack, not a mandatory list to invent items for — resolve "not applicable"
for whichever don't apply here.

Check:
- Route or handler precedence (e.g. a specific-path endpoint that must be registered before a
  wildcard/catch-all endpoint that would otherwise swallow it).
- Registration in any manifest, configuration, or index the project maintains by hand.
- Generated clients, types, forms, or routes that need regenerating.
- Imports or discovery mechanisms the resource must participate in to be found at runtime.
- Caches or other generated artifacts that go stale when this resource changes.
- Build or runtime integration steps.
- Repository-specific diagnostics or quality checks that apply to new code.

Record any known concrete requirement in the relevant canonical issue definition. Implementation-time
command detail (the exact tool invocation, flags, or IDE-specific handling) stays with the consuming
project's own tooling skills — this checklist only needs to flag that the step exists and where it
belongs.
