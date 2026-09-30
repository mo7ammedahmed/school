# Aether School OS — Decisions

## Decision 1: Domain-Oriented Architecture
- **Status:** Accepted
- **Rationale:** Domain boundaries prevent accidental coupling, make authorization explicit, and keep business rules testable.
- **Alternatives considered:** Flat services, hexagonal architecture.
- **Consequences:** More directories, but clearer ownership.

## Decision 2: Inertia.js as Primary CRUD Interface
- **Status:** Accepted
- **Rationale:** Reduces API surface, keeps authorization server-side, simplifies form handling.
- **Alternatives considered:** REST API + separate frontend, Livewire.
- **Consequences:** No separate mobile API yet; future API will be added via Sanctum.

## Decision 3: Spatie Permission for RBAC
- **Status:** Accepted
- **Rationale:** Mature, well-tested, integrates cleanly with Laravel policies.
- **Alternatives considered:** Custom permission system, Laravel Gates only.
- **Consequences:** Permission storage is in dedicated tables; role checks are explicit.

## Decision 4: SQLite for Local/CI, MySQL for Production
- **Status:** Accepted
- **Rationale:** Zero-config local development, production-grade MySQL 8+.
- **Alternatives considered:** SQLite only, PostgreSQL.
- **Consequences:** Must avoid SQLite-specific syntax in migrations.

## Decision 5: Private/S3-Compatible Storage
- **Status:** Accepted
- **Rationale:** Required for secure document storage, GDPR/PDPL compliance.
- **Alternatives considered:** Local filesystem only, public CDN.
- **Consequences:** All file operations go through storage abstraction.

## Decision 6: Bilingual Arabic/English with RTL
- **Status:** Accepted
- **Rationale:** Al Noor School serves Arabic and English speakers; RTL is first-class.
- **Alternatives considered:** English-only, RTL as afterthought.
- **Consequences:** All UI components must support RTL; logical CSS properties required.

## Decision 7: One owner for tenant scope per surface
- **Status:** Accepted
- **Context:** The public site resolved its school inside each controller. Four of
  them forgot, so `/news`, `/events` and `/` listed every school's rows and
  `/news/{post}` opened another school's article by id.
- **Decision:** `SchoolResolver` answers "which school is this request about?"
  and nothing else. The signed-in app asks the base controller
  (`schoolId()` / `ensureOwned()`); public pages extend `PublicController` and
  filter with `BelongsToSchool::forSchool()`. A null id scopes to nothing
  (fail closed) rather than to everything.
- **Consequences:** A query missing `forSchool()` reads as unfinished at the call
  site. Public pages cannot fall back to an unscoped query by accident.

## Decision 8: Static analysis is a gate, and suppressions are itemised
- **Status:** Accepted
- **Context:** PHPStan reported 243 errors at level 5, and the config excluded
  `app/Http/Controllers/Public/*` — the one area whose bugs had already shipped.
- **Decision:** Level 5 covers the whole application with no path exclusions.
  Concrete-model errors are never suppressed: a wrong property on `School`,
  `Subject` or an invoice must fail the build, which is how the missing
  `internal_notes`/`priority`/`assigned_to` columns were found. The remaining
  `ignoreErrors` entries are Larastan imprecisions — each with the counterexample
  that justifies it — and the analyser needs `-d memory_limit=1G` on this
  codebase (`composer run analyse` sets it).
- **Consequences:** A new suppression means a written reason. PHPStan must not be
  run with its default 128M limit: it crashes and reports "incomplete", which
  reads like a clean run.

## Decision 9: Model contracts are declared, not inferred
- **Status:** Accepted
- **Context:** 131 of the original PHPStan errors were undefined properties.
  Larastan cannot see appended accessors (`School::$name`,
  `AttendanceSession::$subject`) or the `_ar` columns the translation layer adds
  at runtime, so it typed them as the base `Model` — the same blind spot that let
  a page print the literal word "name".
- **Decision:** Every accessor-backed attribute and relation a screen reads is
  declared with `@property`, `@property-read` or a relation generic
  (`@return HasMany<X, $this>`) on the model that owns it.
- **Consequences:** The docblock must sit above the attributes, not between them
  and the class, or PHPStan never reads it.

## Decision 10: oxlint for the frontend, not ESLint
- **Status:** Accepted
- **Context:** `resources/js` (223 page components) had no linter at all.
  Installing the usual setup fails: `typescript-eslint@8` declares a peer of
  `typescript >=4.8.4 <6.1.0` and this project runs TypeScript 7.0.2.
- **Decision:** Use oxlint, which parses TypeScript itself and has no TypeScript
  peer dependency. The gate enables the `correctness` category only and fails on
  warnings (`oxlint --deny-warnings resources/js`).
- **Consequences:** It found three real defects on first run — a `className` prop
  that was destructured and never merged into the class list, an unused ternary
  expression standing in for an `if`, and a triple-slash reference the tsconfig
  already covers. Style and `unicorn` suggestions stay off: they recommend
  `Array#toSorted()`, which the ES2021 lib target does not have, so following
  them would break `tsc`.

