# Request Normalization

## Purpose

Where to put input coercion and defaulting for request params — `prepareForValidation()` on the
`FormRequest`, not `??` fallbacks or casts scattered across the controller.

## Why the FormRequest, not the controller

Actions and Filters (see `blueprints/filters-and-sorting.md`) trust `$request->validated()` as already
correct. If the controller has to fall back with `??` or cast with `$request->boolean(...)` after calling
`validated()`, the FormRequest didn't finish its job — an ambiguous shape leaked past the boundary it's
supposed to own.

```php
// ❌ Normalization leaking into the controller
$data = $request->validated();
$flag = $request->boolean('some_flag');
$order = $data['order'] ?? 'asc';
```

```php
// ✅ FormRequest normalizes once; controller trusts validated() as-is
protected function prepareForValidation(): void
{
    $this->merge([
        'some_flag' => $this->boolean('some_flag'),
        'order' => $this->input('order') ?? 'asc',
    ]);
}
```

Once a field is unconditionally coerced this way, drop `nullable` from its rule — it can no longer
actually be null.

## Two independent `??` fallbacks for the same value is a smell

If a default is computed once for driving a query and again, slightly differently, for what gets echoed
back to the frontend, those two formulas can silently drift out of sync on a future edit. Compute the
default once in `prepareForValidation()` so every consumer reads the same `validated()` value.

## Check the frontend contract before merging a default

Merging a default into `prepareForValidation()` changes what "absent" looks like by the time it reaches
`validated()` — e.g. a boolean field becomes `false` instead of `null` when the request omits it. That's
only safe if every frontend consumer of that prop treats `false` and `null` as equivalent; a strict
`=== null` check downstream would break. Verify before applying this pattern to a field whose "unset"
state currently matters.

## Reading validated data: use `validated($key, $default)`, don't destructure

`FormRequest::validated()` accepts a key and default (`data_get($this->validator->validated(), $key,
$default)`), so there's no need to assign the whole array to a variable just to re-add `?? null` per
field.

```php
// ❌ Destructure into a local array, then re-default each read
$filters = $request->validated();
$search = $filters['search'] ?? null;
```

```php
// ✅ Read each field where it's used
$search = $request->validated('search');
```

Still call `$request->validated()` (no key) when a whole array is needed as-is, e.g. passing it straight
into a Filter (see `blueprints/filters-and-sorting.md`) — the point is to stop hand-rolling per-field
defaults the accessor already does.
