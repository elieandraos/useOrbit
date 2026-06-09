# useOrbit — UI Component Rewrite Plan

## Context

useOrbit is an insurance broker SaaS (Lebanese market). The current Laravel/Inertia/Vue/TS starter kit ships with **Reka UI** as its headless component library. The goal is to replace every Reka UI component with custom, fully-owned Vue components that implement the **Meridian design system** — no third-party primitives.

We work **one component at a time**. User reviews and approves each before moving on.

---

## Design System — Meridian

**Direction:** Calm institutional — slate neutrals + indigo accent (Linear/Stripe lineage).

**Fonts:** Geist (sans + display), Geist Mono. Loaded via Google Fonts.

### Tailwind Theme Configuration (`resources/css/app.css`)

All design tokens are registered in the Tailwind v4 `@theme` block so every component uses Tailwind utilities like `bg-surface`, `text-primary`, `text-accent` etc. — no hardcoded hex anywhere.

```css
@theme {
  /* Fonts */
  --font-sans: 'Geist', ui-sans-serif, system-ui, sans-serif;
  --font-mono: 'Geist Mono', ui-monospace, monospace;

  /* Backgrounds */
  --color-bg-app:     #fafafa;
  --color-bg-surface: #ffffff;
  --color-bg-sunken:  #f4f4f5;
  --color-bg-sidebar: #ffffff;

  /* Borders */
  --color-border:        #e7e5e4;
  --color-border-strong: #d4d4d8;
  --color-border-subtle: #f0efee;

  /* Text */
  --color-text-primary:   #18181b;
  --color-text-secondary: #52525b;
  --color-text-tertiary:  #a1a1aa;
  --color-text-inverse:   #ffffff;

  /* Accent (indigo) */
  --color-accent:       #3955d6;
  --color-accent-hover: #2f48b8;
  --color-accent-bg:    #eef0fb;
  --color-accent-ring:  #c5cdf3;
  --color-accent-fg:    #ffffff;

  /* Status */
  --color-success:    #15803d;  --color-success-bg: #ecfdf5;
  --color-warning:    #b45309;  --color-warning-bg: #fef3c7;
  --color-danger:     #b91c1c;  --color-danger-bg:  #fef2f2;
  --color-info:       #0369a1;  --color-info-bg:    #eff6ff;

  /* Border radius */
  --radius-sm:   6px;
  --radius-md:   10px;
  --radius-lg:   12px;
  --radius-xl:   16px;
  --radius-pill: 999px;

  /* Shadow */
  --shadow-card: 0 1px 2px rgba(15,23,42,.04), 0 1px 1px rgba(15,23,42,.03);
}
```

### Key Design Rules
- Buttons: 4 variants (primary / secondary / ghost / destructive), 3 sizes (sm=28px / md=34px / lg=40px), `rounded-[10px]`.
- Inputs & Selects: 36px tall, `rounded-[10px]`, focus = indigo border + 3px `accent-ring` outline.
- Date inputs: always 3 inline steppers (day/month/year pill inputs) — **never a calendar popover**.
- Tabs: underline variant for top-level views (optional count chip); segmented/pill variant for filters.
- Cards: `rounded-[12px]`, `shadow-card`, 1px border.
- Table pagination: always "Showing 1–N of N · Previous · 1 2 3 · Next" pattern, sunken footer bar.
- Long forms (Add/Edit): centered `max-w-[1100px] mx-auto`, single-column card stack, no step rail/numbers.
- Address fields order (everywhere): Street · Building/Floor · City · Governorate · Country.
- No "Save draft" affordances anywhere in the app.

---

## Implementation Priority

Each component replaces its Reka UI counterpart in `resources/js/components/ui/<name>/`. Pages that import the old component stay import-compatible.

### Phase 1 — Design Foundation
| # | Task                                                       | File                    |
|---|------------------------------------------------------------|-------------------------|
| 1 | **Tailwind theme** — register Meridian tokens + Geist font | `resources/css/app.css` |

---

