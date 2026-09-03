# Migrations

`laravel-best-practices`'s `migrations.md` owns general migration guidance (generation, foreign keys,
immutability, indexing, staged changes to existing rows, rollback honesty). This file states one narrow,
verified correctness point it doesn't cover: column nullability.

## Schema columns are non-nullable by default

Add `nullable()` only when `NULL` is a genuinely valid value for the column:

```php
$table->string('status'); // NOT NULL by default
$table->string('cancelled_reason')->nullable(); // NULL is a valid value here
```

## Do not use `->notNull()` — it is silently ignored

`->notNull()` is not a real Laravel schema modifier. `ColumnDefinition` accepts any method name
dynamically and stores it as a plain attribute; every installed schema grammar reads only the `nullable`
attribute to decide whether to emit `NULL`/`NOT NULL`, and none of them reads a `notNull` attribute.
Calling `->notNull()`:

- does not throw — PHP's dynamic call resolution accepts it silently;
- has zero effect on the generated SQL — no grammar ever reads it;
- misleadingly suggests a real modifier exists, when omitting `nullable()` (or explicitly calling
  `->nullable(false)`) already produces the same `NOT NULL` column.

```php
// ❌ accepted by PHP, ignored by every schema grammar — has no effect
$table->string('status')->notNull();

// ✅ already NOT NULL by default — no modifier needed
$table->string('status');
```

This applies uniformly across the MySQL, SQLite, PostgreSQL, and SQL Server schema grammars — verified
against all four.
