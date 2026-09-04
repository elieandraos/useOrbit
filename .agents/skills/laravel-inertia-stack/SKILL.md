---
name: laravel-inertia-stack
description: "Companion conventions for a Laravel + InertiaJS + Vue 3 + Pest stack, additive to Laravel Boost's laravel-best-practices and testing-best-practices skills — never a replacement for them. Activate for Laravel, Inertia, or Pest work on this stack — always alongside the matching Boost skill(s), never alone. See the routing table inside for the specific capabilities covered."
---

# laravel-inertia-stack

Personal companion conventions for Laravel + InertiaJS + Vue 3 + Pest. This skill is additive only:
Laravel Boost's `laravel-best-practices` and `testing-best-practices` own the general Laravel and Pest
baseline, and `inertia-vue-development` owns Inertia/Vue client-side patterns. This skill contains only
the delta genuinely additive to those skills — load it alongside the matching Boost skill(s), never as a
substitute for them, and never as a generic Laravel manual, a complete Vue guide, or documentation for
any one consuming project.

If a task needs a Boost skill this installation doesn't have, say so explicitly and treat this skill as
an incomplete companion for that area rather than silently filling the gap with improvised baseline
guidance.

Current evidence is strongest for Laravel, Inertia, and Pest. Vue 3 is part of the declared stack
boundary, but this skill carries no independent Vue-specific rules — route Vue implementation questions
to `inertia-vue-development`.

## When this activates

- Writing or reviewing a Laravel controller, Form Request, Action, Policy, or Resource in this stack.
- Adding query filtering or sorting to an Eloquent index query.
- Writing or organizing a Pest test.
- Defining an Eloquent local scope, a migration column, or a backed enum used as select options.
- Generating factory or seeder data.

## Routing

Load only the rule, blueprint, or template file(s) the current task needs — do not read every file for
every Laravel task. Resolved decisions live in the file that owns them; this table routes to them rather
than repeating them. See `README.md` for what `rules/`, `blueprints/`, and `templates/` each mean.

| Task | Load |
|---|---|
| Composing a CRUD or single-action controller | `blueprints/resource-controller.md` |
| Writing or organizing a Pest test | `blueprints/pest-testing.md`, then `rules/test-ownership.md` for the concrete class-ownership mapping |
| Wiring Form Request -> Action -> Controller | `rules/actions.md` |
| Authorizing a controller method | `rules/authorization.md` |
| Defining an Eloquent local scope | `rules/eloquent-attributes.md` |
| Adding index filtering and/or sorting | `blueprints/filters-and-sorting.md` + `rules/request-normalization.md` (a sort `direction` must be defaulted, not left nullable) + `templates/app/Filters/QueryFilter.php`, `templates/app/Sorts/QuerySorter.php`, `templates/app/Models/Concerns/Filterable.php`, `templates/app/Models/Concerns/Sortable.php` |
| Exposing a backed enum as select options | `rules/enum-options.md` |
| Coercing or defaulting request input | `rules/request-normalization.md` |
| Building a JsonResource for Inertia | `rules/resources.md` |
| Writing a factory or a dev-only seeder | `rules/factories-and-seeders.md` |
| Adding a mid-chain conditional query clause | `rules/query-conditionals.md` |
| Declaring a new concrete application class | `rules/php-conventions.md` |
| Writing a schema migration column | `rules/migrations.md` |
| Asserting an Inertia prop or flash message in a Pest HTTP test | `blueprints/pest-testing.md` + `templates/app/Providers/TestingServiceProvider.php` |

## Boundary

This skill is not:

- a replacement for `laravel-best-practices`, `testing-best-practices`, or `inertia-vue-development`;
- a generic Laravel manual or a complete Vue guide;
- documentation for any single consuming project;
- an architecture every project must adopt — the controller and Pest blueprints stay conditional
  (tenancy, non-CRUD shapes, filters/sorters) rather than mandatory for every feature.

Code examples throughout this skill use neutral, invented domain concepts. They are not drawn from, and
do not document, any specific consuming project.
