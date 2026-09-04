---
name: lab-it
description: "Investigates and validates how an existing system or capability actually works, from real implementation, tests, and current evidence — never conventions or guesses — then produces one of three results: a new architecture guide documenting existing architecture, an updated existing guide reconciled with verified current reality, or an approved `plan.md` handed to `plan-it`, capturing feature-architecture decisions reached with the user. Trigger to document existing architecture, update an architecture guide, resolve architecture or design decisions for a proposed feature, or synthesize approved findings into `plan.md`. Not for explaining one function, debugging, reviewing a diff, or writing API reference docs."
---

# lab-it

## What this skill does

This skill investigates a real system and turns the resulting understanding into one of three
results:

| User intention                                   | Result                                             |
| ------------------------------------------------ | --------------------------------------------------- |
| Understand and document existing architecture    | New Claude Artifact architecture guide             |
| Reconcile a guide with verified changed reality  | Updated existing Artifact                          |
| Design feature architecture through conversation | Approved `plan.md` handed to `plan-it` |

Investigation comes first in every workflow — whether user confirmation follows, and when, is
conditional; see "Shared investigation and decision discipline" below. Investigation and recap
alone may be the complete result: no guide and no `plan.md` gets produced just because an
investigation happened.

## Shared investigation and decision discipline

Every workflow below starts with the same evidence discipline:

1. Inspect the real current system and relevant evidence — not conventions or assumptions.
2. Reconcile implementation, configuration, schema, tests, runtime evidence, and reliable history.
3. Explain the current architecture and identify uncertainty.

> The system establishes what exists; the user decides what it should become.

Tests are strong evidence — often surfacing real boundaries faster than implementation alone —
but not infallible authority: reconcile them with the implementation and other relevant evidence,
since tests can be incomplete or stale. Implementation is authoritative for implementation facts;
configuration, schema, runtime observations, and external-system state are authoritative for
whatever they each actually govern. If asked, check issue and commit history for *why* — reliable
history explains rationale, it doesn't establish current behavior. Never document something
history says was planned but the evidence doesn't show. Do not begin drafting a guide or a
`plan.md` during this step.

What happens next is conditional, not uniform, per workflow: creating a new guide requires recap
confirmation before publication; planning feature architecture requires explicit, user-approved
decisions before writing `plan.md`; updating an existing guide asks the user only when authority,
intent, or a material decision is unclear — routine, verified current-state documentation does not
require a new product decision.

## Document existing architecture

Route: investigate the real system → recap and obtain confirmation → decide structure from the
center of gravity → load the guide-writing and Artifact-design instructions → use the template →
publish the Claude Artifact → run the architecture-guide review.

1. **Investigate.** Inspect every architectural surface the system
   actually has before writing anything. Typical concerns: persistence and data relationships;
   business rules and lifecycle behavior; request or interaction boundaries; authorization and
   security; background or asynchronous work; external integrations; user-facing surfaces and
   reusable UI logic; schema and operational constraints. These are investigation categories, not
   a fixed checklist — the consuming project supplies its own framework, directories, and concrete
   artifacts. Identify the **architectural center of gravity** — the one idea everything else
   hangs off (a polymorphic contract, a queue pipeline, a runtime subsystem boundary, a
   sync-vs-async split) — as a hypothesis the guide-writing step will need.

2. **Recap and confirm.** Write the recap directly as chat
   output, not a file or an artifact. Cover, with concrete implementation references threaded
   throughout, whichever of these actually apply to the system: the problem being solved, the core
   architecture, reusable pieces versus integration-specific code, runtime behavior, the data
   model, the integration seam, security, testing, architectural decisions, and remaining gaps.
   Don't invent a data model, runtime lifecycle, security boundary, reuse seam, or integration
   split the system doesn't have just to fill out the list. Keep it bullet-driven and honest about
   gaps. **Stop here and wait for the user to confirm before this investigation becomes a
   published guide.** A correction here is real signal about what the guide needs to get right.

3. **Write and publish the guide.** Only after the recap
   is approved:
   - Decide the document's structure from the confirmed center of gravity. Architecture guides do
     not use one fixed section inventory: structure follows the system being explained. See
     `rules/doc-style.md` for the writing grammar and content-block vocabulary.
   - Load the `artifact-design` skill (required before writing any Artifact page).
   - Write the HTML using `rules/template.html` as the starting scaffold. Replace content;
     keep the design system unless the system genuinely needs a new block type.
   - Publish with the `Artifact` tool: title `"{Capability} Architecture"`, a one-sentence
     description, and a stable, domain-appropriate favicon (see
     `rules/doc-style.md#choosing-a-favicon`).
   - Run `rules/review.md`'s checklist against the published guide before considering it
     complete — see "Output-specific non-negotiables" for what the guide itself must do.

Publishing to a Claude Artifact is specific to this workflow (and to updating an existing guide,
below) — investigating and recapping don't depend on it, and neither does the `plan.md` output of
planning feature architecture.

## Update an existing architecture guide

