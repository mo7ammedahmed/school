#!/bin/sh
# =============================================================================
# Aether School OS - PHP container entrypoint
#
# Contract: idempotent, safe on every container start, and never migrates.
#
# This runs before every process in the image - php-fpm, `artisan queue:work`,
# `artisan schedule:work`, and any one-off `docker compose run`. Each pass must
# be a no-op once the container is healthy, so every step below is guarded.
#
# LF line endings and no shebang BOM are load-bearing: a CRLF shebang makes the
# kernel fail with "not found" and a CRLF after "#!/bin/sh" breaks `set -e`
# parsing on some shells.
# =============================================================================

set -e

APP_DIR="${APP_DIR:-/var/www/html}"

log() {
    printf '[entrypoint] %s\n' "$*"
}

warn() {
    printf '[entrypoint] WARNING: %s\n' "$*" >&2
}

# -----------------------------------------------------------------------------
# 1. Writable directories
# -----------------------------------------------------------------------------
# storage/app/tmp is the one directory with no tracked .gitignore, so it is not
# in the image at all and must be created here. The rest ship with a .gitignore
# placeholder and would survive even without this, but a missing view or cache
# directory is a confusing first-boot failure, so all of them are ensured.
mkdir -p \
    "$APP_DIR/storage/framework/cache/data" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/framework/testing" \
    "$APP_DIR/storage/logs" \
    "$APP_DIR/storage/app/private" \
    "$APP_DIR/storage/app/public" \
    "$APP_DIR/storage/app/tmp" \
    "$APP_DIR/bootstrap/cache"

# Only meaningful when the container was started as root. The image declares
# USER www-data, so the normal path skips this entirely - the image already
# owns these paths - and this branch exists for the case where an operator
# overrides the user (for example to debug, or on a host bind mount whose
# directory is owned by a different uid).
if [ "$(id -u)" = "0" ]; then
    log 'running as root; taking ownership of storage/ and bootstrap/cache/'
    chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
fi

# -----------------------------------------------------------------------------
# 2. public/storage symlink
# -----------------------------------------------------------------------------
# config/filesystems.php:76-78 declares public/storage -> storage/app/public.
# The `public` disk writes every uploaded document, material, submission,
# school logo and admission scan there, and public disk URLs are served from
# this link. It does not exist in a fresh checkout (it is gitignored), and it
# cannot be created in the image because it is a symlink into a volume-mounted
# directory.
#
# -L first: test -e follows symlinks, so it would report a broken link as
# missing and we would recreate it forever.
#
# --relative, not the default absolute link. With the compose source bind mount
# enabled, public/ is the host's directory; an absolute link would leave a
# dangling /var/www/html/... target on the host. A relative link
# (../storage/app/public) resolves correctly both inside the container and on
# the host.
if [ ! -L "$APP_DIR/public/storage" ] && [ ! -e "$APP_DIR/public/storage" ]; then
    log 'creating public/storage symlink'
    php artisan storage:link --relative --no-interaction || \
        warn 'storage:link failed; public disk URLs will 404 until it is created'
fi

