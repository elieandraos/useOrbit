# document-it

Create, update, and review explanatory architecture guides — in Markdown, a Claude Artifact, or
both — reusing sufficient, current, verified understanding, and drawing on `lab-it`'s
investigation method only when evidence is missing or stale.

## When to use it

- A system's architecture needs a new guide, in Markdown, an Artifact, or both.
- A published guide needs reconciling with verified current reality.
- An existing guide needs an independent completeness check.

Not for debugging or reviewing a diff. Not for investigating a system from scratch, resolving
architecture decisions for a proposed feature, or synthesizing `plan.md` — those stay with
[`lab-it`](../lab-it/).

## Boring prompts

```shell
"Document the billing architecture."
"Update the architecture guide to match the current implementation."
"Is the authentication guide still accurate?"
"Write a Markdown guide for the export pipeline under docs/."
```

## What normally happens

Every request starts by checking whether currently available understanding is sufficient and
current. When it is, this skill writes or updates directly; when it isn't, it routes the relevant
investigation through `lab-it` first, then resumes. From there:

1. A new guide — the user chooses Artifact, Markdown, or both, unless they've already said.
2. An update to an existing guide, with its identity (file path, or `url` and favicon) preserved
   by default.
3. A standalone review of a guide's completeness — a findings report, not a rewrite.

## Ownership

Owns guide creation, guide maintenance, and guide review, in both supported formats.
Investigation from scratch, architecture decisions reached with the user, and `plan.md` synthesis
belong to [`lab-it`](../lab-it/) — file extension alone doesn't decide ownership; an approved
`plan.md` stays `lab-it`'s even though it's a `.md` file. Planning the resulting work into GitHub
issues belongs to [`plan-it`](../plan-it/).

## Context consumption

Activation loads only `SKILL.md`. `rules/authoring.md`, `rules/doc-style.md`, `rules/review.md`,
`rules/maintenance.md`, and `rules/template.html` each load individually as the current workflow
needs them — a new guide or an existing-guide update reaches `rules/authoring.md` first; a
connected architectural-change update to an Artifact guide can reach all five, since
`rules/authoring.md` routes to `rules/maintenance.md`, and `rules/maintenance.md` and
`rules/review.md` both route back to `rules/doc-style.md` for how a change gets written. A
standalone review's baseline is just `rules/review.md`, on top of activation — it doesn't require
executing `rules/authoring.md`'s writing procedure, whatever else the review consults along the
way. That two-file estimate holds when current evidence and the checklist suffice on their own; a
review may still consult an owning reference such as `rules/maintenance.md` for a specific
identity-preservation fact without that constituting loading the writing procedure. A request
routed to `lab-it` for missing or stale evidence additionally loads `lab-it`'s entrypoint.
See [the context-consumption model and representative-workflow
estimates](https://github.com/elieandraos/agentic-engineering/blob/main/docs/skill-context.md#document-it).

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill document-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
