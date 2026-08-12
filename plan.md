# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

# Organization Activity Notifications (owner/admin visibility + manual Notify)

## Context

The org now has Owner/Admin/Member roles, but the owner has no visibility into significant things members and admins do — the only owner-facing notification today is `MemberJoinedNotification`, fired once when an invite is accepted.

Governing principle for this revision:

> **Notifications surface events that deserve a person's attention; they are not a complete record of everything that happened.**

This reframes the feature into two parts:

- **Part A — automatic notifications**, narrowed to significant lifecycle/access events only (role changes, removals, archive/unarchive). Resource creation and routine updates are ordinary business activity and are explicitly **not** notified automatically.
- **Part B — a general manual "Notify" capability.** Any user can select one or more active users from their own organization and flag a resource to them with one of three fixed reasons. This replaces the earlier "Notify owner" concept — the actor chooses the recipients, not a fixed leadership audience — and (per the finalized UX) replaces free-text messaging with a constrained reason, not open-ended prose.

Both reuse the existing `EnvelopeNotification` envelope pattern (database + broadcast, consumed by the existing bell/dropdown UI) rather than inventing new plumbing.

## Confirmed behavioral rules

**Automatic (leadership/activity) notifications** — Owner + active Admins, excluding the actor:

- member role changed → **two separate notifications**: a leadership-activity copy (see below) and a personal copy to the affected member.
- member removed → leadership-activity copy only. The removed member is hard-deleted (`RemoveOrganizationMemberAction` calls `$member->delete()`; `User` has no `SoftDeletes`), so no in-app notification to them is possible. Whether they should get an external "your access was removed" email is an explicitly deferred decision — see "Unresolved product decisions" below.
- resource archived / unarchived (Client, Carrier, Agent) → leadership-activity copy.