### Phase 2 — Primitive Components
| #  | Component     | Key specs                                                                               |
|----|---------------|-----------------------------------------------------------------------------------------|
| 2  | **Button**    | 4 variants × 3 sizes; `leading`/`trailing` icon slots; `full` prop                      |
| 3  | **Badge**     | 6 tones (neutral/success/warning/danger/info/accent); optional `dot` prefix; pill shape |
| 4  | **Input**     | `leading`/`trailing` slots; focus ring; sm + md; disabled state                         |
| 5  | **Textarea**  | Same geometry as Input                                                                  |
| 6  | **Select**    | Chevron trailing; focus ring; 36px height                                               |
| 7  | **Label**     | Pairs with form fields                                                                  |
| 8  | **Checkbox**  | Indigo checked; disabled state                                                          |
| 9  | **Avatar**    | Initials-based; deterministic hue from name; configurable `size`                        |
| 10 | **Separator** | Horizontal/vertical                                                                     |

---

### Phase 3+ — Deferred
Composite form components, navigation/overlay components, data display, and domain-specific components will be added as features are developed.

---

## App Shell (current sprint — after Phase 2)

The Meridian design uses a **horizontal top nav** (56px), not a sidebar. Design reference: `~/Downloads/useorbit/project/app-shell.jsx`.

### Files to rewrite
| File                                             | Change                                                                                                     |
|--------------------------------------------------|------------------------------------------------------------------------------------------------------------|
| `resources/js/components/AppShell.vue`           | Remove `SidebarProvider`; simple `<div class="flex flex-col min-h-screen bg-bg-app">` wrapper              |
| `resources/js/components/AppTopNav.vue` *(new)*  | 56px header: Brand + nav items (Dashboard only) + search bar + user menu. Active state = indigo underline. |
| `resources/js/components/AppContent.vue`         | Remove `SidebarInset`; plain `<main class="flex-1 overflow-auto">`                                         |
| `resources/js/layouts/app/AppSidebarLayout.vue`  | Rewrite to compose `AppShell` + `AppTopNav` + `AppContent`                                                 |
| `resources/js/components/NavMain.vue`            | Remove SidebarGroup/SidebarMenu; nav items consumed by AppTopNav                                           |
| `resources/js/components/NavUser.vue`            | Remove Reka DropdownMenu; avatar button + dropdown panel in AppTopNav                                      |
| `resources/js/components/AppLogo.vue`            | 26px indigo rounded square "O" + "useOrbit" text                                                           |
| `resources/js/components/AppLogoIcon.vue`        | Just the 26px indigo square "O" mark                                                                       |
| `resources/js/layouts/auth/AuthSimpleLayout.vue` | Update to Meridian tokens; use updated `AppLogoIcon`                                                       |

### Files to delete
| File                                             | Reason                        |
|--------------------------------------------------|-------------------------------|
| `resources/js/components/AppSidebarHeader.vue`   | No sidebar trigger in top-nav |
| `resources/js/components/NavFooter.vue`          | No footer links in design     |
| `resources/js/components/PlaceholderPattern.vue` | Unused                        |
| `resources/js/components/ui/sidebar/`            | Replaced by custom top-nav    |
| `resources/js/components/ui/input-otp/`          | Never used                    |
| `resources/js/layouts/app/AppHeaderLayout.vue`   | Unused alternate layout       |
| `resources/js/layouts/auth/AuthCardLayout.vue`   | Unused alternate layout       |
| `resources/js/layouts/auth/AuthSplitLayout.vue`  | Unused alternate layout       |

### Types cleanup
- `resources/js/types/ui.ts` — remove `AppVariant`
- `AppShell.vue` + `AppContent.vue` — remove variant prop

### Package cleanup (after all Reka UI replaced)
- Remove from `package.json`: `reka-ui`, `class-variance-authority`
- Keep `vue-sonner` until custom Toast is built (Phase 3+)

---

## Design File References
| File                                                    | Contents                                                                               |
|---------------------------------------------------------|----------------------------------------------------------------------------------------|
| `~/Downloads/useorbit/project/components.jsx`           | Button, Input, Select, Badge, Avatar, Card, Tabs, EmptyState                           |
| `~/Downloads/useorbit/project/meridian-foundations.jsx` | MButton, MInput, MStepper, MSwitch, MSeg, MTabs                                        |
| `~/Downloads/useorbit/project/meridian-extras.jsx`      | MField, MTextarea, MCheck, MRadio, MRadioCard, MMenu, MCarrier, MPolicyTimeline, MFile |
| `~/Downloads/useorbit/project/tokens.jsx`               | Canonical token values                                                                 |
