# Milestone Completion

## Principle

> A delivery/phase milestone passes through two distinct milestone-level gates, never automatically:
> PR readiness, once every milestone issue is closed and final manual testing found nothing more —
> and closure, gated on the human's post-merge authorization already covering it and the milestone
> having no open issues right now, both verified fresh immediately before the mutation. That
> authorization is the human approval for closure — this gate does not ask for it a second time.
> Closure also does not wait on the release that milestone represents to have shipped first — see
> "Milestone closure and release do not gate each other" below.

Neither gate is an automatic consequence of issue closure, PR merge, or release publication — each of
those proves something narrower, and none of them individually proves the milestone is ready for the
next step. This rule owns both checks and the closure mutation itself; it does not own deciding what
belongs in the milestone, and it does not create, review, or merge the PR either gate leads toward.

## Where this phase starts

This rule has two distinct start points, for two distinct gates — do not collapse them:

```
all milestone issues closed  →  MILESTONE PR READINESS  →  (human creates/reviews the PR)
                                                                          │
                                                                          ▼
                                                        real CI runs against the open PR
                                                                          │
                                                        fails? → see "CI failure on an open
                                                        milestone PR" below → fix → re-verify →
                                                        real CI re-runs → repeat until green
                                                                          │
                                                                          ▼
                                                              (human merges once genuinely green)
                                                                          │
                                                                          ▼
                                                                    PR merged, human confirms it
                                                                          │
                                                                          ▼
                                       STOP: explicit human authorization to begin the
                                       post-merge progression (rules/release.md's step 0 —
                                       one ask, both branches below)
                                                    │                              │
                                                    ▼                              ▼
                                    MILESTONE CLOSURE GATE              release.md: discover policy
                                    (this file, below)                   → draft → approve → publish
                                                                                    → validate
```

- **Milestone PR readiness** (below) starts once `rules/sequencing.md`'s dependency-ready
  recompute finds zero open issues left in the milestone. It never starts earlier — issues can close
  one at a time for a long time before this point, and that's expected, not a signal to check
  readiness early.
- **The closure gate** (below) starts once the human gives the explicit post-merge authorization —
  the same authorization `rules/release.md`'s step 0 asks for, right after a confirmed PR merge. It
  never starts at issue closure, at PR merge, or at PR-readiness itself — none of those is
  authorization. It also does not wait for release publication to complete first: closure and release
  both branch from the same authorization and proceed independently from there — see "Milestone
  closure and release do not gate each other" below.

Each arrow above is a distinct event with its own evidence. None of the earlier ones implies the
later ones:

- **Issue closure** (`rules/issue-closure.md`) closes one issue once its committed work is
  approved — intentionally before the milestone's PR merges (see "Issue closure precedes PR merge"
  under "Milestone PR readiness" below). It says nothing about the milestone that issue belongs to —
  other issues in the same milestone may still be open, and more may still be discovered.
- **Milestone PR readiness** confirms the milestone's shared branch is a reasonable PR candidate. It
  doesn't create the PR, and it doesn't mean a release was cut.
- **PR merge** lands code. It doesn't mean a release was cut, or that the merged result has been
  validated yet.
