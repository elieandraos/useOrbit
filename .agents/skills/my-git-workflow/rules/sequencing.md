# Sequencing

## Principle

> This rule owns milestone-scoped progression at both ends of an issue's work: which branch that
> work happens on before it starts, and what becomes ready to work on next after a closure. Closing
> one issue can unblock several others — surface that proactively, rather than waiting to be asked.

## Branch readiness before starting an issue

Before implementing a dependency-ready, approved issue, determine which branch that work happens on.
`my-feature-planning`'s `rules/issue-conventions.md` (§6, "Milestones") classifies a milestone's
purpose — a persistent catch-all/backlog-style milestone versus what that section calls a scoped
delivery milestone, and what this file calls a delivery/phase milestone throughout; this rule
consumes that classification and does not redefine it. It does not, however, classify a hotfix
delivery path — mapping a hotfix, or any issue with no delivery/phase milestone, to branch/delivery
behavior is this rule's own job: both take the Backlog/hotfix path below, alongside a persistent
catch-all milestone, while a delivery/phase milestone takes the milestone-branch path.

**Backlog or hotfix issue (no delivery/phase milestone).** This work happens directly on the
repository's trunk branch — never a newly created feature branch, and never the milestone
shared-branch flow below. There is no branch *decision* to make: don't create, propose, or ask about
a branch for this path.

Confirm the trunk branch is actually what's checked out before implementation starts. Which branch a
given repository treats as trunk (commonly `main`, sometimes something else) is discovered from the
repository, not assumed to be a fixed name. If the current checkout is something other than that
branch, surface the mismatch to the human rather than proceeding — do not silently switch branches,
and do not silently implement Backlog/hotfix work on whatever happens to be checked out.

This path also does not end in a PR (see `rules/release.md`'s "Where this phase starts" for what that
means for the release phase).

**Delivery/phase milestone issue.** All issues in that milestone share one working branch —
implementation does not get a fresh branch per issue.

1. Inspect the currently checked-out branch.
2. If it already is that milestone's working branch, proceed directly to implementation.
3. If not, recommend a branch name derived from the milestone's actual nature and scope, and ask the
   human before creating or switching to it. Do not silently create or check out a branch.
4. Only once the correct branch is confirmed active does implementation begin — the rest of the
   working lifecycle (`rules/review-gates.md` onward) is unchanged.

Do not turn observed branch-name patterns into a rigid taxonomy. A name derived from what the
milestone actually is — its area, or the kind of change it bundles — is the goal; illustrative shapes
seen in practice include an area-scoped prefix for a cross-cutting rework (e.g. `core/...`) or a
capability-scoped prefix for a new feature (e.g. `feat/...`). These are examples of the reasoning, not
a fixed prefix list to match against. When the milestone's nature doesn't suggest an obvious name,
ask rather than guess.

Once every issue in the milestone is closed on this shared branch, the recompute below reports an
empty ready set — see "When the ready set is empty" for what happens next.

## Recompute the dependency-ready set

This phase starts only after a validated closure (`rules/issue-closure.md`) — never before.

1. List the open issues remaining in the current milestone:

   ```
   gh issue list --milestone "<name>" --state open
   ```

2. For each issue, read its actual dependency information. Never assume a particular structured
   field or textual syntax — dependencies are represented however the project's established issue
   convention records them. `my-feature-planning`'s issue/dependency convention owns that
   representation; this rule only reads and evaluates the resulting graph, and does not redesign or
   invent one.
3. An issue is dependency-ready when every issue it depends on is closed.
4. An issue with no stated dependency is a root issue, and is always ready.
5. Everything else is blocked — record what each blocked issue is still waiting on.

## Report the graph, recommend, let the human choose

Summarize compactly, in categories — never a flat ready list:

- which issues just became newly ready because of this closure;
- which were already ready;
- which are still blocked, and on what.

For example: issue {A} closes, issues {B} and {C} become ready, and issue {D} remains blocked on
{E}.

Recommend one ready issue, with a concise rationale, when the evidence gives a reasonable basis —
e.g. it unblocks the most follow-on work, or it continues the same implementation layer/context the
recent work was in. When several ready issues are genuinely comparable and the choice is a real
judgment call, present them as options instead of silently picking one — this is a sequencing
choice, and `rules/review-gates.md`'s "multiple valid sequencing choices" stop applies here
directly.

Recommendation is not authorization: investigate enough to recommend when possible, but never
convert a genuine sequencing judgment into an automatic choice. The human always makes the final
sequencing decision.

## When the ready set is empty

For a Backlog/hotfix issue, an empty ready set means nothing beyond itself — there is no milestone
graph to exhaust, and no further handoff.

For a delivery/phase milestone, recomputing after a closure can find zero open issues remaining —
nothing ready, nothing blocked. That is not this rule's job to act on further: report the empty set
and hand off to `rules/milestone-completion.md`'s "Milestone PR readiness" gate rather than
recommending a next issue that doesn't exist. This rule does not check that gate's conditions itself
(final manual testing, whether it found anything) — it only recognizes the empty-set state and points
to where that question actually gets answered.

## Do not chain into the next issue

Recalculating and reporting the ready set ends this workflow pass. Do not start implementing the
recommended or chosen next issue in the same pass — even when the human's answer is immediate and
unambiguous. Starting the next issue is a new pass through `my-git-workflow`, with its own explicit
authorization.

## Do / Don't

**Do**
- Determine Backlog/hotfix vs. delivery/phase milestone before deciding whether a branch decision
  applies at all.
- Confirm the trunk branch is checked out before starting Backlog/hotfix work, and surface a mismatch
  instead of proceeding on it.
- Inspect the current branch before starting milestone work, and ask before creating or switching to
  a milestone branch.
- Recompute readiness from current issue state after every validated closure.
- Explain newly ready, already ready, and blocked work — not just a flat ready list.
- Recommend when the evidence supports one, with a concise rationale.
- Let the human make the sequencing decision.
- Hand off to `rules/milestone-completion.md`'s PR-readiness gate when the ready set is empty, rather
  than treating "no next issue" as nothing to report.

**Don't**
- Propose or create a branch for a Backlog/hotfix issue.
- Silently proceed on a non-trunk branch for Backlog/hotfix work instead of surfacing the mismatch.
- Generalize observed branch-name patterns into a rigid, enforced taxonomy.
- Assume a dependency syntax the project doesn't actually use.
- Silently pick between genuinely comparable ready issues.
- Treat a recommendation, or an immediate human answer, as authorization to start implementing.
- Chain straight into the next issue within the same pass.
- Check milestone PR-readiness or closure conditions from this rule — that's
  `rules/milestone-completion.md`'s job.
