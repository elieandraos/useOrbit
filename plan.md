# Composer & JavaScript Dependency Upgrade

> This section is the source of truth for the subsequent `my-feature-planning` pass. It captures
> decisions, not a frozen snapshot — exact versions and file line numbers should be re-verified
> against the codebase when individual issues are actually built, since the lockfiles will have
> moved on by then. Issue decomposition belongs to `my-feature-planning`; commit boundaries and
> sequencing belong to `my-git-workflow`. Neither is decided here.

## Context / Problem

useOrbit's Composer and npm dependencies have drifted from latest. Most direct dependencies only
need in-range minor/patch bumps, but several majors are available across both ecosystems, and the
CI verification model that's supposed to catch regressions from any of this has gaps of its own.
This initiative scopes and sequences the dependency upgrade *and* the verification-gate correction
needed to trust it — investigated via `composer outdated`/`npm outdated`, registry
peer-dependency queries, targeted breaking-change research, and a direct read of the two GitHub
Actions workflows, using the
[hodstack deps-upgrade methodology](https://github.com/hodstack/hodstack/blob/0.x/skills/skills/deps-upgrade/SKILL.md)
as an execution reference (baseline test → batch minor/patch → investigate each major individually
→ manifest alignment → report).

## Current State

- **Composer**: `composer.json` requires `php: ^8.4`, `laravel/framework: ^13.7` (installed
  `v13.25.0`), and `maatwebsite/excel: ^3.1` (installed `v3.1.69`) among other direct deps. No
  installed package is abandoned.
- **npm is the actual package manager**: `package-lock.json` is the committed, canonical lockfile;
  both CI workflows invoke `npm`; `.npmrc` carries an npm-specific setting (below). `package.json`
  has no `packageManager` field. `pnpm-workspace.yaml` exists at the repo root but there is no
  `pnpm-lock.yaml`, nothing in either CI workflow or `composer.json` invokes `pnpm`, and it is
  unreferenced anywhere else in the repo — it is stale, inherited unchanged from the original
  starter-kit scaffold, not evidence of an intended package manager.
- **`.npmrc`** sets `ignore-scripts=true` — this disables npm lifecycle scripts (`preinstall`,
  `install`, `postinstall`, `prepare`, etc.) for every package during install, a supply-chain-attack
  mitigation against malicious install scripts. It does not affect the platform-conditional
  `optionalDependencies` (`@rollup/rollup-*`, `@tailwindcss/oxide-*`, `lightningcss-*`) since those
  resolve via npm's own platform-matching, not via install scripts. This setting must be preserved
  exactly as-is by any CI correction.
- **`.github/dependabot.yml`** exists but only configures `package-ecosystem: "github-actions"`.
  There is no `npm` or `composer` ecosystem entry — automated dependency-update PRs are not
  currently configured for either manifest this initiative is upgrading.
- **CI verification, as it actually runs today** (corrects the prior version of this plan, which
  claimed the existing scripts already covered this — they don't):
  - `.github/workflows/lint.yml` (internal workflow name `linter`, not to be confused with the
    filename) installs with `npm install` (resolves against `package.json` ranges, not guaranteed
    to reproduce `package-lock.json` exactly), then runs `composer lint` (`pint --parallel`,
    **mutating** — writes style fixes) and `npm run format` (`prettier --write`, **mutating**) and
    `npm run lint` (`eslint . --fix`, **mutating**) — all three *fix* rather than *check*, and
    nothing after them asserts the resulting diff is clean. A branch with unformatted or
    unlinted code as committed can still pass this workflow, because the workflow silently fixes it
    in the ephemeral runner and exits 0.
  - `.github/workflows/tests.yml` installs with `npm i` (same non-reproducible-install issue as
    `npm install`), builds assets, then runs `./vendor/bin/pest` directly. It never runs
    `npm run types:check` (`vue-tsc --noEmit`), `npm run lint:check`, or `npm run format:check`.
  - **No workflow currently invokes `composer.json`'s own `ci:check` script**
    (`npm run lint:check && npm run format:check && npm run types:check && @test`), even though
    that script already encodes exactly the non-mutating, complete verification surface this
    project wants. CI and the repo's own scripts have drifted apart.
  - Net effect: **`vue-tsc` type-checking currently never runs in CI at all**, and neither ESLint
    nor Prettier nor Pint actually gate a PR — they only ever self-correct a checkout that's already
    thrown away.
- **Pre-upgrade baseline**: not established in this investigation. No full `composer test` /
  `php artisan test` run, and no `npm run types:check` run, was actually observed passing against
  the current `main` before any dependency change is proposed. This must not be assumed green.
- **FromQuery ordering invariant** (Laravel-Excel v4 requires exports built on `FromQuery` to have
  deterministic ordering, since the export walks the query via `Builder::chunk()`/`LIMIT`/`OFFSET`
  pagination — see `Maatwebsite\Excel\Concerns\FromQuery::query()`'s docblock): correcting the
  prior version of this plan, which never checked this — **the invariant is already satisfied in
  current code**. `app/Sorts/Sort.php:20-24`'s `apply()` method centrally appends
  `->orderBy($sorted->getModel()->getKeyName())` after resolving *any* sort (a named column method
  or the `default()` fallback), and `app/Models/Concerns/Sortable.php`'s `#[Scope] sort()` wires
  `Sort::apply()` into every `->sort()` call — including the three Export classes'
  `query()` methods (`app/Exports/{Clients,Agents,Carriers}Export.php`). None of `AgentSort`,
  `CarrierSort`, or `ClientSort` need a code change; the primary-key tie-breaker is already
  guaranteed for all of them. What's actually missing is **proof**: no test in
  `tests/Unit/Sorts/{Agent,Carrier,Client}SortTest.php` constructs rows that are genuinely tied on
  every sortable column to confirm the resolved order stays stable via the primary key — existing
  tests distinguish fixtures by the sorted column itself, so they'd pass even if the tie-breaker
  were silently removed.
- **Excel export coverage** (`app/Exports/{Clients,Agents,Carriers}Export.php`,
  `app/Actions/{Clients,Agents,Carriers}/Export*ToExcelAction.php`,
  `app/Http/Controllers/{Clients,Agents,Carriers}/*ExcelExportController.php`): the three export
  classes' `headings()`/`map()` methods are covered by
  `tests/Unit/Exports/{Clients,Agents,Carriers}ExportTest.php`. No test exercises the
  `Excel::download()` facade call itself (the actions/controllers that invoke it).

## Approved Target Architecture

Everything else direct stays on its current major; only the specific bumps below change scope.
In-range updates (patch/minor within the current constraint) are taken across the board as a
routine batch — lower risk than a major, but still gated by the corrected CI, not assumed
automatically safe. CI itself moves from mutating, non-reproducible installs to reproducible,
non-mutating verification that actually runs the full `ci:check` surface, including `vue-tsc`.

## Locked Decisions

- **`maatwebsite/excel` 3.1.69 → 4.0.2**: upgrade is in scope. Verified compatible —
  `illuminate/support: ^12||^13` and `php: ^8.3` requirements are already satisfied; `FromQuery`,
  `WithHeadings`, `WithMapping` concerns and the `Excel::download()` signature are unchanged;
  the app's three export classes already satisfy the new marker `Export` interface for free.
  **Verification is split, per explicit user direction**: the earlier decision to skip new
  automated coverage covered only the `Excel::download()` facade call path (manual verification of
  the three export downloads before this lands) — it did **not** decline coverage of the
  query-ordering invariant. A targeted test proving the tie-breaker holds under genuinely tied rows
  is in scope (see Tests / Behavioral Impact); no application-code change is needed since the
  invariant is already correctly implemented.
- **TypeScript 5.9.3 → 6.0.3 + vue-tsc 2.2.12 → 3.3.11**: both in scope, bumped together.
  TypeScript 7.x is explicitly **not** in scope — `typescript-eslint`'s peer range is
  `>=4.8.4 <6.1.0`, so 7.x has no supported path yet. `tsconfig.json` was already checked against
  TS 6.0's removed/deprecated surface (ES5 target, `amd`/`umd`/`systemjs` modules, `baseUrl`,
  `moduleResolution: node`, `outFile`) — none are in use, so no tsconfig edit is expected, but
  `vue-tsc --noEmit` must be re-run clean as verification, not assumed — and per the CI correction
  below, this must run somewhere CI actually enforces it.
- **`@types/node` stays on `^22`**: take the in-range `22.19.20 → 22.20.1` patch bump only. The
  major (`26.4.0`) is explicitly declined — CI (`tests.yml`) and local dev both run Node 22, and
  types ahead of the runtime invite phantom type errors for APIs that don't exist yet at runtime.
- **`@eslint/js` removal is locked, not open**: it is a devDependency with zero references anywhere
  in `eslint.config.js` or elsewhere in the repo (confirmed by grep). It is removed outright as
  part of this initiative rather than retained against a hypothetical future flat-config need.
- **`pnpm-workspace.yaml` removal is locked**: no `pnpm-lock.yaml` exists, nothing invokes `pnpm`
  anywhere in the repo (CI, `composer.json`, `package.json`), and npm is the package manager
  actually used everywhere else (canonical `package-lock.json`, `.npmrc`, both workflows). It is
  removed as stale scaffold leftover.
- **CI verification model must be corrected to reproducible installs and non-mutating validation**:
  - Replace `npm install` (`lint.yml`) and `npm i` (`tests.yml`) with `npm ci`, which installs
    strictly from the committed `package-lock.json` and fails if the manifest and lockfile have
    drifted, instead of silently re-resolving.
  - Replace the mutating `composer lint` (`pint --parallel`), `npm run format`
    (`prettier --write`), and `npm run lint` (`eslint . --fix`) steps in `lint.yml` with their
    non-mutating equivalents — `composer lint:check` (`pint --parallel --test`),
    `npm run format:check`, `npm run lint:check` — or, equivalently and with less drift risk,
    invoke the repo's own `composer run ci:check` script directly, since it already composes
    `lint:check`, `format:check`, `types:check`, and the full Pest run in one non-mutating pass.
  - Wire `npm run types:check` (`vue-tsc --noEmit`) into CI — it currently runs nowhere. This is
    the direct enforcement gap for the TypeScript/vue-tsc major bump above: without it, a
    type-check regression from that bump would ship unnoticed.
  - `.npmrc`'s `ignore-scripts=true` must be preserved untouched by this correction — neither
    `npm ci` nor any other change here requires or implies removing it.
  - This is a workflow-file change, not a dependency-version change — it belongs in this
    initiative's scope because the dependency bumps above (especially TypeScript/vue-tsc and the
    eslint major) are only verified if CI actually runs the checks that exercise them.
- **A green pre-upgrade baseline is a mandatory gate, not an assumed fact**: before any manifest or
  lockfile is touched, the full existing verification surface (`composer run ci:check`, i.e.
  Pint check + ESLint check + Prettier check + `vue-tsc --noEmit` + the full Pest suite) must
  actually be run and observed passing against current `main`. This plan does not claim that
  baseline already passes — it wasn't run as part of this investigation. If it fails, that failure
  must be resolved (or explicitly scoped as pre-existing and out of this initiative) before any
  upgrade tranche begins, per the hodstack methodology's own rule: no regression can be demonstrated
  against a baseline that wasn't already green.
- **npm/Composer Dependabot automation is explicitly out of scope for this initiative** — it is
  separate, follow-on work, not silently omitted. This initiative is a one-time version-currency
  catch-up plus a CI-correctness fix; configuring `package-ecosystem: npm` and
  `package-ecosystem: composer` entries in `.github/dependabot.yml` for ongoing automated updates
  is a distinct decision (update cadence, grouping, auto-merge policy) that hasn't been made and
  isn't implied by anything decided here.

## Preserved Behavior / Existing Pieces

- Laravel framework stays on major `13` (no v14 exists yet); PHP requirement stays `^8.4`.
- Vue stays on `3.5.x` (`3.5.35 → 3.5.42` patch only), Tailwind stays on `4.x`
  (`4.3.0 → 4.3.3` patch only), Inertia stays on `3.x` (`3.3.1 → 3.7.0` minor only) — none of
  these have a major available.
- `.npmrc`'s `ignore-scripts=true` is preserved exactly as-is (see Locked Decisions).
- The existing `ci:check` / `test` Composer scripts are preserved as the definition of "what CI
  should check" — the correction here is making CI actually call them, not replacing them.

## Changes

**Composer — in-range (lower risk, still gated by the corrected CI, not assumed automatically
safe):**
`laravel/boost 2.5.3→2.7.0`, `laravel/fortify 1.38.0→1.39.0`,
`laravel/framework 13.25.0→13.29.0`, `laravel/sail 1.66.0→1.67.0`,
`mockery/mockery 1.6.12→1.6.15`, `pestphp/pest 5.1.0→5.1.3`.

**Composer — major (investigated above):**
`maatwebsite/excel 3.1.69→4.0.2` (transitively bumps `phpoffice/phpspreadsheet ^1.30→^5.8`; the
app only references `PhpOffice\PhpSpreadsheet\Exception`/`Writer\Exception` for typing in
`app/Actions/*/Export*ToExcelAction.php` and `app/Http/Controllers/*/​*ExcelExportController.php`,
which are stable across that bump). Query-ordering invariant already satisfied — see Locked
Decisions.

**npm — in-range (lower risk, still gated by the corrected CI, not assumed automatically safe):**
`@inertiajs/vite 3.3.1→3.7.0`, `@inertiajs/vue3 3.3.1→3.7.0`, `@lucide/vue 1.17.0→1.34.0`,
`@tailwindcss/vite 4.3.0→4.3.3`, `@types/node 22.19.20→22.20.1`, `@vitejs/plugin-vue 6.0.7→6.0.8`,
`@vue/eslint-config-typescript 14.8.0→14.9.0` (also required for the eslint major below),
`laravel-vite-plugin 3.1.0→3.2.0`, `prettier 3.8.3→3.9.6`, `pusher-js 8.5.0→8.6.0`,
`shiki 4.2.0→4.4.3`, `tailwindcss 4.3.0→4.3.3`, `typescript-eslint 8.60.1→8.68.0`,
`vite 8.0.16→8.2.2`, `vue 3.5.35→3.5.42`.

**npm — majors (investigated above, coordinated where noted):**
- `eslint 9.39.4→10.9.1` + `eslint-plugin-vue 9.33.0→10.10.0` — **must move together**;
  `eslint-plugin-vue@9`'s peer range excludes eslint 10, and `@vue/eslint-config-typescript` only
  gained eslint-10 support at `14.9.0`.
- `typescript 5.9.3→6.0.3` + `vue-tsc 2.2.12→3.3.11` (locked decision above).
- `@vueuse/core 12.8.2→14.4.0` — only `useVModel` is consumed (5 files); confirmed unchanged
  across the v13 and v14 release notes.
- `concurrently 9.2.1→10.0.5` — requires Node ≥22 (already satisfied); `--kill-others` (used in
  `composer.json`'s `dev` script) is unaffected, only the underlying `killOthers` API option is
  deprecated in favor of `killOthersOn`.

**Cleanup (locked, not version currency):**
- Remove `@eslint/js` devDependency (unreferenced).
- Remove `pnpm-workspace.yaml` (stale, no pnpm lockfile or usage anywhere).

**CI workflow correction (locked, mechanism partly open — see below):**
`.github/workflows/lint.yml` and `.github/workflows/tests.yml` move to `npm ci` and non-mutating
validation, with `vue-tsc` wired in where it currently never runs. See Locked Decisions for the
specific required change and Open Implementation Decisions for the remaining mechanism choice.

## Invariants / Boundaries

- Every upgrade tranche (batch, then each major) must pass the corrected, actually-enforced CI
  gate — reproducible install + non-mutating lint/format/type/test checks — before the next
  tranche begins. This gate does not exist in its required form today; making it exist is part of
  this initiative (see Locked Decisions).
- `.npmrc`'s `ignore-scripts=true` must not be weakened or removed by the CI correction.
- CI runs on `ubuntu-latest`; the `@rollup/rollup-linux-x64-gnu` pin is the one cleanup item that
  actually executes in CI (not just locally on macOS) — whichever resolution is chosen (see Open
  Implementation Decisions) must be verified there, not just via a local install.
- `Sort::apply()`'s primary-key tie-breaker (`app/Sorts/Sort.php:20-24`) must keep running for every
  `->sort()` call; nothing in this initiative touches it, but the new ordering-invariant test
  exists specifically to guard it going forward.
- A red pre-upgrade baseline blocks the start of any upgrade tranche (see Locked Decisions) —
  this is a gate, not a formality.

## Open Implementation Decisions

- **CI correction mechanism**: swap the individual mutating commands for their non-mutating
  equivalents inline in each workflow step, or replace those steps with a single
  `composer run ci:check` invocation (which already bundles all of them, including `types:check`).
  Both satisfy the locked requirement (reproducible install + non-mutating validation +
  `vue-tsc` actually running); the choice doesn't change what CI guarantees, only how the YAML
  expresses it. Left for implementation, verified by a green CI run either way.
- **`@rollup/rollup-linux-x64-gnu` / `@rollup/rollup-win32-x64-msvc` pins**: realign the exact
  `4.9.5` pins to a version matching what Vite 8's bundled Rollup actually needs, or remove them
  from `optionalDependencies` entirely and let npm's platform-optional-dependency resolution
  handle it unpinned. This stays open **only** on the condition that whichever option is chosen is
  verified under `npm ci`, `npm run build`, and a green `ubuntu-latest` CI run — not assumed safe
  from a local macOS install, since these packages don't even resolve on macOS.
- **Upgrade sequencing and commit boundaries**: not decided here. Issue decomposition for this
  initiative is `my-feature-planning`'s job; how the resulting work is actually sequenced into
  commits is `my-git-workflow`'s job. This plan states what must end up true, not the order of
  edits that gets there.

## Tests / Behavioral Impact

- **Pre-upgrade baseline must be run and observed green first** (Locked Decisions) — this is a
  precondition for everything below, not an assumption.
- Existing Pest suite (156 files) must stay green through every tranche.
- **New, targeted test for the FromQuery ordering invariant**: construct rows tied on every column
  a given `Sort` class's named methods and `default()` can sort by, and assert the resolved order
  is stable via the primary key — for `ClientSort`, `AgentSort`, and `CarrierSort`. This proves the
  Laravel-Excel v4 `FromQuery` requirement holds, without requiring a code change, since
  `Sort::apply()` already implements it.
- `maatwebsite/excel` v4: `tests/Unit/Exports/{Clients,Agents,Carriers}ExportTest.php` must pass
  unmodified (they exercise `headings()`/`map()`, the concerns most exposed to the major bump).
  The `Excel::download()` call path itself has **no automated coverage** by locked decision above —
  manually exercise all three export downloads before this lands.
- TypeScript 6.0 / vue-tsc 3.x: `npm run types:check` (`vue-tsc --noEmit`) must pass clean, and —
  per the CI correction — must actually run in CI going forward, not just locally.
- eslint 10 / eslint-plugin-vue 10: `npm run lint:check` must pass clean; watch for any flat-config
  rule renames surfaced only at run time (registry peer-dependency checks didn't surface rule-level
  breaking changes, but weren't exhaustive of every rule).
- CI correction itself is validated by a green run of both corrected workflows on a real PR/push
  to `ubuntu-latest`, not just a local `composer run ci:check` pass.

## Before → After

| Package | Current | Target | Type |
|---|---|---|---|
| maatwebsite/excel | 3.1.69 | 4.0.2 | major |
| laravel/boost | 2.5.3 | 2.7.0 | minor |
| laravel/fortify | 1.38.0 | 1.39.0 | minor |
| laravel/framework | 13.25.0 | 13.29.0 | minor |
| laravel/sail | 1.66.0 | 1.67.0 | minor |
| mockery/mockery | 1.6.12 | 1.6.15 | patch |
| pestphp/pest | 5.1.0 | 5.1.3 | patch |
| eslint | 9.39.4 | 10.9.1 | major |
| eslint-plugin-vue | 9.33.0 | 10.10.0 | major |
| @vue/eslint-config-typescript | 14.8.0 | 14.9.0 | minor (required for eslint 10) |
| typescript | 5.9.3 | 6.0.3 | major (7.x deferred — see Locked Decisions) |
| vue-tsc | 2.2.12 | 3.3.11 | major |
| @vueuse/core | 12.8.2 | 14.4.0 | major |
| concurrently | 9.2.1 | 10.0.5 | major |
| @types/node | 22.19.20 | 22.20.1 | patch (26.x major declined) |
| @inertiajs/vite, @inertiajs/vue3 | 3.3.1 | 3.7.0 | minor |
| vite | 8.0.16 | 8.2.2 | minor |
| vue | 3.5.35 | 3.5.42 | patch |
| tailwindcss, @tailwindcss/vite | 4.3.0 | 4.3.3 | patch |
| (+ remaining npm batch listed in Changes) | — | — | minor/patch |
| @rollup/rollup-linux-x64-gnu/win32-msvc | 4.9.5 (stale pin) | realigned or removed | open, dual-path verification |
| @eslint/js | 9.39.4 (unused) | removed | locked cleanup |
| pnpm-workspace.yaml | present (stale) | removed | locked cleanup |

**CI workflow corrections (not a package, tracked separately):**

| Workflow | Current | Target |
|---|---|---|
| `.github/workflows/lint.yml` | `npm install`; mutating `pint --parallel`, `prettier --write`, `eslint --fix` with no diff check | `npm ci`; non-mutating `pint --test`, `prettier --check`, `eslint` (or a single `composer run ci:check`) |
| `.github/workflows/tests.yml` | `npm i`; never runs `types:check`/`lint:check`/`format:check` | `npm ci`; `vue-tsc --noEmit` wired in (directly or via `composer run ci:check`) |

## Source References

- `composer.json`, `composer.lock`, `package.json`, `package-lock.json` — manifests.
- `.npmrc` — `ignore-scripts=true`, preserved invariant.
- `pnpm-workspace.yaml` — stale, locked for removal; no corresponding `pnpm-lock.yaml` exists.
- `.github/dependabot.yml` — `github-actions` ecosystem only; npm/composer ecosystems out of scope.
- `eslint.config.js` — flat config built via `defineConfigWithVueTs`, `vueTsConfigs.recommended`;
  confirms `@eslint/js` is unreferenced.
- `tsconfig.json:14,32,34,35,68` — target/module/moduleResolution/baseUrl/outFile settings audited
  against TS 6.0's removed/deprecated surface.
- `.github/workflows/lint.yml`, `.github/workflows/tests.yml` — actual CI steps, read directly
  (not assumed from `composer.json`'s scripts).
- `app/Sorts/Sort.php:20-24` — the existing primary-key tie-breaker, applied to every sort.
- `app/Models/Concerns/Sortable.php` — wires `Sort::apply()` into the `->sort()` scope used by all
  three Export `query()` methods.
- `app/Sorts/{Agent,Carrier,Client}Sort.php`,
  `tests/Unit/Sorts/{Agent,Carrier,Client}SortTest.php` — no code change needed; test coverage gap
  for the tie-breaker under genuine ties.
- `app/Exports/{Clients,Agents,Carriers}Export.php` — Laravel-Excel concerns usage.
- `app/Actions/{Clients,Agents,Carriers}/Export*ToExcelAction.php`,
  `app/Http/Controllers/{Clients,Agents,Carriers}/*ExcelExportController.php` — `Excel::download()`
  call sites, uncovered by automated tests (manual verification only, by locked decision).
- `tests/Unit/Exports/{Clients,Agents,Carriers}ExportTest.php` — existing coverage for the export
  concerns.
- `resources/js/**` (5 files) — `useVModel` usage from `@vueuse/core`, confirmed unaffected by the
  v13/v14 bumps.
