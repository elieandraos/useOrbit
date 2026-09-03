# Issue Closure

## Principle

> Closure is an explicit human decision that happens only after committed work exists. A successful
> close command is not the same thing as a validated closure.

An issue's state on GitHub is not evidence of anything until it's been re-fetched and checked.
Running `gh issue close` without confirming what actually landed treats the CLI's exit code as
proof — it isn't. This rule governs both halves of that: when it's appropriate to ask about closing
an issue at all, and how to carry out and validate the closure once the human says yes.

**This closure intentionally happens before any PR carrying the issue's commits merges — or, for a
milestone issue, even before that PR exists.** Closure marks that this issue's implementation and
verification are done, not that its commits have reached the trunk branch. For a Backlog/hotfix issue
worked directly on the trunk branch, that gap is usually momentary or nonexistent; for a milestone
issue on a shared branch, several issues can close this way before the branch is ever proposed as a
PR — `rules/milestone-completion.md`'s "Milestone PR readiness" gate is what checks the aggregate
state of all that already-closed work before it moves toward a PR.

## Ask first

Once a committed, approved issue has passed verification, ask the human whether they want to close
it now.

- **Don't ask before that point.** There's nothing to close until the relevant work is committed.
- **Don't close automatically** just because the commits landed and verification passed — closure is
  opt-in, never inferred from a green build.
- **If the answer is no or not yet**, leave the issue open and don't ask again unprompted. The human
  will raise it when they're ready; repeating the question uninvited is not this rule's job.

## When a stated completion criterion can't be satisfied yet

Before asking to close, check whether the issue's own Acceptance Criteria or Tests state a completion
criterion that can't actually be satisfied at this point in this workflow — for example, a criterion
that requires a real CI run against a PR/push, when this issue closes (per this workflow's own
shared-milestone-branch model) before any PR for that milestone exists. This is
`my-feature-planning`'s `rules/issue-conventions.md`'s own authoring contract ("Completion criteria
must be satisfiable at their own closure boundary") — this rule doesn't redefine or duplicate it; it
only makes sure a contradiction between an issue's own text and this workflow's actual closure timing
gets surfaced instead of silently inherited.

- **Surface it, don't silently close past it.** State plainly, before asking to close, that this
  specific criterion cannot be met at this issue's own closure boundary under this workflow, and why.
- **Route it, don't resolve it here.** Whether to close anyway with the gap disclosed, defer the
  criterion explicitly to the actual milestone/PR boundary it can be proven at, or send the issue back
  through `my-feature-planning` for a wording correction is the human's call — this rule reports the
  contradiction; it doesn't pick among those outcomes.
- **This is not a reason to invent a workflow change.** It doesn't move where or when real CI actually
  runs, and it doesn't change this rule's own closure timing (see "Principle" above) — it only makes a
  pre-existing issue/workflow mismatch visible before an issue closes underneath it.

## Closure procedure

Once the human approves closing, run this four-part sequence in order. Don't skip ahead to closing
before the body and comment are in place, and don't report anything as done before the final
validation step passes.

1. **Verify which Tasks are actually complete, and update the body.** Fetch the current body:

   ```
   gh issue view N --json body -q .body
   ```

   Flip every `- [ ]` that's actually done to `- [x]`. Only the ones actually done: if a task was
   intentionally deferred to a later issue or trimmed from scope, leave it unchecked and explain why
   in the closing comment rather than silently checking it to make the issue look complete.

2. **Write the durable closing comment.** See "Closing comment" below for what belongs in it.

3. **Persist the body and comment, then close.**

   ```
   gh issue edit N --body-file <file>      # the updated body with checked Tasks
   gh issue comment N --body-file <file>   # the closing comment
   gh issue close N
   ```

4. **Re-fetch and validate the resulting state.** See "Validation" below — this step is not
   optional, and it's what turns "the close command didn't error" into "the issue is actually
   closed, correctly."

## Closing comment

The closing comment is the durable record future readers rely on instead of reconstructing history
from commits and conversation. It should be a concise summary, not a transcript. Include:

- **What was implemented** — a short summary of the outcome.
- **Verification results** — test counts, full-suite pass/skip/fail, per `rules/verification.md`.
- **The actual commit SHAs** that implement the issue.
- **Anything discovered during implementation or review that's worth preserving** — the kind of
  thing a future maintainer would otherwise have to reconstruct from the diff or ask about. For
  example:
  - a scope discrepancy discovered during implementation (the actual surface area turned out
    larger or smaller than the issue assumed);
  - a naming or architectural decision made during review (a rename for clarity, a trimmed
    allowlist, a boundary moved to a different layer);
  - any other implementation detail a future maintainer genuinely needs and won't get from the
    commit log alone.

Keep it a summary. If it starts reading like a re-narration of the working conversation, cut it back
down to the outcome.

## Validation

> Never report closure as complete from the mutation command's exit code alone. Re-fetch the source
> of truth and verify the resulting state.

All three of the following must be checked after the mutations in step 3, every time:

- **The issue is actually closed:**

  ```
  gh issue view N --json number,title,state,closed
  ```

  Confirm `state: CLOSED` and `closed: true`.

- **The checked-task count reflects what was really completed.** Re-fetch the body and confirm the
  number of checked boxes matches the number of tasks actually done — not the total number of tasks
  in the issue.

- **The closing comment actually exists.** Fetch the last comment and confirm its content is there;
  don't assume the `gh issue comment` call succeeded just because it didn't error.

## Reporting

Report the validated result compactly: issue number, title, resulting state, checked-task count, and
a link. Don't re-print the full issue body or the closing comment — the reader can follow the link.

## What this rule does not do

- **It does not decide whether the issue should be closed.** That's always the human's call, made in
  "Ask first" above — this rule only carries out and validates a closure once approved.
- **It only operates on the single issue** associated with the work that was just committed and
  verified. It does not touch any other issue.
- **It does not create issues.** That's `my-feature-planning`'s territory, not this rule's.
- **It does not reopen issues.** This rule owns closing a committed, verified issue — not reopening
  one. The observed default, when a later finding (e.g. milestone manual testing) concerns work this
  rule already closed, is a *new* issue referencing the original — not reopening it — per
  `rules/milestone-completion.md`'s "When manual testing finds something." That default doesn't make
  reopening this rule's job; it just means reopening isn't the path a normal finding takes.

## Do / Don't

**Do**
- Ask before closing, once work is committed and verified.
- Check off only genuinely completed tasks; explain deferred ones instead of checking them.
- Record useful, durable discoveries in the closing comment.
- Validate every GitHub mutation in this procedure by re-fetching and reading the result back.
- Surface a completion criterion the issue's own text states but this workflow can't yet satisfy,
  before asking to close, rather than closing past it silently.

**Don't**
- Close automatically because commits landed or verification passed.
- Check off deferred or out-of-scope work to make the issue look complete.
- Treat a successful CLI exit code as proof of the resulting state.
- Re-print the entire issue body or comment in the final report.
- Create issues from this rule — that belongs to `my-feature-planning`.
- Reopen issues from this rule — this rule does not own reopening.
