# useOrbit

## Context
useOrbit is an insurance broker SaaS (Lebanese market). 

## Design File References
file located in `_design/`

---

## Client Add / Edit — UI Components

### Components to Build (new)

| Component | Description |
|---|---|
| `MField` | Field wrapper — label, optional/required badge, helper text, error + success hint states |
| `MStepper` | Numeric stepper pill with +/− controls (building block for dates) |
| `DateStepperGroup` | 3 inline `MStepper` pills for day/month/year entry |
| `GenderChips` | Radio-style toggle button group (Female/Male/Non-binary/Other) |
| `PhotoUpload` | Avatar upload block — "new" mode (dashed circle + upload btn) and "edit" mode (initials + change/remove btns) |
| `FormSection` | Titled card wrapper for form groups (title + subtitle + content slot) |
| `NewClientStepsHorizontal` | Horizontal 4-node step progress bar with connectors (add screen only) |
| `NewClientProgress` | Vertical sidebar step navigator (add screen only) |
| `AuditStrip` | Monospace metadata row: created date / updated date / by whom (edit screen only) |
| `UnsavedBar` | Sticky dirty-state bar — amber dot + "Discard" + "Save changes" (edit screen only) |
| `DangerZone` | Red-bordered card with archive + delete rows (edit screen only) |

### Existing Components — Already in Design Foundation

- `Button`, `Input`, `Select`, `Avatar`, `Separator`, `DropMenu` + `DropMenuItem`

### Existing Components — Built but NOT in Design Foundation

| Component | File | Used in screens |
|---|---|---|
| `Breadcrumbs` | `ui/breadcrumbs/Breadcrumbs.vue` | Page chrome on both screens |
| `Card` family | `ui/card/Card.vue` + sub-components | Can back `FormSection` |

Also missing from design foundation (not relevant to these screens): `Tabs`, `Alert`, `Sonner`, `Spinner`, `Skeleton`.

---
