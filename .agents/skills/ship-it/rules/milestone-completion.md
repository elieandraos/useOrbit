# Milestone Completion

## Principle

> Closure is gated on the human's post-merge authorization already covering it and the milestone
> having no open issues right now, both verified fresh immediately before the mutation. That
> authorization is the human approval for closure — this gate does not ask for it a second time.
> Closure also does not wait on the release that milestone represents to have shipped first — see
> `rules/milestone-lifecycle.md`'s "Milestone closure and release do not gate each other."

Closure is not an automatic consequence of issue closure, PR merge, or release publication — each of
those proves something narrower, and none of them individually proves the milestone is ready to
close. This rule owns the closure mutation itself: eligibility, the post-merge-authorization check,
the validated mutation, interrupted-attempt recovery, and reporting. Shared lifecycle guidance
(the entry map, delivery-milestone recognition, the Backlog exemption, description-as-scope-contract,
and closure/release independence) is `rules/milestone-lifecycle.md`'s, freely consultable at any
point, not gated by this rule's own trigger. PR readiness and authorized PR creation are
`rules/milestone-pr-readiness.md`'s, and the CI-failure investigation/authorization split in between
is `rules/ci-failure-correction.md`'s. None of these files owns deciding what belongs in the
milestone, implementing any of it, or approving/merging the PR — the human retains both.

## The closure gate

