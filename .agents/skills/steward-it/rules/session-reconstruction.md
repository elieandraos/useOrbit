# Session Reconstruction

## When this applies

Load for every stewardship pass.

## Workflow

1. Identify the target session and what prompted stewardship.
2. Use the current conversation as the primary session record, then use identifiable session logs,
   tool traces, repository state, and other evidence when relevant and accessible — `telemetry.md`'s
   "Portable contract" owns actually locating and parsing the session record itself; this step supplies
   the target identification that procedure needs, not a separate lookup of its own.
3. Reconstruct meaningful events: skill activation, routing, approvals, delegated work, repository
   mutations, tests, recovery actions, and outcome.
4. Distinguish observed facts from user-supplied facts, inference, and unresolved questions.
5. Compare the observed lifecycle with the owning skill's current contract and the repository state.

A skill trace is optional evidence, not a prerequisite. Never treat an agent's own retrospective
explanation as proof when direct evidence is available.

## Activation trace

Record the relevant activation path when evidence permits:

`skills loaded -> workflow selected -> relevant rules -> key evidence -> decisions/approvals -> observed actions -> outcome`

Use the actual loaded skills and rules, not an assumed activation list.
