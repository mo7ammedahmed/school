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

Still open in this phase: `Finance\InvoiceController::index` still loads every
row with `->get()`, the SchoolResolver fallback has not been re-checked against
the domain/slug requirement, and the audit-log, upload and money findings are not
yet started.

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


