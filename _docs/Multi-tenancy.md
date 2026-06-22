# Organizations — Business Model Reference

> This document captures the design decisions behind useOrbit's multi-tenant organization model for future reference.

---

## Multi-tenancy Model

Each **Organization** is an isolated tenant. Every scoped resource (clients, policies, etc.) carries an `organization_id` foreign key, ensuring records never bleed across org boundaries.

Users belong to one or more organizations through the `organization_user` pivot table. The `users.current_organization_id` column tracks which organization is active for the current session — all queries and authorization checks are scoped against this value.

---

## Ownership Model

The `organizations` table has **no `owner_user_id` column**. Ownership is determined dynamically from the `organization_user` pivot where `role = 'owner'`. This avoids denormalization and allows ownership to transfer without a schema change.

The `Organization` model exposes an `owner()` helper (`wherePivot('role', 'owner')->first()`) for convenience.

---

## Roles & Member Statuses

**OrganizationRole** (`Owner | Member`):

| Action | Owner | Member |
|---|---|---|
| View | ✓ | ✓ |
| Create | ✓ | ✓ |
| Update | ✓ | ✓ |
| Delete | ✓ | ✗ |

**MemberStatus** (`Active | Invited | Suspended`):

| Status | App Access |
|---|---|
| `Active` | Full access |
| `Invited` | Blocked — pending acceptance |
| `Suspended` | Blocked — access revoked |

Only `Active` members pass the organization context middleware.

---

## Registration Flow

When a user signs up, `app/Actions/Fortify/CreateNewUser.php` runs inside a `DB::transaction`:

1. Creates an `Organization` named `"{User Name} Brokerage"`
2. Creates the `User` with `current_organization_id` pointing to the new org
3. Attaches the user to `organization_user` with `role = owner`, `status = active`, `joined_at = now()`

Every new signup automatically becomes the Owner of their own organization. There is no separate org-creation UI in v1. The entire flow rolls back if any step fails.

---

## Authorization Strategy

### Middleware — `EnsureOrganizationContext`

Runs after `auth` on all authenticated routes. Redirects if:

- `user->current_organization_id` is null
- The user's membership in that org is not `status = active`

### Policies — `ClientPolicy`

Enforces record-level access. Every method asserts:

```php
$client->organization_id === $user->current_organization_id
```

This prevents cross-org data access regardless of role. The `delete` method additionally requires `$user->organizationRole() === 'owner'`.

---

## Real-World Scenarios

### Scenario A — Solo broker (John)

John signs up. The system auto-creates **"John Doe Brokerage"** and assigns him as Owner with Active status. He is the sole user; he has full CRUD on all clients including delete.

### Scenario B — John invites his mom

John (Owner) invites his mother as a Member. The system creates an `organization_user` row with `role = member`, `status = invited`, and `invited_by = John's user ID`. Once she accepts, `status` flips to `active`.

His mom can view, create, and update clients — but cannot delete them. `ClientPolicy::delete` blocks any user whose `organizationRole()` is not `owner`.

### Scenario C — Jake runs a small firm (10–20 employees)

Jake registers ("Jake's Insurance Firm" org) and invites Employee A and Employee B as Members. All three share the same organization and see the same client pool scoped to `organization_id`.

Jake (Owner) can delete clients. Employees A and B cannot. If Jake suspends an employee, their `status` is set to `suspended` and the middleware blocks them on the next request — no logout or session invalidation needed.

---

## Future: Granular Resource Permissions

The current model is **binary** — Owner vs. Member, applied uniformly to all resource types. As the product grows, common needs will include:

### Per-resource-type access
_"Employee A can access Clients and Policies but not Companies."_

The single `role` column cannot express this. Options:
- A `permissions` JSON column on `organization_user` (e.g., `{"companies": false, "policies": true}`)
- A separate `organization_user_permissions` table with `(resource_type, can_view, can_create, can_update, can_delete)` rows per member

**Recommended path**: add an opt-in `permissions` JSON column to `organization_user`. Policies fall back to the role default when the column is null — backward-compatible, no migration required for existing rows.

### Per-record access
_"Employee A should only see the 20 clients assigned to them."_

Requires either:
- An `assigned_to` FK on `clients` (simple, single assignee)
- A `client_user` pivot table (many assignees per client)

### Tiered roles
_"A Manager who can delete records but cannot invite or manage members."_

Add a `Manager` value to the `OrganizationRole` enum and update policies and middleware guards accordingly.

---

## Data Model Summary

| Table | Purpose |
|---|---|
| `countries` | Reference data (Lebanon seeded in v1) |
| `organizations` | Tenant record |
| `organization_user` | Pivot: membership, role, status, invite tracking |
| `clients` | Core scoped resource |
| `users` | Extended with `current_organization_id` |

**Key design decisions:**

- `clients.slug` is the route key — prevents ID enumeration in URLs
- Enums stored as `varchar`, not native DB enums — easier to extend without migrations
- `clients.updated_by` is null at creation; only set after the first edit
- `organization_user.invited_by` records who sent the invite for audit purposes
- Compound indexes on `(organization_id, status)` and `(organization_id, last_name, first_name)` for efficient org-scoped queries