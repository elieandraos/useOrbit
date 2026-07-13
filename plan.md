# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

### Existing Components — but NOT in Design Foundation
 `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---

## Document Uploads (future, not built now): async with notifications + Reverb

### Context

When document uploads are built, per-file processing (OCR, virus scanning, external API calls) is I/O-bound and can take real seconds-to-minutes independent of how many clients or policies exist — unlike the Clients export above, this doesn't get faster by chunking a query. That's the actual justification for async processing + live notifications here, where it wasn't for the export.

### Data model

Status lives on the `documents` table itself, not a separate `uploads` table — a document is already a real domain entity (owner, associated client/policy, storage path); processing status is just additional columns on it (`status`: pending/processing/completed/failed, `processed_at`, an error message column for failures). No wrapper table needed for individual files; multiple concurrent uploads are naturally independent, one row and one notification per file — no batching for v1 unless "this batch of files is done" becomes an actual requirement later.

### Backend

- A queued Job processes each uploaded file and updates its `documents` row on completion/failure.
- `notifications` table (Laravel's built-in `notifications:table` migration) + one `App\Notifications\DocumentProcessed` class implementing `via() => ['database', 'broadcast']` — the database row and the live push come from a single `$user->notify(...)` call, no separate systems to keep in sync.
- Reverb, scaffolded via `php artisan install:broadcasting` (package, `config/broadcasting.php`, `routes/channels.php`, `REVERB_*` `.env` vars, `resources/js/echo.ts` — mostly generated, not hand-wired). Delivery over the default per-user private channel (`App.Models.User.{id}`), authorized by the standard generated `routes/channels.php` stub.
- Recipients default to the uploader only (`$user->notify(...)`). Broadening to additional recipients later (assigned agent, org admins) is `Notification::send($recipients, ...)` — a one-line change when there's an actual second recipient to design for, not a rearchitecture. A true org-wide activity feed (a shared channel many users subscribe to, rather than personal notifications) is a distinct pattern with its own questions (does everyone see everything, separate read-state?) — don't build it speculatively.

### Frontend

- `resources/js/composables/useNotifications.ts`: module-scoped `reactive({ items, unreadCount })` singleton, shared by both the bell and the full page so marking read in one place updates the other without a refetch. `unreadCount` bootstrapped via a lightweight Inertia shared prop; the list itself fetched lazily (Inertia v3's `useHttp` hook) on bell-open or page-load.
- One Echo listener, registered once at a persistent point (`app.ts` boot, or `AppLayout.vue`'s `onMounted` — both stay mounted across every Inertia navigation): `Echo.private('App.Models.User.' + user.id).notification(n => { ... })`, using Echo's built-in `.notification()` helper. Pushes into the composable and fires a `vue-sonner` toast alongside the durable row — a live nudge plus a persistent record.
- Bell (`AppTopNav.vue`, already persistent): unread badge + recent-items dropdown + "View all" link.
- `resources/js/pages/Notifications/Index.vue`: full paginated list via `$user->notifications()->paginate()`, following existing index-page conventions (no breadcrumbs, bordered page header).
- Per-document status in whatever upload UI gets built reads from a keyed collection (by document id) fed by the same mechanism — multiple simultaneous uploads show independent per-row status without extra plumbing.
- No Pinia — one cohesive resource (notifications: list + count) read from two places that need to agree, which a single composable singleton handles without a new dependency.

### Explicitly not built until there's a concrete need

Org-wide/shared-channel broadcasting, multi-recipient notifications, resumable/chunked uploads, notification batching/grouping, and filtering on the notifications page.

---

## Client Location Data: countries/states/cities via nnjeim/world

### Context

`clients` currently has a hand-rolled `countries` table (`id`, `name`, seeded with only "Lebanon") plus free-text `state` (hardcoded to 8 Lebanese governorates in the Vue form) and free-text `city`. Still in dev, no production data, and address data will be reused later for `Company`/`Branch`/`Broker` — not worth keeping a Lebanon-only one-off. Replacing with the `nnjeim/world` package (normalized `countries`/`states`/`cities`, all countries, Lebanon's real governorates + underlying cities/towns). We wrap it in our own thin controllers rather than using the package's own routes, so Wayfinder can generate typed frontend calls. `cities` is ~150k rows, so it can't be preloaded — needs a debounced on-demand search, which the existing `Select.vue` (native `<select>`) doesn't support, so a hand-built `Typeahead.vue` is added (no new npm dependency). A shared `HasWorldLocation` trait gives `country()`/`state()`/`city()` relations for reuse by future models.

### Package & schema

- `composer require nnjeim/world`; publish `config/world.php`; enable `states`/`cities`/`currencies` modules only (disable `timezones`/`languages`/`geolocate`); `routes => false` (we use our own controllers); leave `connection` unset so tables share the app's sqlite DB for FK constraints to work.
- `php artisan migrate` + `db:seed --class=WorldSeeder` once locally (`cities.json` ~53MB/~150k rows). `DatabaseSeeder` guards this behind `Country::query()->doesntExist()` so it doesn't rerun every seed.
- Remove the old custom `countries` migration/model/seeder. Edit `create_clients_table` migration directly (dev-only, not shipped): drop `city`/`state` string columns, add `state_id`/`city_id` nullable FKs alongside the existing `country_id`.
- `Client` gets `HasWorldLocation` trait (replacing its inline `country()` relation), `#[Fillable]` swaps `city`/`state` for `state_id`/`city_id`.
- `StoreClientRequest`/`UpdateClientRequest`: `exists:states,id` / `exists:cities,id`, scoped by `country_id`/`state_id` respectively so a mismatched state/city can't be saved.
- `ClientResource`: swap `city`/`state` fields for `state_id`/`city_id` + resolved `state_name`/`city_name` (same pattern as existing `country_name`); `full_address` uses the resolved names.
- `ClientFactory`: replace the hardcoded `CITIES_BY_STATE` list with `firstOrCreate` fixture rows (Lebanon/Mount Lebanon/Jounieh) instead of depending on the full seeded dataset — keeps tests fast.

