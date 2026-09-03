# my-laravel-stack

A personal, portable companion for a **Laravel + InertiaJS + Vue 3 + Pest** stack. It carries
evidence-backed conventions, implementation blueprints, and reusable code templates established for that
stack — the delta genuinely additive to Laravel Boost, not a standalone Laravel education. This file
explains the idea and how the skill is organized; `SKILL.md` is the operational activation and routing
entrypoint agents actually load from.

## What it is, and what it isn't

Laravel Boost's `laravel-best-practices` and `testing-best-practices` already own the general Laravel and
Pest baseline — validation, Eloquent mechanics, resource/CRUD organization, migrations, and the general
testing discipline. `inertia-vue-development` owns Inertia/Vue client-side patterns. `my-laravel-stack`
loads **alongside** those skills, never instead of them, and contains only what they don't already cover.

It is not a replacement for Laravel Boost, a generic Laravel manual, a complete Vue guide, or
documentation for any one consuming project. It doesn't declare a universal architecture every project
must adopt — its blueprints stay conditional (tenancy, non-CRUD shapes, filters and sorters) rather than
mandatory for every feature.

The contents were derived from verified application behavior, tests, and relevant framework evidence,
with current evidence strongest for **Laravel, Inertia, and Pest**. Vue 3 is part of the declared stack
boundary, but this skill does not claim mature, independent Vue-specific guidance; Vue implementation
questions route to `inertia-vue-development` instead.

## Three kinds of content

- **`rules/`** — independently applicable conventions and invariants. Each file stands alone: a rule
  about Eloquent scope attributes, request normalization, or PHP class finality doesn't require any
  other file to make sense.
- **`blueprints/`** — multi-component implementation shapes that compose several rules and
  responsibilities into one recognizable pattern, such as a full controller composition or a Pest test
  taxonomy. A blueprint is where several rules meet in one worked structure.
- **`templates/`** — complete, installable PHP starting points, structured to mirror their intended
  project-relative path (`templates/app/...` mirrors `app/...` in a consuming project). These are meant
  to be copied and adapted, not merely read.

## What's here today

- **Rules** cover Action conventions, `#[Authorize]`-based authorization, `#[Scope]`-based Eloquent
  scopes, query conditionals (`when()` over `if`), request normalization in `prepareForValidation()`,
  JsonResource conventions, enum option lists, factory/seeder realism, PHP class finality, Laravel
  migration column nullability, and per-layer test ownership.
- **Blueprints** cover the conditional CRUD/single-action controller composition (Controller → Form
  Request → Policy/`#[Authorize]` → Action → Resource → Inertia, included only where an endpoint actually
  needs each stage), filtering and sorting, and the Pest testing taxonomy (execution-boundary
  classification and the Boost boundary for testing guidance — concrete per-layer test ownership lives in
  its own rule).
- **Templates** provide generalized, installable versions of `QueryFilter`, `Filterable`, `QuerySorter`,
  `Sortable`, and the Inertia testing macros bundled in `TestingServiceProvider`.

See `SKILL.md`'s routing table for exactly which file a given task needs — this README doesn't repeat
that mapping.

## Installing a template

A template is a starting point, not a fact about any specific project. Before copying one in:

1. Inspect the consuming project for an existing equivalent.
2. Reconcile the two rather than overwriting project-specific code outright.
3. Copy or adapt the template only where that reconciliation calls for it.
4. Validate the resulting implementation in the consuming project.

Never install a template blindly on the assumption that a project has nothing there already.
