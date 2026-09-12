# implement-it

Take an approved GitHub issue from implementation through verified commits and closure.

## When to use it

- Implementing, committing, verifying, or closing an approved issue.
- Checking what's next once a milestone issue closes.
- Performing a delivery correction that the human has explicitly authorized and [`ship-it`](../ship-it/) has handed back.

It is the implementation stage of `Lab -> Plan -> Implement -> Review -> Ship` and handles one approved
issue at a time.

## Boring prompts

```shell
"Implement issue #42."
"Commit the approved work for issue #42."
"What's next in this milestone?"
```

## What normally happens

1. Establish the correct branch, enumerate applicable skills, activate every applicable skill through the consuming agent's skill mechanism, and report the activation checkpoint before writing code.
2. Implement and verify the issue's scope.
3. Invoke [`review-it`](../review-it/) against the completed work, fixing and re-reviewing any finding within scope.
4. Stop for human review at Gate 1, including an `Activated skills:` line, then derive and present the semantic commit plan at Gate 2.
5. Build coherent commits, mechanically verify each actual committed message with a literal command (not a self-reported check) immediately after creating it, re-verify the whole unpushed range before push, and push once authorized.
6. Close the issue only once its commits are reachable on the remote.
7. Recompute the milestone's dependency-ready set and recommend the next issue — or, once zero open issues
   remain, hand off to [`ship-it`](../ship-it/).

Verification always requires targeted proof before Gate 1. The full regression suite is a separate human
choice at the issue boundary rather than an automatic requirement for every issue. Standalone Backlog/trunk
work is normally a good reason to recommend running it; an active phase milestone can reasonably defer it
when targeted verification is strong and the human deliberately chooses to skip it.

## Ownership

Performs the approved implementation itself. Project context supplies repository and domain conventions;
an applicable stack companion supplies technology-specific implementation knowledge. Deciding what work
should exist belongs to `plan-it`; implementation review belongs to `review-it`; milestone PR readiness,
PR creation, and release belong to `ship-it`.

A specific-issue request ends after that issue's own lifecycle — completing one issue is never by itself
authorization to continue into another issue or into milestone delivery.

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill implement-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
