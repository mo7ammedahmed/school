# syntax=docker/dockerfile:1

# =============================================================================
# Aether School OS - container image
#
# Three stages, wired in dependency order:
#
#   assets  -> node:24-alpine. `npm ci` then `npm run build`. vite.config.js has
#              no `build` key, so laravel-vite-plugin's default outDir
#              (public/build) is what lands here. Nothing else from the JS
#              toolchain reaches the runtime image.
#
#   vendor  -> composer install --no-dev. Produces vendor/ with the optimized
#              classmap, plus the application source.
#
#   runtime -> php:8.4-fpm. Receives the application source and vendor/ from
#              the vendor stage and public/build from the assets stage. Nothing
#              else is installed here: no node, no composer, no dev packages.
#
# PHP is pinned to 8.4, not "8". composer.lock resolves nette/schema
# (php "8.1 - 8.5") and nette/utils (php "8.2 - 8.5"), so the lockfile is only
# installable on PHP <= 8.5. .github/workflows/ci.yml pins 8.4 for the same reason.
# =============================================================================


# -----------------------------------------------------------------------------
# Stage 1 - front-end assets
# -----------------------------------------------------------------------------
FROM node:24-alpine AS assets

WORKDIR /app

# Dependencies first so the npm layer is cached until package-lock.json changes.
COPY package.json package-lock.json ./
RUN npm ci

# Then the source. `COPY . .` rather than a hand-written list: tailwind v4
# discovers sources by scanning the tree, and a path missed here fails at build
# time instead of shipping an unstyled page. .dockerignore keeps the context
# small and, importantly, keeps .env out of the image.
COPY . .

RUN npm run build \
 && test -f public/build/manifest.json


# -----------------------------------------------------------------------------
# Stage 2 - PHP dependencies
# -----------------------------------------------------------------------------
# Floating major tag. Pin to a digest before the first production release; a
# composer release that changed platform behaviour would otherwise land in a
# rebuild with no commit pointing at it.
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# --no-scripts is deliberate, not an optimisation.
#
# composer's post-autoload-dump runs `php artisan package:discover`, which BOOTS
# the application. Inside this stage there is no .env, no APP_KEY and no
# database, and the application config is not on disk yet. A production image is
# built without .env by design - secrets arrive as environment variables at run
# time - so letting a build-time script boot the framework here is a guaranteed
# failure and, worse, a habit that leaks secrets into image layers.
#
# The cost is that bootstrap/cache/packages.php is not generated at build time.
# That is recovered twice over: Laravel's PackageManifest rebuilds the manifest
# lazily on first boot when the file is absent, and docker/php/entrypoint.sh runs
# `package:discover` once, non-fatally, when the file is missing. Both are
# idempotent.
#
# --no-dev keeps phpunit, larastan, rector and pint out of the runtime image.
RUN composer install \
        --no-interaction \
        --prefer-dist \
        --no-progress \
        --no-dev \
        --optimize-autoloader \
        --no-scripts

COPY . .


# -----------------------------------------------------------------------------
# Stage 3 - runtime
# -----------------------------------------------------------------------------
# php:8.4-fpm-debian, not -alpine.
#
# Alpine was rejected deliberately, not by default:
#
#   * intl is the one extension here with data-version sensitivity rather than
#     just an API surface. app/Mail/InvoiceMail.php:112 and
#     app/Domain/Finance/Services/InvoiceDeliveryService.php:255 both render
#     money through Illuminate\Support\Number::currency(), which is
#     NumberFormatter underneath. Currency symbols, digit grouping and the
#     Arabic locale data come from the linked ICU version, and Alpine tracks a
#     noticeably older ICU than Debian. Invoice PDFs rendering identically to the
#     developer's machine is worth more here than a smaller image.
#   * glibc avoids musl-specific surprises in the dompdf dependency tree
#     (dompdf/dompdf, masterminds/html5, sabberworm/php-css-parser). Debian is
#     the boring choice and this image is rebuilt rarely.
#
# If image size later matters more than ICU parity, changing this line to
# php:8.4-fpm-alpine and libicu-dev to icu-dev is the whole edit.
FROM php:8.4-fpm-debian AS runtime

