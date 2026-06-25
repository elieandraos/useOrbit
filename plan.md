# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

## ClientsController — TDD via GitHub Issues

### Shared Infrastructure (Issue 1)
- `routes/clients.php` — new file, `require`d from `web.php` inside `['auth', 'verified', 'organization']` middleware group
- `app/Http/Controllers/ClientsController.php` — thin controller scaffold, methods added incrementally
- Route binding uses `{client:slug}` (model returns `'slug'` from `getRouteKeyName()`)

### Issue 1 — Clients: list all clients
**Route:** `GET /clients` → `clients.index`  
**Controller:** `ClientsController@index` — authorizes via `viewAny`, paginates clients scoped to `$user->current_organization_id`  
**Inertia page:** `resources/js/pages/Clients/Index.vue` — minimal: app shell + `<pre>` rendering `clients` prop

**Test file:** `tests/Feature/Http/Clients/ClientIndexTest.php`
- Guest is redirected to login
- Authenticated user gets 200 with only their org's clients in props
- Clients from another org are not included

---

### Issue 2 — Clients: create a new client
**Routes:** `GET /clients/create` → `clients.create`, `POST /clients` → `clients.store`  
**FormRequest:** `app/Http/Requests/Clients/StoreClientRequest.php`  
**Action:** `app/Actions/Clients/CreateClientAction.php` — `handle(User $user, array $attributes): Client`; sets `organization_id`, generates slug, sets `created_by` + `updated_by`, `DB::transaction()`  
**Inertia page:** `resources/js/pages/Clients/Create.vue` — minimal: app shell + `<pre>` for props

**Action test:** `tests/Feature/Actions/Clients/CreateClientActionTest.php`
- Creates client scoped to user's `current_organization_id`
- Sets `created_by` to user id
- Generates a non-empty slug

**Controller test:** `tests/Feature/Http/Clients/ClientStoreTest.php`
- Create page renders (200)
- Store returns validation errors when required fields are missing
- Store redirects to `clients.show` on success and flashes toast
- Guest is redirected to login

---

### Issue 3 — Clients: view a client
**Route:** `GET /clients/{client:slug}` → `clients.show`  
**Controller:** `ClientsController@show` — authorizes via `view` policy  
**Inertia page:** `resources/js/pages/Clients/Show.vue` — minimal: app shell + `<pre>` for `client` prop

**Controller test:** `tests/Feature/Http/Clients/ClientShowTest.php`
- Authenticated user sees a client from their org (200)
- Authenticated user gets 403 for a client from another org

---

### Issue 4 — Clients: edit a client
**Routes:** `GET /clients/{client:slug}/edit` → `clients.edit`, `PATCH /clients/{client:slug}` → `clients.update`  
**FormRequest:** `app/Http/Requests/Clients/UpdateClientRequest.php`  
**Action:** `app/Actions/Clients/UpdateClientAction.php` — `handle(User $user, Client $client, array $attributes): Client`; updates fields, sets `updated_by`, regenerates slug if name changed, `DB::transaction()`  
**Inertia page:** `resources/js/pages/Clients/Edit.vue` — minimal: app shell + `<pre>` for `client` prop

**Action test:** `tests/Feature/Actions/Clients/UpdateClientActionTest.php`
- Updates the client's fields in the DB
- Sets `updated_by` to user id

**Controller test:** `tests/Feature/Http/Clients/ClientUpdateTest.php`
- Edit page renders with client data (200)
- Update returns validation errors when required fields are missing
- Update redirects to `clients.show` on success and flashes toast
- User gets 403 when updating a client from another org

---

### Issue 5 — Clients: delete a client
**Route:** `DELETE /clients/{client:slug}` → `clients.destroy`  
**Controller:** `ClientsController@destroy` — authorizes via `delete` policy (owner-only), `$client->delete()` (soft delete)  
No action needed — single model call, no extra business logic.

**Controller test:** `tests/Feature/Http/Clients/ClientDestroyTest.php`
- Owner soft-deletes client, redirects to `clients.index` with toast
- Non-owner member gets 403
- Soft-deleted client still in DB (`assertSoftDeleted`)

---

### Reuse Notes
- `ClientPolicy`, `ClientFactory` already exist
- `User::factory()->withOrganization()->create()` for auth setup
- `makeUserInOrg()` from `ClientPolicyTest.php` for cross-org 403 tests
- Toast: `Inertia::flash('toast', ['type' => 'success', 'message' => '...'])`
- Layer ownership: action test → DB state; controller test → HTTP contract only; no assertion duplication

---
