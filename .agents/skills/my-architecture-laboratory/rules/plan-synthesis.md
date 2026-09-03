# Synthesizing a plan.md

This is the methodology for the Plan Synthesis track referenced from `SKILL.md`. It converts
verified current-state evidence and explicit, approved target-state decisions into an
initiative-specific `plan.md` handoff for `my-feature-planning`. The same procedure applies to a
narrow fix, a major redesign, a new integration, a migration, security-shaped work, or a
single-purpose system.

## What this is for, and what it is not for

Plan Synthesis consolidates reasoning that is already complete. A `plan.md` produced here lets
`my-feature-planning` start from settled architecture and settled product decisions instead of
reconstructing them from conversation history. It is:

- A **consolidation** of what's already been investigated and decided — not a place to do new
  investigation or make new decisions.
- **Implementation-aware** — it names real evidence the eventual issues will touch — but **not an
  implementation checklist**. It states what must be true, not the literal sequence of edits.
- The **source of truth** for the subsequent feature-planning pass. Once approved,
  `my-feature-planning` should not need to re-derive architecture or re-litigate decisions from
  this conversation.

It is not:
- An architecture guide. A guide explains verified existing architecture for ongoing
  understanding and may document any architecture shape — not necessarily a reusable capability. A
  plan records the approved target architecture and decision boundary for one implementation
  initiative and has no reason to outlive it.
- A place to classify the feature, scope it, reconcile it against design files, decompose it into
  issues, sequence delivery, or touch GitHub — all of that is `my-feature-planning`'s job, done
  *after* the plan is approved.
- A place to do implementation planning or make new product/architecture decisions.

## Preconditions

Don't start writing until both of these are actually true — don't paper over a gap in either:

1. **A real current-state investigation exists.** Concrete evidence — paths, symbols, schema
   elements, configuration, tests, runtime behavior — not conventions or assumptions.
2. **The user has made explicit decisions about the target state.** Not implied by the
   investigation, not inferred because a solution seems obvious or a draft looks polished —
   approval stated by the user, in the active conversation or another reliable, identifiable
   context you can point to.

If either precondition is missing, stop synthesis and return to the applicable investigation or
decision conversation through `SKILL.md`. Plan Synthesis must not paper over missing evidence, and
must not embed an unresolved product or architecture decision in the plan as though it were an
implementation detail.

## The locked-vs-open decision rule

This is the single most important discipline in Plan Synthesis, and the one most likely to be
violated by accident under time pressure. Every material claim in the plan falls into exactly one
of four categories, kept visually and textually distinguishable — a table, labels, headings, or
another clear structure all work; never let a paragraph blur two of them together:

- **Current-state fact** — verified present reality, confirmed against the codebase or other
  authoritative evidence. Falsifiable by checking that source right now.
- **Locked decision** — a target-state product or architecture choice explicitly approved by the
  user. Its authority comes from that approval, and it changes only through another explicit user
  decision.
- **Derived architectural constraint** — a necessary consequence of verified current-state facts
  plus one or more locked decisions. State its premises so downstream planning can detect a stale
  one later — e.g. removing a code path implies its consumer needs a new data source; that
  dependency is the premise.
- **Open implementation detail** — an unresolved implementation choice whose viable outcomes all
  preserve the approved target architecture and guarantees. Left for feature-planning or
  implementation to resolve against actual codebase conventions at build time.

A material product or architecture decision cannot remain classified as an open implementation
detail. Use this test: if changing the choice would alter approved behavior, guarantees,
operator/user promises, security boundaries, ownership, lifecycle, or the target architecture, it
is material — resolve it with the user before synthesis. If every viable choice preserves those
outcomes, it may remain open.

The test turns on consequences, not on what kind of choice it is. A column type, which of two
classes absorbs a few lines of logic, a CLI flag name, or a choice between UI treatments can be
open or material depending on what changing it would break — don't treat any category of choice as
inherently implementation-only.

**Never silently convert an open implementation choice into a locked product decision** by writing
it as though the user had approved one specific solution. If a section must reference an undecided
detail, name the open question and its candidate options inline rather than picking one.

Record an open detail only when leaving it open materially helps downstream planning — not for
every ordinary coding choice, and not to manufacture symmetry with the locked decisions. Don't
require candidate options to be exhaustive; name known viable options only when doing so clarifies
the remaining choice. If everything about a section really is settled, say so and move on.

## Plan content model

Structure follows the initiative, not a fixed template — three short sections for a narrow change
is correct; forcing a large heading inventory onto it is the failure mode to avoid. No exact
headings, order, or one-section-per-concern are required, but the following must be communicated
somewhere, in whatever structure is clearest:

- the initiative and the problem or goal;
- the verified current state relevant to the change;
- the approved target architecture;
- the locked decisions;
- any derived constraints;
- what must remain true or unchanged;
- the conceptual change boundary — what gets introduced, removed, moved, or rewired;
- material open implementation details, if any;
- the evidence grounding the current-state claims.

