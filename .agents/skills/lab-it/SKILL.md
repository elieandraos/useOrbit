---
name: lab-it
description: "Investigates and validates how an existing system or capability actually works, from real implementation, tests, and current evidence — never conventions or guesses — producing a verified answer, an architecture decision reached with the user, or an approved `plan.md` handed to `plan-it`. Trigger to investigate or explain how a system works, resolve architecture or design decisions for a proposed feature, or synthesize approved findings into `plan.md`. Not for explaining one function, debugging, reviewing a diff, writing API reference docs, or creating, updating, or reviewing an architecture guide — route guide work to `document-it`."
---

# lab-it

## What this skill does

This skill investigates a real system and turns the resulting understanding into one of the
following:

| User intention                                                                | Result                                                                                     |
| -------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Understand how a system actually works                                        | A verified answer — investigation and recap, no guide required                            |
| Resolve a feature's architecture through conversation, no `plan.md` requested  | A verified answer or resolved architecture discussion — no document produced              |
| Explicitly request `plan.md` synthesis                                        | Approved `plan.md` handed to `plan-it`, via the existing synthesis and approval procedure  |
| Create, update, or review an architecture guide                               | Routed to `document-it`                                                                    |

Investigation comes first in every workflow — whether user confirmation follows, and when, is
conditional; see "Shared investigation and decision discipline" below. Investigation and recap
alone may be the complete result: no `plan.md` gets produced just because an investigation
happened.

## Shared investigation and decision discipline

Every workflow below starts with the same evidence discipline — including an investigation
`document-it` routes here when its own available evidence is missing or stale:

1. Inspect the real current system and relevant evidence, proportionally to the request — not
   conventions or assumptions, and not past what establishing the answer or the architectural fit
   actually requires. Reuse reliable findings and already-approved decisions already established in
   this conversation or another identifiable prior context instead of re-investigating them.
2. Reconcile implementation, configuration, schema, tests, runtime evidence, and reliable history.
3. Explain the current architecture and identify uncertainty.

> The system establishes what exists; the user decides what it should become.

Tests are strong evidence — often surfacing real boundaries faster than implementation alone —
but not infallible authority: reconcile them with the implementation and other relevant evidence,
since tests can be incomplete or stale. Implementation is authoritative for implementation facts;
configuration, schema, runtime observations, and external-system state are authoritative for
whatever they each actually govern. If asked, check issue and commit history for *why* — reliable
history explains rationale, it doesn't establish current behavior. Never document something
history says was planned but the evidence doesn't show. Do not begin writing a `plan.md` — or,
when this investigation was routed from `document-it`, a guide — during this step.

What happens next is conditional, not uniform: investigation can end in a verified answer alone,
with no further output required; planning feature architecture requires explicit, user-approved
decisions before writing `plan.md`; an investigation routed here from `document-it` ends by
handing back verified findings rather than drafting a guide itself.

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

A request resembling an established pattern does not automatically need a design interview or a
`plan.md`. When comparable features already establish the applicable conventions and investigation
finds no genuine architectural difference or material product decision, a short verified answer with
a recommendation to proceed to `plan-it` completes the request. Existing instances establish
conventions, not automatic approval of new product behavior — investigate only enough to confirm
architectural fit and surface a real difference before deciding whether a decision conversation is
even needed.

When a material decision conversation is needed, scale it to what's actually unresolved:

- **Question only unresolved material choices**, applying the materiality test in
  `rules/plan-synthesis.md` during the conversation itself, not only while drafting the plan — an
  ordinary implementation detail stays open under that same rule. Order questions by dependency:
  settle a foundational choice before asking one that depends on its answer, and raise independent
  questions alongside it when doing so helps.
- **Keep exchanges understandable**: a small, coherent group of related questions, or one question
  when the topic needs focused discussion. Explain the meaningful consequences of a choice and give a
  reasoned recommendation when evidence supports one — a recommendation is guidance, never an approved
  decision.
- **Clarify ambiguous language with concrete scenarios.** When a term could be read more than one
  way and the readings would change behavior, ownership, lifecycle, or guarantees, work through a
  concrete example rather than asking the user to define the term abstractly, and separate what the
  system currently does from what the user wants.
- **Name evidence-dependent uncertainty instead of guessing.** When discussion alone cannot resolve a
  choice, say so and name what would help — closer inspection, an experiment, or a prototype. Stay
  inside existing authorization boundaries: don't silently start implementing to find out, and don't
  treat "I don't know" as approval of a default.
- **Finish proportionally.** Stop once the material decisions are resolved; don't keep exploring
  design branches nobody raised. This doesn't relax Plan Synthesis's own preconditions or approval
  gate below — a narrow initiative can still warrant a short `plan.md` when the user explicitly asks
  for one.

Only after the architecture is sufficiently investigated and the material decisions are approved
does this workflow perform its final writing step, **Plan Synthesis** — consolidating an
already-investigated current state and already-approved user decisions into a draft `plan.md`
intended to become canonical, the input `plan-it` needs instead of reconstructing
decisions from conversation history.

**Only perform Plan Synthesis when the user asks for it.** Never produce a `plan.md` as an
automatic next step after investigation, and never treat a plain "document/explain X" request as
implicitly asking for one.

**Preconditions**, both required: an investigation meeting the same evidence discipline as
"Shared investigation and decision discipline" above (concrete references, not conventions or
guesses), and explicit, user-approved decisions about the target state. If either is missing, do
that work first — Plan Synthesis never manufactures a decision on the user's behalf.

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

**Skill boundary.** This workflow ends at one of two points: a verified answer recommending
`plan-it` directly, when no material decision remains, or an approved `plan.md` handed to `plan-it`,
when Plan Synthesis ran. Document approval gates only the second — it is not a universal
prerequisite for entering `plan-it`. See "Ownership and handoff" for what belongs to `plan-it`
instead.

## Ownership and handoff

This skill owns:

- architecture investigation;
- architectural explanation;
- surfacing and resolving material decisions with the user;
- synthesis of approved architecture into `plan.md`.

An investigation may end in a verified answer alone — a guide is never a required next step.

This skill does not own:

- creating or updating an architecture guide, or guide review (→ `document-it` — draws on this
  skill's investigation method when its own available evidence is missing or stale, rather than
  duplicating it);
- application implementation;
- debugging or diff review;
- API reference documentation;
- feature classification and issue decomposition;
- GitHub issue mutation;
- delivery sequencing;
- Git workflow.

## Rule and supporting-file routing

- the materiality test consulted throughout the decision conversation, and plan writing →
  `rules/plan-synthesis.md`, loaded only when "Plan feature architecture" needs it.

Guide-writing, guide-scaffold, guide-review, and guide-maintenance rules live under `document-it`
and are not duplicated here.

## Output-specific non-negotiables

Plan rules (apply to "Plan feature architecture"; full contract in
`rules/plan-synthesis.md`):

- Keep current-state facts, locked decisions, derived constraints, and open implementation details
  visually and textually distinct — the locked-vs-open rule above all.
- Never present an unresolved decision as settled without the user's explicit confirmation.

Guide-specific non-negotiables live in `document-it/SKILL.md` and `document-it/rules/review.md` —
not duplicated here.
