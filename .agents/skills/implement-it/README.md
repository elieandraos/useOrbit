# implement-it

Take an approved GitHub issue from implementation through verified commits and closure.

## When to use it

- Implementing, committing, verifying, or closing an approved issue.
- Checking what's next once a milestone issue closes.
- Performing a delivery correction that the human has explicitly authorized and `ship-it` has
  handed back — this doesn't require an issue to exist.

The issue can come from [`plan-it`](../plan-it/) or already exist through another valid
route — what matters is that it's approved, not who drafted it.

## Boring prompts

```shell
"Implement issue #42."
"Commit the approved work for issue #42."
"What's next in this milestone?"
```

## What normally happens

1. Establish the correct branch for the work.
2. Implement and verify the issue's scope.
3. Invoke [`review-it`](../review-it/) standalone against the completed work, fixing and
   re-reviewing any finding within scope.
4. Stop for human review — once on the implementation (citing `review-it`'s result), once on the
   proposed commit plan.
5. Build coherent commits and push them, once authorized.
6. Close the issue only once its commits are reachable on the remote.
7. Recompute the milestone's dependency-ready set and recommend the next issue — or, once zero
   open issues remain (not merely an empty ready set), hand off to [`ship-it`](../ship-it/).

## Ownership

Performs the approved implementation itself. Project context supplies repository and
domain conventions; an applicable stack companion (such as
[`laravel-inertia-stack`](../laravel-inertia-stack/)) supplies technology-specific
implementation knowledge. Deciding what work should exist belongs to
[`plan-it`](../plan-it/); implementation review belongs to [`review-it`](../review-it/), invoked
standalone before Gate 1 — this skill fixes what it finds, `review-it` never fixes anything itself;
milestone PR readiness, PR creation, and release belong to [`ship-it`](../ship-it/).

A specific-issue request ends after that issue's own lifecycle — completing one issue is
never by itself authorization to continue into the next, or into milestone delivery.

## Context consumption

Activation loads only `SKILL.md`. Five of its nine rule files load individually as an ordinary
single-issue lifecycle reaches the step each governs, roughly in this order: `sequencing.md`
(branch readiness), `verification.md` (implementing and pre-Gate-1 verification), `review-gates.md`
(Gate 1, then Gate 2), `commit-boundaries.md` (deriving and building the commit plan, after Gate 1),
and `issue-closure.md` (once commits exist). The other four are conditional escalations, loaded
only when their own trigger fires, and each fires on a distinct condition — but not an unrelated
one, since firing one can force another to fire too:
`commit-reconstruction.md` loads only when a review correction belongs to a commit already committed
locally but not yet pushed — a correction found before anything is committed never reaches it, by
that file's own entry condition. `activation-ordering.md` loads only when checking a commit against
runtime activation (configuration, a feature flag, environment-conditioned behavior) finds an
effect — ordinary dependency ordering never reaches it, and reaching it is itself one of the
examples that can also trigger the isolation escalation below.
`isolation-verification.md` and `worktree-preservation.md` load whenever `verification.md`'s own
isolation criteria are met — an intermediate committed state's own correctness needs proving on its
own — which is a broader trigger than reconstruction or activation ordering: it applies
unconditionally to every commit `commit-reconstruction.md` rebuilds, but it can equally apply to an
ordinary commit sequence built from a correction found before anything was committed, if that
sequence's own commit order is load-bearing or an intermediate commit's standalone correctness can't
otherwise be inferred. Only a pass that triggers none of `verification.md`'s isolation criteria,
needs no activation-gate reordering, and never needs history reconstruction loads none of the four.
[`review-it`](../review-it/) is invoked as a separate skill before Gate 1's report — well before
`commit-boundaries.md` or `issue-closure.md` are reached — adding its own entrypoint and rule files
to that pass. See [the context-consumption model and representative-workflow
estimates](https://github.com/elieandraos/agentic-engineering/blob/main/docs/skill-context.md#implement-it).

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill implement-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
