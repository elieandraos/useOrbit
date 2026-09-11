---
name: implement-it
description: "Implementation-stage skill in the Agentic Engineering pipeline. Takes any approved GitHub issue satisfying its entry contract — whether `plan-it` drafted it or it already existed some other way — and carries it through working-branch readiness, the implementation itself, verification, semantic commits, issue closure, and dependency-ready recalculation for the next issue. Use when implementing, committing, verifying, or closing an approved issue, checking what's next in a milestone, or performing a human-authorized delivery correction handed back from `ship-it`. Performs the approved implementation itself, consulting the applicable stack companion for implementation knowledge and conventions — it does not own framework-specific conventions, decide what work should exist, or handle milestone PR readiness, PR creation, or release."
---

# implement-it

## What this skill is

`implement-it` is the implementation stage of the Agentic Engineering pipeline. It starts from any
approved, implementation-ready GitHub issue — whether `plan-it` drafted it or it already existed
some other way — and carries that single issue's work through verified Git/GitHub implementation,
ending at issue closure and the next-issue recommendation. It also accepts an explicitly
human-authorized delivery correction handed to it directly by `ship-it`, which requires no issue to
exist at all (see "Delivery corrections" below).

## Pipeline position

`lab-it → plan-it → implement-it → ship-it`

This skill intentionally begins only once an issue is approved, or a delivery correction is
explicitly authorized — it never decides what work should exist. It hands off to `ship-it` once a
milestone genuinely has zero open issues remaining, not merely an empty dependency-ready set (open
issues can all be blocked without the milestone being done — `rules/sequencing.md`'s "When the ready
set is empty"), or once it has performed a correction `ship-it` hands back.

## What it owns

- Working-branch readiness: the Backlog/hotfix-vs-milestone-branch decision, before implementation
  starts.
- Companion activation: determining which available stack, implementation, testing, and tooling skills apply before implementation begins, and activating all applicable companions rather than treating the first matching skill as sufficient.
- Performing the approved implementation itself.
- Applying project conventions and applicable implementation/testing/tooling skills, and loading an
  applicable custom stack companion when one is available.
- Implementation and commit-plan review gates (Gate 1 and Gate 2), invoking `review-it` only after the
  required verification decisions have been completed and consuming its result as Gate 1's third
  stop condition.
- Verification, including the regression-baseline treatment of pre-existing lint/format/static debt.
- Semantic commit planning and construction.
- Authorized push and issue closure — intentionally before the milestone's PR merges.
- Dependency-ready recalculation and the next-issue recommendation.
- The authorized fix itself for an in-flight delivery correction `ship-it` hands back (see
  "Delivery corrections" below), using the same lifecycle.

## What it does not own

- Deciding what work should exist.
- Defining or scoping milestones.
- Application or framework implementation conventions — a stack companion, when one applies, owns
  those; this skill performs the work using them.
- Milestone PR readiness, PR creation, and merge strategy.
- Independent implementation review — `review-it` owns the checklist and the finding; this skill
  invokes it and fixes what it finds.
- Investigating or explaining a delivery/CI failure, and securing the human's authorization for a
  correction (`ship-it`'s job) — this skill performs the correction only once that human
  authorization has actually been given and `ship-it` hands the fix off.
- Post-merge authorization, release, and post-release milestone completion.
- Deployment automation.

A custom stack companion is optional, used when available and not required for every
implementation. Technology-specific knowledge — commands, branch-naming conventions, framework
idioms — belongs entirely to that companion or to project instructions, never hardcoded here.

## Entry contract

For ordinary implementation work, accept any GitHub issue that meets the structural and content
quality bar `plan-it`'s `rules/issue-conventions.md` and `rules/review.md` define, and that carries
the human's approval to implement it — regardless of whether `plan-it` drafted it or it was authored
some other way. What matters is that it meets that bar and is approved, not who wrote it; do not
recreate or replan an issue that already meets it merely because `plan-it` didn't produce it. For a
single named issue, complete only that issue's authorized lifecycle (implementation through closure
and the next-issue recommendation); this does not by itself authorize continuing into another issue,
or into milestone delivery. For a milestone request, manage progress issue by issue, per "Milestone
progression" below.

An authorized delivery correction (see "Delivery corrections" below) is a separate entry route with
a different prerequisite: it requires the human's explicit authorization, not an approved issue, and
stays available whether or not an issue is open, or was ever created for that scope at all.

## Milestone progression

After each issue closes, recompute the dependency-ready set (`rules/sequencing.md`) and recommend
the next issue, explaining the choice when several are ready. A recommendation is not authorization
to continue — wait for the human's selection before implementing another issue. When the ready set is
empty because every open issue remains blocked, report the blockers; only a genuinely empty milestone
(zero open issues) hands off to `ship-it`'s milestone PR-readiness assessment.

## Delivery corrections

When `ship-it` investigates a CI failure on an open milestone PR, determines a correction stays
within already-approved scope, and the human explicitly authorizes it
(`ship-it/rules/ci-failure-correction.md`'s "CI failure on an open milestone PR"), it hands the
authorized fix to this skill. Accept this entry only once that human authorization actually
accompanies the handoff — `ship-it`'s own determination that a fix stays in scope is necessary but
never sufficient by itself; without the human's explicit authorization there is nothing yet for this
skill to perform. Once accepted, perform the correction through this same lifecycle — targeted
verification, the required full-suite decision, then Gate 1 and Gate 2 as applicable (invoking
`review-it` only after verification is complete and the full-suite decision has been answered), commit
construction, and authorized push — whether or not the original issue is still open. This route stays
available without requiring an open issue to exist; it does not require reopening a closed issue, and
it is separate from genuinely new scope, which still goes through `plan-it`'s discovered-work intake.
Once the correction is verified and pushed, `ship-it` resumes the delivery workflow.

## Composition

- Git and GitHub are intentional core substrate for this methodology, not an abstraction to be
  swapped out.
- This skill composes with whatever implementation, testing, and tooling skills the consuming
  project's stack requires, loaded alongside it.
- Stack-specific knowledge does not belong in this skill.
- This skill invokes `review-it` standalone before Gate 1, and again before Gate 1 of an authorized
  delivery correction — the same independently callable capability at two trigger points, not a
  procedure this skill owns or duplicates. This skill fixes what `review-it` finds; `review-it`
  never fixes anything itself.

## Activation

Trigger on requests shaped like:

- `implement issue {xxx}`
- `commit issue {xxx}`
- `close issue {xxx}`
- `what's next in milestone {name}`
- an authorized delivery correction handed back from `ship-it`

## Rules

- `companion-activation.md` — before writing code, enumerate available implementation, testing,
  tooling, and stack-companion skills; inspect their trigger descriptions and activate every applicable
  one. Do not treat the first matching skill as sufficient, and record relevant non-activation decisions.
- `review-gates.md` — the two pre-merge human approval gates (implementation review, then
  commit-plan review), how Gate 1 consumes `review-it`'s result, the approval-validity check before
  Gate 2 and before push, and the conditions that always warrant a stop; consult once implementation
  is ready to report, and again once a commit plan is ready to propose.
- [`review-it`](../review-it/) — independently callable implementation review; invoke standalone
  against the completed working tree only after required targeted verification and the full-suite
  run/skip decision are complete, before reporting at Gate 1 (`review-gates.md`'s "Consuming review-it's result").
- `commit-boundaries.md` — how to turn an approved diff into semantic commits: boundary reasoning,
  message content, the `Refs #N` trailer, final commit-message validation, and where a review correction
  lands; consult while inspecting the diff and building the commit plan, after Gate 1.
- `commit-reconstruction.md` — the unpublished-history reconstruction procedure
  `commit-boundaries.md` hands off to; consult only for its one specific trigger — a review
  correction belongs to a commit already committed locally but not yet pushed. Ordinary commit
  building, and a correction found before anything is committed, never need it.
- `verification.md` — verification scope: required targeted verification before Gate 1; the human
  full-suite run/skip decision that must be surfaced and answered before `review-it` or Gate 1; the
  completed-issue consequences of that choice; narrowest-reliable verification per commit; cache/TIA/
  replay distinctions; when isolation escalation is warranted; preserving pre-existing worktree
  changes using reliable provenance; and runtime-activation checks that route to the specialized
  rules when required. Consult before reporting Gate 1 and while building/ordering commits.
- `activation-ordering.md` — reordering commits when checking one against runtime activation
  (configuration, a feature flag, environment-conditioned behavior) finds an effect; consult only
  once that check finds one — ordinary dependency ordering never needs it.
- `isolation-verification.md` — the per-commit full-suite escalation technique
  `verification.md`'s "Isolation verification" section triggers; consult only once that section's
  own criteria actually apply, or when `commit-reconstruction.md` mandates it for every rebuilt
  commit — never merely because an issue has multiple commits.
- `worktree-preservation.md` — the qualified stash-based procedure for temporarily setting aside
  unrelated worktree content during a Git rewrite, shared by `isolation-verification.md` and
  `commit-reconstruction.md`; consult only from within one of those two procedures, never directly
  for ordinary work.
- `issue-closure.md` — whether and how to close an issue: asking first, the closing recipe, and
  post-mutation validation; consult after the verification choice has been recorded, once commits
  exist. Closure is intentional before a milestone's PR merges.
- `sequencing.md` — branch readiness before starting an issue (Backlog/hotfix on the trunk branch vs.
  a shared milestone branch, inspected/recommended/created only with human approval), and, after a
  validated closure, recomputing the milestone's dependency-ready set and reporting/recommending the
  next issue — or handing off to `ship-it/rules/milestone-pr-readiness.md` when zero open issues
  remain, as distinct from an empty ready set with blocked issues still open.

> Detailed operational behavior lives in `rules/*.md`.
