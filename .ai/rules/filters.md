---
paths:
  - 'app/Filters/**'
---

# Filters

## Compare date-cast columns with whereDate
Eloquent `date`-cast columns (e.g. policies.effective_date / expiry_date) are written as `Y-m-d 00:00:00`. In SQLite (the test DB) a plain `where('expiry_date', '>=', '2026-01-15')` compares strings and silently misses boundary days; use `whereDate()` so PHP and SQL boundaries agree. The policy display status (`PolicyDisplayStatus::resolve()` ↔ `PolicyFilter::status()`) relies on this.
