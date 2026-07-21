# Orbit

## Local Installation

### Requirements

- PHP 8.4
- Composer
- Node.js & npm

### Setup

```bash
composer run setup
```

This installs PHP dependencies, copies `.env.example` to `.env`, generates the app key, runs migrations, installs npm dependencies, and builds frontend assets.

### Running locally

```bash
composer run dev
```

Runs the following concurrently:

- `php artisan serve` — the app server
- `php artisan queue:listen` — the queue worker
- `php artisan pail` — log tailing
- `npm run dev` — the Vite dev server
- `php artisan reverb:start` — the Reverb WebSocket server, for broadcast notifications

### Task Scheduler

If you need the task scheduler running (e.g. to test scheduled commands like `documents:prune-stale`), start it separately — it's not included in `composer run dev`:

```bash
php artisan schedule:work
```

### Running tests

```bash
php artisan test --compact
```

With coverage:

```bash
php artisan test --compact --coverage
```