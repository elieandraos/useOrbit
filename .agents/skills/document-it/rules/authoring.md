# Creating and updating architecture guides

This file owns the operational mechanics of `SKILL.md`'s three guide-producing conditions: format
selection, writing a new guide, and updating an existing one. `SKILL.md` states the triggering
condition for each and hands off here; it does not restate the mechanics below. `rules/doc-style.md`
owns the writing grammar itself; `rules/maintenance.md` governs judgment calls once an update is
under way; `rules/review.md` judges the finished result.

## Format selection

- **Artifact** output uses `rules/template.html` and requires the `artifact-design` skill and the
  `Artifact` tool. Load `artifact-design` before writing any Artifact page.
- **Markdown**, for a **new guide**, lives under the consuming repository's own `docs/` directory;
  create that directory if it doesn't already exist. This default applies to a new guide only —
  updating an existing Markdown guide preserves its current path instead, including one outside
  `docs/` (see "Update an existing guide," below). Markdown has no `template.html` equivalent and
  does not need Artifact-specific tooling — it must work without it.
- If the requested format's publishing capability is unavailable (no `Artifact` tool, no write
  access to the target repository), explain that plainly and ask the user how to proceed. Never
  silently substitute the other format and report success as if the original request were
  fulfilled.

## Document existing architecture (new guide)

1. **Establish evidence.** Confirm the architectural surfaces the guide will cover are backed by
   concrete, current evidence — implementation, configuration, schema, tests, runtime behavior.
   Reuse what's already sufficient and current; route missing or stale evidence through `lab-it`.
   Identify the **architectural center of gravity** — the one idea everything else hangs off (a
   polymorphic contract, a queue pipeline, a runtime subsystem boundary, a sync-vs-async split) —
   as a hypothesis the writing step will need.

2. **Recap and confirm.** Write the recap directly as chat output, not a file or an artifact.
   Cover, with concrete implementation references threaded throughout, whichever of these actually
   apply to the system: the problem being solved, the core architecture, reusable pieces versus
   integration-specific code, runtime behavior, the data model, the integration seam, security,
   testing, architectural decisions, and remaining gaps. Don't invent a data model, runtime
   lifecycle, security boundary, reuse seam, or integration split the system doesn't have just to
   fill out the list. Keep it bullet-driven and honest about gaps. **Stop here and wait for the
   user to confirm before this investigation becomes a published guide.** A correction here is
   real signal about what the guide needs to get right.

3. **Write and publish/save the guide.** Only after the recap is approved, and only after format
   selection (above):
   - Decide the document's structure from the confirmed center of gravity. Architecture guides do
     not use one fixed section inventory: structure follows the system being explained. See
     `rules/doc-style.md` for the writing grammar and content-block vocabulary — it covers both
     Markdown and Artifact output.
   - **Artifact:** load the `artifact-design` skill (required before writing any Artifact page).
     Write the HTML using `rules/template.html` as the starting scaffold. Replace content; keep
     the design system unless the system genuinely needs a new block type. Publish with the
     `Artifact` tool: title `"{Capability} Architecture"`, a one-sentence description, and a
     stable, domain-appropriate favicon (see `rules/doc-style.md#choosing-a-favicon-artifact-only`).
   - **Markdown:** write the guide under the consuming repository's `docs/` directory (create it
     if needed), applying the same writing grammar and content-block vocabulary in Markdown-native
     form (see `rules/doc-style.md`).
   - **Both:** produce both outputs from the same confirmed recap and center of gravity, and give
     each a minimal, discoverable cross-reference to the other's location (see `SKILL.md`'s
     "Maintaining both formats").
   - Run `rules/review.md`'s checklist against the finished guide(s) before considering the work
     complete — see `SKILL.md`'s "Output-specific non-negotiables" for what the guide itself must
     do.

## Update an existing guide

**Locate and inspect the actual target before updating it.** Discover it from what's supplied: a
stated file path or Artifact URL, an existing cross-reference from another guide or from project
documentation, or a repository search by capability name. Ask the user when identification remains
ambiguous — don't guess, and don't assume the target's content from memory.

**Distinguish three different situations**, since each needs a different response:

- **Genuinely missing** — no guide exists yet at the expected identity. This is a new-guide
  request, not an update; route to "Document existing architecture" above.
- **Inaccessible** — the target exists (a known file path or Artifact URL) but can't currently be
  reached or read.
- **Publishing capability unavailable** — the target is fine, but the tooling needed to update it
  (e.g., no `Artifact` tool in this session) is missing.

For the second and third situations, explain the limitation concretely and ask the user whether to:
prepare or update a Markdown version instead; create a replacement Artifact where that's possible;
or change which format is the maintained one going forward. State which document would change and
which would remain unchanged or stale — the user doesn't need to understand "draft versus
migration" to make this choice. **Never claim that writing a Markdown update updated the original
Artifact** — those are different documents once an Artifact can't be reached or written.

Once the target is confirmed reachable: compare its architectural claims against verified current
implementation, configuration, runtime evidence, and tests (reuse sufficient current evidence;
route to `lab-it` when it's missing or stale) → follow the complete affected claim graph, not only
the section where the change was first noticed → update to describe how the architecture works
now, not as a changelog → preserve identity (below) → run `rules/review.md` against the whole
updated guide, with emphasis on the changed claims and whatever depends on them.

`rules/maintenance.md` governs every judgment call in this route — read it before making any edit.
It preserves a guide's unaffected architectural meaning without freezing architecture the evidence
shows has genuinely changed: the center of gravity, structure, ownership, or reasoning can move
when verified reality requires it.

**Preserve identity by default:**

- **Markdown** — the same file path, even one outside the consuming repository's `docs/`
  directory; `docs/` is only the default location for a new guide, not a requirement imposed
  retroactively. Relocate only when the user explicitly authorizes it. Preserve unaffected front
  matter, title, and other metadata, while allowing the changes the requested update actually
  requires.
- **Artifact** — the same `url` and the same favicon.