### Backend search endpoints

New `app/Http/Controllers/World/StatesController.php` + `CitiesController.php` (thin, invokable), wrapping `World::states()`/`World::cities()` with `filters`/`search` params, returning `{data: [{id, name}]}` directly (no Resource wrapper needed). Both cap results to 20 rows and order matches so prefix matches (`name LIKE 'query%'`) sort before general substring matches (`name LIKE '%query%'`) — keeps the response small and ensures the closest matches survive the cutoff instead of an arbitrary slice. New `routes/world.php` (`auth`, `verified`, `organization` middleware, same as every other route), Wayfinder-generated for the frontend.

### Frontend

New `resources/js/components/ui/typeahead/Typeahead.vue` — styled like `Select.vue`, supports either a static `options` list (country, state) or an async `search` function (city, debounced via `useDebounceFn`, guards stale responses, shows `Spinner`, keyboard nav, click-outside via `onClickOutside`). New `resources/js/composables/useWorldLocations.ts` (`useStateOptions`, `useCitySearch`) using plain `fetch()` per the `useFileExport.ts` precedent. `ClientForm.vue`: country/state/city become `Typeahead` instances with numeric `v-model`s; changing country resets state+city, changing state resets city; city typeahead disabled until a state is picked.

### Testing

Pest feature tests for the new `world/states`/`world/cities` endpoints (fixture rows, not the full seeded dataset), a `ClientTest` covering the `HasWorldLocation` relations, updates to `StoreTest`/action tests for the new FK fields. One Pest 4 browser test (`tests/Browser/Clients/ClientFormLocationTest.php` — first browser test in the repo, no existing convention to match) covering the cascading typeahead behavior end-to-end.

### Full step-by-step plan

#### 1. Package install & config

