# Verification

## Principle

> Verify the property that matters at the boundary being crossed. The completed working tree, each
> semantic implementation step, the integrity of an intermediate committed state when that itself
> needs proof, and the final assembled history are different things to prove — proving one does not
> substitute for proving another.

This rule owns what verification is required at each lifecycle boundary in `implement-it`, including
the decision for when an intermediate committed state itself needs isolation-verification proof —
`rules/isolation-verification.md` owns that technique's mechanics, and
`rules/worktree-preservation.md` owns the shared stash-preservation procedure both isolation
verification and `rules/commit-reconstruction.md` use. It also flags activation risk while building
commits — `rules/activation-ordering.md` owns that procedure's mechanics and its relationship to
dependency ordering. It does not own commit boundaries (`rules/commit-boundaries.md`) or the review
gates verification results get reported into (`rules/review-gates.md`).

The lifecycle this rule verifies:

```
implementation complete → full-suite verification → Gate 1
Gate 1 approved → build semantic commits → narrowest-reliable verification per commit
  (when an intermediate committed state itself needs proof → isolation verification)
all commits assembled → completed-issue verification (a fresh full-suite run, or an earlier
  full-suite run's own result — the pre-Gate-1 run, or one already executed directly against the
  final committed state — reused once its continued applicability is established; see
  "Completed-issue verification: run or reuse" below)
```

Two full-suite verification checkpoints appear in that lifecycle — one before Gate 1, one at the
completed-issue boundary — and neither is optional, even when the completed-issue checkpoint is
satisfied by reusing an earlier result rather than running the suite again:

> The pre-Gate-1 full suite proves the completed implementation works as one working tree, before
> any commit history exists to review or split. The completed-issue checkpoint proves the final
> assembled commit history — however many commits the issue became, whatever order they landed
> in — reconstructs that same correct result once the semantic commits actually exist. A fresh run
> proves this directly; a reused result proves it only once "Completed-issue verification: run or
> reuse" below establishes that the run being reused still applies to the final committed content.

Both checkpoints stay in the loop. Don't drop the completed-issue checkpoint because an earlier run
already went green — reuse is a way to satisfy it with evidence that still applies, never a reason
to skip it.

## Discover the project's verification tools

This rule does not prescribe a test runner, formatter, linter, or static analyzer. Determine what
the consuming project actually uses — its:

- test runner, and any mechanism it offers for running a targeted subset of tests (by path, tag,
  or similar);
- formatter, linter, and static-analysis tools, and whether each can be scoped to changed/dirty
  files, an affected package, or a module, rather than always running project-wide;
- full regression command;
- full, project-wide formatting/lint/static-analysis commands, for whichever of those tools have
  no reliable scoped mode.

Discover these from the repository's own instructions, applicable project/stack skills,
configuration, scripts, CI definitions, or established usage — the same discovery discipline
`ship-it/rules/release.md` applies to release mechanism, not an assumption this rule makes in
advance. What targeting each tool can reliably support is a property of the project's own tooling,
not something this rule assumes in advance. Git and GitHub remain core substrate for this workflow;
test, format, and static-analysis tooling is stack- and project-specific composition on top of it.

## Discover the verification starting state, not just the tools

Knowing what commands exist and how they scope (above) doesn't answer a separate, equally material
question: does running them right now, on this checkout, actually start from the state the delivery
boundary (CI, a fresh clone) would start from? Where it matters, check whether a local result could be
masked by a difference between the current working tree and that boundary's actual starting state —
for example:

- generated files that are gitignored and present only because of earlier local activity, but
  wouldn't exist on a fresh checkout;
- build output retained locally from a previous run, rather than produced by the verification being
  run right now;
- installed dependency state (`node_modules`, vendor directories, a lockfile-resolved tree) that has
  drifted from what a strict, reproducible install would actually produce;
- caches or impact-analysis state (a test runner's cache, a linter's cache, test-impact-analysis
  graphs) that can make a check pass by skipping or replaying work rather than re-executing it — see
  "Cache, replay, and impact-analysis results are not execution proof" below;
- environment or configuration state — an env value, a locally-set flag — that a fresh checkout would
  not have.

