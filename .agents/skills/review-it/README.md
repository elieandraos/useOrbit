# review-it

Independently callable implementation review for a worktree, branch, or PR — verified findings, a
scoped clean result, or explicit limitations, never a fix applied on the spot.

## When to use it

- A branch, PR, or set of local changes needs an implementation-assurance review.
- [`implement-it`](../implement-it/) needs a clean-or-resolved review before it can stop at Gate 1.
- `implement-it` is performing a human-authorized delivery correction and needs the same review
  before that correction's own gates.

No prior `implement-it` session and no `plan-it`-authored issue are required — a standalone branch
or PR opened outside this ecosystem is a valid, ordinary target.

## Boring prompts

```shell
"Review this branch before I open a PR."
"Review PR #42."
"Review my current uncommitted changes."
```

## What normally happens

1. Establish the review target, comparison baseline, intended scope, and available evidence —
   asking the human only when an unresolved ambiguity would materially change the review.
2. Discover applicable project instructions and, where installed and relevant, a stack companion —
   never required; its absence skips only its own custom rules, not applicable framework checks
   otherwise supported by project instructions, configuration, or established usage.
3. Run the ten-category checklist (requirements compliance, correctness and edge cases, security,
   data integrity, likely regressions, architectural fit, maintainability, project/stack convention
   compliance, test adequacy, accidental scope expansion), skipping only what genuinely doesn't
   apply.
4. Verify every candidate finding against concrete evidence — source inspection, or a diagnostic
   command's actual output — before reporting it.
5. Report the reviewed state, confirmed findings, what was actually verified versus supplied by
   others, material limitations, and a scoped clean result when warranted.

## Ownership

Owns independently callable implementation review against the checklist above. Implementing a
correction stays with [`implement-it`](../implement-it/); guide review stays with
[`document-it`](../document-it/); issue-definition and dependency review stay with
[`plan-it`](../plan-it/); investigation discipline and plan-synthesis review stay with
[`lab-it`](../lab-it/); commit-plan review (Gate 2) stays with `implement-it`. A `review-it` result
never grants authorization by itself — every gate it feeds stays a human approval.

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill review-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
