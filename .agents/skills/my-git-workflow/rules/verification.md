# Verification

## Principle

> Verify the property that matters at the boundary being crossed. The completed working tree, each
> semantic implementation step, the integrity of an intermediate committed state when that itself
> needs proof, and the final assembled history are different things to prove — proving one does not
> substitute for proving another.

This rule owns what verification is required at each lifecycle boundary in `my-git-workflow`, and
the isolation-verification escalation for when an intermediate committed state itself needs proof.
It does not own commit boundaries (`rules/commit-boundaries.md`) or the review gates verification
results get reported into (`rules/review-gates.md`).

The lifecycle this rule verifies:

```
implementation complete → full-suite verification → Gate 1
Gate 1 approved → build semantic commits → narrowest-reliable verification per commit
  (when an intermediate committed state itself needs proof → isolation verification)
all commits assembled → final full-suite verification at the completed-issue boundary
```

Two full-suite runs appear in that lifecycle — one before Gate 1, one at the completed-issue
boundary — and they are not a duplicate of each other, even when both run the same command:

> The pre-Gate-1 full suite proves the completed implementation works as one working tree, before
> any commit history exists to review or split. The completed-issue full suite proves the final
> assembled commit history — however many commits the issue became, whatever order they landed in —
> reconstructs that same correct result once the semantic commits actually exist.

Both stay in the loop. Don't drop one because the other already ran green — they prove different
things, even when the command that produces the result is identical.

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
`rules/release.md` applies to release mechanism, not an assumption this rule makes in advance. What
targeting each tool can reliably support is a property of the project's own tooling, not something
this rule assumes in advance. Git and GitHub remain core substrate for this workflow; test, format,
and static-analysis tooling is stack- and project-specific composition on top of it.

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

## Default commit-building loop: narrowest reliable scope per commit, full suite once at the issue boundary

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

Run the **full regression suite once**, at the completed-issue boundary: after the last commit for
the issue, before reporting the issue done. Report the exact pass/skip/fail results wherever the
project's tooling provides them.

The two verifications prove different things:

- the narrowly-scoped verification proves the semantic decision that commit represents is correct;
- the completed-issue full suite proves that the issue, landed as however many commits it took,
  did not regress anything else in the system.

## Isolation verification: a deliberate escalation, not the default

> Do not use isolation verification merely because an issue was split into multiple commits. Use it
> when the correctness of an intermediate committed state is itself a property that needs to be
> proven — not assumed from how the working tree was tested during implementation.

Reach for this when, for example:

- semantic history is being reconstructed from an already-implemented diff, after the fact;
- commit order is itself load-bearing for correctness — an activation step depends on earlier
  commits already being in place (see "Ordering commits" below);
- an intermediate commit's standalone correctness can't safely be inferred from how the working
  tree was tested during implementation.

The technique:

1. Commit the semantic group.
2. `git stash push -u -m "<description>"` — hides every remaining change (staged, unstaged, and
   untracked), leaving the working tree at exactly the state of the commits made so far.
3. Run the project's full formatting/lint/static checks and its **full** regression suite against
   that isolated committed state — the same full scope as pre-Gate-1 and completed-issue
   verification, not the narrower per-commit scoping used during construction. Judge the lint/
   format/static results against the regression-baseline model above, same as at pre-Gate-1.
4. `git stash pop` — restores the remaining work.
5. Repeat for each subsequent semantic commit: stage the next group, commit, stash the rest, verify
   in isolation, pop.
6. After the final commit, run the completed-issue full-suite check with nothing stashed — the
   same check described above.

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

At a boundary that specifically requires a fresh full-regression proof — the completed-issue boundary
above, or isolation verification's per-commit check — use the project's uncached/full mode when the
tooling offers one, rather than trusting a result that could have been produced by cache or replay at
that specific boundary. This does not mean disabling caching, impact analysis, or targeted
verification generally — those remain the correct, efficient default everywhere else in this rule's
narrowest-reliable-scope model (see "Default commit-building loop" above). It means the two moments
that specifically claim to prove a full regression must actually be shown to be that, not merely
consistent with it.

## Ordering commits to keep intermediate states valid

Watch for any change to configuration, feature flags, environment-conditioned behavior, or another
runtime activation gate — flipping one of these can retroactively change what's under test. A test
conditioned on that gate is silent until the moment it flips, and the instant it does, every test
that was quietly inactive starts running immediately, against whatever code currently exists.

Before committing a step that activates a gate:

1. Identify what behavior and tests become active as a result.
2. Inspect existing tests too, not only ones the current issue adds — an unrelated pre-existing test
   can be gated on the same condition.
3. Verify every dependency those newly active paths require is already present in an earlier
   commit.
4. If it isn't, reorder the commits so it is.

The underlying principle:

> Activation comes after the dependencies required by what it activates. A commit must not read as
> green merely because a gate hid the assertions that would have failed once activated.

For example: commit A introduces supporting code while a feature stays disabled; commit B wires the
behavior to that support, still inactive; commit C flips the feature on. C must land after A and B —
enabling the feature activates tests and code paths that depend on both, and landing C first would
make it green only because the gate was still hiding what it activates.

### Relationship to dependency ordering

`rules/commit-boundaries.md`'s derivation procedure already orders commits by structural dependency
(its step 6); this section layers activation-safety ordering on top of that same sequence. No real
work has yet produced a case where the two orderings actually disagree — this is recorded here as an
observed non-conflict, not a claim that they can never conflict. No precedence rule is defined for
if they do. Should that situation actually arise, it's a genuine unresolved decision for
`rules/review-gates.md`'s "when to stop and ask" to surface, not a default to invent here in advance.

## Do / Don't

**Do**
- Run the full suite before Gate 1, against the complete working tree.
- Verify each semantic commit at the narrowest reliable scope that proves it, across tests and
  code-quality/static checks alike.
- Fall back to a check's broader or project-wide mode when the project's tooling has no reliable
  way to scope it.
- Run the full suite once more at the completed-issue boundary, after all commits are assembled.
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
- Treat the pre-Gate-1 and completed-issue full-suite runs as duplicates of each other.
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
