# Security Fixes

Running record for the security mandate. Each finding says whether it was
confirmed, what changed, which tests pin it, and what is still open. Nothing
here is marked fixed without a test that fails against the old behaviour.

Verified at the end of Phase 2: `phpunit` 519 tests / 3614 assertions, PHPStan
level 5 clean, Pint clean (634 files), `tsc --noEmit`, `oxlint` and Vitest clean.

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
