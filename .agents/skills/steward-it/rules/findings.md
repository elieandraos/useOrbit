# Findings and Causality

## When this applies

Load after session reconstruction when there is a workflow deviation, suspicious behavior, possible waste,
recurrence question, diagnostic question, or other material finding to analyze.

## Analysis

Compare expected and observed behavior across the relevant skill contract, project instructions, stack
knowledge, repository state, verification, approvals, and mutations.

When the human asks a diagnostic question after invoking stewardship, answer that question as an
investigation rather than treating it as a request to rerun the standard report. The investigation must
establish the observed behavior, gather checkable evidence, compare it with the current owning guidance,
and classify the smallest supported cause before recommending a canonical change.

Classify the smallest evidence-backed cause:

- methodology gap;
- skill guidance gap;
- missing project knowledge or instruction;
- stack knowledge gap;
- ambiguous human prompt or decision;
- execution mistake;
- external limitation.

For diagnostic classification, explicitly distinguish:

- **Execution gap** — the current guidance was sufficient, but the agent did not follow or apply it.
- **Skill gap** — the current guidance was missing, ambiguous, contradictory, incorrectly routed, or otherwise insufficient.
- **Project/convention gap** — the reusable guidance was sufficient, but the consuming project's local convention or instruction was missing, stale, or undiscovered.
- **External/runtime limitation** — the expected behavior was blocked by a tool, runtime, capability, or unavailable evidence after the applicable discovery path was attempted.
- **Undetermined** — the available evidence does not support a confident classification.

Use the narrower classification when the evidence supports it; do not call something a skill gap merely
because the desired behavior did not occur. A canonical skill change requires evidence that the current
guidance itself is insufficient, not merely that the agent failed to follow sufficient guidance.

Multiple causes are allowed only when the evidence supports them.

## Recurrence

Search retained stewardship evidence or prior identifiable session findings when available. Treat a
single observation as a hypothesis. Do not turn an alternative workflow or isolated preference into a
canonical pattern.

## Finding quality

A durable finding should establish:

1. observed behavior;
2. evidence that can be checked;
3. comparison with the current guidance or owner;
4. why it matters;
5. the smallest reusable recommendation and owning layer.

For a diagnostic question, report the classification and the evidence supporting why it is or is not a
skill gap. If the current guidance already covers the behavior, say so explicitly and prefer an execution,
project, prompt, or external classification as supported.

Prefer one strong finding over many speculative ones. Recommendations are proposals only; stewardship
never edits canonical guidance automatically.
