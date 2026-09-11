# steward-it

Retrospective engineering stewardship for sessions that were unexpectedly slow, difficult, or
repeatedly off course. It reconstructs what happened, checks the intended workflow against observed
behavior, classifies the likely cause, looks for recurrence, and recommends deliberate improvements
without automatically changing canonical guidance.

## When to use it

- A session took substantially longer than expected and you want to know why.
- Agent behavior appeared to ignore or overreach a skill boundary.
- The same workflow problem has appeared more than once and may be systemic.
- You want to compare a skill trace or context report with what actually happened.

It is human-invoked and retrospective. It is not part of the normal `Lab -> Plan -> Implement -> Review -> Ship`
lifecycle and does not silently monitor ordinary sessions.

## What it can investigate

A stewardship pass can combine session timing, human wait time, token/context telemetry, cache and
thinking usage when available, skill attribution, loaded rules, tool execution, tests, approvals,
repository mutations, recovery actions, and final outcome. It can then compare what actually happened
with the owning skill's contract and look for repeated failure patterns.

The useful diagnostic path is:

`session -> timeline -> skill/rule trace -> verification/actions -> token/context -> outcome -> recurrence -> finding`

The exact evidence available depends on the runtime and session log. Do not expect every session to
provide every measurement.

## Example prompts

### Session performance

```text
Steward this session. Report total elapsed execution time, human wait time, token/context
consumption, cache/thinking usage when available, which skills and rules were loaded, and where
the time and context went.
```

### Workflow debugging

```text
Steward this implementation. Trace how implement-it and review-it were actually used, compare
the observed execution with their rules, identify any deviations or wasted work, and tell me
what happened and why.
```

### Recurring pattern

```text
We've seen this problem more than once. Steward the relevant sessions, compare the evidence,
and tell me whether this is a real recurring failure, what the likely cause is, and which layer
owns the smallest justified fix.
```

## What normally happens

1. Reconstruct the relevant session from available evidence.
2. Separate observations from inference.
3. Compare intended guidance with observed behavior.
4. When relevant, analyze execution time and observed token/context telemetry.
5. Classify the likely cause and check for recurrence.
6. Recommend the smallest justified improvement.
7. Record only a compact durable observation when a project evidence file is maintained.

Skill traces and context reports are optional diagnostics, not requirements of normal skill execution.

## Evidence convention

Keep stewardship evidence small. A durable observation should normally be one or two sentences and
include a commit, PR, issue, or other concrete reference when available. The evidence file is not a
diary; it exists so later stewardship can detect repeated failure modes or useful cost patterns from
real work.

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill steward-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
