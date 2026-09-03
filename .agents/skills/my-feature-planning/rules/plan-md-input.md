# Plan.md as Canonical Input

`my-architecture-laboratory`'s Plan Synthesis track can hand off an approved, initiative-specific section of `plan.md` — architecture, locked product decisions, preserved behavior, and stated constraints, already reasoned through and approved by the user before feature planning ever starts. This file governs how `my-feature-planning` consumes that handoff. It's an **additional input path, not a requirement**: normal conversation-driven planning remains valid whenever no matching approved plan exists.

## Recognizing an approved section

A `plan.md` section is in scope for this rule only when all three hold:

- **Initiative match** — the section matches the initiative being planned; a section for an unrelated or already-shipped milestone doesn't apply.
- **Source-of-truth statement** — it contains the semantic statement Plan Synthesis's contract (`my-architecture-laboratory/rules/plan-synthesis.md`) requires near the top: that the section is the source of truth for the subsequent feature-planning pass. Judge the statement's substance, not a literal phrase. This statement identifies the section as an *intended* feature-planning handoff — it is **necessary but not sufficient** evidence of approval, because Plan Synthesis writes this statement into the plan before presenting it for approval, so a still-unapproved draft can already carry it.
- **Known approval** — the user's explicit approval of that plan or section is established from reliable context (stated in this conversation, or clearly confirmed in prior context you can point to). File existence and polished wording do not prove approval; only the user does.

If explicit approval can't be established this way, ask the user rather than guessing or inferring it from the document's presentation — don't treat an unapproved (or ambiguously approved) draft as canonical. Don't invent a persistent approval marker or an exact required phrase to check for. Once explicit approval is established, trust the approved handoff as canonical; feature planning does not rerun Plan Synthesis's internal review pass. If the user names an initiative and no matching approved section exists, fall back to the ordinary conversation-driven workflow.

## Canonical source of truth

Once a matching approved section is confirmed:

- Its architecture, locked decisions, preserved-behavior list, and stated constraints are canonical. Read them from the file — don't reconstruct them from conversation history, and don't re-litigate a decision the plan already settled.
- Current code and other relevant authoritative evidence (configuration, schema, tests, runtime behavior) remain authoritative for implementation details and for anything that has visibly changed since the plan was written — the plan itself says as much ("implementation details must still be verified against the codebase"). A stale reference inside the plan is a reason to re-check the evidence it points to, not a reason to distrust the plan's decisions.

## Respecting the locked/open distinction

`plan.md` sections written by Plan Synthesis mark every claim as one of: current-state fact, locked decision, derived architectural constraint, or open implementation detail (see that skill's locked-vs-open rule). Feature planning must preserve that distinction, not flatten it:

- **Locked decisions** — preserve exactly, always. These are product calls the user already made. Don't soften, expand, or reinterpret them while drafting issues, and don't let anything downstream (a stale design mockup, a codebase that drifted, a "cleaner" alternative) override one silently.
- **Derived architectural constraints** — these follow from the plan's stated facts plus its locked decisions, not from a product decision of their own. Treat them as binding *as long as the premise they were derived from still holds*. Current code and other relevant authoritative evidence remain authoritative for implementation reality: before drafting issues, check whether the codebase, configuration, schema, tests, or runtime behavior has moved enough since the plan was written that the fact a constraint was derived from is no longer true. If it has, don't blindly preserve the now-obsolete reasoning — flag the discrepancy to the user before drafting the affected issues, the same way `rules/design-reconciliation.md` flags a design/shipped-app disagreement. This is a check for staleness in the *derivation*, not license to reinterpret the locked decision underneath it — if the constraint is still validly derived, it stands as-is.
- **Open implementation details** — Plan Synthesis leaves these for feature planning *or* implementation to resolve; ownership isn't assigned to this skill automatically. Resolve one here when the choice materially affects issue scope, dependencies, acceptance criteria, or the ability to produce a developer-ready issue. Leave it for implementation when multiple choices all preserve the same approved guarantees and the issue remains executable without picking one. Use the same test Plan Synthesis used to mark it open in the first place: would flipping the choice alter approved behavior or guarantees, a security boundary, ownership, lifecycle, an operator/user promise, or the target architecture? If yes, it's product/architecture-shaped rather than merely implementation-level — stop and ask the user rather than deciding it inside issue drafting, even though it was marked open. If it's resolved during planning, record the resolution in the relevant canonical issue definition — never silently promote an open detail into a locked decision.

## Handoff into feature planning

This rule affects only how an approved plan's architecture, locked decisions, derived constraints, and open implementation details enter feature planning. Once consumed, continue at classification in `SKILL.md`'s pipeline — every later step and gate `SKILL.md` defines remains mandatory. An approved plan supplies canonical input to that pipeline; it shortcuts nothing in it.

## Division of responsibility

`my-architecture-laboratory` owns producing the approved plan — investigation, target-architecture design, locking decisions with the user, writing and reviewing `plan.md`. `my-feature-planning` owns everything downstream of an approved plan: classification, scope checklists, design reconciliation, canonical issue definitions, review, and the GitHub workflow. This rule doesn't create or amend `plan.md`, and it doesn't re-derive architecture `plan.md` already settled — that stays `my-architecture-laboratory`'s Plan Synthesis track.