Additional exclusions on top of "excluding the actor":
- role change: also excludes the affected member from the **leadership** copy (they get their own personal notification instead, not the third-person one).
- member removal: also excludes the removed member (moot for delivery since they're deleted, but keeps the recipient-resolution rule honest).

**Removed from automatic scope** (previously planned, now explicitly out):
- Client/Carrier/Agent **creation** — `ResourceCreatedNotification` is dropped entirely.
- routine resource **updates** — never were in scope, still aren't.
- the leadership copy of `DocumentsUploadBatchProcessedNotification` — the uploader still gets their own copy (unchanged); no second copy to owner/admins.

**Manual Notify** (Part B) — actor-selected recipients, not a fixed audience:
- works on Client, Carrier, Agent, Document.
- recipients: one or more **active** Users in the actor's **own** organization, selected by the actor.
- actor is excluded from the selectable/recipient set (notifying yourself has no value).
- recipient IDs are validated server-side against `OrganizationContext`, not a client-submitted `organization_id` and not `$actor->organization`.
- duplicate submitted recipient IDs are **rejected** by validation (`distinct`), not silently deduplicated — the UI selects each recipient once, so a duplicate ID in the payload is a malformed request, not a normal case to tolerate.
- **finalized: the sender picks exactly one fixed `reason` from a closed set of three — `needs_review`, `for_attention`, `wants_input` — not free text.** There is no custom/free-text option in this version. See D9 for the full rationale, representation, and envelope shape; this supersedes the free-text `message` field from earlier passes of this plan.
- carries the resource as its subject so the recipient can navigate back to it.

**No digest/batching across events** — one notification per event/recipient, accepted as fine for now. (Unrelated to the duplicate-ID point above: this is about not bundling multiple *different* events into one notification, not about recipient-list handling within a single manual Notify.)

## Design decisions

**D1 — One notification class per business meaning**, not one generic parametrized class, not one class per model.

| Class | `ACTION` const | Audience | Fired from |
|---|---|---|---|
| `ResourceArchivedNotification` | `resource.archived` | leadership | Archive{Carrier,Client,Agent}Action |
| `ResourceUnarchivedNotification` | `resource.unarchived` | leadership | Unarchive{Carrier,Client,Agent}Action |
| `MemberRoleChangedNotification` | `member.role_changed` | leadership | ChangeOrganizationMemberRoleAction |
| `YourRoleChangedNotification` | `member.role_changed` | the affected member | ChangeOrganizationMemberRoleAction |
| `MemberRemovedNotification` | `member.removed` | leadership | RemoveOrganizationMemberAction |
| `ResourceMessageNotification` | `resource.message` | actor-selected | `NotifyAction` (Part B, manual) |

`ResourceCreatedNotification` is dropped (see behavioral rules). `DocumentsUploadBatchProcessedNotification` (renamed from `DocumentsUploadBatchProcessed` — naming-consistency cleanup, folded into this pass; see "Existing files to modify" below) is untouched behaviorally — no second leadership copy is added to it; verified against `app/Actions/Documents/FinalizeDocumentsUploadBatchAction.php:56`, which today sends exactly one copy to the uploader.

**Naming cleanup, no behavior change: `DocumentsUploadBatchProcessed` → `DocumentsUploadBatchProcessedNotification`.** Every other class in this feature and the existing `MemberJoinedNotification` follows an `…Notification` suffix; `DocumentsUploadBatchProcessed` (`app/Notifications/DocumentsUploadBatchProcessed.php`) is the one pre-existing exception. Renaming it makes the full inventory consistent:

`MemberJoinedNotification`, `ResourceArchivedNotification`, `ResourceUnarchivedNotification`, `MemberRoleChangedNotification`, `YourRoleChangedNotification`, `MemberRemovedNotification`, `ResourceMessageNotification`, `DocumentsUploadBatchProcessedNotification`.

Class/file rename only — verified nothing about its contract changes: `ACTION` stays `documents.uploaded`, `subject()`/`meta()`/`summary()` bodies are untouched, the actor-nullable/self-notification behavior (`?User $actor = null`, `attributedSummary()`/`selfSummary()` branching) is untouched, `via()` stays `['database', 'broadcast']`, and it keeps going through `ShouldQueue` + `->afterCommit()` exactly as today. The frontend `DOCUMENTS_UPLOADED` action constant, `notificationTypes.ts` registry entry, and `useNotificationsListener.ts` branching are all keyed on the `documents.uploaded` **action string**, not the PHP class name, so none of them change — confirmed by grepping the frontend for the class name: only the TypeScript type alias `DocumentsUploadBatchProcessedData` (`resources/js/types/notification.ts`) shares the old name, and it's a TS-side name with no coupling to the PHP class identifier, so it's explicitly left as-is (renaming it isn't part of this cleanup and isn't required by anything that reads it).

`MemberRoleChangedNotification` and `YourRoleChangedNotification` are two classes, not one class with an audience flag — their payloads and copy genuinely differ (third-person "Jane's role changed" vs. first-person "your role changed"), and `EnvelopeNotification`'s `summary()`/`meta()` contract is naturally per-class. What they share is the `ACTION` value: **both use `member.role_changed`**, not a personal/audience-specific variant like `your_role_changed` or `member.role_changed.personal`. The `action` field names the underlying business event ("a member's role changed"), not who is looking at the notification or which class produced it — a distinct action per audience would model the *viewer* as if it were a different event, which it isn't. `YourRoleChangedNotification` subject kind is `organization`, links to `organization-members.index` (verified viewable by any org member — `OrganizationMemberPolicy::viewAny()` only checks `organization_id !== null`, not privilege — so this is a real, existing destination, not an invented one).

**Verified this doesn't strain the frontend contract.** `notificationTypes.ts`'s registry is `Record<string, NotificationTypeMeta>` keyed by `action`, and each entry only carries an `icon`, `label`, and `resolveUrl` — none of which differ between the leadership and personal copies of a role change (both reasonably use the same icon and the same static `organization-members.index` destination). `NotificationRow.vue:63` renders `data.summary`, which is per-notification-instance data already, not something the registry supplies — so two classes sharing one action key render correctly with a single registry entry; there's no need for a second entry, and inventing one would only exist to route two classes through the registry that behave identically at the registry's level of concern. Nothing about icon resolution, subject-URL resolution, or `useNotificationsListener.ts`'s `data.action` branching depends on action values being 1:1 with notification classes — the listener and registry are already built around "one entry per action," and one action can legitimately represent multiple classes emitting the same kind of envelope. TypeScript's `NotificationEnvelope<TMeta>.action` is typed as `string`, not a per-class literal, so both classes producing `action: 'member.role_changed'` at runtime is not a type conflict — `MemberRoleChangedMeta` and `YourRoleChangedMeta` remain two distinct meta type aliases (their `meta()` payloads still differ — see "New files" below) even though they share one `action` string.

New contract so Client/Carrier/Agent/Document can share notification-subject shaping:

```php
// app/Models/Contracts/NotificationSubject.php
interface NotificationSubject
{
    public function notificationSubjectKind(): string;   // 'client' | 'carrier' | 'agent' | 'document'
    public function notificationSubjectName(): string;
}
```

Verified this is **not** redundant with the existing `Documentable` contract (`app/Models/Contracts/Documentable.php`): only `Client` implements `Documentable` today (Carrier/Agent don't support document attachments at all — confirmed no `documents()` relation on either model), and `Document` itself implements neither `Documentable` nor anything with kind/name. So:
- `Client::notificationSubjectKind()/Name()` delegates to its existing `documentableKind()/documentableName()` (`app/Models/Client.php:112-122`).
- `Carrier`, `Agent` get small new implementations (`name`, `"$first_name $last_name"` respectively — verified both properties exist).
- `Document` gets a new implementation: kind `'document'`, name `original_filename`.

Keeping `NotificationSubject` as its own contract (rather than folding into `Documentable`) is correct — they answer different questions ("can this model have documents attached" vs. "can this model be the subject of a notification").

**D2 — OrganizationContext as the tenant-scope source, actor as attribution only.**

Verified: `App\Support\Tenancy\OrganizationContext` (`app/Support/Tenancy/OrganizationContext.php`) is the app's real tenant-boundary primitive — request-scoped (`$this->app->scoped(...)` in `AppServiceProvider`), populated by `EnsureOrganizationContext` middleware from `$user->organization_id`, and already used for query scoping (`CurrentOrganizationScope`, `CreateCarrierAction`, `FinalizeDocumentsUploadBatchAction`). `User` has **no** automatic tenant global scope of its own (it doesn't use the `BelongsToCurrentOrganization` trait), so any "active users in this org" query must filter explicitly — there's no scope to lean on implicitly, which makes an explicit `OrganizationContext`-driven query the natural, not just principled, choice.

Original plan's `$actor->organization->privilegedRecipients(...)` is replaced. New shape, renamed to say exactly what it does and moved next to the app's existing `OrganizationMembers` domain grouping (mirrors `app/Actions/OrganizationMembers/`, `app/Http/Requests/OrganizationMembers/`, `app/Http/Controllers/OrganizationMembers/` — the naming convention already used for this domain elsewhere in the app):

```php
// app/Support/OrganizationMembers/ActiveOrganizationRecipients.php
final readonly class ActiveOrganizationRecipients
{
    public function __construct(private OrganizationContext $organizationContext) {}

    /** @return Builder<User> */
    public function query(): Builder
    {
        return User::query()
            ->where('organization_id', $this->organizationContext->id())
            ->where('status', OrganizationMemberStatus::Active->value);
    }
}
```

Named `query()` rather than `active()` — Active eligibility is already carried by the class name, so the method just says what it returns. This is the single shared "who is eligible inside the current organization" pool — reused by leadership resolution, the actual manual-Notify recipient retrieval, and the recipient-picker endpoint. It does **not** load an `Organization` model; a `User` query constrained by `organization_id` is simpler, per the product direction. It lives under `App\Support\OrganizationMembers`, not `App\Support\Notifications`, because eligibility is a property of organization membership, not of notifications — notification-specific rules (leadership, and later mentions/assignees) are layered on top of it from `App\Support\Notifications` instead. That split is deliberate:

- **`ActiveOrganizationRecipients`** → who is eligible to receive *anything* inside the current organization.
- **`LeadershipRecipients`** → which eligible members constitute the leadership audience for *a particular class of events*.
- **a notification class** (e.g. `ResourceArchivedNotification`) → *what happened*.
- **`via()`** → *how* the recipient receives it (database, broadcast, and later mail — see D8).

**D3 — Leadership recipient resolution is support/query infrastructure, not a business Action.**

Resolving "who should hear about this" has no mutation and isn't a standalone use case the way creating, archiving, or removing a member is — it's a query concern that sits under `app/Support/Notifications/`, not in `app/Actions/`. Renamed, narrowed to resolution only, and moved:

```php
// app/Support/Notifications/LeadershipRecipients.php
final readonly class LeadershipRecipients
{
    public function __construct(private ActiveOrganizationRecipients $recipients) {}

    /** @return Collection<int, User> */
    public function resolve(User ...$excluding): Collection
    {
        $excludedIds = array_map(fn (User $user): int => $user->id, $excluding);

        return $this->recipients->query()
            ->whereIn('role', [OrganizationRole::Owner->value, OrganizationRole::Admin->value])
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->get();
    }
}
```

`ActiveOrganizationRecipients` defines eligibility; `LeadershipRecipients` applies only the Owner/Admin role filter plus event-specific exclusions on top of it — the two concrete recipient patterns that exist today (leadership, actor-selected), nothing more elaborate. No resolver interface, no registry, no framework. Delivery at each call site is still the plain, idiomatic one-liner — resolution and delivery stay separate:

```php
Notification::send(
    app(LeadershipRecipients::class)->resolve($user),
    (new ResourceArchivedNotification($carrier, $user))->afterCommit(),
);
```

This leaves a natural extension path — a future `PolicyAssigneeRecipients` or billing-recipient rule would sit next to `LeadershipRecipients` under `app/Support/Notifications/`, built on the same `ActiveOrganizationRecipients` pool — without building that abstraction now. `LeadershipRecipients` is not forced onto manual Notify, which intersects actor-selected IDs directly with `ActiveOrganizationRecipients::query()` instead, with no leadership rule involved at all (D4).

**D4 — Manual Notify (Part B)**, general recipient selection instead of a fixed "owner" target, with the tenant boundary enforced twice: once to produce useful validation errors, once again at the point recipients are actually retrieved. (Recipient handling below is unchanged from the prior pass; the `message` field is replaced by `reason` — see D9 for the full rationale.)

```php
// app/Http/Requests/Notifications/NotifyRequest.php
public function rules(): array
{
    return [
        'reason' => ['required', Rule::enum(NotificationReason::class)],
        'recipient_ids' => ['required', 'array', 'min:1'],
        'recipient_ids.*' => [
            'distinct',
            'integer',
            Rule::notIn([$this->user()->id]),
            Rule::exists('users', 'id')
                ->where('organization_id', app(OrganizationContext::class)->id())
                ->where('status', OrganizationMemberStatus::Active->value),
        ],
    ];
}
```

Recipient validation is byte-for-byte unchanged from the prior pass — required, at least one, `distinct` (duplicates rejected, not deduplicated), actor excluded, `OrganizationContext`-scoped, Active-only. Only `message` → `reason` changed, and it's now a closed enum check rather than a length-bounded string. This proves the submitted `recipient_ids` are eligible and the `reason` is one of the three valid values, giving the UI field-level errors. It is deliberately **not** where recipients are actually retrieved for delivery.

```php
// app/Actions/Notifications/NotifyAction.php
final readonly class NotifyAction
{
    public function __construct(private ActiveOrganizationRecipients $recipients) {}

    /** @param array<int, int> $recipientIds */
    public function handle(User $actor, Model&NotificationSubject $subject, array $recipientIds, NotificationReason $reason): void
    {
        $recipients = $this->recipients->query()->whereKey($recipientIds)->get();

        Notification::send(
            $recipients,
            (new ResourceMessageNotification($subject, $actor, $reason))->afterCommit(),
        );
    }
}
```

`NotifyAction` never performs a naked `User::query()->whereKey(...)` lookup, and deliberately does **not** go through `LeadershipRecipients` — manual Notify has no leadership rule to apply, only actor-chosen IDs intersected with eligibility. It re-derives the same tenant-safe, Active-only pool via `ActiveOrganizationRecipients::query()` and intersects it with the already-validated IDs before sending. This is intentional defense in depth: the `FormRequest` proves the submitted IDs are eligible at the HTTP boundary; `ActiveOrganizationRecipients` proves the Users the business operation actually notifies are still constrained to the current organization and Active status at the moment of delivery. The business operation itself never trusts a bare ID list against an unscoped `User` model. None of this — recipient resolution, defense in depth, tenant scoping — changes because of the `message` → `reason` swap; only the fourth parameter's type changed.

This also resolves the tenancy inconsistency flagged in the prior pass: `NotifyRequest` uses `OrganizationContext::id()`, not `$this->user()->organization_id` — matching, not diverging from, the invariant. (`RemoveOrganizationMemberRequest`'s equivalent rule is folded into this same cleanup — see "Existing files to modify".)

**Recipient picker data**: verified no page currently ships org-member data to the Client/Carrier/Agent/Document show pages, and no shared Inertia prop carries it (`HandleInertiaRequests::share()` only shares `auth` + unread count). Rather than thread a members prop through four separate controllers (and repeat it per-row on the documents list), add one small JSON endpoint — this app already has precedent for plain JSON (non-Inertia) endpoints feeding pickers (`app/Http/Controllers/World/StatesController.php`, used by the existing `Typeahead` component). Settled for this implementation: any Active organization member may retrieve this list (mirrors `OrganizationMemberPolicy::viewAny`, which already permits any org member to view `OrganizationMembers/Index.vue`); the requesting actor is excluded from the results.

```php
// app/Http/Controllers/Notifications/NotifiableMembersController.php
final class NotifiableMembersController
{
    public function __invoke(Request $request, ActiveOrganizationRecipients $recipients): JsonResponse
    {
        $members = $recipients->query()
            ->whereKeyNot($request->user()->id)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);

        return response()->json(['data' => $members]);
    }
}
```

Authorized the same way `viewAny` already permits any org member to see `OrganizationMembers/Index.vue`. This endpoint is also the natural seam for a future Notes-mention picker (D6), so it isn't single-purpose scaffolding.

Org sizes here are small (per product direction), so `NotifyModal.vue` renders the fetched list as a plain checkbox list (existing `ui/checkbox` component) — no new multi-select/combobox component. The existing `Typeahead` component is single-select (`modelValue: number|string|null`) and wouldn't fit without changes, so it's left alone.

**D5 — Authorization for manual Notify**: unchanged from the original plan — no new policy methods. "Can Notify about this resource" maps onto the existing `view` ability, verified identical shape across `ClientPolicy`, `CarrierPolicy`, `AgentPolicy`, `DocumentPolicy` (`$model->organization_id === $user->organization_id`). One controller, four typed methods:

```php
// app/Http/Controllers/Notifications/NotifyController.php
#[Authorize('view', 'client')]   public function client(NotifyRequest $r, Client $client, NotifyAction $a): RedirectResponse
#[Authorize('view', 'carrier')]  public function carrier(...)
#[Authorize('view', 'agent')]    public function agent(...)
#[Authorize('view', 'document')] public function document(...)
```

**D6 — Future-proofing for Notes mentions (not implemented now)**: `ActiveOrganizationRecipients::query()` is the seam a future mention-resolution rule plugs into (`resolve mentioned User(s)` → filter to eligible → send via the same `EnvelopeNotification` + `NotifyAction`-style delivery). Nothing here assumes recipients are always Owner/Admin — that assumption lives only inside `LeadershipRecipients`, which mentions will simply not call.

**D7 — Shared infrastructure does not assume a human actor.** Verified against the current code, not just asserted: `EnvelopeNotification`'s constructor already takes `?User $actor = null` (`app/Notifications/EnvelopeNotification.php:25`) — nullable already, no change needed. `ActiveOrganizationRecipients::query()` takes no actor at all. `LeadershipRecipients::resolve(User ...$excluding)` takes actors only as an optional variadic exclusion list — calling `resolve()` with zero arguments is valid and returns the full Owner/Admin pool. `NotificationSubject` has no actor-shaped members. The concrete classes being added now (`ResourceArchivedNotification`, `ResourceUnarchivedNotification`, `MemberRoleChangedNotification`, `YourRoleChangedNotification`, `MemberRemovedNotification`, `ResourceMessageNotification`) do require `User $actor` in their own constructors, because every one of today's events is genuinely human-caused — that's a property of the concrete class, not of the shared envelope/recipient infrastructure. A future scheduler- or webhook-originated notification (e.g. a Policy renewal reminder) would construct its own class calling `parent::__construct(actor: null)` and reuse `ActiveOrganizationRecipients`/a future recipient rule unchanged. No System User, no nullable-actor-everywhere shim, no event-bus abstraction — this already falls out of the existing `EnvelopeNotification` contract as-is. Nothing here is implemented now; this is a verification that the seam exists, not new code.

**D8 — Recipient selection and delivery-channel selection are separate concerns; not implementing email now, but confirming nothing here blocks it.** Recipients answer *who should know*; a notification's `via()` answers *how that meaning reaches them*, and is owned entirely by the concrete notification class — it has nothing to do with `OrganizationContext`, `ActiveOrganizationRecipients`, `LeadershipRecipients`, or `NotificationSubject`. This isn't a proposed pattern; it's how the app already works. Verified two concrete, pre-existing precedents:

- `EnvelopeNotification::via()` (`app/Notifications/EnvelopeNotification.php:32`) returns `['database', 'broadcast']` and is a plain, non-`final` public method — every subclass can override it today without any base-class change.
- `OrganizationInvitationNotification` (`app/Notifications/OrganizationInvitationNotification.php`) already exists as a `mail`-only notification: `via()` returns `['mail']`, it implements `toMail(): MailMessage` with its own subject/body/CTA, and it's dispatched exactly like the envelope notifications — `ShouldQueue` + `Queueable` + `->afterCommit()` (`app/Actions/OrganizationMembers/InviteOrganizationMemberAction.php:45`). Mail, queuing, and post-commit dispatch are not new architecture this feature would introduce; they're an established pattern one class over.

So when a future notification needs `mail` (e.g. a Policy renewal reminder), the concrete class adds `via()` returning `['database', 'broadcast', 'mail']` and its own `toMail(): MailMessage` — using the envelope's `action()/subject()/meta()/summary()` for database/broadcast exactly as today, and a separately-written `toMail()` for the email's subject/body/CTA, which does not need to read the same way as `summary()` (e.g. `summary()` → `"Policy ABC renews in 30 days."`; `toMail()` → a subject line, renewal date, customer context, and a "View policy" action button). Nothing about `OrganizationContext`, `ActiveOrganizationRecipients`, `LeadershipRecipients`, `NotificationSubject`, or any business call site changes when that happens — only the one concrete notification class gains a method. Channel choice stays fixed per notification meaning (e.g. `ResourceArchivedNotification` might reasonably stay in-app-only forever) — there is no global "every automatic notification also emails" rule, and none is being introduced.

Explicitly **not** built in this pass, and not implied by the above: any email notification, any per-user/per-organization channel preference, notification digests or subscriptions, a channel-selection service, or a delivery-rules engine. Those become real work only when a concrete product requirement needs them; today's job is confirming the seam already exists cleanly, not building on it.

**D9 — Manual Notify carries a fixed `reason`, not free text.** Finalized UX: the sender no longer writes prose. They pick exactly one of three closed values:

| Machine value | Option title (RadioCard) | Persisted `summary()` |
|---|---|---|
| `needs_review` | Needs your review | `Needs your review.` |
| `for_attention` | For your attention | `For your attention.` |
| `wants_input` | Would appreciate your input | `Would appreciate your input.` |

No custom/free-text option exists in this version.

*Representation.* Verified the project already uses PHP backed string enums for exactly this shape of problem — `app/Enums/OrganizationRole.php` is a backed enum with a `label(): string` `match` method, cast directly on `User` (`'role' => OrganizationRole::class` in `app/Models/User.php:60`) and validated in requests via `Rule::in(...)`. A backed enum is the smallest correct representation here too — not introduced merely because the prompt suggested one, but because it's already how this codebase represents "a small closed set of string values with a per-case label":

```php
// app/Enums/NotificationReason.php
enum NotificationReason: string
{
    case NeedsReview = 'needs_review';
    case ForAttention = 'for_attention';
    case WantsInput = 'wants_input';

    public function label(): string
    {
        return match ($this) {
            self::NeedsReview => 'Needs your review',
            self::ForAttention => 'For your attention',
            self::WantsInput => 'Would appreciate your input',
        };
    }

    public function summary(): string
    {
        return $this->label().'.';
    }
}
```

`summary()` is `label().'.'` rather than a second `match`, so the persisted notification text can't drift out of sync with the option title by editing one and forgetting the other. Validation uses `Rule::enum(NotificationReason::class)` (see D4) rather than the app's other pattern, `Rule::in(Arr::pluck(SomeEnum::invitableOptions(), 'value'))` (`ChangeOrganizationMemberRoleRequest.php:17`, `InviteOrganizationMemberRequest.php:21`) — that pattern exists specifically because `OrganizationRole::invitableOptions()` excludes `Owner` from what's selectable. `NotificationReason` has no such subset to carve out; every case is always valid, so Laravel's native `Rule::enum()` is the more direct fit and avoids inventing an unneeded `options()`-style static method.

*The three description sentences* ("Best when the recipient should inspect or validate something.", etc.) shown under each RadioCard option are **not** added to the backend enum. Verified precedent: `OrganizationRole`'s equivalent longer descriptions live in a frontend-only constant, `ROLE_DESCRIPTIONS` (`resources/js/pages/OrganizationMembers/partials/organizationMember.ts`), keyed by the same string values the backend enum produces, and consumed in `InviteMemberModal.vue`/`ChangeRoleModal.vue` via `RadioCard`'s `desc` prop — the backend's role options object only ships `{label, value}`. The Notify reason picker follows the same split: the three fixed options (value, title, description) are a small hardcoded frontend constant, not fetched from a backend endpoint or Inertia prop. Unlike invitable roles, these three values have no business-rule variability (nothing ever changes which reasons are selectable), so there's no server-side "options" concept worth exposing at all — hardcoding on both sides (enum server-side, constant client-side) is smaller than threading a static 3-item list through 4+ controllers as a prop.

*Envelope shape.* `ResourceMessageNotification` constructor becomes `(Model&NotificationSubject $subject, User $actor, NotificationReason $reason)`. `meta()` returns `{reason: string (enum value), parent: {kind,slug,name}|null}` — the stable machine value, structured, exactly like `DocumentsUploadBatchProcessedNotification`'s outcome counts or `MemberJoinedNotification`'s member snapshot live in `meta`. `summary()` returns `$this->reason->summary()` — the human-readable rendering, and nothing else; nothing about `EnvelopeNotification`'s `action()/subject()/meta()/summary()` contract changes shape, only what `ResourceMessageNotification` puts into two of those four methods. No generic wrapper text ("Joyce notified you about Acme Insurance") is generated for the persisted summary — the subject already carries "what," the actor already carries "who," so the summary's only job is "why," per the semantic formula this pass finalizes: `subject` → what, `actor` → who, `meta.reason` → structured why, `summary` → readable why, URL → where.

*Realtime toast, verified against the actual current code.* `useNotificationsListener.ts:23` (`const { failed, completed } = data.meta`) is the one place in the app that currently reads `meta` fields to choose toast variant/text, and only for the documents-uploaded action; the plan's already-approved fix (D-unchanged, see "Existing files to modify" → Frontend) retypes it generically and falls back to `toast.info(data.summary)` for every other action. That fallback is now wrong for `resource.message` specifically: `data.summary` there is just `"Needs your review."`, which alone in a toast popup has no "who/what" context (unlike, say, `MemberRoleChangedNotification`'s summary, which is a complete sentence). So the listener plan gains one more action-specific (not reason-specific) branch: for `action === 'resource.message'`, compose the toast client-side from data already in the same envelope — `` `${data.actor?.name ?? 'Someone'} notified you about ${data.subject.name}` `` — and call `toast.info(...)` with that; every other action keeps using `data.summary` directly. This is not a reason-specific toast variant (all three reasons produce the same toast template), it's a single action-specific template, and it requires no backend change — `actor` and `subject` are already broadcast today.

The persisted bell-dropdown row is unaffected by this — it already renders `data.summary` (`NotificationRow.vue:63`), so it correctly shows just `"Needs your review."` under the subject name, matching the finalized dropdown mock (`Acme Insurance` / `Needs your review.` / `Joyce · just now`).

## New files

**Backend**
- `app/Models/Contracts/NotificationSubject.php`
- `app/Enums/NotificationReason.php` — backed string enum, cases `NeedsReview`/`ForAttention`/`WantsInput`, `label()` + `summary()`; see D9
- `app/Notifications/ResourceArchivedNotification.php` — ctor `(Model&NotificationSubject $resource, User $actor)`
- `app/Notifications/ResourceUnarchivedNotification.php` — same shape
- `app/Notifications/MemberRoleChangedNotification.php` — subject kind `organization`; meta `{member:{id,name,email}, from_role, to_role}`
- `app/Notifications/YourRoleChangedNotification.php` — `ACTION = 'member.role_changed'`, the same value as `MemberRoleChangedNotification` (see D1 — one event, two audiences, one action); subject kind `organization`; meta `{from_role, to_role}`; sent directly to the affected member, not through leadership resolution
- `app/Notifications/MemberRemovedNotification.php` — subject kind `organization`; meta `{member:{id,name,email,role}, successor:{id,name}}` (snapshot before delete)
- `app/Notifications/ResourceMessageNotification.php` — ctor `(Model&NotificationSubject $subject, User $actor, NotificationReason $reason)`; `summary()` returns `$reason->summary()`; `meta()` returns `{reason: $reason->value, parent: {kind,slug,name}|null}` (parent set for documents, to link back to the client's documents tab). No free-text field anywhere in this class — see D9. No generic wrapper text ("X notified you about Y") in the persisted `summary`; the subject/actor envelope fields already carry "what"/"who".
- `app/Support/OrganizationMembers/ActiveOrganizationRecipients.php` (replaces `app/Support/Notifications/OrganizationRecipients.php` from the prior pass — renamed and relocated per D2)
- `app/Support/Notifications/LeadershipRecipients.php` (replaces `NotifyPrivilegedMembersAction`; not an Action — see D3; depends on `ActiveOrganizationRecipients`)
- `app/Actions/Notifications/NotifyAction.php` (replaces `NotifyOwnerAction`) — now depends on `ActiveOrganizationRecipients` and accepts `array $recipientIds` and `NotificationReason $reason` rather than a pre-resolved `Collection` and a free-text `string $message`, per D4/D9
- `app/Http/Requests/Notifications/NotifyRequest.php` (replaces `NotifyOwnerRequest`) — `reason` (`Rule::enum(NotificationReason::class)`) + `recipient_ids`/`recipient_ids.*` rules, see D4/D9
- `app/Http/Controllers/Notifications/NotifyController.php` (replaces `NotifyOwnerController`)
- `app/Http/Controllers/Notifications/NotifiableMembersController.php`

**Frontend**
- `resources/js/components/notifications/NotifyModal.vue` (replaces `NotifyOwnerModal.vue`) — dumb reusable modal, `v-model:open`, props `form` (Wayfinder route form) + `subjectLabel: string`. Fetches recipient options from the new JSON endpoint when opened; renders a checkbox list of recipients + a `RadioCard` group of exactly the three fixed reasons (title + description per option, hardcoded locally — see D9) — **no `Textarea`, no free-text/custom option**. Uses `<Form>` + `FormField` — modelled on `resources/js/pages/OrganizationMembers/partials/InviteMemberModal.vue` (canonical `<Form>`-wraps-`<Dialog>` pattern; **no `useForm()`**; `RadioCard` already supports a hidden `name` input for native serialization, same as `InviteMemberModal.vue`'s role picker). Checkboxes serialize as `recipient_ids[]`, the selected `RadioCard` serializes as `reason`, per the project's "custom components carry their own `name` + native form serialization" convention.
- `resources/js/pages/OrganizationMembers/partials/organizationMember.ts` is **not** modified for this — it's referenced only as the precedent (`ROLE_DESCRIPTIONS`) for where the new `NOTIFY_REASON_OPTIONS` frontend constant should live; see next bullet.
- `resources/js/lib/notifyReasons.ts` (new) — `NOTIFY_REASON_OPTIONS: {value, label, desc}[]`, hardcoded to the three fixed reasons (value/label mirroring the backend enum's values and `label()`, `desc` the longer description sentence), consumed by `NotifyModal.vue`'s `RadioCard`. Not fetched from the backend — see D9 for why.

Wayfinder route modules are generated (`php artisan wayfinder:generate` / vite plugin), not hand-written.

## Existing files to modify

**Backend**
- `app/Models/Client.php`, `Carrier.php`, `Agent.php`, `Document.php` — implement `NotificationSubject`
- `app/Actions/Carriers/ArchiveCarrierAction.php`, `UnarchiveCarrierAction.php` — **no signature change** (both already take `User $user` as first arg — verified); add the leadership `Notification::send()` call after the transaction
- `app/Actions/Clients/ArchiveClientAction.php`, `UnarchiveClientAction.php` — same, no signature change
- `app/Actions/Agents/ArchiveAgentAction.php`, `UnarchiveAgentAction.php` — same, no signature change
- `app/Actions/OrganizationMembers/ChangeOrganizationMemberRoleAction.php` — signature becomes `handle(User $actor, User $member, array $attributes): User` (currently `handle(User $member, array $attributes)` — verified no actor param today); capture `$previousRole` before update; send `MemberRoleChangedNotification` to `LeadershipRecipients::resolve($actor, $member)`, and separately send `YourRoleChangedNotification` directly to `$member`
- `app/Actions/OrganizationMembers/RemoveOrganizationMemberAction.php` — signature becomes `handle(User $actor, User $member, User $successor): void` (currently `handle(User $member, User $successor)` — verified no actor param today); snapshot member payload before `$member->delete()`; send `MemberRemovedNotification` to `LeadershipRecipients::resolve($actor, $member)`
- `app/Http/Controllers/OrganizationMembers/OrganizationMembersChangeRoleController.php`, `OrganizationMembersController.php` (`destroy()`) — pass `$request->user()` as new first arg
- `app/Http/Requests/OrganizationMembers/RemoveOrganizationMemberRequest.php` — **tenancy-consistency cleanup, folded into this work**: swap the `reassign_to` exists-rule's `->where('organization_id', $user->organization_id)` for `->where('organization_id', app(OrganizationContext::class)->id())`, matching the same invariant `NotifyRequest` establishes. Verified behaviorally identical today (`OrganizationContext` is populated from `$user->organization_id` by `EnsureOrganizationContext` middleware on every authenticated request), and verified `tests/Feature/Http/OrganizationMembers/DestroyTest.php` already exercises this rule through full HTTP requests (successor-must-be-active-org-member, cross-org rejection, self-removal-as-successor cases all present) — those tests run through the real middleware stack, so no test changes are required, they simply continue to pass against the new implementation.
- `routes/clients.php`, `routes/carriers.php`, `routes/agents.php`, `routes/documents.php` — add `POST .../notify` routes to `NotifyController`
- `routes/notifications.php` — add `GET notify/recipients` → `NotifiableMembersController`
- **Rename** `app/Notifications/DocumentsUploadBatchProcessed.php` → `app/Notifications/DocumentsUploadBatchProcessedNotification.php` (class renamed to match; naming-consistency cleanup only, no contract/behavior change — see D1). Update the one production call site: `app/Actions/Documents/FinalizeDocumentsUploadBatchAction.php` — `use` import and the `new DocumentsUploadBatchProcessed(...)` call site (line 56) both become `DocumentsUploadBatchProcessedNotification`.
- **Rename** `tests/Unit/Notifications/DocumentsUploadBatchProcessedTest.php` → `tests/Unit/Notifications/DocumentsUploadBatchProcessedNotificationTest.php`; update its `use` import and class references inside.
- `tests/Pest.php` — update the `use App\Notifications\DocumentsUploadBatchProcessed;` import and the `'type' => DocumentsUploadBatchProcessed::class` reference (line ~70, inside the shared notification test helper) to `DocumentsUploadBatchProcessedNotification`.

**Not modified** (explicitly, vs. the original plan):
- `app/Actions/Carriers/CreateCarrierAction.php`, `app/Actions/Clients/CreateClientAction.php`, `app/Actions/Agents/CreateAgentAction.php` — no notification hook added; creation stays unnotified.
- `app/Actions/Documents/FinalizeDocumentsUploadBatchAction.php` — no second notification added and no behavior change; the existing single self-copy to the uploader (line 56) is untouched. It does get one mechanical one-line touch as part of this pass — the `use` import and constructor call update from `DocumentsUploadBatchProcessed` to `DocumentsUploadBatchProcessedNotification` (see the rename entry above) — listed here rather than under "no changes needed" only because that edit is purely nominal, not functional.
- `app/Models/Organization.php` — no `privilegedRecipients()` added here; eligibility lives in `app/Support/OrganizationMembers/ActiveOrganizationRecipients.php`, leadership-specific resolution in `app/Support/Notifications/LeadershipRecipients.php` (D2/D3), with delivery-only usage in `app/Actions/Notifications/NotifyAction.php`.

No changes needed to `routes/channels.php`, the `notifications` table, `NotificationResource`, `NotificationsListController`, or `EnvelopeNotification` — every notification is a per-`User` envelope on the existing `App.Models.User.{id}` channel.

**Frontend**
- `resources/js/types/notification.ts` — add `ResourceEventMeta`, `MemberRoleChangedMeta`, `YourRoleChangedMeta`, `MemberRemovedMeta`, and a **structured** `ResourceMessageMeta` + `…Data` envelope aliases. `ResourceMessageMeta` is now `{ reason: 'needs_review' | 'for_attention' | 'wants_input'; parent: NotificationSubject | null }` — no free-text field. `summary` stays the already-resolved human-readable string from the backend on the shared `NotificationEnvelope<TMeta>` shape; the frontend does not reconstruct it from `meta.reason`.
- `resources/js/lib/notificationTypes.ts` — export **5** new action constants and **5** registry entries (not 6 — `MemberRoleChangedNotification` and `YourRoleChangedNotification` share one `member.role_changed` action/registry entry; the 6th new class, `ResourceMessageNotification`, adds its own `resource.message` entry, so 5 distinct actions cover the 6 new notification classes), a `resolveSubjectUrl(subject, meta)` helper mapping `subject.kind` → the right named route (documents via `meta.parent.slug`; `organization` kind for the member-related events resolves statically to `organization-members.index`, matching the existing `MEMBER_JOINED` entry). Icons from `@lucide/vue`: `Archive`, `ArchiveRestore`, `UserCog`, `UserMinus`, `MessageSquare`.
- `resources/js/composables/useNotificationsListener.ts` — **fix latent bug** (unchanged from original plan, still needed): payload is currently typed as `DocumentsUploadBatchProcessedData` unconditionally and destructures `meta.failed/completed` regardless of action, so e.g. `member.joined` accidentally falls into `toast.success`. Retype to `NotificationEnvelope<unknown>` and branch on `data.action`; keep the failed/completed toast only for the documents-uploaded action. **One more branch, added in this pass**: for `action === 'resource.message'`, compose the toast from `` `${data.actor?.name ?? 'Someone'} notified you about ${data.subject.name}` `` rather than `data.summary` — `summary` there is now just the reason ("Needs your review."), which lacks who/what context as a standalone toast; every other action still uses `toast.info(data.summary)` unchanged. This is one action-specific template, not a per-reason variant — all three reasons produce the same toast shape. See D9 for the verification against the current file.
- `resources/js/pages/Clients/partials/ClientShowHeader.vue`, `Carriers/partials/CarrierShowHeader.vue`, `Agents/partials/AgentShowHeader.vue` — add a "Notify" button (Bell icon, secondary variant) placed alongside the existing Edit/Export actions + local `ref` + `<NotifyModal>`. No separate create-success page or flow change — creating a resource still redirects to its normal Show page with the existing success toast, and Notify is simply available there like any other Show-page action whenever needed.
- `resources/js/components/documents/DocumentRow.vue` — add a `notify` emit + Bell action button beside the existing delete button
- `resources/js/pages/ClientDocuments/Index.vue` — hold the `documentToNotifyAbout` ref, wire `@notify`, render `NotifyModal`

## Phased delivery

1. **Recipient plumbing** (no user-visible change): `NotificationSubject` contract + implementations, `ActiveOrganizationRecipients` (`app/Support/OrganizationMembers/`), `LeadershipRecipients` (`app/Support/Notifications/`, depends on the former) — neither is an Action — plus the `RemoveOrganizationMemberRequest` tenancy cleanup (independent of everything else, safe to land first). Fully testable in isolation.
2. **Part A automatic notifications**: 5 notification classes (`ResourceArchivedNotification`, `ResourceUnarchivedNotification`, `MemberRoleChangedNotification`, `YourRoleChangedNotification`, `MemberRemovedNotification`) + 6 archive/unarchive call sites (no signature changes needed) + the 2 member-action signature changes + frontend registry/type/listener updates.
3. **Part B manual notify — client/carrier/agent**: `NotificationReason` enum, `ResourceMessageNotification`, `NotifyAction`, `NotifyRequest`, `NotifiableMembersController`, `NotifyController` (3 of 4 methods), 3 notify routes + the recipients route, `notifyReasons.ts`, `NotifyModal.vue` (checkbox recipients + `RadioCard` reasons) + 3 show-header buttons.
4. **Part B manual notify — documents**: 4th controller method + route + `DocumentRow.vue`/`ClientDocuments/Index.vue` wiring (split out since documents are the only subject needing the `meta.parent` URL-resolution path and a per-row UI affordance).

## Testing

- **Support/unit tests**: new `tests/Unit/Support/OrganizationMembers/ActiveOrganizationRecipientsTest.php` — `query()` returns only active users scoped to the current `OrganizationContext`, excludes invited/suspended, excludes other organizations. New `tests/Unit/Support/Notifications/LeadershipRecipientsTest.php` — returns owner+admins, excludes members, excludes invited/inactive, excludes passed-in `$excluding` users, excludes other organizations' users. (Replaces the originally-planned `OrganizationTest::privilegedRecipients()` extension — that method no longer exists.)
- **Notification payload tests** (new, one file per class under `tests/Unit/Notifications/`): clone `tests/Unit/Notifications/MemberJoinedNotificationTest.php` — assert `via()`, full `toArray()`, `toBroadcast()->data`, `ACTION` const. Six files: the five automatic classes + `ResourceMessageNotification`. `MemberRoleChangedNotificationTest` and `YourRoleChangedNotificationTest` both assert `ACTION === 'member.role_changed'` — intentionally identical, asserting the shared-action decision rather than accidentally masking a divergence; each still asserts its own distinct `summary()`/`meta()` shape. `ResourceMessageNotificationTest` is now a dataset over the three `NotificationReason` cases: for each, assert `meta()['reason']` equals the case's stable string value and `summary()` equals its exact human-readable text (`'Needs your review.'` / `'For your attention.'` / `'Would appreciate your input.'`), and separately assert the existing Document-parent metadata case (`meta()['parent']` populated for a Document subject, `null` otherwise) still holds. For every one of the six: assert `via()` still returns exactly `['database', 'broadcast']` — no test should assume `mail` is or ever will be part of the fixed set for these particular classes.
- **`NotificationReasonTest`** (new `tests/Unit/Enums/NotificationReasonTest.php`, if the project's existing enum test conventions cover simple backed enums — otherwise fold into `ResourceMessageNotificationTest`'s dataset above): each case's `label()` and `summary()` produce the exact three specified strings.
- **`NotifyAction` test** (new `tests/Unit/Actions/Notifications/NotifyActionTest.php`): the defense-in-depth case the `FormRequest` can't cover by itself — call `NotifyAction::handle()` directly with a recipient ID belonging to another organization (or an inactive/suspended user in the same org) mixed into an otherwise-valid ID list, and assert that user does **not** receive the notification while the valid ones do. Also assert the passed `NotificationReason` case flows through unchanged into the sent `ResourceMessageNotification`'s `meta()['reason']`. This is what actually proves `ActiveOrganizationRecipients` is enforced at the point of delivery, not just in the HTTP layer.
- **Action tests**: extend existing `tests/Unit/Actions/{Carriers,Clients,Agents}/{Archive,Unarchive}*ActionTest.php` (these actions and their tests already exist — verify current coverage, then add `Notification::fake()` assertions: sent to owner+other-admin, **not** sent to the acting admin, not sent to a plain member). Extend `ChangeOrganizationMemberRoleActionTest`/`RemoveOrganizationMemberActionTest` for the new `$actor` param and both notification sends (leadership + personal for role change).
- **Controller test** (new `tests/Feature/Http/Notifications/NotifyTest.php`): HTTP contract only — guest redirect, `assertForbidden()` for cross-org resource (one per subject type), `assertInvalid(['reason'])` for missing reason and for an invalid/unknown value (replaces the old empty/>500-char message cases — those no longer apply, there's no length-bounded string field), a dataset happy-path case per one of the three valid reasons asserting `assertRedirect()`, `Notification::fake()` — exactly the selected recipients notified, the sent notification's `action` is `resource.message`, `meta()['reason']` matches the selected value, and `summary()` matches that reason's exact human-readable text. Recipient validation cases (`assertInvalid(['recipient_ids'])` for empty array / cross-org ID / suspended-user ID / actor's own ID / a duplicate ID within the array) are unchanged from the prior pass.
- **Controller test** (new, for the recipients endpoint): guest rejected, returns only active same-org users, excludes the requesting actor.
- **`RemoveOrganizationMemberRequest` cleanup**: no new test needed — `tests/Feature/Http/OrganizationMembers/DestroyTest.php` already covers successor-must-be-active-org-member, cross-org rejection, and self-removal-as-successor through full HTTP requests; run it after the change to confirm it still passes unchanged.
- **Tests that will break and need updating**: `ChangeOrganizationMemberRoleActionTest`, `RemoveOrganizationMemberActionTest` (new first argument), `OrganizationMembersChangeRoleControllerTest`/equivalent (controller now passes `$request->user()`), and any action test that now fires a real notification without `Notification::fake()`.
- Run `php artisan test --compact --filter=<Name>` per changed area, then `vendor/bin/pint --dirty --format agent` before finishing.

## Verification

- Run the full new/updated test suite: `php artisan test --compact --filter=Notif` and per-action filters listed above.
- Manually: as an admin, archive a client → confirm the owner's bell shows the notification and a toast fires in real time (Reverb must be running: `php artisan reverb:start` or via `composer run dev`) when logged in as owner in a second session. As a member, change another member's role and confirm both the leadership copy (to owner/other admins) and the personal copy (to the affected member) render correctly and distinctly. Send a manual Notify picking "Needs your review" and confirm: the modal shows the `RadioCard` reason picker (no textarea), the recipient's bell row shows the resource name with `Needs your review.` beneath it and the actor/timestamp line, and the realtime toast shows the shorter `"<actor> notified you about <subject>"` composition rather than the bare reason. Ask the user to verify the UI in-browser since this session cannot drive a live browser.

## Unresolved product decisions

1. **Removed-member email**: explicitly deferred per product decision — not building it now, flagging so it isn't silently forgotten. This remains the only deliberately deferred product question; every other item raised in the prior pass (self-notify exclusion, recipient-picker visibility, `RemoveOrganizationMemberRequest` tenancy) is settled and folded into the plan above.

