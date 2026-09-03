# Discovered Work

`SKILL.md`'s two work origins separate Planned work (already-understood or approved scope) from
Discovered work (an unexpected finding surfaced during implementation, review, testing, operations, or
another workflow). This rule governs the Discovered path — turning a raw finding into validated
planning input before it reaches `rules/feature-classification.md` or its downstream checklists, since
running those on a raw finding is how a vague or wrong issue gets drafted.

## 1. Purpose and boundary

A raw finding — a report, an observed symptom, a log entry, or a code-review concern — is not yet
developer-ready scope. That it exists doesn't confirm the underlying behavior: the affected behavior,
its scope, and its cause may still be unconfirmed, and planning must never silently promote an inference
into a fact. A first symptom alone is never sufficient to justify a vague issue. This rule owns the
transition from a raw finding to one of: validated planning input, no actionable gap, or a
blocked/unresolved finding.

Investigation may:
- inspect relevant code and configuration;
- inspect available logs, traces, or recorded evidence;
- reproduce behavior when practical and safe (§2);
- run existing safe diagnostics or tests;
- compare observed behavior against approved decisions and established behavior.

It may not:
- edit application code or implement the fix;
- add instrumentation as an unapproved side change;
- alter live or production state merely to reproduce a report;
- manufacture an issue when the evidence can't support coherent scope.

New instrumentation may become an approved issue Task instead, once the stopping condition (§9) permits
it.

## 2. Safe reproduction

Reproduce only when practical, authorized, and non-destructive — prefer existing evidence, a safe
environment, existing tests, and reversible diagnostics over anything that mutates state, and never
mutate production or live data merely to confirm a report. State any reproduction or access limitation
honestly. Lack of reproduction doesn't itself invalidate a finding, but it constrains what the eventual
issue can honestly claim and whether it's scopeable at all.

## 3. Intake and evidence discipline

Work through this proportionally — a shallow finding can clear all of it quickly:

1. Capture exactly what was reported or observed, and under which known conditions.
2. Separate confirmed facts from reasonable inferences, assumptions, and unknowns; a hypothesis isn't a
   fact until reproduction or other evidence confirms it.
3. Reproduce or corroborate the behavior when practical and safe.
4. Determine whether the first symptom is the actual problem or only one manifestation of it.
5. Investigate enough blast radius to bound the affected behavior and its exclusions honestly.
6. Determine whether the behavior is defective, intended, drift from an approved target, or still
   ambiguous.
7. Check applicable approved product or architecture decisions.
8. Identify any material decision that still requires the user.
9. Decide whether the finding is scopeable now, needs a checkpoint, or is currently blocked.
10. Hand validated input to the normal planning pipeline.

Don't require exhaustive root-cause analysis when it doesn't improve scope.

## 4. Investigation-depth bands

Proportional depth, not a rigid state machine: begin at the least expensive band that can answer the
planning question, deepen only when remaining uncertainty materially affects scope, safety, guarantees,
or developer readiness, and stop once §6's contract is satisfied or a legitimate blocker is identified.

- **Shallow** — the symptom and cause are obvious and deterministic: one plausible mechanism, easily
  confirmed. Once confirmed, the remaining work is verifying completeness and blast radius, then
  stopping.
- **Focused** — multiple plausible causes exist, or the symptom crosses feature boundaries. Reproduce,
  isolate variables, and falsify wrong hypotheses one at a time instead of running with the first
  plausible story, pushing to an actual root cause when it's reasonably reachable with the access
  already available.
- **Deep** — destructive, security-, or data-integrity-relevant behavior; cross-layer or
  protocol-level interaction; contradictory evidence; or a symptom that may hide a materially different
  defect underneath. Trace across whatever boundaries are necessary and prove the claims that matter
  rather than asserting them. Deep investigation still has a legitimate stopping point — see §9.

## 5. Evidence checkpoints

A checkpoint is a visibility and decision mechanism, not a fixed time or token budget — neither an
automatic stop nor an automatic command to continue. It gives the user a decision-ready view whenever the
next step would materially change cost, access, invasiveness, or scope confidence.

Surface one when, for example:
- a major hypothesis has been falsified;
- investigation must cross into a new system, provider, vendor, or tooling boundary;
- a distinct runtime or ownership layer becomes relevant;
- remaining evidence would require unavailable access, special tooling, or invasive instrumentation;
- a natural point is reached where coherent scope may already be possible.

At a checkpoint, report concisely: what's confirmed; what's been ruled out, and how; what remains
unknown; the next useful diagnostic step; and whether the finding is already scopeable.