## Decision 11: The public application journey persists on submit
- **Status:** Accepted
- **Context:** Five steps of answers lived in one flat session array and were
  discarded: the review screen showed a hard-coded applicant, "Submit" was a
  link, uploads were read for their filename and thrown away, and the guardian
  and student steps shared `first_name`/`last_name`/`address`, so the student's
  answers overwrote the guardian's.
- **Decision:** Each step is stored under its own key (`guardian`, `student`,
  `previous_school`, `documents`); `POST /apply/submit` creates the application,
  records a `submitted` event, stores the uploads on the private disk under the
  school, and shows the family a reference. Columns the table was missing were
  added by migration rather than left uncollected.
- **Consequences:** A finished application is visible to the school in its review
  queue; a visitor who skips a step is sent back rather than half-saved.

## Decision 12: Vitest for component tests, with tests beside their pages
- **Status:** Accepted
- **Context:** 223 page components had no test runner, so a UI defect could only
  be found by a human clicking. One had been sitting in the review screen: its
  decision form was a native POST with the submit buttons in the page header and
  a checked `decision` radio inside the form, so the same field name was sent
  twice and PHP keeps the last value — "Reject" submitted `approved`.
- **Decision:** Add Vitest with `happy-dom` and `@testing-library/react` in a
  config separate from `vite.config.js`, with tests named `*.test.tsx` next to
  the page they cover. Pages mount through `renderPage`, which supplies the same
  locale and theme providers `app.tsx` supplies, and Inertia is mocked at the
  `@inertiajs/react` boundary so tests assert what a request actually carries.
- **Consequences:** `*.test.tsx` must be excluded from the Inertia page glob in
  `app.tsx`; otherwise the test file is treated as a page, is bundled for
  production, and pulls the test libraries in with it (447 kB before the fix).

## Decision 13: The shells own failure reporting
- **Status:** Accepted
- **Context:** `FormFeedback` existed but only twelve settings screens used it,
  while 73 pages post native forms and many more post with `useForm`. A rejected
  save therefore looked identical to one that saved nothing: six
  `with('error')` paths — an already-confirmed payment, an incomplete public
  application, a contact message the mail server refused, online payment
  unavailable, the school's own contact address unset, deleting the school you
  are working in — reached no screen at all.
- **Decision:** `AppShell` and `PublicLayout` each render `FormFeedback` once.
  Failure (validation errors and `flash.error`) is reported by the shell; success
  stays with the page, next to the button that saved. A page that wants its own
  success line passes `showErrors={false}`.
- **Consequences:** The shared `flash` payload is down to the two severities
  anything actually flashes (`success`, `error`); `warning` and `info` had no
  producer and no reader and were removed rather than left as an extension point.

## Decision 14: Invoice status is not a form field
- **Status:** Accepted
- **Context:** The create form offered a status dropdown that `store()`
  discarded by hard-coding `draft`, and the edit form offered one that `update()`
  honoured — so an edit could mark an invoice `issued` (or `paid`, or `void`)
  without issuing it, delivering it, or recording a payment. Worse, the edit
  form's list omitted `partially_paid`, and that status is editable, so the
  browser fell back to the first option and saving rewrote a partially paid
  invoice as a draft.
- **Decision:** Status is owned by the workflow: `draft` on create, `issued`
  through the issue action (which delivers to the guardian), then moved on by
  payments. Neither form offers it and `update()` ignores it. `InvoiceCreated`
  was removed with the same reasoning: nothing listened to it, while
  `InvoiceIssued` is the event that carries the delivery.
- **Consequences:** `partially_paid` gained the label it never had, and the list
  and detail screens read the status through one shared helper instead of
  printing the raw column value — which also removed the same five-branch
  variant function that was copied into both screens.

## Decision 15: Cells are data, but a known label in one is still chrome
- **Status:** Accepted
- **Context:** `translateInterfaceCopy` skipped `td` outright — "a table cell is
  data, and data belongs to the school" — and the dashboard prints most of its
  machine values there (23 call sites across 21 files render `status`,
  `priority`, `payment_method` and `event_type` with the underscores taken out).
  So the one thing an Arabic screen always showed in English was the status
  column.
- **Decision:** Visit cells, translate only what the dictionary already knows
  (`value.<stored_value>` pairs, matched on the normalised text so `Bank
  Transfer`, `bank transfer` and `bank_transfer` are the same value), and never
  send a cell to the translation provider. `td` had to join the selector for
  this to reach the common case: most cells hold their text directly rather than
  in a `span`.
- **Consequences:** A school's own data — names, numbers, anything it typed — is
  untouchable, and no provider call is spent guessing at it. The cost is that
  chrome inside a cell which is *not* in the dictionary stays English instead of
  being  machine-translated; the vocabulary covers values, not sentences, so a
  phrase like "Not sent" still needs a dictionary entry to appear in Arabic.

