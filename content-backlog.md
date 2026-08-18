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
| 15 | The IDE warning that took four tries to actually suppress | Agent failures | idea | Short thread | — |
| 16 | A feature flag almost broke a commit that hadn't been written yet | Engineering judgment | idea | Short thread | — |
| 17 | I built a skill by refusing to invent the one thing I didn't have evidence for | Agentic workflow evolution | idea | Longer thread | — |
| 18 | The test was red — and the code was right | Engineering judgment | idea | Short thread | — |
| 19 | I traced a bug into compiled node_modules JS to prove a contract before shipping it | Engineering judgment | idea | Thread | — |
| 20 | The commits I was asked to inspect were already pushed | Engineering judgment | idea | Short thread | — |

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

## The IDE warning that took four tries to actually suppress

- #: 15
- Status: idea
- Category: Agent failures
- Potential format: Short thread
- Added: 2026-08-18

**What happened:** A routine PhpStorm "Unhandled Exception" false-positive kept showing up on
`Notification::assertSentTo()`, `Crypt::decrypt()`/`encrypt()`, and `Google2FA` calls inside Pest
test closures — a known class of warning the project's own PhpStorm-conventions skill already
documented a fix for: place `@noinspection PhpUnhandledExceptionInspection` on the line directly
above the offending statement. That fix worked, repeatedly, across many call sites in the same
session. Then, in a test asserting that a failed insert rolls back a transaction, the same
annotation — placed directly above `expect(fn () => app(...)->handle($attributes))->toThrow(...)`
— did not suppress the warning; PhpStorm kept flagging the `->handle()` call *inside* the arrow
function. Moving the comment inline, immediately before the `fn` keyword itself, didn't fix it
either — same warning, now pointing at the same inner call from a different column. Only when the
throwing call was pulled out of the arrow function entirely, into a real `function () use (...) {
... }` closure body with the suppression comment as its first line, did the warning actually
disappear. That specific case — a documented top-level placement not reaching inside a nested
arrow-function scope — was then written back into the project's `my-phpstorm-conventions` skill as
its own documented pattern, with working and non-working code shown side by side, distinct from
the general rule it extends.

**Why it's interesting:** A fix that had already worked reliably, many times, in the same session,
on the same class of warning, suddenly stopped working — not because the rule was wrong, but
because the code's shape had quietly changed (the throwing call now lived inside a nested function
scope instead of directly in the test body). Two more attempts at placing the same annotation, in
increasingly specific spots, both failed the same way before the actual fix turned out to be
structural rather positional.

**Core insight:** "Works elsewhere in this file" isn't proof it'll work here — when a fix depends
on *where* you put it, the code's shape matters as much as the fix itself.

**Engineering lesson:** A suppression comment is scoped to whatever unit of code it sits inside.
Moving the throwing call into a *new* scope — here, an arrow function passed as an argument — moves
it out of reach of a comment placed in the outer scope, even immediately adjacent to it. When a
documented fix stops working, check whether the code's structure changed before assuming the fix
itself needs to change.

**Human decision / agent responsibility boundary:** The user pointed at each specific IDE warning
by file and line as it appeared, flagging symptoms in real time without diagnosing the cause. The
agent tried the documented fix, then two escalating variations, checked each one against the
actual IDE diagnostic feedback rather than assuming success, and once the real fix was found,
proposed writing it back into the project's own skill; the user asked for exactly that.

**Technical/architectural context:** Pest tests in a Laravel/Fortify app; PhpStorm's "Unhandled
Exception" inspection fires on any call to a method that declares `@throws` in its own docblock
when made inside a Pest closure, which has no real caller to propagate a `@throws` to — the
project's documented workaround is `@noinspection`, not restructuring the call.

**Before → After:** Before — the project's skill documented one fix for this whole class of
warning: `@noinspection` on the line above the statement. After — the skill also documents the
arrow-function edge case explicitly: when the throwing call lives inside `expect(fn () => ...)`,
extract it into a named closure with the suppression comment inside its body, because the
top-level placement structurally can't reach inside a nested function scope.

