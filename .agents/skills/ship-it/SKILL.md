---
name: ship-it
description: "Delivery-stage skill in the Agentic Engineering pipeline. Checks milestone PR readiness once a milestone genuinely has zero open issues remaining and prepares/creates the milestone PR through authorized human approval; investigates and explains delivery/CI failures on an already-open milestone PR, handing any human-authorized correction to `implement-it`; and, once the human confirms the PR merged and authorizes the post-merge progression, closes the milestone and prepares, publishes, and validates the release. Each entry point checks GitHub's actual current state rather than requiring proof of a prior `implement-it` session. Use when checking whether a milestone is ready for a PR, creating that PR, investigating a delivery/CI failure, checking whether a milestone is ready to close, or releasing a version. Does not implement code, decide what work should exist, or approve/merge the PR — the human retains both."
---

# ship-it

## What this skill is

`ship-it` is the delivery stage of the Agentic Engineering pipeline. It checks and acts on a
milestone's or PR's actual current state — PR readiness and creation, investigation and
continuation on an already-open milestone PR, and post-merge closure and release — rather than
requiring proof that a particular `implement-it` session produced that state.

## Pipeline position

`lab-it → plan-it → implement-it → ship-it`

This skill never decides what work should exist and never implements code — `implement-it` is what
closes a milestone's issues, but this skill checks the milestone's and PR's current state directly
against GitHub rather than requiring evidence that a specific `implement-it` session produced it.
Its entry points have different prerequisites, not one shared precondition:

- **Milestone PR readiness and creation** require the milestone to genuinely have zero open issues
  remaining, re-checked fresh — not merely an empty dependency-ready set, since open issues can all
  be blocked without the milestone being done.
- **Investigating or continuing on an already-open milestone PR** (a CI failure, a follow-up push)
  starts from the PR's own existence and state — it does not re-require zero open issues, since the
  milestone already passed that gate once to reach PR creation.
- **Post-merge closure and release** start from the human's confirmation that the PR merged and
  explicit authorization to proceed — independent of any `implement-it` session history in this
  conversation. Closure separately re-verifies zero open issues as one of its own three conditions
  (`rules/milestone-completion.md`'s "The closure gate"); release entry does not.

## What it owns

- Milestone PR readiness, once a milestone's issues are all closed.
- Authorized milestone PR creation, once readiness passes.
- Investigating a delivery/CI failure on an open milestone PR, explaining the correction needed,
  determining whether it stays within already-approved scope, and requesting the human's explicit
  authorization for it — handing off to `implement-it` only once that authorization is given, and
  resuming delivery once the fix is verified and CI is green.
- The post-merge authorization gate, milestone closure, and release preparation, publication, and
  validation.

## What it does not own

- Deciding what work should exist.
- Defining or scoping milestones.
- Application or framework implementation, or any implementation itself — including a delivery
  correction, which `implement-it` performs, using project guidance and applicable stack/
  implementation skills, once the human authorizes it.
- Stack-specific conventions.
- Working-branch readiness, implementation review, verification, commit construction, and issue
  closure — all `implement-it`'s.
- PR approval and merge — the human retains both.
- Deployment automation.

## Composition

- Git and GitHub are intentional core substrate for this methodology, not an abstraction to be
  swapped out.
- This skill composes with `implement-it` for any code correction the human explicitly authorizes
  after this skill's delivery-failure investigation — the investigation itself grants no authority.
- Stack-specific knowledge does not belong in this skill.

## Activation

Trigger on requests shaped like:

- `is milestone {name} ready for a PR`
- `create the milestone PR`
- `why is CI failing on this PR`
- `is milestone {name} ready to close`
- `release {version}`

## Rules

- `milestone-pr-readiness.md` — the three-condition PR-readiness gate (all issues closed + confirmed
  manual testing + no follow-up found), consulted once the milestone genuinely has zero open issues
  remaining (the same condition `implement-it/rules/sequencing.md`'s recompute reports, checked
  directly against current GitHub state); and authorized PR creation once readiness passes —
  convention discovery, existing-PR/interrupted-creation detection, the exact-content approval gate,
  and post-creation validation.
- `ci-failure-correction.md` — investigating and explaining a CI failure on an already-open milestone
  PR, determining whether a correction stays within already-approved scope, requesting the human's
  explicit authorization for it, and handing it to `implement-it` once given; consult once real CI
  fails against an open, not-yet-merged milestone PR.
- `milestone-lifecycle.md` — the shared delivery-lifecycle entry map (which of the four other files
  applies at each point), what counts as a delivery/phase milestone, the Backlog exemption, the
  milestone description as scope contract, and why closure and release don't gate each other. This
  shared guidance is consulted whenever milestone classification, scope interpretation, or lifecycle
  orientation is actually needed — including before merge, e.g. from `milestone-pr-readiness.md`'s
  own "Do" list when a manual-testing finding needs scoping, or to confirm Backlog eligibility. It
  performs no mutation of its own; consulting it authorizes nothing.
- `milestone-completion.md` — the three-part closure gate (delivery/phase milestone, confirmed via
  `milestone-lifecycle.md`; post-merge authorization already covering closure; zero open issues right
  now) plus the validated closure mutation, interrupted-attempt recovery, and reporting, consulted
  once the human gives the post-merge authorization — that authorization is the approval for closure,
  so no second approval is asked, and closure is not gated on release publication itself.
- `release.md` — the release phase: a post-merge authorization gate right after the human confirms a
  PR merged (the same gate that also opens `milestone-completion.md`'s closure gate — neither branch
  waits on the other), then discovering the project's real release policy, understanding the release,
  drafting notes at release altitude, the content-approval gate, publishing, and post-publication
  validation; consult once a PR carrying committed work has merged. Does not apply to Backlog/hotfix
  work, which has no PR to merge.

> Detailed operational behavior lives in `rules/*.md`.
