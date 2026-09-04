# laravel-inertia-stack

A companion for a **Laravel + InertiaJS + Vue 3 + Pest** stack. It carries evidence-backed
conventions, implementation blueprints, and reusable code templates — the delta genuinely additive
to Laravel Boost, not a standalone Laravel education.

## What it is, and isn't

Laravel Boost's `laravel-best-practices` and `testing-best-practices` own the general Laravel and
Pest baseline; `inertia-vue-development` owns Inertia/Vue client-side patterns.
`laravel-inertia-stack` loads **alongside** those, never instead of them, and contains only what they
don't already cover. It's not a universal architecture every project must adopt — its blueprints
(tenancy, non-CRUD shapes, filters/sorters) stay conditional, not mandatory.

Evidence is strongest for Laravel, Inertia, and Pest; Vue-specific questions route to
`inertia-vue-development`.

## Three kinds of content

- **`rules/`** — independently applicable conventions (Actions, `#[Authorize]`, `#[Scope]`, request
  normalization, and more).
- **`blueprints/`** — multi-component shapes composing several rules (resource-controller
  composition, filtering/sorting, Pest testing taxonomy).
- **`templates/`** — installable PHP starting points mirroring their project-relative path
  (`QueryFilter`, `Filterable`, `QuerySorter`, `Sortable`, Inertia testing macros).

See `SKILL.md`'s routing table for exactly which file a task needs.

## Installing a template

A template is a starting point, not a fact about any project. Inspect the consuming project for an
existing equivalent first, reconcile rather than overwrite, adapt only where needed, and validate the
result — never install blindly.