**Hook:** "I fixed the same IDE warning four times before I understood why the first three
attempts didn't count."

**Audience takeaway:** When a "known fix" for a linter/IDE suppression stops working, don't assume
the annotation itself is broken — check whether the code around it changed shape (a new closure, a
new scope) before trying more variations of the same placement.

## A feature flag almost broke a commit that hadn't been written yet

- #: 16
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-18

**What happened:** While splitting an already-implemented 2FA backend issue into semantic commits
— persistence layer, then Security-settings-page controller wiring, then enabling Fortify's
`twoFactorAuthentication` feature flag — the natural order, matching the issue's own task list,
was data layer → controller → flag. Before building the commits, checking what enabling the flag
would actually touch app-wide (not just the new code) surfaced that three pre-existing tests in
`SecurityTest.php` — a file this issue never edited — were gated behind
`skipUnlessFortifyHas(Features::twoFactorAuthentication())`, silently skipped for as long as the
feature had been off, and set to start running the instant the flag flipped on. Those tests
asserted exact Inertia props (`canManageTwoFactor`, `twoFactorEnabled`, `requiresConfirmation`)
that only the not-yet-committed controller change would actually return. Landing "enable the flag"
as the third commit, in reading order, would have made that commit red on arrival — not because of
anything wrong in the commit itself, but because it activated three tests whose dependency hadn't
landed yet. The commits were reordered instead: controller wiring landed second (inert while the
flag is off — every `Features::` check reads `false`), and enabling the flag moved to third,
activating everything at once, including retroactively proving the first two commits correct.

**Why it's interesting:** Nothing about the new code being committed was wrong. The risk was
entirely in *old*, already-skipped tests, in a file nobody was touching, one config change away
from waking up and failing against code that hadn't shipped yet. That's invisible if you only look
at the diff of the commit you're about to write — it only shows up by asking what state change
(not just what code change) that commit causes across the whole app.

**Core insight:** A feature flag doesn't just turn your new code on — it turns on every test
that's been quietly waiting for it, whether you touched that file this week or not.

**Engineering lesson:** Before committing a step that flips a config/feature flag, check which
currently-skipped tests are gated on that exact flag and confirm their dependencies are already
committed — not just the tests the current issue added. A `skipUnlessFortifyHas()`-style runtime
gate is invisible in a normal test run right up until the moment its condition changes.

**Human decision / agent responsibility boundary:** The reordering itself was a technical judgment
call the agent made and explained; the user's role was setting the standard being protected — keep
every commit coherent and passing — and approving the specific reordered plan, and the isolation
technique used to prove each commit really did stand alone, before any commit was written.

**Technical/architectural context:** Laravel Fortify feature flags (`config('fortify.features')`),
a project `skipUnlessFortifyHas()` test helper wrapping Pest's `markTestSkipped()`, and a
`git stash`-based per-commit isolation-verification technique — commit, stash everything else, run
the full suite against just what's landed, pop, repeat — used to prove the reordered split was
actually safe rather than just plausible.

**Before → After:** Before — commits were about to follow the issue's own task-list order (data →
controller → flag). After — commit order follows activation-dependency order instead, with the
flag-flipping commit deliberately landing last specifically because it's the one that changes what
other tests do.

**Hook:** "The bug wasn't in the commit I was about to write — it was in three tests I hadn't
touched."

**Audience takeaway:** Before flipping a feature flag or config switch in its own commit, check
what that flag *un-gates* elsewhere in the suite before deciding where that commit belongs in the
sequence.

## I built a skill by refusing to invent the one thing I didn't have evidence for

- #: 17
- Status: idea
- Category: Agentic workflow evolution
- Potential format: Longer thread
- Added: 2026-08-18

