# Milestone Lifecycle

## Principle

> A delivery/phase milestone passes through two distinct milestone-level gates, never automatically:
> PR readiness, once every milestone issue is closed and final manual testing found nothing more —
> and closure, gated on the human's post-merge authorization already covering it and the milestone
> having no open issues right now, both verified fresh immediately before the mutation.

This file owns the shared entry map, delivery-milestone recognition, the Backlog exemption,
description-as-scope-contract, and closure/release independence — freely consultable at any point,
not gated by any one procedure's trigger. It performs no mutation of its own: closure (eligibility,
authorization check, mutation, recovery, reporting) is `rules/milestone-completion.md`'s; PR
readiness and creation are `rules/milestone-pr-readiness.md`'s; the CI-failure split is
`rules/ci-failure-correction.md`'s.

## Where this phase starts

The full delivery lifecycle has two distinct start points, for two distinct gates — do not collapse
them:

```
all milestone issues closed  →  MILESTONE PR READINESS  →  MILESTONE PR CREATION
                                                             (rules/milestone-pr-readiness.md —
                                                              authorized, human-approved)
                                                                          │
                                                                          ▼
                                                        real CI runs against the open PR
                                                                          │
                                                        fails? → see rules/ci-failure-correction.md
                                                        → investigate, human
                                                        authorizes → implement-it fixes, re-verifies
                                                        → real CI re-runs → repeat until green
                                                                          │
                                                                          ▼
                                                              (human merges once genuinely green)
                                                                          │
                                                                          ▼
                                                                    PR merged, human confirms it
                                                                          │
                                                                          ▼
                                       STOP: explicit human authorization to begin the
                                       post-merge progression (rules/release.md's step 0 —
                                       one ask, both branches below)
                                                    │                              │
                                                    ▼                              ▼
                                    MILESTONE CLOSURE GATE              release.md: discover policy
                                    (rules/milestone-completion.md)      → draft → approve → publish
                                                                                    → validate
```

- **Milestone PR readiness** (`rules/milestone-pr-readiness.md`) starts once the milestone genuinely
  has zero open issues left — the same condition `implement-it/rules/sequencing.md`'s dependency-ready
  recompute reports, checked directly against current GitHub state rather than requiring evidence
  that a specific `implement-it` session produced it. It never starts earlier — issues can close one
  at a time for a long time before this point, and that's expected, not a signal to check readiness
  early.
- **The closure gate** (`rules/milestone-completion.md`) starts once the human gives the explicit
  post-merge authorization — the same authorization `rules/release.md`'s step 0 asks for, right after
  a confirmed PR merge. It never starts at issue closure, at PR merge, or at PR-readiness itself —
  none of those is authorization. It also does not wait for release publication to complete first:
  closure and release both branch from the same authorization and proceed independently from there —
  see "Milestone closure and release do not gate each other" below.

Each arrow above is a distinct event with its own evidence. None of the earlier ones implies the
later ones:

- **Issue closure** (`implement-it/rules/issue-closure.md`) closes one issue once its committed work is
  approved — intentionally before the milestone's PR merges (see `rules/milestone-pr-readiness.md`'s
  "Issue closure precedes PR merge"). It says nothing about the milestone that issue belongs to —
  other issues in the same milestone may still be open, and more may still be discovered.
- **Milestone PR readiness** confirms the milestone's shared branch is a reasonable PR candidate. It
  is a report, not a mutation — it doesn't create the PR itself, and it doesn't mean a release was
  cut.
- **Milestone PR creation** (`rules/milestone-pr-readiness.md`) is the authorized mutation that
  follows a positive readiness report. It says nothing about whether CI passes, and it doesn't mean a
  release was cut.
- **PR merge** lands code. It doesn't mean a release was cut, or that the merged result has been
  validated yet.
