# ship-it

The delivery stage of the Agentic Engineering pipeline. Starts from any approved GitHub issue that
satisfies its entry contract — typically one `plan-it` drafted, reviewed, and created, but not
necessarily; the requirement is an approved issue meeting that contract, not that `plan-it`
specifically produced it. Carries it through implementation review, semantic commits, verification,
closure, and the release/milestone lifecycle.

    lab-it   → understand and reconstruct reality
    plan-it  → turn understanding into approved work
    ship-it  → turn approved work into verified delivery      (this skill)

## When to use it

- Implementing, committing, verifying, or closing an approved issue.
- Checking whether a milestone is ready for a PR, or ready to close.
- Publishing a release once a milestone's PR has merged.

## How it works

Picks up one dependency-ready approved issue at a time, establishes the right branch (trunk for
Backlog/hotfix work, one shared branch per milestone), implements only its scope, and stops for
human review twice — once for the implementation, once for the proposed commit plan. Commits are
built around real implementation decisions, not file/folder splits, verified at the narrowest
reliable scope per commit plus one full run at the issue boundary. Closure is opt-in, always after
commits are confirmed reachable on the remote. Once a milestone's issues are all closed and manual
testing confirms nothing further, it reports PR-readiness. Once a PR merges and the human authorizes
proceeding, release publication and milestone closure run as two independent branches — neither
waits on the other.

Every GitHub mutation this skill performs — closure, release, milestone completion — is re-fetched
and validated afterward; a command's exit code is never treated as proof by itself. Release policy,
version scheme, and publish tooling are discovered from the project's own evidence, never assumed.

## Ownership

Owns branch readiness, the two pre-merge review gates, commit construction, verification scope, issue
closure, milestone PR-readiness, and the release/milestone-completion lifecycle. Does not own
deciding what issues should exist (`plan-it`) or how a PR gets reviewed and merged (the human). It
performs the approved implementation itself, consulting the applicable stack companion for
framework-specific conventions and implementation knowledge — the companion owns that knowledge, not
the application code:

    ship-it + applicable stack companion → verified change

## Rules

`SKILL.md` routes to `rules/sequencing.md` (branch readiness, next-issue recompute),
`rules/review-gates.md`, `rules/commit-boundaries.md`, `rules/verification.md`,
`rules/issue-closure.md`, `rules/milestone-completion.md`, and `rules/release.md`.
