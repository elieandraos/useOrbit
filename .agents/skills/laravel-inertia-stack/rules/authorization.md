# Authorization Pattern

Requires a Laravel version that ships `Illuminate\Routing\Attributes\Controllers\Authorize` — confirm
this class exists in the project's installed `laravel/framework` version before relying on this pattern,
the same way `eloquent-attributes.md` asks for `Illuminate\Database\Eloquent\Attributes\Scope`.

## Use `#[Authorize]` attributes

Authorize a controller method with the `#[Authorize]` PHP attribute — never call `$this->authorize()`
inline.

```php
use Illuminate\Routing\Attributes\Controllers\Authorize;

#[Authorize('viewAny', Project::class)]
public function index(Request $request): Response { ... }

#[Authorize('create', Project::class)]
public function store(StoreProjectRequest $request, CreateProjectAction $action): RedirectResponse { ... }
```

The attribute registers authorization as route middleware, so it runs before the method body executes —
no imperative call is needed inside the method.

## Attribute signature

```php
#[Authorize(ability: string|UnitEnum, models: string|array|null = null)]
```

- `ability` — the policy method name (for example `'viewAny'`, `'create'`) or a `UnitEnum` value.
- `models` — the model class string for a resource-level check, or `null` for a gate-only check.
- The attribute is repeatable — stack more than one on the same method when a route needs more than one
  check.

## Never add `AuthorizesRequests` to the base `Controller`

That trait exists to support the imperative `$this->authorize()` call. The attribute-based approach does
not need it.

```php
// correct
abstract class Controller {}

// wrong — do not add this trait when using #[Authorize]
abstract class Controller
{
    use AuthorizesRequests; // ❌
}
```

## Authorizing `create`/`viewAny` on a child resource

`view`/`update`/`delete` authorize against an **already-bound model instance**: the route parameter
resolves to the real object before the attribute runs, and Laravel infers the policy from that object's
class automatically.

```php
#[Authorize('update', 'task')]
public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse { ... }
```

`create`/`viewAny` have **no instance to infer a policy from** — nothing exists yet. Passing only the
parent's route parameter (`'project'`) would authorize against the *parent's* policy, not the child's.
Name the child's policy class explicitly and pass the parent as the second argument:

```php
#[Authorize('create', [Task::class, 'project'])]
public function store(StoreTaskRequest $request, Project $project, CreateTaskAction $action): RedirectResponse { ... }
```

```php
final class TaskPolicy
{
    public function create(User $user, Project $project): bool
    {
        return $project->members()->whereKey($user->id)->exists();
    }
}
```

Why the array form is required: the `Authorize` attribute treats a string containing `\` as a literal
class name — used to resolve the policy, then stripped before the ability method is called — and
resolves any other string as a route parameter. `[Task::class, 'project']` resolves to
`["App\Models\Task", $projectInstance]`: the class-name string selects `TaskPolicy`, and the resolved
`$project` model is what actually reaches `create()`. `#[Authorize('create', Task::class)]` alone would
call `create($user)` with no parent argument at all — either an `ArgumentCountError`, or worse, a check
that never inspects which parent is involved.

## Testing

See `test-ownership.md` for what a Policy test asserts and where it lives. A controller test confirms
the gate is wired up with a single `assertForbidden()` — it does not duplicate the Policy test's full
permission matrix.
