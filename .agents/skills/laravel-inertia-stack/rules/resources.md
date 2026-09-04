# Model Resources

## Purpose

A controller never passes a raw Eloquent model or query result directly to Inertia. A **JsonResource**
sits between the query and the view — it controls exactly which fields are exposed and their shape.

## Responsibility split

| Concern | Where |
|---|---|
| Field selection and shape | `JsonResource` |
| Eloquent query | `Controller` |
| Data presentation | Vue page component |

## Naming and location

- Place resources in `app/Http/Resources/` — for example `app/Http/Resources/OrderResource.php`.
- Name each resource `{Model}Resource` — for example `OrderResource`.

## Resource structure

```php
/** @mixin Order */
final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'total' => $this->total,
        ];
    }
}
```

## Key rules

- Always `final class` (see `php-conventions.md`).
- Add `/** @mixin ModelClass */` PHPDoc on the class — enables model-property autocomplete and removes
  the need for a `@return` annotation on `toArray()`.
- Do not add a `@return` docblock to `toArray()` — the native return type declaration is sufficient.
- Only expose the fields the view actually needs, not the full model.

## Controller — wrap before passing to Inertia

```php
// index — paginated collection
public function index(): Response
{
    return Inertia::render('Orders/Index', [
        'orders' => OrderResource::collection(Order::query()->paginate()),
    ]);
}

// show — single resource
public function show(Order $order): Response
{
    return Inertia::render('Orders/Show', [
        'order' => OrderResource::make($order),
    ]);
}
```

Never pass a raw Eloquent result:

```php
// ❌ exposes every model attribute, no shape control
return Inertia::render('Orders/Index', [
    'orders' => Order::query()->paginate(),
]);
```

## Exposing a relation — `whenLoaded()`, never a bare accessor, never model `$with`

**Problem:** a resource field that reads a relation directly (`$this->customer?->name`) looks fine
wherever the controller happens to eager-load it, but the resource itself has no memory of which caller
that was. Any other controller action, action class, or test that builds the same resource from a model
that didn't eager-load the relation triggers a silent N+1 — lazy-loaded once per row when the resource is
used on a collection — unless the project has enabled strict lazy-loading prevention. General
lazy-loading-prevention guidance (`Model::preventLazyLoading()`) belongs to `laravel-best-practices`'s
`db-performance.md`; this file does not assume either way whether a given project has enabled it.

**Fix — wrap relation access in `whenLoaded()`:**

```php
// ✅ never lazy-loads; the key is simply omitted from the JSON when not eager-loaded
'customer_name' => $this->whenLoaded('customer', fn () => $this->customer?->name),

// ❌ silently lazy-loads (N+1) in every controller action that didn't eager-load it
'customer_name' => $this->customer?->name,
```

**Do not reach for `protected $with = [...]` on the model to fix this instead.** It eager-loads the
relation on every fetch of that model anywhere in the app — every other controller action, every action
class's internal `->fresh()`, every factory call in every test — not just the one page that actually
needs it. Keep eager-loading explicit and endpoint-scoped (`->load()`/`->with()` in the specific
controller method that needs the field), and let `whenLoaded()` make the resource safe regardless of
which caller reaches it.
