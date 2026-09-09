# Documentation style — grammar for architecture guides

This is the writing grammar for the guide-writing workflow — the vocabulary and rhythm
architecture guides are built from, independent of which capability or stack a guide documents,
and independent of whether the output is Markdown, a Claude Artifact, or both.

A guide's structure follows the capability's architectural center of gravity — the one idea
everything else hangs off (a shared contract, a pipeline, a runtime boundary, a data-ownership
split) — not a template. Two guides for two different capabilities will legitimately have
different section names, counts, and content blocks, because the architectures are actually
different. Reproduce the reasoning and information hierarchy below for whatever capability you're
documenting — not the shape of some other guide.

## Section rhythm

Regardless of what a section is about, it opens the same way:

1. A heading naming the section — in Artifact output, a two-digit stage number in a circle
   (`section-head`) then an `<h2>`, an Artifact design convention.
2. One italicized guiding question — a real question the reader would actually ask, never a
   restatement of the heading (`section-prompt` in Artifact output). E.g. "How does one shared
   foundation support many different consumers of it?" or "Who owns responsibility as a unit of
   work moves through the running system?"
3. One short paragraph answering the prompt before any detail follows (`section-intro`, muted
   styling in Artifact output; a plain paragraph in Markdown). A reader who stops here should have
   the right mental model, just not the depth.

Everything after is composed from the content blocks below, chosen per section — not all of them,
and not the same set, every time. An empty or decorative block is worse than no block.

## Content-block vocabulary

| Communicates | Artifact block (CSS) | Markdown equivalent |
|---|---|---|
| Concrete facts or guarantees grounding the reader before prose — a size limit, a retry count, a cascade behavior. Pick facts that reveal scale or guarantees, not vanity metrics. | Spec strip — `.specsheet` (hero only) | A short bulleted list of 3–6 facts near the top of the guide, before the first section |
| Ownership or topology: data relationships and runtime sequences. Keep it readable in one glance. | Ownership/flow diagram — `.ascii` | A fenced code block carrying the same monospace box-and-arrow diagram (`─│▼├└`) |
| Responsibility mapping: component catalogs, decision/trade-off tables, security-concern tables, state tables — one row per concern. | Responsibility table — `.table-wrap > table.lc` | A standard Markdown table. End an enforceable row with an inline-code reference — a path, symbol, schema element, config key, protocol, or runtime boundary, whichever actually identifies where the rule lives |
| Chronological sequence: strictly ordered phases. Only for genuinely sequential steps — independent operations read better as separate tables or flows. | Ordered timeline — `.flow-steps` | A numbered list, each item bold-led with sub-bullets |
| Supporting explanation: a status card, a short "why this architecture?" Q&A, or a boundary explanation. Not tied to a particular section or position. | Side card — `.callouts > .callout` | A blockquote |
| Composition, cost, or lifecycle summary, used when a formula reads clearer than prose. May close a section or the guide; not required because a section covers an operation. | Equation recap — `.formula` | An inline or fenced formula line |
| Recurring variants: short inline tags for 2–3 recurring variants. Add a new variant only when a recurring distinction genuinely needs one. | Variant badge — `.pill` (+ a variant class) | Inline code or a short bold tag, used consistently for the same variant everywhere it appears |
| Closing takeaway: one bolded sentence stating a reuse contract — "A caller integrates by composing X. It does not reimplement Y." Useful for a contract worth a concise close; not required by subject, nor by section name. | Clincher sentence — plain `<strong>` | A bolded sentence, identical in role |

Don't force an Artifact CSS class into a Markdown file, or invent Markdown-only formatting inside
an Artifact page — write each format the way it's natively read.

## Section structure

A guide's section list follows what the capability's information actually looks like, not a
copied table of contents. Compact illustrations, not a checklist:

- A sequential lifecycle reads well as an ordered timeline.
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

## Choosing a favicon (Artifact only)

Pick one or two emoji from the capability's own domain and keep it stable across every redeploy —
the Artifact tool requires a favicon on every publish call, including updates, so re-supply the
same one rather than omitting it. When maintaining a guide you didn't originally publish, discover
or confirm its current favicon first — read the published Artifact, or ask the user if you can't
tell — rather than silently picking a new one; a changed favicon reads as a different document.
Markdown output has no favicon equivalent; a Markdown guide's identity is its file path.

## Responsibility routing

This file covers the grammar to write *with*. It doesn't own what belongs elsewhere:
`rules/template.html` owns the Artifact HTML/CSS scaffold and the classes named above;
`rules/review.md` owns critically evaluating a finished guide — don't mistake self-checking
against this file's block vocabulary for a review, that's a separate pass run once a draft
exists; `rules/maintenance.md` owns changing an existing guide without breaking its narrative;
`rules/authoring.md` owns format selection and the new-guide/existing-guide-update procedures that
call on this grammar; `SKILL.md` owns which workflow you're in, which format(s) apply, and how
these files route together.
