# Composer & JavaScript Dependency Upgrade

> This section is the source of truth for the subsequent `my-feature-planning` pass. It captures
> decisions, not a frozen snapshot — exact versions and file line numbers should be re-verified
> against the codebase when individual issues are actually built, since the lockfiles will have
> moved on by then.

## Context / Problem

useOrbit's Composer and npm dependencies have drifted from latest. Most direct dependencies only
need minor/patch bumps, but several majors are available across both ecosystems. This initiative
scopes and sequences that upgrade: which majors to take now, which to defer, and what verification
each one needs — investigated via `composer outdated`/`npm outdated`, registry peer-dependency
queries, and targeted breaking-change research, using the
[hodstack deps-upgrade methodology](https://github.com/hodstack/hodstack/blob/0.x/skills/skills/deps-upgrade/SKILL.md)
as an execution reference (baseline test → batch minor/patch → investigate each major individually
→ manifest alignment → report).

## Current State

- **Composer**: `composer.json` requires `php: ^8.4`, `laravel/framework: ^13.7` (installed
  `v13.25.0`), and `maatwebsite/excel: ^3.1` (installed `v3.1.69`) among other direct deps. No
  installed package is abandoned.
- **npm**: `package.json` is ESM-only (`"type": "module"`), Vue 3.5, Vite 8, Tailwind 4,
  ESLint 9 flat config (`eslint.config.js`) built via `defineConfigWithVueTs`.
- **Runtime pins**: `.github/workflows/tests.yml` pins Node `22` and PHP `8.4` in CI; local PHP is
  `8.4.8`, local Node is `22.23.1` — CI and local agree.
- **Verification surface** (already scripted, not something this initiative needs to invent):
  - `composer.json` → `ci:check` runs `npm run lint:check && npm run format:check && npm run
    types:check && @test`; `test` runs `artisan config:clear`, `lint:check` (Pint), then
    `artisan test` (Pest, 156 test files across `tests/Unit` and `tests/Feature`).
  - `.github/workflows/linter.yml` runs Pint + `npm run format` + `npm run lint` on every push/PR
    to `develop`/`main`/`master`/`workos`.
  - `.github/workflows/tests.yml` runs `composer install`, `npm run build`, then `./vendor/bin/pest`
    on the same branches.
  - `tsconfig.json` already sets `target: "ESNext"`, `module: "ESNext"`,
    `moduleResolution: "bundler"`; `baseUrl` and `outFile` are commented out (not in use).
- **Excel export coverage** (`app/Exports/{Clients,Agents,Carriers}Export.php`,
  `app/Actions/{Clients,Agents,Carriers}/Export*ToExcelAction.php`,
  `app/Http/Controllers/{Clients,Agents,Carriers}/*ExcelExportController.php`): the three export
  classes' `headings()`/`map()` methods are covered by
  `tests/Unit/Exports/{Clients,Agents,Carriers}ExportTest.php`. No test exercises the
  `Excel::download()` facade call itself (the actions/controllers that invoke it).

## Approved Target Architecture

Everything else direct stays on its current major; only the specific bumps below change scope.
Non-major updates (patch/minor within the current constraint) are taken across the board as a
routine batch — no product decision attached, per the locked-vs-open rule below.

## Locked Decisions

- **`maatwebsite/excel` 3.1.69 → 4.0.2**: upgrade is in scope. Verified compatible —
  `illuminate/support: ^12||^13` and `php: ^8.3` requirements are already satisfied; `FromQuery`,
  `WithHeadings`, `WithMapping` concerns and the `Excel::download()` signature are unchanged;
  the app's three export classes already satisfy the new marker `Export` interface for free.
  **Verification for this major is manual only** — the existing Unit coverage of `headings()`/
  `map()` is judged sufficient for the mapping logic, and the user explicitly declined adding new
  Feature tests for the `Excel::download()` call path. The three export downloads (Clients,
  Agents, Carriers) must be manually exercised before this lands.
- **TypeScript 5.9.3 → 6.0.3 + vue-tsc 2.2.12 → 3.3.11**: both in scope, bumped together.
  TypeScript 7.x is explicitly **not** in scope — `typescript-eslint`'s peer range is
  `>=4.8.4 <6.1.0`, so 7.x has no supported path yet. `tsconfig.json` was already checked against
  TS 6.0's removed/deprecated surface (ES5 target, `amd`/`umd`/`systemjs` modules, `baseUrl`,
  `moduleResolution: node`, `outFile`) — none are in use, so no tsconfig edit is expected, but
  `vue-tsc --noEmit` must be re-run clean as verification, not assumed.
- **`@types/node` stays on `^22`**: take the in-range `22.19.20 → 22.20.1` patch bump only. The
  major (`26.4.0`) is explicitly declined — CI (`tests.yml`) and local dev both run Node 22, and
  types ahead of the runtime invite phantom type errors for APIs that don't exist yet at runtime.
- **Opportunistic cleanup included**: the stale `@rollup/rollup-linux-x64-gnu` /
  `@rollup/rollup-win32-x64-msvc` exact pins (`4.9.5`) in `package.json`'s `optionalDependencies` —
  inherited unchanged from the original starter-kit scaffold (`7149faf`), never intentionally
  pinned by this project — and the `@eslint/js` devDependency, which is not referenced anywhere in
  `eslint.config.js`. Exact resolution (realign vs. remove) is an open implementation detail below.

## Preserved Behavior / Existing Pieces

