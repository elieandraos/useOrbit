# Commit Reconstruction

## When this applies

Use this procedure only for the specific case `commit-boundaries.md`'s "Review corrections fold
into their semantic commit" hands off to it: a review correction belongs to a commit that already
exists locally but has not yet been pushed. A correction found before anything is committed never
reaches this file — it simply becomes part of whichever semantic commit it belongs to, with no
reconstruction needed. Ordinary commit-boundary derivation and commit building never need this
procedure either.

Don't bolt a fixup commit on top. Rebuild history so the correction lands inside the commit it
actually belongs to — but only within the unpublished range. Rewriting a commit already reachable on
the remote branch is outside this recipe entirely: that requires specific human authorization and a
different path, never a silent rewrite. This maintenance boundary applies generally, not only to
this skill's own commits.

1. **Find the owning commit and confirm it's unpublished.** Identify which commit the correction
   actually belongs to — call it O. Confirm every commit from O through `HEAD` is still unpublished
   (`rules/issue-closure.md`'s "Push readiness": `git fetch origin <branch>`, then
   `git log origin/<branch>..HEAD --oneline` — every commit this reconstruction touches must appear
   in that list). If O predates the unpublished range, this recipe does not apply.
2. **Establish a scratch location for this reconstruction's own recovery artifacts, outside the
   repository working tree** — an explicit, uniquely-named directory (for example, one made with
   `mktemp -d`), never a path inside the worktree. Every file this procedure captures for its own
   bookkeeping — a correction's isolated content, a private index, an extracted or merged result —
   lives there. A recovery artifact placed inside the worktree instead is exactly the kind of
   untracked content `git stash -u` (used below) would sweep away. Preserve everything in this
   location until reconstruction is verified complete; remove it only then, never on a failure.
3. **For each path the correction touches, capture its committed, staged, and actual current
   content — nothing is categorized yet.** Save the path's committed content
   (`git show HEAD:<path> > <scratch>/head--<path>`, empty for a new path), its real-index content
   (`git show :<path> > <scratch>/indexblob--<path>`, empty only if the path has no index entry), and its
   actual current combined content (`cp <path> <scratch>/combined--<path>`). Nothing here mutates the
   real index or working tree.
4. **Isolate the correction's own hunk(s) explicitly, from every difference the path shows against
   `head` — regardless of what already happens to be staged.** A capture that only inspects the
   real index and treats "whatever's left unstaged" as automatically homogeneous can mislabel an
   *additional* unrelated hunk as part of the correction the moment more than one thing differs from
   `head` for the same path — for example, a single file can carry three separate hunks at once: one
   unrelated hunk already staged, a second unrelated hunk still unstaged, and the correction itself
   also unstaged. Subtracting only the known, already-staged unrelated hunk from the combined content
   would fold that second, unstaged unrelated hunk into "the correction" — and the mistake would
   still round-trip cleanly (step 5's round-trip check confirms recombination, not attribution, so it
   cannot catch this on its own). Never derive the correction by subtracting a known piece from
   `combined` — establish it positively instead:
   - Seed a private index copy from `HEAD` (`GIT_INDEX_FILE=<scratch>/index git read-tree HEAD`),
     then run `GIT_INDEX_FILE=<scratch>/index git add -p -- <path>` against that private copy. This
     shows every hunk that differs from `head` — whether or not the real index already carries some
     of them — as individually selectable units. Select only the hunk(s) that are the correction.
   - **If the correction can't be cleanly separated this way, stop** — this is a capture-time
     ambiguity, not something to guess past:
     - one hunk mixes the correction with something else, with no way to select one without the
       other — for example, a single-line correction directly adjacent to a single-line unrelated
       insertion of identical text; `git add -p`'s own hunk-splitting can refuse outright on a case
       like this;
     - two or more candidate edits are textually identical, so the diff gives no way to tell which
       one is the correction — selecting one over the other by position or order is a guess, not a
       determination, even where the tooling would technically let you force it.
   - Otherwise, materialize the private index's blob as the correction's content
     (`GIT_INDEX_FILE=<scratch>/index git show :<path> > <scratch>/correction--<path>`), then discard
     the private index file. The real index and working tree are untouched throughout — every hunk
     that was already staged elsewhere, and its staged/unstaged arrangement, stays exactly as found.
