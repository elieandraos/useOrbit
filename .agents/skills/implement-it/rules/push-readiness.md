# Push Readiness

## When this applies

Consult this file whenever this skill needs to confirm that a set of local commits is reachable on
its correct remote branch, or needs to push commits to reach that state. Current callers:

- `rules/issue-closure.md`'s "Ask first" — confirms reachability, pushing if needed, before asking
  whether to close an issue.
- An authorized delivery correction (`SKILL.md`'s "Delivery corrections") — pushes the correction once
  it is verified, with no issue closure involved at all.
- `rules/commit-reconstruction.md` — reads this file's unpushed-range check (step 2 below) to confirm
  every commit a reconstruction touches is still unpublished before rewriting any of it.
- `rules/review-gates.md`'s "Approval validity before Gate 2 and before push" — reads this file's
  reachability check (step 2 below) to distinguish a commit's mere remote presence from proof that it
  was ever reviewed or authorized.

This file owns the reachability-and-push procedure itself. It does not decide whether an issue should
be closed (`rules/issue-closure.md`) and does not decide whether a delivery correction is authorized
(`ship-it/rules/ci-failure-correction.md`) — it only carries out and verifies the push once a caller
needs one.

## Procedure

> Push readiness marks a set of commits as done — reachable on the remote branch this workflow
> actually tracks that work against, not merely present in a local checkout. A record that cites a SHA
> GitHub cannot resolve is not durable.

Once the verification this work requires (`rules/verification.md`) has passed, confirm the relevant
commits are reachable on the correct remote branch.

1. **Identify the correct remote branch.** For ordinary issue implementation,
   `rules/sequencing.md`'s "Branch readiness" already selected it — the repository's trunk branch for
   Backlog/hotfix work, or the milestone's shared branch for milestone work; this step reads that
   selection, it does not re-derive or override it. For an authorized delivery correction, the branch
   is the milestone's already-open PR branch the correction is being applied against.
2. **Check whether the commits are already there.**

   ```
   git fetch origin <branch>
   git log origin/<branch>..HEAD --oneline
   ```

   An empty result means every local commit is already on the remote branch. A non-empty result means
   commits still need to be pushed.
3. **Re-run the mechanical trailer check across the whole unpushed range, not just the last
   commit.** `rules/commit-boundaries.md`'s mechanical post-commit verification already checks each
   commit individually at creation time; this is a deliberate second, independent pass over every
   commit about to leave the local repository — the last line of defense before a violation added after
   its own creation-time check had already run reaches the remote:

   ```
   for sha in $(git log origin/<branch>..HEAD --format=%H); do
     git log -1 --format=%B "$sha" | git interpret-trailers --parse | grep -q . \
       && echo "violation: $sha"
   done
   ```

   Check each commit's message through `git interpret-trailers --parse` individually — concatenating
   the whole range's raw messages before parsing would blur trailer blocks across commits and can
   both miss and misattribute a violation; parsing one commit at a time is what keeps the result
   attributable to a specific SHA. Per `rules/commit-boundaries.md`'s "Trailer policy," this workflow's
   commits contain no Git trailers at all — the policy has no exception — and the workflow's own
   `Refs #N` reference line never parses as one, so any parsed trailer output is unconditionally a
   violation, with no authorized case to check for. No `violation:` line printed for the whole range
   is the only passing result; quote the literal command and its (absence of) output as evidence. Any
   `violation: <sha>` line is a hard failure while that commit is still unpushed — correct it via
   `rules/commit-boundaries.md`'s amend-and-reverify procedure before proceeding to step 5 below. If
   step 2 was already empty (the commits are already remote) and this check still prints a violation,
   the violation is already published: report it plainly rather than silently amending — rewriting
   already-pushed history is a separate, explicitly authorized path (`rules/commit-boundaries.md`'s
   "Review corrections fold into their semantic commit"), not something this step does on its own.
4. **Either way, confirm the approval this step relies on is still valid** — per
   `rules/review-gates.md`'s "Approval validity before Gate 2 and before push," which owns the
   substantive check; this rule only routes to it. Run it on both paths, not only the one that
   pushes — resumed work with nothing left to push still needs its approval confirmed applicable
   before advancing, exactly as much as work that still needs pushing does. Remote presence never
   substitutes for that confirmation. If the check finds missing or stale approval evidence, report it
   and resolve it the way `rules/review-gates.md` directs — never silently assume the approval still
   applies.
   - **Already remote (step 2 was empty).** Once approval validity is confirmed, this procedure is
     satisfied; proceed to whatever the caller does next (`rules/issue-closure.md`'s "Ask first," or
     the authorized correction's own next step).
   - **Not remote yet.** Once approval validity is confirmed, ask for explicit authorization to
     push — unless push authorization for this exact content was already granted earlier in this
     same session and remains applicable, in which case proceed to step 5 without asking a second,
     redundant time. Re-check applicability again immediately before the actual push mutation, even
     when authorization was granted earlier: preserve it if it still demonstrably applies; if it no
     longer does, that's a stop, not a silent reuse.
5. **Push normally once authorized.** A plain push to the branch identified in step 1 — never
   `--force` or an equivalent override. A push rejected because the remote has diverged is a genuine
   problem to surface to the human, not something to force past.
6. **Verify the result; don't trust the exit code.**

   ```
   git fetch origin <branch>
   git log origin/<branch>..HEAD --oneline             # empty: local/remote parity restored
   git merge-base --is-ancestor <sha> origin/<branch>  # per commit this check covers
   ```

   Both must hold: nothing local remains unpushed, and every commit this check covers is specifically
   an ancestor of the remote branch.

Only once step 2 or step 6 confirms remote reachability is this procedure satisfied.

## What this procedure does not do

**This is a reachability check, not a milestone or release event.**

- It does not create, review, or merge a PR. A milestone issue can close while its shared branch is
  still well before PR creation — the branch itself carrying the pushed commits is what this
  procedure adds, not a PR.
- It does not trigger or imply a release (`ship-it/rules/release.md`) or milestone closure
  (`ship-it/rules/milestone-completion.md`) — those stay gated on their own, later, post-merge
  authorization.
- It is not a reason to rerun a prior full-suite verification. That verification
  (`rules/verification.md`) already proved the relevant commits correct on the working tree that
  produced them; pushing that same, already-verified state to the remote doesn't change what it
  proved.

## Do / Don't

**Do**
- Confirm commits are reachable on the correct remote branch before treating this procedure as
  satisfied, requesting explicit authorization to push when they aren't.
- Re-run the mechanical trailer check across the entire unpushed range before requesting push
  authorization, quoting its literal result as evidence.
- Push with a plain, non-force push once authorized, and verify the remote ref afterward instead of
  trusting the exit code.
- Confirm approval validity on both the already-remote and not-yet-remote paths, per
  `rules/review-gates.md`.

**Don't**
- Push a range that hasn't passed the mechanical trailer re-check, or rely on the creation-time check
  alone.
- Force-push, or treat a push's exit code as proof it reached the remote.
- Treat a commit's mere presence on the remote branch as evidence it was ever reviewed or authorized.
- Rerun a prior full-suite verification merely because this procedure ran.
- Create, review, or merge a PR, or trigger a release or milestone closure, from this procedure.
