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

## Inertia SSR

The pages are pre-rendered by a Node process, so a deployment needs three things beyond
the usual PHP ones:

1. **Node 22+ on the machine or worker that runs the SSR server.** Inertia's SSR server
   will not start on an older Node.
2. **Both bundles built.** `npm run build` runs `vite build && vite build --ssr`, then verifies the artifacts: the second
   pass compiles `resources/js/ssr.tsx` into `bootstrap/ssr/ssr.js`. A deployment that only
   runs `vite build` ships no SSR bundle, and Inertia then renders in the browser without
   saying anything — the application looks fine and never server-renders.
   `npm run build:ssr` runs the same complete build, including the browser manifest.
   Building only SSR leaves Blade's `@vite` without `public/build/manifest.json`, which
   causes a 500 even when the SSR service is healthy.
3. **The SSR server running** as its own long-lived process:

   ```bash
   php artisan inertia:start-ssr          # listens on 127.0.0.1:13714
   php artisan inertia:check-ssr          # health check, exits non-zero when unhealthy
   ```

**On Laravel Cloud:** turn on *Use Inertia SSR* in the app cluster's **Advanced** settings
(the cluster needs Node, which the build image has), make sure the build command under
**Settings → Deployments** compiles the frontend — the exact command is in **Laravel Cloud**
below — and let the platform supervise the SSR process. Verify the result from outside:
`curl -s https://<domain>/ | grep -o 'data-server-rendered="true"'` must print the flag.
If it does not, the bundle or the process is missing — not the config.

## The build must produce `public/build`

`public/build` and `bootstrap/ssr` are git-ignored, so they exist only on a machine that has
run `npm run build`. A deploy whose build step stops at `composer install` ships no manifest
and answers **500** on every page, from `resources/views/app.blade.php`:

```
Illuminate\Foundation\ViteManifestNotFoundException:
Vite manifest not found at: /var/www/html/public/build/manifest.json
```

Laravel Cloud runs the build commands you give it and nothing else — there is no default that
adds the frontend step. Two things cover it:

1. **The build command** (the fix): one that compiles the frontend, not only the PHP side.
   **Laravel Cloud** below gives the command this project uses, frontend first so the hook
   below never repeats the npm work.
2. **`post-install-cmd` runs `php artisan assets:ensure`** (the safety net): when the manifest
   is missing it runs `npm ci && npm run build` (plain `npm run build` when `node_modules`
   already exists) and fails the build when the build finishes without a manifest, so the
   failure lands in the deploy log instead of on the first visitor. It is a no-op wherever
   the manifest is present. `php artisan assets:ensure --force` rebuilds on demand from the
   Cloud **Commands** tab.

The hook only helps when the build command runs `composer install`; if that step is missing
from the command, fix the command.

