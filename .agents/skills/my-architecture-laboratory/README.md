# my-architecture-laboratory

This skill investigates how a system actually works. It can document existing architecture,
reconcile an existing guide with verified changed reality, or help the user settle feature
architecture before downstream planning or implementation. This file explains the idea and
rationale; `SKILL.md` and `rules/` contain the operational instructions.

## What the skill does

The skill turns a real investigation into one of three results:

| User intention                                          | Result                                             |
| --------------------------------------------------------- | ---------------------------------------------------- |
| Understand and document existing architecture           | New Claude Artifact architecture guide             |
| Reconcile an existing guide with verified changed reality | Updated existing Artifact                          |
| Design feature architecture through conversation        | Approved `plan.md` handed to `my-feature-planning` |

Investigation and a recap can also be the complete result on their own: when the user only wants
to understand a system, the skill stops there. A guide and a `plan.md` are outputs someone has to
ask for, not something an investigation produces automatically.

## Shared method

Every workflow starts with the same discipline:

1. Inspect the real, current system — not conventions or assumptions.
2. Reconcile implementation, configuration, schema, tests, runtime evidence, and reliable history.
3. Explain the architecture, including what's still uncertain.
4. Obtain user decisions when the requested output would settle target architecture.

> The system establishes what exists; the user decides what it should become.

Tests carry real weight — they often surface the actual behavioral boundary faster than the
implementation alone — but they aren't infallible authority. They get reconciled with the
implementation and other relevant evidence, because a test can be incomplete or stale.
Implementation is authoritative for implementation facts; configuration, schema, runtime
observations, and external-system state are authoritative for whatever they each govern. History
explains rationale, not current behavior — a plan recorded in history is never proof of what exists
now.

What step 4 requires differs by workflow, deliberately:

- A **new guide** needs the user to confirm the investigation's recap before it gets published.
- **Planning feature architecture** needs explicit, user-approved decisions for every material
  question before `plan.md` gets written.
- **Updating an existing guide** only asks the user when authority, intent, or a material decision
  is unclear. Refreshing an already-settled fact doesn't reopen a decision.

## Three workflows

### Document existing architecture

Use this to understand and record how something already works. The skill investigates the real
implementation, recaps its understanding for the user to confirm, then writes and publishes a new
Claude Artifact architecture guide and runs a review pass against it.

The guide teaches how the system works, why it's shaped that way, and — where extension is
relevant — how it can be extended. Its structure follows the architecture itself; there's no
universal section template every guide gets poured into.

### Update an existing architecture guide

Use this when a published guide needs reconciling with verified current reality — a stale claim, a
stale evidence reference, changed configuration or runtime behavior, or a prior documentation
defect, not only a changed implementation. This is its own workflow, not something that
automatically follows creating a new guide.

It locates the existing Artifact rather than minting a new one, reconciles its architectural claims
against verified current evidence, and follows the complete affected claim graph rather than only
the section where the change was first noticed — presenting the architecture as it works now, not
a changelog. A claim that genuinely changed can move the guide's structure, not just its wording;
an unaffected claim stays as it was. The guide keeps its identity (same URL, same favicon), and the
whole updated guide is reviewed again, with emphasis on the changed claims and whatever depends on
them. `rules/maintenance.md` governs the judgment calls involved.

### Plan feature architecture

Use this when the point of the work is preparing a real change, not teaching a system. It can
start from a feature idea raised in conversation, an architecture question, an existing
investigation, or decisions already approved elsewhere.

The skill investigates how the current system supports or constrains the proposed feature,
discusses viable target approaches with the user, and surfaces the decisions that genuinely need a
human call. An implementation detail that stays open under every viable option is left open, not
resolved just to look finished.

Once the architecture is understood well enough and the material decisions are approved, the
workflow's final step — **Plan Synthesis** — writes the approved result into a draft `plan.md`
intended to become canonical. Plan Synthesis names that last step, not the whole workflow:
everything before it is ordinary investigation and decision-making. The written section states
that it is the source of truth for the subsequent `my-feature-planning` pass — but that statement
only marks intended handoff; it is not proof of approval. The plan becomes canonical only once the
user has actually approved it, and only then does it go to `my-feature-planning`.

Every claim in `plan.md` is exactly one of four things, kept visibly distinct:

- **current-state fact** — verified present reality, grounded in whatever evidence actually governs
  it (code, configuration, schema, tests, runtime behavior), not just what the code does today;
- **locked decision** — something the user explicitly approved;
- **derived constraint** — something that necessarily follows from verified current-state facts
  plus one or more locked decisions;
- **open implementation detail** — a real choice nobody has made yet, left open only because every
  viable option preserves the approved architecture and guarantees.

## Outputs and handoffs

A new or updated architecture guide is the finished output of its own workflow. Publishing or
updating one doesn't automatically hand anything to `my-feature-planning`.

Only an approved `plan.md` crosses that boundary. From there, `my-feature-planning` owns feature
classification, scope, issue decomposition, sequencing, metadata, and GitHub issue creation.
Application implementation and Git workflow sit outside this skill either way.

`my-feature-planning` treats an approved plan as canonical — it doesn't silently reopen a locked
decision. It can still validate a current-state fact against current evidence when issues are
drafted, and it can flag a derived constraint whose premise no longer holds. Neither of those is
re-deciding something the user already settled.

## Ownership

This skill owns:

- architecture investigation and explanation;
- surfacing material architecture decisions;
- new architecture guides;
- maintenance of existing guides;
- synthesis of approved feature architecture into `plan.md`.

It does not own:

- application implementation;
- debugging or diff review;
- API reference documentation;
- downstream issue planning;
- GitHub mutation;
- delivery or Git workflow.

## Portability

The investigation discipline, the decision gates, the guide's structure-follows-architecture
principle, and Plan Synthesis's fact/decision/constraint/detail categories are stack-neutral —
none of them assume a particular language, framework, or project layout. A consuming project
supplies its own framework, directories, architecture, and concrete artifacts; the skill supplies
the method.

Two dependencies are deliberate rather than leftover project-specificity: the guide workflow
publishes to a Claude Artifact using the `artifact-design` capability, and the planning
workflow writes `plan.md` and hands it to `my-feature-planning`. Both are named openly.
Neither makes this skill tracker-specific: GitHub is `my-feature-planning`'s substrate, not
this skill's.

## Rules and supporting files

- `SKILL.md` — operational routing and decision gates.
- `rules/doc-style.md` — guide-writing grammar.
- `rules/template.html` — guide scaffold (supporting file, not a rule).
- `rules/review.md` — guide review checklist.
- `rules/maintenance.md` — guide maintenance methodology.
- `rules/plan-synthesis.md` — `plan.md` synthesis contract.
