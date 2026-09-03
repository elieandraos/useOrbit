# Issue & Structural Review

Semantic review and integrity validation are different jobs. Semantic review judges whether an
issue or dependency is *right*; integrity validation checks canonical structure and whether a
rendered body, rendered manifest, or live GitHub state reflects its canonical or approved source.
Different artifacts require different validations; no one surface substitutes for another. Five
surfaces cover this:

1. semantic issue/dependency review;
2. canonical structural integrity;
3. rendered issue-body integrity;
4. rendered manifest integrity;
5. post-mutation live-state validation.

`SKILL.md` owns the two approval gates. This rule owns the validations run around them — it does
not reproduce the pipeline.

## 1. Validation timing

1. **After canonical issue definitions are drafted, and after every revision** — issue quality,
   conditional extensibility-claim validation, dependency quality, canonical structural integrity.
2. **Before the user's full content review** — freshly render each issue body from its canonical
   definition, then run issue-body content integrity.
3. **Immediately before presenting the compact manifest for final approval** — revalidate the
   current canonical set, freshly render the manifest, run rendered-manifest integrity.
4. **Immediately before any issue body is created or edited** — freshly render it from the current
   approved canonical definition, rerun issue-body integrity.
5. **After every GitHub mutation episode** — independently re-fetch the live state, validate it,
   report a compact summary.

## 2. Semantic issue-quality review

Validate that:

- each issue represents one coherent outcome under `rules/sequencing.md`'s decomposition test;
- work is neither fragmented into tasks masquerading as issues nor bundled across independently
  meaningful outcomes;
- Context makes the problem, intended outcome, constraints, boundaries, exclusions, and meaningful
  dependencies clear;
- approved product/architecture decisions are preserved;
- no material unresolved decision needed for developer-ready scope is hidden inside the issue;
- Tasks describe delivery work rather than merely locations;
- Acceptance Criteria, when present, express guarantees and observable outcomes rather than
  restated Tasks;
- Tests, when present, identify behavior-level proof obligations rather than implementation
  recipes;
- verification instructions inside a multi-group issue's Tasks/Tests are proportional to each
  checkpoint's affected surface, not a uniform full-regression instruction copied after every group
  merely for symmetry (`rules/issue-conventions.md` §10);
- every completion requirement (Acceptance Criteria or Tests) is satisfiable at this issue's own
  expected closure boundary under the consuming delivery workflow — not stated as a per-issue gate
  when it can only be proven at a later milestone/PR boundary (`rules/issue-conventions.md` §11);
- non-goals are explicit where accidental scope growth is plausible;
- technical references identify real seams without turning the issue into a code blueprint.

Apply proportionally. A small coherent change needs less supporting text than a cross-cutting or
high-risk outcome, but it receives the same correctness bar.

Route authoring definitions to `rules/issue-conventions.md` and decomposition to
`rules/sequencing.md`; do not restate either file in full.

## 3. Conditional extensibility-claim validation

Run this only when an issue actually makes an extensibility claim, or
`rules/capability-checklist.md` question 12 applies.

Validate the claim against every real adoption seam in the consuming project, considering only
applicable categories such as:

- registration, discovery, or required contracts;
- runtime input/output or dispatch paths;
- user-facing representation and navigation;
- authorization, tenancy, or isolation;
- configuration, generated artifacts, build, or tooling integration.

These are categories to inspect against actual project evidence, not a mandatory architecture
template.

If the claimed extension surface is incomplete: revise the canonical claim, Tasks, or Acceptance
Criteria; add omitted scope where required; use `rules/sequencing.md`'s coherent-outcome test if
the correction may change issue boundaries. Do not automatically create another issue for every
omitted seam. Where a concrete source reference materially supports the claim, include it — do not
require an origin-project example.

## 4. Dependency-quality review

Validate against `rules/sequencing.md`:

- every internal edge represents a real prerequisite;
- preferred order, shared context, milestone membership, or a project delivery convention has not
  become a false edge;
- separately proposed foundation work is independently coherent and provable;
- work that requires a real consumer for proof is bundled appropriately;
- external constraints remain external and do not become fabricated internal issues;
- external constraints do not leave the issue non-developer-ready;
- roots, parallel nodes, and disconnected components are allowed;
- every internal dependency points to an existing canonical issue;
- the graph is acyclic;
- canonical order is topological, with internal dependencies pointing backward.

If a cycle or false edge appears, correct the canonical boundaries or dependency claims through
`rules/sequencing.md` — never "fix" it by imposing arbitrary serial order.

## 5. Canonical structural integrity

Validate the canonical definitions themselves — never a rendered preview:

- declared or expected issue count matches the canonical entries;
- canonical identifiers are unique;
- titles are unique;
- exactly one canonical body exists per issue;
- no complete canonical body is duplicated (shared constraints may legitimately recur where
  standalone understanding requires them);
- no issue's Tasks or Acceptance Criteria are misplaced into another issue;
- every internal dependency resolves to a canonical issue;
- the graph is acyclic;
- canonical order is dependency-safe;
- no canonical definition was reconstructed from an earlier render.

**Member-level closed-set coverage.** When approved scope defines an exhaustive set — through an
inventory, named members, an exact count, or "all/every" wording — validate:

- every in-scope member from that source appears exactly once across the canonical issues, checked
  against the actual members and never inferred from a matching headline total;
- any member the approved scope explicitly defers or excludes remains outside the canonical issue
  set;
- any count claimed for that set reconciles with the canonical issues' actual membership.