# Build-time only. libxml2 is promoted to manual below because the dom extension
# links against it directly, so apt must not sweep it away as an orphan when the
# -dev packages are removed.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
        # ffmpeg/ffprobe: the live-recording finalize job remuxes MediaMTX's
        # fMP4 into a faststart MP4 (so browsers can seek) and reads durations
        # from uploaded lesson videos. Both are runtime tools, not build ones.
        ffmpeg \
        curl; \
    \
    # pdo_mysql - required by config/database.php:64, which branches on
    #            extension_loaded('pdo_mysql') before setting MYSQL_ATTR_SSL_CA.
    # intl      - Number::currency() in the invoice mail and delivery paths.
    # zip       - ZipArchive in app/Domain/Assessment/Services/DocxQuestionParser.php
    #            (the .docx question import).
    # dom       - DOMDocument in the same parser; also a hard composer platform
    #            requirement of dompdf/dompdf, masterminds/html5 and
    #            tijsverkoyen/css-to-inline-styles, all production dependencies.
    #            Without it `composer install` refuses to run.
    docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip dom; \
    \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual libxml2; \
    apt-get purge -y --auto-remove --no-install-recommends \
        libicu-dev libzip-dev libxml2-dev; \
    rm -rf /var/lib/apt/lists/*

# opcache ships as a shared module in the official images, so it is enabled
# rather than compiled. Settings live in docker/php/99-app.ini.
RUN docker-php-ext-enable opcache

COPY docker/php/99-app.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# Prove the image is usable at build time. If purging the -dev packages had
# pulled a runtime library out from under dom, zip or intl, this step fails the
# build instead of producing an image that 500s on the first request needing it.
#
# The list is not aspirational. Every entry is either a real call site in app/ or
# a hard platform requirement of a production dependency in composer.lock:
#
#   pdo_mysql   config/database.php:64
#   pdo_sqlite  phpunit.xml sets DB_CONNECTION=sqlite; also the local fallback
#   mbstring    9 call sites, e.g. DocxQuestionParser.php
#   zip         ZipArchive, DocxQuestionParser.php:56,289,291
#   dom         DOMDocument, DocxQuestionParser.php:329; required by dompdf
#   intl        NumberFormatter, InvoiceMail.php:112
#   fileinfo    Flysystem mimeType(), TimetableController.php:546
#   ctype       ctype_digit, TotpService.php:64
#   filter      10 filter_var call sites
#   openssl     random_bytes, bcrypt, APP_KEY, encrypted cookies
#   curl        Guzzle via Http:: - MoyasarGateway, SmsSender, AiTranslator
#   tokenizer   required by laravel/framework
#   session     required by laravel/framework
#   json        required by laravel/framework
#   iconv       NOT a call site, but symfony/polyfill-mbstring and
#               sabberworm/php-css-parser both require ext-iconv, so
#               `composer install` fails without it.
#
# Deliberately NOT installed, because nothing needs them:
#   gd, bcmath, exif, simplexml, xmlwriter
# ext-xmlwriter is required only by phar-io/manifest, phpunit and
# php-code-coverage - all dev-only, so --no-dev never asks for it.
# app/Domain/Localization/Services/ArabicShaper.php shapes Arabic presentation
# forms in pure PHP precisely to avoid intl's transliterator. Do not add gd or
# xmlwriter "to be safe"; do not "fix" ArabicShaper.
RUN set -eux; \
    php -r ' \
        $want = ["pdo_mysql","pdo_sqlite","mbstring","zip","dom","intl", \
                 "fileinfo","ctype","filter","openssl","curl","tokenizer", \
                 "session","json","iconv","opcache"]; \
        $missing = array_values(array_filter($want, fn ($e) => ! extension_loaded($e))); \
        if ($missing !== []) { \
            fwrite(STDERR, "missing PHP extensions: " . implode(", ", $missing) . PHP_EOL); \
            exit(1); \
        } \
    '

# The application writes to storage/ and bootstrap/cache/ at run time (compiled
# views, sessions, logs, uploaded files, the package manifest). Owning them to
# the runtime user now means the image works with no volume mounted at all; the
# entrypoint repeats the chown whenever it happens to run as root.
RUN set -eux; \
    mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        storage/app/private \
        storage/app/public \
        storage/app/tmp \
        bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Run unprivileged.
#
# php-fpm's master and its pool workers both run as www-data (uid 33). Port 9000
# is above 1024, so no capability is needed to bind it. `--nodaemonize` (below)
# matters here twice over: php-fpm must stay PID 1 so Docker's SIGTERM reaches
# it, and the pid file is only written when php-fpm daemonizes, which is what
# would otherwise fail on a root-owned path.
#
# If this ever has to be reverted - a future base image writing its pid file
# unconditionally, say - deleting this line is the whole change. The image's
# default is a root master with www-data pool workers, so the code handling
# untrusted input still drops privileges either way.
USER www-data

EXPOSE 9000

# A dependency-free liveness probe.
#
# `cgi-fcgi -bind -connect 127.0.0.1:9000` is the usual FastCGI ping but it is
# not in the image, and installing fcgi purely for a health check is not worth
# the extra package. This asserts the two things that actually break an app
# container: the autoloader and the artisan binary are present and readable. It
# is a weak check - it cannot see a broken framework - and the honest place to
# assert that the app answers requests is nginx, not the PHP container.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r 'exit(is_readable("/var/www/html/vendor/autoload.php") && is_readable("/var/www/html/artisan") ? 0 : 1);'

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm", "--nodaemonize"]
