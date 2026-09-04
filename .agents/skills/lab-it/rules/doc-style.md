# Documentation style — grammar for architecture guides

This is the writing grammar for the guide-writing workflow — the vocabulary and rhythm
architecture guides are built from, independent of which capability or stack a guide documents.

A guide's structure follows the capability's architectural center of gravity — the one idea
everything else hangs off (a shared contract, a pipeline, a runtime boundary, a data-ownership
split) — not a template. Two guides for two different capabilities will legitimately have
different section names, counts, and content blocks, because the architectures are actually
different. Reproduce the reasoning and information hierarchy below for whatever capability you're
documenting — not the shape of some other guide.

## Section rhythm

Regardless of what a section is about, it opens the same way — an Artifact design convention, not
a per-capability choice:

1. `section-head` — a two-digit stage number in a circle, then an `<h2>` naming the section.
2. `section-prompt` — one italicized guiding question, a real question the reader would actually
   ask, never a restatement of the heading. E.g. "How does one shared foundation support many
   different consumers of it?" or "Who owns responsibility as a unit of work moves through the
   running system?"
3. `section-intro` — one muted paragraph answering the prompt before any detail follows. A reader
   who stops here should have the right mental model, just not the depth.

Everything after is composed from the content blocks below, chosen per section — not all of them,
and not the same set, every time. An empty or decorative block is worse than no block.

## Content-block vocabulary

| Block | CSS | Communicates |
|---|---|---|
| Spec strip | `.specsheet` (hero only) | Concrete facts or guarantees: 3–6 operational numbers grounding the reader before prose — a size limit, a retry count, a cascade behavior. Pick facts that reveal scale or guarantees, not vanity metrics. |
| Ownership/flow diagram | `.ascii` | Ownership or topology: monospace box-and-arrow diagrams (`─│▼├└`) for data relationships and runtime sequences. Keep it readable in one glance. |
| Responsibility table | `.table-wrap > table.lc` | Responsibility mapping: component catalogs, decision/trade-off tables, security-concern tables, state tables — one row per concern. End an enforceable row with `<span class="src-ref">{{reference}}</span>` — a path, symbol, schema element, config key, protocol, or runtime boundary, whichever actually identifies where the rule lives. Don't force a class/method shape onto a rule implemented some other way, or genuinely distributed. |
| Ordered timeline | `.flow-steps` | Chronological sequence: strictly ordered phases (bold), each with sub-bullets. Only for genuinely sequential steps — independent operations read better as separate tables or flows. |
| Side card | `.callouts > .callout` | Supporting explanation: a status card, a short "why this architecture?" Q&A, or a boundary explanation. Not tied to a particular section or position. |
| Equation recap | `.formula` | Composition, cost, or lifecycle summary, used when a formula reads clearer than prose. May close a section or the guide; not required because a section covers an operation. |
| Variant badge | `.pill` (+ a variant class) | Recurring variants: short inline tags for 2–3 recurring variants, using variant classes and custom properties from `rules/template.html`. Add a new variant only when a recurring distinction genuinely needs one. |
| Clincher sentence | plain `<strong>` | Closing takeaway: one bolded sentence stating a reuse contract — "A caller integrates by composing X. It does not reimplement Y." Useful for a contract worth a concise close; not required by subject, nor by section name. |

## Section structure

A guide's section list follows what the capability's information actually looks like, not a
copied table of contents. Compact illustrations, not a checklist:

- A sequential lifecycle reads well as an ordered timeline (`.flow-steps`).
- Independent responsibilities (several things true at once, not a sequence) read better as
  separate tables or flows.
- A real, ordered extension process reads well as numbered steps.
- A capability with no runtime behavior of its own shouldn't get a runtime section just because
  other guides have one.

Keep recurring concerns conditional: purpose, security, runtime ownership, decisions, limitations,
and a closing summary appear only when they materially improve understanding of *this*
capability — never because a previous guide included them. Nothing here requires a section
literally named Building Blocks, Integrating, Architectural principles, Focused Improvements,
Runtime, or Summary — a guide may cover those concepts, but the capability decides what each is
called, how many there are, and where they sit. A closing-takeaway block (the clincher sentence,
the formula recap) can still end a guide; it just can't depend on that section being named
"Summary."

## Tone and evidence

Declarative and technical; no marketing language — but declarative isn't the same as certain:

- State verified facts directly; don't hedge what you've confirmed.
- Label uncertainty, inference, and unresolved decisions honestly instead of smoothing them into
  confident-sounding prose.
- For claims that matter architecturally (a reuse guarantee, a security boundary, an ownership
  rule), name the mechanism or evidence behind it — a symbol, a schema element, a config key, a
  protocol, a runtime boundary, whichever is appropriate to the system — not a fixed shape like "a
  class and a method" forced onto every claim.
- Avoid unsupported certainty: don't state something as universal ("every X does Y") unless
  you've verified it holds everywhere, and flag known exceptions instead of smoothing over them.

Numbers can appear wherever they communicate best — a spec strip, a table, or plain prose.

## Choosing a favicon

Pick one or two emoji from the capability's own domain and keep it stable across every redeploy —
the Artifact tool requires a favicon on every publish call, including updates, so re-supply the
same one rather than omitting it. When maintaining a guide you didn't originally publish, discover
or confirm its current favicon first — read the published Artifact, or ask the user if you can't
tell — rather than silently picking a new one; a changed favicon reads as a different document.

## Responsibility routing

This file covers the grammar to write *with*. It doesn't own what belongs elsewhere:
`rules/template.html` owns the HTML/CSS scaffold and the classes named above;
`rules/review.md` owns critically evaluating a finished guide — don't mistake self-checking
against this file's block vocabulary for a review, that's a separate pass run once a draft
exists; `rules/maintenance.md` owns changing an existing guide without breaking its
narrative; `SKILL.md` owns which workflow you're in and how these files route together.
