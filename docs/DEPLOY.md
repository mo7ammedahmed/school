# Aether School OS — Deploy

## Prerequisites

- **PHP** \^8.4\ per \composer.json\; the real floor is \>=8.4.1\ (16 Symfony packages require it).
- **Composer** 2.x.
- **Node** 24 (LTS).
- **Docker + Compose v2** for the container path.

## Local development with the compose stack

\\ash
cp .env.production.example .env.docker
make up
\
**Honest trade-offs:**

- \.dockerignore\ excludes \	ests/\ so the test suite is not in the image.
- The image is built with \composer install --no-dev\; \endor/bin/phpunit\ does **not** exist in the runtime container.
- The \Makefile\ target \	est\ runs \endor/bin/phpunit\ **on the host** (not in the container).
- The \	est-container\ target is a documented placeholder that prints an error and exits 1 — it will fail until a test stage is added to the Dockerfile or a \docker-compose.test.yml\ override is implemented.

## First deploy

\\ash
make build
# copy .env.production.example to .env and fill in ALL placeholders
make key-generate   # generates APP_KEY — NEVER commit it
make migrate        # php artisan migrate --force
make storage-link   # php artisan storage:link --relative
make optimize       # config:cache, route:cache, view:cache, event:cache
\
**Route cache safety:** outes/web.php\ contains one closure route at line 444:

\\php
Route::get('/general', fn () => redirect()->route('settings.school'))->name('general');
\
A closure makes oute:cache\ throw. **oute:cache\ is NOT safe** until that closure is moved to a controller method.

**PHPStan memory rule (Decision 8):** The analyser **must** run with \-d memory_limit=1G\ (as \composer run analyse\ and \make analyse\ do). With the default 128M limit it crashes and reports “incomplete”, which reads like a clean run.

## Worker restart

\\ash
make queue-restart   # php artisan queue:restart
\
**Reality check:** There are **ZERO** classes in \pp/\ implementing \ShouldQueue\ and **ZERO** \Schedule::\ definitions anywhere in the project. The \queue\ and \scheduler\ containers have nothing to do today.

## Rollback

\\ash
# deploy previous release
make migrate   # only for reversible migrations
\
**Zero-downtime expand/contract rule:** Never drop a column in the same migration that adds its replacement. Add the new column, backfill, switch reads, then drop in a later migration.

**The empty \down()\ in \database/migrations/2026_09_28_120000_allow_either_language_in_bilingual_tables.php\:**

\\php
public function down(): void
{
    //
}
\
The comment explains: “Deliberately not reversed: records saved in one language only have NULL in the other column by design, so restoring NOT NULL could not succeed without deleting or mangling exactly the data this migration exists to allow.” **This migration cannot be rolled back.**

## Backups and restore

**There is NO backup tooling in this repo.** Say so.

Operator-level guidance:
- Database: \mysqldump\ (or your managed DB's snapshot/export).
- Private files: \storage/app/private\ (the \local\ disk root in \config/filesystems.php\).
- RPO and RTO are **decisions the operator must make**, not facts this repo can provide.

## Webhook setup

Each school configures its own gateway under **Settings → Payments**. That
screen shows the delivery URL and stores the school's `webhook_secret`
(encrypted at rest):

```
https://<app-host>/webhooks/payments/moyasar
```

- Only `moyasar` is accepted today. HyperPay and Stripe have no gateway client
  in this codebase, so their deliveries are answered **404** rather than
  trusted. Adding one means implementing its verifier **and** a client that can
  re-fetch the payment — a signature nobody can confirm against the provider is
a check in name only.
- Every delivery is verified against that school's secret (`secret_token` in
  the body, compared in constant time) before anything is written. A school
  with no secret cannot settle: the delivery is refused with 401.
- Settlement never trusts the payload. The gateway is asked for the real
  status, and the amount and currency must match the local payment (422
  otherwise). Responses: 200 settled/replayed/ignored, 401 unverifiable, 422
  inconsistent, 500 retryable — the provider retries 5xx, which is exactly why
  a permanent mismatch answers 422 instead.
- The path is CSRF-exempt (a gateway cannot hold a token) and rate limited at
  60 requests per minute.

## Configuration notes

- **\REDIS_CLIENT\ trap:** A pre-existing \.env\ saying \REDIS_CLIENT=phpredis\ overrides the corrected default (\predis\) and fails, because this project installs \predis/predis\ and **not** \ext-redis\. Change the \.env\ value to \predis\.
- **\MAIL_EHLO_DOMAIN\** falls back to the \APP_URL\ host (parsed by \config/mail.php\). **\APP_URL\ must be browser-reachable** for this fallback to work.

## Known gaps

1. **No queued jobs, no scheduled tasks.** The \queue\ and \scheduler\ containers run but have nothing to process.
2. **No backup tooling.** See Backups and restore above.
3. **No health endpoint beyond Laravel’s \/up\.** Confirmed: \ootstrap/app.php\ has \health: '/up'\.
4. **No S3 private disk configured.** \config/filesystems.php\ defines exactly three disks: \local\, \public\, \s3\. No \private\ disk exists (Decision 5 requires private storage for student documents; only \s3\ is configured).
5. **The route table is checked as a table.** `RouteIntegrityTest` fails the
   build when a route names a controller method that does not exist, or when an
   earlier route answers a named route's URI first. It found 27 and 3 of those
   respectively; the 26 endpoints with no screen behind them were retired and
   the verification notice gained the method it named (see `SECURITY_FIXES.md`,
   Phase 5). Two known gaps remain in that area: the verification *link* route
   (`verification.verify`) is not registered because nothing sends a
   verification mail yet, and the onboarding wizard is a walkthrough with no
   write path — provisioning is `POST /schools`.
6. **\docs/architecture.md\ claims domains \Analytics\ and \Integrations\ that do not exist under \pp/Domain\ and omits \Localization\ which does.**
7. **CI has never run on GitHub Actions from this machine.**
