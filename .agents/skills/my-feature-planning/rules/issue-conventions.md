# Issue Conventions

Format and metadata-approval methodology for every drafted issue, portable across GitHub-based
projects. This rule owns the portable title shape and issue-body methodology below; the consuming
project supplies its domain vocabulary, metadata conventions, taxonomy, palette, assignment rules, and
live GitHub state.

## 1. Canonical definitions

Every rendered preview, every manifest entry, and every final GitHub body is generated fresh from the
current **canonical issue definition** — never reconstructed from an earlier preview or rendered
draft. See `SKILL.md`'s non-negotiable contracts and `rules/review.md`'s three integrity checks for
how this is enforced; this file doesn't restate that mechanism.

## 2. Title convention

One form, for every issue regardless of layer, shape, or origin (planned, extension, bug, refactor,
or discovered-work):

```
<Area/Capability>: <action or outcome>
```

- Describe the change or observable outcome, not the implementation layer — never encode
  implementation-layer batching in the title itself. Categorization may be represented through
  approved project metadata (a label, a milestone), but none is guaranteed to exist.
- Use a concise action ("add", "remove", "fix") or an observable outcome ("silently resets...",
  "returns invalid response for...") — whichever states the change most plainly.
- Add a surface/location qualifier (a page name, a settings area) only when it materially improves
  clarity — not by default.
- Keep it concise and scannable — a title is a label a developer scans in a list of thirty open
  issues, not a summary of the Context section.
- No bracket prefixes. Titles are plain sentences — no `[Billing]`, `[Backend]`, etc. The title shape
  above already states the change or outcome; a prefix only breaks scannability.

Examples:
- `Billing: add invoice export`
- `Search: fix pagination on filtered results`

## 3. Standalone GitHub references

`#N` in an issue body is reserved exclusively for real GitHub issue/PR references — a legitimate
dependency (`Depends on #<N>`), or a pointer to another real issue in the same set. GitHub linkifies
any `#` followed by digits whether you meant it that way or not, so keep these four things distinct
and never collapse one into another's syntax:

- **Real GitHub references** — an actual issue/PR number, written `#<N>`.
- **Canonical planning identifiers** — this planning pass's own sequence numbers before issues exist
  on GitHub (e.g. "canonical issue 5").
- **Plan decision identifiers** — a locked decision's number from `plan.md` (e.g. "decision 7").
- **Plan section references** — a section citation (e.g. "`plan.md` §2.5").

Never write any of the last three as `#N` — `decision #7` or `LOCKED #9` renders as a link to whatever
issue or PR happens to be numbered 7 or 9 in this repo, almost certainly unrelated. Use natural,
hash-free wording instead: "decision 7", "canonical issue 5". Before an issue is created, every
planning-only number in its body must already be resolved to either a real `#<N>` (a genuine
dependency) or rewritten as plain wording — never left as a bare planning-only number.

Plan sections and planning conversations are never load-bearing. Do not put a `plan.md` section
citation or a reference to "the planning conversation" into an issue body as a substitute for
explanation — summarize the necessary decisions, constraints, and rationale directly in Context or
Acceptance Criteria instead. A brief `plan.md` pointer is allowed only *in addition to* an
already-complete explanation, never in place of one.

## 4. Issue-body authoring

The issue has to be useful to a developer who wasn't in the planning conversation — not just a
checklist for whoever drafted it. Structure every body around this order:

1. **`## Context`** — why this issue exists: the problem it solves, the outcome that should be true
   when it's done, the constraints that actually matter, what's in scope, what's explicitly out of
   scope, and relevant dependencies. Prose, not a task list. Context must explain a decision, never
   merely cite one — "Implements decision 7" names where an explanation could be found instead of
   giving it.
2. **`## Tasks`** — the work required to deliver the outcome, not merely "where to work." Real,
   literal Markdown checkboxes:

   ```markdown
   ## Tasks
   - [ ] <change 1>
   - [ ] <change 2>
   - [ ] Tests
   ```

   Tailor the steps to what the issue actually covers. GitHub renders these as checkboxes ticked off
   as work lands, giving a visible in-progress state on an issue spanning more than one sitting.
3. **`## Acceptance Criteria`** (optional) — guarantees and observable outcomes that must remain true
   when the work is done, not a restatement of a Tasks line with different wording: business rules,
   authorization/tenancy invariants, lifecycle guarantees, observable outcomes, explicit non-goals
   wherever accidental scope expansion is likely. Skip it for a small issue where Tasks alone are
   clear — not every issue needs one. These stay ordinary `- ` bullets, never checkboxes.
