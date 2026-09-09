# Milestone PR Readiness

## When this applies

Once a delivery/phase milestone genuinely has zero open issues remaining — the same condition
`implement-it/rules/sequencing.md`'s dependency-ready recompute reports, checked directly against
current GitHub state rather than requiring evidence that a specific `implement-it` session produced
it — this file governs whether the milestone's shared branch is ready to become a PR, and the
authorized creation of that PR once it is. See `rules/milestone-lifecycle.md`'s "Where this phase
starts" for how this fits the full delivery lifecycle end to end, including what happens once the PR
opens (`rules/ci-failure-correction.md`) and once it merges
(`rules/milestone-completion.md`'s closure gate). A Backlog or other persistent catch-all milestone
never reaches this gate — see `rules/milestone-lifecycle.md`'s "Backlog is exempt."

## Milestone PR readiness

> A milestone's shared branch becomes a PR candidate only once every one of its issues is closed,
> the human confirms final manual testing has actually been done, and that testing found nothing
> left to do.

This is the first milestone-level gate, and it is deliberately narrower than
`rules/milestone-completion.md`'s closure gate: it's a readiness check for a PR, not a check that the
milestone is finished. Passing it means "the branch is worth putting up for review," not "the
milestone is done."

### The three conditions

1. **Every issue in the milestone is closed right now.** Re-query fresh against current GitHub
   state — this is the same condition `implement-it/rules/sequencing.md`'s recompute reports as zero
   open issues remaining, distinct from an empty dependency-ready set, which can also occur while
   open issues remain, all blocked (that rule's "When the ready set is empty").
2. **Final manual testing has actually happened.** This isn't something this rule can verify from
   GitHub state — ask the human directly whether it's been done. Don't infer it from "all issues
   closed" or from time having passed.
3. **That testing found nothing further to do.** If it did, this gate does not pass — see "When
   manual testing finds something" below.

All three must hold together, checked fresh, the same discipline `rules/milestone-completion.md`'s
closure gate uses.

### When manual testing finds something

A bug or missing piece found during this final testing pass is a Discovered-work finding in exactly
the sense `plan-it`'s `rules/discovered-work.md` already defines — it goes through that
same intake, not a special case invented here. The result is a **new issue**, explicitly noting it
was discovered during or after the work represented by the original (now-closed) issue, attached to
this still-open milestone. Re-run this gate from scratch once that new issue closes.

**Do not silently reopen the original closed issue as the default behavior.** Closing that issue was
already an explicit, approved decision (`implement-it/rules/issue-closure.md`); a new finding doesn't retroactively
undo it. This rule takes no position on whether reopening is ever appropriate in some other
circumstance — it just isn't the default path a manual-testing finding takes.

### Issue closure precedes PR merge — intentionally

Every issue in the milestone is closed, per `implement-it/rules/issue-closure.md`, at the completed-issue boundary
— before the milestone's PR is even opened, let alone merged. This is the confirmed, intentional
shape of this workflow, not an oversight: an issue's closure marks that its implementation and
verification are done, not that its commits have reached the trunk branch yet. This gate and
`rules/milestone-completion.md`'s closure gate are what actually confirm the aggregate state of all
that already-closed work before it moves toward a PR and, later, a release.

### The milestone-PR reference convention

> A PR carrying milestone work is expected to reference the milestone it integrates.

This is a confirmed observed convention of this workflow, not archaeological context from a single
past run. "Milestone PR creation" below is what now produces a PR against that milestone once the
branch is PR-ready — this rule states the convention and is the one that carries it out, subject to
the human approval that section requires; it is no longer merely a contract this rule is aware of
without enforcing.

By contrast, a Backlog/hotfix issue worked directly on the trunk branch produces no PR at all
(`implement-it/rules/sequencing.md`'s "Branch readiness before starting an issue") — the reference convention
applies only to a milestone's PR.

### What this gate does not do

- It does not itself create the PR. Readiness is a report on the branch, not a mutation — creation is
  the separate, later, authorized step in "Milestone PR creation" below.
- It does not decide milestone closure. That's the separate, later gate `rules/milestone-completion.md`
  owns.

Report the result compactly: which of the three conditions hold, and — if not all — what's missing
and why. This is a report, not a mutation, so there's nothing to seek approval for beyond confirming
the manual-testing question with the human.

## Milestone PR creation

> Readiness alone does not authorize creation. Once the three conditions above pass, prepare a
> concrete PR proposal and get explicit human approval of its exact content before creating anything.

The request that led here — asking to check readiness, or asking to create the milestone PR —
already authorizes preparing that proposal; steps 1–3 below need no separate "may I start preparing"
question. What still requires its own explicit approval, before anything is created, is the specific
title, base/head branches, and body actually proposed (step 4) — preparation and creation are
different acts, and only the second is a mutation. This mutation is precedented by, and mirrors,
`rules/release.md`'s own discover → draft → approve → act → validate pattern — a bounded,
already-reviewed shape applied to a new mutation, not a new kind of gate.

1. **Discover the project's PR-target/base-branch convention.** The same discovery order
   `rules/release.md` applies to release mechanism: an explicit repository-stated convention first,
   then a pattern inferred from established history, then ask the human when the evidence is
   ambiguous or conflicting. Do not assume a base branch, head-branch naming, or PR-template
   requirement without evidence.
2. **Check for an existing PR before proposing creation.** Query the repository for an open PR
   against this milestone's head branch — a matching title alone is not sufficient identity, since a
   differently-scoped PR can share a title; confirm the actual repository, head, and base branches,
   and the milestone reference "The milestone-PR reference convention" expects. A failed or
   timed-out query is not proof no PR exists — retry it before concluding creation is still needed.

   - **An existing PR is found.** Identify and report what it actually is — number, base, head,
     title, and body — from that repository/head/base and milestone evidence alone. Recognizing it
     never requires a prior proposal from this workflow: a PR a human opened directly, or one from
     another process, is just as real a match as one this workflow itself proposed.
   - **The found PR is this workflow's own interrupted creation attempt, and its approved
     proposal is still available** (this session's own record of the exact title, base/head, and
     body approved in step 4 below). Validate the recovered PR against that exact proposal —
     title, branches, and body — the same standard a fresh creation's post-mutation check applies
     (step 5 below). A mismatch is a failed validation to report, not a discrepancy to accept
     silently.
   - **The proposal or approval evidence that stricter validation needs is missing** — a
     discovered PR this workflow never proposed, or one whose original proposal can no longer be
     recovered. Report that limitation plainly rather than inventing a proposal to validate
     against, duplicating the PR to force a fresh approval cycle, or silently changing its content
     to match what would have been proposed.

   Either way, finding a match means creation is not this rule's next step — never create a second
   PR for one that already exists.
3. **Draft the PR at PR scope.** Title, base and head branches, and a body referencing the milestone
   per "The milestone-PR reference convention" above — describing the integrated change as a whole,
   the same altitude distinction `rules/release.md` draws between a commit, a PR, and a release.
4. **Present the complete proposal — title, base/head branches, and body together — and stop for
   explicit human approval of that exact content before creating anything.** This is the one
   approval this mutation requires — of the specific content about to be created, the same content-
   approval discipline `rules/release.md`'s step 4 applies to a release's exact version/target/
   title/body. Passing readiness, or having been asked to create the PR, is not approval of this
   specific proposal; approval of this proposal is not the PR approval or merge that still belongs to
   the human once the PR exists.
5. **Create the PR through the discovered mechanism once approved**, then re-fetch it and verify the
   actual result — number, base, head, title, and body — instead of trusting the creation command's
   exit code. A mismatch is a failed validation to report and fix, not a cosmetic discrepancy.
6. **PR approval and merge stay entirely human-owned from here.** Creating the PR triggers the
   project's real CI (see `rules/milestone-lifecycle.md`'s "Where this phase starts"); this rule does
   not review, approve, or merge it.

## Reporting

**PR readiness**, report compactly:
- Milestone number/title.
- The three PR-readiness conditions and how each was confirmed (including the human's direct answer
  on manual testing).
- If not ready: what's missing, and — if a new issue was filed — its number and what it references.

**PR creation**, report the validated result compactly:
- The discovered PR-target/base-branch convention and its source.
- The proposed title, base/head branches, and body, and the human's approval of them.
- The created PR's number, base, head, title, and body as re-fetched — not merely the creation
  command's exit code.

## Cross-rule dependencies

This rule sits downstream of several other contracts and does not redefine any of them:

- **`implement-it/rules/sequencing.md`** owns recomputing the dependency-ready set and reports zero
  open issues remaining, distinct from an empty ready set that can still hold open, blocked issues —
  that condition is what makes this rule's PR-readiness check applicable, checked directly against
  current GitHub state rather than requiring a live report from a specific session. This rule doesn't
  recompute readiness itself.
- **`implement-it/rules/issue-closure.md`** closes each issue, intentionally before the milestone's PR merges.
  PR readiness's first condition consumes that closed state; this rule doesn't re-decide whether an
  issue should be closed.
- **`plan-it`'s `rules/discovered-work.md`** owns the intake for a manual-testing
  finding — this rule hands off to it rather than defining its own investigation process.
- **`rules/release.md`** owns the discover → draft → approve → act → validate pattern this rule's PR
  creation mirrors, and the project's PR-target/base-branch discovery order this rule reuses. Neither
  rule's completion is a precondition for the other's mutation.
- **`plan-it`'s `rules/issue-conventions.md`** owns milestone classification, naming,
  descriptions, and issue drafting. This rule consumes that classification and description as
  given; it does not decide what belongs in a milestone, name one, or draft its description.
- **`rules/milestone-lifecycle.md`** owns the full delivery lifecycle's entry map ("Where this phase
  starts"), what counts as a delivery/phase milestone, the Backlog exemption, and the milestone
  description as scope contract. This rule does not redefine any of those.
- **`rules/milestone-completion.md`** owns the closure gate this readiness gate is deliberately
  narrower than. Not restated here.

## What this rule does not do

- It does not decide milestone scope or draft issues.
- It does not review or merge the PR it creates — approval and merge stay human-owned. Authorized
  creation itself is this rule's own job ("Milestone PR creation" above).
- It does not run PR readiness before the milestone actually has zero open issues remaining.
- It does not touch Backlog or any other persistent catch-all milestone.
- It does not decide whether to reopen a closed issue — a manual-testing finding's default path is a
  new issue, not reopening (see "When manual testing finds something" above).

## Do / Don't

**Do**
- Check PR readiness only once the milestone actually has zero open issues remaining, re-verified
  against current GitHub state.
- Ask the human directly whether final manual testing has happened, rather than inferring it.
- File a manual-testing finding as a new Discovered-work issue, referencing the original.
- Treat the milestone description as the scope contract when one exists
  (`rules/milestone-lifecycle.md`'s "The milestone description, when present, is the scope
  contract").
- Discover PR conventions, check for an existing matching PR, and present a complete title/branches/
  body proposal for explicit human approval before creating the milestone PR.
- Re-fetch a created PR to verify number, base, head, title, and body instead of trusting the
  creation command's exit code.
- Re-query before retrying an interrupted or ambiguous PR-creation attempt — an existing matching
  PR — and validate what's found rather than assuming nothing happened.

**Don't**
- Infer PR readiness from zero open issues alone.
- Infer manual testing happened because issues are closed or time has passed.
- Silently reopen a closed issue as the default response to a later finding.
- Propose or run PR readiness against Backlog.
- Treat a failed or timed-out query as proof no existing PR exists, or identify an existing PR by
  title alone.
- Create a duplicate milestone PR without first checking for an existing one, or create any milestone
  PR before the human approves the exact proposed title, branches, and body.