Use this workflow when a published guide needs reconciling with verified current reality —
triggered by a stale architectural claim, a stale evidence reference, changed configuration or
runtime behavior, or a prior documentation defect, not only a changed implementation. This is its
own workflow, not an automatic fourth stage after creating a new guide.

Route: locate the existing Claude Artifact, rather than minting a new one → compare its
architectural claims against verified current implementation, configuration, runtime evidence, and
tests → follow the complete affected claim graph, not only the section where the change was first
noticed → update to describe how the architecture works now, not as a changelog → preserve its
identity and stable presentation metadata (same `url`, same favicon) → run `rules/review.md`
against the whole updated guide, with emphasis on the changed claims and whatever depends on them.

`rules/maintenance.md` governs every judgment call in this route — read it before making any
edit. It preserves a guide's unaffected architectural meaning without freezing architecture the
evidence shows has genuinely changed: the center of gravity, structure, ownership, or reasoning can
move when verified reality requires it.

## Plan feature architecture

Use this workflow when the point of the architecture work is to prepare a real implementation
initiative, not to teach a system. It can begin from a feature idea discussed in conversation, an
architecture question, an existing investigation, or already-approved findings and decisions.

The workflow may need to:

- investigate how the current system supports or constrains the proposed feature;
- discuss viable target approaches with the user;
- distinguish current facts from proposed choices;
- obtain explicit decisions for material product/architecture questions;
- leave implementation details open when every viable option preserves the approved guarantees.

Only after the architecture is sufficiently investigated and the material decisions are approved
does this workflow perform its final writing step, **Plan Synthesis** — consolidating an
already-investigated current state and already-approved user decisions into a draft `plan.md`
intended to become canonical, the input `plan-it` needs instead of reconstructing
decisions from conversation history.

**Only perform Plan Synthesis when the user asks for it.** Never produce a `plan.md` as an
automatic next step after investigation, and never treat a plain "document/explain X" request as
implicitly asking for one.

**Preconditions**, both required: an investigation of the same quality "Document existing
architecture" requires (concrete references, not conventions or guesses), and explicit,
user-approved decisions about the target state. If either is missing, do that work first — Plan
Synthesis never manufactures a decision on the user's behalf.

Every claim in the plan must fall into exactly one of four categories — see "Output-specific
non-negotiables" for the rule. `rules/plan-synthesis.md` owns the full methodology: the
four-category claim model, the flexible initiative-driven content model, evidence and placement
rules, the internal review pass, and the approval/handoff contract — read it before writing
anything.

State at the top of the written section that it is the source of truth for the subsequent
`plan-it` pass. That statement marks the section's intended handoff role — it does not
by itself prove approval. `plan-it` treats a plan as canonical only once the initiative
matches and the user's explicit approval is established; see
`plan-it/rules/plan-md-input.md` for the full recognition procedure.

**Skill boundary.** This workflow stops at an approved `plan.md` — see "Ownership and handoff" for
what belongs to `plan-it` instead.

## Ownership and handoff

This skill owns:

- architecture investigation;
- architectural explanation;
- surfacing and resolving material decisions with the user;
- new architecture guides;
- maintenance of existing guides;
- synthesis of approved architecture into `plan.md`.

This skill does not own:

- application implementation;
- debugging or diff review;
- API reference documentation;
- feature classification and issue decomposition;
- GitHub issue mutation;
- delivery sequencing;
- Git workflow.

## Rule and supporting-file routing

These are loaded only when their workflow needs them — none is a universal prerequisite:

- guide writing → `rules/doc-style.md`
- guide scaffold → `rules/template.html`
- guide review → `rules/review.md`
- guide maintenance → `rules/maintenance.md`
- plan writing → `rules/plan-synthesis.md`

## Output-specific non-negotiables

Guide rules (apply to "Document existing architecture" and "Update an existing architecture
guide"):

- Explain architecture, not implementation: why it exists, why it's shaped that way, and — where
  extension is relevant — how it can be extended. Code blocks exist only at genuine extension
  seams, never a walkthrough of a whole method body.
- When a real line exists between reusable infrastructure and integration-specific code, make it
  explicit in whatever structure the guide already uses — never a mandated section or table.
- Explain runtime ownership and lifecycle wherever the system has either — don't invent one for a
  capability with no runtime story.
- Ground material architectural claims in concrete evidence or enforcement references when an
  identifiable mechanism exists; never force a misleading one.
- No API documentation, no endpoint inventories, no line-by-line implementation walkthroughs, no
  duplicated explanations across sections.
- Not every guide needs a limitations section. When limitations or deferred work materially affect
  understanding, state the reason rather than adding a generic TODO list.
- A guide is never done until it has passed a `rules/review.md` pass — writing and reviewing
  are two separate steps.

Plan rules (apply to "Plan feature architecture"; full contract in
`rules/plan-synthesis.md`):

- Keep current-state facts, locked decisions, derived constraints, and open implementation details
  visually and textually distinct — the locked-vs-open rule above all.
- Never present an unresolved decision as settled without the user's explicit confirmation.

Don't apply the guide rules to a `plan.md`, or the plan rules to a guide.
