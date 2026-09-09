# Verification Checkpoints in Multi-Group Issues

## When this applies

Consult this file only once a canonical issue's Tasks actually span multiple implementation groups
or checkpoints — a multi-tranche dependency upgrade, a multi-stage migration. An issue whose Tasks
form one coherent implementation group needs nothing from this file;
`rules/issue-conventions.md` §4's ordinary Context → Tasks → Acceptance Criteria → Tests shape
already covers it.

## What each checkpoint should ask for

What a verification step should ask for after each group is a portable authoring question, distinct
from how deeply `implement-it` actually executes verification once implementation starts
(`implement-it/rules/verification.md` owns that).

- **State what each intermediate checkpoint needs to prove, not a fixed command to run.** A checkpoint
  after a frontend-only group needs to prove the frontend surface is sound; it does not automatically
  need proof that an unrelated backend suite still passes.
- **Do not copy the same full-regression instruction after every group merely for symmetry.** Wording
  like "run the corrected CI gate and confirm green" after each of several groups, applied uniformly
  regardless of which surface that group actually touches, reads as thorough but drives verification
  disproportionate to what changed — re-running a full backend regression after a change that could
  not have touched the backend proves nothing new each time.
- **Scale the checkpoint to the affected surface.** A group that only touches one stack (frontend
  tooling, a single package family) calls for verification scoped to that surface; a group that could
  plausibly affect a different surface (a linter or type-checker major version, for instance, can
  change results in files nobody touched) may legitimately warrant a broader check — justify the
  broader ask by what that specific group could actually affect, not by habit.
- **Reserve a full regression run for the issue's own completion, not every intermediate group.**
  `implement-it/rules/verification.md` already owns exactly this run, at the completed-issue
  boundary, once all of the issue's commits exist — an issue's own Tasks/Tests should not duplicate
  that requirement at every checkpoint along the way. Ask for a full regression run at an intermediate
  checkpoint only when that specific intermediate state genuinely needs broader proof (for example, a
  reconstructed or reordered intermediate state whose own correctness must independently be shown) —
  not as the default per-checkpoint instruction.
- **A concrete project command may appear when current repository evidence makes it material** — e.g.
  naming the one aggregate command a project actually exposes, when no narrower one exists yet — but
  state it as evidence for this issue's own wording, not as a portable checkpoint methodology. A
  project's specific command name is never itself the rule; the rule is proportionality to affected
  surface.

## Relationship to implement-it's execution

This file governs what an issue's own Tasks/Tests ask for. It does not change what `implement-it`
actually runs once implementation starts — that remains
`implement-it/rules/verification.md`'s default narrowest-reliable-scope-per-commit model, with a
full regression run reserved for the completed-issue boundary.