4. **`## Tests`** (optional) — earns its own section only when naming the specific behaviors/
   guarantees needing proof adds real value beyond what Acceptance Criteria and Tasks already imply
   (e.g. a multi-audience event needs a test per audience, a tenancy boundary needs both an in-tenant
   and cross-tenant case). Stay at the level of what needs proving — no method names, no
   assertion-by-assertion recipe.

Don't let the issue become a code blueprint. A technical reference may identify a real seam when it
helps define scope (e.g. "extends the same base class as the existing filter"), but it supports
Context/Acceptance Criteria — it doesn't replace them. None of these belong in a planning-drafted
issue: a walkthrough of every class/method to be written, a copy of the investigation that led to the
issue, a step-by-step coding tutorial, or an exact test-implementation recipe. If a draft starts
accumulating that kind of detail, cut it back to context, scope, and guarantees.

Mechanical validation of all of the above — self-containedness, literal checkboxes, real guarantees —
is `rules/review.md`'s job, not this section's; see its issue-body content integrity check.

## 5. Metadata proposal and approval

This is a proposal-and-approval workflow, not a lookup-and-create one. Keep four things distinct at
every step: **explicit project conventions** (patterns the consuming project has stated, used only to
shape a proposal), **observed GitHub state** (what currently exists), **planning's proposed metadata**
(what's being suggested for this feature), and **approved metadata** (what actually gets created, only
after the user says so).

> Repetition in repository history is evidence that may shape a proposal; it is not automatically a
> project policy.

The workflow, in order:

1. **Query complete current GitHub state** — milestones (including closed ones, when relevant to a
   collision/reuse decision) and labels, without silently truncating results.
2. **Discover explicit project conventions** from project documentation, configuration, or reliable
   user context.
3. **Draft exact proposals** for the applicable fields (milestone title/description, label name/
   color, assignee).
4. **Distinguish existing metadata to reuse from new metadata requiring creation** — don't blur the
   two into one list.
5. **Carry the proposal into `SKILL.md`'s final manifest/metadata approval gate.** There is no
   separate, earlier or later approval checkpoint for metadata — it's proposed early and approved at
   that one existing gate.
6. **Create nothing until that approval.** Don't create a milestone or label speculatively while
   still drafting issue content, and don't create one just because a convention made the value seem
   obvious.

If no explicit project convention exists for a field: don't invent one and present it as policy. Make
a reasonable GitHub-native proposal from current state and the feature's own language, label its basis
honestly (e.g. "no existing convention found — proposing X because..."), and leave the final choice to
the user.

## 6. Milestones

- If the user has already named the milestone, reuse it — but still check whether it exists and
  whether it's open and appropriate, rather than assuming.
- Otherwise, propose the title explicitly; don't guess it.
- Never assume sequential or numbered phase naming — a consuming project's convention, if any, is
  discovered per §5, not assumed.
- Distinguish a scoped delivery milestone from a persistent catch-all/backlog-style milestone by
  purpose, not by name — a project may name either one anything.
- Don't default unclear work into a catch-all milestone; ask when placement materially affects scope.
- Milestone creation requires the metadata approval in §5, same as any other field.

Milestone lifecycle beyond this point is not this rule's job. This rule's responsibility stops at
classifying and defining a milestone's scope during planning — milestone PR-readiness and closure
belong entirely to `my-git-workflow`'s `rules/milestone-completion.md`, whose current contract governs
when a milestone is ready for a PR and when it's eligible to close, independently of when its release
publishes. A scoped delivery milestone and a persistent catch-all milestone stay structurally distinct
through that downstream lifecycle too, since it treats them differently. Always consult that rule's
current contract directly rather than restating it here.

### Milestone descriptions

A description is optional — propose one only when the title and issue set alone don't already make
the milestone's intent, boundaries, exclusions, governing principles, or completion criteria clear. It
isn't a default habit to fill in for every milestone.

- A proposed description must be derived from already-approved scope and decisions — it must not
  introduce a new product or architecture decision of its own.
- It cannot override an approved `plan.md` locked decision; if the two conflict, that's a discrepancy
  to surface, not something the description resolves silently.
- Once approved and created, downstream issues drafted into that milestone must not silently
  contradict its stated scope.
- An existing milestone description is scope input for later drafting, but any conflict between it and
  a canonical approved decision must be surfaced rather than blindly inherited.
- Propose the exact description text at the same metadata proposal stage as any other field (§5) —
  never write one directly into GitHub without approval.

## 7. Labels

- Query and reuse exact existing labels when appropriate — don't recreate one that already exists
  under a slightly different name.
- Avoid near-duplicate labels.
- Follow an explicit project-supplied label taxonomy or palette when one exists (discovered per §5).
- For a genuinely new label, propose the exact name and color; create nothing before approval.
- Justify any label proposed outside an established project taxonomy — don't introduce one just
  because it seems useful.
- Don't assume every feature needs its own label, or that a given implementation category always
  gets a shared label — that's project convention to discover, not a default to apply.

