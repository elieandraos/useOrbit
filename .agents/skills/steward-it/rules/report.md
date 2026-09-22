# Steward Report

## When this applies

Load for every non-trivial stewardship pass after the available evidence has been reconstructed.

## Standard baseline

Keep the default report compact but use the same semantic section order so reports remain comparable across sessions:

1. **Expected** — what the user expected and why stewardship was requested.
2. **Execution** — total elapsed, active time, human wait, and phase breakdown when measurable.
3. **Context / usage** — output, thinking, cache creation/read, and usage by skill when attributable.
4. **Skill activation** — expected applicable skills, observed loaded skills, and mismatches when supported by evidence.
5. **Verification / workflow** — material verification, approvals, review, commit, closure, and other workflow events.
6. **Findings** — confirmed problems, or no material finding.
7. **Cause** — evidence-backed classification, separated from inference.
8. **Pattern** — one-off, recurring, or unknown from available evidence.
9. **Recommendation** — smallest justified improvement and likely owner.
10. **Evidence record** — only when durable evidence is applicable.

The semantic structure is stable; presentation inside a section may adapt to the evidence. Use tables when they make timing, usage, attribution, or other comparable measurements easier to inspect. Use concise prose when the evidence is better expressed narratively. Do not omit or rename baseline sections merely because a session has unusually little or unusually rich evidence; state `not applicable` or `unavailable` where appropriate.

A diagnostic follow-up is different from the standard report: when the human asks a specific causal question after invoking stewardship, answer that question directly with the relevant evidence, classification, and recommendation rather than repeating the full baseline report. The stable semantic contract applies to the baseline report, not to every diagnostic answer.

After a standard baseline report, offer one optional next step when the session contains meaningful telemetry: a detailed **execution-time and context/usage breakdown by skill**. Keep it opt-in so the default report stays compact. Do not offer it for a trivial or genuinely telemetry-unavailable session. When requested, use the detailed telemetry evidence and clearly caveat any sticky or otherwise non-causal skill-attribution field before presenting per-skill tables.

Keep unavailable measurements visible as unavailable rather than silently dropping the baseline. Add deeper
telemetry or phase reconstruction only when it materially helps explain the session.

For any telemetry field, "unavailable" means `telemetry.md`'s discovery procedure was actually attempted
and the evidence is genuinely absent or inaccessible — never that it simply wasn't looked for.
Label each timing/context field measured, reconstructed, or unavailable per `telemetry.md`'s evidence
states, and when a field is unavailable, state which discovery step failed rather than reporting the
gap silently.

Do not expand the retrospective into a full implementation or architecture review unless separately requested.
