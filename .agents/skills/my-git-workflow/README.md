# my-git-workflow

## What this skill is

This is the delivery stage of the Agentic Engineering pipeline. It starts from approved work
produced by `my-feature-planning` and owns the Git/GitHub workflow from implementation through
verified delivery and the related release/milestone lifecycle.

It picks up one already-approved work item at a time, establishes which branch that work happens on,
implements only its scope, stops for human review, turns the finished diff into a small number of
coherent commits (also reviewed before they're written), verifies at the right scope, optionally
closes the item, and reports what's unblocked next. Separately, once every issue in a milestone is
closed, it checks whether that milestone's shared branch is actually ready for a PR. Separately
again, once a PR carrying that work has merged and the human authorizes proceeding, two independent
branches open from that same authorization: it discovers the project's real release policy,
classifies the release, drafts outcome-level notes, stops for approval, publishes, and validates the
result — and, for a milestone issue, it checks whether the milestone the work belonged to is actually
ready to close. Neither branch waits for the other to finish.

## Pipeline role

```
my-architecture-laboratory   → understand and reconstruct reality
my-feature-planning          → turn that understanding into approved work
my-git-workflow              → turn approved work into verified delivery      (this skill)
```

**Input:** one already-approved GitHub issue that `my-feature-planning` has drafted, reviewed, and
created. This skill never decides what work should exist and never starts earlier than an
already-approved item.

## What it owns

- Picking up one dependency-ready approved issue at a time, and establishing which branch its work
  happens on (a Backlog/hotfix issue's trunk branch, or a milestone's shared branch).
- The implementation review gate and the commit-plan review gate.
- Proposing and building semantic commit boundaries from the actual diff.
- Verification scope (narrowest reliable scope per commit vs. full-suite vs. isolation-proving a
  split), the regression-baseline treatment of pre-existing lint/format/static debt, and commit
  ordering around feature-activation risk.
- The issue-closure recipe and its post-mutation validation — intentionally before the milestone's
  PR merges.
- Recalculating and reporting the milestone's dependency-ready set.
- Once that set is empty: the milestone's PR-readiness gate.
- Once a PR merges and the human authorizes proceeding: release-policy discovery, classification,
  outcome-level drafting, the release approval gate, publishing, and post-publication validation.
- From that same authorization, independently: the milestone-completion gate, the Backlog exemption,
  and the closure mutation itself — not gated on release publication having finished first (see
  "Release and milestone-completion lifecycle" below).

What it does *not* own is in "Composition and boundaries" below.

## Working lifecycle

There are two distinct paths, chosen by which kind of milestone the issue belongs to (a persistent
Backlog/catch-all vs. a delivery/phase milestone — `my-feature-planning`'s existing distinction):

```
choose a dependency-ready, approved issue
  → BRANCH READINESS
      Backlog/hotfix issue  → work directly on the repository's trunk branch; no feature branch,
                               no branch decision — surface a mismatch if trunk isn't checked out
      Milestone issue       → inspect current branch; if wrong, recommend a branch derived from the
                               milestone's nature and ask before creating/switching; every issue in
                               that milestone shares this one branch
  → implement only its scope → verify (full-project scope)
  → STOP — human reviews the implementation
  → (approved) inspect the finished diff → propose a semantic commit plan
  → STOP — human reviews the commit plan
  → (approved) build the commits, narrowest-reliable verification per commit
  → full regression suite once, at the completed-issue boundary
  → ask: close the issue?
  → (if yes) check Tasks, closing comment, close, post-mutation validation
      (this closure intentionally precedes the milestone's PR merging — see below)
  → recalculate the milestone's dependency-ready set, report it, recommend — human chooses next
      (unless the set just went to zero — see "Milestone PR readiness" below)
```

A Backlog/hotfix issue normally ends here, with no PR. A milestone issue's commits stay on the shared
branch until the milestone itself is PR-ready.

**Branch readiness** is read from which kind of milestone the issue belongs to — a persistent
Backlog/catch-all or a delivery/phase milestone, `my-feature-planning`'s existing distinction, not
redefined here. Backlog/hotfix work makes no branch decision at all — it runs directly on the
repository's trunk branch, never a newly created feature branch; if the checkout isn't already trunk
when that work begins, surface the mismatch rather than silently proceeding on it. Milestone work
shares one branch across every issue in that milestone; if the wrong branch is checked out, recommend
one derived from the milestone's actual nature (never a rigid prefix taxonomy) and ask before creating
or switching to it. Full detail: `rules/sequencing.md`.

Two review gates, not one — approving the implementation is not approving a commit structure; the
same diff can be split several defensible ways, and the human sees the split before it becomes
permanent history. Full detail: `rules/review-gates.md`.

**Semantic commit boundaries** are not a mechanical split by file or folder — a semantic commit is
one implementation decision describable in a single sentence of *why*, leaving a coherent,
non-broken state on its own. Tests travel with the step they prove. Review corrections found before
anything is committed land inside the commit they belong to, never a separate fixup commit. Every
commit implementing a tracked item carries a `Refs #N` trailer — never `Closes`/`Fixes`, since
closing stays a separate, human-approved step. Full detail: `rules/commit-boundaries.md`.

**Verification** uses the narrowest reliable scope per commit — across tests, formatting, linting,
and static analysis — plus one full regression run at the completed-issue boundary — not after every
commit. When a split needs real proof, there's a stronger technique:
commit, stash the rest, verify what's landed in isolation, pop, repeat. Watch for a feature-flag
activation retroactively un-skipping tests whose supporting code hasn't landed yet — reorder before
it lands red, not after. Full detail: `rules/verification.md`.

**Issue closure** is opt-in and always after the commits exist — ask first. If yes: check off
completed Tasks, add a closing comment (summary, verification numbers, commit SHAs), close it, then
re-fetch and confirm state, checkboxes, and comment actually landed — a `gh` command's exit code is
never proof by itself. This closure is intentionally before the milestone's PR merges — closure marks
implementation and verification as done, not that the commits reached the trunk branch. If a later
finding (e.g. milestone manual testing) concerns work an issue already closed, the default is a new
issue referencing the original, not reopening it. Full detail: `rules/issue-closure.md`.

**What's next:** after a validated closure, recompute the milestone's dependency-ready set before
doing anything else, summarize the graph, recommend when there's a clear case, and leave the final
choice to the human. Starting the next issue is its own new pass through this workflow, not a
continuation of the current one. When that recompute finds zero open issues left in the milestone,
report that instead and move to the milestone's PR-readiness gate. Full detail: `rules/sequencing.md`.

## Core principles

- **Issues describe outcomes; commits describe coherent, verified implementation steps.** Neither
  file count, issue size, nor "how many things changed" predicts a commit split — only the actual
  diff does.
- **Two review gates, not one, on the pre-merge side.** Approving the implementation is never
  approval of a commit structure.
- **Never trust a mutation's exit code.** Every GitHub mutation this skill performs — issue closure,
  release publication, milestone closure — is re-fetched and validated afterward.
- **Discover the project's own mechanism before assuming one.** Release policy and publish tooling
  are read from what the project actually has, never assumed from a template.
- **A stop is a report plus a question**, not a wall of options with no recommendation — investigate
  enough to have a position, state it, and let the human decide.

## Release and milestone-completion lifecycle

**Milestone PR readiness**, the first milestone-level gate, starts once `rules/sequencing.md`'s
recompute finds zero open issues left in the milestone — never earlier:

```
milestone's ready set goes to zero
  → confirm, freshly: every issue closed + human confirms final manual testing done
      + that testing found nothing further
  → testing found something → file it as Discovered work (my-feature-planning), attach to this
      still-open milestone, re-run this gate later — never silently reopen the closed issue
  → all three hold → report "PR-ready" (a report, not a mutation — PR creation stays human-owned)
```

Full detail: `rules/milestone-completion.md`. The PR that eventually carries this branch is expected
to reference the milestone it integrates — a confirmed observed convention, not something this gate
enforces (`rules/milestone-completion.md`'s "The milestone-PR reference convention").

**Once a PR merges**, one authorization gate opens two independent branches — publishing a release and
closing the milestone that PR belonged to. Neither branch waits on the other to finish; each is
checked and validated through its own rule:

```
PR merges → human confirms it merged (this confirmation is the integration gate — no independent
    re-verification of merge state)
  → STOP — one human acceptance, authorizing both branches below
      (e.g. "close the milestone and start the release?" → accepted)
  ┌──────────────────────────────┴──────────────────────────────┐
  ▼                                                              ▼
MILESTONE CLOSURE (rules/milestone-completion.md)       RELEASE (rules/release.md)
  → delivery/phase milestone, not Backlog                 → discover the project's actual release
  → zero open issues right now                                policy (explicit config, else history)
  → close (authorization already given above —          → determine theme/outcomes — never from
      no second approval asked), re-fetch, confirm            size
      state is `closed`                                  → draft notes at release-level altitude
                                                            → STOP, human approves version/target/
                                                                title/body
                                                            → publish, re-fetch, validate every field
```

The single acceptance above answers "should we proceed at all" — it is not the same question as
"is this exact release content correct." Milestone closure has no content to approve beyond the
already-granted acceptance and its own eligibility facts, so it proceeds straight to the mutation;
release still has a version/title/body that didn't exist yet at acceptance time, so it keeps its own
separate content-approval stop before publishing.

Neither branch is a precondition for the other: milestone closure does not wait for release
publication to complete, and release publication does not wait for milestone closure. On the project
this workflow was extracted from, the human has typically closed the milestone first and drafted the
release after — but that's an observed habit on one project, not a rule either file enforces; running
the two in the opposite order, or interleaved, is equally valid.

A Backlog/hotfix issue has no PR to merge, so none of this phase applies to it — what release cadence,
if any, covers hotfix work directly on the trunk branch isn't designed here; there's no evidence yet
to extract a rule from. Neither the version scheme nor the release mechanism is baked into this
skill — both are read from each project's own evidence every time.

A delivery/phase milestone represents a bounded body of work intended to ship as a release — that's
about scope and intent, not naming syntax. It stays open through manual testing and any small,
legitimately-scoped follow-up issue discovered there; that's expected, not a process failure. This
is a forward-looking workflow decision rather than an extraction of past practice — see "Real
example of usage" below. Full detail: `rules/release.md` and `rules/milestone-completion.md`.

## Composition and boundaries

**Does NOT own:**
- Deciding what issues should exist, or drafting/creating them; milestone/label creation or scoping.
- The application code, tests, or framework-specific conventions.
- Product/architecture decisions, any review/approval gate's outcome, or final sequencing among
  ready issues.
- How a PR gets created, reviewed, or merged, or any deployment automation.

Those belong to `my-feature-planning`, the stack-specific implementation skills, or the human.

**Composes with:**
- Git and GitHub as intentional core substrate for this methodology — not an abstraction to be
  swapped out.
- Whatever implementation, testing, and tooling skills match the consuming project's actual stack,
  loaded alongside it.
- The workflow machinery is what this skill owns — never the code, tests, or framework conventions.

**Relationship to `my-feature-planning`:** it defines, scopes, drafts, and creates approved work;
`my-git-workflow` implements, reviews, commits, verifies, closes, releases, and completes the
milestone.

```
my-feature-planning   → classify / scope / reconcile / draft / review / create issues + milestone
  → issue approved
my-git-workflow       → branch readiness (trunk for Backlog/hotfix, shared branch for a milestone)
my-git-workflow       → implement / review / commits / review / verify / close  (per issue)
  → milestone's ready set empty → PR readiness (all closed + manual testing confirmed clean)
  → PR opened, reviewed, merged        (not owned by this skill; Backlog/hotfix has no PR at all)
my-git-workflow       → authorization to proceed, then two independent branches (neither waits on
                          the other to finish):
                          - milestone completion check / closure (delivery/phase only, never Backlog)
                          - release: discover policy / classify / draft / approve / publish / validate
```

## Real example of usage

The methodology above is reusable as stated and doesn't require any of what follows to be
understood. This section exists to show it isn't theoretical — it was extracted from a real,
completed pass through this workflow in the project where it was first built, generalized here so no
prior knowledge of that project is needed.

- **One issue, one commit, even at scale.** An issue that touched 21 files across a dozen route
  files, application code, a migration, and tests was still one coherent decision — and shipped as
  one commit. File count didn't predict the split; the diff did.
- **One issue, several dependency-ordered commits.** Another issue split cleanly into
  persistence → wiring → activation, each commit a real implementation decision building on the
  last.
- **Activation-ordering caught a near-miss.** While sequencing a multi-commit build, enabling a
  feature flag in the planned commit order would have retroactively un-skipped pre-existing tests
  whose assertions depended on a change still sitting in a later, not-yet-landed commit. Caught and
  fixed by reordering before it ever landed red — this is the concrete case behind
  `rules/verification.md`'s ordering rule.
- **Release mechanism, discovered, not assumed.** The release phase was extracted from one real
  merge-and-release run: a PR bundling several issues merged to the main branch, then shipped as
  release `{version}` — the project's own established mechanism (a tag plus a published release),
  found by inspecting its prior release history rather than assumed. The first draft of the notes
  led with implementation detail; the approved version described the same work at outcome altitude
  instead — the concrete evidence behind "release-level altitude."
- **Milestone completion is a deliberate, forward-looking exception.** Every milestone in that
  project's history had actually been left open, including ones where every issue inside was already
  closed — evidence of past practice, not a convention worth preserving. The completion lifecycle
  above (`rules/milestone-completion.md`) was added as an explicit forward-looking decision instead:
  closure requires the human's explicit post-merge authorization and approval, not just zero open
  issues, and it does not wait on the milestone's release to have published first. No existing
  milestone was retroactively closed because of it.
- **A shared milestone branch, confirmed across multiple milestones.** What started as a single data
  point — one branch carrying a whole milestone's work — recurred across enough real milestones
  (naming patterns like `core/multitenancy`, `feat/agents`, `feat/documents`) to confirm as the actual
  convention, not just one milestone's happenstance: `rules/sequencing.md`'s "Branch readiness"
  section. The prefixes themselves are illustrative of "derived from the milestone's nature," not a
  taxonomy to enforce.
- **Backlog hotfixes bypass milestone planning and PR entirely.** A run of Backlog issues worked
  directly on the trunk branch, closed after ordinary implementation/review/verification, with no
  branch decision and no PR — confirming the second, lighter-weight path this skill's two-path
  working lifecycle now models explicitly.
- **Issues close before their milestone's PR merges — by design, not by accident.** Every issue in a
  real milestone closed individually, on the shared branch, well before that branch was ever proposed
  as a PR. A finding from later manual testing on that branch was filed as its own new issue
  referencing the one it was found near, rather than reopening the closed issue — the concrete
  evidence behind `rules/milestone-completion.md`'s "When manual testing finds something."

Where an identifier is needed above, treat it as a placeholder — issue `{xxx}`, PR `{xxx}`, milestone
`{name}`, release `{version}` — the point is the shape of the example, not the specific numbers it
was first observed with.

## Known limitations

Not designed yet, because no real evidence exists to extract from:

- PR creation and description conventions, PR review procedure, and merge strategy (merge commit vs.
  squash vs. rebase-and-merge) — PR creation/review/merge stays entirely human-owned; this skill picks
  up again once the human reports the outcome (a merge) or a manual-testing finding on the branch.
- Deployment triggers.
- What release cadence, if any, applies to Backlog/hotfix work committed directly to the trunk branch
  with no PR — `rules/release.md`'s trigger is the milestone-PR-merge path specifically.
- Whether dependency ordering and activation-safety ordering can actually conflict during commit
  construction — no real work has produced such a case yet, so no precedence rule is defined
  (`rules/verification.md`'s "Relationship to dependency ordering").
- What happens if a milestone's PR is later rejected or materially revised after some of its issues
  already closed — the issues would already be closed per the intentional pre-merge-closure design,
  and neither this skill nor `my-feature-planning` currently owns un-doing that.

The same method that built this skill — watch what actually happens, extract the rule the evidence
actually supports, say so explicitly when the evidence isn't there yet — is how each of those should
get added later. `rules/release.md`'s policy-discovery method should keep growing the same way: a
project with different evidence gets a different mechanism, discovered the same way, not assumed
from this one.
