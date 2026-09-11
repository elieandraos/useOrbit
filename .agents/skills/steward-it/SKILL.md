---
name: steward-it
description: "Retrospectively investigates an engineering session when work was unexpectedly slow, difficult, repeatedly off course, or exposed a recurring agent-workflow problem. Reconstruct the session from available conversation, tool, repository, skill-trace, context-cost, and outcome evidence; distinguish observed facts from inference; classify the likely cause across methodology, skill, project, stack, prompt, execution, or external limitation; detect repeated patterns; and recommend deliberate improvements without changing canonical guidance automatically. Invoke explicitly for requests such as 'steward this session', 'what happened here?', 'why did this take so long?', or 'is this a recurring problem?'. Not part of the normal lifecycle and not an automatic reviewer of ordinary work."
---

# steward-it

## What this skill does

Use this skill as a retrospective diagnostic for Agentic Engineering work that feels unexpectedly
slow, confusing, wasteful, or repeatedly off course.

It investigates the session that already happened. It does not join the normal execution workflow,
does not silently monitor sessions, and does not automatically modify skills, project instructions,
or methodology.

The goal is to answer:

> What happened, why did it happen, is it a one-off or a pattern, and what deliberate improvement is justified?

## Invocation

This skill is **human-invoked only**. Do not activate it merely because a session is long, a tool
failed, or an implementation needed recovery. Activate when the user explicitly asks for a
retrospective, stewardship, diagnosis, or investigation of how the engineering session itself went.

Good prompts include:

```text
"Steward this session. I expected this to be much faster."
"What happened here? We spent three hours on something I thought was small."
"Steward this failure and tell me whether the skill is the problem."
"We've seen this twice now. Is there a pattern?"
```

Useful diagnostic requests can be specific about the evidence wanted:

```text
"Report total elapsed time, human wait time, token/context consumption, cache and thinking usage
when available, which skills/rules were loaded, and where the time and context went."
"Trace how implement-it and review-it were actually used, compare the execution with their rules,
and tell me what deviated or caused wasted work."
"Compare these stewardship findings and tell me whether this is a recurring failure and which layer
owns the smallest justified fix."
```

## Retrospective workflow

1. **Establish the target session and complaint.** Identify what the user expected, what actually
   happened, and what prompted the retrospective. Use the current conversation as the primary
   session record; use other evidence only when identifiable and relevant.
2. **Reconstruct the observed execution.** Trace meaningful events such as skill activation and
   routing, user approvals, delegated work, repository mutations, tests, recovery actions, and
   final outcomes. Use an explicit skill trace when one was produced in the session, but do not
   require a trace to perform stewardship.
3. **Assess context and execution cost when relevant.** Prefer observed session telemetry over static
   estimates. When available, use request-level token usage, cache-read/cache-creation usage,
   thinking-token usage, skill attribution, timestamps, tool execution timing, and explicit human
   approval intervals. Separate active execution time from human wait time when the evidence permits.
4. **Separate evidence from interpretation.** Mark claims as observed, supplied by the user,
   inferred, or unresolved. Do not turn an agent's own explanation into canonical fact without
   supporting evidence.
5. **Compare expected and observed behavior.** Identify where the session followed the intended
   workflow and where it diverged. Check the relevant skill contract, project instructions, and
   actual repository state when accessible.
6. **Classify the cause.** Prefer the smallest evidence-backed category that explains the problem:
   methodology gap, skill guidance gap, missing project knowledge or instruction, stack knowledge
   gap, ambiguous human prompt/decision, execution mistake, or external limitation. Multiple causes
   are allowed when the evidence supports them.
7. **Check for recurrence.** Search retained stewardship evidence or prior identifiable session
   findings when available. Treat a single observation as a hypothesis, not a canonical pattern.
8. **Recommend, do not mutate.** Recommend the smallest justified improvement and identify where it
   belongs. Do not edit canonical skills, rules, project instructions, or methodology automatically.
   Ask the human to retain or reject a steward-worthy finding before it becomes durable guidance.
9. **Record only durable evidence.** When the project keeps a stewardship evidence file, add a
   compact observation of no more than two sentences, with a commit, PR, issue, or other concrete
   reference when one exists. Do not create a diary or copy the full retrospective into the file.

## Skill traces

A skill trace is an optional diagnostic artifact, not a required part of ordinary skill execution.

When available, use it to reconstruct operational behavior such as:

`skills loaded -> workflow selected -> relevant rules -> key evidence -> decisions/approvals -> observed actions -> outcome`

A trace should describe what the agent did or what it can directly observe. It should distinguish
inference from observation and should never be treated as proof merely because the agent reported it.

## Context and execution evidence

Treat context and execution cost as diagnostic signals, not quality verdicts.

When reviewing consumption or time, keep these evidence types separate:

- **Observed session telemetry:** request-level input/output tokens, cache reads/creation, thinking tokens, skill attribution, and related runtime metadata when available.
- **Observed execution time:** elapsed session/tool time, with human approval or answer waits separated from active execution when the evidence permits.
- **Static or modeled estimates:** older file-size or workflow models may appear in historical evidence, but they are not runtime measurements and should not be used as current consumption proof.

Use observed telemetry to explain where time and context went, and compare real sessions before
recommending a split or rewrite. Large prompts, repeated loads, cache footprints, or long phases are
evidence for investigation, not proof of waste. Preserve uncertainty where telemetry is unavailable
or depends on undocumented runtime formats.

## Finding quality

A steward-worthy finding should contain four things:

- **Observed behavior:** what concretely happened.
- **Evidence:** where the observation can be checked.
- **Why it matters:** how it caused delay, risk, confusion, or repeated work.
- **Reusable recommendation:** what should change, if anything, and the owning layer.

Prefer one strong finding over many speculative ones. Do not create a finding merely because an
alternative workflow is imaginable.

## Ownership boundaries

This skill owns retrospective investigation of the engineering process and recommendations for
improvement.

It does not own:

- normal implementation, planning, review, documentation, or delivery work;
- automatic session monitoring;
- automatic skill or methodology changes;
- replacing the owning skill's workflow with a second implementation of it;
- treating historical intent as evidence of current behavior.

When the finding belongs elsewhere, identify the likely owner: the relevant skill, project
knowledge, stack companion, prompt, methodology, or external system.

## Output

Use a compact retrospective suited to the materiality of the session:

```text
## Steward review

Expected
[What the user expected and why the session was reviewed.]

Observed
[What actually happened, with concrete evidence.]

Finding
[Confirmed problem, or no material finding.]

Cause
[Evidence-backed classification; separate inference from observation.]

Pattern
[One-off, recurring, or unknown from available evidence.]

Recommendation
[Smallest justified improvement and its likely owner.]

Evidence record
[At most two compact sentences for the durable project evidence file, when appropriate.]
```

When the user asks specifically for a performance/debug trace, include the measured timing and
telemetry breakdown before the causal analysis. Do not force every section when the session is trivial.
Do not expand a retrospective into a full architectural or implementation review unless the user
separately asks for that work.