- **Post-merge authorization** is the human's explicit go-ahead, right after confirming the PR
  merged, to begin the post-merge progression at all (`rules/release.md`'s step 0). It opens both
  `rules/milestone-completion.md`'s closure gate and `rules/release.md`'s drafting/publication —
  neither branch is implied to wait for the other to finish.
- **Release + release validation** (`rules/release.md`) confirms a specific version actually
  published correctly. It doesn't by itself confirm every issue the milestone needed is closed —
  release validation can pass cleanly while the milestone still has open work (a Backlog issue
  discovered and filed elsewhere, for instance) — and it is not a precondition
  `rules/milestone-completion.md`'s closure gate requires; see "Milestone closure and release do not
  gate each other" below.
- **Milestone completion check** is the first point where post-merge authorization and current issue
  state are confirmed together — see `rules/milestone-completion.md`'s "The closure gate."
- **Milestone closure** (`rules/milestone-completion.md`) is the mutation itself, gated and validated
  like every other GitHub mutation this workflow performs.

Do not let issue closure, PR-readiness, or PR merge auto-trigger milestone closure. None of them is
evidence of it on its own, and none of them is the closure gate's trigger point. Release publication
does not auto-trigger milestone closure either, and milestone closure does not auto-trigger release
publication — each proceeds through its own rule, both starting from the same authorization.

## What counts as a delivery/phase milestone

A delivery/phase milestone is a bounded body of work intended to ship as a release. That
definition is about scope and intent, not naming syntax — a milestone qualifies by what it bounds,
not by what it's called. Whatever naming convention a given project actually uses for its delivery
milestones, apply this rule the same way once that convention is identified — `plan-it`'s
`rules/issue-conventions.md` is where that convention gets defined (see "Cross-rule dependencies"
below).

A persistent Backlog or other catch-all milestone is not a delivery/phase milestone, regardless of
what it's named — see "Backlog is exempt" below. Backlog/hotfix issues never go through either gate
— no milestone branch, no PR, no PR-readiness check, no closure gate.

## The milestone stays open through discovered work

A milestone is not complete merely because every issue known about it right now is closed — this
holds at both milestone-level gates: PR readiness (`rules/milestone-pr-readiness.md`) and closure
(`rules/milestone-completion.md`). Implementation, review, manual testing, and any follow-up
discovered along the way can all still be in flight while the milestone stays open — that's the
expected shape of the middle of this lifecycle, not a sign something's wrong.

A small issue discovered during manual testing that genuinely belongs to this milestone's scope may
legitimately be added to the still-open milestone. Attach it there and keep working the milestone —
don't force it into a separate milestone, or into Backlog, just to preserve a "zero open issues"
appearance on this one.

> Do not infer "milestone complete" from open issues = 0 alone. Zero open issues is necessary for
> PR readiness and for closure, never sufficient by itself for either — see
> `rules/milestone-pr-readiness.md` and `rules/milestone-completion.md`'s closure gate. It's also not
> a permanent signal: a milestone can go from 0 open issues back to more than 0 the moment manual
> testing surfaces something real, and that's a legitimate state, not a bug in the process.

## The milestone description, when present, is the scope contract

`plan-it`'s optional milestone-description convention doesn't change here. When a
milestone carries a description defining its intent, boundaries, exclusions, or completion
criteria, that description is what "does this belong to this milestone's scope" gets checked
against when deciding whether a manual-testing finding belongs in this still-open milestone or
somewhere else. This rule consumes that description as-is — it does not draft, redraft,
reinterpret, or second-guess it (see "Cross-rule dependencies" below).

## Milestone closure and release do not gate each other

> Both branches start from the same event — the human's explicit post-merge authorization — and
> each proceeds entirely through its own rule from there. Neither is a precondition for the other.

`rules/milestone-completion.md`'s closure gate does not wait for `rules/release.md`'s
post-publication validation to have passed, and `rules/release.md` does not wait for that closure
gate to have run. On the project this workflow was extracted from, the human has typically closed
the milestone first and drafted the release after — but that is an observed sequencing habit on one
project, not a rule either file enforces. A different project running the two in the opposite order,
interleaved, or with real time between them, is equally valid under this methodology.

## Backlog is exempt

A persistent Backlog (or equivalent catch-all) milestone is never subject to this lifecycle, either
gate. It isn't a bounded body of work shipping as a release, so "is it PR-ready" and "did its release
ship" and "does it have zero open issues right now" are all meaningless questions for it. Do not
propose a PR-readiness check or a closure against it, do not run either gate against it, and do not
treat a quiet stretch of zero open issues on it as anything worth acting on.

## Cross-rule dependencies

This file sits alongside, not downstream of, the gates that consult it, and performs no mutation of
its own:

- **`implement-it/rules/issue-closure.md`** closes each issue, before the milestone's PR merges.
- **`plan-it`'s `rules/issue-conventions.md`** owns milestone classification, naming, descriptions,
  and issue drafting; this file consumes that as given.
- **`rules/milestone-pr-readiness.md`** owns PR readiness and authorized creation.
- **`rules/ci-failure-correction.md`** owns the CI-failure investigation/authorization split.
- **`rules/milestone-completion.md`** owns closure: eligibility, authorization check, the validated
  mutation, recovery, and reporting.
- **`rules/release.md`** owns release drafting, publication, and validation.

None of these is restated here; this file supplies only the shared classification and orientation
each of them consumes.

## What this file does not do

It performs none of the gates above — no closure eligibility, authorization, or mutation; no PR
readiness or creation; no CI-failure investigation or authorization; no release drafting or
publication. Consulting it authorizes nothing: reading the entry map or the independence guidance is
orientation, not approval for whichever gate's mutation comes next.
