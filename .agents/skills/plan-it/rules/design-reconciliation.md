# Design Reconciliation

Design artifacts drift from what actually ships. That's normal — real product decisions get made
mid-implementation and never get folded back into the design source. This rule's job is to
**surface** that drift before it silently repeats, not to resolve it in either direction on its own.

## When this pass runs

Run this pass whenever the planned work has any frontend/UI scope — this runs during planning,
before canonical issue definitions exist, so scope means the user-facing surface being planned, not
only an issue already framed as UI wiring. Backend-only or infrastructure-only planned work with no
user-facing surface skips this file entirely.

Once frontend/UI is in scope, running the pass is not discretionary. But discovering that no
relevant design artifact exists is a valid, ordinary outcome of running it — it does not by itself
block planning. See "No relevant artifact" below.

## Locating design artifacts

This rule does not assume a directory, filename, format, design provider or tool, tracked-vs-
untracked status, or refresh mechanism, and it does not depend on any private memory entry.
Discover the consuming project's actual design-artifact sources, and how to access them, from
project-provided documentation, configuration, or reliable context already established in
conversation.

If the location or access method is unclear, ask the user — but only when resolving it is
materially necessary to produce a correct, developer-ready issue. Don't invent an artifact, and
don't invent a project convention for where one would live.

## Authority model

Reconciliation compares sources. Some authority is established in advance — most importantly an
approved locked decision — while unclear chronology or authority between other sources must be
surfaced, not assumed:

- An approved `plan.md` locked decision (`rules/plan-md-input.md`) governs the intended target for
  anything it covers. A design artifact or the current implementation can't silently override it.
- Current code is authoritative for current-state facts, and for discovering shipped product
  conventions elsewhere in the app.
- A design artifact is evidence of intended UI — not automatically the newest or winning source
  just because it exists.
- Shipping alone doesn't prove a deliberate, confirmed product decision. Something can ship without
  anyone having decided it should stay that way.
- When chronology or authority between two sources is genuinely unclear, and they express genuinely
  different product choices, the user decides — don't guess which one is "more current."
- A mismatch against an approved locked decision is recorded as drift against the approved target,
  not treated as grounds to re-litigate the decision. Only the user can amend a locked decision, and
  only explicitly.

Whether a derived constraint from `plan.md` has gone stale against current code is
`rules/plan-md-input.md`'s procedure, not this rule's — don't duplicate it here; apply this rule's
outcomes to whatever that check leaves in place.

## The procedure

Before drafting canonical issue definitions whose scope depends on a UI choice:

1. Identify any approved UI/product decisions that apply (from `plan.md` or otherwise established).
2. Locate and inspect the relevant available design artifacts, if any exist.
3. Inspect the affected shipped surface, and the closest established product precedent, where
   either exists — neither is guaranteed to exist for genuinely new UI.
4. Compare what the sources actually say.
5. Classify the result using the outcomes below, and record the resulting scope, constraint, drift
   note, or open product decision before drafting the affected issue(s).

## Outcomes

- **No material drift.** At least two relevant sources align, or their differences don't change
  product behavior or issue scope. Proceed.
- **Resolved drift.** Relevant sources materially differ, but an approved decision or established
  chronology resolves the intended target — a stale artifact superseded by a later confirmed
  decision, or shipped behavior that hasn't yet caught up with an approved future target. Record
  the drift against the resolved target; shipping alone still doesn't establish authority.
- **Genuine unresolved disagreement.** Relevant sources express different product choices, and no
  approved authority or established chronology resolves which one governs.
- **Genuinely new UI.** A relevant artifact describes a new surface with no shipped counterpart and
  no conflicting approved decision. Follow the artifact, subject to normal planning rules.
- **No relevant artifact.** No applicable artifact exists or can be accessed. Continue from approved
  decisions and established product conventions when they give enough direction; never invent
  layout or interaction requirements to fill the gap.

## Drafting readiness

Classifying the source relationship is separate from deciding whether drafting can proceed. After
classifying, check whether a material product decision needed for developer-ready scope is still
unresolved:

- Genuine unresolved disagreement normally leaves such a decision open, and blocks only the
  affected issue definitions.
- No relevant artifact doesn't block by itself.
- If a missing artifact leaves a necessary product decision unresolved, that unresolved decision —
  not the missing artifact — blocks the affected drafting.
- Issue definitions unaffected by the open decision may continue.
- The user remains the authority either way — present a genuine disagreement as the decision-ready
  list below, or ask directly when the gap came from insufficient source material.

## Presenting a blocking disagreement

When the outcome is a genuine unresolved disagreement, compile a short, decision-ready list before
drafting the affected issue(s):

- What each source indicates.
- Why neither clearly supersedes the other.
- The specific product decision required.
- An optional recommendation, with reasoning.

Don't silently pick a side. Don't draft the affected canonical issue definitions until the user
decides.

## What this rule owns

Comparing sources, classifying drift, and surfacing unresolved product choices. It does not create
or amend `plan.md`, update design artifacts, prescribe where a project stores design files, choose
implementation/component details, implement the UI, or duplicate issue drafting or review mechanics
owned elsewhere in this skill. Its output becomes input to canonical issue scope.
