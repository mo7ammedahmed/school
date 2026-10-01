# Security Fixes

Running record for the security mandate. Each finding says whether it was
confirmed, what changed, which tests pin it, and what is still open. Nothing
here is marked fixed without a test that fails against the old behaviour.

Verified at the end of Phase 3: `phpunit` 538 tests / 3727 assertions, PHPStan
level 5 clean, Pint clean (634 files), `tsc --noEmit`, `oxlint` and Vitest clean.
At the Phase 4 checkpoint below: `phpunit` 560 tests / 3781 assertions, PHPStan
level 5 clean, Pint clean (646 files); the frontend gates are untouched by this
phase. At the Phase 5 checkpoint: `phpunit` 564 tests / 3786 assertions, PHPStan
level 5 clean, Pint clean (647 files), `tsc --noEmit` clean, `oxlint` 0 warnings,
Vitest 17 passed.

## Phase 1 — Authorization (complete)

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | The `auth + school.context` group enforced no permissions, so a guardian or pupil of the school's own school could open the registrar's screens | Confirmed | `permission:` middleware on each route group, permissions from the seeder (`74a21d6`) | `RouteGroupAuthorizationTest` (guardian/student → 403 on `/students`, `/finance/payments`, `/finance/refunds`, `/audit-logs`), `RoleRouteMatrixTest` (all 8 seeded roles × every gate, derived from the router at run time) |
| 2 | `manage-discounts` and `view-audit-logs` were checked but never seeded | Confirmed | Seeded (`687db95`) | `RoleRouteMatrixTest`, `SeededRolePermissionsTest` |
| 3 | `AuthServiceProvider` was never registered in `bootstrap/providers.php`, so its policy map had never run; Laravel's naming convention resolved what it could and silently failed where names diverged (`TeacherProfile` had no policy at all) | Confirmed | Provider deleted; explicit `Gate::policy()` map in `AppServiceProvider` (`dba127e`, `aee37e6`) | `RoleRouteMatrixTest` exercises every gated route through the real Gate |
| 4 | Eight duplicate policy classes under `App\Policies` shadowed (or diverged from) the domain policies | Confirmed | Deleted; the subclasses are mapped to the domain policy | `PermissionNamesExistTest` scans both policy directories |
| 5 | `StudentPolicy::view/update` used `manage-students \|\| same school`, so either condition alone granted access | Confirmed | Permission **and** school match (`493f34b`) | `StudentPolicyTest` (no-permission same-school user denied; other-school denied; graduated delete denied) |
| 6 | 22 permission names checked by policies were not in the catalogue, so `hasPermissionTo()` threw `PermissionDoesNotExist` — a 500, not a deny | Confirmed | Catalogue completed (`29dd486`) | `PermissionNamesExistTest` pins the 22 names *and* greps every policy source for unseeded literals |
| 7 | `FeeStructurePolicy::update/delete` never checked a permission | Confirmed | `manage-fee-structures` required (`85f8ee8`) | `FeeStructurePolicyTest`, `InvoicePolicyTest` |

**Open from Phase 1**

- ~~**Four dead routes** named controller methods that do not exist~~ — closed in
  Phase 5: all four retired with their reasons, and `RoleRouteMatrixTest` no
  longer carries an exclusion list, so it exercises every gate again.
- ~~**Controller-level `authorize()` is thin**~~ — closed below. The six resource
  controllers that consulted nothing now consult their policy in every `show`,
  `edit` and mutating action, and doing so surfaced a latent over-grant in 53
  policy abilities that had to be fixed first.

### The latent over-grant, and why adding `authorize()` was not safe on its own

`StudentPolicy` was corrected to `&&` in Phase 1, and the same disjunction was
left in 53 abilities across 53 other policies:

```php
return $user->hasPermissionTo('manage-students') ||
    $student->school_id === session('school_id');
```

That grants to a registrar as intended, and it also grants to a pupil whose only
claim is that the row belongs to their own school. It was harmless *by accident*:
no controller called `authorize()`, so the second operand was never reached on a
real request. **The Phase 1 follow-up was the thing that would have made it
reachable** — adding `authorize()` to a screen turns a dormant over-grant into a
live one. The order matters more than either change alone.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 8 | 53 policy abilities conjoined nothing: `permission \|\| same school` granted on the tenant alone, dormant only because no controller consulted them | Confirmed, latent | `\|\|` → `&&` in all 53; `StudentPolicy` was already correct and is not in the count | `PolicyAbilitiesAreConjunctiveTest` — a source-level scan over every policy on disk, written red at 53 failures, plus a guard that every ability checks *some* permission |
| 9 | `InvoicePolicyTest` never pinned the session, so the old `\|\|` masked it and the test asserted against a policy that was never really consulted | Confirmed | Session pinned in the two affected cases; a third case added asserting the permission alone does **not** open another school's invoice, so the conjunction cannot be "simplified" back | `InvoicePolicyTest` |
| 10 | `StudentController`, `GuardianController`, `TeacherController`, `RoomController`, `MessageController` and `UserController` consulted no policy in `show`/`edit`/mutating actions | Confirmed | `$this->authorize()` in all 15 show/edit/mutating actions | `ActionLevelAuthorizationTest` — 53 cases: a foreign row refused, a same-school row allowed through to validation, and a same-school row still refused when the session points elsewhere. Verified red without the change |
| 11 | `UserController::ensureUserBelongsToCurrentSchool()` hand-rolled a membership check, answering 404 where every other layer answers 403 | Confirmed | Deleted; `UserPolicy` consulted instead. The policy was already equivalent — `currentMembership` filters `is_active = true` | `ActionLevelAuthorizationTest` user cases |
| 12 | `InvoiceController` carried its own copy of the invoice immutability rule and it was weaker than `InvoicePolicy`'s: an issued invoice with no recorded payments could be deleted, and an issued or partially paid invoice could be issued a second time (re-announcing and re-delivering it). The copy also spelled the locked status `void`, which the enum spells `voided`, so a voided invoice would have slipped through it. The policy's third ability — `update` allowed on a draft only — was the stale layer: the update path deliberately preserves a partially paid invoice's collected amount, and `InvoiceTest::test_editing_a_partially_paid_invoice_keeps_it_partially_paid` pins that | Confirmed | The policy is now consulted by `edit`, `update`, `destroy` and `issue`, and is the single source of truth: `update` allows `draft|partially_paid`; `delete` and `issue` stay draft-only. The controller's duplicated `abortIfLocked()` is deleted | `InvoicePolicyEnforcementTest` — 9 cases, written red at 3 against the old controller and policy (issued invoice deleted 302, issued invoice re-issued 302, partially paid invoice re-issued 302); `InvoicePolicyTest` pins the corrected `update` and the draft-only `delete` |

