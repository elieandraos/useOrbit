# ship-it

Take a completed milestone from PR readiness through delivery and release.

## When to use it

- Checking whether a milestone is ready for a PR, or creating that PR once it is.
- Investigating and explaining a delivery/CI failure on an open milestone PR.
- Checking whether a milestone is ready to close.
- Publishing a release once a milestone's PR has merged.

This skill checks the milestone's or PR's actual current GitHub state for each of these — it
doesn't require proof that a particular [`implement-it`](../implement-it/) session produced that
state, and it doesn't implement code itself.

## Boring prompts

```shell
"Check whether this milestone is ready for its pull request."
"Create the milestone PR."
"Why is CI failing on this PR?"
"Is this milestone ready to close?"
"Publish the approved release."
```

## What normally happens

1. Confirm the milestone's three PR-readiness conditions.
2. Discover the project's PR conventions, propose the PR, and create it once approved.
3. If CI fails on the open PR, investigate, explain the correction, and request the human's
   authorization for it — once given, [`implement-it`](../implement-it/) performs the fix using
   project guidance and applicable stack/implementation skills, and this skill resumes once it's
   verified and CI is green.
4. Once the human confirms the PR merged and authorizes the post-merge progression, close the
   milestone and draft, approve, publish, and validate the release.

## Ownership

Does not implement code. Deciding what work should exist belongs to
[`plan-it`](../plan-it/); implementation, verification, commits, and issue closure belong to
[`implement-it`](../implement-it/); reviewing and merging the PR belongs to the human.

## Context consumption

Activation loads only `SKILL.md`. Each of its five rule files loads for its own phase — shared
lifecycle orientation, PR readiness and creation, CI-failure investigation, closure, and release. The
shared lifecycle-orientation file (`rules/milestone-lifecycle.md`) is freely consultable at any point
and performs no mutation of its own; reaching it is not itself approval for anything. Each other
file's own gated mutation is separate — readiness passing is not approval to create the PR, and a
confirmed merge is not approval to publish the release. Each owning rule defines its own eligibility
conditions and the separate human approval its mutation requires; this section does not restate them.
See [the context-consumption model and representative-workflow
estimates](https://github.com/elieandraos/agentic-engineering/blob/main/docs/skill-context.md#ship-it).

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill ship-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
