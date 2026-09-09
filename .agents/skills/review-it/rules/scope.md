# Establishing the Review

## When to consult this file

Always, at the start of every `review-it` invocation, before running `rules/checklist.md`.

## Principle

> A review is only as trustworthy as the target, baseline, and scope it was actually checked
> against. Establish all three before inspecting a single line, and report plainly whichever one
> couldn't be established rather than substituting a guess.

## Establish the review target

Determine whether the review covers a worktree (uncommitted or locally committed changes), a
branch (its full diff against a base), or a PR (its actual current diff on GitHub, not a stale
local checkout).

Discover the target from what's actually available: a target named explicitly in the request (a
branch name, a PR number or URL, "review my current changes"), the repository's current git state
when nothing else is named, or a PR already linked to the work under discussion. Ask the human only
when the target is genuinely ambiguous — more than one plausible candidate, or no working-tree
changes and nothing named — and the choice can't be resolved from a single unambiguous current
state.

## Establish the comparison baseline, where one applies

A worktree, branch, or PR review needs a baseline to diff against — and, for a branch or PR, the
correct starting point for that diff, not only its endpoint.

- **Uncommitted changes** compare against `HEAD`, and the comparison must cover the actual in-scope
  content: staged changes, unstaged changes to already-tracked files, and new files that are part of
  the reviewed work but not yet tracked by git at all. A diff against `HEAD` alone never surfaces an
  untracked file — enumerate untracked files separately (e.g. the untracked entries a status check
  reports) and inspect their actual content directly, in addition to the tracked diff, whenever
  they're part of what's being reviewed. Never stage a file, or otherwise mutate the worktree's
  index or content, merely to bring it into a diff for inspection — read it as it stands.
- **Discover the intended base**, for a branch or PR review, in order of reliability:
  1. An explicit comparison stated in the request controls this review outright — use it, even when
     it differs from what an associated PR would otherwise indicate.
  2. Absent an explicit request, an associated PR's own declared base branch, discovered from
     GitHub — never assumed from local branch naming.
  3. Absent both, other reliable evidence of the actual intended integration target — for example, a
     release- or branching-convention documented in project instructions, or an explicit statement
     already in the conversation about which branch this work is meant to land on.

  A branch's configured upstream or tracking branch is **not** evidence of its intended integration
  target, and is not a tier in this order at all. A feature branch commonly tracks its own remote
  counterpart (e.g. `origin/feature-x`) — that names where the branch's own commits are pushed, not
  what it's meant to merge into. Never select the tracking branch as the comparison base merely
  because it happens to be configured.

  Fall back to the merge-base with the project's trunk branch only once none of the tiers above is
  available — a defensible last resort, never the default first choice. Ask the human only when the
  available evidence leaves genuinely ambiguous, materially different candidate bases; otherwise use
  the most reliable evidence found.
- **Diff from the merge-base, not the base's current tip.** Once the intended base is established,
  identify the changes the reviewed branch or PR actually introduces by diffing from the merge-base
  of that base and the reviewed head — not a direct comparison against the base's current tip, which
  would also include whatever the base branch has itself gained since the branch diverged. This is
  the ordinary comparison for a branch or PR review; preserve it unless the request explicitly asks
  for a different comparison, per tier 1 above.
- **A PR's baseline** is the PR's own declared base branch, discovered from GitHub. When the
  request's explicit comparison differs from the PR's actual declared base, use the requested
  comparison per the discovery order above, and label it plainly in the review as the requested
  comparison, distinct from the PR's own declared base — never present the two as if they were the
  same thing.

Diffing against the wrong base or the wrong starting point — trunk instead of a branch's real
target, the base's current tip instead of the merge-base, or a requested comparison silently
reported as if it were the PR's own — silently misattributes changes the reviewed head did not
introduce, or hides the actual comparison in use. Treat this as a correctness risk to avoid, not a
harmless default.

Not every review needs a baseline — inspecting one already-isolated commit or a single file in
isolation does not. Treat this as conditional, not universal.

## Establish the intended scope

Discover intended scope from whatever is actually available: a linked issue or milestone
description, a PR description, an approved `plan.md`, explicit scope stated directly in the
request, or `Refs #N` trailers and commit messages already present in the diff.

`review-it` does not require a `plan-it`-authored issue, an approved `plan.md`, or a prior
`implement-it` session to run. A standalone worktree, branch, or PR with no issue at all is a
valid, ordinary target. When no scope evidence exists, state that plainly as a limitation in the
final report — never infer an intended scope from the diff's own shape and then treat that
inference as if it had been approved. A diff is evidence of what changed, never proof of what was
authorized.

## Establish available evidence

Gather what's actually reachable before starting: the diff itself, a linked issue or PR body,
`plan.md` or an architecture guide, CI results already produced by someone else, applicable project
instructions, and an applicable stack companion if one is installed. State plainly, in the final
report, which of these existed and which didn't — missing evidence is a limitation to report, not a
gap to silently fill by inference.

## Ask only when it materially matters

Ask the human only when an unresolved ambiguity would materially change what gets reviewed or the
standard it's checked against — for example, two equally plausible target branches, or a request
that could mean either "review my uncommitted changes" or "review the whole feature branch." Do not
ask about an ambiguity that wouldn't change the review's outcome; note it as a limitation instead
and proceed with the most defensible reading.

## Discover applicable conventions — portable and stack-aware

Discover applicable project instructions, repository conventions, and — when one is installed and
actually applicable to the reviewed change — a stack companion's rules, using the same
discoverable-tooling model `implement-it/rules/verification.md` already applies to test and lint
tooling: read from the repository's own instructions, configuration, and established usage, never
assumed in advance.

An optional stack companion sharpens the "Architectural fit," "Project/stack convention
compliance," and "Correctness and edge cases" checks when it applies to the reviewed stack. It is
never a prerequisite for running this review, and its absence does not disable applicable framework
checks. Discover framework and technology conventions the same way project conventions are
discovered — from project instructions, configuration, established usage already in the repository,
and other available authoritative guidance (documentation the project itself references, or a
convention the codebase's own established patterns already demonstrate) — not only from an installed
companion. Run the full checklist with no stack companion installed. Skip only the specific
sub-check that depends on a custom stack companion's own rules and has no other available
authoritative source for the same requirement; never skip a framework or correctness check that
established project evidence, or ordinary engineering reasoning, can otherwise support. State
plainly, in the report, which sub-checks were skipped this way and why — and, for a finding grounded
in framework or convention reasoning, whether it rests on an established project requirement or on
general technical reasoning with no such backing (see `rules/checklist.md`'s "Project and stack
convention compliance").

## Entry points reuse this procedure identically

Whether `review-it` is invoked standalone, by `implement-it` before Gate 1, or by `implement-it`
during an authorized delivery correction, this procedure runs the same way every time. A call from
`implement-it` typically supplies richer scope evidence (the approved issue, the completed diff);
a standalone call may supply less. Neither situation changes the procedure — only how much evidence
turns out to be available, which the final report states either way.
