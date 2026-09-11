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
`testing-best-practices`, and `inertia-vue-development`, carrying only the delta genuinely
additive to them.

## Boring prompts

```shell
"Add an is_active field with its migration, factory state, and tests."
"Build a filterable resource index."
"Implement issue #42 using this project's Laravel conventions."
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

The consuming project remains authoritative over its own conventions. This skill fills
the gap between Laravel Boost's general baseline and this specific stack's delta — it
isn't a generic Laravel manual, a complete Vue guide, or documentation for any one
project.

## Install

```shell
npx skills add elieandraos/agentic-engineering --skill laravel-inertia-stack
```

See [`SKILL.md`](SKILL.md) for the complete operational contract.
