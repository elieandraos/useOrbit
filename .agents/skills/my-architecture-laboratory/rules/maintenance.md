# Maintaining an architecture guide

This file governs `SKILL.md`'s "Update an existing architecture guide" workflow: how to reconcile
a published architecture guide with verified current reality without breaking the identity and
architectural meaning it already carries. `rules/review.md` judges whether a finished guide
communicates its architecture; this file governs what you're allowed to touch when a guide already
does, so a maintenance pass doesn't quietly undo that.

Maintenance fails in two opposite directions: leaving stale documentation in place for the sake of
continuity, and rewriting or restructuring the guide beyond what the verified change actually
requires. Both are defects this file exists to prevent.

## The maintenance unit: architectural claims

Treat every statement the guide makes as an **architectural claim** — a center of gravity, a
responsibility or ownership, a boundary, an invariant or guarantee, a lifecycle or state, an
integration or extension seam, a decision and its trade-off, a limitation or deferred item, or a
concrete evidence reference. A claim, not the section, table, or paragraph carrying it, is the unit
of maintenance — the same claim routinely repeats across several of those.

## Reconcile before editing

Locate the existing Artifact rather than assuming its content. Compare its claims against verified
current implementation, configuration, runtime evidence, and relevant tests — the same evidence
discipline `SKILL.md` requires before any investigation-based output; don't repeat that workflow
here, use it.

Classify what you find:

1. **No documentation event** — neither a claim nor its evidence reference changed. Leave the guide
   alone; not every commit is a documentation event.
2. **A narrow update** — one fact or evidence reference is now wrong, with no architectural shift
   behind it. Correct it in place.
3. **A connected architectural change** — several claims or structural elements depend on each
   other and must move together (see "Follow the claim graph," below).
4. **Unresolved authority, intent, or a material decision** — stop. Return it to the user through
   `SKILL.md`'s investigation and decision discipline rather than deciding it during maintenance.
   Maintenance updates a guide to match an already-resolved reality; it does not resolve product or
   architecture questions itself.

## Preserve continuity without freezing the guide

Preserve every unaffected claim, explanation, section, and presentation choice. Don't rewrite
nearby prose merely because the file is already open, and don't restructure for taste, tidiness, or
conformity with another guide's shape.

Continuity is not the same as freezing the guide's center of gravity, ownership, boundaries, or
reasoning in place. If verified architecture actually changed one of those, the guide must change
with it: a strengthened guarantee updates exactly where it already lives, but a moved
responsibility, a new center of gravity, or a shifted boundary requires restructuring that section,
a connected set of sections, or — rarely — the whole guide, in proportion to what actually changed.
A prior documentation defect — a claim that was always misplaced or wrong, independent of any new
implementation change — can also justify moving or restructuring content.

Preserve the Artifact's identity. A maintenance pass evolves the existing guide; it does not mint a
new one.

## Follow the claim graph, not the triggering issue

An issue, PR, or request names the trigger, not the documentation scope. Once a changed claim is
identified, find every place that depends on or repeats it, wherever the guide actually has them:
hero or introductory framing, navigation or section labels, prose, diagrams, tables, counts or
spec-strip facts, source references, decisions and trade-offs, limitations or deferred work, and
closing material. None of these is mandatory in every guide — update whichever the guide actually
uses to state the claim, forming the smallest complete connected set, not just the section the
issue happened to name.

## Implementation-only changes

Not every implementation change requires a guide edit — but treat the guide as unaffected only when
both hold:

- the architecture the guide communicates remains accurate;
- every concrete evidence reference, path, symbol, configuration key, count, or other durable fact
  the guide prints remains valid.

A refactor with no architectural effect can still invalidate a printed reference. When it does, the
guide needs an edit even though the architecture itself didn't move.

## Keep deferred work current

Wherever the guide records limitations or deferred work, it must describe current reality:

- remove an item once it's completed — don't strike it through or leave it as a note;
- update whatever claim the completed work actually changed, using the rules above;
- don't retain completed work as release notes.

No section is required for this, and none should be added to hold a single completed-item note. A
guide with no materially useful limitations doesn't need one.

## Present-tense architecture, not changelog

Describe the architecture as it works now — write it the way you'd write it if documenting the
system for the first time today. Don't narrate commits, pull requests, or before/after
implementation history.

This doesn't erase rationale. When a past decision or a rejected alternative materially explains
why the current architecture is shaped the way it is, preserve that reasoning as durable
architectural content, not chronology. Omit only the parts that serve solely as release history.

## Redeployment and review

Before editing: locate the existing Artifact, note its current `url`, and discover its current
favicon — read the published Artifact, or ask the user if you can't tell — per
`rules/doc-style.md`. Don't silently mint a replacement.

After editing: redeploy through the `Artifact` tool with the same `url` and the same favicon, then
run `rules/review.md` against the whole updated guide, not only the sections you touched,
paying particular attention to the claims you changed and whatever depends on them. Confirm the
changes don't contradict untouched material elsewhere in the guide.

## Sequence

1. Locate the existing Artifact and gather current evidence.
2. State the changed claim or evidence reference in one sentence.
3. Classify it (see "Reconcile before editing") and determine the complete connected scope.
4. Stop for unresolved authority or a material decision — route it to the user through `SKILL.md`.
5. Update only the affected claim graph.
6. Preserve unaffected narrative and the Artifact's identity.
7. Remove stale deferred-work claims and update whatever they affected.
8. Reread the edit as present-tense architecture, not release notes.
9. Redeploy the same Artifact — same `url`, same favicon.
10. Run `rules/review.md` against the whole updated guide.
