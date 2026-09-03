# Sequencing

## What this rule owns

This rule owns two connected decisions: decomposing canonical scope into coherent issues, and
connecting and ordering them by real prerequisites. It does not decide implementation order, which
ready issue gets worked next, branch strategy, or commit structure — those belong to
`my-git-workflow`, particularly its own `rules/sequencing.md`.

## Decompose canonical scope into coherent issues

Every classification shape — new resource, cross-cutting capability, extension, or refactor —
decomposes the same way: by coherent outcome, real dependency, and independent provability. Checklist
answers supply scope, not a mechanical map to issues — no question number determines issue count.

Each issue should represent one coherent outcome that's independently understandable and, where
practical, independently landable and provable. Split work when:

- the parts produce independently meaningful outcomes;
- they have materially different prerequisite chains;
- one can be completed and verified without the other;
- combining them would obscure distinct acceptance criteria, risk boundaries, or ownership.

Bundle work when:

- the pieces have no meaningful or provable outcome apart from each other;
- a foundation can't be trusted without a real consumer exercising it — per
  `rules/capability-checklist.md` question 2, bundle the smallest consumer needed unless the foundation
  is independently provable; never separate one just because it looks architecturally tidy;
- splitting would create unused scaffolding;
- the smallest coherent deliverable crosses more than one implementation area.

Do not split mechanically — by checklist question/track, file/class/route/component/layer, system-side
vs. user-facing work, CRUD artifact, fixed count, or an earlier feature's structure.

Separate verification is not sufficient reason to create a separate issue. Split work only when it
also produces a durable, independently useful outcome or must land separately because of a real
dependency. Treat baselines, upgrade tranches, migration stages, and verification checkpoints as Tasks
or Tests within their coherent issue unless they pass that test.

Collateral work is recorded distinctly in canonical scope (`rules/capability-checklist.md` question
10); whether it becomes its own issue follows this same test, not an automatic default.

## Define a real dependency

Issue B depends on issue A only when A's outcome must exist before B can be safely completed, landed,
or verified — never a preferred order, shared subject matter, milestone membership, drafting order, an
implementation-layer convention, or a wish to share context. If B can proceed independently against a
stable, approved contract, don't invent a dependency to serialize it.

An external prerequisite this issue set doesn't own — another team's deliverable, a third-party
dependency — may be recorded as an external constraint when the issue remains developer-ready. A
material unresolved product/architecture decision is not an external constraint: it routes through the
applicable owning decision gate and blocks the affected drafting. Separately, an open implementation
detail may remain unresolved only when every viable option preserves the approved guarantees and the
issue remains executable, per `rules/plan-md-input.md`. Never fabricate an internal issue merely to
represent an external prerequisite.

## Build and validate the dependency graph

Each canonical issue is a node; each real prerequisite is a directed edge. The result must satisfy:

- every internal dependency points to another canonical issue;
- root issues have no prerequisites;
- the graph is acyclic, with unambiguous dependency direction;
- a dependent issue's body summarizes any relied-upon behavior a reader needs for standalone
  understanding, per `rules/issue-conventions.md`.

A cycle means the boundaries or a dependency claim are wrong — resolve it by combining inseparable work
or correcting the false dependency. Never accept a cycle, or break one with an arbitrary ordering
decision instead of fixing the cause.

## Preserve parallel-ready work

A dependency graph is a partial order, not one total implementation sequence. Represent it as waves:
roots have no internal prerequisites, and a later wave's nodes have their prerequisites in an earlier
wave — exposing possible parallelism, not claiming an issue is currently authorized or ready to
implement. Present and create issues in that stable topological order, so every dependency reference
points backward, as an operational convenience — not a claim that same-wave issues must be implemented
serially. Ask the user to choose between equivalent topological orders only when it would materially
change scope, boundaries, or risk. Live readiness, from actual issue closure, and the choice of which
issue to implement next, are both `my-git-workflow`'s job after creation — not this rule's.

## Project-supplied delivery constraints

A consuming project may supply a documented or approved sequencing convention. Treat it as project
input, not methodology: identify it as project-supplied, decide whether it creates a real prerequisite
or only a preferred order, encode only a real prerequisite as a dependency edge, and record a
non-dependency preference separately rather than fold it in as a false edge. Ask the user only when
applying it would materially change scope or boundaries and its meaning is genuinely unclear.

This rule prescribes no fixed implementation-layer order — vertical slices, parallel work
against a stable contract, interface-first delivery, or another approved model are all compatible with
it.

## Planned and Discovered work use one method

Origin doesn't determine issue count or order. A validated Discovered finding
(`rules/discovered-work.md`) is decomposed from its own actual scope. Scope membership and dependency
edges are separate decisions — a canonical graph may contain parallel roots or disconnected components,
so belonging to an initiative never requires an edge to every issue in it. Membership follows actual
scope alone, never merely when or where the finding surfaced; if it belongs, merge it into an existing
canonical issue or add a distinct one via the coherent-outcome test above, adding a dependency edge only
where a real prerequisite exists; if it doesn't belong, it stays outside that canonical scope for normal
milestone/metadata placement. A finding may resolve to one issue or reveal several; the same
decomposition test decides which.

## Dependency-safe GitHub creation

Before creation, canonical definitions may reference each other using canonical planning identifiers.
The complete set must pass `rules/review.md`'s applicable validations, then `SKILL.md`'s approval
gates, before creation in dependency-safe order — nothing is created merely because it has no internal
prerequisites or appears in an earlier wave.

Create issues in the stable topological order: capture each created issue's real GitHub number as it's
created, resolve a dependent issue's canonical references to real `#N`s before creating it,
and never derive the issue set back from created issues or a rendered preview.

## Handoff

`rules/review.md` owns issue quality, dependency-quality validation, structural/rendered integrity, and
mutation validation. `SKILL.md` owns the two approval surfaces and creation pipeline. Once approved
issues are created, `my-git-workflow` owns branch readiness, the next ready issue, and recomputing the
live dependency-ready set as issues close.
