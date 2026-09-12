# Verification

## Principle

> Verify the property that matters at the boundary being crossed. Match the strength of the check to
the property being claimed, and do not spend broader verification merely by habit.

This rule owns verification scope and the points where `implement-it` must stop for a verification decision.
Commit boundaries live in `commit-boundaries.md`; review gates live in `review-gates.md`; specialized
isolation, activation-ordering, reconstruction, and worktree-preservation procedures live in their own
rules.

## Discover the project's verification tools

Do not prescribe a test runner, formatter, linter, or static analyzer. Discover from the consuming
project, its stack skills, configuration, scripts, CI, and established usage:

- targeted test commands and the full regression command;
- formatter/linter/static-analysis commands and any reliable scoped modes;
- any cache, replay, or impact-analysis behavior that can make a green result less than a fresh execution.

The project owns the actual commands. Technology-specific commands belong in project or stack knowledge,
not in this methodology.

## Discover the verification starting state

Where a result could be masked by local state, check the relevant boundary before trusting it. Examples:

generated files, retained build output, dependency drift, caches or impact-analysis state, environment
flags, and other differences from the state a fresh checkout or delivery boundary would use.

This is proportional, not blanket. Do not manufacture a fresh clone, reinstall, cache purge, or other
expensive reset for an ordinary change unless the change is actually exposed to that risk.

### Preserve pre-existing worktree changes

Distinguish this task's changes from pre-existing worktree content before modifying or verifying anything.
Use reliable provenance such as the session's recorded starting state, reflog evidence for committed
history, or an explicit human statement. Ask when unresolved provenance would materially affect safe
continuation. Never invent ownership or discard unrelated work.

## Reproducible install boundary

When a dependency change only installs through a local bypass or compatibility override that the delivery
boundary does not normally use, treat that as evidence of a reproducibility problem. Verify with the
project's actual strict delivery-boundary mechanism before considering the dependency state proven.
A legitimate project-wide override is fine when it is itself the real delivery mechanism.

## Regression baseline for code-quality checks

Pre-existing unrelated formatter, linter, or static-analysis violations are tolerated debt. The relevant
question is whether this change introduces new failures. This does not weaken test pass/fail semantics:
regression tests still need to pass whenever a full regression is chosen or otherwise required.

## Targeted verification before Gate 1

Once the approved implementation is complete, before reporting Gate 1:

1. Run the project's targeted verification for the changed behavior. This is mandatory when the project
   provides a meaningful targeted mode; use the narrowest reliable scope that proves the implementation.
2. Run the applicable formatter/linter/static-analysis checks at their narrowest reliable scope.
3. Inspect the results for failures, warnings, or limitations that could affect the claim being made.
4. Then ask the human whether to run the **full regression suite for this issue** or skip it.

The targeted check is the required implementation proof. The full suite is a separate regression choice.
Do not silently decide that a full suite is required merely because the issue is complete.

### Full-suite choice

The full regression suite is a human-controlled choice at the issue boundary.

After targeted verification, present the choice briefly:

- **Run full suite now.** Recommended when the issue is a standalone Backlog/trunk change, when the
  affected surface is broad or risky, when targeted coverage leaves meaningful uncertainty, or when the
  human wants immediate full-regression evidence.
- **Skip full suite for this issue.** Reasonable for a low-risk issue inside an active phase milestone
  when reliable targeted verification is strong and the human intentionally prefers to defer the broader
  regression check.

A milestone issue may still be run through the full suite; the distinction is the decision point, not a
prohibition. Skipping does not claim that the entire milestone is regression-clean. A later milestone
level check, CI run, or manual-testing pass can provide that broader evidence according to the delivery
workflow.

If the human chooses to run the full suite, use the project's genuine full-regression mode. If the project
uses a test-impact-analysis, cache, or replay feature by default and that would prevent proving a fresh
complete run, use the project's uncached/full mode. In useOrbit, for example, full-suite proof uses
`php artisan test --compact --no-tia` because TIA is enabled by default.

If the human chooses to skip it, report that choice explicitly at Gate 1. Do not describe targeted tests
as full-regression proof.

## Gate 1 evidence

Gate 1 requires:

- approved scope implemented;
- targeted verification complete;
- applicable code-quality checks complete;
- `review-it` clean, or findings fixed and re-reviewed;
- the human's explicit full-suite choice, including the result if run or an explicit skip.

A full-suite result is evidence of regression coverage, not authorization. Gate 1 remains the human's
approval of the implementation report.

## Commit-building verification

After Gate 1 approval, verify each semantic commit at the narrowest reliable scope that proves its
behavior. Do not reflexively run the full suite after every commit when a reliable narrower check exists.
Use broader checks only when the tooling cannot meaningfully scope the proof.

If the human chose a full suite for the issue and the final committed content remains equivalent to what
was tested, that result may satisfy the issue's completed-issue regression evidence. If the final content or
relevant environment changed, the earlier result no longer proves the final state. Ask the human again
whether to run the full suite before completion when that change matters.

If the human originally chose to skip the full suite, the completed issue can be reported with that skip
unless a later decision, project rule, or risk escalation requires broader regression evidence. Never
silently convert a skip into a run.

## Cache, replay, and impact-analysis results are not fresh-execution proof

A successful command can represent:

- **freshly executed** checks against the current state;
- **impact-selected** execution where only a subset ran and the rest was replayed or inferred;
- **cached/replayed** results from earlier work.

At a boundary that specifically claims a fresh full regression, use the project's uncached/full mode when
available and state what actually happened. A cached or impact-selected green tally is not evidence that
the complete suite executed.

## Isolation verification: deliberate escalation

Use isolation verification only when the correctness of an intermediate committed state itself needs proof,
for example during history reconstruction, activation-sensitive ordering, or another case where an
intermediate commit cannot safely be inferred from working-tree verification. It is intentionally expensive
and is not triggered merely because an issue has multiple commits.

See `isolation-verification.md` for the procedure and `worktree-preservation.md` for its stash-preserving
mechanics. `commit-reconstruction.md` can mandate isolation verification for its own trigger.

## Runtime activation and commit ordering

Before finalizing commit order, check whether any commit changes configuration, feature flags, or other
runtime activation gates that affect what the tests exercise. Ordinary dependency ordering remains required.
Only when an activation effect is found should `activation-ordering.md` be loaded for its procedure.

## Do / Don't

**Do**
- Require targeted verification before Gate 1.
- Ask the human after targeted verification whether to run the full suite for the issue or skip it.
- Recommend the full suite by default for standalone Backlog/trunk work and when risk or uncertainty is
  materially higher; allow intentional deferral for suitable milestone issues.
- Discover the project's real tooling and scoping behavior rather than assuming commands.
- Distinguish fresh execution from cache, replay, and impact-analysis selection.
- Verify semantic commits at the narrowest reliable scope.
- Escalate to isolation verification only when an intermediate committed state itself needs proof.
- Preserve unrelated worktree content using reliable provenance.

**Don't**
- Treat targeted tests as full-regression proof.
- Run the full suite automatically merely because an issue reached Gate 1.
- Treat a human's skip as an error or silently override it.
- Treat a cached, replayed, or impact-selected result as proof that a complete suite freshly ran.
- Assume a full-suite result still applies after relevant content or environment changes.
- Manufacture full clones, reinstalls, or cache purges without evidence that the change is exposed to the
  corresponding risk.
- Reach for isolation verification merely because an issue has multiple commits.
