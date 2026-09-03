# Cross-Cutting Capability Checklist

Load this when `rules/feature-classification.md` says **B (cross-cutting capability)** — resolve
every question below explicitly, applicable or not. For **capability-shaped C**, use only the
questions the changed slice actually affects — never replan the whole existing capability for one
changed slice. For **D (architectural/refactor milestone)**, use it loosely: outcome, boundaries,
preserved behavior, exclusions, dependencies, and proof matter; skip questions asking about a
product capability's shape when there isn't one.

The capability is organized around behavior or infrastructure spanning multiple existing domains,
rather than the lifecycle of one first-class resource. It may persist its own state, even own
tables, and still belong here — concrete persistence or implementation artifacts don't determine the
shape; the organizing responsibility does. This is not architecture documentation; its only job is
to produce correct canonical scope and dependency ordering.

Resolve each explicitly — mark "not applicable" outright rather than silently dropping a question
because it's inconvenient to answer:

1. **What problem/capability is being introduced?** One or two sentences: what's missing today, and
   what observable outcome should exist once this ships.
2. **What shared foundation is needed, and does it need its own issue?** First identify the thing
   every other piece builds on. Then decide the split by this rule, not by default:
   - If the foundation is a coherent, independently provable outcome on its own — landable and
     verifiable with no consumer exercising it — it can be its own issue.
   - If it can't actually be proven correct without a real consumer exercising it, bundle the
     smallest meaningful consumer into the same issue rather than shipping untested scaffolding.
   - Don't create "foundation with no consumer" just because separating it sounds architecturally
     neat — let dependency structure and provability decide. Don't presume a fixed issue count.
3. **Which existing domains produce, consume, or are affected by it?** Name the actual domains and
   what each contributes or receives — not "various parts of the system."
4. **What are the key runtime paths?** Where is this triggered from, how does it move through the
   system, and where does its outcome become observable?
5. **What security, authorization, tenancy, or isolation boundaries matter?** Name the actual
   invariant the consuming project enforces, not just "add auth."
6. **Which business events or integration points need wiring?** Enumerate them concretely (a named
   set of lifecycle transitions, not "various events").
7. **What established contracts or mechanisms can be reused system-side?** An existing pattern,
   authorization rule, or data representation the project already has, instead of a new one.
8. **What established patterns can be reused on user-facing surfaces?** An existing interaction or
   presentation pattern the project already has, instead of a new one.
9. **What user-visible integrations sit on top, if any?** Where does this surface to a user, and on
   which existing surfaces? Not applicable for infrastructure-only work.
10. **Which existing pieces need collateral changes?** Renames, signature changes, or cleanups that
    ride along because they're now inconsistent with the new work — record them distinctly in
    canonical scope rather than folding them silently into unrelated work. Whether each becomes its
    own issue is a later, outcome- and dependency-driven decision, not decided here.
11. **What is explicitly out of scope?** State it, don't leave it implicit.
12. **Do future extension seams need to be checked at all?** Conditional — only ask when at least
    one of these is true:
    - Extensibility is explicitly part of the stated goal.
    - The design already introduces an explicit extension point (a registry, a strategy interface,
      a config-driven adapter list).
    - An issue or its acceptance criteria claims future adopters can join through a minimal,
      enumerated surface — that claim needs verifying (see `rules/review.md`'s extensibility-claim
      validation).
    Otherwise skip it — don't invent a hypothetical future requirement just to fill out the
    checklist. When it applies, name the seam and confirm it already exists in the current design
    rather than proposing new work.
13. **What needs explicit proof?** Name the outcomes, invariants, runtime paths, and
    failure/security boundaries from the answers above that need verification — tests or another
    form — before this is trustworthy.

Answers become canonical scope and dependency information, not a fixed issue template — issue
decomposition follows coherent outcomes, dependencies, and independent provability
(`rules/sequencing.md`). Question numbers never map mechanically to issue numbers or batches.
