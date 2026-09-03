# Commit Boundaries

> Issues describe outcomes. Commits describe coherent, verified implementation steps.

An issue answers "what should exist when we're done, and why." A commit answers "what is the
smallest coherent, independently-true step that got us there." Nothing forces those two counts to
match.

A semantic commit represents one coherent implementation decision. Read in order, a sequence of
semantic commits should make the implementation path understandable from the history alone: a
developer — or an agent — should be able to reconstruct what decisions were made, and how the
implementation evolved from the approved outcome, using nothing but the commit log and the diffs it
contains.

The commit boundary is what makes that possible: it decides which decision gets recorded as one
historical unit. Within that unit, the commit message explains the decision and the guarantees it
establishes, and the diff shows how the decision was actually implemented. Draw the boundary wrong —
too broad, too narrow, split along the wrong axis — and neither the message nor the diff can recover
the reasoning; the history stops teaching anything and becomes a raw file-change log.

## Do not choose commit count in advance

Neither "one commit per issue" nor "many small commits" is the default. Commit count is not
determined by issue size, file count, how many directories or file types are touched, or how a
previous change happened to split — it is discovered from the actual, finished, reviewed diff, every
time (see "How to derive commit boundaries" below).

A large diff can be one commit, if it's one decision applied consistently everywhere it reaches. A
small diff can be several commits, if it actually contains several decisions. A twenty-file change
that removes one capability everywhere it appears is one decision, and belongs in one commit; a
three-file change that happens to touch a model, a route, and a template can still be three separate
decisions, if each one is independently true without the others.

File count in particular is not commit count: splitting a single decision by directory or file
type — data-layer changes in one commit, request-handling in another, tests in a third — produces
commits that each leave the implementation in a half-migrated state. That's worse history, not
better, even though it looks more organized on the surface.

## What makes a commit coherent

A semantic commit is one implementation decision you could summarize in a single sentence of *why*
— not a bucket defined by folder, file type, or how the diff happened to be typed out over the
course of implementation.

Apply this test to every candidate commit: if it existed alone, on top of everything that came
before it, does it represent one real, describable decision — and does the resulting state hold
together on its own terms?

"Hold together on its own terms" is a narrower bar than "safe to deploy" or "user-visible":

> Every semantic commit leaves a coherent, structurally valid state that does not depend on a later
> commit to become structurally valid.

- **Coherent does not mean deployable, and it does not mean user-visible.** A commit can be coherent
  and still be inert — dead today, live once something later activates it — as long as it's
  structurally complete and correct on its own terms. A data-model change with no consumer yet is
  coherent: it does nothing, and does it correctly. A code path gated behind a flag that's still off
  is coherent: it's dead code today, live once the flag flips.
- **A commit that depends on a definition introduced only by a later commit is not coherent**, no
  matter how small it is — a reference to a class, function, route, or column that doesn't exist yet
  breaks structural self-sufficiency. That's the actual line: structural self-sufficiency, not
  user-visible behavior.

For example, a feature built in layers might land as three coherent commits: the data layer, then
the code path that reads it while still gated off, then the commit that flips the gate on. The first
two are each structurally complete and inert; the third activates both at once.

## How to derive commit boundaries

Never decide commit boundaries before the implementation exists and has been reviewed. Planning
boundaries speculatively while writing code — or copying how a previous, superficially similar change
happened to split — produces exactly the file-type/directory split this rule exists to avoid.

The procedure:

1. Finish the implementation.
2. Pass implementation review (Gate 1 — `rules/review-gates.md`).
3. Inspect the actual diff (`git status`, `git diff --stat`, then per-file diffs).
4. Identify the implementation decisions the diff actually contains.
5. Group changes by decision, not by file location or type.
6. Order the groups by dependency. `rules/verification.md`'s activation-ordering rule can require
   reordering on top of this — see that rule's "Relationship to dependency ordering" for how the two
   interact.
7. Verify each intermediate state would be coherent, per "What makes a commit coherent" above.
8. Propose the commit plan for human review (Gate 2 — `rules/review-gates.md`) before writing a
   single commit.

