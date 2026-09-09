# Maintaining an architecture guide

This file governs the "Update an existing guide" workflow — triggered from `SKILL.md`, with its
locating, situation-handling, and identity-preservation mechanics in `rules/authoring.md` — for how
to reconcile a published architecture guide — Markdown, Artifact, or both — with verified current
reality without breaking the identity and architectural meaning it already carries. `rules/review.md`
judges whether a finished guide communicates its architecture; this file governs what you're allowed
to touch when a guide already does, so a maintenance pass doesn't quietly undo that.

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

Locate the existing guide rather than assuming its content. Compare its claims against verified
current implementation, configuration, runtime evidence, and relevant tests — the same evidence
discipline `SKILL.md` requires before any documentation output: reuse what's already sufficient
and current, and route missing or stale evidence through `lab-it` rather than repeating its
investigation method here.

Classify what you find:

1. **No documentation event** — neither a claim nor its evidence reference changed. Leave the guide
   alone; not every commit is a documentation event.
2. **A narrow update** — one fact or evidence reference is now wrong, with no architectural shift
   behind it. Correct it in place.
3. **A connected architectural change** — several claims or structural elements depend on each
   other and must move together (see "Follow the claim graph," below).
4. **Unresolved authority, intent, or a material decision** — stop. Return it to the user rather
   than deciding it during maintenance. Maintenance updates a guide to match an already-resolved
   reality; it does not resolve product or architecture questions itself. A decision that requires
   fresh investigation or an architecture decision with the user routes through `lab-it`.

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

**Preserve the guide's identity by default.** A maintenance pass evolves the existing guide; it
does not mint a new one:

- **Markdown** — the same file path. Preserve unaffected front matter and title, and any other
  metadata not implicated by the update, while allowing the changes the requested update actually
  requires. Markdown metadata is not treated as universally immutable the way an Artifact's `url`
  and favicon are — preserve what the update doesn't touch, and change only what it must.
- **Artifact** — the same `url` and the same favicon, with equal weight: neither changes as a side
  effect of an ordinary content update.

## Follow the claim graph, not the triggering issue

An issue, PR, or request names the trigger, not the documentation scope. Once a changed claim is
identified, find every place that depends on or repeats it, wherever the guide actually has them:
hero or introductory framing, navigation or section labels, prose, diagrams, tables, counts or
spec-strip facts, source references, decisions and trade-offs, limitations or deferred work, and
closing material. None of these is mandatory in every guide — update whichever the guide actually
uses to state the claim, forming the smallest complete connected set, not just the section the
issue happened to name. When both formats are maintained, the claim graph spans both by default —
a claim changed in one carries the same obligation in the other, per `SKILL.md`'s "Maintaining
both formats." When the user explicitly limits this update to one format, update only that
format's claim graph, and record the resulting divergence in the other rather than leaving it
unstated.

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

## Redeployment, resaving, and review

Before editing: locate the existing guide. For an **Artifact**, note its current `url` and
discover its current favicon — read the published Artifact, or ask the user if you can't tell —
per `rules/doc-style.md`. For **Markdown**, confirm its current file path and note the front
matter or title metadata the update must preserve. Don't silently mint a replacement in either
format.

After editing: for an **Artifact**, redeploy through the `Artifact` tool with the same `url` and
the same favicon. For **Markdown**, save to the same file path, preserving unaffected metadata.
Then run `rules/review.md` against the whole updated guide — every maintained format, not only the
one edited first, confirming that a format deliberately left unupdated by the user's request has
its resulting divergence accurately reported rather than left unstated or presented as current —
not only the sections you touched, paying particular attention to the claims you changed and
whatever depends on them. Confirm the changes don't contradict untouched material elsewhere in the
guide.

## Sequence

1. Locate the existing guide and gather current evidence — reuse what's sufficient and current;
   route through `lab-it` when it's missing or stale.
2. State the changed claim or evidence reference in one sentence.
3. Classify it (see "Reconcile before editing") and determine the complete connected scope.
4. Stop for unresolved authority or a material decision — route it to the user, or to `lab-it` when
   it requires fresh investigation or an architecture decision.
5. Update only the affected claim graph — in every maintained format by default, or only the
   format the user explicitly authorized, recording the resulting divergence in the other.
6. Preserve unaffected narrative and the guide's identity.
7. Remove stale deferred-work claims and update whatever they affected.
8. Reread the edit as present-tense architecture, not release notes.
9. Redeploy or resave the guide, preserving its identity — same `url` and favicon for Artifact;
   same file path, with unaffected metadata preserved, for Markdown.
10. Run `rules/review.md` against the whole updated guide.
