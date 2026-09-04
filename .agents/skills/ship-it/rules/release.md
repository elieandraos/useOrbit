# Release

## Principle

> A release describes the merged change at release-level altitude. It communicates what shipped and
> why it matters, not a replay of commits, issue titles, or implementation details.

The same altitude relationship holds at every level of this workflow:

- a **commit** explains one implementation decision — *why this diff, on its own terms*
  (`rules/commit-boundaries.md`);
- a **PR** is the integrated change being merged — what the reviewer is approving as a whole;
- a **release** is the shipped outcome — what a user, operator, or downstream project actually gets,
  and why it's worth noting.

Each level is a summary of the level below it, not a concatenation of it. A release that just
stitches together commit messages or PR titles has skipped the summarizing step this rule exists to
perform.

## Where this phase starts

```
PR merged → human confirms it merged → STOP: authorization to begin the post-merge phase
  → discover release policy → understand release → draft → human approval → publish
  → re-fetch and validate
```

The same authorization also opens `rules/milestone-completion.md`'s closure gate for a milestone
issue. The two branches proceed independently from there — neither this rule's publication nor that
rule's closure is a precondition for the other; see that rule's "Milestone closure and release do not
gate each other."

- This phase starts only once a PR has been **successfully merged** — not when implementation
  finishes, and not when the last commit lands. A merged PR is the trigger; nothing earlier in the
  lifecycle is. This describes the milestone-PR path specifically; a Backlog/hotfix issue worked
  directly on the trunk branch (`rules/sequencing.md`'s "Branch readiness") has no PR to merge, and
  what release cadence, if any, applies to that work is not designed by this rule — there's no
  evidence yet to extract a rule from.
- **PR creation and merge strategy are not owned by this rule.** However a PR came to be merged is
  out of scope here — this rule picks up from "a PR merged," full stop. For a milestone issue, the
  observed convention is that its PR references the milestone it integrates — see
  `rules/milestone-completion.md`'s "The milestone-PR reference convention"; this rule has no PR-
  content requirements of its own.
- **The human's explicit confirmation that the PR merged is the integration gate.** Before that
  confirmation, the human is expected to have already waited on CI/CD checks, confirmed there's no
  merge conflict, reviewed the diff again, and merged. This rule does not independently re-fetch or
  re-verify the merge state once the human says it happened — that would re-litigate a human gate
  this rule doesn't own, not add a genuine check.

## 0. Confirm authorization before beginning

Merge confirmation is necessary to start this phase, but it is not by itself authorization to start
it. Once the human confirms the PR merged, stop and ask for explicit authorization to begin the
post-merge progression — which may include drafting and publishing a release (this rule) and, for a
milestone issue, closing the milestone (`rules/milestone-completion.md`'s closure gate). A single
question can cover both, e.g. "close the milestone and start the release?" — but they are separate
mutations, and completing one is never a precondition for starting the other (see
`rules/milestone-completion.md`'s "Milestone closure and release do not gate each other"). Do not
begin policy discovery below until this authorization is explicit.

This is a separate, earlier gate from step 4's approval of the exact release content, the same way
Gate 1 and Gate 2 in `rules/review-gates.md` are separate: "may I start this phase at all" is not the
same question as "is this exact version/title/body correct." Do not treat "the PR merged" as an
implicit green light to start drafting, and do not treat authorization to start as approval of what
gets published.

## 1. Discover the release policy before proposing anything

Do not assume a versioning scheme, a release mechanism, a tag type, or a hosting platform. Determine
what this repository actually does, in this order:

1. **Look for an explicit policy first.** A documented versioning/release policy, changelog-tooling
   configuration, a `VERSION` file, semantic-release config, or anything else the repository states
   outright. Concrete places to check:
   - a documented release/versioning policy (in a `CONTRIBUTING`, `RELEASING`, or similar file);
   - version-tracking files (`VERSION`, a `version` field in a package manifest, etc.);
   - changelog or release-automation tooling configuration;
   - existing tags and their naming pattern;
   - hosted releases (e.g., GitHub Releases) and their structure;
   - published package versions, where the project ships one;
   - release-automation workflows (CI/CD configuration that tags, publishes, or drafts releases);
   - any other artifact the repository itself owns and uses to describe its release process.

   If an explicit policy exists, it wins — do not override a stated policy with something inferred
   from history, even if history looks inconsistent with the stated policy. A stated policy and messy
   history disagreeing is itself worth flagging to the human, not silently resolving in history's
   favor.
2. **Otherwise, infer from the repository's established release artifacts and history.** Tags,
   hosted releases, changelog files, package-registry versions — whatever the repository actually
   has. Read enough of it to name the pattern with evidence, not guess at one after skimming two
   examples.
3. **If the evidence is ambiguous or conflicting** — no policy file, thin or inconsistent history,
   two competing conventions, or no history at all — stop and ask the human rather than inventing
   one. This is the same "genuine unknown" stop `rules/review-gates.md` applies elsewhere in this
   skill, extended here to release policy: a missing decision does not get invented and presented as
   fact.

Explicit, repository-stated policy always outranks a pattern inferred from history.

## 2. Understand the release being made

Before drafting anything, determine the release's primary theme and the meaningful outcomes it
bundles — not by counting commits, files, or lines changed.

> Release size is not a versioning rule. Line count, commit count, and file count decide nothing
> about version importance; the project's discovered release policy does.

The discovered policy (step 1) determines *version classification* — what makes something a major,
minor, or patch release, or whatever scheme the project actually uses. The *actual merged outcomes*
determine the release's theme and the content of its notes. These are two separate questions,
answered from two separate sources of evidence — don't let one substitute for the other.

A release may represent, among other shapes:

| Shape | What it communicates |
|---|---|
| Feature/capability | A new user- or operator-facing capability now exists. |
| Infrastructure/architecture | The system's internal structure changed in a way that matters beyond this one change. |
| Hardening/reliability | Something that already worked now fails less often or more safely. |
| Maintenance/UX polish | Small, real improvements with no single headline feature. |
| Upgrade work | A dependency, runtime, or platform version moved, with consequences worth noting. |
| Simplification/refactor | Code got smaller or clearer without changing what it does — or removed a risk while doing so. |
| Multi-area | Several of the above, shipped together under one primary theme because they merged as one unit of work. |

These are conceptual shapes to recognize a release by, not a fixed taxonomy to force every release
into. A release can legitimately bundle several related areas even when it has one primary theme in
its title — that's a property of what actually merged, not a rule to avoid.

## 3. Draft release notes at release-level altitude

Release notes describe what the release delivers, not how the code implements it. Group outcomes by
meaningful area — not by commit, not by file — and include infrastructure/technical detail only when
it carries real architectural, operational, reliability, or future-capability significance to a
reader, not just because it happened to land in the same PR.

Do not replay commit messages or issue titles verbatim, and do not default to implementation
vocabulary when a plainer description of the outcome communicates the same fact. For example:

> **Too low-level:** "Added a new authentication middleware that checks a `verified` claim before
> the route handler runs, backed by a new `sessions` table and a background job that expires stale
> rows."
>
> **Release-level:** "Sessions now expire automatically, and access is blocked the moment a session
> is no longer valid — closing a gap where a revoked session could still be used until it aged out on
> its own."

Same underlying change, described at the altitude a release reads at rather than the altitude a diff
reads at. Technical language earns its place only when the technical fact *is* the outcome that
matters (e.g., "requires PostgreSQL 15 or later" is release-level, not implementation detail, because
it's something an operator must act on).

The release title identifies the version plus its primary theme, in whatever syntax the project's
discovered convention (step 1) actually uses. Don't invent a title format the repository's own
history and policy give no evidence for.

## 4. Human approval before publication

Before any publication mutation, present all of the following together and stop:

- the proposed version;
- the tag target (which commit);
- the release title;
- the complete release body.

This mirrors the two pre-merge review gates (`rules/review-gates.md`) and step 0 above: approval of
the release *content* is not implicit in the merge having happened, and is not implicit in the step-0
authorization to begin either — a **merged PR is not publication approval, and authorization to start
drafting is not approval of what got drafted**. Approving one wording tweak is not the same as
pre-approving everything else.

If the human requests any change — to wording, version, tag target, or title — revise and confirm
the *exact final content* before running any publish command. Do not tag or publish anything before
this approval is explicit and covers the version actually about to be published.

## 5. Publish using the project's discovered mechanism

Use whatever release mechanism step 1 actually discovered — don't default to git tags, GitHub
Releases, or any other specific tooling absent evidence for this project. Preserve the established
tag/release semantics (tag type, what commit it targets, draft vs. published, prerelease flag) unless
the human explicitly asks for something different this time.

Do not add deployment triggers, rollback machinery, prerelease channels, or changelog automation the
repository shows no evidence of wanting — that is inventing release machinery, exactly what this step
exists to avoid. Git and GitHub remain intentional core substrate for this workflow, but that does
not mean every repository publishes releases through the same tag/release command sequence; the
mechanism is discovered per repository, not assumed.

For illustration only — not a default to apply unless step 1 actually finds this pattern in a given
repository:

```
# Illustrative only. The discovered mechanism (step 1) is authoritative.
git tag <version> <target-commit-sha>
git push origin <version>
gh release create <version> --title "<title>" --target <branch> --notes-file <approved-body>
```

## 6. Post-publication validation

> A publish command's successful exit code is not proof anything actually landed correctly.

This is the same discipline `rules/issue-closure.md` applies to every GitHub mutation in this skill,
extended here to releases. Re-fetch the tag and release from the source of truth discovered in step
1, and validate at minimum:

- version/tag name;
- tag target commit (does it point at the commit approved in step 4?);
- release title;
- release body (matches the approved draft, including any last-minute wording change);
- published/draft/prerelease state, wherever the mechanism has one.

A mismatch on any field is a failed validation to report and fix, not a cosmetic discrepancy to gloss
over because the publish command didn't error.

Report the result compactly — what was created, and a field-by-field confirmation — not a re-print of
the whole release body.

## What this rule does not do

- **It does not close issues.** That already happened, per issue, in `rules/issue-closure.md`,
  before the PR was even opened.
- **It does not decide whether or how a PR gets created or merged.** This rule starts from "a PR
  merged," however that happened, and has no opinion on PR creation or merge strategy.
- **It does not independently re-verify the merge state.** The human's confirmation that the PR
  merged is the integration gate; whatever CI/conflict/diff checks the human performed before saying
  so are theirs, not this rule's to redo.
- **It does not invent deployment, rollback, prerelease-channel, or changelog-automation behavior**
  beyond what step 1 actually found evidence for.
- **It does not decide milestone closure, and does not gate it.** `rules/milestone-completion.md`'s
  closure gate starts from the same post-merge authorization this rule's step 0 asks for, not from
  this rule's publication having completed — see that rule's "Milestone closure and release do not
  gate each other." A milestone can close before, after, or without regard to the timing of this
  rule's release.

## Do / Don't

**Do**
- Ask for explicit authorization to begin the post-merge progression — covering both this rule's
  release phase and, for milestone work, `rules/milestone-completion.md`'s closure gate — once the
  human confirms the PR merged, before starting policy discovery.
- Discover the release policy before proposing a version, title, or publication mechanism.
- Let explicit repository policy override inferred history when they disagree.
- Stop and ask when the evidence for release policy is ambiguous or conflicting.
- Draft notes at release-level altitude, grouped by meaningful outcome area.
- Present version, tag target, title, and full body together, and get explicit approval before
  publishing.
- Publish through the mechanism discovered in step 1, preserving its established semantics.
- Re-fetch and validate every required field after publication.
- Report results compactly rather than re-printing the release body.

**Don't**
- Treat the human's merge confirmation as authorization to start drafting, or independently
  re-verify the merge state.
- Treat this rule's publication as something milestone closure must wait for, or vice versa.
- Infer version importance from diff size, commit count, or file count.
- Replay commit messages or issue titles as release notes.
- Assume a specific tag/release command sequence without discovery evidence for this repository.
- Publish before the human has approved the exact final version, target, title, and body.
- Trust a publish command's exit code as proof of the resulting state.
- Invent deployment, rollback, prerelease, or changelog automation the repository shows no evidence
  of wanting.
