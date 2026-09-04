# Pest Testing Blueprint

## Boundary

This file defines the target `tests/Unit`/`tests/Feature` execution-boundary taxonomy for this stack.
`testing-best-practices` owns the general layer-ownership/de-duplication principle, record-level
test-data minimalism, and the general framework-vs-project testing distinction — this file does not
restate any of them. `rules/test-ownership.md` is the single source of truth for the concrete
Action/Policy/Filter/Sorter/Resource/Model/HTTP class-taxonomy mapping, per-layer assertion focus,
existence-versus-exact-value assertions, and the no-redundancy rule — load it alongside this file when
writing a layer-specific test.

## Unit vs. Feature — classify by execution boundary first

- **`tests/Unit`** — genuinely isolated only: no Laravel application boot, no database or factories, no
  service container, no Laravel facades. A plain enum, a plain value object, or any class with no
  framework dependency belongs here.
- **`tests/Feature`** — anything that boots Laravel or uses the database. This includes Action, Policy,
  Filter, Sorter, Resource, and Model tests in this stack, since they typically use factories, the
  database, or Laravel's container — not only HTTP tests. Mirror the owning application area inside
  `Feature` only where useful (`Http`, `Actions`, `Policies`, `Filters`, `Sorts`, `Resources`, `Models`)
  — never force a category a feature has nothing to put in.

Before relying on this distinction, confirm what the project's `tests/Pest.php` actually binds database
refresh to. A suite that applies it uniformly to both `Feature` and `Unit` has not drawn this boundary in
practice yet, regardless of the directory names — treat a directory name as aspirational until the
binding matches it, and don't assume a class under `tests/Unit` is actually isolated without checking.

## Binding `tests/Pest.php` to the boundary

Confirming the mismatch above is not the end state — the binding has to change to match it. Once every
framework-dependent test has been moved out of `tests/Unit`, narrow the project's `tests/Pest.php`
accordingly:

- the project's Laravel `TestCase` binding applies only to `Feature`;
- its chosen database-refresh trait (`RefreshDatabase` below is illustrative — `LazilyRefreshDatabase` or
  another project-selected mechanism applies the same way) applies only to `Feature`;
- genuinely isolated `Unit` tests receive no separate Laravel `TestCase` binding at all, and fall back to
  Pest/PHPUnit's default test case.

```php
pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

Binding the Laravel `TestCase` to `Unit` without the database trait does not satisfy the isolated
boundary either: `Illuminate\Foundation\Testing\TestCase` boots the full application in `setUp()`
regardless of whether a database-refresh trait is attached, so a `Unit` test bound to it still boots
Laravel.

Order matters when migrating an existing suite: move every framework-dependent test out of `Unit` first,
then narrow this binding. Narrowing it before the move is complete strips `TestCase` and the database
trait from tests still sitting in `Unit` mid-migration, breaking them.

## Preserve endpoint-oriented HTTP test files

Keep one Pest file per HTTP endpoint or closely related group (`IndexTest.php`, `ShowTest.php`,
`StoreTest.php`, `UpdateTest.php`, `DestroyTest.php`, and any custom-action endpoint's own file) inside
`tests/Feature/Http/{Domain}/`. Do not force every controller's tests into one oversized
`{Controller}Test.php` file merely for consolidation.

## Warning: don't let a resource-built comparison stand in for the value a test is specifically responsible for proving

The reusable `assertHasResource`/`assertHasPaginatedResource` macros (below) compare the actual Inertia
prop against a value built by instantiating the same production Resource the endpoint itself uses. That
comparison is appropriate when what an HTTP test needs to prove is integration — the correct Resource
class wraps the correct model instance for this route — because the Resource's own field-level
correctness is the Resource test's job (see `rules/test-ownership.md`). It stops being appropriate
when the HTTP test is specifically responsible for proving a field's value or a transformation the
Resource performs: building the expected side of that assertion from the same Resource makes the
comparison self-referential — if the Resource's shape regresses in a way that stays internally
self-consistent, the test cannot catch it. In that case, assert the literal expected value instead of
deriving it from the Resource under test.

## Minimal test data — the attribute-level delta

`testing-best-practices`'s `test-data.md` already owns record-level minimalism ("create only the records
required to arrange the behavior or support an assertion"). This file adds the narrower, additive
attribute-level rule it doesn't cover:

> Each test sets only the attributes required to establish its preconditions and prove the behavior under
> test. Set additional attributes only when they are material to the case — for example ordering,
> filtering, tenant isolation, a collision, an absence, or complete field mapping. "Minimal" does not mean
> incomplete coverage: a test specifically proving field mapping may intentionally provide the complete
> relevant payload.

## Reusable Inertia testing macros

`hasResource`, `hasPaginatedResource`, `assertHasResource`, `assertHasPaginatedResource`, and
`assertHasInertiaFlash` are custom Pest/Inertia-testing macros this stack ships as a reusable
implementation template — see
[`templates/app/Providers/TestingServiceProvider.php`](../templates/app/Providers/TestingServiceProvider.php)
for the implementation, required packages, and registration/guard instructions. Install and register that
provider before writing a test that calls any of these macros; do not write a test that assumes they
exist without checking first.

```php
test('an authenticated user can view an order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertHasResource('order', OrderResource::make($order));
});
```

This proves integration — the `show` route wraps the requested `$order` in `OrderResource` — which is
exactly the case the warning above allows. It would stop being appropriate if the test's actual
responsibility were proving a specific field transformation `OrderResource` performs; that case calls for
a literal expected value instead.

## Capability gating

Treat Pest TIA (`pest()->tia()->always()->locally()` — a core Pest 5 feature) and any other optional Pest
plugin (architecture testing, type coverage, and similar) as capability-gated: check what the consuming
project has actually installed and configured before assuming any of them is available. Never
universally prescribe one.

`LazilyRefreshDatabase` is a measured future candidate for a suite that currently binds `RefreshDatabase`
uniformly across `Feature` and `Unit` — record it for evaluation, not as a requirement to adopt.
