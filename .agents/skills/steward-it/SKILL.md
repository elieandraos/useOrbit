---
name: steward-it
description: "Retrospectively investigates an engineering session when work was unexpectedly slow, difficult, repeatedly off course, or exposed a recurring agent-workflow problem. Reconstruct the session from available conversation, tool, repository, skill-trace, context-cost, and outcome evidence; distinguish observed facts from inference; classify the likely cause across methodology, skill, project, stack, prompt, execution, or external limitation; detect repeated patterns; and recommend deliberate improvements without changing canonical guidance automatically. Invoke explicitly for requests such as 'steward this session', 'what happened here?', 'why did this take so long?', or 'is this a recurring problem?'. A plain stewardship request produces the standard compact retrospective; a stewardship request followed by a diagnostic question switches to focused causal investigation. After a standard report, offer an optional execution-time and context/usage breakdown by skill when meaningful telemetry is available. Not part of the normal lifecycle and not an automatic reviewer of ordinary work."
---

# steward-it

## What this skill does

`steward-it` is the retrospective diagnostic companion for Agentic Engineering work that was unexpectedly
slow, confusing, wasteful, repeatedly off course, or useful as evidence for improving the system.

It investigates the session after the work happened. It is human-invoked only — do not activate it
merely because a session is long, a tool failed, or an implementation needed recovery — does not
silently monitor ordinary sessions, and does not automatically modify skills, project instructions, or
methodology.

The goal is:

> What happened, why did it happen, is it a one-off or a pattern, and what deliberate improvement is justified?

## Invocation

Use when the human explicitly asks for stewardship, retrospective diagnosis, or investigation of the
engineering session itself.

A plain stewardship request produces the standard compact retrospective. When the human follows the
stewardship request with a specific diagnostic question, switch to focused causal investigation for that
question rather than merely repeating the baseline report.

Typical prompts:

```text
"Steward this session."
"What happened here?"
"Why did this take so long?"
"Is this a recurring workflow problem?"
```

A diagnostic follow-up may look like:

```text
"Why were the issues left unassigned? Was this a plan-it execution gap or a skill gap?"
```

## Workflow

1. Identify the target session and reconstruct the observed execution.
2. Load the conditional rule(s) required by the available evidence and requested analysis.
3. Compare expected and observed behavior against the owning skill contract and project state.
4. Classify supported findings and check for recurrence.
5. Produce either the standard compact stewardship report or, when the human asked a diagnostic
   question, a focused causal answer with evidence, classification, and recommendation.
6. After a standard report, offer one optional execution-time and context/usage breakdown by skill when
   meaningful telemetry is available. Keep it opt-in so the baseline report remains compact.

## Rules

- `session-reconstruction.md` — identifies the target, reconstructs the timeline, records the
  activation path, and separates observation from inference; always load.
- `telemetry.md` — owns timing, human-wait detection, token/cache/thinking usage, phase timing, and
  skill attribution; load for every non-trivial session when session or runtime evidence may be
  available. Owns the explicit locate-then-parse discovery procedure for the session log and the
  measured/reconstructed/unavailable evidence states — a field is never "unavailable" before that
  procedure has actually been attempted.
- `findings.md` — owns causal classification, recurrence, and finding quality; load when there is a
  deviation, suspicious behavior, possible waste, recurrence question, diagnostic question, or other
  material finding to analyze.
- `evidence.md` — owns the durable-evidence recording convention; load only when the consuming project
  maintains a stewardship evidence file and the current review produces durable evidence worth
  retaining.
- `report.md` — owns the compact baseline report, presentation of measured, reconstructed, and
  unavailable evidence, and the optional telemetry follow-up; load for every non-trivial stewardship
  pass.

`session-reconstruction.md`, `telemetry.md`, and `report.md` form the standard baseline and load by
default for any non-trivial pass. `findings.md` and `evidence.md` are the genuinely conditional rules —
do not load either merely to satisfy a fixed checklist; load them only once their own trigger actually
applies. A diagnostic question is itself a trigger for `findings.md` because the answer must compare the
observed behavior with current guidance and classify the supported cause.

The point of the split is conditional detail beyond the baseline: the base skill routes the
retrospective, while each rule owns one diagnostic concern.

## Ownership boundaries

This skill owns retrospective investigation and improvement recommendations.

It does not own:

- normal implementation, planning, review, documentation, or delivery work;
- automatic session monitoring;
- automatic skill or methodology changes;
- replacing an owning skill's workflow with a second implementation of it;
- treating historical intent as evidence of current behavior.

When a finding belongs elsewhere, identify the likely owner: methodology, skill, project knowledge, stack
companion, prompt, execution, or external system.

> Detailed stewardship behavior lives in `rules/*.md` and is loaded only when its trigger applies.
