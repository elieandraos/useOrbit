# Verification, Evidence, and Reporting

## When to consult this file

While confirming a candidate finding from `rules/checklist.md` earns its place in the report, and
when preparing the final report itself.

## Verify before reporting

Every finding is verified before being reported — traced to a concrete file and line, or confirmed
by a diagnostic command's actual output — never a suspicion stated as fact. A successful command or
a passing exit code is not, by itself, proof that it validated the specific property in question;
read what the command actually checked, not merely whether it exited zero.

Distinguish, explicitly in the report, findings `review-it` actually verified itself — by source
inspection, reproduction, or diagnostic execution — from evidence merely supplied by someone else
(a CI result quoted in the request, a claim already made in a PR description) and not independently
checked. Never imply a finding was reproduced by execution when it was established through source
inspection alone; state which one it was.

## Diagnostic execution

`review-it` may run the project's own existing verification commands — tests, linters, static
analysis — to confirm a specific concern, discovered the same way
`implement-it/rules/verification.md` discovers them: from the repository's own instructions,
configuration, scripts, CI definitions, or established usage, never assumed in advance.

- Inspect a command and its environment before running it — what it does, what it writes, and what
  it could affect — rather than running an unfamiliar script on trust.
- This is diagnostic execution, not read-only inspection. Running a test suite, a linter, or a
  static analyzer can write to caches, temporary directories, or other local state. Do not describe
  this review's execution as free of side effects.
- If safe diagnostic execution isn't available — no relevant command discoverable, a command that
  would require credentials or write access this session doesn't have, or an environment that can't
  run it — report that limitation plainly rather than skipping the concern silently or guessing at
  the result.
- Never mutate GitHub or any other live or production state to gather evidence. Reading published
  state (an existing PR's diff, its CI results, its description) is evidence-gathering; creating,
  editing, or merging anything is not this skill's job under any circumstance.

## Staleness and review identity

A finding, or a clean result, is tied to the specific state it was checked against — the same
discipline `plan-it/rules/review.md` already applies to issue review. State that identity precisely
enough to actually distinguish the reviewed state from a different one, not merely a label that
happens to be available:

- **A branch or PR review** is not fully identified by its head commit SHA alone — the identical
  head diffed against a different base produces a different diff, and can produce different
  findings, so the head SHA by itself does not name what was actually reviewed. State: the head
  commit SHA; the resolved base (the branch or ref actually used, per `rules/scope.md`'s "Establish
  the comparison baseline"); the actual comparison-start SHA where one applies (the merge-base of
  that base and the head, when the merge-base method was used); and the comparison method itself —
  a merge-base diff against the resolved base, or the request's own explicit alternative comparison,
  named as what it actually is. This combination, not the head SHA alone, is what distinguishes this
  review from a different review of the identical head compared against a different base.
- **An isolated commit reviewed with no comparison** — its own content inspected directly, not
  diffed against a base — is identified by its exact commit SHA alone; there is no base to record.
- **A worktree carrying uncommitted content** is not fully identified by `HEAD` alone either. `HEAD`
  names only the last commit; it says nothing about the staged, unstaged, or untracked content
  sitting on top of it, and two different dirty states can share the identical `HEAD`. State the
  `HEAD` commit plus an identity for the actual uncommitted content reviewed: the tracked diff's own
  content (a content hash, or the diff itself when short enough to state in full) and an explicit
  accounting of every untracked file reviewed, by path and content as reviewed (`rules/scope.md`'s
  "Establish the comparison baseline").

None of the above is a durable registry or storage mechanism `review-it` maintains between
invocations — each invocation states its own identity fresh, in its own report; it exists so the
identity alone lets the caller recognize exactly what state, and what comparison, was actually
checked.

A material change invalidates this pass for the affected surface, whether or not `HEAD` itself
moved: an edit made to an already-reviewed file with no new commit is a material change; so is a
change to the resolved base or the comparison method — a different declared base, a rebase, or a
switch from one comparison to another — even when the head commit is unchanged. Never carry forward
a clean result across a changed base or comparison merely because the head SHA looks the same;
reassess before relying on it again. The caller — a human, or `implement-it` before Gate 1 —
requests a fresh pass, full or scoped to the correction, before relying on this result again.
`review-it` does not track or store a review's history itself; each invocation is stateless with
respect to any prior pass, and relies entirely on the caller supplying the current state and
comparison to check.

**A scoped re-review** — invoked against only the affected surface after a fix — states plainly
which files or surface it actually checked this time, alongside the same state/comparison identity
described above for whatever it was actually run against. Naming the surface does not replace
naming that identity; both are required. It must not imply, by omission or general language, that it
independently rechecked the entire implementation; its "Reviewed target and state" (below) names the
scoped surface explicitly alongside that identity, not the whole worktree/branch/PR's full identity
as if every category had run again.

## Report shape

Every `review-it` result states:

- **Reviewed target and state** — the worktree, branch, or PR reviewed, and its precise identity per
  "Staleness and review identity" above: for a branch or PR, the head SHA, the resolved base, the
  comparison-start SHA where one applies, and the comparison method; for an isolated commit with no
  comparison, its commit SHA alone; for a dirty worktree, `HEAD` plus the actual diff/
  untracked-content identity. A scoped re-review states the scoped surface it actually checked
  alongside that same identity, not the whole target's full-coverage identity as if every category
  had run again.
- **Confirmed findings** — ordered by consequence, each with its file or location, the evidence or
  reasoning that verified it, its concrete consequence if left unaddressed, and — when the finding
  depends on a requirement or project convention (`rules/checklist.md`'s "Philosophy") — the
  specific source that requirement or convention actually comes from, stated concisely rather than
  as a requirement-by-requirement table.
- **Verification performed** — which checks `review-it` actually ran or traced itself, distinguished
  from evidence supplied by others and not independently verified (see "Verify before reporting,"
  above).
- **Material limitations and unresolved questions** — missing scope evidence, unreachable
  diagnostics, or an ambiguity that was proceeded past rather than resolved (`rules/scope.md`).
- **A scoped clean result**, when warranted — state plainly which categories applied and passed, and
  which were skipped as inapplicable, rather than a bare "looks good."

Do not report a finding as resolved, or a review as clean, merely because no evidence of a problem
was found where evidence was never actually available to check. An unchecked category is a
limitation to state, not a pass to imply.

## A review does not grant authorization

A clean `review-it` result, or a set of findings marked resolved and re-verified, is input to Gate
1's stop condition — it is not itself an approval, and it never substitutes for the human's
decision at Gate 1, Gate 2, or any other approval boundary a calling skill owns. `review-it` never
implies that a clean result authorizes anything to proceed on its own.

## What review-it never does

Reports only. It does not edit application code, apply formatting fixes, commit, push, approve a
gate, merge, or mutate GitHub or any other live or production state — regardless of how minor or
obviously correct a fix would be. A finding this skill could trivially fix by hand is still
reported, not applied. Every correction returns to `implement-it`.
