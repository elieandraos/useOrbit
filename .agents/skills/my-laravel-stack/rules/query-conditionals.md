# Query Conditionals

Prefer the query builder's `when()` over an `if` block for conditionally applying a clause mid-chain.

```php
// ✅ when() — stays part of the fluent chain
#[Scope]
protected function inPriceRange(Builder $query, ?float $min, ?float $max): Builder
{
    return $query
        ->when($min !== null, fn (Builder $query): Builder => $query->where('price', '>=', $min))
        ->when($max !== null, fn (Builder $query): Builder => $query->where('price', '<=', $max));
}

// ❌ if — breaks the fluent chain, needs an extra bare `return $query` at the end
if ($min !== null) {
    $query->where('price', '>=', $min);
}

if ($max !== null) {
    $query->where('price', '<=', $max);
}

return $query;
```

- Applies to conditionally adding a clause to an existing query builder chain (`Builder`, a relation
  query, and similar) — not to unrelated control flow such as early returns, guard clauses, or branching
  into different logic.
- If the condition needs both a truthy and a falsy branch, `when()` takes a third closure argument (the
  "default" branch) instead of an `if`/`else`.
