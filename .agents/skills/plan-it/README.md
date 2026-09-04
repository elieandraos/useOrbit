# plan-it

Turns a feature request — or an approved `plan.md` section from `lab-it` — into a reviewed, drafted
set of GitHub issues, created only after approval. It plans the work; `ship-it` implements it.
Either starting point is equally valid — `lab-it` having run first is not a precondition.

## When to use it

- "Let's build X" — classify, scope, draft issues, directly from conversation.
- `lab-it` has already produced an approved `plan.md` — treated as canonical input, not re-derived
  from conversation.
- An unexpected finding (bug, stale reference, production issue) needs investigating before deciding
  whether it's issue-worthy.

## How it works

Classifies the work first (new resource, cross-cutting capability, extension, or
architectural/refactor), loads only the questions that shape actually needs answered, and — when UI
is in scope — reconciles design artifacts against what's shipped and any locked `plan.md` decision.
Issues are decomposed by real dependency and independent provability, presented as
dependency-ordered waves, never by a fixed template. Two checkpoints gate GitHub: a content review of
the full issue set, and a final approval of metadata (milestone, labels, assignee) — nothing is
created until both pass, and every mutation is re-fetched and validated afterward.

## Ownership

Owns feature classification, scope discovery, discovered-work triage, design reconciliation, issue
drafting, dependency sequencing, review, and GitHub issue creation after approval. Does not own
application implementation, commit structure, or milestone/release delivery — see `ship-it`.

    lab-it   → approved architecture / plan.md
      → plan-it → GitHub issues
      → ship-it + applicable stack companion → verified change

## GitHub is the substrate

Plans work as GitHub milestones, labels, and issues intentionally — portable across GitHub-based
projects with different stacks, not across issue trackers. A consuming project supplies its own stack
conventions, milestone naming, and label palette.

## Rules

`SKILL.md` routes to `rules/plan-md-input.md`, `rules/discovered-work.md`,
`rules/feature-classification.md`, `rules/design-reconciliation.md`, `rules/sequencing.md`,
`rules/review.md`, `rules/issue-conventions.md`, `rules/capability-checklist.md`, and
`rules/resource-feature-checklist.md`.
