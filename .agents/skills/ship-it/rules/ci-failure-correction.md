# CI Failure Correction

## When this applies

Real CI fails against an already-open, not-yet-merged milestone PR — a distinct moment from either
milestone-level gate: narrower than `rules/milestone-pr-readiness.md`'s readiness check (which only
confirmed local issue/testing state), and earlier than `rules/milestone-completion.md`'s post-merge
authorization (which hasn't happened yet because there's no merge to confirm). This file governs
investigating that failure, securing human authorization for an in-scope correction or routing
genuinely new scope elsewhere, and resuming once `implement-it` has verified and pushed the fix. It
does not require an existing PR to have come from this workflow's own PR-creation step, or a
specific `implement-it` session behind it — it starts from the PR's own current state.

## CI failure on an open milestone PR

> A milestone's PR looking ready and a milestone's PR actually being green are different facts. Real
> CI failing after the PR opens, before merge, is a distinct moment from either milestone-level
> gate — narrower than PR readiness (`rules/milestone-pr-readiness.md`, which only checked local
> issue/testing state), and earlier than the post-merge authorization
> (`rules/milestone-completion.md`, which hasn't happened yet because there's no merge to confirm).

This is not a new gate with its own approval — it's this rule naming a moment
`rules/milestone-lifecycle.md`'s own phase diagram ("Where this phase starts") would otherwise pass
over silently: real CI running against an already-open, not-yet-merged milestone PR can fail, and the
milestone stays in this in-between state — PR open, not merged, not authorized for post-merge
progression — until it's resolved.

**This rule investigates, explains, and secures the human's authorization; `implement-it` performs
the authorized correction.** This rule never edits application code itself — steps 1–5 below are
this rule's own job: investigating, determining scope, and either asking for and confirming the
human's explicit authorization for an already-approved-scope correction, or routing genuinely new
scope to `plan-it`'s discovered-work intake. Only step 6, performing the correction, is
`implement-it`'s — and only once the human has actually authorized it. This rule's own determination
that a fix stays in scope is necessary background for that authorization; it is not the authorization
itself, and never substitutes for it.

1. **The PR stays unmerged.** A red CI run on an open PR is never a reason to merge anyway, wait it
   out, or treat local green as sufficient — merge remains blocked until the PR is genuinely green
   again.
2. **Investigate the failure before recommending a remedy.** Root-cause it the same way any other
   unexpected finding gets investigated before a fix is chosen — don't guess at a correction from the
   failure message alone.
3. **Determine whether the correction stays within already-approved milestone scope, or introduces new
   scope or another decision, and explain what correction is needed.** A fix that only corrects what
   the milestone's own issues already approved (a config/workflow file wired up incorrectly, a
   dependency pin that needs adjusting to what was already intended) is different from one that
   touches something no issue in the milestone scoped — determine which this is, per
   `implement-it/rules/review-gates.md`'s "when to stop and ask" (a commit decomposition or scope
   question with no clearly better answer is exactly that kind of stop), and report the finding and
   the correction it calls for.
4. **A correction that stays within already-approved scope, including an already-closed issue's
   scope, requires explicit human authorization before `implement-it` performs it.** The issue that
   scope belongs to may have already gone through its own approved implementation, review, and
   closure (`implement-it/rules/issue-closure.md`) — reopening that work implicitly, without asking,
   would silently bypass the review this workflow already gave it. This does not require reopening
   the closed issue, or creating a new one, to permit the correction. Ask, and only hand the fix to
   `implement-it` once the human explicitly authorizes a direct fix.
5. **Otherwise, route the finding through the existing discovered-work intake.** A failure that reveals
   real, unscoped work — not a correction to something already approved — is a Discovered-work finding
   in exactly the sense `plan-it`'s `rules/discovered-work.md` already defines (the same
   intake `rules/milestone-pr-readiness.md`'s "When manual testing finds something" section also hands
   off to). Create or attach an issue to the still-open milestone when that intake finds the finding
   warrants one — this is not automatic for every CI failure; a narrow, already-scoped correction with
   explicit human authorization (step 4) can be the legitimate direct-fix path instead, without a new
   issue.
6. **`implement-it` performs the authorized correction through its own lifecycle** — Gate 1/Gate 2 as
   applicable (invoking `review-it` standalone before Gate 1, per
   `implement-it/rules/review-gates.md`'s "Consuming review-it's result"), commit construction
   (`implement-it/rules/commit-boundaries.md`), and verification
   (`implement-it/rules/verification.md`) — then pushes once authorized. This rule resumes once the
   correction is verified and pushed: confirm real CI runs again against the PR.
7. **No merge, milestone closure, or release progression until the PR is genuinely green and the human
   authorizes the next boundary.** A second (or later) real CI failure on the same PR repeats this
   section from step 1 — there is no cap on how many times this can legitimately happen before the PR
   is actually green.

Not every CI failure on an open milestone PR demands a new issue — a narrow, in-scope, explicitly
authorized direct fix (steps 3–4) is a legitimate outcome of this section, not a fallback to avoid.
What this section prevents is the other failure mode: silently patching the milestone branch past a
real CI failure with no authorization, or no investigation, because the milestone already looked
PR-ready. This rule does not implement the fix under this flow, and this route stays available
without requiring an open issue to exist.

## Cross-rule dependencies

This rule sits downstream of several other contracts and does not redefine any of them:

- **`plan-it`'s `rules/discovered-work.md`** owns the intake for a finding that reveals real,
  unscoped work — this rule hands off to it rather than defining its own investigation process, the
  same intake `rules/milestone-pr-readiness.md`'s manual-testing finding also hands off to.
- **`implement-it/rules/review-gates.md`** owns the "when to stop and ask" standard this rule applies
  to decide whether a correction stays in scope or needs a new decision. This rule investigates,
  explains, and secures the human's explicit authorization for the correction; only once that
  authorization is given does **`implement-it`'s own lifecycle** — that same gate, plus
  `implement-it/rules/commit-boundaries.md` and `implement-it/rules/verification.md` — actually
  perform, commit, and verify it. This rule does not implement, and its own scope determination is
  not itself the authorization.
- **`implement-it/rules/issue-closure.md`** already closed the issue whose scope a correction may
  touch — this rule doesn't redecide whether that closure was correct, and doesn't require reopening
  it to authorize a direct fix against its scope.
- **`rules/milestone-lifecycle.md`** owns the full delivery lifecycle's entry map ("Where this phase
  starts") this section names one moment of. This rule does not redefine it.
- **`rules/milestone-completion.md`** owns the post-merge authorization/closure gate this section's
  step 7 defers to. Not restated here.

## What this rule does not do

- It does not implement a delivery correction itself, and its own investigation grants no authority —
  `implement-it` performs the fix only once the human explicitly authorizes it.
- It does not require every real CI failure on an open milestone PR to produce a new issue — a
  narrow, already-approved-scope correction with explicit human authorization is a legitimate direct
  fix.

## Do / Don't

**Do**
- Keep an open milestone PR unmerged through a real CI failure, investigate before recommending a
  remedy, and get explicit human authorization before handing `implement-it` an already-closed
  issue's scope to correct directly on the branch.

**Don't**
- Merge, close the milestone, or start release progression while an open milestone PR's CI is red.
- Implement a delivery correction directly, or authorize `implement-it` to patch an already-closed
  issue's scope on the milestone branch without explicit human authorization; force every CI failure
  through a new issue when an authorized direct fix is the legitimate path.