# -----------------------------------------------------------------------------
# 3. Wait for the database
# -----------------------------------------------------------------------------
# compose already gates the app on the mysql service's healthcheck, so this is
# belt and braces: it covers a DB that is healthy but still accepting no
# connections, an externally managed database, and `docker compose run` which
# bypasses depends_on entirely.
#
# Bounded: 60 attempts, 2s apart, so a database that never comes up produces a
# clear "gave up" line and a boot that proceeds - not a container wedged in a
# silent infinite loop. A worker with no database is still a useful process to
# keep alive behind a proxy that is not serving yet.
#
# fsockopen over the TCP port only. It proves MySQL is listening and accepting,
# which is what a cold container actually races on; it deliberately does not
# authenticate, so no credentials end up in a subprocess argument list.
if [ -n "${DB_HOST:-}" ] && [ "${DB_CONNECTION:-}" != "sqlite" ]; then
    DB_PORT="${DB_PORT:-3306}"
    DB_WAIT_MAX="${DB_WAIT_MAX:-60}"
    DB_WAIT_SLEEP="${DB_WAIT_SLEEP:-2}"
    log "waiting for database at ${DB_HOST}:${DB_PORT} (max ${DB_WAIT_MAX} x ${DB_WAIT_SLEEP}s)"

    db_ready=0
    i=0
    while [ "$i" -lt "$DB_WAIT_MAX" ]; do
        if php -r '
            $host = getenv("DB_HOST") ?: "127.0.0.1";
            $port = (int) (getenv("DB_PORT") ?: 3306);
            $sock = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($sock === false) {
                exit(1);
            }
            fclose($sock);
            exit(0);
        '; then
            db_ready=1
            break
        fi
        i=$((i + 1))
        sleep "$DB_WAIT_SLEEP"
    done

    if [ "$db_ready" = "1" ]; then
        log "database at ${DB_HOST}:${DB_PORT} is accepting connections"
    else
        warn "database at ${DB_HOST}:${DB_PORT} did not answer after $((DB_WAIT_MAX * DB_WAIT_SLEEP))s; starting anyway"
    fi
else
    log 'no remote database configured; skipping the database wait'
fi

# -----------------------------------------------------------------------------
# 4. Application key
# -----------------------------------------------------------------------------
# Advisory only, by design.
#
# APP_KEY cannot be auto-generated usefully here: `artisan key:generate` writes
# to a .env file, and this image has no .env - secrets arrive as environment
# variables. Generating one into the filesystem on every boot would also mean
# every session cookie and every encrypted column breaks whenever the container
# is recreated.
#
# Generate it once, deliberately:
#   docker compose run --rm app php artisan key:generate --show
# and put the result in the env file or secret store.
if [ -z "${APP_KEY:-}" ]; then
    warn 'APP_KEY is not set. Every encrypted cookie, every session and every'
    warn 'encrypted model attribute will fail. Run:'
    warn '  docker compose run --rm app php artisan key:generate --show'
fi

# -----------------------------------------------------------------------------
# 5. Package manifest
# -----------------------------------------------------------------------------
# The build stage installs with --no-scripts so composer's package:discover
# cannot boot the framework without an environment. Laravel rebuilds this
# manifest lazily on first request when the file is missing, so this step is
# purely an optimisation - it moves that cost off the first user-visible
# request. Non-fatal on purpose: if it fails the lazy rebuild still happens,
# and a manifest failure should not stop php-fpm from starting.
if [ ! -f "$APP_DIR/bootstrap/cache/packages.php" ]; then
    log 'package manifest missing; discovering packages'
    php artisan package:discover --ansi --no-interaction || \
        warn 'package:discover failed; Laravel will retry lazily on first request'
fi

# -----------------------------------------------------------------------------
# 6. Migrations are deliberately NOT run here
# -----------------------------------------------------------------------------
# A container that migrates on boot is a known production hazard, not a
# convenience. During a rolling deploy several versions of the application are
# alive at once: the new containers boot, discover the pending migration, and
# race each other to apply it while the old containers are still serving
# requests against the previous schema. The outcome is a partial apply, a lock
# contention stall, or - worst - new code reading a column the migration is
# midway through renaming.
#
# Schema changes are therefore an explicit, ordered, human-supervised step that
# happens once, before traffic shifts:
#
#   docker compose run --rm app php artisan migrate --force
#
# --force is required because APP_ENV is not "local". Do not add
# `php artisan migrate` to this file, and do not add it to the queue or
# scheduler command either.

# -----------------------------------------------------------------------------
# 7. Hand over
# -----------------------------------------------------------------------------
# exec, not a shell: the container's PID 1 becomes the real process, so Docker's
# SIGTERM reaches php-fpm for a graceful stop, and `docker compose run` can
# replace CMD with an arbitrary command (that is how migrate, key:generate and
# tinker are invoked above).
exec "$@"