Include the following only when relevant to this initiative: security, failure, or recovery
behavior; lifecycle or before/after comparison; test and behavioral consequences; external
dependencies; limitations or migration constraints.

It must not become a file-by-file edit sequence, an implementation checklist, an issue
decomposition, or a delivery plan, regardless of heading structure.

## Leading with an owner-readable summary

The user approving the plan may be technically capable but unfamiliar with the specific system,
tooling, or investigation area it covers. When the plan is long, technically dense, or reaches into
a domain the approving owner isn't fluent in, open it with a short, plain-language summary before
the technical content, so approval doesn't require first reading through unfamiliar mechanics and
evidence to find the decision being asked for.

Cover whatever of the following actually applies to the initiative: the outcome, the motivation, the
target shape, the normal operating model going forward, material trade-offs, and what stays
unchanged or is explicitly excluded. Place the detailed evidence, mechanics, and verification
procedures below it, unchanged in role — the summary orients the reader toward the technical record;
it never substitutes for it as the source of truth.

The summary must never replace, weaken, or silently reinterpret a canonical claim, a locked
decision, a stated uncertainty, or an evidence reference — restate them in plainer language, don't
re-decide or soften them. A short, already-straightforward plan doesn't need a redundant mandatory
summary merely for symmetry with denser ones. No fixed heading, line count, or template is
required — the requirement is an understandable approval surface, not one project's formatting
choice. When the conversation establishes the approving owner's familiarity with the domain,
calibrate the summary's depth and vocabulary to that owner rather than to a generic reader.

## Evidence

Ground current-state claims in architecture-neutral evidence: paths or symbols, schema elements,
configuration keys, protocols or contracts, runtime boundaries or observed behavior, relevant
tests, or authoritative external-system state. Don't force one evidence shape (a class-and-method
pair, a route) onto a system that expresses the same fact some other way.

Thread evidence through the claims it supports, rather than clustering it apart from them. A
separate source-reference area is optional, and should hold only evidence with no more natural
home next to a claim. Avoid brittle line-number references unless a specific line is genuinely
stable and useful.

## Where the plan lives

Default to a `plan.md` at the repository root, unless the project supplies a different convention.

Before writing, inspect whether `plan.md` already exists, and if so what it contains: the same
initiative, a different initiative, durable project context, stale material, or an empty
placeholder. Never overwrite or restructure unrelated content merely because the file exists. If
it holds a different, unrelated initiative or durable project context, add a clearly-scoped new
section rather than disturbing what's there. If it already covers the same initiative, update the
relevant section in place instead of creating a duplicate — existing content isn't immutable when
the user has asked to revise that same initiative. Ask rather than guess when ownership or
placement is materially ambiguous.

## The handoff statement

State explicitly, near the top of the initiative-specific plan or section, that it is the source
of truth for the subsequent `my-feature-planning` pass, and that implementation details still need
verifying against the codebase when individual issues are built.

This statement marks the document's intended handoff role. It is not evidence that the user has
approved the draft — only explicit user approval makes the plan canonical, per
`my-feature-planning/rules/plan-md-input.md`.

## Internal review before presenting the plan

This is a Plan Synthesis-specific review, distinct from an architecture guide's review. Before
presenting the plan for approval, confirm all of the following against your own draft:

- Every locked decision established by the applicable approved context — the active conversation
  or another reliable, identifiable prior context — appears in the plan, worded so it still
  matches what the user actually approved — not softened, not expanded, not reinterpreted.
- No material product or architecture decision is disguised as an open implementation detail.
- Current-state facts, locked decisions, derived constraints, and open implementation details
  remain distinguishable throughout.
- Each derived constraint actually follows from the premises it states.
- No superseded proposal is presented as current or approved.
- Current-state evidence references resolve against their authoritative source — the codebase,
  configuration, schema, tests, runtime evidence, or external-system state — at synthesis time.
- No "preserved behavior" claim conflicts with a described change.
- Stated invariants are compatible with the target architecture.
- When the plan opens with an owner-readable summary, it restates locked decisions, current-state
  facts, and evidence rather than softening, expanding, or reinterpreting them.
- The plan is sufficient for `my-feature-planning` to distinguish settled from open without
  re-deriving from conversation.
- The plan performs no classification, issue decomposition, sequencing, or GitHub work.

Report findings from this pass as a short list, not a rewrite, then fix what the findings surface
before presenting the plan. If review exposes missing evidence, unresolved authority, or a
material decision, stop and return to the user rather than silently resolving it.

For a later narrow correction, recheck every claim or section the correction could affect. Run the
complete review again only when the correction changes or potentially invalidates broader plan
reasoning.

## Approval gate

Present the synthesized plan — or the specific sections changed, for a revision — and wait for the
user's explicit approval.

Before approval, the plan is a draft: the handoff statement does not make it canonical, don't hand
it to `my-feature-planning`, and don't imply its decisions are settled. After approval, the
matching initiative-specific section becomes canonical input under
`my-feature-planning/rules/plan-md-input.md`. Don't invent a persistent approval marker or an
exact required phrase to check for.
