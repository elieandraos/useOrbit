# Reviewing an architecture guide

This is a check on whether a finished guide actually communicates the architecture — not a
proofreading pass. A guide can be well-written and still fail this review; a guide with rougher
prose can pass it. Wording is not the subject.

## When to consult this file

- After publishing a new architecture guide, before considering it complete.
- After updating an existing architecture guide, before considering the update complete.
- Any time you're explicitly asked to evaluate whether a guide is complete or still
  architecturally misleading.

**Not** while a guide is still being drafted — this is a review of a finished artifact, not a
drafting aid. Drafting uses `rules/doc-style.md`; switch to this file once there's a document
to hold up to the light.

## Philosophy

- The question is always "does this explain the architecture," never "does this read well."
- Every finding must materially affect what the reader understands about how the system is
  shaped, owned, constrained, or reasoned about — if a suggested change wouldn't change that, it
  isn't a finding, it's a preference. Drop it.
- Don't nitpick style, word choice, or section ordering unless it actually obscures an
  architectural claim (an ambiguous pronoun that leaves "who owns this" unclear is a finding; a
  synonym you'd have picked instead is not).
- A guide is allowed known gaps. The review's job is to confirm a gap that materially affects
  understanding is stated somewhere in the guide, not silently absent — an unstated gap is the
  finding, not the gap itself.
- The output of a review is a short list of findings, not a rewrite. Identify weaknesses; let the
  guide's author decide what to act on. Don't rewrite prose inline as part of a review pass.

## How to run it

1. Inspect the actual artifact — the published Claude Artifact when one exists, or the exact
   artifact being proposed when it doesn't — the way a new engineer would actually encounter it,
   not your own memory of drafting it. Working from memory lets gaps silently fill themselves in.
2. Work through the checklist below. Treat each category as conditional: a question about a
   concern this architecture doesn't have (reuse, runtime, security, testing, decisions,
   limitations) is inapplicable, not failed — skip it rather than forcing an answer.
3. When a claim's factual accuracy is in question, reconcile it against verified implementation or
   investigation evidence, not the guide's own wording. This is a truth check on an existing
   claim, not a fresh investigation — investigating the system from scratch is `SKILL.md`'s
   "Document existing architecture" or "Update an existing architecture guide" workflow, not this
   review.
4. For every question you can't answer "yes" outright, write one finding: what's missing,
   unclear, or contradicted, and where in the guide. One sentence each — this stays lightweight.
5. Stop there. Don't fix findings inline. Report them, or act on only the ones that clearly
   matter, and leave stylistic judgment calls alone.

## Checklist

### Architectural center

- Is the architectural center of gravity clear early enough to orient the reader — established in
  the hero, an opening section, or wherever the guide's structure puts it first — rather than only
  inferable by the end?
- Does every major section reinforce that central idea, or does at least one section wander into
  a tangent that doesn't serve it?
- Where the guide has closing material, however it's structured, does it leave the reader
  remembering the architectural abstraction itself — a contract, a pipeline, a boundary — rather
  than a list of features? A guide isn't required to have closing material at all.

### Responsibility and ownership

- Are responsibilities, ownership, and boundaries explicit — could a reader point to where one
  component's responsibility ends and another's begins?
- Where the guide states a material rule, guarantee, or boundary and an identifiable enforcement
  mechanism exists for it, is that mechanism named — a path, symbol, schema element, config key,
  protocol, or runtime boundary, never a class/method shape forced onto a rule enforced some other
  way? Don't require a reference when no single identifiable mechanism exists.

### Reuse, integration, and variation — when the architecture has any

- If the guide covers reusable infrastructure alongside integration-specific or model-specific
  code, is the line between them explicit — could a reader point to where "shared" ends and
  "specific" begins?
- Where an extension seam exists, does the guide clearly distinguish what a new implementation or
  integration must *provide* from what it inherits for free, without omitting or glossing over the
  inherited behavior? A section reading like a tutorial for the whole feature, with no such
  distinction, fails this question.
- When the guide covers several concrete implementations of the same architecture, is one clearly
  the running example, with the others invoked only where they show a meaningful variation,
  exception, or pressure point — rather than several implementations taught in parallel until the
  shared architecture is harder to follow than the examples themselves? Multiple equal examples
  are legitimate when the architecture genuinely has no representative path, or when comparing
  implementations is itself the point.

### Runtime, lifecycle, and state — when the capability has any

