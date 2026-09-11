# Telemetry

## When this applies

Load for every non-trivial stewardship pass when session telemetry is available or reasonably discoverable.

## Evidence

Prefer observed runtime telemetry over static estimates. When available, inspect request-level:

- input/output tokens;
- cache-read/cache-creation usage;
- thinking-token usage;
- `attributionSkill` or equivalent skill attribution;
- timestamps and tool execution timing;
- human approval or answer intervals.

Older file-size or workflow models may still appear in historical evidence such as prior scenario
records. Treat those as historical intent only — they are not runtime measurements and must not be
cited as current consumption proof.

## Timing

Report:

- total elapsed time;
- active execution time;
- human wait time;
- phase timing when the session log contains identifiable phase boundaries.

Human wait is excluded from active execution time. For Claude Code sessions, treat a confirmed
`AskUserQuestion` interaction as human wait from its tool-use event through the matching tool-result
event. Do not require the enclosing user turn to have `origin.kind == human`; observed sessions may
have `origin: None` on those result turns.

State timing as measured when the evidence supports it. Otherwise mark the field unavailable.

## Context and skill attribution

When `attributionSkill` is available, group meaningful token and cache usage by skill, including
unattributed requests. Distinguish directly reported usage from inferred percentages or aggregates.
Do not invent skill attribution that the runtime does not expose.

Use telemetry as diagnostic evidence, not as a quality verdict. Large prompts, repeated loads, cache
footprints, and long phases justify investigation but do not prove waste by themselves.