- Laravel framework stays on major `13` (no v14 exists yet); PHP requirement stays `^8.4`.
- Vue stays on `3.5.x` (`3.5.35 → 3.5.42` patch only), Tailwind stays on `4.x`
  (`4.3.0 → 4.3.3` patch only), Inertia stays on `3.x` (`3.3.1 → 3.7.0` minor only) — none of
  these have a major available.
- The existing `ci:check` / `test` Composer scripts and the two GitHub Actions workflows are the
  verification mechanism for this entire initiative; nothing about them needs to change.

## Changes

**Composer — batch (no breaking changes, safe to take together):**
`laravel/boost 2.5.3→2.7.0`, `laravel/fortify 1.38.0→1.39.0`,
`laravel/framework 13.25.0→13.29.0`, `laravel/sail 1.66.0→1.67.0`,
`mockery/mockery 1.6.12→1.6.15`, `pestphp/pest 5.1.0→5.1.3`.

**Composer — major (investigated above):**
`maatwebsite/excel 3.1.69→4.0.2` (transitively bumps `phpoffice/phpspreadsheet ^1.30→^5.8`; the
app only references `PhpOffice\PhpSpreadsheet\Exception`/`Writer\Exception` for typing in
`app/Actions/*/Export*ToExcelAction.php` and `app/Http/Controllers/*/​*ExcelExportController.php`,
which are stable across that bump).

**npm — batch (no breaking changes, safe to take together):**
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

**Cleanup (not version currency, found during this investigation):**
`@rollup/rollup-linux-x64-gnu` / `@rollup/rollup-win32-x64-msvc` stale exact pins, and the unused
`@eslint/js` devDependency.

## Invariants / Boundaries

- Every tranche (batch, then each major) must pass `composer run ci:check` (Pint + ESLint +
  Prettier + `vue-tsc --noEmit` + full Pest suite) before moving to the next tranche — this is the
  existing gate, not a new one.
- CI runs on `ubuntu-latest`; the `@rollup/rollup-linux-x64-gnu` pin is the one cleanup item that
  actually executes in CI (not just locally on macOS) — verify a CI run green after touching it,
  not just a local install.
- `FromQuery::query()` in all three export classes must keep producing a Laravel-Excel-compatible
  query; this isn't touched by the upgrade itself.

## Open Implementation Decisions

- **`@rollup/rollup-*` pins**: realign the exact `4.9.5` pins to a version matching what Vite 8's
  bundled Rollup actually needs, or remove them from `optionalDependencies` entirely and let
  npm's platform-optional-dependency resolution handle it unpinned. Either resolves the staleness;
  the choice doesn't change what the project guarantees, so it's left for implementation to decide
  against how npm resolves optional platform binaries in the CI (`ubuntu-latest`) environment.
- **`@eslint/js` removal**: delete the devDependency outright (confirmed unreferenced in
  `eslint.config.js`), or leave it bumped-in-place if a future flat-config change wants
  `js.configs.recommended`. Recommend removal; either is a no-op for current behavior.
- **Upgrade sequencing within a tranche**: e.g. whether the eslint-tooling trio lands in one commit
  or three, whether TypeScript/vue-tsc land before or after the eslint trio. No functional
  difference; left to `my-feature-planning`/implementation to sequence for reviewable commit size.

## Tests / Behavioral Impact

- Existing Pest suite (156 files) must stay green through every tranche — this is the regression
  net for everything except the two items called out below.
- `maatwebsite/excel` v4: `tests/Unit/Exports/{Clients,Agents,Carriers}ExportTest.php` must pass
  unmodified (they exercise `headings()`/`map()`, the concerns most exposed to the major bump).
  The `Excel::download()` call path itself has **no automated coverage** by locked decision above —
  manually exercise all three export downloads before this lands.
- TypeScript 6.0 / vue-tsc 3.x: `npm run types:check` (`vue-tsc --noEmit`) must pass clean; no
  tsconfig changes are expected per the Current State audit, but this must be confirmed against the
  real bump, not assumed from the audit alone.
- eslint 10 / eslint-plugin-vue 10: `npm run lint:check` must pass clean; watch for any flat-config
  rule renames surfaced only at run time (registry peer-dependency checks didn't surface rule-level
  breaking changes, but weren't exhaustive of every rule).

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
| @rollup/rollup-linux-x64-gnu/win32-msvc | 4.9.5 (stale pin) | realigned or removed | cleanup |
| @eslint/js | 9.39.4 (unused) | removed (recommended) | cleanup |

## Source References

- `composer.json`, `composer.lock`, `package.json`, `package-lock.json` — manifests.
- `eslint.config.js` — flat config built via `defineConfigWithVueTs`, `vueTsConfigs.recommended`.
- `tsconfig.json:14,32,34,35,68` — target/module/moduleResolution/baseUrl/outFile settings audited
  against TS 6.0's removed/deprecated surface.
- `.github/workflows/linter.yml`, `.github/workflows/tests.yml` — CI verification gates.
- `app/Exports/{Clients,Agents,Carriers}Export.php` — Laravel-Excel concerns usage.
- `app/Actions/{Clients,Agents,Carriers}/Export*ToExcelAction.php`,
  `app/Http/Controllers/{Clients,Agents,Carriers}/*ExcelExportController.php` — `Excel::download()`
  call sites, uncovered by automated tests.
- `tests/Unit/Exports/{Clients,Agents,Carriers}ExportTest.php` — existing coverage for the export
  concerns.
- `resources/js/**` (5 files) — `useVModel` usage from `@vueuse/core`, confirmed unaffected by the
  v13/v14 bumps.