## 6. Validated-finding readiness

A finding is ready for the normal pipeline once it establishes, as applicable:
- the observed behavior and the conditions under which it occurs;
- the boundary between confirmed facts and remaining unknowns;
- the expected or intended behavior, or an honest classification of the discrepancy;
- enough blast radius to bound the outcome and meaningful exclusions;
- security, privacy, data-integrity, or operational boundaries when material;
- a coherent outcome and its proof obligations;
- no unresolved material product or architecture decision blocking developer-ready scope.

Exact root cause and the final implementation mechanism aren't always required. Uncertainty is
acceptable only when it's bounded and doesn't make the issue speculative, misleading, or non-executable.

## 7. Authority and decisions

Approved locked decisions remain authoritative unless the user explicitly amends them; current behavior
contradicting an approved target is normally drift, not grounds to silently reopen the decision.
Derived-constraint staleness against current code is `rules/plan-md-input.md`'s check, not this rule's.
An unresolved material product or architecture choice blocks the affected drafting and routes through
the applicable owning decision gate; an open implementation detail may stay unresolved only under
`rules/plan-md-input.md`'s executability test.

## 8. Disposition and readiness

Classify the evidence, then assess drafting readiness separately.

**A. Disposition**
1. **Confirmed current defect** — violates an already-applicable expectation, not an adjacent gap or
   approved-target drift; no material decision remains unresolved.
2. **Intended behavior, no actionable gap** — record it; no issue.
3. **Intended behavior, adjacent gap** — scope the gap (communication, documentation, operations, UX),
   not a mislabeled defect.
4. **Drift from an approved target** — approved decision stays authoritative unless amended; scope
   drift.
5. **Unresolved material decision** — outcomes diverge materially; stop drafting; route to owning gate.
6. **Not yet classifiable** — state what's missing, what would resolve it; don't assume defect from
   tone.

**B. Readiness.** Disposition alone doesn't authorize drafting:
- defect, adjacent gap, or drift proceeds under §6; unresolved mechanism becomes a Task under §9;
- no actionable gap: no issue; unresolved material decision blocks drafting;
- a not-yet-classifiable finding requires further investigation or remains blocked — it never
  automatically becomes an instrumentation issue.

## 9. Stopping condition

> Investigate until the finding can be scoped honestly, not necessarily until its complete root cause or
> fix is known.

Stopping before a complete explanation is valid when further work is disproportionate to its planning
value, necessary access or tooling is unavailable, confirming the mechanism would require invasive
instrumentation or implementation work, or the remaining unknown doesn't prevent a coherent, executable
issue.

Unavailable evidence doesn't automatically make a finding scopeable. If the remaining unknown prevents a
reliable outcome, boundary, or proof obligation, report a blocker instead of drafting. An unresolved
mechanism may become a Task only when the issue still describes a coherent outcome and an implementer
can proceed without guessing a product or architecture decision.

## 10. Handoff

This rule's special responsibility begins with a raw finding and ends once it's dismissed, blocked, or
validated into honest planning input. From there the finding is ordinary planning input:

- classify it (`rules/feature-classification.md`) — a validated Discovered finding may have any shape;
  origin never determines classification;
- reconcile applicable approved inputs through the appropriate rules;
- draft it (`rules/issue-conventions.md`);
- decompose and sequence it (`rules/sequencing.md`) — Planned and Discovered work share one
  decomposition and dependency method. Scope membership and dependency-edge creation are separate
  decisions: surfacing during implementation doesn't by itself make a finding dependent on the issue
  that exposed it. Once validated, merge it into existing canonical scope or add a distinct issue via
  the same coherent-outcome test, adding a dependency edge only where a real prerequisite exists;
- validate it (`rules/review.md`);
- pass `SKILL.md`'s approval gates before creation.

This rule doesn't fix code, and it doesn't create a second, lighter issue-authoring path.

After approved issue creation, `my-git-workflow` owns the downstream Git/GitHub delivery
workflow — branch readiness, live readiness, commits, verification, and closure — composing with the
consuming project's own implementation skills, which own the actual application/framework code.
Approved canonical scope is the implementation starting point, and implementation shouldn't
repeat intake investigation by default — but new evidence that materially contradicts a confirmed fact,
assumption, approved decision, or scoped outcome must not be ignored; route it through the applicable
review, discovered-work, or human decision gate. An issue that explicitly includes investigation or
instrumentation work necessarily expects some uncertainty to be resolved during implementation.
