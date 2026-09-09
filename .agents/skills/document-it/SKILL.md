---
name: document-it
description: "Creates, updates, and reviews explanatory architecture guides in Markdown, a Claude Artifact, or both — reusing sufficient, current, verified understanding already available, and routing to lab-it's investigation method only when evidence is missing or stale. Trigger to document existing architecture, update an existing guide, or review a guide's completeness. Not for debugging or reviewing a diff. Not for investigating a system from scratch, resolving architecture decisions for a proposed feature, or synthesizing plan.md — those stay with lab-it."
---

# document-it

## What this skill does

This skill creates, maintains, and reviews explanatory architecture guides — in Markdown, a Claude
Artifact, or both — from already-available, sufficient, and current understanding. It is
independently callable: a documentation request does not require a fresh investigation to begin.

| User intention                                    | Result                                         |
| --------------------------------------------------- | ------------------------------------------------ |
| Document existing architecture                     | A new guide (Markdown, Artifact, or both)        |
| Reconcile a guide with verified changed reality    | An updated existing guide                        |
| Evaluate whether a guide is complete               | A findings report, not a rewrite                 |

## Evidence: reuse first, route to `lab-it` only when needed

Before writing or updating anything, establish whether currently available understanding is
sufficient and current:

- **Sufficient, current evidence already exists** — from this conversation, a recent
  investigation, or evidence directly verifiable against the real system right now. Use it
  directly. Don't repeat a full investigation just because a documentation workflow is starting.
- **Evidence is missing or stale.** Route the relevant investigation through `lab-it`'s canonical
  investigation method (its "Shared investigation and decision discipline") rather than
  duplicating that method here. Once `lab-it` returns verified findings, resume the documentation
  workflow.

This distinction determines the entry point, not the destination: the result is always a guide, or
a review of one — never a `plan.md`. An approved `plan.md` stays `lab-it`'s output regardless of
which skill performed the underlying investigation; file extension does not decide ownership.

## Format selection

For a **new guide**, ask the user whether they want an Artifact, Markdown, or both — unless
they've already specified. Don't infer the format from context. See `rules/authoring.md` for the
Artifact/Markdown mechanics, the `docs/` location default, and how to handle unavailable
publishing capability.

## Document existing architecture

Triggered by a user request for a new guide — an existing guide for this capability does not
block an explicit request for another; that's a separate, deliberate choice the user is entitled
to make, not an error condition. Route: establish sufficient current evidence (reuse, or route to
`lab-it`) → recap and obtain confirmation → decide structure from the architectural center of
gravity → write in the confirmed format(s) → publish/save → run `rules/review.md`. See
`rules/authoring.md` for the full procedure, including the mandatory recap-and-confirm gate before
anything is published or saved.

## Update an existing guide

Use this workflow when a published guide needs reconciling with verified current reality —
triggered by a stale architectural claim, a stale evidence reference, changed configuration or
runtime behavior, or a prior documentation defect, not only a changed implementation. See
`rules/authoring.md` for locating the target, the three missing/inaccessible/unavailable-tooling
situations, and identity preservation. `rules/maintenance.md` governs every judgment call once the
target is confirmed reachable — read it before making any edit.

## Maintaining both formats

When a guide is maintained in both Markdown and Artifact:

- **Keep architectural claims synchronized** across both on an update, unless the user explicitly
  requests otherwise for that update.
- **Format-appropriate presentation, not identical rendering.** Use `rules/review.md` for both —
  one shared checklist, with a handful of items that apply to only one medium (favicon/URL checks
  apply to Artifact only; a link-integrity/structure check applies to Markdown only).
- **Keep the association between the two outputs discoverable** through a minimal cross-reference
  each carries to the other's location — not a registry, and nothing beyond that is required.
- **Report precisely which outputs changed**, and name any remaining divergence, rather than
  reporting a single "done." State the reason, and distinguish two different cases: a publication
  failure (e.g., the Markdown update succeeds but the Artifact can't be reached) versus the user
  explicitly limiting this update to one format. The second is authorized divergence, not a
  defect — report which output changed, which the user chose to leave as-is, and don't claim the
  untouched one is current.

## Review a guide

Run `rules/review.md` against the actual published or saved guide — Artifact, Markdown, or both —
any time you're asked to evaluate whether a guide is complete or still architecturally misleading,
independent of whether you also just wrote or updated it. See `rules/review.md` for the full
checklist and its medium-conditional items.

## Ownership and handoff

This skill owns:

- creating new architecture guides, in Markdown, Artifact, or both;
- maintaining and reconciling existing guides with verified current reality;
- reviewing a guide for architectural completeness.

This skill does not own:

- investigation from scratch when understanding is missing or stale (→ `lab-it`);
- architecture decisions reached with the user, or `plan.md` synthesis (→ `lab-it` — file
  extension does not decide ownership: an approved `plan.md` stays `lab-it`'s even though it is a
  `.md` file);
- application implementation;
- debugging or diff review;
- API reference documentation;
- feature classification and issue decomposition;
- GitHub issue mutation;
- delivery sequencing;
- Git workflow.

## Rule and supporting-file routing

These are loaded only when their workflow needs them — none is a universal prerequisite:

- format selection, new-guide creation, existing-guide update → `rules/authoring.md`
- guide writing (Markdown and Artifact) → `rules/doc-style.md`
- Artifact scaffold → `rules/template.html`
- guide review → `rules/review.md`
- guide maintenance → `rules/maintenance.md`

## Output-specific non-negotiables

- Explain architecture, not implementation: why it exists, why it's shaped that way, and — where
  extension is relevant — how it can be extended. Code blocks exist only at genuine extension
  seams, never a walkthrough of a whole method body.
- When a real line exists between reusable infrastructure and integration-specific code, make it
  explicit in whatever structure the guide already uses — never a mandated section or table.
- Explain runtime ownership and lifecycle wherever the system has either — don't invent one for a
  capability with no runtime story.
- Ground material architectural claims in concrete evidence or enforcement references when an
  identifiable mechanism exists; never force a misleading one.
- No API documentation, no endpoint inventories, no line-by-line implementation walkthroughs, no
  duplicated explanations across sections.
- Not every guide needs a limitations section. When limitations or deferred work materially affect
  understanding, state the reason rather than adding a generic TODO list.
- A guide is never done until it has passed a `rules/review.md` pass — writing and reviewing are
  two separate steps.
