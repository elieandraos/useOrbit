# laravel-inertia-stack

Bring evidence-backed Laravel, InertiaJS, Vue 3, and Pest conventions into the Implement
stage.

## When to use it

- Writing or reviewing a Laravel controller, Form Request, Action, Policy, or Resource on
  this stack.
- Adding query filtering or sorting to an Eloquent index query.
- Writing or organizing a Pest test.
- Implementing an approved issue that touches this stack.

This is an optional stack companion, not a pipeline stage — and not a replacement for
Laravel Boost. It loads **alongside** Boost's `laravel-best-practices`,
`testing-best-practices`, and `inertia-vue-development`, stating this stack's own durable,
opinionated conventions on top of that baseline. A rule here may restate or refine a Boost
topic when the stack has a stable position on it — Boost covering a topic is not, by
itself, a reason to drop the rule; only pointless mechanical duplication is trimmed.

## Boring prompts

```shell
"Add an is_active field with its migration, factory state, and tests."
"Build a filterable resource index."
"Implement the approved issue using this project's Laravel conventions."
```

## What normally happens

A task loads only the specific rule, blueprint, or template it needs — never the whole
skill at once. Three kinds of content back that up:

- **`rules/`** — independently applicable conventions (Actions, authorization, request
  normalization, and more).
- **`blueprints/`** — conditional shapes for multi-component work (resource controllers,
  filtering/sorting, Pest test taxonomy) — not mandatory architecture every project must
  adopt.
- **`templates/`** — adaptable starting points, never blindly copied: the consuming
  project is inspected for an existing equivalent, reconciled, and validated before a
  template is installed.

## Ownership

Laravel Boost is the first-party baseline and reference. `laravel-inertia-stack` states
the durable, opinionated conventions for this Laravel + Inertia + Vue 3 + Pest stack — it
may restate or refine a Boost topic when the stack has a stable position on it, trimming
only mechanical explanation Boost already covers well. The consuming project remains
authoritative over its own conventions; this skill isn't a generic Laravel manual, a
complete Vue guide, or documentation for any one project.

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill laravel-inertia-stack
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
