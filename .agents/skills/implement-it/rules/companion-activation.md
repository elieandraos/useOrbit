# Companion Activation

## Before implementation

Before writing or modifying code, enumerate the available implementation, testing, tooling, and
stack-companion skills that may apply to the requested issue. Do not stop after finding the first
matching skill, and do not assume one general framework or testing skill makes a custom companion
unnecessary.

For each candidate skill, inspect its activation or trigger description and decide whether it applies.
A matching stack companion remains applicable even when a Boost, framework, or testing skill also
applies; one does not substitute for another unless the applicable skill explicitly says so. When a
skill states that it must be loaded alongside another named skill, treat that relationship as part of
its activation condition and satisfy both sides.

An applicable skill is not considered activated merely because its files were read or its guidance was
consulted. Activate it through the consuming agent's supported skill-loading mechanism before
implementation begins. Direct file reads are supporting evidence, not a substitute for activation.

This is a repeated, confirmed failure mode in practice, not a hypothetical one: an applicable skill's
rule files get read directly and implementation proceeds on that basis, with the skill mechanism never
actually invoked for it. Restating the requirement again did not prevent recurrence — the fix below
makes activation an explicit precondition with a concrete, checkable trigger, rather than a reminder to
keep in mind while working.

### Activation is a precondition, not a step to remember mid-task

Enumerate candidates and decide applicability *before* touching any implementation surface — before the
first file edit, file creation, or other implementation-directed tool use for this issue. If a candidate
skill's applicability is genuinely unclear before implementation starts, resolve that uncertainty (read
its trigger description, ask if still unclear) before proceeding — never resolve it implicitly by
reading its rule files and continuing.

Apply this concrete trigger throughout implementation, not only at the start: **if you are about to
read a rules file, doc, or guidance belonging to a skill that has not yet appeared as activated (through
the skill mechanism) in this session's activation checkpoint, stop before reading it and activate that
skill through the mechanism first.** A direct file read is never how a skill's applicability question
gets resolved mid-task; it is only ever how an already-activated skill's guidance gets consulted.

### Activation checkpoint

Before writing code, produce a concise activation checkpoint in the working session that records:

- candidates considered;
- candidates activated through the skill mechanism;
- applicable candidates that were not activated, with the reason;
- any unavailable activation metadata or mechanism.

Do not begin implementation until this checkpoint is complete. If an applicable skill cannot be
activated through the available mechanism, stop and report the limitation rather than silently
continuing with direct file reads or sibling-code precedent.

## Evidence of activation

The activation checkpoint is the evidence of the decision. At Gate 1, report the activated skill set
again in one concise `Activated skills:` line so the pre-implementation decision remains visible at
the implementation boundary.

Do not infer activation from the implementation outcome afterward. When no custom stack companion
applies, continue with the applicable general implementation skills and do not invent a stack-specific
substitute.
