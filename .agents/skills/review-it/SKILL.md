---
name: review-it
description: "Standalone implementation-review skill in the Agentic Engineering pipeline. Reviews a worktree, branch, or PR against a substantive, evidence-backed checklist and reports verified findings, a scoped clean result, or explicit limitations — never fixing what it finds. Callable standalone against any worktree, branch, or PR with no prior implement-it session or plan-it-authored issue required; also callable by implement-it before Gate 1 and during an authorized delivery correction. Trigger to review implemented code, review a branch or PR, or verify a correction before it's relied on. Reports only — it never fixes application code, applies formatting fixes, commits, pushes, approves a gate, merges, or mutates GitHub or other live/production state; every correction returns to implement-it. Does not own guide review, issue/plan-synthesis review, investigation discipline, or commit-plan review — those stay with document-it, plan-it, lab-it, and implement-it."
---

# review-it

## What this skill is

`review-it` is an independently callable implementation-assurance skill. It reviews a worktree,
branch, or PR against a substantive, evidence-backed checklist and reports verified findings, a
scoped clean result, or explicit limitations where evidence or diagnostics weren't available. It
does not fix what it finds — every correction returns to `implement-it`.

It is portable: it runs the same way whether or not `implement-it` or `plan-it` were ever involved.
A worktree with no issue, a branch with no plan, or a PR opened outside this ecosystem entirely are
all valid, ordinary targets.

## Entry points

The same capability, reached from three different places — not three procedures:

- **Standalone.** A human asks directly: review this branch, this PR, my current changes.
- **Before Gate 1.** `implement-it` invokes this skill once implementation and its own verification
  are complete, before stopping at Gate 1 (`implement-it/rules/review-gates.md`).
- **During an authorized delivery correction.** `implement-it` invokes this skill the same
  standalone way before that correction's own Gate 1/Gate 2 pass, when performing a correction
  `ship-it` has handed it (`ship-it/rules/ci-failure-correction.md`'s "CI failure on an open
  milestone PR").

No entry point requires another skill's context. A standalone invocation proceeds with whatever
scope evidence is actually available (see `rules/scope.md`) — it does not wait for, or invent, an
`implement-it` session or a `plan-it` issue that doesn't exist.

## Establishing the review

Before running the checklist, establish the review target, the comparison baseline where one
applies, the intended scope, and the evidence actually available — from the request and the
repository, never invented. Ask the human only when unresolved ambiguity would materially change
what gets reviewed or against what standard; otherwise proceed and record the ambiguity as a
limitation. See `rules/scope.md` for the full procedure, including how this skill discovers
applicable project instructions and an optional stack companion without requiring one.

## The checklist

Run the ten-category checklist in `rules/checklist.md`: requirements compliance, correctness and
edge cases, security, data integrity, likely regressions, architectural fit, maintainability,
project/stack convention compliance, test adequacy, and accidental scope expansion. Each category
is skip-if-inapplicable, not skip-by-default — skip only a category the reviewed change genuinely
doesn't touch, and say so.

## Verification, evidence, and reporting

Every finding is verified before being reported — traced to concrete code, or confirmed by a
diagnostic command's actual output — never a suspicion stated as fact. `review-it` may run the
project's own existing tests, linters, or static analysis to confirm a concern; this is diagnostic
execution, not read-only inspection, and may write caches or temporary state. See
`rules/verification.md` for the verification standard, diagnostic-execution boundaries, the
staleness rule, and the required report shape.

## What this skill never does

`review-it` reports; it does not fix application code, apply formatting fixes, commit, push,
approve a gate, merge, or mutate GitHub or any other live or production state — regardless of how
minor or obviously correct a change would be. A finding this skill could trivially fix by hand is
still reported, not applied. Every correction returns to `implement-it`.

## Ownership and handoff

This skill owns: independently callable implementation review against the checklist above, for a
worktree, branch, or PR.

This skill does not own:

- implementing a correction (→ `implement-it`);
- guide review (→ `document-it`);
- issue-definition and dependency review (→ `plan-it`);
- investigation discipline and plan-synthesis review (→ `lab-it`);
- commit-plan review, i.e. Gate 2 (→ `implement-it/rules/review-gates.md`);
- approving Gate 1, Gate 2, or any GitHub mutation — a review does not grant authorization; the
  human decision stays exactly where the calling skill already places it.

## Activation

Trigger on requests shaped like:

- `review this branch`
- `review PR #{n}`
- `review my current changes`
- an invocation from `implement-it` before Gate 1, or during an authorized delivery correction

## Rules

- `scope.md` — establishing the review target, comparison baseline, intended scope, and available
  evidence before running the checklist; discovering applicable project and stack conventions;
  consult first, at the start of every invocation.
- `checklist.md` — the ten-category substantive checklist with concrete inspection instructions;
  consult while inspecting the reviewed change.
- `verification.md` — verifying a finding against concrete evidence, diagnostic-execution
  boundaries, the staleness rule, and the required report shape; consult while confirming a finding
  and when preparing the final report.

> Detailed operational behavior lives in `rules/*.md`.