Relevant environment variables (Inertia's own defaults apply when unset):

| Variable | Purpose |
| --- | --- |
| `INERTIA_SSR_ENABLED` | `true` by default; set `false` to render client-side only (the test suite does this in `phpunit.xml`). |
| `INERTIA_SSR_URL` | Where the SSR server listens; `http://127.0.0.1:13714` by default. |
| `INERTIA_SSR_TIMEOUT` | Bound on a slow or hung SSR server. **Set this in production** — Laravel's HTTP client otherwise waits its 30s default on the render request, and every page pays it while the SSR server is unresponsive. 5 is generous: a page renders in tens of milliseconds. |
| `INERTIA_SSR_THROW_ON_ERROR` | Throws instead of silently falling back. Never in production; use it to make a broken SSR build loud during a smoke test. |
| `LOG_SLACK_WEBHOOK_URL` | Set it and every SSR alert also lands in that Slack channel. Unset, the alert is the log file alone. |
| `LOG_SSR_LEVEL` | Minimum level written to `storage/logs/ssr.log`; `warning` by default. |
| `LOG_SSR_DAYS` | How many days of it to keep; 30 by default. |

Two operational notes:

- **The SSR bundle is a build artifact of the frontend.** Any deploy that changes
  `resources/js` must rebuild it, or the server keeps rendering the previous page while the
  browser runs the new client code. `bootstrap/ssr/` is git-ignored for that reason.
- **A failed render is not a failed request.** Inertia falls back to client-side rendering
  and the page still answers 200, so a green deploy is not evidence that SSR worked. Two
  things watch for it instead:
  - `App\Listeners\ReportSsrRenderFailure` listens for Inertia's `SsrRenderFailed` event
    (the SSR server answered badly, or the request to it threw) and writes a `critical`
    line to the `ssr` log channel — `storage/logs/ssr.log`, and Slack too when
    `LOG_SLACK_WEBHOOK_URL` is set. A broken SSR server fails on every request, so one
    alert stands for a five-minute window and counts the failures behind it
    (`suppressed_since_last_alert`) instead of writing a line per page view.
  - `App\Http\Middleware\ReportSsrFallback` reads the HTML that was actually sent. Inertia
    *announces* a failure only when the SSR server answers or throws; a bundle no deploy
    built returns nothing without a word, as does a body that is not JSON. That path leaves
    a page that renders in the browser with a 200, and this middleware is what names it.

  So the check after a deploy is `tail -f storage/logs/ssr.log` plus
  `php artisan inertia:check-ssr`, not the absence of errors in the web log.

## Laravel Cloud

Cloud builds the commands you give it, serves the app through Octane (FrankenPHP), and resets
the environment's filesystem on every deploy — and each replica has its own. Four settings and
one variable carry this project; the rest is the platform working as documented.

| Where | Setting | Value |
| --- | --- | --- |
| Deployments → Build commands | build | `npm ci --include=dev --audit=false && npm run build:ssr && composer install --no-dev && php artisan optimize` |
| Deployments → Deploy commands | deploy | `php artisan migrate --force` |
| App cluster | Scheduler | **on** — Cloud then runs `schedule:run` every minute |
| App cluster → Advanced | Use Inertia SSR | **on**, or the SSR bundle built above is never used |
| Environment variables | | `APP_KEY`, `APP_URL=https://<domain>`, `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=mysql`, `PRIVATE_DISK=s3` |

The `--include=dev` flag installs Vite and the other build tools even if the build
environment sets `NODE_ENV=production`. Both build scripts check the browser manifest,
its referenced files, and `bootstrap/ssr/ssr.js`, and fail when any is missing.

### Fix a missing Vite manifest after enabling SSR

The error `Vite manifest not found at: /var/www/html/public/build/manifest.json`
means the deployed browser assets are missing. Before this fix, this project's
`build:ssr` script compiled only SSR, unlike Laravel's starter kit script that Cloud's
instructions assume. Use the build command above and deploy again after the corrected
`package.json` and `scripts/check-build.mjs` reach the deployment branch. Build assets
in **Build commands**, where Cloud persists them into the deployed image; assets built
in **Deploy commands** are not persisted.

After deployment, run these in Cloud's **Commands** tab:

```bash
test -s public/build/manifest.json && test -s bootstrap/ssr/ssr.js
php artisan inertia:check-ssr
```

Then request the homepage and check that it returns 200 and includes
`data-server-rendered="true"`. Leave `APP_DEBUG=false` on the public environment.

Never add `php artisan storage:link` or `php artisan optimize:clear` to the deploy commands:
the symlink does not survive a deploy, and clearing the cached config right after the build
wrote it is the opposite of the point.

**Why that build order.** `composer install` fires `post-install-cmd`, which this project hooks
to `php artisan assets:ensure`. With the frontend built *first* the hook is a no-op. With
`composer install` first the hook would instead run `npm ci && npm run build` on its own, and
the explicit `npm ci` after it deletes `node_modules` and repeats the install — two full
frontend builds, against a fifteen-minute build limit.

`php artisan optimize` belongs in the build, not the deploy: it writes `bootstrap/cache`, and
only build-command changes to the filesystem are kept for the running deployment. It succeeds
here because no route is a closure (see "Route cache safety" above).

### Files belong on a bucket

`storage/app/private` is not persistent on Cloud, so `PRIVATE_DISK` has to name a disk that is.
Attach a bucket, give it the disk name `s3` — the name `config/filesystems.php` already defines
— and set `PRIVATE_DISK=s3`. Uploads (documents, materials, submissions, admission
attachments, finished recordings) then land in the bucket and every reader follows:

- `/documents|materials|submissions/{id}/download` streams the bytes through the app, as before.
- `/materials/{id}/stream` hands the player a short-lived signed URL: a bucket has no filesystem
  path, and the bucket answers the player's `Range` requests itself, so seeking still works and
  the video never travels through PHP. A disk whose driver cannot sign falls back to a temporary
  local copy, ranges included.

`league/flysystem-aws-s3-v3` is in `composer.json` for this — it is also what Cloud's managed
queues require. The `s3` disk reads `AWS_REGION` / `AWS_ENDPOINT_URL` as well as the
`AWS_DEFAULT_REGION` / `AWS_ENDPOINT` names, because those are the variables Cloud injects for
an attached bucket.

### What the platform cannot carry: the live pipeline

A live lesson needs MediaMTX — a process, a WebRTC port, and a **disk shared with whoever
finalizes the recording** (`config/media.php`, `MEDIA_RECORDINGS_DISK`). Cloud has no shared
volume and resets its disk on every deploy, and the `recordings` disk is scratch space that
MediaMTX writes and `FinalizeLiveSession` reads. Keep that stack on a host you run (the compose
stack in this repository) and let the *published* lesson live in the bucket. A persistent
network filesystem is the other way to give both processes the same scratch disk — `.env.example`
already carries an `ARCHIL_*` block for one, and nothing in this codebase reads it, so mounting
it in the right places is a deployment decision rather than an application setting. Check `ffmpeg` on
whichever host runs the job — `MEDIA_FFMPEG_BINARY` / `MEDIA_FFPROBE_BINARY` point at it, and
without it a multi-segment lesson is published as its last segment, with no duration recorded:

```
php artisan tinker --execute="var_dump(\Illuminate\Support\Facades\Process::run('ffmpeg -version')->successful());"
```

#### The HLS fallback has its own port, and its own failure mode

`MEDIA_WEBRTC_URL` is the path the student player tries first; `MEDIA_HLS_URL` is the one it
switches to when the WebRTC media path never opens. That is a real network, not a hypothetical
one: WHEP carries media over UDP, and a school firewall that allows 443 and nothing else leaves
the handshake answering successfully and the `<video>` element black, with no error to catch.
The fallback asks for nothing the page itself did not already need.

Two deployment facts make it work:

- **Proxy the HLS port behind the same TLS origin as the app** — `8888` next to `8889` in the
  compose file, and in nginx a location (or a name of its own) that forwards to it. An `http://`
  media URL on an `https://` page is mixed content: the browser blocks it before CSP or CORS
  are consulted, and the console error names a different problem than the one being debugged.
- **Set `MEDIA_HLS_URL` to the URL the browser can reach**, not a compose service name. The
  Content-Security-Policy derives `connect-src` / `media-src` from this value
  (`app/Http/Middleware/SecurityHeaders.php`), so an unreachable hostname shows up as a CSP
  violation in the console rather than as a silent black screen. When the media server has an
  origin of its own, its HLS endpoint also has to allow the app's origin — the shipped
  `docker/mediamtx/mediamtx.yml` sets `hlsAllowOrigins: ['*']` because the playlist endpoint is
  read-only and only serves a stream whose key the viewer already has.

Codecs: HLS remuxes whatever was published. Against a live MediaMTX, both H264/AAC and
AV1/Opus (the codec a browser publish falls back to when H264 is refused) produce real
segments and low-latency parts, and hls.js plays them. The one rough edge is the start of a
stream: with `hlsAlwaysRemux: no` the muxer is created when the first HLS viewer arrives, and
the window before it existed is listed as `#EXT-X-GAP`, which hls.js skips on its way to the
live edge. Set `hlsAlwaysRemux: yes` if viewers should never see that first join, and accept a
muxer running for every published stream.

After a deploy, check the endpoint itself — the first request is a redirect carrying
MediaMTX's cookie check, so `-L` is what makes this a 200:

```bash
curl -sL -o /dev/null -w '%{http_code}\n' https://<media-origin>/<stream-key>/index.m3u8
```

To exercise the fallback on purpose, point `MEDIA_WEBRTC_URL` at an unreachable port; the
player switches after its six-second media check.

### After a deploy

```bash
curl -fsS https://<domain>/up                       # health endpoint
curl -s https://<domain>/ | grep -o 'data-server-rendered="true"'
curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/build/manifest.json   # 200, not 500
```

Then sign in and upload a document. Downloading it back after a **re-deploy** is what proves
`PRIVATE_DISK` points at the bucket; uploading and downloading it within the same request
proves nothing, because the disk that request wrote to is still there.

A release that adds interface copy also needs the dictionary topped up. The Arabic strings ship
as data (`database/seeders/data/interface-translations-ar.json`), and the running app reads them
from the database, not the file:

```bash
php artisan db:seed --class=InterfaceTranslationSeeder --force
```

It writes only what is missing, so it is safe on every deploy and never overwrites wording a
school has edited. Without it, new strings render in English on an Arabic page — the student
player's backup-stream label included.

## First deploy

\\ash
make build
# copy .env.production.example to .env and fill in ALL placeholders
make key-generate   # generates APP_KEY — NEVER commit it
make migrate        # php artisan migrate --force
make storage-link   # php artisan storage:link --relative
make optimize       # config:cache, route:cache, view:cache, event:cache
\
**Route cache safety:** `route:cache` refuses to serialise a closure, and this project has no
closure routes left — the last one (`/settings/general`) is a controller method. So
`php artisan optimize` (config, events, routes, views) succeeds, which is exactly what the
Cloud build step runs. Adding a `fn () => ...` route to `routes/web.php` breaks the build
again: put the method on a controller instead.

**PHPStan memory rule (Decision 8):** The analyser **must** run with \-d memory_limit=1G\ (as \composer run analyse\ and \make analyse\ do). With the default 128M limit it crashes and reports “incomplete”, which reads like a clean run.

## Worker restart

\\ash
make queue-restart   # php artisan queue:restart
\
**Reality check:** exactly one class implements `ShouldQueue`
(`App\Jobs\FinalizeLiveSession`) and exactly two tasks are scheduled —
`live-sessions:finalize` every five minutes and `live-sessions:prune` hourly, both in
`routes/console.php`. A deployment that runs no queue worker leaves ended lessons stuck at
`processing`; one that runs no scheduler leaves recordings unpublished and the scratch disk
unpruned. On Laravel Cloud both are dashboard toggles (see Laravel Cloud below).

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
- Private files: the disk `PRIVATE_DISK` names — `storage/app/private` while it
  stays `local` (the `local` disk root in `config/filesystems.php`), or the bucket it names
  otherwise.
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

Live media now requires application authorization rather than anonymous stream-key access.
Deploy the application and restart MediaMTX with the updated HTTP-auth configuration;
outside Compose, point `MTX_AUTHHTTPADDRESS` at the reachable app `/media/authorize` endpoint.
Configure `MEDIA_HLS_INTERNAL_URL` and private `MEDIA_API_USER` / `MEDIA_API_PASSWORD`
for authenticated HLS fallback and control-API reconciliation. Empty API credentials deny
access. See [ACCESS-AUDIT.md](ACCESS-AUDIT.md) for rollout checks and verification limits.

1. **One queued job, two scheduled tasks.** `FinalizeLiveSession` (recording post-processing)
is the only `ShouldQueue` class, and `live-sessions:finalize` (every five minutes) plus
`live-sessions:prune` (hourly) are the only `Schedule::` entries (`routes/console.php`). All
three need something to run them: the compose stack's `queue` and `scheduler` containers, or
Cloud's managed queue and the App-cluster Scheduler toggle. A deployment that runs neither
leaves recordings stuck at `processing` and the scratch disk unpruned. Both tasks are marked
`onOneServer`, so a multi-replica deployment needs a cache store they all share — the default
`database` store is one.
2. **No backup tooling.** See Backups and restore above.
3. **No health endpoint beyond Laravel’s \/up\.** Confirmed: \ootstrap/app.php\ has \health: '/up'\.
4. **Private storage is a disk a deployment chooses.** `config/filesystems.php` has a
`private` key (`PRIVATE_DISK`, `local` by default) and four disks: `local`, `recordings`,
`public`, `s3`. Docs, materials, submissions, admission attachments and finished recordings all
go through that one name. On a platform whose filesystem is ephemeral — Laravel Cloud — leaving
`PRIVATE_DISK` unset writes those files to a disk that is reset on the next deploy, and they are
gone. The `s3` driver needs `league/flysystem-aws-s3-v3`, which `composer.json` now requires.
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
