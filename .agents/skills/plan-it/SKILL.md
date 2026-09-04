---
name: plan-it
description: "Plans a feature end-to-end into GitHub milestone/label metadata and a reviewed, drafted set of issues — classify, scope, draft, review, and create only after approval. Trigger when asked to plan, scope, or scaffold a feature, or to turn an approved plan.md initiative into milestones/issues. When `lab-it` has already produced an approved, initiative-specific `plan.md` section, treat it as canonical input instead of re-deriving decisions from conversation. Also trigger when an unexpected finding needs investigation before deciding whether it's issue-worthy. Only plans, drafts, and reviews GitHub milestones/issues; does not implement code, and does not produce or amend `plan.md` itself (that's `lab-it`'s job)."
---

# plan-it

Personal workflow for turning "let's build a feature" into a milestone and a reviewed, drafted set of
GitHub issues. The methodology — classify, scope, reconcile design, draft, review, sequence, create —
is portable across GitHub-based projects; each consuming project supplies its own stack conventions
inside that shape. See `README.md` for the plain-English walkthrough and reasoning.

## Two work origins

- **Planned work** — starts from an intentional proposed change, not an unexpected finding. A feature
  ask in conversation enters the pipeline below at classification (step 2) and still requires scope
  discovery; an approved `plan.md` section (`rules/plan-md-input.md`) enters at step 1 with its
  architecture and decisions already canonical. Both are the same Planned-work origin.
- **Discovered work** — starts from an unexpected finding (implementation, code review, manual
  testing, production/debugging, another workflow) with scope not yet known. Runs
  `rules/discovered-work.md`'s investigation first; only a validated finding enters the pipeline.

Both origins converge on the same canonical-issue pipeline and the same review bar — Discovered work
never gets a lighter-weight issue.

## Pipeline

1. Consume an approved `plan.md` input when one exists (`rules/plan-md-input.md`); otherwise skip to
   classification.
2. Classify the feature — resource/CRUD, cross-cutting capability, extension, or refactor
   (`rules/feature-classification.md`).
3. Load the matching scope checklist and resolve every applicable question
   (`rules/resource-feature-checklist.md` or `rules/capability-checklist.md`).
4. When frontend/UI is in scope, reconcile the project's available design artifacts against the
   shipped application and any approved architecture decisions (`rules/design-reconciliation.md`).
5. Query existing GitHub milestones/labels and propose the applicable metadata — milestone,
   label, and assignee fields (`rules/issue-conventions.md`); create nothing yet.
6. Draft canonical issue definitions (`rules/issue-conventions.md`).
7. Sequence the work by its actual dependency graph, run the review pass, and present the full draft
   for content review; then obtain final approval of the compact manifest and the proposed metadata
   (`rules/sequencing.md`, `rules/review.md`).
8. Create GitHub issues in dependency-safe order and validate every mutation by reading state back.

## Non-negotiable contracts

- An approved `plan.md`'s locked decisions are never re-litigated during planning.
- Canonical issue definitions are the source of truth — every rendered draft, the compact manifest,
  and every `gh issue create` call are generated fresh from them, never from a prior render.
- A created GitHub issue must stand alone — understandable even if `plan.md`, the planning
  conversation, and this skill's own prior output all disappeared.
- Two review surfaces gate creation — a full content review, then a final compact-manifest/metadata
  approval — and nothing (no milestone, label, or issue) is created until both are approved.
- Reaching the content-review gate requires proof, not narration: every `rules/review.md` surface
  applicable to the current canonical set has actually run against the exact literal bodies being
  presented, captured in that file's compact review-execution record — a general claim that review
  was applied does not satisfy this, and a correction invalidates the affected issue's prior record
  entry until it is re-rendered and rechecked.
- Every issue body is re-validated against its canonical definition immediately before it touches
  GitHub, and every mutation is re-fetched from GitHub and validated again afterward, at creation and
  on any later change — a `gh` command succeeding is never treated as proof by itself.

Full mechanics for all of the above: `rules/review.md`.

## What it owns

Feature classification, scope discovery, discovered-work investigation/triage, design reconciliation,
issue drafting, dependency-aware sequencing, review, milestone/label/assignee proposals, and GitHub
issue creation after approval.

## What it does not own

Application implementation, framework-specific conventions, test implementation details, commit
structure/messages, fixing a defect it discovers, and deciding when a milestone closes.

## Rule index

- `rules/plan-md-input.md` — consuming an approved `plan.md` section as canonical input.
- `rules/discovered-work.md` — investigating an unexpected finding before it's classified.
- `rules/feature-classification.md` — which shape a feature is, and which checklist applies.
- `rules/resource-feature-checklist.md` / `rules/capability-checklist.md` — shape-specific scope
  questions.
- `rules/design-reconciliation.md` — reconciling design artifacts against the shipped app when UI is
  in scope.
- `rules/issue-conventions.md` — issue format, and milestone/label/assignee proposal-and-approval.
- `rules/sequencing.md` — coherent-outcome decomposition, the real dependency graph, parallel-ready
  planning waves, and dependency-safe creation order.
- `rules/review.md` — issue quality, structural/rendered/content integrity, and post-mutation
  validation.

## Handoff

Planning's responsibility ends once approved GitHub metadata and issues are created and validated.
From there, `ship-it` owns the downstream Git/GitHub delivery workflow — branch readiness,
review gates, commits, verification, issue closure, and release/milestone progression — and the
consuming project's own implementation skills own the actual application/framework code. The two
compose during delivery, neither replacing the other, and this skill writes no code itself.
