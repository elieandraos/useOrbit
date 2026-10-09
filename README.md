# useOrbit

**An insurance broker SaaS for solo brokers and small agencies.**

useOrbit brings clients, policies, insurance carriers, and agents into one workspace. It supports Medical, Automotive, Expat, Fire, Life, and Travel policies, with forms and details tailored to each insurance class.

I’m building it as a real product: working through the domain rules, the interface, and the less visible details that make everyday workflows reliable.

**Status:** Actively developed, pre-production. This repository contains the application and its ongoing engineering work.

## What it does

- Manage clients, carriers, carrier branches, and agents.
- Create and manage policies, including group medical policies with covered members.
- Attach documents and notes to clients and policies.
- Filter client, carrier, agent, and policy lists, export them to Excel, and export individual records to PDF.
- Record policy amounts in their selected currency, with an organization default currency pre-selected on new policies.
- Manage organization members, roles, invitations, and two-factor authentication requirements.
- Deliver in-app notifications with real-time updates.

## A quick tour of the engineering

If you’re exploring the code, these are useful starting points:

| Area | What to look for |
| --- | --- |
| [Document storage job](app/Jobs/StoreDocumentJob.php) | Queued processing, transactional claims, retry backoff, checksum verification, and failure handling. |
| [Policy modeling](app/Models/Policy.php) | A shared policy core with class-specific detail records for six insurance classes, plus covered members for group medical policies. |
| [Medical policy creation](app/Actions/Policies/CreatePolicyMedicalAction.php) | Creating the policy, its medical details, and covered members within one transaction. |
| [Policy validation](app/Http/Requests/Policies/StorePolicyMedicalRequest.php) | Conditional domain rules and validation messages written for the person completing the form. |
| [Organization scoping](app/Models/Scopes/CurrentOrganizationScope.php) | Organization-scoped queries backed by an explicit [organization context](app/Support/Tenancy/OrganizationContext.php). |
| [Policy entry flow](resources/js/pages/Policies/Create.vue) | Vue, TypeScript, Inertia, remembered selections, and shared form components. |
| [Notifications](app/Notifications/EnvelopeNotification.php) | A shared envelope for stored and broadcast notifications, queued delivery, [action-owned recipients](app/Actions/Notifications/NotifyAction.php), and a [real-time frontend listener](resources/js/composables/useNotificationsListener.ts). |
| [Medical policy tests](tests/Feature/Http/Policies/MedicalStoreTest.php) | Cross-organization references, duplicate policy numbers, conditional requirements, and financial boundaries. |
| [Document job tests](tests/Feature/Jobs/StoreDocumentJobTest.php) | Existing destination files, checksum mismatches, retries, cancellation, and duplicate claims. |
| [Development data](database/seeders/PoliciesSeeder.php) | Believable, related insurance scenarios across all six classes, including currency-aware amounts and group medical members, backed by [domain-consistent factories](database/factories). |

## Engineering approach

The application follows Laravel conventions, with controllers handling HTTP concerns, form requests owning validation, and focused actions implementing business operations.

A few decisions illustrate the approach:

- **Related writes belong together.** Creating a medical policy and its dependent records happens within a database transaction.
- **Background work needs failure behavior.** Document processing tracks its state, verifies existing destination files, and distinguishes useful diagnostic logs from safe user-facing messages.
- **Tenant context is explicit.** Organization-scoped queries require an established context; a missing context raises an exception.
- **Financial filters need meaning.** An amount range requires a selected currency.
- **Exports should agree with the interface.** Policy lists and Excel exports share their filters and ordering.
- **Tests should protect business rules.** The suite covers invalid combinations, authorization boundaries, and failure paths alongside successful workflows.

## Stack

- PHP 8.4 / Laravel 13
- Vue 3 / TypeScript / Inertia 3
- Tailwind CSS 4
- Laravel Fortify for authentication and two-factor authentication
- Laravel queues and Reverb
- Pest
- Laravel DomPDF and Laravel Excel

GitHub Actions runs the PHP test suite, frontend build, formatting, linting, and TypeScript checks.

## AI-assisted development

useOrbit is also where I apply and refine [Agentic Engineering](https://github.com/elieandraos/agentic-engineering), my public workflow for investigation, planning, implementation, review, and shipping.

The workflow grows from real project work: observing what helps, where agents make mistakes, and turning those findings into better instructions and verification.

AI contributes substantially to implementation. I own the product decisions, engineering tradeoffs, review, and acceptance of the result.

## Run locally

### Requirements

- PHP 8.4
- Composer
- Node.js and npm

### Setup

```bash
composer run setup
```

This installs PHP and npm dependencies, copies `.env.example` to `.env` if needed, generates the application key, runs migrations, and builds frontend assets.

`.env.example` defaults to SQLite, the database queue, Reverb broadcasting, and the log mailer. Review `.env` if you want a different local configuration.

To seed reference data (countries, states, currencies), plus local development records when `APP_ENV=local`:

```bash
php artisan db:seed
```

### Development

```bash
php artisan dev
```

Starts the registered dev processes (`php artisan dev:list` shows them):

- `server` — `php artisan serve`, the application server
- `queue` — `php artisan queue:listen`, the queue listener
- `logs` — `php artisan pail`, log tailing
- `vite` — `npm run dev`, the Vite dev server
- `reverb` — `php artisan reverb:start`, the Reverb WebSocket server, for real-time notifications

The scheduler is not one of them. To run scheduled tasks locally, such as the daily `model:prune` that removes stalled document uploads and expired invitations, start it separately:

```bash
php artisan schedule:work
```

### Verification

Run the PHP tests:

```bash
php artisan test --compact
```

Run the combined build, formatting, lint, type, and test checks:

```bash
composer run ci:check
```

Run tests with coverage, with a coverage driver installed:

```bash
php artisan test --compact --coverage
```