This is a judgment call proportional to actual risk, not a blanket requirement. Require
clean-equivalent verification — reproducing the relevant piece of a fresh checkout's state before
trusting a result — only where one of these differences could plausibly hide a real failure: a change
that introduces or depends on a generated artifact, a dependency-manifest change, a check whose
scoping mode might replay stale results, or similar. An ordinary change with no such exposure does not
need a fresh clone, a full reinstall, or a cache purge before it can be verified — manufacturing that
overhead for every change is exactly the kind of reflexive broadening this rule warns against
elsewhere.

### Preserve pre-existing worktree changes

A starting or resumed session can encounter a worktree that already carries content this session
didn't create — a prior session's unfinished work, or something the human left in progress. Before
making or verifying any change, distinguish that pre-existing content from this task's own changes,
and preserve what isn't this task's to touch.

Recency, being uncommitted, or resembling the approved issue's scope does not by itself prove a
change belongs to this session — appearance is not provenance. Use whatever reliable provenance is
actually available to judge origin instead:

- `git reflog` establishes commit/ref history — what `HEAD` pointed to, and what commands moved it —
  but it does not prove who authored an uncommitted, never-committed edit; don't stretch it past what
  it actually shows.
- the session's own recorded start point (the working tree or commit state noted when this task
  began);
- an explicit statement from the human about what's already there and why.

Ask the human when unresolved provenance would materially affect whether it's safe to continue —
not on every harmless ambiguity, and not by discarding the work merely to obtain a clean checkout.
Never invent ownership from appearance, and never modify or discard content whose origin can't be
established this way.

## Reproducible install boundary

A dependency install that only succeeds locally because of a bypass or compatibility override the
delivery/CI path does not normally use — a lenient-resolution flag, a forced install, an equivalent
mechanism on any package manager — is evidence, not a false alarm: the moment an override becomes
*necessary* rather than merely convenient for a fresh install to succeed, the project's actual strict
install mechanism at the delivery boundary may fail on that same tree even though the lenient local
install just "worked."

- The moment such an override becomes necessary for a dependency-manifest change to install cleanly,
  verify using the project's actual strict, reproducible install mechanism at the delivery boundary —
  whatever that project's own tooling calls it — before treating that dependency state as proven. Don't
  leave this to individual habit, or defer it to whatever check happens to run next.
- This is about install *reproducibility*, not banning a legitimate override. A project can have a
  real, documented reason for a lenient install flag to be its own normal delivery-boundary mechanism —
  if that's genuinely what the delivery boundary runs, using it is correct, not a violation of this
  rule. What this flags is a *local-only* bypass diverging from what the delivery boundary actually
  runs, never the existence of an override in general.
- This generalizes the underlying risk without hard-coding one package manager or one incident: any
  ecosystem's dependency resolver can have a strict mode (used at the delivery boundary) and a lenient
  mode (convenient locally) that silently diverge on the same manifest — the check applies regardless
  of which tool exhibits it.

## Regression baseline for lint/format/static checks

> This rule does not require a project's full-project lint/format/static-analysis output to already
> be at zero violations before this workflow can run. Pre-existing, unrelated violations are
> tolerated debt, not a blocker — the standard is that this work introduces no new failures beyond
> whatever baseline already existed, not that the whole project becomes clean because this issue
> touched it.

This applies specifically to lint/format/static-analysis, wherever this rule runs one of those checks
at full-project scope (pre-Gate-1, and inside isolation verification). It does not change how the
regression **test** suite is treated, at any boundary: a test either passes or fails regardless of
the codebase's history, so the full-suite pass/fail signal stays exactly as strict as described
elsewhere in this rule.

How a project actually distinguishes "pre-existing" from "newly introduced" for its own lint/format/
static tooling — comparing against a baseline snapshot, filtering to the files this issue touched, or
whatever mechanism the project's tooling supports — is discovered the same way the tools themselves
are (see "Discover the project's verification tools" above), never assumed in advance. When a
full-project check comes back red, the first question is whether this issue's work caused it, not
whether the project was already carrying that debt.

## Pre-Gate-1 verification

Once the approved issue's implementation is complete, and before reporting at Gate 1
(`rules/review-gates.md`):

1. Run the project's complete formatting/lint/static checks, at the project's full-project scope
   — not the narrower, per-change scoping used later while building commits. Judge the result
   against the regression-baseline model above: report new failures this work introduced, not every
   pre-existing violation the project already carried.
2. Run the full regression suite.
3. Report the exact results at Gate 1.

This validates the complete working tree as one coherent whole, at the project's full verification
scope, before any commit history has been written to split it. It is the last point at which "does
the implementation work" is answered without any interference from how it will later be divided
into commits.