This rule never stores a project's live label state (an existing palette, a "next color to assign," a
rotation position) as part of its own methodology — that state changes the moment a label is created,
and belongs to querying current GitHub state (§5 step 1) and any project-supplied taxonomy (§5 step
2), never to this file.

## 8. Assignee

Assignment is project- or user-supplied metadata, not a portable default. The proposed assignee must
come from one of:

- explicit user instruction,
- an established project convention (discovered per §5), or
- an explicit proposal included in the final metadata approval (§5).

Never silently assign anyone by default. Before the mutation, verify a named assignee is actually
valid for the repository.

## 9. Discovered-work issues

Issues coming out of `rules/discovered-work.md`'s intake follow the same Context → Tasks → Acceptance
Criteria → Tests shape and the same self-containedness bar as any other issue, with one legitimate
difference: the mechanism behind the finding isn't always fully resolved by the time the issue is
written.

- **Context states what's confirmed vs. what's still a hypothesis or unknown, explicitly.** Don't let
  an unresolved mechanism get smoothed into a confident-sounding sentence that isn't actually proven —
  a reader must be able to tell which parts are fact and which are still open.
- **An unresolved mechanism can be one of the issue's own Tasks** (e.g. "capture the actual request to
  identify why...") rather than blocking issue creation until it's fully understood. This is
  legitimate only when `rules/discovered-work.md`'s stopping condition was actually met — never as a
  shortcut around investigating.
- **A defect distinguished from intended behavior during investigation is scoped as what it actually
  is.** If investigation shows the reported behavior is working as designed, the issue (if one is
  still warranted) is about the real remaining gap — a missing explanation, a UX gap — not a
  mischaracterized code defect.

This section governs how the finding is *written*, not how deeply it's investigated — investigation
depth and its stopping condition are `rules/discovered-work.md`'s job; don't duplicate that procedure
here.

## 10. Verification checkpoints inside a multi-group issue

An issue whose Tasks span multiple implementation groups or checkpoints (a multi-tranche dependency
upgrade, a multi-stage migration) may legitimately name a verification step after each group. What
that step should ask for is a portable authoring question, distinct from how deeply `my-git-workflow`
actually executes verification once implementation starts (`my-git-workflow/rules/verification.md`
owns that).

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
  `my-git-workflow/rules/verification.md` already owns exactly this run, at the completed-issue
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

This section governs what an issue's own Tasks/Tests ask for. It does not change what
`my-git-workflow` actually runs once implementation starts — that remains
`rules/verification.md`'s default narrowest-reliable-scope-per-commit model, with a full regression
run reserved for the completed-issue boundary.

## 11. Completion criteria must be satisfiable at their own closure boundary

Before a canonical issue is finalized, check its Tasks, Acceptance Criteria, Tests, dependencies, and
expected closure boundary together — not each in isolation. A completion requirement is only real if
it can actually be satisfied at the point this issue is expected to close under the consuming
project's own delivery workflow.

`my-feature-planning` does not own that delivery workflow, and this section does not redesign it — see
`SKILL.md`'s "Handoff" and `my-git-workflow/rules/milestone-completion.md`'s current contract for how
and when a milestone issue actually closes relative to its milestone's PR. What this section owns is
narrower: an issue's own stated completion bar must not describe proof that structurally cannot exist
yet at that issue's own closure boundary.

- **A per-issue Acceptance Criterion or Test that depends on evidence only available at a later
  milestone boundary is not a valid per-issue completion requirement.** For example: a milestone
  delivery workflow that closes each of its issues on a shared branch, before any PR exists for that
  milestone, cannot produce a real CI run against a PR or push as proof for an issue closing earlier
  in that same milestone — the PR, and the CI trigger it opens, don't exist yet at that issue's own
  closure boundary, regardless of how the issue is worded.
- **Route that proof to where it can actually happen.** If real, PR-triggered verification is a
  genuine requirement of the work, state it as a requirement at the milestone/PR boundary it actually
  belongs to — not as a per-issue closing condition, and not as something blocking a dependent issue
  from starting. A dependent issue is unblocked by the prerequisite's actual outcome existing, not by
  a proof that can only be produced later.
- **Disclosing the gap in the issue body is not a substitute for not creating it.** Naming the
  limitation honestly is better than hiding it, but an issue whose own Tests section states a
  condition that cannot be met by its own closure is still an internally inconsistent contract — fix
  the requirement's placement, don't just caveat it.
- **This is a narrow check, not a license to redesign delivery.** It doesn't decide branch strategy,
  when a milestone issue actually closes, or when a milestone's PR opens — those stay
  `my-git-workflow`'s. It only stops planning from handing that workflow an issue whose own completion
  bar was never satisfiable in the first place.