**What happened:** After four issues shipped with a real, repeated implementation pattern —
approve an issue, implement only its scope, stop for review, inspect the finished diff, propose
semantic commit boundaries, get that plan approved separately, build the commits, verify, ask
before closing the issue, recalculate what's unblocked next — that pattern was extracted into a
new skill, `my-git-workflow`. The framing was explicit: this is an extraction exercise, not a
greenfield design, and the instruction named the actual evidence to extract from — two issues that
shipped as one clean commit each, two that split into several dependency-ordered commits, all from
the same four-issue implementation history. While building it, one gap in the evidence stood out:
every commit across all four issues happened on a single branch,
`feature/organization-owner-provisioning`, that ended up carrying all four issues' worth of work
rather than one branch per issue. That's one data point about how this particular milestone
happened to be worked, not a repeated pattern — so the skill says exactly that, and explicitly
leaves branch-naming, PR conventions, merge strategy, and release process undesigned rather than
filling them in with plausible-sounding defaults.

**Why it's interesting:** The easy failure mode when building a workflow skill from a handful of
examples is generalizing past what was actually seen — turning "this is what happened once" into
"this is the rule now." The commit-splitting pattern had four real data points behind it and was
safe to codify as a rule. The branching pattern had exactly one, and codifying it anyway would have
produced a skill that sounded authoritative about something it had no basis for.

**Core insight:** A skill built from one example isn't a workflow yet — it's a guess wearing a
workflow's clothes.

**Engineering lesson:** When extracting a reusable process from real history, the evidence bar
isn't "did this happen" — it's "did this happen more than once, in more than one shape, for a
reason that generalizes." A single occurrence is a fact about that one instance, not yet a rule.

**Human decision / agent responsibility boundary:** The user set the extraction constraint up
front — build v0.1 from the actual implementation history, not from imagining a good workflow, and
explicitly do not invent branch conventions beyond the evidence available. The agent's job was
applying that discipline consistently while drafting the skill, including noticing and calling out
the branch-naming gap rather than quietly smoothing it over to make the skill feel more complete.

**Technical/architectural context:** The extracted skill sits between an existing planning skill
(`my-feature-planning`, which decides what work should exist and owns issue creation) and the
project's implementation skills (which own the actual code) — `my-git-workflow` owns everything in
between: implementation review, commit-boundary proposals, verification scope, issue closure, and
recalculating a milestone's dependency-ready set afterward.

**Before → After:** Before — the implement → review → commit-split → verify → close → recalculate
loop existed only as something the agent and user had converged on conversationally, issue by
issue, re-explained each time. After — it's a standing skill invokable with a short prompt
("implement #290, same workflow"), with its own rule files grounded in the four issues that proved
each rule, and an explicit list of what's still undesigned.

**Hook:** "The most important line in my new skill is the one that says 'we don't know this yet.'"

**Audience takeaway:** When you ask an agent to turn a real workflow into a reusable skill, the
evidence bar for "this is a rule" should be higher than "this happened once" — and a good
extraction says so out loud when it hits that limit, instead of quietly padding the gap with
something that sounds like a convention.

## The test was red — and the code was right

- #: 18
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-18

**What happened:** While fixing a `Switch` component's form-serialization bug, added a new backend
test posting the string `'1'` then `'0'` to toggle an organization's 2FA requirement on and back
off in one test, as the same Owner. The second assertion failed — the flag was still `true` after
posting `'0'`. Rather than assume the just-written fix was broken, added a temporary file-based
debug log inside the controller and reran the test: only one of the two HTTP requests ever reached
the controller body at all. Traced the second one to `EnsureTwoFactorRequirementIsMet` — a
pre-existing middleware from an earlier issue, sitting in the same route-middleware group — which
had redirected the Owner to the security settings page on their own very next request, because the
first request had just turned the org-wide 2FA requirement on and this particular Owner hadn't
enrolled their own 2FA yet. The fix was to rewrite the test into two independent, single-transition
cases instead of a two-step toggle, with the "turn it off" case starting from an Owner who already
has 2FA enabled.

**Why it's interesting:** The instinct when a brand-new test fails right after touching code is to
assume the new code is wrong. Here the code was correct and the test's own scenario was
unrealistic — a real Owner in production, in that exact situation, would hit the same redirect.
This is the mirror image of a more common failure mode (green tests hiding a real problem): a red
test that was actually surfacing correct, intentional enforcement working exactly as designed.