## Default commit-building loop: narrowest reliable scope per commit, completed-issue checkpoint at the issue boundary

> During commit construction, use the narrowest reliable verification scope that proves the
> semantic commit. Do not broaden verification merely by habit, and do not narrow it past what the
> project's tooling can meaningfully and reliably validate.

This targeting principle applies to every kind of verification available for the change, not only
tests. While building each semantic commit — whether the issue lands as one commit or several
(`rules/commit-boundaries.md`) — verify it at the narrowest scope that reliably proves that
decision, which may include:

- tests scoped to the commit's change, where the project's test runner supports it;
- formatting checks scoped to the changed/dirty files, where the formatter supports it;
- linting scoped to the affected files, package, or module, where the linter supports it;
- static analysis scoped to the affected surface, where the tool supports it.

Where the project's tooling has no meaningful, reliable way to scope one of these checks, run that
check's broader or project-wide mode instead for that commit. Falling back to the broader check in
that case is correct, not a compromise — the target is the narrowest scope that is actually
reliable, not the narrowest scope regardless of whether the tooling can back it.

Do not reflexively run the full regression suite, or any other check's project-wide mode, after
every commit merely by habit. It's slow, and for a commit that's deliberately inert — structurally
complete but not yet activated, per `rules/commit-boundaries.md` — a broader run proves nothing the
narrower, reliable one didn't already cover.

Satisfy the completed-issue checkpoint once, after the last commit for the issue, before reporting
the issue done — either by running the full regression suite again or by reusing an earlier
qualifying full-suite result (the pre-Gate-1 run, or a run already executed directly against the
final committed state) under the conditions in "Completed-issue verification: run or reuse" below,
which owns those conditions; this summary doesn't restate them. Report the exact result either way:
pass/skip/fail counts for a fresh run, or the reused result and why it still applies.

The two checks prove different things:

- the narrowly-scoped, per-commit verification proves the semantic decision that commit represents
  is correct;
- the completed-issue checkpoint proves that the issue, landed as however many commits it took, did
  not regress anything else in the system — whether that proof comes from a fresh run or an
  established reuse of an earlier full-suite run.

## Completed-issue verification: run or reuse

> The completed-issue checkpoint's job is unchanged: prove the final assembled commit history
> reconstructs the same correct result already proved for its content. What can change is how that
> proof is produced — a fresh full-suite run against the final assembled commits, or an earlier
> full-suite run's own result, reused once its continued applicability to the final committed
> content is actually established. Reuse is evidence-based, never a default, and never a shortcut
> past this checkpoint.

**Run the full suite again** — the default, and the only correct choice whenever applicability
can't be established below.

**Reuse an earlier full-suite result**, only when every one of the following holds. Two different
earlier runs can supply that result, and condition 2 applies differently to each:

- **The pre-Gate-1 run** — executed against the working tree before any commit history existed.
  Reusing it requires actively establishing that the final assembled commits reproduce that same
  content (condition 2 below).
- **A full-suite run already executed directly against the final committed state itself** — for
  example, isolation verification's last per-commit run (below), when nothing remained stashed
  afterward, so that per-commit state actually is the final committed state, not merely an
  intermediate one. Reusing this satisfies condition 2 by construction: the run already covered
  exactly this content, with nothing to establish after the fact.

1. **Identifiable evidence of an actual successful, complete run exists.** The earlier run's own
   result is identifiable — its command, and the exact state it ran against — not merely remembered
   or assumed from an earlier "it passed" summary. This applies to either source above equally.
2. **The final committed content matches the tested content.** For a run already executed directly
   against the final committed state itself, this holds by construction — skip to condition 3. For
   the pre-Gate-1 run: a clean worktree, an unchanged `HEAD`, or a successful commit command are not,
   on their own, evidence of this — none of them proves the tree the suite actually ran against is
   the same tree the assembled commits now represent. Establish the match directly: for example,
   confirm the commits' combined diff against the pre-Gate-1 starting point is exactly the content
   the pre-Gate-1 suite tested, with nothing added, dropped, or altered while building commits.
3. **Relevant test inputs and environment remain equivalent.** Dependencies, configuration,
   generated inputs, and any other state the project's checks actually depend on haven't changed
   between the reused run and now. Where the project's checks consume commit metadata (an
   environment-conditioned test, a hook that inspects the commit under verification), that
   metadata's equivalence matters too — this checkpoint isn't only about file content when the
   project's own tooling reads more than that.
