# Actions

## Purpose

Business logic and mutations live in an **Action**, not the controller or the Form Request. See
`blueprints/resource-controller.md` for how an Action fits between the Form Request and the Resource in
the full controller composition — this file owns only the Action's own shape.

## Naming and location

- Place actions in `app/Actions/{Domain}/` — for example `app/Actions/Orders/`.
- Name each action `{Verb}{Model}Action` — for example `CreateOrderAction`, `UpdateOrderAction`,
  `ArchiveOrderAction`.
- The action's entry method is always named `handle()`.

## Action structure

```php
final class CreateOrderAction
{
    /**
     * @param  array{customer_id: int, notes?: string|null}  $attributes
     */
    public function handle(User $user, array $attributes): Order
    {
        return DB::transaction(function () use ($user, $attributes): Order {
            return Order::query()->create([
                ...$attributes,
                'created_by' => $user->id,
            ]);
        });
    }
}
```

`customer_id` is typed `int` here only because the corresponding Form Request's `prepareForValidation()`
normalizes it explicitly (see "Data payload convention" below and `request-normalization.md`) — the
`integer` validation rule alone would not justify that type.

## No HTTP concerns inside an Action

An Action must never resolve its own state by calling `request()`, `Auth::user()`, or any other
HTTP-layer global inside `handle()`. The controller resolves the current user, a route-bound model, or
any other value it has access to, and passes it into the Action as a plain, already-resolved argument
(a `User`, a `Model`, a scalar, a validated array — never a live request or auth facade call). An action
built this way can also run unchanged from a console command, job, or listener, none of which have an
HTTP request to resolve state from.

## Data payload convention

- Name the validated-array parameter `$attributes` — not `$data`, `$input`, or `$payload`.
- Add a `@param array{...}` PHPDoc shape on `handle()`. Derive it from two independent things: which
  keys the Form Request's `rules()` guarantees will be present, and what runtime type each key's value
  actually has by the time `validated()` returns it — not from a fixed rule-to-type table, because a
  validation rule constrains a value; in general it does not cast it.

### Key presence

- A `required` or `present` rule guarantees the key exists in `validated()` whenever validation
  succeeds — the request never reaches `handle()` otherwise.
- A field with no presence-requiring rule is normally optional (`?`) in the shape: it may be absent
  entirely.
- `sometimes`, a conditionally applied rule (`Rule::when()`, `required_if`, and similar), and an
  exclusion rule (`exclude_unless`, `exclude_if`, and similar) can also make presence conditional even
  when the field's rule set elsewhere looks like `required` — base the shape on what actually controls
  presence for that specific field, not merely on whether the word `required` appears in its rule list.

### Nullability

`nullable` controls only whether a *present* value may be `null` — it says nothing about whether the key
exists at all. A required, nullable field is always present but may be `null` (`key: string|null`); a
present, non-nullable field is guaranteed not to be `null` when it appears. Presence and nullability
combine independently: `key: string`, `key: string|null`, `key?: string`, and `key?: string|null` are
four distinct, individually valid shapes depending on the field's actual rules.

### Type

A validation rule constrains the value; most rules do not cast it. `integer` validates that the input is
integer-like — it does not itself guarantee the value is a PHP `int` by the time `validated()` returns
it. The same applies to `boolean` (does not itself guarantee PHP `bool`), `numeric`/`decimal` (does not
itself guarantee `int|float`), and an enum rule such as `new Enum(OrderStatus::class)` (validates that
the value matches one of the enum's backing values — it does not transform the value into an
`OrderStatus` instance).

Type each key from the actual value the Action receives, not from the validation rule that merely
checked it:

- If the Form Request's `prepareForValidation()`, or any other explicit transformation, casts or
  replaces the value, type it as that transformation's real output (see `request-normalization.md` for
  where that coercion belongs).
- If nothing normalizes the field, represent its real accepted runtime type instead of a narrower type
  the validation rule doesn't guarantee — for most HTTP input this is `string`, even behind an `integer`,
  `boolean`, or `numeric` rule.
- Use a narrow scalar or enum type (`int`, `bool`, `OrderStatus`) in the shape only when a normalization
  step genuinely produces it. Don't infer that type from the rule name alone.

## Reusing an Action from another Action

Inject it via the constructor — Laravel resolves the dependency automatically:

```php
final class CreateOrderAction
{
    public function __construct(private readonly AssignDefaultPricingAction $assignPricing) {}

    public function handle(User $user, array $attributes): Order
    {
        $order = DB::transaction(fn (): Order => Order::query()->create([
            ...$attributes,
            'created_by' => $user->id,
        ]));

        $this->assignPricing->handle($order);

        return $order;
    }
}
```

## Keep external side-effect execution out of the transaction; defer it with `afterCommit()`

`DB::transaction()` may contain whatever reads, locks, and writes the atomic unit of work actually
needs — a transaction is not limited to a single write. What must not happen inside it is the actual
*execution* of an external side effect: sending mail synchronously, calling another service, or a queued
job actually running. Invoking `dispatch()` chained with `->afterCommit()` from inside the transaction is
safe, and often the natural place for it: `afterCommit()` does not push the job onto the queue
immediately — it registers the dispatch to run only once the outermost active transaction commits.

```php
$order = DB::transaction(function () use ($attributes): Order {
    $order = Order::query()->create($attributes);
    $order->lineItems()->createMany($attributes['line_items']);

    SendOrderConfirmation::dispatch($order)->afterCommit();

    return $order;
});
```

The `dispatch()->afterCommit()` call itself executes inside the transaction — it only registers a
deferred callback. The actual queue push, and the job's later execution by a worker, both happen only
after the transaction commits. If the transaction rolls back, the registered callback is discarded and
the job is never enqueued at all.

If a dispatch is only ever invoked after every enclosing transaction has already committed,
`afterCommit()` adds nothing. Chain it whenever the dispatching code could still be running inside a
transaction — including one opened by a caller the Action doesn't control — since `handle()` generally
has no reliable way to know that from the inside.

| Scenario | Without `afterCommit()` | With `afterCommit()` |
|---|---|---|
| A worker picks up the job before the transaction commits | It runs against data that isn't committed yet, or that doesn't yet exist from its own connection's point of view | Not possible — the job isn't enqueued until after the commit |
| The transaction rolls back after the job was already enqueued | The job remains queued and will still run — against data that was rolled back and no longer exists | The dispatch was never enqueued — nothing runs |

The `database` queue driver is a partial exception to the table above, but only when its configured
queue connection is the same database connection participating in the application transaction. In that
configuration, the uncommitted job row is normally invisible to other connections until commit, and a
rollback removes it along with everything else the transaction wrote — so the caveat applies to both
rows in the table: a worker generally can't pick the job up before commit, and rollback discards it. If
the database queue driver is configured to use a different connection than the one running the
transaction, its enqueue is independent of the application transaction — the same as Redis, SQS, and
similar drivers for these purposes — and the table's stated risks apply in full. Either way, projects
should still use `afterCommit()` rather than relying on queue-driver or connection-specific behavior to
get this right.

## Testing

See `test-ownership.md` for what an Action test asserts and where it lives.