This is the second, later milestone-level gate — distinct from PR readiness
(`rules/milestone-pr-readiness.md`), and starting once the human has given the explicit post-merge
authorization (see `rules/milestone-lifecycle.md`'s "Where this phase starts"). That authorization
*is* the human approval for this mutation: this gate verifies eligibility against current fact, it
does not request a second, separate approval to close. Close the milestone once all three conditions
hold at once, checked fresh at the moment of the closure decision:

1. **It's a delivery/phase milestone, not Backlog** (or an equivalent persistent catch-all) — see
   `rules/milestone-lifecycle.md`'s "What counts as a delivery/phase milestone" and "Backlog is
   exempt" for how to classify it.
2. **The human's post-merge authorization actually covers closure.** This is the same authorization
   `rules/release.md`'s step 0 asks for right after the human confirms the PR merged — confirmed here
   as already given, not re-requested. It is not implied by the release itself having been drafted,
   published, or validated — see `rules/milestone-lifecycle.md`'s "Milestone closure and release do
   not gate each other."
3. **The milestone has no open issues right now** — re-query it fresh; don't reuse an earlier read
   from before authorization, since discovered work may have added an issue since.

If any one of these doesn't hold, do not close the milestone — report what's missing and stop, the
same discipline `implement-it/rules/issue-closure.md` uses for a declined close: don't ask again unprompted, the
human revisits it when ready.

- **An issue remains open** → do not close, regardless of release state. This holds even if the
  open issue looks trivial — the gate is on issue state, not a judgment call about the issue's
  size.
- **Authorization hasn't been given, or didn't cover closure** → do not close. This gate cannot
  substitute its own approval for that authorization, and it does not wait on the release to have
  published first to become eligible.
- **Manual testing (or anything else) added another issue to the milestone after authorization** →
  the milestone stays open until that issue is completed and the milestone is otherwise closeable
  again. Re-run the whole gate from scratch at that point rather than treating the new issue as the
  only thing left to check.
- **The milestone is Backlog or an equivalent persistent catch-all** → this gate does not apply to
  it at all. Don't run it, don't propose closing it, and don't treat its issue count as evidence of
  anything — see `rules/milestone-lifecycle.md`'s "Backlog is exempt."

Each condition is necessary on its own, but no single one is sufficient:

> Zero open issues ≠ milestone complete. Post-merge authorization alone ≠ milestone complete either.
> All three conditions, checked from current state, are what closure requires — and once they do,
> closing proceeds without asking the human to approve the same closure twice.

## Closing the milestone

Closing a milestone is a validated GitHub mutation, held to the same trust model as every other
mutation this workflow performs: do not infer success from the closure command's exit code alone.

Closure does not get its own, second human approval. The post-merge authorization already granted —
"close the milestone and start the release?" or whatever form it actually took — is the approval for
this mutation. This rule's job is to confirm that authorization is actually present and actually
covers closure, confirm the milestone is actually eligible, and then act — not to ask the human to
approve the same closure a second time.

1. **Confirm the gate, explicitly, against freshly queried state** — not memory from earlier in the
   conversation. State which of the three conditions is being confirmed and how, e.g.:
   - "Post-merge authorization for milestone `{title}` was given by the human on {reference}, and
     covered closure."
   - `gh issue list --milestone "{milestone title}" --state open` returns zero.

   If any condition doesn't hold — including authorization not actually having been given, or given
   but not scoped to closure — stop here and report what's missing instead of closing. Don't ask
   again unprompted; the human revisits it when ready.
2. **Before running the closure mutation, check whether a prior attempt already succeeded.** A lost
   response or a retried request is not evidence the milestone is still open — fetch its current
   state (the same query step 4 below runs) before mutating. If it's already closed, don't run the
   closure mutation again; validate the existing closed state against the gate just confirmed and
   report it as the completed result, rather than re-issuing a mutation that risks erroring or
   masking what actually happened.
3. **Run the closure**, using whatever mechanism the installed/project-supported GitHub tooling
   actually offers — discover it rather than assuming a specific command exists. For example:

   ```
   gh api repos/{owner}/{repo}/milestones/{number} -X PATCH -f state=closed
   ```

4. **Re-fetch the milestone afterward** and confirm its state is actually closed:

   ```
   gh api repos/{owner}/{repo}/milestones/{number}
   ```

   A successful exit code from step 3 is not proof; reading the result back is.
5. **Report the result compactly** — see "Reporting" below.

## Reporting

See `rules/milestone-pr-readiness.md`'s own "Reporting" for PR-readiness and PR-creation reports —
this section covers closure only.

**Closure**, report the validated result compactly:
- Milestone number/title.
- The three closure-gate conditions and how each was confirmed.
- The verified closed state.
- If not eligible: which condition is missing — nothing further is asked until the human revisits
  it.

Do not re-print the milestone's issue list or the release notes — the reader can follow the links.

## Cross-rule dependencies

This rule sits downstream of several other contracts and does not redefine any of them:

- **`rules/milestone-lifecycle.md`** owns the shared guidance named in the Principle above —
  condition 1 consumes its classification directly, without restating it.
- **`implement-it/rules/issue-closure.md`** closes each issue, intentionally before the milestone's PR
  merges — this rule's closure gate re-verifies zero open issues at the moment of closure, but doesn't
  re-decide whether any individual issue should have been closed.
- **`rules/release.md`** owns release drafting, publication, and post-publication validation, and
  its step 0 owns asking the post-merge authorization this rule's condition 2 also consumes. Neither
  rule's completion is a precondition for the other's gate — see `rules/milestone-lifecycle.md`'s
  "Milestone closure and release do not gate each other."
- **`plan-it`'s `rules/issue-conventions.md`** owns milestone classification, naming,
  descriptions, and issue drafting. This rule consumes that classification and description as
  given; it does not decide what belongs in a milestone, name one, or draft its description.
- **`rules/milestone-pr-readiness.md`** owns milestone PR readiness and authorized creation, the
  earlier milestone-level gate this one is deliberately narrower than.
  **`rules/ci-failure-correction.md`** owns the CI-failure investigation/authorization split between
  PR creation and merge. Neither is restated here.

## What this rule does not do

- It does not decide milestone scope or draft issues.
- It does not run closure at issue closure or at PR merge.
- It does not touch Backlog or define what counts as one (`rules/milestone-lifecycle.md` does).
- It does not require release publication first, or vice versa (`rules/milestone-lifecycle.md`'s
  "Milestone closure and release do not gate each other").
- It does not ask for a second, separate human approval before closing. The post-merge authorization
  already covers it; this rule only re-verifies that authorization and eligibility are both actually
  present before acting.

`rules/milestone-pr-readiness.md` and `rules/ci-failure-correction.md` state their own negatives for
PR readiness/creation and CI-failure correction respectively; not restated here.

## Do / Don't

**Do**
- Re-check all three closure-gate conditions, from fresh state, immediately before closure.
- Treat the same post-merge authorization that opens `rules/release.md`'s phase as this gate's own
  trigger too — not release validation.
- Confirm post-merge authorization was already given and actually covers closure — don't ask for it
  again — before running the closure mutation.
- Verify the resulting state by re-fetching the milestone after closing it.
- Re-query before retrying an interrupted or ambiguous closure mutation — a milestone already closed
  by an earlier attempt — and validate what's found rather than assuming nothing happened.

**Don't**
- Infer completion from zero open issues alone, or from release publication alone.
- Require release publication to finish before checking or passing the closure gate, or treat
  milestone closure as something `rules/release.md` must wait for.
- Propose or run closure against Backlog.
- Trust the closure command's exit code as proof of the resulting state.
- Treat a failed or timed-out query as proof a prior closure mutation didn't happen.
- Re-run the closure mutation against a milestone a prior attempt already closed.
- Close a milestone without re-checking issue state and authorization fresh, immediately before the
  mutation.
- Ask for a second, separate approval to close once post-merge authorization already covers it.

`rules/milestone-pr-readiness.md` and `rules/ci-failure-correction.md` each state their own Do/Don't
for PR readiness/creation and CI-failure correction; not restated here.
