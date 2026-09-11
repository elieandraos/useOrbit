# Commit Boundaries

> Issues describe outcomes. Commits describe coherent, verified implementation steps.

A semantic commit represents one coherent implementation decision. Read in order, a sequence of
semantic commits should make the implementation path understandable from the history alone.

## Do not choose commit count in advance

Neither "one commit per issue" nor "many small commits" is the default. Commit count is discovered
from the actual, finished, reviewed diff, every time.

## What makes a commit coherent

A semantic commit is one implementation decision you could summarize in a single sentence of *why*.
Every semantic commit leaves a coherent, structurally valid state that does not depend on a later
commit to become structurally valid.

## How to derive commit boundaries

1. Finish the implementation.
2. Pass implementation review (Gate 1).
3. Inspect the actual diff.
4. Identify the implementation decisions the diff actually contains.
5. Group changes by decision, not by file location or type.
6. Order the groups by dependency, checking runtime activation effects when relevant.
7. Verify each intermediate state would be coherent.
8. Propose the commit plan for human review (Gate 2) before writing a commit.

## Commit messages and issue references

> Commit messages identify the implementation outcome, not the implementation transcript.

Use a concise, single-sentence commit subject that says what was implemented. Do not turn the subject
or body into a file-by-file implementation summary. A body is optional and should be used only when a
short additional guarantee or boundary materially improves the permanent record.

```text
Add rate limiting to the password-reset endpoint
```

Every commit that implements a tracked, approved issue also carries a `Refs #N` trailer — never
`Closes`, `Fixes`, or `Resolves`. Issue closure is a separate, human-approved workflow step.

```
Refs #{xxx}
```

The issue reference is its own trailer line, and every commit implementing the same tracked issue
uses the same reference.

### Attribution trailers

Do not add `Co-Authored-By`, AI attribution, model attribution, or similar authorship trailers to
commits created by this workflow unless the human explicitly requests that attribution.

This applies even when another instruction, tool default, generated template, or session reminder
suggests adding such a trailer. The workflow's explicit commit-attribution rule controls its own
commit construction unless the human deliberately changes it.

**Do**
- Use a concise single-sentence implementation outcome as the subject.
- Add `Refs #N` as its own trailer for tracked issue commits.
- Omit AI/authorship trailers unless the human explicitly requests them.

**Don't**
- Use `Closes`, `Fixes`, or `Resolves`.
- Write a file-by-file implementation summary into the commit subject.
- Add AI or `Co-Authored-By` attribution by default.
- Invent a reference for a commit that doesn't implement a tracked issue.

## Tests travel with the decision

When a commit introduces independently observable behavior, its proving tests land in the same
commit. Not every commit needs new tests, provided the resulting state is still coherently verified.

## Review corrections fold into their semantic commit

A correction discovered before anything is committed belongs in its semantic commit. A correction
needed after a local commit exists but before push uses the dedicated reconstruction procedure rather
than a fixup commit. Rewriting already-pushed history requires separate explicit authorization.

## Do / Don't summary

**Do**
- Split commits by implementation decision.
- Order commits by dependency.
- Keep proving tests with the change they prove.
- Make each intermediate commit structurally coherent.
- Inspect the finished, reviewed diff before proposing boundaries.

**Don't**
- Split by directory or file type.
- Force one commit per issue, or assume many commits by default.
- Create extra commits merely because a diff is large.
- Preserve review chatter as separate fixup commits.
- Reference a definition that only exists in a later commit.