- Is runtime ownership explained wherever the capability actually has runtime behavior?
- Is lifecycle kept conceptually distinct from runtime ownership — a state machine and a
  responsibility handoff are different ideas even when they describe the same feature? They don't
  need separate diagrams; conflating them so a reader can't tell which is which is the finding.
- Are state transitions ("the record becomes X") distinguishable in the prose from responsibility
  transitions ("component A hands off to component B")? A guide that uses one sentence shape for
  both is blurring them.

### Architecture versus implementation

- Does the guide explain the concept before showing code, every time, or does any section lead
  with a code block?
- Are code snippets limited to genuine extension seams — a contract, an interface, a scope — per
  `SKILL.md`'s output rules, or has a full method body snuck in for narrative decoration?
- Has API documentation or an implementation walkthrough started replacing architecture? A tell: a
  section regenerable by reading the source top to bottom instead of by understanding the design.

### Structure and content

- Does each section answer a coherent reader question, with a direct architectural answer, rather
  than trying to cover two unrelated questions at once?
- Is the structure driven by this capability's architecture, not carried over from a different
  guide's section list — a section that exists only because another guide had one is a finding?
- Are diagrams used for movement, ownership, topology, or relationships — not decoration? A static
  topology or relationship diagram is valid on its own; one that shows none of these is a candidate
  for deletion, not a finding to soften.
- Are tables used for responsibilities, guarantees, states, decisions, or trade-offs — not as
  formatting for prose that would read fine as a paragraph?

### Consistency

- Do counts, labels, and guarantees mean the same thing everywhere they appear — a number in one
  place matching what a table or diagram elsewhere claims?
- Is a volatile configuration value (a pagination size, a UI constant) presented as a durable
  architectural fact?
- Does the guide give a clear architectural home — not necessarily a layer, table, or diagram, but
  some identifiable place — to every feature it treats as core, such as one named in the opening or
  repeated across sections? A feature mentioned repeatedly without its ownership ever being pinned
  down anywhere is a finding, even if each mention is accurate on its own.
- Does any absolute claim ("every mutation uses X," "every model carries Y") conflict with an
  exception documented elsewhere, or state an intended convention as if it were a universal fact?
  The goal isn't zero exceptions; it's that the guide distinguishes convention from current
  implementation from known exception, and notes an exception wherever the convention itself is
  taught — not only wherever it happens to be listed.

### Decisions and limitations — when the guide states any

- Where the guide records an architecture decision, does it state the decision, the reason, and
  the accepted trade-off — not just what the code does?
- Where the guide states future or deferred work, is it genuinely still open, with a stated
  reason, rather than something already built? A completed item left in as future work is a
  finding.
- Is each known limitation's real status accurate — intentional, deferred, unresolved, or an
  acknowledged defect, whichever it is — with its consequence and known reasoning stated, rather
  than smoothed over or left as a bare "TODO: handle this better"? It doesn't need to be
  intentional to pass; it needs to be described as what it actually is.
- A known and accurately stated limitation is not itself a finding, unless it contradicts another
  claim, hides a consequence that matters, or is presented misleadingly.

### Overall

- Would another engineer understand *why* the system was designed this way, not just what it
  does? If every "why," "because," and trade-off were removed, would anything architectural
  actually be lost? If not, the guide hasn't cleared this bar yet.
- For a feature the guide treats as core, can a reader locate its architectural layer or
  responsibility owner? Ask further about runtime path, security or isolation guarantees, testing
  boundary, or trade-offs only where this architecture actually makes that dimension apply — don't
  require every core feature to carry every dimension.
- Does the guide leave the reader understanding the architectural impact of a future change and
  the invariants it must preserve — where responsibility lies, which guarantees matter, what would
  break — without the guide needing to substitute for reading the implementation itself?

## Review output

- Report only material findings, one sentence each: what's missing, unclear, contradicted, or
  obscured, and where in the guide.
- Include the relevant evidence when a finding is a factual mismatch against verified
  implementation.
- Order by consequence.
- Don't rewrite the guide.
- If there are no material findings, say the guide passes. Don't invent improvements to avoid
  returning a clean review.

## What this review does not flag

- Word choice, sentence rhythm, or comma placement that doesn't change what's understood.
- A different but equally valid section order or shape, when it communicates the architecture
  equally well.
- A missing content block, unless a real recurring distinction is being described in prose that a
  reader can't actually follow without it.
- A "when applicable" checklist category that doesn't apply to this architecture.
- A stated known gap, unless it contradicts another claim, hides a material consequence, or is
  presented misleadingly.
