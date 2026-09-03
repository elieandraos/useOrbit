# Filters & Sorting Blueprint

## Purpose

Reusable, class-based query filtering and sorting for index endpoints, without ad-hoc query building in
the controller. This is a multi-component implementation blueprint: it coordinates query-parameter
validation in a Form Request, concrete Filter and Sorter classes, reusable base-class and model-trait
templates, model scope wiring, controller query composition, and direct database-backed tests into one
implementation shape.

## Responsibility split

| Concern | Where |
|---|---|
| Validating query params | `FormRequest` |
| Deciding which filters/sort columns apply, and how | `QueryFilter`/`QuerySorter` subclass |
| Wiring into the query | `Model` (via `Filterable`/`Sortable` trait) + `Controller` |

Filter and sorter classes never validate input — they trust an already-validated array or value, exactly
like Actions trust `$request->validated()`.

## Naming and location

- `app/Filters/QueryFilter.php` — abstract base class shared by every domain filter. Not `final` — it's
  designed for extension (see `rules/php-conventions.md`). Ships as a reusable implementation template:
  [`templates/app/Filters/QueryFilter.php`](../templates/app/Filters/QueryFilter.php).
- `app/Filters/{Model}Filter.php` — one concrete filter per model, flat in `app/Filters/`. Name:
  `{Model}Filter`. Always `final`.
- `app/Models/Concerns/Filterable.php` — trait adding the `filter` scope to a model. Ships as a reusable
  implementation template:
  [`templates/app/Models/Concerns/Filterable.php`](../templates/app/Models/Concerns/Filterable.php).
- `app/Sorts/QuerySorter.php` — abstract base class shared by every domain sorter. Not `final`. Ships as
  a reusable implementation template:
  [`templates/app/Sorts/QuerySorter.php`](../templates/app/Sorts/QuerySorter.php).
- `app/Sorts/{Model}Sort.php` — one concrete sorter per model, flat in `app/Sorts/`. Name: `{Model}Sort`.
  Always `final`.
- `app/Models/Concerns/Sortable.php` — trait adding the `sort` scope to a model. Ships as a reusable
  implementation template:
  [`templates/app/Models/Concerns/Sortable.php`](../templates/app/Models/Concerns/Sortable.php).

Before installing any of these templates into a project, inspect whether an equivalent already exists
(`app/Filters/`, `app/Sorts/`, `app/Models/Concerns/`) and reconcile rather than overwrite it.

## Filtering

```php
// app/Filters/OrderFilter.php
final class OrderFilter extends QueryFilter
{
    public function search(string $value): Builder
    {
        return $this->builder->where('reference', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
```

Each filter method takes the raw (validated) value, reads/writes `$this->builder`, and returns
`Builder`. Dispatch is convention over configuration: `QueryFilter::apply()` `Str::camel()`s each
validated array key and calls the same-named method on the concrete filter — no registration step. A key
with no matching method, or a `null`/`''` value, is silently skipped.

```php
// app/Models/Concerns/Filterable.php — see the reusable implementation template for the exact trait
#[Scope]
protected function filter(Builder $query, QueryFilter $filters): Builder
{
    return $filters->apply($query);
}
```

## Sorting

The sorting counterpart follows the identical shape, with one addition: an abstract `default()` method
the base class falls back to when no sort column, or an unrecognized one, is requested.

```php
// app/Sorts/OrderSort.php
final class OrderSort extends QuerySorter
{
    public function total(string $direction): Builder
    {
        return $this->builder->orderBy('total', $direction);
    }

    protected function default(Builder $builder): Builder
    {
        return $builder->orderBy('created_at', 'desc');
    }
}
```

`QuerySorter::apply()` resolves the requested column the same way `QueryFilter` resolves a filter key,
then breaks ties on the model's primary key — so pagination and tests get a stable, repeatable order
regardless of the query plan the database chooses for the primary sort column.

## Wiring: FormRequest → Controller

```php
final class IndexOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', new Enum(OrderStatus::class)],
            'sort' => ['nullable', 'in:total,created_at'],
            'direction' => ['in:asc,desc'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'direction' => $this->input('direction') ?? 'asc',
        ]);
    }
}
```

`QuerySorter`'s `$direction` constructor parameter is a non-nullable `string` — default it in
`prepareForValidation()` as shown above (see `rules/request-normalization.md`) rather than leaving it
`nullable` and reading a possibly-`null` value from `validated()`. Skipping this step is the most common
way to wire a `QuerySorter` incorrectly: an omitted `direction` query param passes `null` straight into a
`string` parameter and throws a `TypeError`.

```php
#[Authorize('viewAny', Order::class)]
public function index(IndexOrderRequest $request): Response
{
    $orders = Order::query()
        ->filter(new OrderFilter($request->validated()))
        ->sort(new OrderSort($request->validated('sort'), $request->validated('direction')))
        ->paginate()
        ->withQueryString();

    return Inertia::render('Orders/Index', [
        'orders' => OrderResource::collection($orders),
    ]);
}
```

With no query params, `validated()` is `[]` for the filter and `null` for the sort column, both loops are
no-ops, and behavior is identical to the unfiltered/default-sorted endpoint — no special-casing needed
for the "no filters" case. Choose a page size for `paginate()` deliberately for the endpoint; this
pattern does not prescribe one.

## Explicitly out of the core

Filtering and sorting are optional capabilities loaded only when an index endpoint genuinely needs
them — see `resource-controller.md`'s "Explicitly out of the core" section for why they,
exports, and admin-table tooling are not part of every CRUD controller's mandatory shape.

## Testing

See `rules/test-ownership.md` for full conventions. In short: filter and sorter classes are tested directly
against a real query and database — no HTTP — which places them in `tests/Feature` under this stack's
Pest taxonomy (see `blueprints/pest-testing.md`), not `tests/Unit`; "direct" here means without going
through the HTTP layer, not genuinely framework-isolated. Controller tests stay thin — a wiring smoke
test plus validation-error cases.
