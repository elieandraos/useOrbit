# Isolation Verification

## When this applies

See `rules/verification.md`'s "Isolation verification: deliberate escalation" for the trigger — this
file covers only the technique's mechanics, not the decision to use it. Do not load this file merely
because an issue was split into multiple commits; consult it only once that section's own criteria
actually apply.

## The technique

1. Commit the semantic group.
2. Set aside every remaining change (staged, unstaged, and untracked) using the qualified procedure
   in `rules/worktree-preservation.md`, leaving the working tree at exactly the state of the commits
   made so far. When nothing remains — the commit just made was the last one, and the working tree is
   already clean — that procedure creates no entry; proceed straight to step 3 against the
   already-clean tree.
3. Run the project's full formatting/lint/static checks and its **full** regression suite against
   that isolated committed state — the same full scope as pre-Gate-1 and completed-issue
   verification, not the narrower per-commit scoping used during construction. Judge the lint/
   format/static results against `rules/verification.md`'s regression-baseline model, same as at
   pre-Gate-1.
4. Restore the set-aside entry from step 2, per that same procedure — only when step 2 actually
   created one.
5. Repeat for each subsequent semantic commit: stage the next group, commit, set aside the rest,
   verify in isolation, restore.
6. After the final commit, satisfy the completed-issue checkpoint — ordinarily a fresh full-suite
   run with nothing stashed. When step 3's own full-suite run against the final commit's isolated
   state already covered this exact content, with nothing left to stash afterward, that run can
   itself satisfy `rules/verification.md`'s "Commit-building verification" reuse allowance: the
   content-match condition holds by construction, since the run executed directly against the final
   committed state, not an earlier one. Still confirm the human actually chose the full suite for this
   issue and that no relevant content or environment changed afterward — the same conditions that
   section requires for any other reuse. Isolation verification's per-commit runs happening at all does
   not by itself establish this: an isolated run for an earlier commit, superseded by a later one,
   proves only that earlier state — not the final one now being reported done.