5. **Extract the unrelated-only content, and verify the split by round-trip.** A successful command
   is not, by itself, proof of correct attribution — a context-matched or zero-context patch can
   silently apply against the wrong occurrence of identical-looking content without ever reporting a
   conflict. Use a real three-way merge instead — and because the correction is already known,
   positively, from step 4, this step only needs to compute its complement, never guess at the
   correction itself:
   ```
   cp <scratch>/head--<path> <scratch>/extracted-unrelated--<path>
   git merge-file -p <scratch>/extracted-unrelated--<path> <scratch>/correction--<path> \
     <scratch>/combined--<path> > <scratch>/eu-out--<path> && \
     cp <scratch>/eu-out--<path> <scratch>/extracted-unrelated--<path>
   ```
   **A nonzero exit code is a conflict: stop.** Do not resolve it by guessing which occurrence is
   which. Report the conflict; every file step 3 and step 4 captured remains in the scratch location
   as recovery data, and nothing about the real repository has been touched yet. On a zero exit code,
   verify by round-trip before trusting the result — reconstruct the original combined content
   independently and require an exact match:
   ```
   cp <scratch>/correction--<path> <scratch>/roundtrip--<path>
   git merge-file -p <scratch>/roundtrip--<path> <scratch>/head--<path> \
     <scratch>/extracted-unrelated--<path> > <scratch>/roundtrip-result--<path>
   diff <scratch>/roundtrip-result--<path> <scratch>/combined--<path>
   ```
   Anything but an exact match means the split is not trusted, exactly as if the merge had
   conflicted — stop and report; do not proceed on an unverified split. **This round-trip proves only
   that `correction` and `extracted-unrelated` recombine to reproduce `combined` exactly — it is a
   check on content recombination, not on semantic ownership.** It cannot, by itself, prove the split
   correctly separates the correction from unrelated content; that attribution came from step 4's
   explicit, per-hunk selection. A round-trip match on top of a wrong step-4 selection would still
   pass — round-trip is a mechanical safety net layered on top of correct attribution, never a
   substitute for establishing it.
6. **Before clearing any real path, stashing, or resetting, confirm the unrelated content's
   staged/unstaged shape can actually be restored later.** Compare `extracted-unrelated` against
   `head` and against `indexblob`:
   - identical to `head` — no unrelated content exists for this path; nothing to protect here.
   - identical to `indexblob` — every bit of the unrelated content is already staged, cleanly;
     restore it staged (step 12).
   - `indexblob` identical to `head` — nothing was ever staged for this path; restore the unrelated
     content unstaged (step 12).
   - **otherwise** — `indexblob` matches neither. Either the real index already contains a mix of
     the correction and unrelated content for this path — both staged together, with nothing further
     unstaged, is one concrete way this happens — or the unrelated content itself spans a staged
     fragment and an unstaged fragment (the three-hunk case in step 4 is exactly this once the
     correction is subtracted out). Either way, the restoration this recipe supports (step 12) applies
     one staged/unstaged classification to the whole path — it cannot represent that split. **Stop
     here.** Nothing has been cleared, stashed, or reset yet; report the unsupported split and leave
     this path exactly as found. This is a deliberate limitation, not a gap to route around with more
     merge machinery — a genuinely mixed path needs a human decision, not an invented resolution.
7. **Exclude a correction-touched shared path from the general protection step below; protect
   everything else there instead.** For a path where step 6 found no genuine unrelated content,
   there's nothing to protect for that path at all. For a path with genuine, cleanly-classified
   unrelated content sharing the file with the correction, set its working tree content to `head`
   directly (`cp <scratch>/head--<path> <path>`, unstaging first if needed) — its unrelated content
   stays safe in the scratch location, to be reapplied in step 12 against the *reconstructed* content,
   not restored through git's stash. Git stash's own restoration depends on the commit its entry was
   taken against still matching history; a shared path's committed content changes by the very act of
   reconstruction, which can make an ordinary `git stash apply` conflict against content a direct
   merge handles cleanly.
