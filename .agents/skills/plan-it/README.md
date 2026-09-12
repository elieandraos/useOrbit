# plan-it

Turn a feature request or approved `plan.md` into reviewed, implementation-ready GitHub
issues.

## When to use it

- "Let's build X" — a direct feature request, planned from scratch.
- [`lab-it`](../lab-it/) has already produced an approved `plan.md` — treated as
  canonical input, not re-derived from conversation.
- An unexpected finding needs investigating before deciding whether it belongs in the
  backlog.

Both entry paths — a direct request and an approved `plan.md` — are equally valid
starting points.

## Boring prompts

```shell
"Plan invoice exports into GitHub issues."
"Turn the approved plan.md into GitHub issues."
"Investigate whether this bug belongs in the backlog."
```

## What normally happens

The work gets classified, scoped, and drafted into canonical issue definitions, sequenced
by real dependency rather than a fixed template. Nothing reaches GitHub without two
rounds of human approval: the full issue content is reviewed first, then the proposed
milestone/label/assignee metadata is approved second. Only after both approvals does
anything get created — and every mutation is validated by reading it back afterward.

## GitHub is the substrate

Plans work as GitHub milestones, labels, and issues intentionally — portable across
GitHub-based projects with different stacks, not across issue trackers.

## Ownership

Owns feature classification, scope discovery, issue drafting, sequencing, review, and
GitHub issue creation after approval. Implementation belongs to
[`implement-it`](../implement-it/), with milestone delivery and release handled downstream by
[`ship-it`](../ship-it/) — this skill plans the work, it doesn't build it.

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill plan-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