This is also why the commit plan is its own review gate, separate from implementation review — see
`rules/review-gates.md`.

## Commit messages and issue references

> Commit messages explain the decision, not the diff.

Prefer a concise title and, when useful, a short body describing the outcome and important
guarantees or boundaries. Don't turn the message into a file-by-file implementation summary — the
diff already shows what changed; the message's job is to preserve the reasoning behind the change —
the one-sentence "why" — in the permanent record. History should read as a sequence of engineering
decisions, not a transcript of the development conversation.

```
Add rate limiting to the password-reset endpoint

Limits reset requests to 5 per hour per account. Requests over the
limit return 429 without revealing whether the account exists.
```

Every commit that implements a tracked, approved issue also carries a `Refs #N` trailer — never
`Closes`, `Fixes`, or `Resolves`. Issue closure in this workflow is an explicit, human-approved step
that happens after commits exist and verification passes (`rules/issue-closure.md`); an auto-closing
keyword would let `git push` close the issue on its own, silently skipping that approval. `Refs #N`
links the commit to the issue without closing anything:

```
Refs #{xxx}
```

- The trailer is its own line, after the body — not folded into the title or body, which stay
  focused on the decision.
- For a multi-commit issue, every commit carries the same `Refs #N`. The issue doesn't change
  mid-split; only the commit boundaries do.
- Not every commit made while this skill is in use implements a tracked issue. When a commit isn't
  implementing one, omit the reference rather than inventing one.
- This is a rule about this skill's own commits for the dependency-ready, approved issue currently
  being implemented, not a general policy for every commit in the repository.

**Do**
- Add `Refs #N` as its own trailer, on every commit that implements the tracked issue.
- Reuse the same reference across every commit in a multi-commit issue.

**Don't**
- Use `Closes`, `Fixes`, or `Resolves`.
- Invent a reference for a commit that doesn't implement a tracked issue.
- Fold the reference into the semantic title or body.

## Tests travel with the decision

When a commit introduces behavior that's independently observable, its proving tests land in the
same commit — never split "the code" into one commit and "the tests that prove it" into another.

Not every commit needs new tests. A commit that's intentionally inert (see "What makes a commit
coherent" above) can legitimately ship with none, as long as its proof is negative: the full suite
stays exactly as green as it was before the commit landed (`rules/verification.md`). Once a later
commit makes that behavior observable, that activating commit's own tests retroactively prove the
earlier, inert-looking commits correct too. Conversely, a change that's fully testable in isolation
the moment it lands — a validation rule with no surrounding system needed to exercise it, say —
carries its test immediately, in the same commit.

## Review corrections fold into their semantic commit

A correction discovered during review does not automatically become its own commit. Where it lands
depends on whether anything has been committed yet.

**Nothing committed yet.** The correction is simply part of whichever semantic commit it belongs to.
No separate "fix review comments" commit exists, because none of the work had been committed when
the correction was made.

**Something already committed, correction needed before push.** Don't bolt a fixup commit on top.
Rebuild history so the correction lands inside the commit it actually belongs to:

1. `git reset --soft HEAD~1` — undoes the commit, keeps every change staged.
2. Selectively `git add` and `git commit` per semantic group.
3. Verify each resulting commit in isolation before moving to the next (`rules/verification.md`).

The result reads as if it had been built that way from the start — there's no trace in the history
that it was originally committed differently.

Never preserve every conversational step as its own commit "for the record." The history should read
as a sequence of decisions, not a transcript.

## Do / Don't summary

**Do**
- Split commits by implementation decision.
- Order commits by dependency.
- Keep proving tests with the change they prove.
- Make each intermediate commit structurally coherent on its own.
- Inspect the finished, reviewed diff before proposing boundaries.

**Don't**
- Split by directory or file type.
- Force one commit per issue, or assume many commits by default.
- Create extra commits merely because a diff is large.
- Preserve review chatter as separate fixup commits.
- Reference a definition that only exists in a later commit.