**On the answer a refusal takes.** `ActionLevelAuthorizationTest` accepts 403
*or* 404 for a foreign row rather than pinning one, and that is deliberate. For
every tenant-bound model `BelongsToSchool` narrows route-model binding, so the
id never resolves and the answer is 404 — the better of the two, since a 403
confirms the row exists elsewhere. `User` carries no `school_id` and is not
tenant-bound, so nothing upstream can refuse it and the policy inside the action
is the only defence there; those four cases are held to an exact 403. Asserting
one fixed code everywhere would have pinned the suite to whichever layer happens
to run first and called a working defence a regression the next time a model
gained or lost `BelongsToSchool`.

Writing that test also caught a false pass worth recording: the first version
granted only `manage-users`, but `/settings/*` is gated on
`manage-settings|manage-schools` at the group, so the middleware refused every
user case with a 403 — which the refusal assertions would have accepted as a
pass while the policy was never consulted. Both permissions are now granted, and
the allow-case assertion is what makes that class of mistake loud.

**The policy-discovery guard that came out of this pass.** `PolicyDiscoveryTest`
enumerates the models each route binds, resolves each through the real
`Gate::getPolicyFor`, and requires a policy or a written exception. Written red
at three: `AdmissionApplication`, `InterfaceTranslation` and `School` are bound
and unpolicied — each is a shared queue or a super-admin screen where the route
gate is the whole decision, and each is now named with that reason. The same
test asserts the thin `App\Models\*` aliases resolve to the same policy as their
domain parents, which caught `App\Models\Classroom`: a compatibility alias onto
the `rooms` table whose explicit `manage-classrooms` permission the seeder
derives from `manage-rooms`, so the two answer identically today. No route binds
it; it is recorded as the one documented exception, and consolidating the alias
belongs with the model-layer consolidation rather than this phase.

**One divergence in this pass was not a one-way fix.** `InvoiceController` and
`InvoicePolicy` disagreed about all three write abilities, so which layer won was
decided per ability from the evidence: `update` follows the controller and its
pinned feature test (a partially paid invoice stays editable and keeps the
collected amount), while `delete` and `issue` follow the policy (draft-only),
because no screen offered either on a non-draft invoice, no test relied on it,
and `send` is the resend path. The local duplicate is gone, including its
spelling of the locked status — it checked `void` where the enum spells `voided`.

## Phase 2 — Payments and webhooks (complete)

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | No authenticity check anywhere: any POST to `/webhooks/payments/{gateway}` was settled | Confirmed | Per-school encrypted `webhook_secret`, constant-time compare, verification before any write (`e1bfef2`) | `WebhookSecurityTest`: unsigned, wrong secret, another school's secret, no secret configured |
| 2 | `webhooks/*` was not CSRF-exempt, so a real gateway delivery was answered 419 before application code ran | Confirmed | Exempted in `bootstrap/app.php`; 60 requests/minute throttle (`018305b`) | Exemption asserted in the middleware's own exclusion list, plus a request posted with CSRF enforced; 429 after 60 |
| 3 | The payload was read in a flat shape the provider never sends — the payment lives under `data` in Moyasar's documented envelope | Confirmed | Envelope parsing (`e1bfef2`); `PaymentSettlementTest` rewritten to the documented shape | `WebhookSecurityTest` settles only from the documented envelope |
| 4 | Settlement trusted the payload: `GatewayTransaction` was written `completed` unconditionally, no amount or currency check, no gateway confirmation | Confirmed | Re-fetch from the gateway is the only authority; amount and currency compared with the local payment; mismatches refused 422; the gateway's real status is stored (`952a1ee`) | `test_the_payloads_claim_of_success_is_not_enough_to_settle`, amount mismatch, currency mismatch, `test_the_gateway_is_asked_to_confirm_the_payment` |
| 5 | Dedup was check-then-insert, a failed event could never be retried, and a concurrent loser could demote a completed event | Confirmed | One transaction per delivery; payment and invoice locked; `failed` events retryable, `completed` events replayed; a lost unique-index race defers to the winner (`952a1ee`) | replay settles once, retry-after-failure, amount paid never doubles, one receipt |
| 6 | Failures were swallowed and the endpoint still answered `200 {"status":"success"}`, so the provider never retried | Confirmed | Result-driven statuses: 200 settled/replayed/ignored, 401 unverifiable, 422 inconsistent, 500 retryable | `WebhookSecurityTest` asserts the status of each outcome |
| 7 | Failures logged the whole payload, which carries card metadata | Confirmed | Logs carry gateway, event id and school only | `test_a_webhook_failure_does_not_log_the_payload` (listens to the real log events) |
| 8 | `hyperpay` and `stripe` were accepted although neither has a client or a verifier | Confirmed | A gateway with no registered verifier is answered 404 | `test_an_unimplemented_gateway_is_refused` |
| 9 | The unique index on `(gateway, event_id)` was claimed missing | **Not reproducible** | It already existed (`2025_01_01_000042`); the retry semantics around it were the real defect | — |
| 10 | `Payment::find()` on untrusted metadata, unscoped | Confirmed, deliberately kept | The lookup only identifies which school's secret must verify the delivery; nothing settles on it, and the write path is anchored to the verified payment | `test_one_schools_secret_does_not_verify_another_schools_delivery` |

**Open from Phase 2**

- **A school with no `webhook_secret` stops settling** until an operator sets
  one in Settings → Payments. That is the intended fail-closed behaviour; the
  field and the webhook URL are already on that screen.
- **HyperPay and Stripe are refused**, not verified. Adding either means a
  `WebhookVerifier` *and* a gateway client — signing a payload nobody can
  re-fetch from would be a check in name only.
- **`gateway_transactions` has no unique index** on
  `(gateway, gateway_transaction_id)`. Concurrent deliveries for the same
  payment are serialised by the payment row lock, so the remaining window is
  narrow; a reversible unique-index migration is the follow-up if the table
  ever shows duplicates.
- **Settlement is synchronous** inside the webhook request. Moyasar asks for a
  fast 2xx before slow work; moving settlement to a queued job belongs with the
  infra scheduler/queue phase, and the current path is fast (one HTTP call and
  a transaction).
- **`webhook_events.payload` still stores the delivery** for audit. It is not
  logged; pruning/retention belongs to the infra scheduler phase.
- **Tenant scoping of the identification lookup** arrives with the global
  school scope (infra Phase 3 / security Phase 4), which is one piece of work,
  not two.

