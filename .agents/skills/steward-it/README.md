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

Every non-trivial stewardship pass starts with the same compact baseline: execution timing, context /
usage, skill activation, and workflow verification, followed by findings and causal analysis. Detailed
telemetry and analysis are loaded through the relevant conditional rules under [`rules/`](rules/).

A plain `/steward-it` request produces that standard compact retrospective. When the human follows it
with a specific diagnostic question, stewardship switches to focused causal investigation for that
question and explicitly classifies the supported cause rather than merely repeating the baseline report.

## Baseline report structure

The standard retrospective keeps a stable semantic section order so reports remain comparable across
sessions:

1. **Expected** — why stewardship was requested and what was expected.
2. **Execution** — elapsed time, active time, human wait, and phase breakdown when measurable.
3. **Context / usage** — output, thinking, cache usage, and skill attribution when supported.
4. **Skill activation** — expected and observed activation plus mismatches.
5. **Verification / workflow** — tests, review, approvals, commits, closure, and other material events.
6. **Findings** — confirmed problems or no material finding.
7. **Cause** — evidence-backed causal classification.
8. **Pattern** — recurrence status from available evidence.
9. **Recommendation** — smallest justified improvement and likely owner.
10. **Evidence record** — only when durable evidence applies.

The semantic structure is stable, but presentation can adapt to the evidence. Tables are encouraged when
they make comparable timing, usage, or attribution data easier to inspect. Narrative is appropriate when
the evidence is better expressed that way. The baseline sections should not be silently renamed or
omitted merely because a session is unusually small or rich; use `not applicable` or `unavailable` when
necessary.

Diagnostic follow-ups are intentionally different: when the human asks a specific causal question after
invoking stewardship, answer that question directly with the relevant evidence, classification, and
recommendation rather than repeating the entire baseline report.

After a standard baseline report, when meaningful telemetry is available, offer one optional next step:
request a detailed execution-time and context/usage breakdown by skill. Keep it opt-in so the default
report remains compact. When that breakdown is requested, clearly caveat any sticky or otherwise
non-causal skill-attribution field before presenting per-skill tables.

The useful diagnostic path is:

`session -> timeline -> skill/rule trace -> verification/actions -> token/context -> outcome -> recurrence -> finding`

The exact evidence available depends on the runtime and session log — but "depends on the runtime"
describes what discovery can find, not a reason to skip attempting it. Do not expect every session to
provide every measurement, and do not conclude a measurement is unavailable before actually locating and
parsing the session log.

## Rules

- [`session-reconstruction.md`](rules/session-reconstruction.md) — always loaded.
- [`telemetry.md`](rules/telemetry.md) — loaded for non-trivial sessions when session or runtime evidence
  may be available; owns actually locating and parsing the session log before concluding telemetry is
  unavailable.
- [`findings.md`](rules/findings.md) — loaded when there is a material deviation, suspicious behavior, recurrence question, or diagnostic question.
- [`evidence.md`](rules/evidence.md) — loaded only when durable project evidence applies.
- [`report.md`](rules/report.md) — loaded for every non-trivial stewardship pass.

The split keeps the base skill small while allowing detailed diagnostics to load only when useful.

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

### Diagnostic cause classification

```text
Steward this session. Why were the issues left unassigned? Was this a plan-it execution gap
or a skill gap? Use repository evidence and the current plan-it rules to classify the cause.
```

### Recurring pattern

```text
We've seen this problem more than once. Steward the relevant sessions, compare the evidence,
and tell me whether this is a real recurring failure, what the likely cause is, and which layer
owns the smallest justified fix.
```

## What normally happens

1. Reconstruct the relevant session from available evidence.
2. Load the conditional rules required by the session and requested analysis.
3. Separate observations from inference and compare intended guidance with observed behavior.
4. Classify the likely cause and check for recurrence.
5. Produce either the standard compact stewardship report or, when a diagnostic question was asked,
   a focused causal answer with evidence, classification, and recommendation, plus a durable evidence
   record only when a project evidence file is maintained.
6. After a standard report, offer the optional telemetry breakdown when meaningful telemetry exists.

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
