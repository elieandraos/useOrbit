# Enum Options

A backed enum used as select or filter options exposes a static `all()` method, instead of controllers
or tests mapping `::cases()` inline at each call site.

```php
// ✅ the enum owns the mapping
enum Priority: string
{
    case Low = 'low';
    case High = 'high';

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function all(): array
    {
        return array_map(fn (self $case) => [
            'label' => $case->label(),
            'value' => $case->value,
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::High => 'High',
        };
    }
}

// Controller
'priorities' => collect(Priority::all()),

// ❌ do not inline the mapping at call sites
'priorities' => collect(Priority::cases())->map(fn ($case) => ['label' => $case->label(), 'value' => $case->value]),
```

- `all()` returns a plain `array`, not a `Collection` — the enum itself has no dependency on
  `Illuminate\Support\Collection`. A caller wraps it in `collect()` only if it needs collection methods.
- Replace every `::cases()`-mapping call site for that enum (index, create, edit pages, and tests) when
  introducing `all()` — don't leave some call sites inlined and others using it.
- Use the name `all()` consistently across every enum that adopts this pattern, rather than mixing
  `all()`, `options()`, and `toArray()` for what is otherwise the same shape.
