# The Review Checklist

## When to consult this file

After `rules/scope.md` has established the target, baseline, intended scope, and available
evidence — while inspecting the reviewed change itself.

## Philosophy

- Every category is skip-if-inapplicable, not skip-by-default. Skip a category only when the
  reviewed change genuinely doesn't touch what it checks, and say so in the report rather than
  leaving it silently absent.
- Every finding traces to concrete code, configuration, or a diagnostic command's actual output —
  never a suspicion stated as fact. See `rules/verification.md` for how a candidate finding earns
  that status.
- When a finding also depends on a requirement or project convention — not only on the
  implementation evidence itself — identify that source (an issue, `plan.md`, a PR description,
  project instructions, configuration, or an established repository convention) alongside the
  evidence and the finding's concrete consequence. Name a source only when one actually exists;
  never invent a requirement to back a finding that has none — say plainly that the finding rests on
  general engineering reasoning instead. This applies across every category below wherever it's
  relevant, not only "Project and stack convention compliance"; state it concisely next to the
  finding, not as a mandatory requirement-by-requirement table.
- A finding must materially affect correctness, security, data integrity, regressions,
  architectural fit, maintainability, convention compliance, test adequacy, or scope — a personal
  style preference the project's own conventions don't already support is not a finding (see "What
  this review does not flag," below).
- Inspect the changed code and the relevant surrounding behavior it touches, not only the lines the
  diff highlights — a caller, a shared helper, or an adjacent code path can be exactly where a
  change's real consequence shows up.

## Checklist

### Requirements compliance

Compare the reviewed change against the intended scope established in `rules/scope.md`. For each
capability the scope actually defines, confirm it's implemented — not silently narrowed, deferred,
or reinterpreted along the way. For each part of the diff, confirm it traces to something the scope
actually asked for; a part that doesn't belongs under "Accidental scope expansion," below, not a
silent pass here.

Skip only when no scope evidence exists at all (`rules/scope.md`) — state that absence as a
limitation in the report rather than skipping this category silently.

### Correctness and edge cases

Trace the actual code paths the change introduces or modifies against their real inputs, not only
the path the tests already exercise. For each new or changed branch, ask what input, state, or
ordering would take the other path, and confirm the change handles it. Check edge cases the
established scope implies even when untested: empty, null, or zero-valued inputs; boundary values;
repeated or concurrent invocation; partial failure mid-operation; an unexpected but reachable
ordering of surrounding calls.

A suspected defect becomes a finding only once traced to the actual code path that produces it —
state the reasoning or the reproduction, not just the suspicion.

### Security

Inspect every surface the change touches for:

- authorization and authentication checks that gate access to the new or changed capability, at
  every entry point that reaches it, not only the one exercised by tests;
- input validation and sanitization at trust boundaries — user input, external API responses, file
  paths, deserialized data;
- injection risk wherever the change builds a query, shell command, template, or file path from
  variable input;
- secret, token, credential, or sensitive-data exposure — in logs, error messages, client-visible
  responses, or a location with looser access control than the data warrants.

Skip only where the reviewed change genuinely touches none of these surfaces.

### Data integrity

For a schema migration or data-shape change: confirm it's reversible, or that its irreversibility
is deliberate and stated; check default values and backfill behavior against existing rows, not
only newly created ones. For a concurrent-write or shared-state path: check for race conditions,
a missing lock or transaction boundary, or a read-modify-write sequence that isn't atomic where
correctness requires it to be. Confirm the change cannot silently drop, truncate, or overwrite
data that existed before it.

Skip when the reviewed change touches no persisted or shared state.

### Likely regressions

Identify existing behavior the change plausibly affects that the test suite doesn't already cover —
a shared code path, a caller outside the diff's own files, a configuration or feature-flag
interaction the change alters the meaning of. This is distinct from correctness above: a regression
is existing behavior put at risk, not a new capability's own bug. State which existing callers or
behaviors were actually checked, and which weren't reachable to verify from available evidence.

### Architectural fit

Compare the change's shape against the approved architecture — a linked guide, `plan.md`, or an
established repository pattern, whichever is actually available (`rules/scope.md`) — and this
project's own conventions. Flag a change that duplicates an existing abstraction, bypasses an
established boundary, or introduces a second way to do something the codebase already does one
way, grounded in a concrete existing pattern the change actually conflicts with.

Skip when no architectural reference exists and the change is too small to imply one; state that as
a limitation rather than inventing an architecture to check the change against.

### Maintainability

Identify unnecessary complexity, duplication, or a shape likely to fight the next foreseeable
change: a premature abstraction, a helper introduced for a single call site, dead code left behind
by the change, or a structure that makes an adjacent change harder than it needs to be. Ground each
finding in the actual diff and a concrete foreseeable consequence — not a general preference for a
different style the project itself doesn't already establish.

Before reporting an abstraction or added layer of indirection as unnecessary complexity, run the
concrete thought experiment: if this layer were removed or inlined at its call site(s), would that
actually eliminate unnecessary complexity — or would it instead spread important knowledge and
responsibilities (a validation rule, a derivation, an invariant that has to hold everywhere it's
used) back out across every caller, making each one responsible for reproducing it correctly? A
layer whose removal would spread that knowledge or responsibility across callers is doing real work
— that result supports retaining it, not reporting it. A single current caller or a thin wrapper is
a fact about the code, not by itself proof of a problem — some single-caller abstractions exist
deliberately, to isolate a boundary the project has already established (a repository pattern, a
framework seam, a documented architectural layer, per "Architectural fit" above) even though only
one call site happens to use it today. Report a finding here only once the layer's own continued
existence has a concrete, demonstrated cost — actual duplication it introduces, actual navigation or
maintenance overhead it imposes, or an actual adjacent change it makes harder today — attributable to
the layer itself, never merely because it currently has one caller or because fewer layers would be
preferable in the abstract.

### Project and stack convention compliance

Check the change against applicable project instructions and, where one is installed and actually
applicable, the loaded stack companion's rules — naming conventions, established idioms, and
required patterns the companion documents for its stack. Skip only the specific sub-check that
depends on a custom stack companion's own rules when none is installed for this project; never
invent one of those from general knowledge and present it as this project's own rule.

A missing stack companion does not disable applicable framework checks elsewhere in this list — a
correctness, security, or architectural-fit concern grounded in project instructions, configuration,
established repository usage, or ordinary engineering reasoning about the stack still applies with
no companion installed (`rules/scope.md`'s "Discover applicable conventions"). Apply the sourcing
rule from "Philosophy," above, to any finding here: state whether it's grounded in an established
project requirement or in general technical reasoning with no such backing, rather than presenting
the latter as though it were a discovered project rule.

### Test adequacy

For each test added or changed by the reviewed change, confirm it actually proves the decision it
claims to — a test that only proves the code ran, with no assertion on the actual behavior or
output, or one whose mocked dependency hides the real behavior under test, is a finding. Confirm the
established scope's material edge cases have some test coverage, or state plainly which don't. A
passing test suite is not by itself evidence of adequate coverage — inspect what the tests actually
assert, not only whether they pass.

Also check whether an assertion's expected side independently proves the behavior under test, or
merely repeats the implementation back at itself. An expected value built by exercising the same
logic, class, or transformation the test exists to prove cannot catch a regression that stays
internally self-consistent, because the "expected" side moves in lockstep with the code under test —
that self-referential comparison is a finding when the test is the one specifically responsible for
proving that value or transformation is correct. Independence from the implementation is necessary
but not sufficient: an expected value that's literal, or derived by reasoning about the requirement
rather than by exercising the code under test, still has to actually match the behavior the change is
supposed to produce, and still has to be checked by an assertion that would actually fail if that
behavior regressed — a literal value that's simply wrong, or an assertion too weak to catch the case
it's supposed to prove, is its own finding, not a pass merely because it was derived independently.
It is not a finding when what the test is actually responsible for proving is integration — that
the right object
reached the right collaborator, or the right model reached the right endpoint — and a separate,
lower-level test already owns that value's own correctness; a legitimate integration assertion and
established test-layer ownership are not defects. Where the Laravel companion is installed, its
`blueprints/pest-testing.md` warning and `rules/test-ownership.md`'s no-redundancy rule work through
one concrete version of this distinction: comparing an HTTP response against a Resource built from
the same production Resource class proves wiring, while the Resource's own test owns proving each
field's actual transformation. This is a distinction to apply, never a blanket rule against mocks,
database assertions, or any other particular testing style — the concern is specifically an
expectation that repeats the implementation instead of independently proving the behavior under
test, not the technique used to express it.

### Accidental scope expansion

Compare every changed file and hunk against the established intended scope (`rules/scope.md`).
Flag a change outside that scope that wasn't already flagged as a stop, per
`implement-it/rules/review-gates.md`'s "when to stop and ask" — an unrelated refactor, an
unrequested dependency bump, a drive-by rename, a file touched with no connection to the stated
scope. Not every out-of-scope change is a defect; some are legitimate and already authorized
elsewhere. An unflagged one is still a finding to surface, not something to silently accept because
it looks harmless.

An explicitly authorized scope change is exempt from this category alone — it is not accidental,
so it is not a finding here. It is not exempt from anything else: run every other applicable
category (Correctness and edge cases, Security, Data integrity, Likely regressions, Architectural
fit, Maintainability, Project and stack convention compliance, Test adequacy) against it exactly as
thoroughly as against any other part of the diff. The authorization approved doing the work; it did
not verify the work, and it never suppresses a defect discovered in it or stands in as acceptance of
that defect's consequences.

## What this review does not flag

- A stylistic preference the project's own conventions don't already support.
- A speculative concern with no traced code path or reproducible evidence behind it.
- A category the reviewed change genuinely doesn't touch — state it as skipped, not as a pass.
- An explicitly authorized scope change, as Accidental scope expansion's own finding — never as an
  exemption from any other category. A defect discovered in that same change is reported exactly as
  it would be anywhere else in the diff; the authorization approved the work happening, not its
  correctness, security, or safety.
- A finding invented to avoid returning a clean result. A scoped clean pass, stated plainly with
  which categories applied, is a legitimate and complete outcome.
