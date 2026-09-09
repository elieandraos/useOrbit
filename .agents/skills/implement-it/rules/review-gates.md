# Review Gates

## Principle

> Two human approvals gate everything before a merge — not one — and a genuine unresolved decision
> found along the way gets an explicit human stop, never a silent guess.

```
implement + verify → Gate 1: implementation review → derive commit plan → Gate 2: commit-plan review → build commits
```

Gate 1 and Gate 2 are never collapsed into a single approval, and approval at Gate 1 does not imply
approval at Gate 2. No commit is created before Gate 2 is approved.

The post-merge release workflow has its own separate approval boundary
(`ship-it/rules/release.md`), over the proposed version, tag target, title, and body. That boundary
is not part of either gate below — a merged PR does not satisfy it.

`rules/issue-closure.md`'s push-authorization request — asking whether to push already-approved
commits to the correct remote branch, before asking to close the issue — is a similar separate
boundary, not a third review gate. It approves nothing about the implementation or the commit
structure; Gate 1 and Gate 2 below remain the only approvals of either.

## Gate 1 — implementation review

Stop here once:

- the approved scope has been implemented — the approved issue, for ordinary implementation work,
  or the explicitly authorized correction, for a delivery correction (see "Consuming review-it's
  result," below, for how each supplies `review-it`'s intended scope);
- the verification appropriate to it has been run (`rules/verification.md`);
- `review-it`'s pass against the completed implementation is either clean, or its findings have
  been resolved and re-verified.

Invoke `review-it` standalone against the completed working tree once the first two bullets hold,
before reporting at this gate — see "Consuming review-it's result," below. A `review-it` pass does
not grant authorization by itself; it is evidence this gate's report cites, and Gate 1's approval
mechanics — the report below, then explicit human approval — stay exactly as they were.

Report concisely:

- what changed;
- the implementation approach;
- files or surface area touched, where useful;
- verification results;
- `review-it`'s result: clean, or each finding and how it was resolved and re-verified.

Then wait for explicit human approval. Approval at this gate authorizes moving on to commit
planning — nothing more. Do not begin deriving commit structure before it.

If implementation surfaces a genuine unresolved decision before reaching this point, stop and ask
then, per "When to stop and ask" below, rather than silently choosing an answer and presenting the
choice as part of this report.

## Consuming review-it's result

Once implementation and verification are complete, invoke `review-it` the same way a standalone
caller would (`review-it/rules/scope.md`) against the completed working tree, supplying the intended
scope this invocation is actually working from:

- **Ordinary implementation work.** Supply the approved issue as the intended scope, exactly as
  today — this bullet does not weaken or make optional the ordinary issue-entry requirement above.
- **An authorized delivery correction.** No approved issue is required to reach this gate at all
  (`SKILL.md`'s "Delivery corrections"); supply the explicitly authorized correction scope and its
  supporting evidence instead — the investigated failure, the scope determination, and the human's
  authorization (`ship-it/rules/ci-failure-correction.md`'s "CI failure on an open milestone PR").

Either way, this gate always supplies whatever intended-scope evidence it actually has to
`review-it` — it never invokes `review-it` with no scope evidence at all, unlike a standalone caller
who may genuinely have none. Treat `review-it`'s result as follows before reporting at this gate:

- **Clean.** Report it as such and proceed to the Gate 1 report above.
- **Findings, within this skill's authorized scope to resolve.** Fix them, then request a re-review
  scoped to the affected surface — a material change invalidates `review-it`'s prior pass for that
  surface (`review-it/rules/verification.md`'s staleness rule). Report the original findings and
  their resolution, plus the re-review's clean result, at Gate 1.
- **A finding that reveals a genuine unresolved decision** — architecture, scope, or a choice with
  no clearly better answer — is not this gate's to resolve silently. Stop and ask, per "When to
  stop and ask," below, citing `review-it`'s finding as the evidence.
- **A material limitation `review-it` reports** — missing evidence, an unreachable diagnostic, or an
  ambiguity it proceeded past rather than resolved (`review-it/rules/verification.md`'s report
  shape). When the missing evidence is actually obtainable from this skill's own context (the
  approved issue, the authorized correction's scope, a project instruction `review-it` couldn't
  reach), supply it and request a fresh pass. When it isn't obtainable, report the specific blocker
  and the human decision it requires — this is a stop under "When to stop and ask," below, not a
  silent gap. An optional absent artifact that `review-it` itself treated as inapplicable — no stack
  companion, no PR description beyond what was already supplied — does not by itself trigger this
  bullet; only a limitation that leaves a required check genuinely unresolved does.

Never report a finding as resolved, or treat an unresolved finding or a material limitation as if it
were clean, without an actual fix — or actually obtained evidence — and an actual re-review behind
that claim, the same discipline `review-it` itself applies to its own report
(`review-it/rules/verification.md`). Stopping to ask about a limitation or an unresolved finding is
not, by itself, Gate 1's approval — it is the same kind of pause "When to stop and ask" already
describes, and Gate 1 still requires its own explicit human approval of the complete report
afterward, per the two-gate mechanics in "Principle," above.

## Gate 2 — commit-plan review

Only after Gate 1 is approved:

1. Inspect the completed diff (`rules/commit-boundaries.md`).
2. Derive the semantic commit plan.
3. Present the plan before creating any commit.

The plan must communicate:

- the proposed grouping — which files, in which commit;
- the commit order, and why — structural dependency order always, plus whether any commit could
  affect runtime activation (configuration, a feature flag, environment-conditioned behavior);
  consult `rules/activation-ordering.md` only when that check finds one;
- which tests travel with which commit, and why any commit is intentionally test-free
  (`rules/commit-boundaries.md`);
- draft commit messages, or at minimum the one-sentence implementation decision each commit
  represents;
- the `Refs #N` trailer for any commit implementing the tracked issue (`rules/commit-boundaries.md`).

Get explicit human approval of the complete plan before writing a single commit. Approval at this
gate is what authorizes creating commits.

If the human requests a change — to grouping, ordering, splitting, merging, messages, or anything
else in the plan — revise it and present the complete, resulting plan again before committing.
Partial feedback on part of a plan is not approval of the rest of it.

## Approval validity before Gate 2 and before push

> An approval is scoped to what it actually reviewed. Work, scope, or the proposed action moving on
> after that approval doesn't automatically carry the approval forward with it.

Before Gate 2, and again before requesting push authorization (`rules/issue-closure.md`'s "Push
readiness"), check that the current work, its scope, and the action about to be proposed still
match what the relevant approval actually covered:

- **A material change requires the affected review and approval to be renewed.** A scope change
  since Gate 1, a diff that no longer matches what Gate 2 approved, or an issue body edited since
  its own approval each invalidate the approval that covered the prior state — re-review and
  re-approve the affected surface before relying on it again. This is the same principle
  `review-it/rules/verification.md`'s staleness rule already applies to a `review-it` pass; where the
  change specifically affects a surface `review-it` already reviewed, follow that rule's own
  staleness contract for requesting the scoped re-review rather than duplicating it here.
- **Preserve an approval that demonstrably remains applicable.** This check exists to catch a real
  divergence, not to manufacture one. Ordinary staging and assembling of unchanged, already-approved
  content into its already-approved commits must not automatically invalidate Gate 1 merely because
  `HEAD` moved or the remaining working-tree diff changed shape while commits were being built —
  that's the expected, unavoidable effect of committing, not evidence the approved content changed.
  Confirm the actual content is unchanged before treating an approval as still valid, and confirm it
  again before treating it as stale.
- **Remote commit reachability proves presence, not verification or authorization.** Finding a
  commit already on the remote branch (`rules/issue-closure.md`'s "Push readiness," step 2) shows
  only that it's there — it is not evidence that Gate 1, Gate 2, or push authorization actually
  happened for it. Resuming interrupted work from remote state still requires confirming those
  approvals independently, the same way any other resumed state does.

When this check surfaces a genuine material change with no already-renewed approval covering it,
that's a stop under "When to stop and ask," below — report the divergence and what it means, rather
than silently treating the stale approval as still valid or silently re-deriving a new one.

## When to stop and ask

An agent may investigate and recommend. It must never convert a genuine unresolved human decision
into an implementation fact by silently choosing one. Stop, at either gate or during implementation,
whenever:

- a product or architecture decision is missing;
- implementation evidence contradicts approved architecture or assumptions;
- multiple valid sequencing choices exist and none is already authorized — whether that's
  implementation order or which dependency-ready issue to pick up next (`rules/sequencing.md`);
- the commit decomposition has multiple defensible boundaries with no clearly better answer.

Ordinary engineering choices — ones the approved scope, repository conventions, applicable skills,
and available evidence already support — are part of normal execution, not a reason to stop. Reserve
a stop for a genuinely missing decision, a contradiction, or a choice the evidence can't narrow down;
otherwise, keep executing.

A stop is a report plus a question, not a context-free question or a wall of unexplored options:

1. Investigate enough to understand the decision.
2. Report the relevant evidence.
3. State a recommendation, when the evidence supports one.
4. Ask the human to decide.

For example: implementation reveals that the approved plan assumed three affected surfaces, but the
repository actually has many more — or two commit decompositions are both structurally coherent and
neither is clearly better. Both call for a stop built the way above, not a silent pick.

## Do / Don't

**Do**
- Stop at Gate 1 once implementation, verification, and a clean-or-resolved `review-it` pass are
  complete.
- Invoke `review-it` before reporting at Gate 1, and again, scoped to the affected surface, after
  fixing any finding it raises.
- Derive the commit plan only after Gate 1 is approved.
- Show the complete commit plan before creating any commit, and get explicit approval of it.
- Check, before Gate 2 and before requesting push authorization, that the current work, scope, and
  proposed action still match what was actually approved — renewing the affected approval on a
  material change, and preserving one that demonstrably still applies.
- Investigate a genuine unknown and offer a recommendation before asking the human to decide.

**Don't**
- Collapse Gate 1 and Gate 2 into one approval.
- Treat implementation approval as commit-plan approval.
- Invalidate Gate 1 merely because `HEAD` or the remaining diff changed while assembling unchanged,
  already-approved content into its already-approved commits.
- Treat a commit's mere presence on the remote branch as evidence it was ever reviewed or
  authorized.
- Report a `review-it` finding as resolved without an actual fix and an actual re-review behind
  that claim.
- Report `review-it`'s material limitation as a clean result, or leave it unaddressed when the
  missing evidence was actually obtainable.
- Treat asking the human about a `review-it` finding or limitation as Gate 1's own approval.
- Treat a clean `review-it` result as authorization by itself — it is evidence Gate 1's report
  cites, not a substitute for the human's approval.
- Create a commit before Gate 2 is approved.
- Silently resolve a missing product or architecture decision.
- Ask the human to choose among unexplored options when evidence could narrow the decision first.
