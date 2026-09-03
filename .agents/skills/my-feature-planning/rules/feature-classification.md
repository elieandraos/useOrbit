# Feature Classification

Classify before picking a checklist — applying single-resource assumptions (its own persisted
identity, slugs, archive/unarchive, exports) to a capability with no single owning entity over-scopes
cross-cutting work; skipping shared-infrastructure thinking under-scopes it.

Applies identically to Planned work and to a validated finding from `rules/discovered-work.md`'s
intake — origin never predetermines shape.

## The four shapes

**A. New resource / first-class domain entity** — a brand-new entity with its own persisted identity
and a meaningful lifecycle, where operations center on that entity and authorization applies to it
where relevant. → `rules/resource-feature-checklist.md`. The consuming stack determines the concrete
storage, domain, authorization, and interface artifacts; those artifacts do not define the category.

**B. Cross-cutting application capability** — behavior or infrastructure whose organizing
responsibility spans multiple existing domains rather than one new entity's own lifecycle (a
notification system, a shared search/reporting layer, a background-processing pipeline, an external
integration). It may persist its own state, even own tables, and still be B — the state supports
cross-cutting behavior rather than being a resource users manage through its own CRUD lifecycle. →
`rules/capability-checklist.md`.

**C. Extension of an existing capability** — new behavior added to something that already ships (a
new filter, export shape, child entity, or integration point). Route only the affected slice: a
CRUD-shaped extension uses the relevant tracks of `resource-feature-checklist.md`; an extension to a
cross-cutting capability uses the relevant questions of `capability-checklist.md`. Never replan the
whole existing parent capability for one changed slice.

**D. Architectural/refactor milestone** — no new user-facing capability; the outcome is structural,
corrective, or refactoring-oriented (a rename sweep, a cleanup, a pattern migration). Route loosely
through `capability-checklist.md`'s outcome/boundaries/preserved-behavior/exclusions/dependencies
questions — most of the rest doesn't apply, and D isn't a fixed checklist template of its own.

## Choosing between them

Classify by organizing responsibility, not implementation footprint. For mixed characteristics,
classify by the primary organizing responsibility and apply secondary checklist questions only where
the actual scope warrants it, rather than forcing two full checklists for symmetry. Ask the user only
when the classification would change which questions get asked or materially change scope, and the
evidence genuinely supports more than one shape.

## Worked examples

- A new "Project" entity users create, configure, and manage through its own pages → A
- An audit-logging capability recording events from several existing resources → B
- "Add a CSV export to an existing resource's list page" → C (resource-shaped slice)
- "Add a delivery channel to an existing notification capability" → C (capability-shaped slice)
- "Replace three duplicated permission checks with one shared helper, no visible behavior change" → D