1. `composer require nnjeim/world`.
2. `php artisan vendor:publish --tag=world --force` → publishes `config/world.php`.
3. Edit `config/world.php`:
   - `modules`: enable `states`, `cities`; disable `timezones`, `currencies`, `languages`, `geolocate` (not needed).
   - `routes` => `false` (we use our own controllers, item 4).
   - Leave `connection` at its default (do **not** set `WORLD_DB_CONNECTION` in `.env`) so `countries`/`states`/`cities` live in the same sqlite database as `clients`, keeping FK constraints valid.
   - Leave `allowed_countries`/`disallowed_countries` empty — load all countries; simplest for now.
4. `php artisan migrate` — creates `countries`, `states`, `cities` (module toggles skip the rest).
5. `php artisan db:seed --class=WorldSeeder` once locally (slow: `cities.json` is ~53MB/~150k rows). Wire long-term seeding into `database/seeders/DatabaseSeeder.php` guarded so it doesn't rerun every time:
   ```php
   public function run(): void
   {
       if (\Nnjeim\World\Models\Country::query()->doesntExist()) {
           $this->call(\Database\Seeders\WorldSeeder::class);
       }

       if (app()->environment('local')) {
           $this->call(UserSeeder::class);
           $this->call(ClientsSeeder::class);
       }
   }
   ```
   Remove the `CountrySeeder::class` call (superseded).

#### 2. Schema & backend model changes

**Remove** (dev-only, no production data to preserve): `database/migrations/2026_06_22_181606_create_countries_table.php`, `app/Models/Country.php`, `database/seeders/CountrySeeder.php`.

