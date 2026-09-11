# Steward Report

## When this applies

Load for every non-trivial stewardship pass after the available evidence has been reconstructed.

## Standard baseline

Keep the default report compact but always include:

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

Keep unavailable measurements visible as unavailable rather than silently dropping the baseline. Add deeper
telemetry or phase reconstruction only when it materially helps explain the session.

Do not expand the retrospective into a full implementation or architecture review unless separately requested.