## Phase 3 — Authentication hardening (complete)

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | A two-factor account signed in with a password alone: the login controller never consulted `two_factor_enabled`, and the challenge sat in the guest group reading `$request->user()`, so it could only answer a user who was already signed in — the one state it exists to prevent. The `auth.two_factor_confirmed` flag it set was read by nothing | Confirmed | A correct password parks the identity in the session (guard logged out, pending id and remember choice stored) and the challenge finishes the sign-in; a visitor with no pending sign-in is sent to the login (`26aaa76`) | `TwoFactorEnforcementTest` — password alone, dashboard unreachable, valid code, invalid code, recovery code once, no pending sign-in, plus the untouched no-2FA path. `TwoFactorTest` now pins the page for the state it is for |
| 2 | Login, forgot-password, reset-password and the challenge were not limited at all: a password could be guessed at request speed, the reset endpoint could flood an inbox indefinitely, and six digits is a small space to walk | Confirmed | `ThrottlesAttempts`: five attempts a minute keyed by the identity tried (email, or the pending user) plus the caller's address, cleared on success, refused even when the sixth is correct (`8201a4f`) | `AuthThrottleTest` — all four surfaces, plus a sign-in inside the limit |
| 3 | Recovery codes were stored as plaintext (encrypted at rest, but plaintext) and compared with `in_array`, a scan that stops at the first differing byte | Confirmed | SHA-256 hashes compared with `hash_equals`; a spent code is removed; a legacy plaintext set keeps working and is re-hashed the first time one is used (`b4ca6bc`) | `RecoveryCodesTest` (hashing, matching, spending, legacy) and the enforcement test's recovery-code case |
| 4 | A password change did not revoke other sessions: the session middleware that keeps the password-hash copy was never enabled | Confirmed | `$middleware->authenticateSessions()`; the change ends every other session on its next request while the device that changed it stays signed in (`ebb29c0`) | `SessionHardeningTest` |
| 5 | Switching school put a new tenant in the session without regenerating its id; enabling or disabling 2FA did the same for a security-posture change | Confirmed | `regenerate()` on school selection and on both 2FA transitions (`ebb29c0`) | `SessionHardeningTest` (school switch); the 2FA transitions are covered by review, not a case |
| 6 | The only 2FA test asserted the challenge page answers 200 for a visitor with no sign-in in progress — the behaviour that had to change | Confirmed | Rewritten to the pending state; the enforcement cases moved to `TwoFactorEnforcementTest` | `TwoFactorTest` |

**Open from Phase 3**

- **Recovery codes are never shown.** They are generated, hashed and counted,
  and the settings screen displays only how many remain — so a user who loses
  their authenticator cannot actually use one. The follow-up is to display the
  set once, when it is generated.
- **An administrator changing another user's roles or memberships does not end
  that user's sessions.** Only the user's own school switch and 2FA transitions
  regenerate their session. Invalidating on role change needs either a sweep of
  the session store or an auth-version column.
- **The limiter is per identity and address.** A distributed attempt across many
  addresses is not covered by it; that is what an edge/WAF rate limit is for.
- **The challenge redirect tells an attacker the password was right** for a
  two-factor account. That is inherent to a two-step flow and matches common
  practice; the alternative is a generic "continue" page that reveals nothing,
  at the cost of a worse experience for the overwhelming majority who are
  legitimate.

## Phase 4 — Tenant isolation (complete)