**Edit `database/migrations/2026_06_22_184351_create_clients_table.php`** directly (not a new migration — it hasn't shipped):
- Remove `$table->string('city', 100)->nullable();` and `$table->string('state', 100)->nullable();`.
- Keep `$table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();` (now resolves against the package's `countries` table).
- Add: `$table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();` and `$table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();`.
- The published `nnjeim/world` migrations carry old (2020/2021-era) timestamps, so they'll naturally sort before this migration — confirm after `vendor:publish` that they land in `database/migrations/` with earlier filenames.

**`app/Models/Client.php`**:
- Add `use App\Models\Concerns\HasWorldLocation;` to the trait list (alphabetical: `BelongsToCurrentOrganization, Filterable, HasFactory, HasSlug, HasWorldLocation, SoftDeletes, Sortable`).
- Remove the inline `country(): BelongsTo` method (moves to the trait).
- Update `#[Fillable([...])]`: drop `'city'`, `'state'`; add `'state_id'`, `'city_id'`.
- Update the `@property`/`@property-read` PHPDoc block accordingly; import `Nnjeim\World\Models\{Country,State,City}` instead of the local `Country`.

**`app/Http/Requests/Clients/StoreClientRequest.php`** and **`UpdateClientRequest.php`** (currently identical rule sets):
```php
// remove: 'city' => [...], 'state' => [...]
'country_id' => ['nullable', 'integer', 'exists:countries,id'],
'state_id' => ['nullable', 'integer', 'exists:states,id'],
'city_id' => ['nullable', 'integer', 'exists:cities,id'],
```
Add cross-field consistency so a `state_id`/`city_id` can't be saved against the wrong country/state:
```php
'state_id' => ['nullable', 'integer', Rule::exists('states', 'id')->where(fn ($q) => $q->when(
    $this->filled('country_id'), fn ($q) => $q->where('country_id', $this->input('country_id')),
))],
```
(same idea for `city_id` scoped to `state_id`). Verify `Rule::exists()->where()` closure support against the installed Laravel 13 API at implementation time; fall back to a `Validator::after()` closure if needed.

**`app/Actions/Clients/CreateClientAction.php` / `UpdateClientAction.php`**: update the `@param array{...}` PHPDoc shape (`city`/`state` → `state_id`/`city_id`); no logic changes, both just spread validated attributes into `Client::query()->create()`/`update()`.

**`app/Http/Resources/ClientResource.php`**: replace `'city'`/`'state'` fields with `'state_id'`/`'city_id'`; resolve display names the same way `$countryName` already is (`$this->relationLoaded('state') ? $this->state?->name : null`, same for city); expose `country_name`/`state_name`/`city_name`; update `full_address` to use the resolved names instead of raw `city`/`state`.

**`app/Http/Resources/CountryResource.php`**: swap `use App\Models\Country` → `use Nnjeim\World\Models\Country` (shape is unchanged, still just `id`/`name`).

**`app/Http/Controllers/ClientsController.php`**:
- Swap the `Country` import.
- `edit()`: change `$client->load('updatedBy')` → `$client->load(['updatedBy', 'country', 'state', 'city'])` so the edit form can pre-fill the typeahead labels.
- `show()`: change `$client->load('country')` → `$client->load(['country', 'state', 'city'])`.
- `create()`/`edit()` continue to preload the full `countries` list (small, ~250 rows) but do **not** pass `states`/`cities` props — those are fetched client-side on demand (item 5).

**`database/factories/ClientFactory.php`**: replace the hardcoded `CITIES_BY_STATE` const and `city`/`state` fields with real seeded World rows, scoped to Lebanon, using cheap `firstOrCreate` fixtures rather than depending on the full seeded dataset (keeps tests fast/independent):
```php
$country = Country::query()->firstOrCreate(['iso2' => 'LB'], ['name' => 'Lebanon', 'status' => 1]);
$state = State::query()->firstOrCreate(['name' => 'Mount Lebanon', 'country_id' => $country->id]);
$city = City::query()->firstOrCreate(['name' => 'Jounieh', 'country_id' => $country->id, 'state_id' => $state->id]);
// ...
'country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id,
```
Note: package models (`Country`/`State`/`City`) have no factories of their own — create rows directly as above.

#### 3. `HasWorldLocation` trait

New file `app/Models/Concerns/HasWorldLocation.php`:
```php
trait HasWorldLocation
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(\Nnjeim\World\Models\Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(\Nnjeim\World\Models\State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\Nnjeim\World\Models\City::class);
    }
}
```
Plain trait, matches the style of existing `app/Models/Concerns/*` traits. Assumes the host model has `country_id`/`state_id`/`city_id` columns — `Client` adopts it now; `Company`/`Branch`/`Broker` can adopt it later by adding the same three columns.

#### 4. Backend search endpoints (for the typeahead)

New sub-namespace `app/Http/Controllers/World/` (mirrors the existing `Settings/` grouping convention):

- **`StatesController`** (`__invoke`, thin, invokable) — validates `country_id`/`search` via a new `App\Http\Requests\World\SearchStatesRequest`, calls `World::states(['filters' => array_filter(['country_id' => ...]), 'search' => ...])`, returns `response()->json(['data' => $result->data->values()])` directly (skip a `StateResource` — the package already normalizes each row to `{id, name}`, so wrapping adds nothing).
- **`CitiesController`** — same shape, via `App\Http\Requests\World\SearchCitiesRequest` (`country_id`, `state_id`, `search`), calling `World::cities(['filters' => array_filter([...]), 'search' => ...])`.
- **Result capping & ordering**: both endpoints limit to 20 rows and order prefix matches (`name LIKE 'query%'`) before general substring matches (`name LIKE '%query%'`), e.g. `->orderByRaw('name LIKE ? DESC', ["{$search}%"])->orderBy('name')->limit(20)`. Even though `city_id` search is always scoped by `state_id`, a broad/short search term against a large state (e.g. California) could otherwise return thousands of rows or an arbitrary slice that hides the closest match. **Verify at implementation time**: whether `World::states()`/`World::cities()` accept `limit`/`orderByRaw` directly in the `filters` array, or whether the facade returns a query builder/collection that needs `->take()`/`->sortBy()` applied afterward — check `vendor/nnjeim/world/src/World.php` once installed.
- **Verify at implementation time**: exact facade import (`Nnjeim\World\Facades\World` vs `Nnjeim\World\World`) — check `vendor/nnjeim/world/src/World.php`'s actual namespace once installed, since it can vary slightly by release.

New `routes/world.php` (required from `routes/web.php` alongside the existing requires):
```php
Route::middleware(['auth', 'verified', 'organization'])->prefix('world')->name('world.')->group(function () {
    Route::get('states', StatesController::class)->name('states.index');
    Route::get('cities', CitiesController::class)->name('cities.index');
});
```
Same middleware trio as every other authenticated route in this app, for consistency.

Run `php artisan wayfinder:generate` afterward so the frontend gets typed `resources/js/routes/world/states/index.ts` / `.../cities/index.ts` helpers — no hardcoded URLs.

#### 5. `ClientForm.vue` changes

File: `resources/js/pages/Clients/partials/ClientForm.vue`.

- `ClientFormValues`: replace `city: string | null; state: string | null;` with `state_id: number | null; city_id: number | null;`, and add `country_name`, `state_name`, `city_name` (sourced from the resource fields added in item 2) so the typeaheads can show a label for the pre-selected value on the edit page before their option lists are fetched.
- Local refs become numeric: `countryId = ref<number | null>(...)`, `stateId`, `cityId` — a deliberate change from the current `String(...)` coercion the native `<select>` required; the new `Typeahead` (item 6) has no such constraint.
- Add two `watch()`es: changing `countryId` resets `stateId`/`cityId` to `null`; changing `stateId` resets `cityId`. Since `watch()` without `{ immediate: true }` doesn't fire on initial registration, this is safe on the edit page's initial load — no extra guard needed.
- New composable `resources/js/composables/useWorldLocations.ts` (alongside `useFileExport.ts` etc.):
  - `useStateOptions(countryId: Ref<number|null>)` — `watch(countryId, ..., { immediate: true })`, fetches `world/states?country_id=` via `fetch()` (matching the existing imperative-fetch precedent in `useFileExport.ts`, not Inertia's router) and returns `{ options, loading }`. States are few enough per country to preload wholesale on country change (still "static options" from the typeahead's point of view).
  - `useCitySearch(stateId: Ref<number|null>)` — returns an async `(query: string) => Promise<TypeaheadOption[]>` function hitting `world/cities?state_id=&search=`, for the city typeahead's async search mode. No debounce here — debouncing is the `Typeahead` component's job (item 6).
- Template: replace the City `<Input>` + Governorate `<Select>` (hardcoded 8-option list) and the Country `<Select>` with three `<Typeahead>` instances — country uses static preloaded `options`, state uses the reactive `options`/`loading` from `useStateOptions`, city uses the `search` function from `useCitySearch` and is `:disabled="!stateId"`.
- Controller side needs no new props beyond what item 2 already adds (`countries` stays preloaded; `states`/`cities` are never passed as props).

#### 6. New `Typeahead.vue` component

New file `resources/js/components/ui/typeahead/Typeahead.vue` (sibling folder to `ui/select/`), styled to match `Select.vue`'s wrapper classes/tokens for visual consistency (reuse the `cn()` helper from `@/lib/utils`, same `border-border`, `focus-within:ring-accent-ring`, `size: 'sm'|'md'` conventions). Look at `resources/js/components/ui/drop-menu/` for how this codebase already positions a floating panel below a trigger (z-index, shadow, `absolute`/`mt-1` conventions) and reuse that instead of inventing new tokens.

Props (mirrors `Select.vue`'s `v-model`/`placeholder`/`size`/`disabled`/`class` contract, plus):
- `options?: { value: number|string; label: string }[]` — static mode (country, state).
- `search?: (query: string) => Promise<{ value; label }[]>` — async mode (city). Exactly one of `options`/`search` should be given.
- `loading?: boolean` — parent-controlled loading flag (state options refetching).
- `initialLabel?: string | null` — display label for a pre-selected value before its options have loaded (needed on the edit page, since state/city options aren't preloaded).
- `debounce?: number` (default 300) — only applies in async mode; debounce via `useDebounceFn` from `@vueuse/core` (already a dependency, just not yet used elsewhere in this codebase).

Behavior: filtered/searchable text input + dropdown `<ul>`; static mode filters `options` client-side (no debounce needed, synchronous); async mode debounces calls to `props.search`, shows the existing `Spinner` component (`ui/spinner/`) while pending, and guards against out-of-order responses (a monotonic request-id or `AbortController`, since city search latency can vary). Keyboard nav (Up/Down/Enter/Escape), click-outside close via `onClickOutside` from `@vueuse/core`, empty state text, and a hidden `<input type="hidden" :name :value>` for the actual form-submitted value (the visible text input is search/display only and must not carry `name`, unlike `Select.vue`'s native `<select>` which submits directly).

**Reusable by design**: `Typeahead.vue` itself has no knowledge of "world", "city", or any specific model — it only knows `options`/`search` props returning `{value, label}[]`. All the world-specific plumbing (hitting `world/states`/`world/cities`) lives in `useWorldLocations.ts`, not the component. Any future searchable field (e.g. `Company`/`Branch`/`Broker` address lookups, or an unrelated user/organization picker) reuses the same component by supplying its own `options` array or `search` function — no changes to `Typeahead.vue` required.

#### 7. Testing

**Backend (Pest feature/unit)**:
- `tests/Feature/Http/World/StatesIndexTest.php` / `CitiesIndexTest.php` — guest redirect, search scoped by `country_id`/`state_id`, empty-result case. Create fixture rows directly (`State::query()->create([...])`, package models have no factories) rather than seeding the full dataset.
- `tests/Unit/Models/ClientTest.php` (new) — asserts `$client->country`/`state`/`city` resolve to the right model instances, exercising `HasWorldLocation`.
- Update `tests/Feature/Http/Clients/StoreTest.php`: the existing `'create page passes the Lebanon country id as the default country'` test constructs `Country::create(['name' => 'Lebanon'])` against the old model/import — switch to `Nnjeim\World\Models\Country` and supply `iso2` (no DB default). Add a case asserting `state_id`/`city_id` persist on store.
- Update `CreateClientActionTest.php`/`UpdateClientActionTest.php` for any `'city'`/`'state'` attribute arrays → real `state_id`/`city_id` from created fixture rows (sqlite enforces FKs).
- `grep -rn "Achrafieh\|Mount Lebanon\|Beirut" tests/` after the change to catch any test coupled to the old hardcoded `CITIES_BY_STATE` values.

**Frontend (Pest 4 browser)**: this repo has no `tests/Browser/` directory yet, so there's no existing convention to match — follow Pest 4's browser API directly. Add one focused test, e.g. `tests/Browser/Clients/ClientFormLocationTest.php`, covering the core new behavior: select a country → state typeahead populates; select a state → type in the city typeahead → matching result appears and is selectable; changing country afterward resets both. Keep it to this one scenario rather than a large matrix — it's the only genuinely new interactive behavior being added.

Run `vendor/bin/pint --dirty --format agent` after PHP changes.

#### Verification

1. `php artisan migrate:fresh --seed` locally — confirm `countries`/`states`/`cities` populate and `ClientsSeeder` succeeds using the new factory.
2. `php artisan test --compact --filter=Client` and `--filter=World` for the new/updated Pest suites.
3. Manually exercise `/clients/create`: switch country Lebanon → France and confirm state/city reset and the state typeahead repopulates; pick a state, type a partial city name, confirm debounced results appear; submit and check `/clients/{slug}` shows the resolved country/state/city in `full_address`.
4. Repeat on `/clients/{slug}/edit` to confirm existing state/city labels pre-fill correctly via `initialLabel` before any search has run.

---
