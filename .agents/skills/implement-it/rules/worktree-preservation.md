# Worktree Preservation

Any procedure in this skill that needs to temporarily clear the working tree or index around content
that is not part of what it's isolating or reconstructing — `rules/isolation-verification.md`'s
technique, or whatever `rules/commit-reconstruction.md`'s reconstruction of an unpublished commit
protects this way — uses this same qualified procedure. Never substitute an unqualified `git stash
push` / `git stash pop` pair for it: an unqualified pop restores whatever is topmost on the stash,
which is not necessarily the entry this step created, and can silently apply or drop an unrelated,
older stash instead. That reconstruction procedure protects most unrelated content this way, but
excludes a correction-touched path that shares unrelated content with the correction itself — a
stash entry's own restoration depends on the commit it was taken against still matching history,
which reconstruction changes by design; that specific case is handled by a direct merge against the
reconstructed content instead (`rules/commit-reconstruction.md`'s steps 7 and 12).

1. **Check whether there's anything to set aside.** `git status --porcelain`. If the working tree and
   index are already clean, skip stashing entirely — do not run `git stash push` against a clean
   tree, and do not run any restoration step afterward.
2. **Otherwise, create the entry with an identifiable message**: `git stash push -u -m
   "<description>"`. Confirm a new entry was actually created — compare `git stash list` before and
   after, or read the command's own confirmation. A push against a tree with nothing to save reports
   "No local changes to save" and creates nothing; treat that as the clean-tree case in step 1, not
   as a created entry to restore later.
3. **Record the new entry's commit SHA, immediately**: `git rev-parse stash@{0}`. The SHA, not a
   `stash@{n}` position or the message, is this entry's stable identity — a position shifts as other
   stashes are pushed or dropped, and a message is not guaranteed unique (two entries can carry the
   same description). Resolve the entry's current position from the recorded SHA whenever a
   `stash@{n}` selector is actually needed; never assume a remembered position still applies.
4. Do the isolated work.
5. **Restore by the recorded SHA, preserving the original staged/unstaged distinction**:
   `git stash apply --index <the recorded SHA>`. `apply` accepts a bare commit SHA directly — unlike
   `drop` (step 7), it treats `<stash>` as any commit that looks like a stash, not only a
   `stash@{n}` reflog entry.
6. **Verify before dropping.** Confirm the restoration actually matches what was set aside — the same
   file contents, and the same staged/unstaged split — using `git status --porcelain` and the
   relevant diffs, before removing the entry.
7. **Resolve the current selector before dropping — do not assume the SHA itself works.**
   `git stash drop` only accepts a `stash@{n}` reflog entry, never a bare SHA. Immediately before
   dropping, resolve which current position holds the recorded SHA: `git stash list --format='%gd
   %H'`, and take the `%gd` of whichever line's SHA matches the one recorded in step 3. If no line
   matches — the recorded entry can't be found in the current stash list — stop: do not guess a
   position, and do not drop anything. Report that the entry's identity couldn't be resolved; nothing
   is lost as long as nothing is dropped.
8. **On conflict, failed verification, or unresolved identity: do not drop the entry, and do not
   report the restoration as successful.** Leave it in place, report the specific problem, and let the
   human decide how to resolve it — the content stays recoverable exactly because the entry was never
   dropped.
9. **Never act on a stash entry this procedure did not itself create in this step** — an older,
   unrelated entry, even one carrying an identical message, is left exactly as found by matching its
   SHA, never its position or message.

This is the smallest reliable procedure for this specific need. It is not a general Git-management
subsystem, and using it here does not mandate stashing, or any other isolation mechanism, for
ordinary work that never needed to clear the tree in the first place.
