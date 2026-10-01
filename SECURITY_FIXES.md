# Security Fixes

Running record for the security mandate. Each finding says whether it was
confirmed, what changed, which tests pin it, and what is still open. Nothing
here is marked fixed without a test that fails against the old behaviour.

Verified at the end of Phase 3: `phpunit` 538 tests / 3727 assertions, PHPStan
level 5 clean, Pint clean (634 files), `tsc --noEmit`, `oxlint` and Vitest clean.
At the Phase 4 checkpoint below: `phpunit` 555 tests / 3774 assertions, PHPStan
level 5 clean, Pint clean (643 files); the frontend gates are untouched by this
phase.

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

- **Four dead routes** name controller methods that do not exist, so any role
  that passes the gate gets a 500: `subjects.offerings`, `my-grades`,
  `finance.my-fees`, `finance.fee-assignments.index`. They are named with
  reasons in `RoleRouteMatrixTest::EXCLUDED_GATES` and in `docs/DEPLOY.md`, so
  they cannot be forgotten — but each one still needs deciding: implement the
  screen or retire the route.
- **Controller-level `authorize()` is thin** (four controllers, twelve call
  sites). Route groups are the enforcement layer now, and every guarded model
  has a mapped policy; the follow-up is to consult policies in show/edit and
  mutating actions so the check lives next to the action it protects.

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

## Phase 4 — Tenant isolation (in progress)

Verified by `tests/Feature/Security/TenantIsolationTest.php`, a cross-tenant
matrix that asserts the *required* behaviour and started fully red, and by
`tests/Feature/Security/TenantValidationRuleScopeTest.php`, which proves the
validation mechanism and scans the source for object rules that bypass it.
Findings 1–6 are fixed; 7 is named work still to do.

| # | Finding | Status | Change | Tests |
|---|---------|--------|--------|-------|
| 1 | Implicit route-model binding resolved ids straight off the primary key, so `show`, `edit`, `update` and `destroy` had nothing to check: enrolments, admission applications, discounts, students, sections and academic years could be opened, edited and deleted by a foreign school | Confirmed | `BelongsToSchool` adds the active tenant to `resolveRouteBindingQuery`; foreign ids answer 404 (not 403, which would confirm the row exists elsewhere) (`2af16b0`) | `TenantIsolationTest` list/show/edit/update/delete cases; the two older 403 assertions were updated to 404 |
| 2 | `EnrollmentController` listed every school's enrolments, offered every school's students/sections/years in the form, and accepted foreign ids in `exists:` rules — a school could enrol its pupil into a stranger's section | Confirmed | `forSchool()` on list and form queries; `Rule::exists(...)->where('school_id', …)` on both rule sets; `school_id` stamped from the tenant context (`2af16b0`) | `TenantIsolationTest` (list, form, two foreign-FK cases) |
| 3 | The admissions review queue asked platform-wide questions with one school's id: the reviewer picker listed every school's staff, `assign` accepted any user on the platform, and `bulkUpdate` validated and updated foreign application ids — writing status and event rows onto a stranger's application | Confirmed | Queue resolves the id through `schoolId()`; reviewer candidates must hold an active membership in this school; application ids are scoped by rule and the service takes the school id, filtering both of its queries (`1b6be3c`) | `TenantIsolationTest` (bulk write refused whole-request, foreign reviewer refused, reviewer picker) |
| 4 | `DiscountController` listed and offered platform-wide rows and stamped `school_id` from the raw session | Confirmed | `forSchool()` on list and pickers; stamp from the tenant context (`1b6be3c`) | `TenantIsolationTest` (list, open, edit, delete) |
| 5 | 63 models carry `school_id`; only 13 carried `BelongsToSchool`, so the rest could be queried across schools by an omission | Fixed | Rollout complete: `TenantContext`, fail-closed `TenantScope`, auto-stamp and `withoutSchoolScope()` are live on every school-owned model (`2af16b0`, `1f058bc`, `85b99ca`, `1a436b6`, `597c4c9`, `b9e9725`). Three deliberate exclusions, each with its reason recorded: `UserMembership` (the resolver cannot depend on its own answer), `AuditLog` (platform and support actions too), `WebsiteThemePreset` (no `school_id` by design) | The whole suite runs against the live scope; the rollout exposed and fixed real unscoped services: the timetable conflict detector, the settings stores used outside requests, invoice delivery, and settlement |
| 6 | 181 `exists:` rules across controllers, form requests and DTOs are unscoped, so a foreign id validates | Fixed | `TenantAwareValidator` is resolved by the validation factory and narrows every string `exists:`/`unique:` rule naming a table with a `school_id` column to the active tenant, fail-closed with no context (`7d3d6a9`). `Rule::exists()`/`Rule::unique()` objects bypass the validator, so the guard test scans the source and fails when one of them names a tenant table without its own filter, with a reasoned exemption list for deliberate crossings | `TenantValidationRuleScopeTest` — four runtime cases (foreign row refused, other school's unique value free, platform table untouched, no context accepts nothing) plus the source scan; the whole suite passes unchanged with the resolver live |
| 7 | Ownership checks answer 403 (`ensureOwned` and hand-rolled copies) while binding now 404s, so the two halves of the app disagree about what "not yours" means | Open, no longer reachable | Every model those checks guarded is now tenant-bound, so a foreign id 404s at the route before the check runs; the remaining call sites are dead code to delete, not a live disagreement | The isolation matrix and the suite exercise the binding path |

**Open from Phase 4**

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