4. **No unresolved limitation undermines that equivalence.** A known gap in what the reused run
   checked, an unreachable diagnostic, or any other open question that could plausibly affect the
   final state disqualifies reuse for that state.

An intermediate isolated run that does **not** correspond to the actual final committed state — one
taken before a later commit added more content, for instance — cannot satisfy this checkpoint under
either source above: it proved a different, earlier state, not the one now being reported done. Only
a run genuinely executed against the final committed state, or independently proven equivalent to it
per the pre-Gate-1 path, qualifies.

Use practical evidence actually available in the project, proportionate to the change — the relevant
diff, the dependency manifest and lockfile state, configuration files touched while building
commits, and whatever else the project's own checks actually depend on. This is not a mandatory
snapshot system, and it does not require an exhaustive environment inventory for every issue — a
small, low-risk issue with no dependency or configuration change clears this bar with
correspondingly little evidence to check; a larger or riskier one needs correspondingly more.

**If relevant content or inputs changed since the run being reused, or applicability can't actually
be established, run the full suite again.** This includes a correction made after that run —
whether it originates from a `review-it` finding, a Gate 1/Gate 2 revision, or anything else: the
corrected state must itself satisfy this checkpoint's own verification requirements before the issue
is done. A narrowly-scoped, per-commit check is never a substitute where a full suite is what this
checkpoint requires — commit-construction-time targeted verification and this checkpoint answer
different questions (see "Default commit-building loop" above).

**Report reuse honestly and precisely.** State plainly that the completed-issue checkpoint was
satisfied by reuse, name which execution actually satisfies it — the pre-Gate-1 run, or a specific
full-suite run already executed against the final committed state, never mislabeled as the
pre-Gate-1 run when it wasn't — and the evidence that established its continued applicability. Never
report a reused result as if it were newly executed — the same discipline "Cache, replay, and
impact-analysis results are not execution proof" below already applies to a single run's own
execution, extended here to whether an entire checkpoint's proof came from this issue's own fresh
execution or from an earlier one.

This checkpoint's applicability judgment is separate from a single command's cache/replay behavior:
a cached test-runner result, or a result selected through impact analysis, is not by itself evidence
that a complete suite ran even once, and cannot alone establish condition 1 above for either reuse
source — see "Cache, replay, and impact-analysis results are not execution proof" below.

Final-state equivalence, however established, proves the completed-issue checkpoint alone. It does
not prove any other intermediate committed state along the way — isolation verification (below)
remains the only way to prove an intermediate commit when that commit's own standalone correctness
is a property that needs proving, distinct from whether its state happens to also satisfy this
checkpoint.

## Isolation verification: a deliberate escalation, not the default

> Do not use isolation verification merely because an issue was split into multiple commits. Use it
> when the correctness of an intermediate committed state is itself a property that needs to be
> proven — not assumed from how the working tree was tested during implementation.

Reach for this when, for example:

- semantic history is being reconstructed from an already-implemented diff, after the fact;
- commit order is itself load-bearing for correctness — an activation step depends on earlier
  commits already being in place (see `rules/activation-ordering.md`);
- an intermediate commit's standalone correctness can't safely be inferred from how the working
  tree was tested during implementation.

See `rules/isolation-verification.md` for the technique itself, loaded only once one of the criteria
above actually applies — not merely because an issue happened to split into multiple commits.

This is intentionally expensive — a full suite run per commit — which is exactly why it stays an
escalation, not the default for every multi-commit issue. An issue with no ordering or
reconstruction risk verifies each commit at its own narrowest reliable scope, same as the default
loop, and needs isolation verification for none of them.

## Cache, replay, and impact-analysis results are not execution proof

A successful command is not, by itself, evidence that its underlying checks freshly executed. Caching,
result replay, and test-impact analysis (running only a computed subset while still reporting a full
tally) can each produce a green, complete-looking result without re-executing everything it appears to
cover.

Where the project's tooling can distinguish these, state which one actually happened rather than
reporting only pass/fail:

- **freshly executed** — every check actually ran against the current code, uncached;
- **selected through impact analysis** — the tool determined a subset needed re-running and reported
  the full tally alongside it, replaying the rest;
- **cached or replayed** — the result came from a prior run, not from re-execution now.