- **Post-merge authorization** is the human's explicit go-ahead, right after confirming the PR
  merged, to begin the post-merge progression at all (`rules/release.md`'s step 0). It opens both the
  closure gate below and `rules/release.md`'s drafting/publication — neither branch is implied to wait
  for the other to finish.
- **Release + release validation** (`rules/release.md`) confirms a specific version actually
  published correctly. It doesn't by itself confirm every issue the milestone needed is closed —
  release validation can pass cleanly while the milestone still has open work (a Backlog issue
  discovered and filed elsewhere, for instance) — and it is not a precondition this rule's closure
  gate requires; see "Milestone closure and release do not gate each other" below.
- **Milestone completion check** is the first point where post-merge authorization and current issue
  state are confirmed together — see "The closure gate" below.
- **Milestone closure** is the mutation itself, gated and validated like every other GitHub
  mutation this workflow performs.

Do not let issue closure, PR-readiness, or PR merge auto-trigger milestone closure. None of them is
evidence of it on its own, and none of them is the closure gate's trigger point. Release publication
does not auto-trigger milestone closure either, and milestone closure does not auto-trigger release
publication — each proceeds through its own rule, both starting from the same authorization.

## Milestone closure and release do not gate each other

> Both branches below start from the same event — the human's explicit post-merge authorization —
> and each proceeds entirely through its own rule from there. Neither is a precondition for the
> other.

This rule's closure gate does not wait for `rules/release.md`'s post-publication validation to have
passed, and `rules/release.md` does not wait for this rule's closure to have happened. On the project
this workflow was extracted from, the human has typically closed the milestone first and drafted the
release after — but that is an observed sequencing habit on one project, not a rule either file
enforces. A different project running the two in the opposite order, interleaved, or with real time
between them, is equally valid under this methodology.

## What counts as a delivery/phase milestone

A delivery/phase milestone is a bounded body of work intended to ship as a release. That
definition is about scope and intent, not naming syntax — a milestone qualifies by what it bounds,
not by what it's called. Whatever naming convention a given project actually uses for its delivery
milestones, apply this rule the same way once that convention is identified — `plan-it`'s
`rules/issue-conventions.md` is where that convention gets defined (see "Cross-rule dependencies"
below).

A persistent Backlog or other catch-all milestone is not a delivery/phase milestone, regardless of
what it's named — see "Backlog is exempt" below. Backlog/hotfix issues never go through either gate
below — no milestone branch, no PR, no PR-readiness check, no closure gate.

## Milestone PR readiness

> A milestone's shared branch becomes a PR candidate only once every one of its issues is closed,
> the human confirms final manual testing has actually been done, and that testing found nothing
> left to do.

This is the first milestone-level gate, and it is deliberately narrower than the closure gate below:
it's a readiness check for a PR, not a check that the milestone is finished. Passing it means "the
branch is worth putting up for review," not "the milestone is done."

### The three conditions

1. **Every issue in the milestone is closed right now.** Re-query fresh — this is the same trigger
   `rules/sequencing.md`'s recompute reports when the ready set comes back empty.
2. **Final manual testing has actually happened.** This isn't something this rule can verify from
   GitHub state — ask the human directly whether it's been done. Don't infer it from "all issues
   closed" or from time having passed.
3. **That testing found nothing further to do.** If it did, this gate does not pass — see "When
   manual testing finds something" below.

All three must hold together, checked fresh, the same discipline the closure gate uses.

### When manual testing finds something

A bug or missing piece found during this final testing pass is a Discovered-work finding in exactly
the sense `plan-it`'s `rules/discovered-work.md` already defines — it goes through that
same intake, not a special case invented here. The result is a **new issue**, explicitly noting it
was discovered during or after the work represented by the original (now-closed) issue, attached to
this still-open milestone. Re-run this gate from scratch once that new issue closes.

**Do not silently reopen the original closed issue as the default behavior.** Closing that issue was
already an explicit, approved decision (`rules/issue-closure.md`); a new finding doesn't retroactively
undo it. This rule takes no position on whether reopening is ever appropriate in some other
circumstance — it just isn't the default path a manual-testing finding takes.

### Issue closure precedes PR merge — intentionally

Every issue in the milestone is closed, per `rules/issue-closure.md`, at the completed-issue boundary
— before the milestone's PR is even opened, let alone merged. This is the confirmed, intentional
shape of this workflow, not an oversight: an issue's closure marks that its implementation and
verification are done, not that its commits have reached the trunk branch yet. The milestone-level
gates in this file are what actually confirm the aggregate state of all that already-closed work
before it moves toward a PR and, later, a release.

### The milestone-PR reference convention

> A PR carrying milestone work is expected to reference the milestone it integrates.

This is a confirmed observed convention of this workflow, not archaeological context from a single
past run: the human opens the milestone's PR against that milestone once the branch is PR-ready. It
is stated here as a contract this rule is aware of, not as something it enforces — PR creation,
description, and content stay entirely human-owned (see "What this gate does not do" below and
`rules/release.md`'s "PR creation and merge strategy are not owned by this rule"). This rule does not
check, require, or block on a PR actually carrying that reference.

By contrast, a Backlog/hotfix issue worked directly on the trunk branch produces no PR at all
(`rules/sequencing.md`'s "Branch readiness before starting an issue") — the reference convention
applies only to a milestone's PR.

### What this gate does not do

- It does not create the PR. PR creation, review, and merge stay human-owned, exactly as
  `rules/release.md` already states for the phase after this one — this rule only reports whether the
  branch looks ready.
- It does not verify PR content. The milestone-PR reference convention above is stated as a contract
  this rule is aware of, not something it checks or enforces.
- It does not decide milestone closure. That's the separate, later gate below.

Report the result compactly: which of the three conditions hold, and — if not all — what's missing
and why. This is a report, not a mutation, so there's nothing to seek approval for beyond confirming
the manual-testing question with the human.

## CI failure on an open milestone PR

> A milestone's PR looking ready and a milestone's PR actually being green are different facts. Real
> CI failing after the PR opens, before merge, is a distinct moment from either gate above — narrower
> than PR readiness (which only checked local issue/testing state), and earlier than the post-merge
> authorization (which hasn't happened yet because there's no merge to confirm).

This is not a new gate with its own approval — it's this rule naming a moment its existing phase
diagram would otherwise pass over silently: real CI running against an already-open, not-yet-merged
milestone PR can fail, and the milestone stays in this in-between state — PR open, not merged, not
authorized for post-merge progression — until it's resolved.

1. **The PR stays unmerged.** A red CI run on an open PR is never a reason to merge anyway, wait it
   out, or treat local green as sufficient — merge remains blocked until the PR is genuinely green
   again.
2. **Investigate the failure before choosing a remedy.** Root-cause it the same way any other
   unexpected finding gets investigated before a fix is chosen — don't guess at a correction from the
   failure message alone.
3. **Determine whether the correction stays within already-approved milestone scope, or introduces new
   scope or another decision.** A fix that only corrects what the milestone's own issues already
   approved (a config/workflow file wired up incorrectly, a dependency pin that needs adjusting to what
   was already intended) is different from one that touches something no issue in the milestone
   scoped — check which this is before proceeding, per `rules/review-gates.md`'s "when to stop and ask"
   (a commit decomposition or scope question with no clearly better answer is exactly that kind of
   stop).
4. **A correction applied directly on the milestone branch, when the affected issue's scope is already
   closed, requires explicit human authorization first.** The issue that scope belongs to has already
   gone through its own approved implementation, review, and closure (`rules/issue-closure.md`) —
   reopening that work implicitly, without asking, would silently bypass the review this workflow
   already gave it. Ask, and only proceed once the human explicitly authorizes a direct fix.
5. **Otherwise, route the finding through the existing discovered-work intake.** A failure that reveals
   real, unscoped work — not a correction to something already approved — is a Discovered-work finding
   in exactly the sense `plan-it`'s `rules/discovered-work.md` already defines (the same
   intake this rule's "When manual testing finds something" section, above, also hands off to). Create
   or attach an issue to the still-open milestone when that intake finds the finding
   warrants one — this is not automatic for every CI failure; a narrow, already-scoped correction with
   explicit human authorization (step 4) can be the legitimate direct-fix path instead, without a new
   issue.
6. **Re-run the relevant local and remote verification.** Verify the correction the same way any other
   change in this workflow is verified (`rules/verification.md`), then push and let real CI run again
   against the PR.
7. **No merge, milestone closure, or release progression until the PR is genuinely green and the human
   authorizes the next boundary.** A second (or later) real CI failure on the same PR repeats this
   section from step 1 — there is no cap on how many times this can legitimately happen before the PR
   is actually green.

Not every CI failure on an open milestone PR demands a new issue — a narrow, in-scope, explicitly
authorized direct fix (steps 3–4) is a legitimate outcome of this section, not a fallback to avoid.
What this section prevents is the other failure mode: silently patching the milestone branch past a
real CI failure with no authorization, or no investigation, because the milestone already looked
PR-ready.

## The milestone stays open through discovered work

A milestone is not complete merely because every issue known about it right now is closed — this
holds at both gates above and below. Implementation, review, manual testing, and any follow-up
discovered along the way can all still be in flight while the milestone stays open — that's the
expected shape of the middle of this lifecycle, not a sign something's wrong.

A small issue discovered during manual testing that genuinely belongs to this milestone's scope may
legitimately be added to the still-open milestone. Attach it there and keep working the milestone —
don't force it into a separate milestone, or into Backlog, just to preserve a "zero open issues"
appearance on this one.

> Do not infer "milestone complete" from open issues = 0 alone. Zero open issues is necessary for
> PR readiness and for closure, never sufficient by itself for either — see the two gates above and
> below. It's also not a permanent signal: a milestone can go from 0 open issues back to more than 0
> the moment manual testing surfaces something real, and that's a legitimate state, not a bug in the
> process.

## The milestone description, when present, is the scope contract

`plan-it`'s optional milestone-description convention doesn't change here. When a
milestone carries a description defining its intent, boundaries, exclusions, or completion
criteria, that description is what "does this belong to this milestone's scope" gets checked
against when deciding whether a manual-testing finding belongs in this still-open milestone or
somewhere else. This rule consumes that description as-is — it does not draft, redraft,
reinterpret, or second-guess it (see "Cross-rule dependencies" below).

## The closure gate

This is the second, later milestone-level gate — distinct from PR readiness above, and starting once
the human has given the explicit post-merge authorization (see "Where this phase starts"). That
authorization *is* the human approval for this mutation: this gate verifies eligibility against
current fact, it does not request a second, separate approval to close. Close the milestone once all
three conditions hold at once, checked fresh at the moment of the closure decision:

1. **It's a delivery/phase milestone, not Backlog** (or an equivalent persistent catch-all).
2. **The human's post-merge authorization actually covers closure.** This is the same authorization
   `rules/release.md`'s step 0 asks for right after the human confirms the PR merged — confirmed here
   as already given, not re-requested. It is not implied by the release itself having been drafted,
   published, or validated — see "Milestone closure and release do not gate each other."
3. **The milestone has no open issues right now** — re-query it fresh; don't reuse an earlier read
   from before authorization, since discovered work may have added an issue since.

If any one of these doesn't hold, do not close the milestone — report what's missing and stop, the
same discipline `rules/issue-closure.md` uses for a declined close: don't ask again unprompted, the
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
  anything — see "Backlog is exempt."

Each condition is necessary on its own, but no single one is sufficient:

> Zero open issues ≠ milestone complete. Post-merge authorization alone ≠ milestone complete either.
> All three conditions, checked from current state, are what closure requires — and once they do,
> closing proceeds without asking the human to approve the same closure twice.

## Backlog is exempt

A persistent Backlog (or equivalent catch-all) milestone is never subject to this lifecycle, either
gate. It isn't a bounded body of work shipping as a release, so "is it PR-ready" and "did its release
ship" and "does it have zero open issues right now" are all meaningless questions for it. Do not
propose a PR-readiness check or a closure against it, do not run either gate against it, and do not
treat a quiet stretch of zero open issues on it as anything worth acting on.

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
2. **Run the closure**, using whatever mechanism the installed/project-supported GitHub tooling
   actually offers — discover it rather than assuming a specific command exists. For example:

   ```
   gh api repos/{owner}/{repo}/milestones/{number} -X PATCH -f state=closed
   ```

3. **Re-fetch the milestone afterward** and confirm its state is actually closed:

   ```
   gh api repos/{owner}/{repo}/milestones/{number}
   ```

   A successful exit code from step 2 is not proof; reading the result back is.
4. **Report the result compactly** — see "Reporting" below.

## Reporting

**PR readiness**, report compactly:
- Milestone number/title.
- The three PR-readiness conditions and how each was confirmed (including the human's direct answer
  on manual testing).
- If not ready: what's missing, and — if a new issue was filed — its number and what it references.

**Closure**, report the validated result compactly:
- Milestone number/title.
- The three closure-gate conditions and how each was confirmed.
- The verified closed state.
- If not eligible: which condition is missing — nothing further is asked until the human revisits
  it.

Do not re-print the milestone's issue list or the release notes — the reader can follow the links.

## Cross-rule dependencies

This rule sits downstream of several other contracts and does not redefine any of them:

- **`rules/sequencing.md`** owns recomputing the dependency-ready set and reports when it's empty —
  that report is what triggers this rule's PR-readiness check. This rule doesn't recompute readiness
  itself.
- **`rules/issue-closure.md`** closes each issue, intentionally before the milestone's PR merges.
  PR readiness's first condition consumes that closed state; this rule doesn't re-decide whether an
  issue should be closed.
- **`plan-it`'s `rules/discovered-work.md`** owns the intake for a manual-testing
  finding — this rule hands off to it rather than defining its own investigation process. A CI failure
  on an open milestone PR that reveals unscoped work hands off to the same intake (see "CI failure on
  an open milestone PR" above).
- **`rules/review-gates.md`** owns the "when to stop and ask" standard this rule's CI-failure section
  applies to decide whether a correction stays in scope or needs a new decision, and
  **`rules/verification.md`**/**`rules/commit-boundaries.md`** own how that correction is actually
  verified and committed once authorized — this rule doesn't restate either.
- **`rules/release.md`** owns release drafting, publication, and post-publication validation, and
  its step 0 owns asking the post-merge authorization this rule's condition 2 also consumes. Neither
  rule's completion is a precondition for the other's gate — see "Milestone closure and release do
  not gate each other."
- **`plan-it`'s `rules/issue-conventions.md`** owns milestone classification, naming,
  descriptions, and issue drafting. This rule consumes that classification and description as
  given; it does not decide what belongs in a milestone, name one, or draft its description.

## What this rule does not do

- It does not decide milestone scope or draft issues.
- It does not create, review, or merge the PR either gate leads toward — PR creation/merge/review
  stays human-owned.
- It does not run PR readiness before the ready set is actually empty, or closure at issue closure
  or at PR merge.
- It does not touch Backlog or any other persistent catch-all milestone.
- It does not decide whether to reopen a closed issue — a manual-testing finding's default path is a
  new issue, not reopening (see "When manual testing finds something" above).
- It does not require release publication to have completed before closing the milestone, and
  closing the milestone is not itself a precondition for release publication — the two proceed
  independently once the human gives post-merge authorization (see "Milestone closure and release do
  not gate each other").
- It does not ask for a second, separate human approval before closing. The post-merge authorization
  already covers it; this rule only re-verifies that authorization and eligibility are both actually
  present before acting.
- It does not require every real CI failure on an open milestone PR to produce a new issue — a
  narrow, already-approved-scope correction with explicit human authorization is a legitimate direct
  fix (see "CI failure on an open milestone PR" above).

## Do / Don't

**Do**
- Check PR readiness only once the milestone's ready set is actually empty.
- Ask the human directly whether final manual testing has happened, rather than inferring it.
- File a manual-testing finding as a new Discovered-work issue, referencing the original.
- Re-check all three closure-gate conditions, from fresh state, immediately before closure.
- Treat the same post-merge authorization that opens `rules/release.md`'s phase as this gate's own
  trigger too — not release validation.
- Treat the milestone description as the scope contract when one exists.
- Confirm post-merge authorization was already given and actually covers closure — don't ask for it
  again — before running the closure mutation.
- Verify the resulting state by re-fetching the milestone after closing it.
- Keep an open milestone PR unmerged through a real CI failure, investigate before remedying, and get
  explicit human authorization before correcting an already-closed issue's scope directly on the
  branch.

**Don't**
- Infer PR readiness or completion from zero open issues alone.
- Infer manual testing happened because issues are closed or time has passed.
- Silently reopen a closed issue as the default response to a later finding.
- Infer completion from release publication alone.
- Require release publication to finish before checking or passing the closure gate, or treat
  milestone closure as something `rules/release.md` must wait for.
- Propose or run either gate against Backlog.
- Trust the closure command's exit code as proof of the resulting state.
- Merge, close the milestone, or start release progression while an open milestone PR's CI is red.
- Patch an already-closed issue's scope directly on the milestone branch without explicit human
  authorization, or force every CI failure through a new issue when an authorized direct fix is the
  legitimate path.
- Close a milestone without re-checking issue state and authorization fresh, immediately before the
  mutation.
- Ask for a second, separate approval to close once post-merge authorization already covers it.