8. **Protect whatever unrelated content remains** — every path the correction never touched, plus any
   correction-touched path step 7 didn't already clear — using the qualified procedure in
   `rules/worktree-preservation.md`, unmodified. When nothing remains (every touched path was cleared
   in step 7, and nothing else was ever uncommitted), that procedure's own clean-tree check means it
   protects nothing, correctly.
9. **Rewind to the owning commit's parent, keeping everything from O through `HEAD` staged**:
   `git reset --soft O~1` (or the equivalent parent reference) — not a hard-coded `HEAD~1`, which
   only reaches the single most recent commit and cannot fold a correction into an earlier one. This
   stages the combined diff of every commit from O through `HEAD` in one index; it does not, on its
   own, separate that index back into O's corrected content and whatever later commits actually
   contain.
10. **Reapply the correction directly from its captured content — never by pattern-matching a patch,
    and never by retyping it from memory.** For each path the correction touches, the working tree
    is, at this point, exactly `head` (nothing else is present, per steps 7–8):
    `cp <scratch>/correction--<path> <path>`, then `git add <path>`. This is a direct, exact,
    byte-for-byte write of already-verified content, with no patch-matching ambiguity possible.
11. **Rebuild each semantic commit from that combined index, in order, staging only that commit's own
    content each time.** A single reconstructed commit is not the same thing as "one whole file" —
    use `git restore --staged <path>` to unstage what a later commit owns, and `git add -p` (or an
    equivalent patch-level staging tool) to stage only part of a file when O's correction and a later
    commit's content share one file.
    - **Before every `git commit` in this rebuild, inspect the actual staged diff**
      (`git diff --staged`) and confirm it contains exactly the intended semantic group — no more, no
      less — rather than trusting which `git add` commands were run. This review is a defense, not a
      substitute for correct capture: it catches an obviously wrong diff, but a wrongly-included hunk
      that looks plausible in context can still slip past it — steps 4–6 are what make the diff
      trustworthy in the first place.
    - Commit, then verify the resulting commit in isolation before moving to the next group — every
      commit this reconstruction produces needs `rules/isolation-verification.md`'s technique (the
      same escalation `rules/verification.md`'s "Isolation verification" section reserves for exactly
      this case), not only when it happens to be convenient. Reconstructing semantic history from an
      already-implemented diff, after the fact, is exactly the case that technique reserves the
      escalation for.
    - Repeat until every group from O through `HEAD` has its own commit again: O reconstructed with
      the correction folded in, then each subsequent original commit rebuilt intact from the
      remaining staged content — unless the correction itself changes what a later commit should
      contain.
12. **Restore the protected content.** For an ordinary path, restore from step 8's stash entry using
    that procedure's own restoration steps, once every reconstructed commit exists. **For a
    correction-touched path step 7 set aside separately, restore by the same merge technique as
    step 5 — against the path's *new*, reconstructed content, not the pre-reconstruction committed
    state:**
    ```
    cp <path> <scratch>/final--<path>
    git merge-file -p <scratch>/final--<path> <scratch>/head--<path> \
      <scratch>/extracted-unrelated--<path> > <scratch>/final-result--<path>
    ```
    A nonzero exit code, or a result missing either the correction's own content or the unrelated
    content, is a restoration failure — stop, report it, and drop or discard nothing; the scratch
    location still holds every piece needed to retry or hand off. On success, write the result back
    (`cp <scratch>/final-result--<path> <path>`) and stage it or leave it unstaged exactly as step 6
    already classified — that classification, not a fresh judgment call here, decides it.
13. **Confirm nothing unrelated leaked in.** After the last commit, `git status` and `git diff`
    should show exactly the restored unrelated content and nothing else outstanding from this
    reconstruction. Only once this holds, remove the scratch location from step 2 — not before, and
    never on a failure.

The result reads as if it had been built that way from the start — there's no trace in the history
that it was originally committed differently, and every intermediate commit still satisfies
`commit-boundaries.md`'s "What makes a commit coherent."

Never preserve every conversational step as its own commit "for the record." The history should read
as a sequence of decisions, not a transcript.
