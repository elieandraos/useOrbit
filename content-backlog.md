# Content Backlog — Agentic in Public

Persistent content memory for this project. Not a chronological activity log — an entry added
months ago is exactly as usable as one added today.

> I build software with agents, and I'm documenting how I decide what they should own, what I
> should own, and how I make the workflow improve itself.

Entries are raw material for future posts/threads, not finished content. Managed by the
`content-backlog` skill (`.claude/skills/content-backlog/`) — see that skill for how entries get
added and how suggestions work. Entries are only ever added on explicit request; nothing here was
written automatically.

Each entry is numbered once, on creation, and keeps that number for its lifetime — including if
it's later archived. Each entry: `##` heading (working title), a small metadata block (including
its stable `#`), then the story fields.

## Index

| # | Topic | Category | Status | Potential format | Priority |
|---|---|---|---|---|---|
| 1 | My planning agent found a flaw in its own planning during a smoke test | Agent failures | idea | Thread | — |
| 2 | At some point, a 100-line prompt is a smell | Agentic workflow evolution | idea | Short thread | — |
| 3 | My coding agent created a reusable abstraction. I made it delete it | Engineering judgment | idea | Short thread | — |
| 4 | I gave my agents contracts with each other | Agentic workflow evolution | idea | Longer thread | — |
| 5 | The tests were green. I still changed the feature | Engineering judgment | idea | Thread | — |
| 6 | I tried to make three controllers into one. The codebase said no | Engineering judgment | idea | Short thread | — |
| 7 | Centralizing tenant context didn't remove the dependency — it made it explicit | Engineering judgment | idea | Short thread | — |
| 8 | The org roster leaked more than it should have to regular members | Engineering judgment | idea | Single post | — |
| 9 | I wrote the plan to make my own skills public — and the first decision was "not yet" | Agentic workflow evolution | idea | Short thread | — |
| 10 | The same agent skipped the same rule twice — because the trigger was wrong, not the rule | Agent failures | idea | Thread | — |
| 11 | I gave my content skill a rule against over-engineering — then revised it in the same session, on purpose | Agentic workflow evolution | idea | Short thread | — |
| 12 | A locked security rule that could never actually fire | Engineering judgment | idea | Short thread | — |
| 13 | Three ways my own planning skill could lie to me — found on issues it had already created | Agent failures | idea | Longer thread | — |
| 14 | Twice accused of the same bug, twice couldn't find it in my own output | Agent conversations worth sharing | idea | Thread | — |

---

<!-- Entries are appended below this line. -->

## My planning agent found a flaw in its own planning during a smoke test

- #: 1
- Status: idea
- Category: Agent failures
- Potential format: Thread
- Added: 2026-08-16

**What happened:** Deliberately smoke-tested the `architecture-laboratory` → `plan.md` →
`my-feature-planning` workflow end-to-end using a hypothetical "Global Search" feature — chosen
specifically because it doesn't exist and never got built. No Global Search code was written, no
GitHub issues were created; the feature itself was disposable test fixture. During the planning
pass, the agent drafted a proposed issue that made an extensibility claim: adding a future
searchable model to Global Search would only require adding the model plus one registry entry.
Reviewing that proposed issue surfaced a second, uncounted integration seam the claim had
missed — the frontend kind/presentation registry, which would also need an entry for the new
model to actually render anywhere.

**Why it's interesting:** The bug wasn't in the hypothetical feature — it was in the *skill*
that plans features. A disposable smoke test using a fake feature exposed a real, general
weakness in `my-feature-planning`'s extensibility reasoning: it was validating "how do you add
X" claims against only one integration seam instead of walking all of them. Because the test
feature was never going to ship, the flaw could be caught and fixed cheaply, in a throwaway
context, instead of surfacing later inside a real planning pass for a real feature.

**Core insight:** Before I trust an agentic workflow on a real feature, I want to break it on
something disposable first. A fake feature is a cheap place to find an expensive mistake.

