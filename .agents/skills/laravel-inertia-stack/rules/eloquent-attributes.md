# Eloquent Attributes

Conventions for computed model attributes and local scopes. `laravel-best-practices` already covers
the `#[Scope]` attribute's own mechanics; see "Local scopes" below for this stack's own migration-debt
policy on top of that baseline.

## Computed attributes

Expose a derived model property as an `Attribute` accessor when the value is naturally consumed as a
model attribute, especially when several consumers need the same derived value. Prefer a protected
accessor returning `Attribute` over a public helper method that represents the same property.

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

Consume the derived value through Eloquent's attribute interface, using the snake-cased property name:

```php
$client->full_name;
```

Reserve an accessor for a derived property, not as a generic replacement for domain behavior. A
reusable operation with behavior or side effects stays a method or an Action, not an Eloquent
attribute.

`Illuminate\Database\Eloquent\Casts\Attribute` supports `make()`, `get()`, and `set()` for accessors
and mutators. Confirm the project's installed Laravel version supports the API before relying on it.

## Local scopes

Every new local scope uses the `#[Scope]` attribute — never the legacy `scope`-prefixed method-name
convention. `laravel-best-practices` already demonstrates the attribute's own mechanics, so this rule
does not re-teach them; the stack's own position is narrower and durable: a model with older,
legacy-named scopes is migration debt, not a pattern to extend, so a new scope on that same model
still uses the attribute rather than matching the model's existing style.

```php
// ✅ every new scope, regardless of what else exists on the model
#[Scope]
protected function filter(Builder $query, QueryFilter $filters): Builder
{
    return $filters->apply($query);
}

// ❌ do not add a new scope this way, even to match an older scope already on the model
public function scopeFilter(Builder $query, QueryFilter $filters): Builder
{
    return $filters->apply($query);
}
```

Requires a Laravel version that provides `Illuminate\Database\Eloquent\Attributes\Scope` — confirm
this class exists in the project's installed `laravel/framework` version before relying on it.