**Core insight:** A failing test isn't always pointing at a bug. Sometimes it's pointing at a
scenario that couldn't actually happen.

**Engineering lesson:** When a new test fails immediately after a change, trace before rewriting —
a temporary debug log at the actual boundary (here, the first line of a controller method) settles
in seconds whether the code or the test's premise is wrong, instead of guessing from the failure
message alone.

**Human decision / agent responsibility boundary:** The user had approved a specific correction
(move a component's serialization fix into the shared component itself) and asked for the relevant
tests to be added or updated as appropriate. Writing the new test, hitting the unexpected failure,
diagnosing it via debug logging, and deciding to rewrite the test rather than second-guess the
approved fix were all agent judgment calls, reported transparently as part of the verification
summary rather than glossed over.

**Technical/architectural context:** Laravel's `EnsureTwoFactorRequirementIsMet` middleware
(introduced in an earlier issue in the same project) sits in the same `organization`
middleware group as the settings-update route it was tested against — meaning any request from an
unenrolled user, including the very Owner who just changed the setting, is subject to it
immediately, with no grace period.

**Before → After:** Before — one test chained two sequential requests as the same Owner, toggling
the flag on then off. After — two independent tests, each proving one transition, with the
"turn off" case using an Owner who already has 2FA enabled so the middleware doesn't intercept the
request the test is trying to make.

**Hook:** "My test failed. The bug was in the test's assumptions, not the code."

**Audience takeaway:** A red test right after a change doesn't automatically mean the change broke
something — trace it to the actual point of failure before assuming the fix is wrong and rewriting
code to match a test that was never realistic to begin with.

## I traced a bug into compiled node_modules JS to prove a contract before shipping it

- #: 19
- Status: idea
- Category: Engineering judgment
- Potential format: Thread
- Added: 2026-08-18

**What happened:** Asked to review a two-factor-authentication confirmation form against the
actual framework source before sign-off. Reading Laravel Fortify's `ConfirmTwoFactorAuthentication`
action directly showed it throws its validation failure into a *named* error bag
(`confirmTwoFactorAuthentication`), not the default one — confirmed independently against an
already-passing backend test that explicitly asserted that exact named bag. That raised a real
question: would the Vue form's plain `errors.code` binding actually see that error at all? Traced
Inertia's Laravel adapter source to see how it resolves session validation errors into the shared
`errors` prop — confirmed that without a `'default'` bag present, named-bag errors return nested
under their bag name, not flattened. Then went into the *compiled* `@inertiajs/vue3`/`@inertiajs/core`
JavaScript bundles in `node_modules` to confirm the client-side `<Form>` component only unwraps a
named bag into its flat `errors` slot when told which bag to read. The form in question wasn't
telling it. `errors.code` would have been `undefined` on every genuine wrong-code submission —
the input would have silently rejected the user's code with zero visible feedback.

**Why it's interesting:** This defect was invisible to every automated check the project actually
runs — formatter, backend test suite, linter, TypeScript checker — because the stack has no
frontend component or browser test layer. The backend test proved the *session* carried the
bagged error; nothing proved what the frontend actually did with it. The only way to catch it was
reading the real contract at every layer it crossed: PHP action, PHP framework adapter, and
finally the actual shipped JavaScript the browser runs.

**Core insight:** When there's no test that would catch it, reading the actual source three layers
down is the test.

**Engineering lesson:** A named error bag is a real contract between backend and frontend, and
it's opt-in on both ends — using one server-side buys nothing on the client unless the client
explicitly asks for that same bag by name. Nothing fails loudly when this is missed; the error
message just never appears.

**Human decision / agent responsibility boundary:** The user's review request explicitly asked to
verify the flow against Fortify's actual response contracts, not just against documentation or
assumption. The agent's job was to actually go read the three source layers rather than trust that
a standard-looking `<Form v-slot="{ errors }">` binding would just work — found and reported the
defect with exact file/line citations; the user approved the fix directly from that evidence.

**Technical/architectural context:** `Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication`'s
`->errorBag('confirmTwoFactorAuthentication')` call; `inertiajs/inertia-laravel`'s
`resolveValidationErrors()` bag-resolution logic; the Inertia Vue3 `<Form>` component's
`errorBag`/`error-bag` prop, verified directly in the compiled `dist/index.js` of both
`@inertiajs/core` and `@inertiajs/vue3`.

**Before → After:** Before — the confirm form silently discarded validation errors on a wrong
code, with no visible feedback to the user. After — `error-bag="confirmTwoFactorAuthentication"`
on the form makes the same errors appear correctly.

**Hook:** "The bug had no test that could catch it — so I went and read the framework's compiled
JavaScript instead."

**Audience takeaway:** When a stack has a genuine test-coverage gap — no component tests, no
browser tests — don't let that gap become invisible risk on anything that crosses it. Go verify
the actual contract in source instead of trusting docs, convention, or "it looks like every other
form."

## The commits I was asked to inspect were already pushed

- #: 20
- Status: idea
- Category: Engineering judgment
- Potential format: Short thread
- Added: 2026-08-18

**What happened:** Asked to inspect a set of "unpushed" commits for a missing convention and
report how to safely amend them, without making any changes yet. Ran `git fetch` before trusting
that framing, rather than assuming the local branch state matched the premise — and found the
branch was already fully in sync with `origin`. Every one of the commits in question was already
public. That single fact changed what the eventual operation actually was: not a quiet local
`git commit --amend`-style fixup, but a full history rewrite of already-shared commits requiring a
force-push, explicit verification that nothing else depended on that history, and a considered,
authorized destructive-git-operation decision — not something to walk into on the strength of a
one-word assumption in the request. Reported the discrepancy plainly before proposing anything.
When later authorized to proceed, executed the rewrite by replaying each commit through
`git commit-tree` (preserving trees and author/committer metadata exactly, changing only the
messages), validated it with a tree-hash identity check and a full `git range-diff` before
touching the remote, confirmed no open PR or other branch depended on the old history, and pushed
with `--force-with-lease` rather than a bare force push.

**Why it's interesting:** The word "unpushed" in the request was doing a lot of unexamined work —
it implicitly set the whole risk profile for what came next. Taking five seconds to fetch and
check, before reasoning about how careful the rest of the operation needed to be, turned out to
matter more than any of the individual safety mechanics used afterward.

**Core insight:** Before you decide how careful to be, check whether the thing you're about to
touch is actually as private as you think it is.

**Engineering lesson:** "Unpushed" and "pushed" aren't just a descriptive label on a request —
they determine whether an operation is a safe, purely local rewrite or a shared-history rewrite
that needs explicit authorization, a dependency check, and a force-push. Re-verify
state-dependent assumptions before calibrating risk around them, even when the assumption comes
from the user's own phrasing of the task.

**Human decision / agent responsibility boundary:** The user asked for inspection and a report
first, explicitly deferring the actual rewrite decision. The agent's job was to investigate
accurately, including the state of the branch itself, not only the specific thing asked about (the
missing trailers) — surfaced the pushed/unpushed discrepancy unprompted, proposed a safe rewrite
procedure, and only executed it after an explicit follow-up approval that added its own further
safety constraints (verify no dependents, preserve content exactly, validate with a diff before
pushing).

**Technical/architectural context:** `git commit-tree` used to replay a linear commit history with
new messages while keeping every tree hash and every author/committer identity and timestamp
byte-identical; `git range-diff` as the mechanical, human-checkable proof that nothing but the
messages changed; `--force-with-lease` as the push mechanism that refuses if the remote moved
unexpectedly since the last fetch.

**Before → After:** Before — the task was framed as amending some unpushed commits. After — a
verified, safe rewrite and force-push of eleven already-public commits, proven safe via tree-hash
identity and a full range-diff before anything touched the remote.

**Hook:** "I was asked to fix some unpushed commits. They weren't unpushed."

**Audience takeaway:** When a request assumes a particular state — "this is local," "this hasn't
shipped," "nobody's seen this yet" — verify that assumption before calibrating how carefully to
proceed. The assumption itself is often what determines whether the rest of the plan is actually
safe.
