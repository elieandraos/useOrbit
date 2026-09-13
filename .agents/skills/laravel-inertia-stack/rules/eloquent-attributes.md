# Eloquent Attributes

This file owns the narrow Eloquent attribute conventions that are additive to the general Laravel baseline.

## Computed attributes

Expose a derived model property as an Eloquent `Attribute` accessor when the value is naturally consumed as a model attribute, especially when the same derived value is used by multiple consumers. Prefer a protected accessor returning `Attribute` over a public helper method that represents the same model property.

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

final class Client extends Model
{
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->client_type === ClientType::Company
                ? $this->company_name
                : trim("{$this->first_name} {$this->last_name}"),
        );
    }
}
```

Consume the derived value through Eloquent's attribute interface using the snake-cased property name:

```php
$client->full_name;
```

Use an accessor for a derived property, not as a generic replacement for domain behavior. A reusable operation with behavior or side effects remains a method or action rather than an Eloquent attribute.

Laravel's `Illuminate\Database\Eloquent\Casts\Attribute` supports `make()`, `get()`, and `set()` for defining accessors and mutators. Confirm the project's installed Laravel version supports the API before relying on it.

## Local scopes

Define local Eloquent scopes with the `#[Scope]` attribute, not the legacy `scope`-prefixed method-name convention. Requires a Laravel version that provides `Illuminate\Database\Eloquent\Attributes\Scope` — confirm this class exists in the project's installed `laravel/framework` version before relying on it.

```php
// ✅ attribute-based
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

final class Order extends Model
{
    #[Scope]
    protected function filter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }
}

// ❌ legacy naming convention — do not use for new scopes
public function scopeFilter(Builder $query, QueryFilter $filters): Builder
{
    return $filters->apply($query);
}
```

- The method name is the scope name itself — no `scope` prefix (`filter`, not `scopeFilter`).
- Method visibility is `protected`.
- The call site is unchanged either way: `Order::query()->filter(...)`.
