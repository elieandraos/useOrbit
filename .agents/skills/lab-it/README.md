# lab-it

Investigate how a system actually works, answer architecture questions, and—when
useful—turn approved decisions into a change plan.

## When to use it

- You need an answer about how something in the codebase actually works, backed by real
  implementation, tests, and current evidence — not conventions or guesses.
- A feature idea needs its architecture decisions settled before planning or
  implementation starts.

Not for explaining one function, debugging, reviewing a diff, writing API reference docs, or
creating, updating, or reviewing an architecture guide — see [`document-it`](../document-it/) for
guide work, which draws on this skill's investigation method when its own evidence is missing or
stale.

## Boring prompts

```shell
"How does authentication work across the application?"
"Plan how invoice exports should fit into the system."
```

## What normally happens

Every request starts with the same investigation: inspect the real system, reconcile
implementation, config, schema, tests, and history, and explain what's there — including
what's still uncertain. Investigation and any decision conversation scale to the request: a
feature that closely follows established, already-approved conventions may need only enough
inspection to confirm architectural fit, with no design interview or `plan.md` required — existing
instances establish conventions, not automatic approval of new product behavior. From there, one of
two outcomes follows:

1. Investigation and a direct answer — the complete result on its own.
2. An approved `plan.md`, handed off as canonical input for planning.

A `plan.md` is something a request specifically asks for, never an automatic next step after
investigation.

## Ownership

Owns architecture investigation, architectural explanation, and turning approved
decisions into a `plan.md`. Creating, updating, or reviewing an architecture guide belongs to
[`document-it`](../document-it/) — file extension alone doesn't decide ownership; an approved
`plan.md` stays this skill's even though it's a `.md` file. Planning the resulting work into
GitHub issues belongs to [`plan-it`](../plan-it/); implementing it belongs to
[`implement-it`](../implement-it/).

## Context consumption

Activation loads only `SKILL.md`. Its one rule file, `rules/plan-synthesis.md`, loads only for the
"Plan feature architecture" workflow — a plain investigation-and-answer request never reaches it.
See [the context-consumption model and representative-workflow
estimates](https://github.com/elieandraos/agentic-engineering/blob/main/docs/skill-context.md#lab-it).

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill lab-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
