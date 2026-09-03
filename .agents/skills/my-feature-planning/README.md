# my-feature-planning

A "turn the idea into buildable work" skill. It takes a feature request or an approved `plan.md`
section, works out the scope and dependencies, drafts reviewed GitHub issues, and creates them only
after approval. It plans the work — it does not implement it, that's a different skill's job, picked
up after an issue is approved.

## Three starting points, two work origins

A feature conversation and an approved `plan.md` are the two starting points for Planned work; an
unexpected finding is the starting point for Discovered work.

**From a feature conversation.** Most features start here: "let's build X" → classify → work out
scope → ask the important unanswered questions → draft issues.

**From an approved `plan.md`.** When `my-architecture-laboratory` has already investigated the
problem and the target architecture has been approved, this skill treats that architecture and its
locked decisions as canonical instead of re-deriving them from conversation. It resolves only the
remaining open details that materially affect scope, dependencies, acceptance criteria, guarantees,
or developer readiness. Other open implementation details may stay open for implementation, under
the owning rule's executability test. It must not re-litigate a decision the plan already locked.
Full detail: `rules/plan-md-input.md`.

**From a Discovered finding.** Not every issue starts from known scope — sometimes the first thing
that exists is a symptom: a blank page during manual testing, a stale reference found in review, an
unexpected error in production. This skill investigates before drafting — separates what's actually
known from what's assumed, reproduces it when practical, and works out the real problem, the blast
radius, and whether it's actually a defect — proportional to the evidence, not automatically
exhaustive. Only a validated finding enters the same canonical-issue pipeline, and the same review
bar, as any other issue; this path decides *whether* there's a real issue and writes it, it doesn't
fix code. Full detail: `rules/discovered-work.md`.

## How it scopes a feature

It classifies first — new resource/CRUD, cross-cutting capability, extension of something that
already exists, or architectural/refactor work — then loads only the questions that shape actually
needs answered. Shared infrastructure isn't automatically its own issue; that's decided by the actual
dependency graph, not a rule of thumb.

When frontend/UI is in scope, it also reconciles the project's available design artifacts against
what's actually shipped and any locked `plan.md` decision. An approved locked decision always governs
what it covers; current code is authoritative for current-state facts and established shipped
conventions, but shipping something is not by itself proof of a deliberate newer product decision. A
design artifact is evidence of intended UI, not an automatic winner merely for existing. Where
resolved authority or chronology can classify a mismatch, it does; a genuine unresolved disagreement
blocks only the affected drafting, and the user decides. A missing design artifact is a normal,
non-blocking outcome — unless its absence leaves a necessary product decision unresolved. Full
detail: `rules/feature-classification.md`, the shape-specific checklist, and
`rules/design-reconciliation.md`.

## What a good issue looks like

An issue is not a code blueprint. It answers what problem is being solved, why, what outcome should
exist, what constraints must remain true, and what's in and out of scope — through Context and Tasks,
with Acceptance Criteria and Tests added only where they earn their place. It must stand on its own:
understandable even if `plan.md`, the planning conversation, and this skill's own prior output all
disappeared tomorrow.

There's one canonical definition per proposed issue. Every rendered draft, the compact manifest, any
revision, and the final GitHub body are generated fresh from that definition — never copied from an
earlier rendered preview. That's what keeps a large issue set from drifting (duplicated sections,
misplaced acceptance criteria) as it goes through a few rounds of revision.

## Sequencing

Canonical scope is decomposed by coherent outcome, real dependency, and independent provability —
never by a fixed template such as backend-before-frontend, or by checklist question, file, or layer.
A shared foundation becomes its own issue only when it's independently coherent and provable on its
own; otherwise the smallest real consumer bundles with it. Real prerequisites form a dependency graph
(a DAG): roots, disconnected components, and parallel-ready work are all valid outcomes, not
exceptions to explain away.

The graph is presented as waves — issues whose prerequisites all sit in an earlier wave — which
exposes possible parallelism and gives a dependency-safe order for presenting and creating issues.
A wave says nothing about live implementation readiness or which issue to work next; no
implementation-layer order is built into the portable methodology itself. Planned work and a
validated Discovered-work finding (`rules/discovered-work.md`) use this same decomposition and
dependency method once the finding is validated — origin never changes how work is decomposed or
ordered. Live readiness, and choosing the next issue to implement, belong to `my-git-workflow` once
issues exist. Full detail: `rules/sequencing.md`.

## Review and approval

Two checkpoints gate GitHub: a **content review** of the full issue set, where scope, context, tasks,
and acceptance criteria get fixed; and a **final approval** of the compact manifest plus the proposed
metadata — milestone, labels, and assignee, including the approved absence of any of them. Nothing —
no milestone, no label, no issue — gets created until both are approved. Every issue body is checked
once more immediately before it touches GitHub, and every mutation is re-fetched from GitHub and
validated again afterward, at creation and on any later edit — a `gh` command succeeding is never
treated as proof by itself. Full detail: `rules/review.md`, and `rules/issue-conventions.md` for how
metadata is proposed.

## What it owns

Feature classification, scope discovery, discovered-work investigation and triage, design
reconciliation, issue drafting, dependency planning, review, milestone/label/assignee proposals, and
GitHub issue creation after approval.

**It does not own** application implementation, fixing a defect it discovers, framework-specific
coding conventions, test implementation details, commit structure/messages, or deciding when a
milestone closes. This skill's responsibility ends once approved issues are created and validated;
`my-git-workflow` then owns the downstream Git/GitHub delivery workflow (branch readiness, review
gates, commits, verification, closure, release, and milestone completion), and the consuming
project's own implementation skills own the actual application/framework code. The two compose during
delivery rather than replacing one another.

```
my-architecture-laboratory → approved architecture / plan.md
  → my-feature-planning     → GitHub issues
  → my-git-workflow (Git/GitHub delivery) + implementation skills (application code)
```

## GitHub is the planning substrate

This skill plans work as GitHub milestones, labels, and issues intentionally, not as a stand-in for
some other tracker. Portability, for this skill, means being reusable across GitHub-based software
projects with different application stacks and project conventions — not across issue trackers.

Application-stack conventions, milestone-naming patterns, and label palettes are inputs the consuming
project supplies to the methodology, not part of the portable method itself. The portable rules in
this skill carry the methodology; the consuming project supplies its own stack, domain, metadata,
design, and live-repository inputs into it. Any project-specific convention found folded into a rule
file's methodology more deeply than a clearly-labeled example should be treated as a defect to fix,
not as expected unfinished work.

---

`my-feature-planning` turns a settled feature idea into clear, reviewable, dependency-safe work
without making the developer rediscover the architecture or the planning conversation.
