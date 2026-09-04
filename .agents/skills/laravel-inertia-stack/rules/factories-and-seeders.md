# Factories & Seeders

Conventions for generating realistic fake data in model factories, and for structuring dev-only seeders,
so seeded or demo data looks plausible rather than merely structurally valid.

## Derive dependent fields from one source of truth

Never randomize two fields independently when they have a real-world relationship. Pick the field that
drives the relationship first, then derive the other(s) from it.

✅
```php
$gender = fake()->randomElement(Gender::cases());
$firstName = fake()->firstName($gender->value); // Faker's firstName() accepts 'male'|'female'
```

❌
```php
'first_name' => fake()->firstName(), // gender-agnostic
'gender' => fake()->randomElement(Gender::cases()), // picked independently — can mismatch
```

The same principle applies to any dependent pair — for example a region field constraining which
sub-areas are valid: pick the parent value first, then constrain the child's `randomElement()` to the
set that's valid for it, rather than randomizing both from unrelated pools.

## Build emails from the generated name

Prefer an email derived from the same generated name over Faker's random `safeEmail()`/`freeEmail()`,
rotating across a small pool of realistic domains instead of one hardcoded domain:

```php
$emailDomain = fake()->randomElement(['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com']);

'email' => Str::slug($firstName, '_').'_'.Str::slug($lastName, '_').'@'.$emailDomain,
```

## Chain date fields chronologically

When a factory sets multiple date fields, don't randomize each independently within its own range — a
later date should be derived from, or bounded by, an earlier one, so a seeded record can never describe
an impossible timeline.

✅ *(illustrative — bound the later date's range using the earlier one)*
```php
$hiredAt = fake()->dateTimeBetween('-5 years', 'now');

'hired_at' => $hiredAt,
'probation_ends_at' => fake()->dateTimeBetween($hiredAt, Carbon::parse($hiredAt)->addMonths(3)),
```

❌
```php
'hired_at' => fake()->dateTimeBetween('-5 years', 'now'),
'probation_ends_at' => fake()->dateTimeBetween('-3 months'), // independent range, no relation to hired_at
```

## Relationship scoping: custom state vs. built-in `for()`

When a relationship-scoping array literal repeats across tests, extract a named factory state — but only
when the foreign-key value must be *derived* from another model, not copied directly from one already in
scope.

✅ *(derived — the `Department` isn't in scope, only the `Employee`)*
```php
public function reportingTo(Employee $manager): static
{
    return $this->state(fn (array $attributes): array => [
        'department_id' => $manager->department_id,
    ]);
}
```

❌ *(the related model is already in scope — no custom state needed)*
```php
public function inDepartment(Department $department): static
{
    return $this->state(fn (array $attributes): array => [
        'department_id' => $department->id,
    ]);
}
```

✅ *(use Eloquent's built-in relationship binding instead)*
```php
Employee::factory()->for($department)->create();
```

`for()` infers the relation (`department()`) from the target model's `belongsTo` return type — no extra
factory code is needed for the direct-model case. Reserve a custom state for a genuinely derived value.

When introducing a new state for a repeated pattern, replace every existing call site across the test
suite, not only new ones going forward.

## Seeder structure

- One seeder per concern or model — don't inline unrelated model creation into `DatabaseSeeder` itself.
- Environment gating (`app()->environment('local')`) lives once in `DatabaseSeeder`, wrapping the
  dev-only `$this->call(...)` calls — not repeated inside each individual seeder.
- A seeder that depends on another seeder's data looks it up through existing model relationships (for
  example `Department::query()->firstOrFail()`) rather than threading it through fragile `callWith()`
  parameters.