Verified by `tests/Feature/Security/TenantIsolationTest.php`, a cross-tenant
matrix that asserts the *required* behaviour and started fully red, and by
`tests/Feature/Security/TenantValidationRuleScopeTest.php`, which proves the
validation mechanism and scans the source for object rules that bypass it.
All seven findings are fixed; what remains below them are follow-ups, not holes.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | Implicit route-model binding resolved ids straight off the primary key, so `show`, `edit`, `update` and `destroy` had nothing to check: enrolments, admission applications, discounts, students, sections and academic years could be opened, edited and deleted by a foreign school | Confirmed | `BelongsToSchool` adds the active tenant to `resolveRouteBindingQuery`; foreign ids answer 404 (not 403, which would confirm the row exists elsewhere) (`2af16b0`) | `TenantIsolationTest` list/show/edit/update/delete cases; the two older 403 assertions were updated to 404 |
| 2 | `EnrollmentController` listed every school's enrolments, offered every school's students/sections/years in the form, and accepted foreign ids in `exists:` rules — a school could enrol its pupil into a stranger's section | Confirmed | `forSchool()` on list and form queries; `Rule::exists(...)->where('school_id', …)` on both rule sets; `school_id` stamped from the tenant context (`2af16b0`) | `TenantIsolationTest` (list, form, two foreign-FK cases) |
| 3 | The admissions review queue asked platform-wide questions with one school's id: the reviewer picker listed every school's staff, `assign` accepted any user on the platform, and `bulkUpdate` validated and updated foreign application ids — writing status and event rows onto a stranger's application | Confirmed | Queue resolves the id through `schoolId()`; reviewer candidates must hold an active membership in this school; application ids are scoped by rule and the service takes the school id, filtering both of its queries (`1b6be3c`) | `TenantIsolationTest` (bulk write refused whole-request, foreign reviewer refused, reviewer picker) |
| 4 | `DiscountController` listed and offered platform-wide rows and stamped `school_id` from the raw session | Confirmed | `forSchool()` on list and pickers; stamp from the tenant context (`1b6be3c`) | `TenantIsolationTest` (list, open, edit, delete) |
| 5 | 63 models carry `school_id`; only 13 carried `BelongsToSchool`, so the rest could be queried across schools by an omission | Fixed | Rollout complete: `TenantContext`, fail-closed `TenantScope`, auto-stamp and `withoutSchoolScope()` are live on every school-owned model (`2af16b0`, `1f058bc`, `85b99ca`, `1a436b6`, `597c4c9`, `b9e9725`). Three deliberate exclusions, each with its reason recorded: `UserMembership` (the resolver cannot depend on its own answer), `AuditLog` (platform and support actions too), `WebsiteThemePreset` (no `school_id` by design) | The whole suite runs against the live scope; the rollout exposed and fixed real unscoped services: the timetable conflict detector, the settings stores used outside requests, invoice delivery, and settlement |
| 6 | 181 `exists:` rules across controllers, form requests and DTOs are unscoped, so a foreign id validates | Fixed | `TenantAwareValidator` is resolved by the validation factory and narrows every string `exists:`/`unique:` rule naming a table with a `school_id` column to the active tenant, fail-closed with no context (`7d3d6a9`). `Rule::exists()`/`Rule::unique()` objects bypass the validator, so the guard test scans the source and fails when one of them names a tenant table without its own filter, with a reasoned exemption list for deliberate crossings | `TenantValidationRuleScopeTest` — four runtime cases (foreign row refused, other school's unique value free, platform table untouched, no context accepts nothing) plus the source scan; the whole suite passes unchanged with the resolver live |
| 7 | Ownership checks answer 403 (`ensureOwned` and hand-rolled copies) while binding now 404s, so the two halves of the app disagree about what "not yours" means | Fixed | `Controller::ensureOwned` and every hand-rolled copy deleted — student, guardian, teacher, room, message and the grading scale/category screens. Every model they guarded is tenant-bound, so a foreign id is answered 404 at the route and the check was unreachable; `Controller::schoolId()` now reads the tenant context rather than the raw session | The whole suite passes without them; the isolation matrix exercises the binding path |

**Open from Phase 4**

- **Policies still compare the session directly** (`$model->school_id ===
  session('school_id')` in every per-school policy). That is fail-closed — a
  console or queued `authorize()` has no session, so it denies — but it is a
  second tenant comparison next to `TenantContext`. Folding the policies onto
  the context is the follow-up; it is a refactor, not a hole.
- **Two public entry points had to pin their own tenant, and that shape is
  worth remembering.** Route model binding runs before route middleware, so the
  signed `/pay/{invoice}` links cannot type-hint the model: the middleware
  resolves the invoice unscoped (the signature is the access proof), pins its
  school, and the controller's scoped lookup finds it. A webhook names its
  payment in metadata and does the same — one deliberate `withoutSchoolScope()`
  lookup to identify the school, then everything inside that school. Both are
  the pattern for any future unauthenticated route with a tenant row.
- **`App\Models` aliases duplicate the domain layer.** Thirty thin subclasses
  exist so old type hints resolve; the policy map knows both names. Consolidating
  them removes the second name from every `Gate` lookup, but it touches most
  controllers, so it belongs with the infra Phase 4 model-layer work.
- **Roles are platform-global** (`config/permission.php` `teams => false`),
  and the reviewer-picker bug showed why that has to be answered deliberately:
  "who is in this school" is a membership question, never a role question. The
  decision and its consequences are recorded as Decision 18.

## Phase 5 — Routing bugs (complete)

Every earlier phase met this defect one route at a time: a route that names a
controller method nobody wrote is a 500 for every role that passes its gate,
and the authorization matrix had four such routes in an exclusion list instead
of proving them. So the table itself is now checked as a table, by
`tests/Feature/Security/RouteIntegrityTest.php`, written red (`02e8ebd`) and
running against every registered route at build time: a `uses` string naming a
missing method fails, and a named route that an earlier route answers first
fails too.

It found 27 routes naming missing methods — the four known ones and 23 more —
and three routes shadowed by an earlier registration. The decisions below are
per finding, all made from the call sites: nothing in the frontend or in PHP
linked to any retired endpoint.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | 27 routes named controller methods that do not exist (`onboarding.*` × 9, `roles.*` × 6 + `permissions.index`, `reports.export`, `teachers.destroy`/`schedule`, `academic-years.destroy`, `subjects.offerings`, `my-grades`, `finance.my-fees`, `finance.fee-assignments.index`, `documents.categories`, `enrollments.waitlist`, `verification.notice`) | Confirmed | `verification.notice` gained the method it named; the other 26 were retired with a reason each (`11b123d`), because no screen calls them | `RouteIntegrityTest` (table-wide), `RoleRouteMatrixTest` (no exclusions left) |
| 2 | The verification notice also sat in the `guest` group, so the signed-in unverified user it is for was redirected to the dashboard | Confirmed | Route moved into the authenticated group; `VerifyEmailController::create()` renders `auth/verify-email` with the session status (`db6bb0f`) | `VerifyEmailTest` — unverified user answered 200, guest redirected to `/login` |
| 3 | Three routes were shadowed by an earlier route: `GET|POST /documents/upload`, `GET /documents/categories` and `GET /enrollments/waitlist` were all answered by the resource show route with the literal segment as the id | Confirmed | Deleted; `/documents/create` and `POST /documents` are the upload screen the UI already posts to (`11b123d`) | `RouteIntegrityTest` — every named route must match itself first |
| 4 | The onboarding wizard's nine POST routes were unnamed, unlinked and unimplemented: every Continue button was a 500 | Confirmed | Write path retired; the steps navigate and their forms are illustrative (`6c98e5c`). Provisioning stays on `POST /schools`, which creates the school **and** the creator's membership | `RouteIntegrityTest`; frontend gates |
| 5 | Retiring `finance.my-fees` left `App\Http\Controllers\FinanceController` with no route at all, and its three methods duplicate screens other controllers serve | Confirmed | Deleted (`334448e`) | full suite, PHPStan |

What was retired, and what stands in for it:

- **`my-grades` / `finance.my-fees`** — the student portal (`student.grades`,
  `student.fees`) and the guardian portal (`guardian.children.*`) serve both,
  and the sidebar links to those.
- **`roles.*` and `/permissions`** — the roles screen is read-only by design
  ("Roles are assigned to users from the Settings → Users screen"), so it keeps
  one `roles.index` route.
- **`reports.export`** — the reports index is a hard-coded list of three names;
  there is no report to export yet.
- **`teachers.destroy` / `teachers.schedule`** — the staff list links to show
  and edit only, and a teacher's timetable is read from the timetable screens.
- **`academic-years.destroy`** — no delete action in the UI, and a year
  cascades into its sections.
- **`subjects.offerings`, `finance.fee-assignments.index`** — no screen, and
  the permission strings stay seeded because `OfferingPolicy` and
  `FeeAssignmentPolicy` still check them.

**Open from Phase 5**

- **The verification link has no route.** `VerifyEmailController@__invoke`
  reads the signed URL a verification mail carries, but nothing sends that
  mail, so only the notice half of the flow exists. Wiring `verification.verify`
  belongs with a registration flow.
- **A real onboarding provisioning flow is still unwritten.** The wizard is now
  honest about being a walkthrough. If it should create the school, its year,
  grades, subjects and fee structure, it should call the existing domain
  actions (`CreateAcademicYear`, and the model paths the settings screens use)
  with tests, not the `store*` methods that never existed.
- **Retired permissions have no screen.** `manage-offerings` and
  `manage-fee-assignments` are checked only by policies for models no screen
  exposes, and `view-own-grades` / `view-own-fees` are checked by nothing now
  that the portals are the only self-service routes. They stay seeded until the
  screens exist or the policies are retired with them.

## Phase 6 — Platform hardening (in progress)

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | Every generated absolute URL was built from the request's `Host` header. A password-reset request sent with `Host: evil.test` mailed the victim a genuine, token-bearing reset link to the attacker's domain; the same origin builds invoice links and the signed `/pay/{invoice}` URLs | Confirmed | `URL::forceRootUrl(config('app.url'))` in `AppServiceProvider::boot()`, in every environment, not only production | `GeneratedUrlHostTest` — the reset mail is asserted to carry the configured host; `url('/')` is asserted to be rooted there too |

`forceScheme('https')` was already there but production-only, and it fixes the
scheme rather than the host, so it never covered this. Forcing the root in every
environment is deliberate: `APP_URL` is set in all of them, and a
host-header-driven link is a defect everywhere. A deployment that genuinely
serves the app from several hostnames is a tenant *routing* question (see
`SchoolResolver` and Decision 7), not a link-generation one.

Still open in this phase: the SchoolResolver fallback has not been re-checked
against the domain/slug requirement, and the audit-log, upload and remaining
money findings are not yet started.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 2 | `script-src` carried `'unsafe-inline' 'unsafe-eval'`, which together make the CSP a no-op against XSS: any script string on the origin executes, and script that runs before the CSRF token, the tenant scope or the policy layer is consulted defeats all three | Confirmed | Both removed. A 32-byte CSPRNG nonce per request authorises the tags the response actually emits, propagated to every Vite-generated tag via `Vite::useCspNonce()` | `ContentSecurityPolicyTest` — 8 cases: neither token present, `'self'` retained, nonce unique per response, nonce length and alphabet, dev server still permitted. Written red at 5 of 8 |
| 3 | `X-XSS-Protection: 1; mode=block` was set. It was removed from every current browser years ago, so it advertises a protection the application does not have | Confirmed | Header removed | `ContentSecurityPolicyTest` |
| 4 | A CSP that is correct as a string and broken in a browser is worse than a weak one, because it is trusted. Nothing checked that the rendered document satisfies the header | Confirmed | — | `RenderedPageMatchesItsCspTest` renders `/`, reads the nonce off the response's own header, and fails on any executable inline `<script>` that does not carry it. Data blocks (`type="application/json"`, which is how Inertia ships the page payload) are correctly not counted |
| 5 | `getAllPermissions()` cost three queries on every request and Inertia ships the result on every navigation. Spatie's permission cache does not cover it: that caches the role→permission relation, while the per-user union is rebuilt from the model the session guard re-resolves each request — so in a client-side app the cost was paid on every page load, for a list that rarely changes | Confirmed | `SharedPermissionList` caches per user and per guard; the middleware reads through it. Invalidation is attached to the role/permission mutators themselves rather than to call sites, so a role assigned from a controller nobody updated still invalidates | `SharedPermissionListIsCachedTest` — 6 cases: the three queries named on a cold cache, zero on a warm one for both the service and the rendered payload, plus role change / grant / revoke / no cross-user leak. Written red at 4 of 6 |
| 6 | The list shared to the client is a privilege statement; whether it leaked anything else was never checked | Confirmed, no change needed | `safeSchool()` is a fixed column allowlist and the user payload is id/name/email/roles/permissions. The cache is keyed by id *and* guard, because one id can hold different roles under different guards | `SharedPermissionListIsCachedTest` — two roles are never served the same list |

**Two things worth remembering about the permission cache.** Invalidation had to
be *event-driven* rather than time-based: a TTL cache is wrong in both
directions, and the wrong one is a security problem — a permission revoked would
keep being offered to the browser until the entry expired, and one granted would
be missing for the same window. The TTL is a backstop, not the mechanism.

The second is a bug this phase introduced and then had to find. The invalidation
guarded on `app()->bound(...)` so seeders and console commands, which boot
without the HTTP bindings, could not fail — and because the service was never
registered, that guard made *every* invalidation a silent no-op. The cache looked
like it worked (queries fell to zero) while being impossible to invalidate. A
defensive guard around a lookup that is supposed to succeed turns a loud error
into a silent one; the binding is now registered explicitly, and the guard is
kept only for the console path that genuinely needs it.

**`style-src` keeps `'unsafe-inline'`, deliberately.** The UI sets element styles
at runtime, so removing it needs a nonce threaded through every style binding or
a refactor of the frontend. It is a real gap and is recorded here rather than
quietly treated as hardened. It is also materially weaker than the script case:
a style injection cannot read the CSRF token, call the API, or exfiltrate
anything, whereas inline script can do all three. Removing it belongs with a
frontend pass, not a header edit.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 7 | `Finance\InvoiceController::index` answered `->get()` and mapped every row in the school. The ledger is the largest table in the product, so this returns the school's entire invoice history to the browser and holds all of it in memory to build the response | Confirmed | `->paginate(15)->withQueryString()`, mapping one page. `withQueryString()` so a filtered ledger's page links keep their filter — without it, page two of a filtered list is page two of the whole list under the same filter bar | `InvoiceListIsPaginatedTest` — 8 cases: a 60-row table yields at most 15 rows, a total is reported, page two is disjoint from page one, the total counts only this school, the `status` and `search` filters still bind the later page *and* the total, a page past the end is empty rather than a 500, and no page leaks another school's invoice. Written red at 8 of 8 || 8 | The same map cast `total_amount` and `balance_due` to `float`. The columns are decimals, and a float cannot hold most decimal fractions exactly — `(float) '1150.07'` is not `1150.07` — so the browser was handed figures that are not the figures in the database | Confirmed | Both are cast to `string`, at the column's own scale. `formatCurrency` accepts `number \| string` and parses at the moment it renders, so the value is in binary floating point only for the `Intl` call that prints it | `InvoiceTest::test_the_list_carries_money_as_a_decimal_string` — the payload value must be a string and must equal the stored column. Deliberately asserted against the column rather than a literal: the contract is that the stored decimal is handed through untouched, not that this code picks a scale |

**Why the pager had to arrive with a UI, not just a `paginate()`.** The shared
`DataTable` accepts a paginator payload and renders its rows, and nothing else —
it has no pager. Switching the controller alone would have been a silent
truncation: the accountant would see fifteen invoices and no way to reach the
sixteenth, and nothing on the screen would say the list was cut off. The pager
is now on the page, carrying the filters, with a count of what is being shown.
The same gap exists on the students list, which is already paginated on the
server and already shows no pager; it is recorded here rather than fixed in this
commit, which is about the ledger.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 9 | The missing pager was not two screens, it was the shared component. `DataTable` accepted a paginator, rendered its `data`, and had no pager — so of the ~45 lists the controllers paginate at 15, every screen that had not hand-written its own pager showed fifteen rows of a longer list with nothing to indicate the rest existed | Confirmed | The pager moved into `DataTable`, beside the code that decides what one page is. It renders only for a paginator payload: a plain array gets no pager, one page gets no pager, an empty result gets no count. Page requests are built from the current URL's own query string, so a filter survives paging without every call site having to forward it | `data-table.test.tsx` — 8 cases: a paginator offers its later pages, the total is reported, an array gets no pager, a single page gets no pager, an empty result gets neither pager nor count, an existing filter survives paging, page one is the *absence* of `?page=` rather than `?page=1`, and the current page is marked `aria-current="page"`. Written red at 8 of 8 |
| 10 | Six screens declared their list prop as `{ data: T[] }` — the shape they needed rather than the shape they were sent. It type-checked, so the under-declaration was invisible, and it is the same misreading that hid finding 9 | Confirmed | `Paginator<T>` is exported from `DataTable` and those six props now declare it. The screens that typed their list as a bare array and were handed a paginator are not caught by this, and are not claimed to be | `tsc --noEmit` — the six were the type errors this change surfaced |

**Why the pager belongs in the component and not on each screen.** Forty-odd call
sites had the same defect and the fix that scales is one component, not forty
edits each with its own idea of what a page link looks like. Paging reads the
current query string back out of the URL instead of taking filter props: that is
the only version of this that cannot be forgotten at a call site, and it is the
mistake a hand-written pager makes — page two of the *unfiltered* list, under a
filter bar that still shows the old filter.

This is a behaviour change on every screen that passes a paginator, and it is the
one commit here that touches many pages at once. It was verified by rendering the
component rather than by reading forty screens: 8 component cases, the full
frontend suite, `tsc`, lint, and a production build.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 11 | The gateway was charged `(int) ($amount * 100)` while settlement expected `(int) round($amount * 100)`. `8.20 * 100` is `819.9999999999999` in binary floating point, so an 8.20 payment was charged **819 halalas**; the gateway confirmed 819; settlement expected 820, called it a mismatch and answered 422. The customer's money is taken and the invoice stays open | Confirmed | One conversion, `Finance\Support\Money::toMinorUnits()`, used by `createPayment`, by `refund` (which had the same truncation), and by the settlement's mismatch check. Rounded, so the nearest halala is sent | `GatewayChargesWhatSettlementExpectsTest` — 6 cases over three amounts that truncate (8.20, 33.30, 0.29): the minor units sent equal the minor units settlement expects, and a payment the gateway confirms at exactly the figure we charged it settles rather than being refused. Written red at 6 of 6 |

**Why this was invisible for so long, and why the test is shaped the way it is.**
The existing settlement tests fake a gateway that *rounds* — independently, and
correctly, because they are testing the settlement side. Both sides therefore
agreed, and neither agreed with what was actually sent. Asserting each side
against its own literal would have kept that property forever.

So the test does not assert the arithmetic twice. It charges a payment through
the real `MoyasarGateway`, then has the fake gateway confirm the figure that was
actually transmitted, and asserts the payment settles. That is the invariant a
customer experiences, and it fails whenever either side drifts.

The scale of it: **137 of the first 2000 two-decimal amounts** truncate to the
wrong number of halalas — about 7% of ordinary money, not an edge case. The
three in the test are a sample of real ones from that set.

A related float hazard is *not* fixed here and is not claimed to be:
`amountFromMinorUnits()` divides by 100 and rounds to 4 places, which happens to
match the column's scale and is therefore left alone. A column with more scale
than that would need the same treatment, and the reverse conversion belongs
beside `toMinorUnits()` when someone next touches it.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 12 | Materials, documents and submissions validated the upload as `'file' => 'file\|max:10240'` and stored it with `store(..., 'public')`. `file` asserts that a file arrived and nothing about what it is, so `shell.php` passed it exactly as readily as `lesson.docx`; the `public` disk is `storage/app/public`, which `storage:link` exposes **inside the document root** as `/storage/materials/<name>.php`. Any account holding `manage-materials`, `manage-documents` or `manage-submissions` could place a file where the web server will execute it | Confirmed — **the most serious finding in this phase** | Two independent changes, because either alone leaves a way in. (1) `Validation\AllowedAttachment` — an extension **and** MIME allowlist of office formats and images, which also refuses `report.pdf.php` and a `.docx` that is really PHP. (2) The private `local` disk. Applied to all four upload endpoints, including the anonymous `POST /apply/documents` | `UploadsAreTypedAndKeptOffTheWebRootTest` — 11 cases: `.php`, `.html` and `report.pdf.php` refused on each endpoint; nothing written to the public disk; the file is still written somewhere; and a real PDF/DOCX is still accepted, so the allowlist is not a rule that refuses everything. Written red at 8 of 11 |
| 13 | The audit log was not evidence. `ip_address` and `user_agent` are columns in `audit_logs` that **no write site in the application ever populated**, so every row the system had ever written had them null. Worse, the gateway settlement path wrote `user_id => auth()->id()` — and that path is an unauthenticated webhook, so the single most audit-worthy event in a school, money arriving, was recorded with no actor, no origin, and no reference to the gateway transaction that caused it. The screen rendered a tidy table and answered none of the questions it existed to answer | Confirmed | `markPaid` now records the request's address and user agent, and the gateway charge it is reacting to (`gateway` and `gateway_transaction_id`) is threaded through from both gateway call sites. Nulls are dropped from `new_values` rather than stored, so a hand-confirmed payment stays distinguishable from a gateway one instead of both showing `gateway: null`. A `user_id` is still left null on the webhook path — fabricating an actor would be worse than recording none — so the address is what makes an automated settlement traceable at all | `SettlementIsAuditableTest` — 5 cases: the gateway settlement names the transaction and the gateway, a gateway settlement records where it came from while naming no user, a manual settlement names the user, the address and the client, and a manual one is not recorded as if a gateway had settled it. Written red at 4 of 5 |

**Why both halves, and why the file name being random was not a defence.** The
stored name is a Laravel random hash, which is obscurity rather than a control:
the uploader is shown its own `file_path`, and any list screen that renders one
hands that path to whoever is reading it. A type rule alone would leave the next
undiscovered extension (`.phtml`, `.svg`, `.html`) as a way in; a private disk
alone would leave the allowlist as the only thing between an upload and a request.

The type rule also closes a hole nobody was looking at: `.html` and `.svg` are not
executable but are *documents*, so a `.html` file on the application's own origin
is a phishing page that satisfies every same-origin check a browser makes. SVG is
excluded for the same reason — it can carry script.

**Existing uploads on the public disk had to be moved too.** The change above
closes the hole for new uploads and leaves it open for everything already
uploaded, which on the day it ships is the majority of a school's documents. The
delete paths in all three controllers remove from *both* disks, so a pre-existing
file is cleaned up the next time it is replaced or deleted — but that is a
per-file, per-lifecycle fix and it reaches nothing that is simply never touched.
`php artisan uploads:relocate` does the sweep.

The design point that makes it safe to run against production: **the stored path
does not change.** A row says `documents/abc.pdf` and the controller chooses the
disk, so relocating the file to the same relative path on the private disk leaves
every row valid, every screen working, and every link already emailed to
somebody still resolving to the same record. There is no data migration and
nothing to roll back but the file move itself. The sweep streams each file rather
than reading it into memory, and it reads rows with `withoutSchoolScope()` and
`withTrashed()` — a console command has no tenant context and the tenant scope's
answer to that is "no rows", so a sweep that quietly saw zero documents would
report success and leave every school's files exposed; a soft-deleted row's file
is still in the document root and still executable. Rows are read
`chunkById(200)`, so a school with a large archive does not load it all at once.
When a file exists on both disks the private copy is kept and the public copy
deleted, because a file replaced after the disk change means the private copy is
the newer one. A file that cannot be read is reported as an error and left in
place rather than being counted as relocated, since it is still exposed.

Run it once with `--dry-run` to see the counts first. Evidence:
`PublicUploadsAreRelocatedTest` — 9 cases: the file lands on the private disk
and leaves the public one; the stored path is unchanged; documents, materials and
submissions are all swept; a dry run moves nothing; an existing private copy is
not overwritten but the stale public one is removed; a soft-deleted upload is
still moved; and a row pointing at a missing file is left alone. One of them
asserts the tenant scope hides the row from ordinary queries, so the
`withoutSchoolScope()` call is doing necessary work rather than being decorative.

**Why the audit log was treated as a finding rather than a feature request.**
The screen, the seeded `view-audit-logs` permission and the nav link all present
the log as complete, so its silence reads as "nothing happened" — the exact
inference that makes an audit trail worth having, and the one it was
manufacturing. An auditor asking who marked an invoice paid would have been told
the truth and the wrong thing at the same time: an entry existed, and it was
empty of everything that identifies a source.

Two things this deliberately did **not** do. It did not make the webhook name a
user, because there is no user and an invented one is a lie an auditor would
rely on. And it did not widen the audit log to cover the rest of the application
— the log records two action types in total (`AdmissionsController`,
`PaymentSettlementService`), where the product has hundreds of state-changing
actions, so most of what a school would want to audit is not recorded at all.
That is a larger piece of work with a real design question in it (what to record,
and what to keep, given `old_values`/`new_values` can hold personal data), and it
is recorded here rather than started. The provenance gap is the part that made
the existing entries worthless, and that is now closed.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 14 | The assessment import is an upload surface the upload work never reached. It has no web-root problem — the file is parsed and discarded, never stored — which is presumably why it was never looked at. `extractText()` calls `ZipArchive::getFromName('word/document.xml')`, which inflates a whole zip member into memory. `max:8192` bounds the *compressed* upload, and a zip entry's decompressed size is unrelated to the size of the file carrying it: repetitive text compresses about a thousandfold, so a .docx of a few kilobytes that passes the upload limit can demand hundreds of megabytes. `loadXML()` then holds that string and a DOM tree built from it at once. One crafted file, from anyone who can reach the import screen | Confirmed | The member's **declared** size is read from the archive's own directory and refused above 4 MB, *before* the member is read — measuring after reading is the expensive part. A member that cannot be described is refused rather than read, matching the existing "no readable body" path. `loadXML()` runs with no entity flags, so this is a resource-exhaustion fix and is not an XXE fix | `DocxImportIsBoundedTest` — 5 cases: a document that expands past the ceiling is refused, the refusal explains itself (it is rendered back to the teacher on the import screen), a normal question paper is still read, a zip with no document body is still refused clearly, and a non-zip file is refused. The first case asserts the crafted archive is itself under the 8 KB upload limit, so it demonstrates the gap rather than merely tripping the existing one |

**Why the cap is checked against the declared size rather than the file's real
length.** The only way to learn a member's true length is to inflate it, which is
precisely the allocation being refused; the zip's central directory carries the
size up front, so it can be consulted for free. The number is a ceiling rather than
a working limit — four megabytes of document XML is a thousand questions' worth of
text several times over.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 15 | Documents, materials and submissions were moved off the public disk by finding 12, but their writes read `->store('documents')` — with **no disk argument**. `store()` then falls back to `config('filesystems.default')`, which is `FILESYSTEM_DISK`. The sibling admissions upload names `store(..., 'local')` and was safe by construction; these three were safe only because `.env` happened to say `local`. `FILESYSTEM_DISK=public` is an ordinary, reasonable-looking setting to set for asset serving, and the moment it is set, every user upload in the product is back inside the document root with no code change at all | Confirmed | All three name `'local'` at the call site, so the guarantee is in the code rather than in the environment. Two scans keep it that way: no controller writes a user upload without naming a disk, and the public disk holds exactly three writes — the appearance screen's logo and favicon, and the school settings screen's logo | `UserUploadsDoNotDependOnTheDefaultDiskTest` — 5 cases. The three behavioural ones set `filesystems.default` to `public` deliberately and assert the upload is absent from the web root *and* present on the private disk, so an endpoint that simply refused everything could not pass them. The scan for public-disk writes is written as an exact list, so a fourth entry fails by name rather than by count; it earned its keep by finding the second logo endpoint, which had not been enumerated |

**Why a value in `.env` is not a security control.** Finding 12's allowlist still
refuses a `.php`, so the two are not independent and this is not a second live
hole — it is the disk guarantee resting on an environment setting a deployer can
change for an unrelated reason, with no code review in the path. The two branding
images that legitimately stay on the public disk are restricted to raster types by
their own validation, and are named individually in the test so neither is
accidental.

**One structural observation the scan surfaced, recorded rather than fixed.** The
logo is writable from two endpoints — the appearance screen and the school settings
screen — which store to two different directories. That is one setting with two
write paths, which is exactly the shape that let finding 15 exist. Consolidating
them is a UI decision about which screen owns branding, not a security fix, so it
is left here.

### An upload could not be downloaded. Now it can, through an authorized route.

Worth stating plainly because it was a consequence of finding 12: **no route in
the application returned the bytes of an uploaded document, material or
submission.** The only `download` response in the codebase was a generated `.ics`
calendar and a generated timetable PDF — both built on the fly, neither reading a
stored upload. The three show pages declared `file_path` in their prop types and
never rendered it.

The feature was already write-only before the disk change; the public disk only
supplied an *accidental* back door at a predictable `/storage/documents/<hash>.pdf`
that worked for anyone who could derive or observe the path. Finding 12 removed
that, which is the point of the change, and the consequence was that the access
path became gone entirely rather than merely unintended.

**Who may download was not treated as an open question, because the app had
already answered it.** Each of the three models has a policy `view` ability, and
each of those is a staff permission *and* a same-school check
(`manage-documents` / `manage-materials` / `manage-submissions`). A download
authorized by `view` therefore grants exactly the people who could already open
the record's own page — no student, no guardian and no cross-school user gains
anything they did not have. That is a reuse of an existing answer rather than a
new privacy decision, which is why it was built rather than left open.

A narrower option — guardians reading their own child's submission without that
generalising to every submission — remains available and is a genuine product
question. It is not needed for this to be safe.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 16 | Moving uploads to the private disk made the upload feature write-only (see above) | Fixed | `ServesStoredAttachment` plus a `download` action on each of the three controllers and a `download` route each, gated on the same permission the surrounding group already requires and authorized by the record's own `view` policy. All three show pages render a Download button, conditional on the record actually having a file. Two decisions inside the trait: a stored path that does not name a file inside the disk is refused **before** the disk is touched, because this is the one route in the application that opens a file by a path held in a database column — "nobody can put that value there" is a fact about today's callers, not about the column, and the failure mode if it changes is reading any file the web process can reach, `.env` among them. And the download is named after the record's title plus the stored extension, because serving the stored basename hands someone `9f2c1a7b.pdf` for a document they know as "Term one report" | `UploadedFilesAreDownloadedFromPrivateStorageTest` — 8 cases: each of the three downloads returns the real bytes; the filename is the record's, not the hash; a user in another school gets 404 from the tenant binding rather than a 403 that would confirm the document exists; a user in the same school without the permission gets 403; a traversal path is refused; and a submission with no attachment is 404 rather than a 500, since a text-only submission is an ordinary record |

**The traversal guard is defence in depth, and the test says so.** Flysystem's
path normaliser already refuses `../../` — writing such a file during the test
threw `PathTraversalDetected` before the route was ever reached. What the
explicit guard adds is the *status code*: left to that exception, the download
would surface as a 500 on a route whose only job is to hand back a file. The
guard is checked segment by segment rather than by substring, so a filename that
merely contains dots is not refused.

**`RoleRouteMatrixTest` caught a mistake in this change, which is what it is for.**
The material and submission download routes were first registered as bare
`Route::get()` calls beside their resources. `->middleware()` chained onto a
`Route::resource()` applies to that resource's own routes and not to a separate
route registered next to it, so both were ungated — open to every signed-in
member of the school. The matrix named `GET /materials/{material}/download`
before the suite finished. Both now carry the permission explicitly, with a
comment saying why it is repeated rather than inherited.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 18 | The public faculty **list** chose its columns — eight, allowlisted — and the public single-teacher **page** returned the whole row. So a visitor opening one teacher saw twenty fields: everything the list withheld, which is `employee_id`, `hire_date`, `metadata` and `school_id` — an HR record and a join key — plus `created_at`, `updated_at`, `deleted_at`, `avatar_path` and the Arabic `bio_ar` / `qualification_ar` / `specialization_ar` the list never published. `user_id` is the one that matters most in principle: it ties a public profile to a login account | Confirmed | The eight columns are now a single `PUBLIC_COLUMNS` constant used by both methods, so the two cannot drift again. `metadata` is excluded for a second reason: nothing constrains what goes in it, so a column that is safe today is not safe by design | `PublicStaffPagesDoNotLeakInternalColumnsTest` — 5 cases, written red at 4 of 5 against the old controller (the set-equality case showed all twenty field names). Three assert the specific leaked fields are absent. One asserts the profile page's field set is *set-equal* to the list's, minus `subjects` — so a column added to the model later cannot appear here unnoticed, which a list of forbidden keys would not catch. The fifth guards the other direction, because set equality would also pass if the list quietly dropped everything |

**Why this was missed for as long as it was.** `PublicSiteTest` already covered this
page, and it passes: it asserts the page *has* `specialization` and `subjects`, and
never asks what else is in the payload. A test that checks what a page contains is
not a test of what a page may contain — which is the same shape as finding 15,
where the security of a write depended on a setting nobody had looked at.

**`config/database.php` was also changed, and not for the reason above.** PHPStan
reported two errors in a file nobody had touched: a `PHP_VERSION_ID >= 80500`
version check that is always true under `php: ^8.5`, so its fallback branch is
unreachable. The check was also
holding onto `PDO::MYSQL_ATTR_SSL_CA`, which PHP 8.5 deprecates in favour of
`Pdo\Mysql::ATTR_SSL_CA` — the constant the true branch already used. Removing the
check is a simplification rather than a behaviour change, and it retires a
deprecated constant, so no `phpstan.neon` entry was added; the config's own comment
records why.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 17 | A conversation typed `direct` is readable by every teacher in the school. The `conversations` table has no participants at all — only `school_id`, `type` (defaulting to `'direct'`) and `subject` — so `ConversationPolicy::view` has nothing to check beyond the permission and the school, and `manage-messages` is not in the teacher role's exclusion list, so every teacher holds it. Verified rather than inferred: a second teacher, holding the permission, in the same school, with no relationship to the conversation, opened one and received the full body of a message naming a child and describing that child's behaviour | Confirmed — **not fixed, and this is why** | None. The fix is a schema change, not a policy change | Verified with a throwaway test that was then deleted — a test asserting the current broad visibility would be asserting the finding, not the fix, and committing it would make the behaviour look intentional. Not claimed as a test |

**Why this was not fixed here, specifically.** There is no way to make the policy
narrower without first deciding who a conversation's participants *are*, and that
question has no cheap answer: a new `conversation_participants` table is easy, but
the backfill is not. Existing conversations have no recorded participants, and the
only derivable approximation — "everyone who has sent a message" — would silently
lock staff out of conversations they can currently read and cannot be verified as
correct. A migration that guesses is worse than the honest state of having no
participants.

So the choice is a product one, and the options are genuinely different:

- **Add participants** and make `view` a membership check, with an explicit policy
  decision about who may see a conversation they are not in (an administrator, for
  instance, for safeguarding reasons) and what the backfill does.
- **Keep it school-wide and stop calling it `direct`.** The access model may well
  be right for internal staff messaging; what is misleading is the type value and
  the UI, which both promise a private channel the schema does not provide.
- **Keep the type and accept the exposure**, on the record, with the reasoning.

What should not happen is leaving it as it is by default. The word `direct` is
doing work the data model does not support, and a school that assumes its teachers'
messages about individual children are private will be wrong.

**Audited this round and found sound, recorded so they are not re-litigated.** The
guardian portal's `/children/{child}/…` routes call `authorizeChild()`, which
verifies the guardian–student relationship and answers 403, with the child binding
tenant-scoped on top. The student portal takes no student id from the URL at all
and derives the student from `user_id` plus the session school. School switching
requires an active membership, takes the *membership's* school id rather than the
request's, regenerates the session, and is POST-only. School provisioning and the
platform routes are gated on `manage-schools` (super_admin only) and
`can:access-platform` respectively, so the unscoped `School` route binding is
intentional. `SupportAccessController` is a stub returning a static string.

**One upload path was checked and found sound, recorded so it is not re-litigated.**
`SchoolSettingsController::store()` takes a logo on `image|max:2048`, and `image`
looks like it should admit SVG — an SVG can carry script, and the file is served
from the application's own origin. It does not: since Laravel 11 `validateImage()`
lists nine raster types and adds `svg` only for an explicit `image:allow_svg`,
which this rule does not pass. The sibling `AppearanceSettingsController` already
named its four types explicitly. Both logo paths are safe, and no change was made
to either.

Also removed: `StoreMaterialRequest`, `UpdateMaterialRequest`,
`StoreDocumentRequest` and `UpdateDocumentRequest`. They were dead — no
controller, route or test referenced them — and each declared `'file' => 'file|
max:10240'`. Leaving them in place meant that "tidy up the controllers by using
the Form Requests" reintroduced the vulnerability exactly.

The same reasoning retired `resources/js/layouts/app-shell.tsx.backup` and
`resources/js/Pages/dashboard.tsx.backup`: tracked, unreferenced, and 191 and 157
lines behind the live files respectively. A stale copy of a file is not neutral —
it is the version somebody reads when they need to know what the code does.


