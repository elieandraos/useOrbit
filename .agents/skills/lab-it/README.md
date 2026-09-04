# lab-it

Investigates how a system actually works, from real implementation, tests, and current evidence —
never conventions or guesses — then produces one of three results:

| Intention | Result |
|---|---|
| Understand and document existing architecture | New architecture guide |
| Reconcile a guide with verified changed reality | Updated existing guide |
| Design feature architecture through conversation | Approved `plan.md` handed to `plan-it` |

Investigation and a recap can be the whole result on their own — a guide or a `plan.md` is something
someone has to ask for, not an automatic next step.

## When to use it

- You want to understand or document how something in the codebase actually works.
- A published architecture guide is stale relative to verified current behavior.
- You're about to build a feature and need the architecture decisions settled before planning or
  implementation starts.

Not for explaining one function, debugging, reviewing a diff, or writing API reference docs.

## How it works

Every workflow starts the same way: inspect the real system, reconcile evidence (implementation,
config, schema, tests, history), and explain the architecture including what's still uncertain — the
system establishes what exists, the user decides what it should become. What happens next differs by
workflow: a new guide needs recap confirmation before publishing; planning feature architecture needs
explicit, user-approved decisions before `plan.md` is written; updating a guide only asks the user
when authority or a material decision is unclear.

A `plan.md` produced here keeps every claim in one of four categories — current-state fact, locked
decision, derived constraint, or open implementation detail — so `plan-it` can treat it as canonical
without re-deriving decisions from conversation.

## Ownership

Owns architecture investigation and explanation, new and updated architecture guides, and synthesis
of approved feature architecture into `plan.md`. Does not own application implementation, downstream
issue planning, or Git/GitHub delivery — see `plan-it` and `ship-it`.

## Rules

`SKILL.md` routes to `rules/doc-style.md` (guide writing), `rules/template.html` (guide scaffold),
`rules/review.md` (guide review), `rules/maintenance.md` (guide updates), and
`rules/plan-synthesis.md` (`plan.md` methodology).