This validates fidelity to the membership already established by the approved scope — it does not
require searching for members beyond what that source defines. When the affected set itself isn't
established, resolving it stays `rules/feature-classification.md`'s and the applicable scope
checklist's (`rules/resource-feature-checklist.md`, `rules/capability-checklist.md`) job, or
`rules/discovered-work.md`'s when the origin is a finding; this check must not be used to silently
expand approved scope.

If this check fails, revise the canonical definitions and rerun every semantic or structural check
the change affects.

## 6. Issue-body content integrity

Validates the freshly rendered full body text of one issue — not the canonical collection, and not
the compact manifest.

**References**
- No `#N` represents a canonical planning identifier, plan decision, or other non-GitHub number.
- Before internal issues have real GitHub numbers, use unambiguous hash-free canonical
  identifiers.
- Immediately before creation, internal dependency references have been resolved to the correct
  real `#N` values in dependency-safe creation order.
- Every real `#N` reference exists and is contextually intended.
- Legitimate external issue/PR references are allowed; they need not belong to the current
  milestone.
- Plan sections or other source documents are never load-bearing substitutes for explanation.

**Planning leakage**
- No unresolved placeholder that should have been substituted remains.
- No reference assumes access to the planning conversation, previous draft, or other transient
  context.
- No unexplained canonical-only terminology survives into the final body.

**Self-contained Context**
- Context explains why the issue exists, the intended outcome, relevant constraints, scope,
  exclusions, and meaningful dependencies.
- A dependency link does not replace explaining the behavior this issue relies on when that
  behavior is necessary to understand the dependent issue — without duplicating an entire
  prerequisite issue merely to make this one self-contained.
- For Discovered work (`rules/discovered-work.md`), confirmed facts remain visibly distinct from
  hypotheses or unknowns. An unresolved mechanism appears as an investigation/instrumentation Task
  only when the owning stopping condition permits it.

**Body mechanics**
- Every entry under `## Tasks` is a literal Markdown checkbox.
- Acceptance Criteria are ordinary bullets, never task checkboxes.
- A Tests section appears only when it adds distinct proof obligations, and stays at behavior
  level.
- Technical references do not become an implementation walkthrough.
- The body matches the current canonical definition after authorized substitutions such as real
  dependency numbers.
- Where Context, Tasks, Acceptance Criteria, or Tests present members of a canonical exhaustive set
  (§5's member-level closed-set coverage), the rendered body preserves the exact membership and
  count assigned to that canonical issue — a missing, duplicated, or additional member blocks
  presentation or creation.

**If the check fails:** block presentation or mutation at the applicable stage; report the exact
issue and failing field; revise the canonical definition, never the rendered body directly;
rerender and rerun the check.

## 7. Rendered-manifest integrity

The compact manifest is a fresh, one-way render of the canonical definitions. Before presenting
it, verify:

- canonical issue count equals rendered row count;
- every canonical identifier appears exactly once;
- every canonical title appears exactly once;
- rendered order matches canonical order;
- no canonical issue is missing;
- no extra or duplicated row exists;
- every dependency or metadata value the manifest displays matches the current canonical or
  proposed state;
- where a canonical issue's Tasks, Acceptance Criteria, or Tests represent an approved exhaustive
  set (§5's member-level closed-set coverage), the manifest's stated count for that set matches the
  canonical issues' actual membership — never accepted merely because a headline total matches.

This says nothing about body quality — issue-body integrity owns that.

**If it fails:** do not present the manifest; report the exact missing, duplicated, reordered,
retitled, or mismatched field; regenerate it from canonical definitions; never patch the rendered
table by hand.

## 8. Post-mutation live-state validation

> A successful mutation response or exit code proves only that GitHub accepted the request, not
> that the intended state now exists.

After each related mutation episode:

1. independently re-fetch the affected live resources;
2. compare them with the exact approved target;
3. report a compact result.

Validate one related group of mutations as one episode, not one report per API call. No metadata
field — milestone, labels, or assignee — is mandatory merely because this skill supports it;
validate against whatever was actually approved, including its approved absence.

**By mutation type:**
- **Issue creation** — created count for the episode, titles, bodies, dependency references, and
  each issue's milestone, labels, and assignee match the approved target, including approved
  absence.
- **Issue-body edit** — the fetched live body matches the freshly rendered approved canonical
  definition and passes issue-body integrity.
- **Issue metadata change** — title, milestone, labels, and assignee match the intended complete
  state; unrelated fields remain unchanged.
- **Milestone creation** — title and any approved description match; intended state is correct
  where applicable.
- **Label creation** — name and color match.
- **Later mutation** — apply the same relevant checks; initial creation is not a special
  exemption.

Report compactly: mutation type, affected items, exact result, validation status. Use real `#N`
syntax only for actual GitHub issues or PRs; identify milestones and labels by their own names.

**If validation fails:** do not report success; state the exact resource and field that diverged;
state that GitHub is currently inconsistent with the approved target; identify whether a follow-up
mutation is needed. Never silently hide or normalize a failed mutation.

## Responsibility boundaries

This file owns: semantic issue/dependency review, canonical structural validation, rendered-body
validation, rendered-manifest validation, and post-mutation live-state validation.

It does not own: defining issue syntax or metadata policy (`rules/issue-conventions.md`);
decomposing or sequencing work (`rules/sequencing.md`); investigating raw findings
(`rules/discovered-work.md`); resolving product/design decisions
(`rules/design-reconciliation.md`, `rules/plan-md-input.md`); approval-gate ownership
(`SKILL.md`); implementation review, issue closure, or delivery progression (`my-git-workflow`).
