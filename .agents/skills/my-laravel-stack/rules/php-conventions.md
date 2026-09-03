# PHP Conventions

## Final-by-default application classes

Declare a concrete application class `final` by default when it is not designed for inheritance. Leave a
class extensible only when it represents an intentional extension seam, or a verified framework,
proxying, or tooling requirement prevents `final`.

**Applies to:** Controllers, Actions, Resources, Filters, Sorters, Policies, Form Requests, Models, and
other concrete application classes.

**Does not apply to:**

- **Abstract classes** — designed for extension by definition. `QueryFilter` and `QuerySorter` (see
  `blueprints/filters-and-sorting.md`) stay non-`final`.
- **Interfaces, traits, and enums** — finality doesn't apply to these constructs.

**This is a default, not an absolute:**

- A class with a real extension seam stays open — this rule does not forbid deliberate inheritance.
- A verified framework or tooling requirement overrides it.

Model `final class` in every concrete-class code example in this skill's other files, unless the example
specifically illustrates an extension seam.

```php
// ✅ concrete, no extension seam
final class CreateOrderAction { ... }

// ✅ abstract, designed for extension — not final
abstract class QueryFilter { ... }
```
