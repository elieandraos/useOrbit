# Activation Ordering

## When this applies

Consult this file only once inspecting the commits being built shows one could affect runtime
activation — a change to configuration, a feature flag, environment-conditioned behavior, or
another activation gate. Structural dependency ordering (`rules/commit-boundaries.md`'s step 6)
is required regardless and never needs this file on its own.

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

## Relationship to isolation verification

This ordering is one of `rules/verification.md`'s named triggers for isolation verification
(`rules/isolation-verification.md`) — `verification.md` owns that decision, not this file.
Reordering commits correctly here does not by itself supply that proof; when the activation step's
own intermediate state also needs proving, both apply together.