At a boundary that specifically requires a fresh full-regression proof — the pre-Gate-1 run, a fresh
completed-issue run, or isolation verification's per-commit check — use the project's uncached/full
mode when the tooling offers one, rather than trusting a result that could have been produced by
cache or replay at that specific boundary. This does not mean disabling caching, impact analysis, or
targeted verification generally — those remain the correct, efficient default everywhere else in
this rule's narrowest-reliable-scope model (see "Default commit-building loop" above). It means a
moment that specifically claims to prove a full regression must actually be shown to be that, not
merely consistent with it.

This applies to reuse exactly as it applies to a fresh run: a cache hit, a replayed result, or a
subset selected through impact analysis must never manufacture evidence that a complete suite ran.
It cannot, by itself, satisfy "Completed-issue verification: run or reuse" above's first
condition — identifiable evidence of an actual successful, complete run — and it cannot substitute
for a fresh run when reuse isn't otherwise established.

## Ordering commits to keep intermediate states valid

Before treating a commit's dependency order as final, check it against runtime activation: does it
change configuration, a feature flag, environment-conditioned behavior, or another activation
gate? Never assume no effect without that check — flipping one of these can retroactively change
what's under test. Dependency ordering and intermediate-state coherence remain required regardless
of the answer. Only when the check finds an activation effect, see `rules/activation-ordering.md`
for the procedure — this rule does not restate it.

## Do / Don't

**Do**
- Run the full suite before Gate 1, against the complete working tree.
- Verify each semantic commit at the narrowest reliable scope that proves it, across tests and
  code-quality/static checks alike.
- Fall back to a check's broader or project-wide mode when the project's tooling has no reliable
  way to scope it.
- Satisfy the completed-issue checkpoint by a fresh run by default, reusing an earlier full-suite
  result (the pre-Gate-1 run, or one already executed against the final committed state) only once
  its continued applicability to the final committed content is actually established.
- Report a reused completed-issue result honestly — identify the earlier run and why it still
  applies, never as if it were newly executed.
- Preserve pre-existing worktree changes using reliable provenance, and ask the human when
  unresolved provenance would materially affect safe continuation.
- Use isolation verification when an intermediate committed state itself needs proof.
- Inspect activation/configuration changes for tests and dependencies they newly make active.
- Discover the project's test, format, lint, and static-analysis tooling — and what each can and
  can't scope — from the repository itself.
- Judge a full-project lint/format/static-analysis result against the regression-baseline model:
  new failures block, pre-existing ones don't.
- Check whether local state (generated files, retained build output, installed dependencies, caches/
  impact-analysis graphs, environment) could mask a fresh-checkout-only failure, when a change is
  actually exposed to that risk.
- Escalate to the project's actual strict, reproducible install mechanism the moment a dependency
  install needs a bypass or compatibility override to succeed locally.
- Distinguish freshly executed results from cached, replayed, or impact-analysis-selected ones,
  especially at a boundary that specifically requires a fresh full-regression proof.

**Don't**
- Treat the pre-Gate-1 run and the completed-issue checkpoint as duplicates of each other, or as
  interchangeable merely because reuse is available.
- Infer reuse eligibility from a clean worktree, an unchanged `HEAD`, or a successful commit command
  alone.
- Treat a cached, replayed, or impact-analysis-selected result as evidence a complete suite ran, for
  reuse eligibility or otherwise.
- Assume uncommitted worktree content belongs to this session merely because it's recent,
  uncommitted, or resembles the approved scope.
- Run the full suite, or any other check's project-wide mode, after every commit merely by habit
  when a reliable scoped mode exists.
- Narrow verification past what the project's tooling can meaningfully and reliably validate.
- Reach for isolation verification merely because an issue has multiple commits.
- Assume a project's test/format/lint/static-analysis commands, or their scoping ability, without
  discovering them from the repository.
- Land an activation commit before the code and tests it activates can stand behind it.
- Invent a precedence rule for dependency ordering vs. activation ordering — none has been needed yet.
- Require a project's full historical lint/format/static-analysis output to be violation-free before
  this workflow can run.
- Require a fresh clone, full reinstall, or cache purge for every ordinary change regardless of
  whether it's actually exposed to a starting-state difference.
- Assume a locally lenient install succeeding proves the delivery boundary's strict install will also
  succeed.
- Report a cached, replayed, or impact-analysis-selected result as equivalent to a freshly executed
  full regression without saying so.
