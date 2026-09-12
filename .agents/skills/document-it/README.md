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

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill document-it
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
