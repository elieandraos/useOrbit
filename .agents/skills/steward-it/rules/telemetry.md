# Telemetry

## When this applies

Load for every non-trivial stewardship pass when session or runtime evidence may be available.
"May be available" is a reason to run the discovery procedure below, not a reason to skip it — do not
decide telemetry is unavailable before attempting it.

## Portable contract

### Discovery is a precondition

This is a repeated, confirmed failure mode in practice, not a hypothetical one: a stewardship pass
reports timing and token/context telemetry as "unavailable" without ever attempting to locate or open
the session's own log — even when that log exists, is well-formed, and contains everything this
contract asks for. The miss is that discovery is never tried.

Regardless of runtime, before marking any field in this rule unavailable, actually attempt to discover
it:

1. **Recognize an identifiable session or runtime reference** — something the current context already
   exposes that points at a concrete, inspectable record of this session (a path, an id, a handle).
   Treat its presence as the signal that session-level telemetry is reasonably discoverable, and proceed
   to actually locate the record — never treat the reference itself, or the fact that it's merely
   present, as the telemetry.
2. **Locate the actual record** using that reference, confirming it exists and is non-empty before
   proceeding. When stewarding a session other than the current one, locate it by a distinguishing,
   confirmed match (content or timing) rather than guessing an identifier.
3. **Parse it** for the fields this contract asks for, using a script rather than eyeballing — the
   record can be large.
4. **Only once discovery and parsing have actually been attempted**, and the record is genuinely
   missing, unreadable, or lacking the specific field in question, mark that field unavailable — and say
   which step failed and why (no identifiable reference existed; no matching record was found; the
   record existed but the field was absent; the record was inaccessible). "I have not looked yet" is
   never a valid basis for "unavailable."

**Do not treat ambient `<total_tokens left>` / context-window headroom indicators as session
consumption telemetry.** These figures describe remaining context budget, move non-monotonically
(compaction can raise them), and are not a record of input/output/cache/thinking tokens actually
consumed. They are not the session log, and their presence or behavior is never evidence that the
actual telemetry is unavailable — that determination can only follow the discovery procedure above.

### Evidence states

Report every telemetry field as one of exactly three states, and say which:

- **Measured** — read directly from the session record: a timestamp delta, a token/cache usage field, a
  skill-attribution value, a matched question/answer interval.
- **Reconstructed** — derived, not directly stated: a phase boundary inferred from an identifiable event
  (a skill activation, a commit, a gate report or approval message, an issue closure), or an interval
  computed across several measured timestamps. State what it was derived from.
- **Unavailable** — only after the discovery procedure above was actually attempted and the evidence is
  genuinely absent or inaccessible. State why (step 4 above).

Never report a field "unavailable" when discovery was simply skipped. That distinction — "not yet
inspected" is not "unavailable" — is itself part of the report; see `report.md`.

### What to report

Report, per session:

- total elapsed time, active execution time, and human wait time — human wait is excluded from active
  execution time;
- phase timing, reconstructed from identifiable events when the record doesn't state phases explicitly;
- input/output token usage, cache creation/read usage, and thinking-token usage, when the runtime
  exposes them;
- usage grouped by skill, including unattributed usage, when the runtime exposes skill attribution —
  distinguish directly reported usage from an inferred percentage or aggregate, and do not invent skill
  attribution the runtime does not expose;
- human-wait intervals for question/answer exchanges and plain-text approvals alike (the gap between an
  assistant report or question and the human's next turn), not only a subset of them.

State each field as measured, reconstructed, or unavailable per the evidence states above.

Use telemetry as diagnostic evidence, not as a quality verdict. Large prompts, repeated loads, cache
footprints, and long phases justify investigation but do not prove waste by themselves.

## Claude Code discovery mechanism (verified instance)

The following is the concrete instance of "Portable contract" steps 1–3 verified against actual Claude
Code session logs. It is runtime-specific implementation detail, not the portable requirement itself —
a different runtime satisfies the same contract through its own equivalent mechanism (see "Other
runtimes" below), not this one.

1. **Recognize the session reference.** A Claude Code session's own system prompt normally names a path
   that embeds the session id — for example a scratchpad directory such as
   `/private/tmp/claude-<uid>/<project-slug>/<session-id>/scratchpad`.
2. **Locate the log file.** Each session is stored as a JSONL file at
   `~/.claude/projects/<project-slug>/<session-id>.jsonl`, where `<project-slug>` is the working
   directory's path with `/` replaced by `-`, and `<session-id>` is the identifier from step 1.
   - **Stewarding the current session:** derive both parts directly from the identifiable path already
     present in context.
   - **Stewarding a different or historical session** (for example, investigating an earlier session
     from within a later one): do not guess a session id. Search the same project directory for the
     JSONL whose content contains a known, distinguishing string (an issue number, a commit SHA, a
     quoted prompt) or whose modification time matches the known event window, and confirm the match
     before relying on it.
3. **Parse it.** Each line is one JSON record. Read `type` (`user`, `assistant`, `system`, and so on). An
   `assistant` record normally carries a top-level `timestamp` (ISO-8601) and a `message.usage` object
   (`input_tokens`, `output_tokens`, `cache_creation_input_tokens`, `cache_read_input_tokens`,
   `output_tokens_details.thinking_tokens`). A record may carry a top-level `attributionSkill` naming the
   skill responsible for that request. `tool_use` and its matching `tool_result` pair by `tool_use_id`.

For Claude Code sessions specifically, treat a confirmed `AskUserQuestion` tool-use/tool-result pair as a
human-wait interval from the tool-use event through the matching tool-result event. Do not require the
enclosing user turn to have `origin.kind == human`; observed sessions may have `origin: None` on those
result turns. A plain-text approval (a human reply following an assistant report, with no
`AskUserQuestion` involved) is also a measurable human-wait interval — the gap between the report's
timestamp and the next user turn's timestamp.

## Other runtimes

A runtime other than Claude Code satisfies "Portable contract" the same way: an identifiable session
reference, a way to locate the underlying record from it, and fields mapping to timing, token/cache
usage, and skill attribution. Do not invent an unverified discovery mechanism for a runtime with no
confirmed evidence behind it. When no verified discovery mechanism exists for the runtime actually in
use, that absence is itself the reportable state — say plainly that no verified discovery mechanism
exists for this runtime, rather than fabricating one or silently falling back to the Claude Code
mechanism above for a session it was never verified against.

## Historical evidence

Older file-size or workflow models may still appear in historical evidence such as prior scenario
records. Treat those as historical intent only — they are not runtime measurements and must not be
cited as current consumption proof.