**Engineering lesson:** Extensibility claims in a plan ("adding a new X only requires touching
Y") are a specific, falsifiable claim about integration seams — and should be checked against
the actual seams in the codebase, not accepted because they sound plausible. A single-seam claim
is a smell worth verifying every time, not just when it happens to be wrong.

**Human decision / agent responsibility boundary:** The user chose to run a deliberate,
throwaway smoke test rather than trust the workflow on a real feature first — a testing
methodology decision. The agent produced the flawed extensibility claim; the human (via
reviewing the proposed issue) caught the missing seam; the fix was then to improve
`my-feature-planning` itself to validate extensibility claims against all actual integration
seams going forward, closing the loop so the same class of miss is structurally less likely next
time.

**Technical/architectural context:** The workflow under test chains three project skills:
`architecture-laboratory` (reconstructs/validates architecture, produces `plan.md` as canonical
input), then `my-feature-planning` (turns `plan.md` into scoped GitHub issues). Global Search was
used only as a stand-in feature to exercise the chain. The specific miss was a backend/frontend
seam mismatch: the backend registry entry was accounted for, the frontend kind/presentation
registry entry was not.

**Before → After:** Before — `my-feature-planning` validated extensibility claims informally,
apparently checking only the seam that came to mind first (the backend registry). After — the
skill now validates extensibility claims against *all* actual integration seams in the codebase
before including them in a proposed issue.

**Hook:** "I ran a smoke test on my planning workflow with a fake feature — and the workflow
still managed to plan itself wrong."

**Audience takeaway:** Testing your agentic workflows with disposable, low-stakes fixtures (a
feature you'll never ship) is a legitimate methodology — it lets you catch systemic flaws in the
*process* cheaply, before they cost you on something real.

## At some point, a 100-line prompt is a smell

- #: 2
- Status: idea
- Category: Agentic workflow evolution
- Potential format: Short thread
- Added: 2026-08-16

**What happened:** Wrote a long, detailed prompt (~100 lines) to get an agent to reconstruct and
validate the architecture of the Auth/Invitation/2FA flow. The prompt worked extremely well — the
output was accurate and useful. But the success itself was the problem: getting that result
required manually re-encoding a reusable methodology (how to reconstruct and validate an
architecture, how to turn findings into a locked plan) into prose, from scratch, in the prompt
itself. That recognition — "I keep hand-writing this same methodology every time I need it" — led
directly to building Plan Synthesis as a formal part of the `architecture-laboratory` skill,
turning the one-off prompt into a repeatable workflow step.

**Why it's interesting:** The signal to build a skill wasn't a failure — it was a *success* that
was expensive to repeat. A prompt that works well but is long and bespoke is doing the job a
skill should do. The lesson is about recognizing when repeated manual prompting has quietly
become undocumented methodology, and converting it before the next repetition costs the same
100 lines of re-derivation.

**Core insight:** If I keep rewriting the same 100-line prompt, I probably don't need a better
prompt. I need to teach the agent the workflow once.

**Engineering lesson:** Prompt length is a proxy for encoded process. When a prompt has to
re-explain "how to do the thing" every time rather than just "what thing to do this time," that's
the line between a one-off request and a workflow that deserves to be a skill.

**Human decision / agent responsibility boundary:** The user recognized the pattern (repeated,
successful, but manually re-encoded methodology) and made the call to formalize it. The
architecture reconstruction/validation work itself was already agent-led; what changed is that
the *methodology for how to do it* moved from the human's prompt into the skill definition, so
the agent now owns applying the workflow instead of the human owning re-explaining it each time.

**Technical/architectural context:** The specific case was reconstructing the Auth/Invitation/2FA
architecture. The outcome was Plan Synthesis becoming a track within `architecture-laboratory` —
turning approved findings/decisions into a canonical `plan.md` that downstream skills (like
`my-feature-planning`) can treat as locked input instead of re-deriving architecture from
conversation each time.

**Before → After:** Before — a ~100-line bespoke prompt, hand-written per architecture
investigation, encoding the methodology implicitly every time. After — Plan Synthesis as a named,
reusable track inside `architecture-laboratory`, invoked with a short trigger phrase instead of
re-derived prose.

**Hook:** "At some point, a 100-line prompt is a smell."

**Audience takeaway:** When a long prompt keeps working, that's not proof you don't need a skill —
it's usually the exact moment you do. Recognize repeated methodology in your own prompts before
you write it out a fourth time.

## My coding agent created a reusable abstraction. I made it delete it

- #: 3
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-16

**What happened:** During the Notifications work, the task was recipient selection for automatic
notifications — deciding who gets notified for a given event. The agent introduced a reusable
recipient-resolution abstraction to handle this generically. On review, the abstraction's value
was questioned: what was actually reusable were lower-level User query scopes —
`activeInCurrentOrganization()` and `privileged()` — while the event-specific rules (e.g.
excluding the actor, excluding the affected member) were clearer written directly inside the
relevant Action than hidden behind a generic recipient resolver. The higher-level abstraction was
removed; the lower-level scopes were kept.

**Why it's interesting:** The abstraction wasn't broken or wrong — it worked and looked
reasonable. The problem was altitude: it generalized at the wrong layer, bundling
per-event business rules into a shared resolver instead of leaving them where they're easiest to
read and change. The story isn't "AI over-engineers" or "abstractions are bad" — it's that a
plausible-looking reusable abstraction still needs a human judgment pass on *which level* of
reuse is actually correct.

**Core insight:** An AI can write perfectly good code at the wrong level. The hard part isn't
making something reusable — it's knowing what should actually be reused.

**Engineering lesson:** Reusability has a correct altitude. Low-level, stable primitives (query
scopes describing "what is an active org member," "what is a privileged user") are good reuse
candidates because they're true regardless of caller. High-level, event-specific composition
rules (who gets excluded for *this* notification) are not — they read better and stay easier to
change when they live directly in the Action that owns that specific business rule, not behind a
generic resolver that has to parameterize around every event's exceptions.

**Human decision / agent responsibility boundary:** The agent proposed and implemented the
reusable recipient-resolution abstraction. The human reviewed it and made the call that it wasn't
earning its abstraction cost — asking "is this actually buying us anything?" — and directed
keeping the low-level scopes while inlining the event-specific rules back into the Actions. The
agent executed the removal; the judgment call on the right abstraction boundary was the human's.

**Technical/architectural context:** Notifications feature, recipient selection for
automatically-triggered notifications. Kept: `User` query scopes `activeInCurrentOrganization()`
and `privileged()`. Removed: a generic recipient-resolution abstraction. Event-specific exclusion
rules (actor, affected member) now composed directly inside the relevant Action.

**Before → After:** Before — recipient selection routed through a generic, reusable
recipient-resolver abstraction encoding per-event rules as configuration. After — reusable logic
lives only in low-level User query scopes; each Action composes those scopes directly with its
own explicit, inline exclusion rules.

**Hook:** "My coding agent created a reusable abstraction. I made it delete it."

**Audience takeaway:** A reusable abstraction can be well-built and still be wrong — the question
isn't just "does this work," it's "is this reuse happening at the right level." That judgment call
is still the engineer's job, agent or no agent.

## I gave my agents contracts with each other

- #: 4
- Status: idea
- Category: Agentic workflow evolution
- Potential format: Longer thread
- Added: 2026-08-16

**What happened:** `architecture-laboratory` and `my-feature-planning` started as two separately
useful skills and evolved into a deliberate handoff workflow with an explicit contract between
them. `architecture-laboratory` owns understanding the real system, reasoning about target
architecture together with the human, capturing locked decisions, and producing an approved
`plan.md`. `my-feature-planning` consumes that approved `plan.md` as canonical input — it owns
classification, scope, design reconciliation, issue decomposition, dependency/review validation,
and GitHub issue creation after approval — and is not supposed to re-investigate or re-litigate
architecture the first skill already settled. Implementation skills take over after planning and
own the actual code, tests, and commits. Both sides of the contract were then deliberately
strengthened: `architecture-laboratory` learned Plan Synthesis so a giant hand-written prompt was
no longer needed to produce the handoff artifact (see "At some point, a 100-line prompt is a
smell"), and `my-feature-planning` learned to treat an approved `plan.md` as canonical rather than
reconstructing decisions from conversation history (which is also what exposed the extensibility
gap in "My planning agent found a flaw in its own planning during a smoke test").

**Why it's interesting:** The upgrade wasn't "add more skills" — it was defining a *contract*
between skills: who owns what, what artifact passes between them, and what each side is
forbidden from redoing. That's the same discipline used to split responsibilities between
services or team boundaries in ordinary software architecture, applied to a chain of agent
workflows instead of a chain of systems.

**Core insight:** Adding more agents isn't the interesting part. Giving each one a clear job and
a clean handoff to the next is.

**Engineering lesson:** Specialized agents get more valuable when their responsibility
boundaries are explicit and there's a trustworthy, versioned artifact handed off between them —
not just a shared conversation history. Without a locked contract, each downstream skill is
tempted to re-derive upstream decisions from scratch (expensive, and a chance to silently
contradict what was already decided). With a locked `plan.md`, the downstream skill can treat
upstream decisions as settled and spend its effort on its own layer of the problem.

**Human decision / agent responsibility boundary:** The human owns product/architecture decisions
and the approval gates between stages — approving `plan.md` before planning starts, approving
proposed issues before creation, and so on. Each skill owns applying its own workflow inside those
boundaries: `architecture-laboratory` owns investigation and architecture synthesis,
`my-feature-planning` owns scoping and issue decomposition, implementation skills own code/tests/
commits. No skill owns another skill's decisions — only its own process for turning approved
decisions into the next artifact.

**Technical/architectural context:** The contract artifact is `plan.md`, produced by
`architecture-laboratory`'s Plan Synthesis track and treated as canonical, locked input by
`my-feature-planning` (see its `rules/plan-md-input.md`). This is the same three-skill chain
referenced in the Global Search smoke test and the 100-line-prompt entries — this entry is the
"why we built the contract this way" framing that ties those two together.

**Before → After:** Before — two useful but loosely coupled skills; handing work from one to the
other meant re-explaining decisions via a long manual prompt, with no guarantee the next skill
wouldn't re-litigate settled architecture. After — an explicit contract: `architecture-laboratory`
produces an approved, canonical `plan.md`; `my-feature-planning` consumes it as locked input and
never re-investigates architecture it already settled; implementation skills take over after
issues are approved.

**Hook:** "I gave my agents contracts with each other."

**Audience takeaway:** As you build more specialized agent workflows, the leverage doesn't come
from adding more of them — it comes from defining explicit contracts between them: who owns what
decision, and what artifact one hands the next so it doesn't have to re-derive settled ground.

**Supporting material:** Ties together two earlier backlog entries as concrete evidence of the
contract paying off — "At some point, a 100-line prompt is a smell" (why the `plan.md` handoff
artifact was built) and "My planning agent found a flaw in its own planning during a smoke test"
(a case where trusting `plan.md` as canonical input surfaced a real gap in the downstream skill).

## The tests were green. I still changed the feature

- #: 5
- Status: idea
- Category: Engineering judgment
- Potential format: Thread
- Added: 2026-08-16

**What happened:** From the Notifications / manual Notify work. The backend implementation was
working correctly and the full test suite was green. Rather than stop there, the feature was
tested as an actual user: logged in as a Member, manually notified the Organization Owner about a
Client, then switched to the Owner experience to see what receiving that notification actually
felt like. Technically everything worked — correct recipient, notification appeared in the
dropdown, the selected reason showed, the resolved URL navigated correctly to the Client, realtime
delivery worked. But using it (not just testing it) exposed a UX problem the automated tests were
never meant to catch: the Owner got a realtime toast like "{member} notified you about
{resource}," while the persistent notification dropdown itself showed only the bare reason, e.g.
"Needs your review" — the toast had context the persistent row lacked, and the toast itself felt
unnecessary for this kind of event. That led to two changes: realtime toasts were removed for
normal persistent-notification events; and the notification row for manual `resource.message`
notifications was improved to show actor + subject context ("{member} notified you about
{resource}") followed by the selected reason and timestamp.

**Why it's interesting:** The tests weren't wrong and didn't miss a bug — the implementation
correctly satisfied the tested contract (recipient targeting, authorization, envelope shape,
navigation data, delivery). What green tests can't answer is whether the experience *feels* right
to a human receiving it. This is a clean, concrete illustration of the boundary between
"verified correct" and "actually good" — a distinction that's easy to state abstractly and easy to
skip in practice once CI is green.

**Core insight:** Agents can get you to "correct" very quickly. That makes the human's job of
deciding whether "correct" is actually good more important, not less.

**Engineering lesson:** Automated tests prove a contract was met; they don't prove the contract
was the right one to experience. Manually walking through a feature as the actual user role
(not just as the actor who triggers it) is a distinct verification step from "tests pass," and it
catches a different class of problem — one about information design and interaction feel, not
correctness.

**Human decision / agent responsibility boundary:** The agent's implementation was correct and
fully covered by tests. The human made the call to keep testing past green — actually using the
feature from the receiving user's point of view — and that manual pass is what surfaced the UX
gap. The human also made the more nuanced judgment call: not applying the toast-removal fix
uniformly everywhere. Document-upload completion notifications deliberately kept their realtime
toast, because that event reports completion of the *current user's own* asynchronous work — a
different UX purpose (progress feedback on your own action) than a persistent notification about
someone else's action on a resource you now need to review. Recognizing that these two toast
use cases only look similar on the surface, and resisting a blanket rule, was a human judgment
call the tests had no way to prompt.

**Technical/architectural context:** Notifications feature, manual `Notify` flow and
`resource.message` notification type. Realtime toast delivery vs. persistent notification-dropdown
rendering are two separate UX surfaces fed by the same underlying notification event; they were
tuned independently once it became clear they were serving different jobs (interrupt vs. record).

**Before → After:** Before — realtime toast handling was broadly applied across notification
events, with the toast carrying actor/subject context the persistent row lacked; the
`documents.uploaded` event already had its own special-cased toast behavior, distinct from the
rest. After — realtime toasts fire only where they're actually earning their interruption (kept
for document-upload completion, removed for normal persistent-notification events); the
persistent notification row itself was enriched to carry the actor + subject context it was
previously missing.

**Hook:** "The tests were green. I still changed the feature."

**Audience takeaway:** Green tests confirm the contract was implemented correctly — they don't
confirm the contract was the right experience. Manually using a feature as the receiving user is
a separate, necessary verification step, and the fixes it surfaces (like the toast decision here)
still need engineering judgment to apply narrowly rather than as a blanket rule.

## I tried to make three controllers into one. The codebase said no

- #: 6
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-17

**What happened:** From the Notifications work. Manual-notify routes for Client, Carrier, and
Agent existed as three methods on one `NotifyController`. Collapsing them further into a single
generic `NotifyResourceController` with one invokable method looked cleaner and more reusable, so
it was considered. Investigating that option showed it was actually a worse fit: Laravel's
implicit route-model binding needs concrete model types for `{client}`, `{carrier}`, and
`{agent}`; the app's existing authorization convention uses the `#[Authorize]` attribute against a
concrete model and ability; and a single generic invokable controller would need either awkward
conditional route-model resolution or a new route-level authorization mechanism the rest of the
app doesn't use. More decisively, every other Client/Carrier/Agent triad in the codebase already
uses separate per-resource controllers/classes for the same kind of operation — Archive,
Unarchive, Create, Update, PDF export, Excel export. The endpoints were split into
`NotifyClientController`, `NotifyCarrierController`, `NotifyAgentController`, and later
`NotifyDocumentController`.

**Why it's interesting:** The generic controller wasn't rejected on the reflex "generic code is
bad." It was rejected because it fought two concrete things at once: Laravel's binding/
authorization model, and an architectural pattern the rest of the codebase had already committed
to everywhere else this exact triad (Client/Carrier/Agent) shows up. The near-duplication across
three controllers wasn't an oversight to clean up — it was already the codebase's chosen answer
to this exact shape of problem.

**Core insight:** Three near-identical controllers can be the right call. The question isn't
whether code repeats — it's whether collapsing it means fighting the framework and every other
place the codebase already answered this same question.

**Engineering lesson:** A repeated pattern across a resource triad (Client/Carrier/Agent, in this
codebase) is a convention, not just duplication — and checking how every other operation on that
same triad is structured is a cheaper, more reliable signal than judging a single new feature's
shape in isolation. Fighting a framework convention (route-model binding, attribute-based
authorization) to enable a generic abstraction is a cost the abstraction has to earn back, not a
neutral implementation detail.

**Human decision / agent responsibility boundary:** The generic `NotifyResourceController` idea
was considered as the more "reusable" option before implementation. The investigation into how it
would interact with Laravel's binding/authorization model, and the check against how every other
Client/Carrier/Agent operation in the codebase is structured, was the human judgment step that
rejected it in favor of matching the established per-resource controller pattern.

**Technical/architectural context:** Notifications feature, manual `Notify` flow. Final shape:
`NotifyClientController`, `NotifyCarrierController`, `NotifyAgentController`,
`NotifyDocumentController` — one controller per resource, consistent with how Archive, Unarchive,
Create, Update, PDF export, and Excel export are already structured for the same Client/Carrier/
Agent triad elsewhere in the app. Authorization uses the `#[Authorize]` attribute against a
concrete model and ability per controller.

**Before → After:** Before — three manual-notify methods lived on one `NotifyController`, with a
single generic `NotifyResourceController` considered as a further consolidation. After — one
controller per resource (`NotifyClientController`, `NotifyCarrierController`,
`NotifyAgentController`, `NotifyDocumentController`), matching the app's existing per-resource
controller convention instead of introducing a new generic pattern.

**Hook:** "I tried to make three controllers into one. The codebase said no."

**Audience takeaway:** When an abstraction looks cleaner in isolation, check it against two
things before adopting it: does it fight the framework's own conventions, and does it match or
break the pattern the rest of the codebase already uses for this exact shape of problem. Losing
either check is a real cost, not a style preference.

## Centralizing tenant context didn't remove the dependency — it made it explicit

- #: 7
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-17

**What happened:** The application originally had tenant-awareness scattered through Actions and
other layers, each one explicitly constraining queries with `where organization_id = ...`.
`OrganizationContext` was introduced as the canonical place to answer "what organization is
currently active," and `CurrentOrganizationScope` — a global Eloquent scope — was built on top of
it to automatically constrain normal tenant-aware queries. The goal was to stop making every
Action manually remember and apply tenant identity: instead of repeated query logic, tenant
identity became application context, and Actions were free to focus on their actual business
rules.

**Why it's interesting:** Centralizing tenant context looks, on the surface, like it removes a
dependency — no more `organization_id` checks sprinkled through every Action. It doesn't remove
the dependency, it relocates it. `CurrentOrganizationScope` isn't HTTP-specific; it depends on
`OrganizationContext`, and HTTP is just one runtime that happens to establish that context
automatically on every request. Any other runtime executing the same tenant-aware code — queue
workers, Artisan commands, scheduled jobs, tests, future background processes — inherits the same
question the HTTP request used to answer for free: who establishes `OrganizationContext` here?

**Core insight:** If every Action has to remember which organization it's in, you don't really
have tenant context — you have tenant homework. Centralizing it cleans up the application, but it
doesn't make the dependency disappear; it just moves the question from every Action to every
runtime.

**Engineering lesson:** Pulling a cross-cutting concern like tenant identity out of business logic
and into a shared service/global scope is good architecture — it stops every caller from having to
reconstruct the same fact. But a global scope built on centralized, service-resolved context is
only as safe as the guarantee that something establishes that context before the scope runs. The
payoff (Actions that stop repeating tenant logic) and the obligation (every runtime has to
deliberately establish `OrganizationContext`) are two halves of the same architectural decision,
not a free win followed by a surprise bill.

**Human decision / agent responsibility boundary:** Introducing `OrganizationContext` and
`CurrentOrganizationScope` was a deliberate architectural decision to relocate tenant-awareness
out of Actions and into application context. That decision carries its own follow-on
responsibility: for every runtime capable of executing tenant-aware code, someone has to decide —
explicitly — how `OrganizationContext` gets established there. The web request lifecycle answers
that question automatically; every other runtime has to answer it on purpose.

**Technical/architectural context:** `OrganizationContext` holds the current organization's
identity as application state, independent of any particular runtime. `CurrentOrganizationScope`
is a global Eloquent scope that reads from `OrganizationContext` to constrain normal tenant-aware
queries automatically. Neither class is HTTP-only — the scope depends on the context service, not
on the request lifecycle; HTTP middleware is simply the runtime that happens to populate
`OrganizationContext` for web requests.

**Before → After:** Before — Actions and other layers repeatedly carried tenant-awareness
themselves through explicit `organization_id` constraints. After — the runtime establishes
`OrganizationContext`, normal tenant-aware Eloquent queries inherit `CurrentOrganizationScope`,
and Actions can focus on business logic instead of repeatedly reconstructing tenant identity.

**Hook:** "Centralizing tenant context didn't make the dependency go away. It just moved the
question from every Action to every runtime."

**Audience takeaway:** Centralizing a cross-cutting concern like tenant identity is a real
architectural win — but it converts a repeated implementation detail into a single, load-bearing
dependency. For every runtime that can execute that code (web, queue, console, scheduler, tests),
ask what actually establishes that context there — don't assume the answer that works for HTTP
requests generalizes for free.

## The org roster leaked more than it should have to regular members

- #: 8
- Status: idea
- Category: Engineering judgment
- Potential format: Single post
- Added: 2026-08-17

**What happened:** `OrganizationMembersController@index` was gated with
`#[Authorize('viewAny', User::class)]`, and the `OrganizationMemberPolicy::viewAny()` check only
verified `$user->organization_id !== null` — i.e., "are you in this organization at all." That
meant any Member, not just Owners/Admins, could load the full org roster: id, name, email, role,
status, `joined_at`, and `last_login_at` for every other member. The fix introduced a new `manage`
ability (`organization_id !== null && role->isPrivileged()`), moved the controller to
`#[Authorize('manage', User::class)]`, and rewrote the index tests to assert Owner/Admin get 200
and a plain Member gets a 403.

**Why it's interesting:** This wasn't new feature code going wrong — it was an existing, working
endpoint whose authorization was quietly broader than what it exposed. The policy method's name,
`viewAny`, answered "is this user in-tenant," which is a different question from "should this user
see this much detail about every teammate." That mismatch is easy to miss precisely because the
endpoint was already "authorized" — just against the wrong question.

**Core insight:** "Can view" and "can view *this much detail*" are different questions — a policy
that only checks tenant membership will happily authorize a page that was actually meant for
privileged roles only.

**Engineering lesson:** When reviewing an authorized endpoint, don't stop at confirming a policy
check exists — check whether the specific ability being tested actually matches what the response
exposes. `viewAny`-style tenant checks are often the *default* an endpoint inherits, not a
deliberate decision about that endpoint's data sensitivity; `last_login_at` and full roster
visibility warranted a narrower, role-aware ability.

**Human decision / agent responsibility boundary:** Identifying that the roster's authorization
was too permissive for its content, and defining what "privileged" should mean for this specific
view (a new `manage` ability rather than reusing an existing one), was the human judgment call. The
mechanical work — adding the policy method, swapping the attribute, updating tests to assert the
403 — followed from that decision.

**Technical/architectural context:** `app/Http/Controllers/OrganizationMembers/OrganizationMembersController.php`
and `app/Policies/OrganizationMemberPolicy.php`. New `manage()` ability checks
`role->isPrivileged()`. Tests in `tests/Feature/Http/OrganizationMembers/IndexTest.php` now assert
Owner/Admin can access the index and Member is forbidden; the earlier per-row
`can_change_role`/`can_remove`/`can_revoke` visibility tests for a Member viewer were removed
since Members can no longer reach the page at all.

**Before → After:** Before — any organization member could open the roster page and see every
other member's role, status, join date, and last login. After — only Owners/Admins (privileged
roles) can access the index at all; a Member gets a 403.

**Hook:** "Every member of the org could see everyone's last login time. The endpoint was
'authorized' the whole time."

**Audience takeaway:** An endpoint having *an* authorization check isn't the same as having the
*right* one — worth periodically asking, for each authorized view, whether the ability being
checked actually matches the sensitivity of what gets returned.

## I wrote the plan to make my own skills public — and the first decision was "not yet"

- #: 9
- Status: idea
- Category: Agentic workflow evolution
- Potential format: Short thread
- Added: 2026-08-17

**What happened:** Drafted a full v3 plan (`prompt.txt`, written 2026-08-16) to turn the internal
`architecture-laboratory` + `my-feature-planning` skill pair into a portable, publishable skill
pair for skills.sh — usable on Rails, Django, Node/TypeScript, .NET, and other stacks, not just
this Laravel/Vue app. The plan's own first instruction to itself: "Do NOT start portability work
yet. First continue using the current skills on real features until the methodology is stable and
we have enough evidence to know what is truly project-specific." It then designs the eventual
split — a portable "core methodology" (understand before changing, validate before documenting,
decide before planning, synthesize approved decisions into a canonical plan) versus a
project-specific "adapter" (GitHub conventions, milestone/label naming, issue title formats,
directory structure, frontend framework, design-file workflow) — and defines an explicit
"portability test" as the gate before publishing: prove the methodology survives on a genuinely
different stack, with no Laravel/Vue/GitHub/useOrbit assumptions leaked into the portable core.

**Why it's interesting:** Most "I built a skill" content is about the skill already working. This
is about *refusing* to generalize a skill that already works, on the theory that a project-tuned
workflow can't yet be sorted into "universal methodology" versus "useOrbit habit wearing a
methodology costume" until it's been proven against more than one project shape. The discipline
is in the "not yet," written down as the first line of the plan for publishing — not left as an
implicit intention.

**Core insight:** A skill that works great on one project hasn't earned the right to be generic
yet — only repeated use on genuinely different problems tells you which parts of it were ever
universal.

**Engineering lesson:** Portability is a claim that needs evidence, the same way an extensibility
claim in a plan does. The plan explicitly enumerates what "prove it" means here — run the
methodology against this project, a substantially different stack, a cross-cutting architecture
problem, a new feature, and an existing subsystem redesign — before trusting that the "core" is
actually core and not just this project's conventions in disguise.

**Human decision / agent responsibility boundary:** The decision to delay portability work, and
the definition of what the portability test requires before publishing is allowed, were the
human's call — a discipline decision about sequencing, not a technical one. The agent's role was
to draft the plan itself: separating the two skills' current responsibilities into a portable
core and a project-adapter layer, and specifying what must not leak between them (Claude
Artifacts as an output mechanism, Laravel Actions, Vue, GitHub-specific conventions).

**Technical/architectural context:** The two skills under discussion:
`architecture-laboratory` (owns architectural investigation, target-state reasoning, decision
capture, architecture documentation, and Plan Synthesis) and `my-feature-planning` (owns feature
classification, scope, design reconciliation, implementation-level planning, issue decomposition,
dependency/review validation, and downstream project-management integration — currently
GitHub-specific). The plan introduces a "project adapter" concept to hold language/framework,
backend/frontend conventions, architecture-artifact publishing mechanism, issue tracker, and
label/milestone conventions, kept optional from the core methodology's perspective but
recommended per-project.

**Before → After:** Before — two skills, tuned entirely to this project's stack and conventions,
useful only here. Planned after — a portable core methodology (investigate, validate, decide,
plan) usable across stacks, with useOrbit's specific conventions isolated into a separate,
swappable adapter layer — but only once the portability test has actually been run, not on the
strength of the plan alone.

**Hook:** "Before I open-sourced my planning skill, I wrote down every reason not to yet."

**Audience takeaway:** When a working, project-specific skill gets exciting enough to want to
publish it, the interesting engineering move might be writing down the test it has to pass first
— and holding off until it does — rather than genericizing on the first success.

## The same agent skipped the same rule twice — because the trigger was wrong, not the rule

- #: 10
- Status: idea
- Category: Agent failures
- Potential format: Thread
- Added: 2026-08-17

**What happened:** A standing rule required invoking `my-phpstorm-conventions` and
`my-laravel-patterns` alongside the relevant base skill (`pest-testing` or
`laravel-best-practices`) for any PHP write. It was skipped anyway — twice, in two separate
sessions, after the rule already existed as recorded feedback. First while generating a new test
file, producing `Client::first()` and `Client::count()` instead of the project's
`Client::query()->first()` / `Client::query()->count()` convention. Then again during the Clients
archived-toggle work, while adding a single `archived()` method to an already-open
`ClientFilter.php` and two test cases to an already-open `IndexTest.php` — producing a
`PhpUndefinedMethodInspection` on an `onlyTrashed` macro and a "potentially polymorphic call"
warning on a factory's `create()` return type, both caught reactively after the user flagged
them. Diagnosing the second miss surfaced the actual root cause: the mental trigger for invoking
the skills had quietly become "starting new code," so small, incremental edits to files already
open in the session never fired it. The rule was rewritten around the real trigger — "about to
write PHP," not "about to create a file" — explicitly calling out that a single added method or
test case counts.

**Why it's interesting:** This isn't "the agent forgot a rule." The rule existed, was recorded,
and was known — and still didn't fire, in the same shape, twice. The interesting failure is one
level up: the rule was correct, but the *trigger condition* attached to it was subtly wrong, and
it took a second, near-identical miss to see that the pattern wasn't random forgetting but a
specific blind spot — incremental edits to files already open in the session.

**Core insight:** When an agent breaks the same rule twice, don't just restate the rule louder —
the trigger condition attached to it is probably the actual bug.

**Engineering lesson:** A behavioral rule for an agent is only as good as the condition that fires
it. "Invoke X when writing PHP" sounds precise but silently narrowed, in practice, to "invoke X
when starting a new file" — a gap invisible until tested against the specific case of editing an
already-open file. Fixing the recurrence required naming the exact failure shape (incremental edit
to an open file) rather than re-emphasizing the original instruction.

**Human decision / agent responsibility boundary:** The user caught both incidents by reviewing
the resulting code/IDE warnings, not by watching the agent's skill-invocation decisions directly.
Recognizing, after the second miss, that the failure was a trigger-condition problem rather than a
one-off lapse — and rewriting the rule's "how to apply" section around the actual failure
pattern — was the human's diagnostic call. The agent's role was executing the reactive fixes each
time and then following the corrected trigger going forward.

**Technical/architectural context:** Feedback memory `feedback_phpstorm_skill_activation`,
originally written after a `StoreTest.php` generation issue, updated after a second recurrence
during Clients archived-toggle work. Final rule: test files (new or adding cases to an existing
file) invoke `pest-testing` + `my-laravel-patterns` + `my-phpstorm-conventions`; any other PHP
file (new or editing/adding a method to an existing class) invokes `laravel-best-practices` +
`my-laravel-patterns` + `my-phpstorm-conventions`.

**Before → After:** Before — the rule fired reliably on new files but silently didn't fire on
incremental edits to files already open in the session, because the encoded trigger was "starting
new code." After — the trigger is "about to write PHP," explicitly including a single added
method or test case, closing the specific gap that caused two recurrences.

**Hook:** "My rule for the agent was right. The condition for *when it fired* was wrong — twice."

**Audience takeaway:** A repeated agent mistake despite an existing rule is a signal to inspect the
trigger condition, not just restate the rule — the second occurrence is often the data point that
reveals the actual pattern the first fix missed.

## I gave my content skill a rule against over-engineering — then revised it in the same session, on purpose

- #: 11
- Status: idea
- Category: Agentic workflow evolution
- Potential format: Short thread
- Added: 2026-08-17

**What happened:** Earlier in the same session, added an explicit "evidence-driven, don't
optimize before the workload proves it" rule to `content-backlog`'s file-scaling guidance — no
database, no index, no multi-file split, wait for real signal that reading the full backlog is
becoming expensive before introducing any retrieval infrastructure. Almost immediately after,
came back with instructions to add a compact metadata Index to `content-backlog.md` anyway —
explicitly labeled as *replacing* the rule just written, not breaking it. The stated reasoning: a
tiny in-file index table is cheap, stays inside the same Markdown file, needs no external
infrastructure, and is useful even at 10 entries — so it doesn't count as the "bigger content
system" the original rule was written to prevent. Only genuinely heavier retrieval (a database,
embeddings, vector search, a separate index file) still has to wait for evidence. The migration
that followed gave all existing entries stable numbers and a real Index table at the top of the
file, and `backlog-file.md`, `capture.md`, `suggestions.md`, and the README were all updated to
keep the Index synchronized with future captures and edits.

**Why it's interesting:** On the surface this looks like writing a rule and breaking it in the
same breath. It isn't — the second pass didn't violate the anti-premature-optimization
principle, it *sharpened* what the principle actually gates. "Don't over-engineer this" and
"never add any structure" turned out to be different rules, and the session had to discover that
distinction by trying to apply the first version to a concrete, cheap improvement and finding it
didn't actually forbid it.

**Core insight:** Telling an agent "don't over-engineer this" isn't the same as "never add
structure" — if the rule doesn't say what it's actually gating, you can't tell a cheap win from
the thing you were trying to prevent.

**Engineering lesson:** Anti-premature-optimization rules need an explicit boundary around what
they gate — added moving parts, external infrastructure, new failure modes — or they either
block obviously-good cheap wins or become a vague catch-all that has to be re-litigated the first
time a real improvement shows up. Naming the boundary explicitly (in-file, human-readable,
zero-infra changes are exempt; anything external still needs evidence) let the second version of
the rule stay just as strict about the thing it actually cared about.

**Human decision / agent responsibility boundary:** Both calls were the human's: first, to write
the conservative evidence-driven rule; then, after seeing what a genuinely cheap structural win
looked like (a stable numbering scheme plus a small index table), to explicitly replace that rule
with a more nuanced one rather than treat the Index as a one-off exception. The agent's role was
executing both passes faithfully — writing the original rule, then performing the migration and
rewriting the rule files consistently with the replacement, including a validation pass
confirming no story content was altered and no external infrastructure was introduced.

**Technical/architectural context:** `.claude/skills/content-backlog/rules/backlog-file.md`'s
"Scaling the backlog" section was written, then rewritten, within the same session.
`content-backlog.md` itself was migrated in place: every existing entry got a stable `- #: N`
field, and a compact `# | Topic | Category | Status | Potential format | Priority` table was
added at the top as a navigation layer over the same full entries — not a second data store.
`capture.md` and `suggestions.md` were updated so new captures add an Index row automatically and
suggestion mining scans the Index before reading full entries.

**Before → After:** Before — a scaling rule that treated "don't add retrieval infrastructure
before there's evidence it's needed" as a single, undifferentiated bar covering everything from a
database to a one-column table. After — an explicit two-tier rule: a small in-file Index ships
from day one because it's cheap and infrastructure-free; a database, embeddings, vector search,
or a separate index file still wait for evidence the Index itself has stopped being enough.

**Hook:** "I told my own skill not to over-engineer itself. An hour later I told it to add a
feature anyway."

**Audience takeaway:** When an anti-over-engineering rule meets its first genuinely cheap
improvement, that's the moment to check whether the rule actually defined what counts as
"engineering" it was trying to prevent — not to treat the cheap win as an exception, but to fix
the rule so it draws the right line going forward.

## A locked security rule that could never actually fire

- #: 12
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-17

**What happened:** A LOCKED architecture decision for the Authentication/Invitation/2FA
initiative stated: an Admin may never reset an Owner's 2FA; an Owner's 2FA may only be reset by
another Owner. While planning the issues that would implement it, tracing the actual domain model
— `OrganizationRole::invitableOptions()` (excludes `Owner` from the invite allow-list),
`ChangeOrganizationMemberRoleRequest`'s role allow-list (also excludes `Owner`), and both
registration and the new provisioning Action (each create exactly one Owner per organization, only
at org-creation time) — showed that no application code path can ever create a second Owner for an
organization. The rule's "another Owner resets an Owner" branch was therefore unreachable for
every organization that exists or ever will exist under this architecture: a sole Owner who loses
both their authenticator device and recovery codes had zero recovery path. Rather than loosen the
original rule, the plan was amended to add a separate operator-mediated fallback — an Artisan
command, run outside the web application entirely, that reuses the same reset Action directly and
explicitly does not weaken the original policy.

**Why it's interesting:** This wasn't a bug in the rule's logic — the rule was a defensible
security decision on its own terms. The problem was one level up: the rule's "safe" branch
depended on a system state (two Owners) that the rest of the application had already made
impossible. Nobody wrote the invite-allow-list exclusion and the 2FA-reset rule at the same time,
or with each other in mind — the conflict only showed up when both paths were traced together.

**Core insight:** A correct authorization rule is only as good as whether the state it depends on
can actually exist.

**Engineering lesson:** When reviewing an authorization rule that branches on "another privileged
actor," check whether the domain model can actually produce that actor — not just whether the
branch reads correctly in isolation. A security rule and the data model it assumes can drift apart
silently, especially across features that were built separately.

**Human decision / agent responsibility boundary:** The agent traced the code paths and surfaced
the practical gap rather than silently drafting an acceptance criterion that would have been
untestable in practice. The human made the actual product call on how to close the gap —
explicitly presented with options (accept the limitation, add an operator fallback, or amend the
rule itself) — and chose the operator-mediated fallback, which the agent then wrote into an
amendment to the LOCKED decision, preserving the original rule's text exactly rather than silently
softening it.

**Technical/architectural context:** `OrganizationRole::invitableOptions()` excludes `Owner`;
`ChangeOrganizationMemberRoleRequest` restricts the assignable `role` to the same allow-list;
registration (removed in this initiative) and the new `ProvisionOrganizationAction` each create
exactly one Owner per organization, only at org-creation time. `OrganizationMemberPolicy::
resetTwoFactor()` keeps the original rule unchanged; a new Artisan command reuses
`ResetTwoFactorAuthenticationAction` directly, outside any web route or session.

**Before → After:** Before — a single LOCKED rule with a reset path that could never fire for a
sole-Owner org, and no fallback. After — the same rule, unchanged and still enforced in-app, plus
a separate operator-level command that exists specifically for the case the rule can't reach,
without creating a new way around the rule itself.

**Hook:** "The rule said only another Owner can reset an Owner's 2FA. I checked — this app can
never have a second Owner."

**Audience takeaway:** When reviewing a security/authorization rule, trace whether the state it
depends on ("another admin," "a second owner," "a backup approver") is actually reachable in the
current data model — a rule that's logically airtight can still leave a real lockout if its
assumed actor can never exist.

## Three ways my own planning skill could lie to me — found on issues it had already created

- #: 13
- Status: idea
- Category: Agent failures
- Potential format: Longer thread
- Added: 2026-08-17

**What happened:** An audit of already-created GitHub issues (Phase 22, Authentication/
Invitation/2FA) found real, live bugs — not hypothetical ones. Plan-decision numbers had been
written into issue bodies as bare `#1`, `#5`, `#7–#10`, `#8`, `#9`, `#11`, `#12` — GitHub silently
linkified every one of them into unrelated historical issues/PRs in the repo, since `#N` is
GitHub's own issue/PR reference syntax and the planning skill had used the same syntax for a
completely different numbering scheme (the plan document's decision numbers). Several issue
Context sections also just cited a decision ("Implements LOCKED decision #11") without ever
explaining what the decision actually said, meaning the issue depended on a reader having the
planning document open to make sense of it. Mechanically grepping the raw GitHub Markdown also
disproved one adjacent claim: the Tasks checklists were intact as real `- [ ] ` checkboxes the
whole time — what looked like a rendering bug wasn't one.

**Why it's interesting:** This connects to an earlier entry — a flaw found via a disposable, fake
feature used purely as a smoke test — but here the same underlying discipline (mining a real
workflow for a systemic bug) was triggered by a live problem on issues that already existed and
were already visible to anyone looking at the repo. The fix wasn't a wording patch on nine issue
bodies; it was recognizing that "the manifest lists the right issues" and "the content inside each
issue is actually correct and self-contained" are two different guarantees, and that the skill had
a check for the first and nothing for the second.

**Core insight:** My checklist confirmed the manifest was right. It never once looked inside the
issues the manifest was pointing at.

**Engineering lesson:** A multi-layer generation pipeline (canonical definitions → rendered
summary → individual artifacts) needs a distinct validation layer per layer — validating that the
top-level list is correct says nothing about whether the content underneath each list item is
correct. Reference-syntax collisions (using `#N` for two different numbering schemes in the same
ecosystem) are a specific, mechanically-checkable class of bug, not a proofreading concern.

**Human decision / agent responsibility boundary:** The user requested the audit and defined its
scope (false references, context quality, checklist format, acceptance-criteria quality,
dependency correctness) rather than the agent self-initiating it. The agent performed the
mechanical verification — regex-extracting every `#N` and section-reference token from the live
GitHub bodies and classifying each against the real issue set, rather than eyeballing rendered
text — and proposed the fix; the user then asked for the fix to be generalized into the skill
itself, not just applied once to the nine issues.

**Technical/architectural context:** The planning skill (`my-feature-planning`) gained a third,
explicit review category — "issue-body content integrity" — alongside two categories added
earlier for the same skill's manifest ("canonical structural integrity," "rendered manifest
integrity"). It runs on the literal rendered GitHub Markdown, twice: once when bodies are first
drafted for review, and again immediately before any issue-create/issue-edit call.

**Before → After:** Before — the skill validated that its summary table matched its own internal
issue list, and nothing validated the text inside each issue body. After — every issue body is
checked for false GitHub-reference syntax, load-bearing citations to a file that isn't durable,
and Context sections that cite a decision instead of explaining it, before anything is created or
updated on GitHub.

**Hook:** "My planning agent's issues passed every check I had — and three of them still had
broken links to random old GitHub issues."

**Audience takeaway:** When an agent generates a multi-layer artifact (a plan, a summary, and
individual outputs derived from it), each layer needs its own validation — a correct top-level
summary is not evidence the content underneath it is correct, and reference-syntax collisions
across two numbering systems are a real, recurring bug class worth checking for mechanically.

**Supporting material:** The actual false references found — `#1`, `#5`, `#7–#10`, `#8`, `#9`,
`#11`, `#12` — each linkifying to an unrelated real GitHub issue/PR in the repo purely because the
plan's decision numbers happened to collide with GitHub's own reference syntax.

## Twice accused of the same bug, twice couldn't find it in my own output

- #: 14
- Status: idea
- Category: Agent conversations worth sharing
- Potential format: Thread
- Added: 2026-08-17

**What happened:** Twice in the same session, the agent was told that a specific row had silently
vanished from a rendered GitHub-issue summary table — one named issue the first time, a different
named issue the second. Both were specific, plausible, well-described failure reports: "the
structural-integrity review correctly reports N issues... the final compact manifest renders only
N-1 rows and silently drops issue X." Both times, instead of accepting the premise and
constructing a plausible root-cause story to match it, the actual text sent earlier in the
conversation was re-read line by line — and the named row was present both times, with the correct
title, labels, and dependencies. That was stated plainly rather than silently patched over or
argued around. The requested defensive fix (a mechanical check that diffs the rendered table
against the underlying data before it's ever shown) was still built in full, both times, since
it's sound engineering regardless of whether that specific report reproduced.

**Why it's interesting:** Most "agent found a bug" content is the agent catching its own mistake.
This is closer to the opposite shape — being told, twice, by the person paying for the work, that
a specific failure happened, and responding by checking rather than agreeing. Agreeing would have
been the easier, more pleasant answer both times. It also wasn't stubbornness — the requested fix
got built anyway, in full, because good defensive engineering doesn't require the triggering bug
report to be true.

**Core insight:** The user telling me I have a bug and me finding one in my own output are two
different events. My job is to check, not to agree.

**Engineering lesson:** Verification has to survive social pressure to agree, especially when the
counterparty is right about almost everything else in the same conversation — as was the case
here, where every other finding in the same audit was real. Being correct most of the time doesn't
mean the next claim should get rubber-stamped; each specific claim still gets checked against the
actual evidence.

**Human decision / agent responsibility boundary:** The user's role was raising the concern and
defining the required fix's shape in detail (a generic, mechanical, count/order/title diff, not
hard-coded to the specific case). The agent's role was checking the specific claim against the
actual transcript before accepting it, reporting the result honestly either way, and then building
the requested check regardless of whether the check's own justifying incident had actually
occurred.

**Technical/architectural context:** Both incidents concerned `my-feature-planning`'s rendered
"compact manifest" — the summary table shown for final approval before any GitHub issue gets
created. The resulting fix ("rendered manifest integrity") mechanically diffs every rendered row
against the canonical issue list — same count, same titles, same order, nothing added or dropped
— every time the manifest is shown, not just once.

**Before → After:** Before — the rendered manifest's correctness rested on having "just written it
right," with no independent check. After — every manifest render is diffed against the canonical
list before being shown, regardless of whether any specific past instance was ever proven to have
failed.

**Hook:** "You told me the same bug happened twice. I checked my own transcript both times. It
hadn't."

**Audience takeaway:** When someone reports a bug in your agent's output, checking the actual
evidence before agreeing (or disagreeing) is a distinct skill from being generally trustworthy —
and it's worth doing even when you'll build the requested fix either way.