## Decision 16: A delivery is authenticated per school, and settled from the gateway's own answer
- **Status:** Accepted
- **Context:** `/webhooks/payments/{gateway}` is reachable by anyone. It used to
  accept any POST, write `GatewayTransaction.status = completed`, and settle the
  invoice the payload named — so a forged body settled money, and a real
  delivery would have been a 419 anyway because the path was not CSRF-exempt.
  The payload was also read in a flat shape Moyasar does not send, which is why
  the missing check was invisible: the documented envelope puts the payment
  under `data`, and only that shape carries `secret_token`.
- **Decision:** Three rules. (1) A delivery is verified before it can write
  anything, using the *school's own* encrypted `webhook_secret` compared in
  constant time; a school with no secret refuses deliveries. (2) The gateway is
  the only authority on whether money arrived: the payment is re-fetched, and
  the amount and currency must match the local record before anything settles.
  (3) A gateway with no registered `WebhookVerifier` is answered 404 — the
  registry will not invent a shared secret for a provider that does not send
  one. Settlement is one transaction that locks the payment and its invoice;
  `failed` deliveries may be retried, `completed` ones are replayed.
- **Consequences:** A forged or replayed delivery cannot move money, and a
  school must configure its secret before online payments settle — fail-closed
  rather than silently trusting. The cost is that adding a provider takes two
  pieces (a verifier and a client that can re-fetch), and that the webhook
  response is now the protocol signal for retries: 4xx means "do not retry",
  5xx means "try again".

## Decision 17: A password starts a challenge, not a session
- **Status:** Accepted
- **Context:** Two-factor authentication was decoration. The login controller
  never looked at `two_factor_enabled`, and the challenge controller — sitting
  in the guest group — read `$request->user()`, so it answered only a visitor
  who was already signed in; the flag it set was read by nothing. None of the
  authentication routes were rate limited, recovery codes were plaintext
  compared with `in_array`, and a password change left every other session
  alive because the session middleware was never enabled.
- **Decision:** A correct password parks the identity in the session
  (`auth.two_factor_user_id` plus the remember-me choice) and logs the guard
  out; only a valid TOTP code or a usable recovery code calls `Auth::login`.
  All four surfaces — sign-in, forgot-password, reset-password, challenge —
  allow five attempts a minute, keyed by the identity tried plus the caller's
  address, and the counter is cleared by success. Recovery codes are SHA-256
  hashes compared with `hash_equals`, with a legacy plaintext fallback that is
  re-hashed on first use. `authenticateSessions()` is enabled, so a password
  change ends the other sessions; switching school and toggling two-factor
  authentication regenerate the session id.
- **Consequences:** The session is only created after both factors, and the
  limiter makes the second factor's small space expensive. Two costs are worth
  naming: the challenge redirect confirms to an attacker that a password was
  correct (inherent to a two-step flow), and recovery codes exist but are never
  displayed yet, so the follow-up is to show the set once at generation.

## Decision 18: A school is a tenant, not a role team
- **Status:** Accepted
- **Context:** `config/permission.php` has `teams => false`, so Spatie roles and
  permissions are platform rows: there is one `registrar` role, and every
  school's registrar holds it. Tenancy lives in `UserMembership` — user, school,
  role name, `is_active`, last login — and `EnsureSchoolContext` proves an active
  membership before a request is allowed in. That split was being read as an
  oversight: the admissions review picker asked "who has the reviewer
  permission?" and offered every school's staff, and `assign` accepted any user
  id on the platform. The two candidate fixes were to turn Spatie teams on
  (roles become per-school rows) or to keep roles global and make membership the
  tenant link. A third question sits behind them: how tenant scope itself is
  enforced on model queries.
- **Decision:** Roles stay global; membership is the only answer to "who belongs
  to this school", and every query about a school's people joins memberships.
  Tenant scope is `TenantContext` plus a fail-closed global scope: a model that
  uses `BelongsToSchool` is filtered to the active school on every query, a null
  context matches nothing rather than everything, writes with no school and no
  context throw, an update can never move a row between schools, and
  `withoutSchoolScope()` is the named escape hatch for the few places that mean
  to cross schools. The context is pinned by `EnsureSchoolContext` after
  membership is proven, by `ApplySiteMetadata` for the public site (a visitor
  has no session), and by `TenantContext::runFor()` around code that was handed
  a school — actions, services, jobs.
- **Consequences:** The permission catalogue stays one editable vocabulary, the
  role matrix test keeps covering all eight roles, and "who may do this here?"
  composes as permission **and** active membership **and** model scope — the
  reviewer picker is the pattern. The costs are honest: per-school custom roles
  are not possible without a later mechanism, a feature that forgets membership
  still passes its permission check (which is exactly the bug the reviewer
  picker had), and the rollout of the global scope is deliberately incremental —
  each model that joins the trait can expose a query that was relying on seeing
  every school's rows, and that exposure is the audit.
