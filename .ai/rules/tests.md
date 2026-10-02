---
paths:
  - 'tests/**'
---

# Tests

## SQLite test DB rounds 15-digit decimals read back through Eloquent
Tests run on in-memory SQLite, which returns DECIMAL(15,2) columns as PHP floats. The `decimal:2` cast then stringifies them at 14-digit precision, so 9999999999999.99 read back from the DB becomes "10000000000000.00". MySQL (production) returns decimals as strings and is unaffected. When asserting max-amount rendering, use the in-memory model (no fresh()/refresh()) or assertDatabaseHas, and pass amounts as strings.
