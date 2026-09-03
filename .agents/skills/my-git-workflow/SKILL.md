---
name: my-git-workflow
description: "Delivery-stage skill in the Agentic Engineering pipeline, immediately after my-architecture-laboratory and my-feature-planning. Takes an approved GitHub issue and carries it through working-branch readiness, implementation review, semantic commits, verification, issue closure, milestone PR readiness, release, and post-release milestone completion. Use when implementing, committing, verifying, closing, or releasing an approved issue, or checking whether a milestone is ready for a PR or ready to close. Owns the Git/GitHub delivery workflow — not application code, framework conventions, or deciding what work should exist."
---

# my-git-workflow

## What this skill is

`my-git-workflow` is the delivery stage of the Agentic Engineering pipeline. It starts from an
approved GitHub issue produced by `my-feature-planning` and carries that work through verified
Git/GitHub delivery and the related release/milestone lifecycle.

## Pipeline position

`my-architecture-laboratory → my-feature-planning → my-git-workflow`

This skill intentionally begins only once planning has produced approved work — it never decides
what work should exist, and never starts earlier than an already-approved issue.

## What it owns

- Working-branch readiness: the Backlog/hotfix-vs-milestone-branch decision, before implementation
  starts.
- Implementation and commit-plan review gates.
- Semantic commit planning and construction.
- Verification, including the regression-baseline treatment of pre-existing lint/format/static debt.
- Issue closure — intentionally before the milestone's PR merges.
- Dependency-ready recalculation.
- Milestone PR readiness, once a milestone's issues are all closed.
- The post-merge authorization gate, release, and post-release milestone completion.

## What it does not own

- Deciding what work should exist.
- Defining or scoping milestones.
- Application or framework implementation.
- Stack-specific conventions.
- PR creation and merge strategy, where not yet covered.
- Deployment automation.

## Composition

- Git and GitHub are intentional core substrate for this methodology, not an abstraction to be
  swapped out.
- This skill composes with whatever implementation, testing, and tooling skills the consuming
  project's stack requires, loaded alongside it.
- Stack-specific knowledge does not belong in this skill.

## Activation

Trigger on requests shaped like:

- `implement issue {xxx}`
- `commit issue {xxx}`
- `close issue {xxx}`
- `what's next in milestone {name}`
- `is milestone {name} ready for a PR`
- `release {version}`
- `is milestone {name} ready to close`

## Rules

- `review-gates.md` — the two pre-merge human approval gates (implementation review, then
  commit-plan review) and the conditions that always warrant a stop; consult once implementation is
  ready to report, and again once a commit plan is ready to propose.
- `commit-boundaries.md` — how to turn an approved diff into semantic commits: boundary reasoning,
  message content, the `Refs #N` trailer, and safely folding in review corrections; consult while
  inspecting the diff and building the commit plan, after Gate 1.
- `verification.md` — verification scope: the narrowest reliable scope per commit across tests,
  formatting, linting, and static analysis, the two distinct full-suite moments, the stronger
  isolation technique for proving a split, and ordering commits around feature-activation risk;
  consult while implementing and while building/ordering commits.
- `issue-closure.md` — whether and how to close an issue: asking first, the closing recipe, and
  post-mutation validation; consult after the completed-issue full-suite pass, once commits exist.
  Closure is intentional before a milestone's PR merges.
- `sequencing.md` — branch readiness before starting an issue (Backlog/hotfix on the trunk branch vs.
  a shared milestone branch, inspected/recommended/created only with human approval), and, after a
  validated closure, recomputing the milestone's dependency-ready set and reporting/recommending the
  next issue — or handing off to `milestone-completion.md` when the set is empty.
- `release.md` — the release phase: a post-merge authorization gate right after the human confirms a
  PR merged (the same gate that also opens `milestone-completion.md`'s closure gate — neither branch
  waits on the other), then discovering the project's real release policy, understanding the release,
  drafting notes at release altitude, the content-approval gate, publishing, and post-publication
  validation; consult once a PR carrying committed work has merged. Does not apply to Backlog/hotfix
  work, which has no PR to merge.
- `milestone-completion.md` — two milestone-level gates: PR readiness (all issues closed + confirmed
  manual testing + no follow-up found), consulted once `sequencing.md` reports an empty ready set;
  and the three-part closure gate plus the Backlog exemption and validated closure mutation,
  consulted once the human gives the post-merge authorization — that authorization is the approval
  for closure, so no second approval is asked, and closure is not gated on release publication
  itself.

> Detailed operational behavior lives in `rules/*.md`.
