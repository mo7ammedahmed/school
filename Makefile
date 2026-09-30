# =============================================================================
# Aether School OS - Makefile
#
# Mirrors composer/package.json scripts and provides docker compose conveniences.
# All targets are phony; default target is `help`.
#
# UNTESTED: Docker is not installed on this machine; these targets have not been
# executed. Verify before relying on them in CI/CD.
# =============================================================================

# -----------------------------------------------------------------------------
# Configuration
# -----------------------------------------------------------------------------
COMPOSE_FILE ?= docker-compose.yml
COMPOSE = docker compose -f $(COMPOSE_FILE)

# -----------------------------------------------------------------------------
# Help
# -----------------------------------------------------------------------------
.PHONY: help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# -----------------------------------------------------------------------------
# Docker Compose Lifecycle
# -----------------------------------------------------------------------------
.PHONY: up
up: ## Start the stack in detached mode
	$(COMPOSE) up -d

.PHONY: down
down: ## Stop and remove containers (keeps volumes)
	$(COMPOSE) down

.PHONY: down-v
down-v: ## Stop and remove containers AND volumes (DESTRUCTIVE)
	$(COMPOSE) down -v

.PHONY: build
build: ## Build or rebuild all images
	$(COMPOSE) build

.PHONY: restart
restart: ## Restart all services
	$(COMPOSE) restart

.PHONY: logs
logs: ## Tail logs for all services (Ctrl-C to exit)
	$(COMPOSE) logs -f

.PHONY: logs-app
logs-app: ## Tail logs for the app service only
	$(COMPOSE) logs -f app

.PHONY: shell
shell: ## Open a shell in the app container
	$(COMPOSE) exec app sh

.PHONY: shell-root
shell-root: ## Open a root shell in the app container
	$(COMPOSE) exec -u root app sh

# -----------------------------------------------------------------------------
# Testing
# -----------------------------------------------------------------------------
# HONEST TRADE-OFF:
# The runtime image is built with `composer install --no-dev` and `.dockerignore`
# excludes `tests/`. Therefore `vendor/bin/phpunit` does NOT exist in the runtime
# image, and the test suite CANNOT run inside the container as written.
#
# Options considered:
#   a) Run tests on the host (current CI does this via .github/workflows/ci.yml)
#   b) Add a `test` stage to Dockerfile with dev deps and tests/ copied in
#   c) Compose a docker-compose.test.yml override
#
# This Makefile chooses (a): the `test` target runs phpunit on the HOST.
# The `test-container` target is provided as a placeholder that documents the
# gap -- it will fail until option (b) or (c) is implemented.
.PHONY: test
test: ## Run PHPUnit test suite on the HOST (not in container)
	vendor/bin/phpunit

.PHONY: test-container
test-container: ## ATTEMPT to run tests inside container -- WILL FAIL (see comment above)
	@echo "ERROR: The runtime image has no phpunit (--no-dev) and no tests/ (excluded by .dockerignore)."
	@echo "Either run 'make test' on the host, or implement a test stage in Dockerfile."
	@exit 1

.PHONY: test-coverage
test-coverage: ## Run PHPUnit with coverage on the HOST
	vendor/bin/phpunit --coverage-html coverage

# -----------------------------------------------------------------------------
# Static Analysis & Linting
# -----------------------------------------------------------------------------
.PHONY: analyse
analyse: ## Run PHPStan with 1G memory limit (required -- see Decision 8)
	php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress

.PHONY: phpstan
phpstan: analyse ## Alias for analyse

.PHONY: lint
lint: ## Run frontend linter (oxlint)
	npm run lint

.PHONY: typecheck
typecheck: ## Run TypeScript type checker (tsc --noEmit)
	npm run typecheck

.PHONY: format
format: ## Run rector + pint (composer format)
	composer run format

# -----------------------------------------------------------------------------
# Database
# -----------------------------------------------------------------------------
.PHONY: migrate
migrate: ## Run migrations (non-destructive)
	$(COMPOSE) exec -T app php artisan migrate --force

.PHONY: migrate-fresh
migrate-fresh: ## DROP ALL TABLES and re-run migrations (DESTRUCTIVE -- guarded)
	@echo "WARNING: This will DROP ALL TABLES in the database."
	@echo "Type 'yes' to confirm:"
	@read -r confirm && [ "$$confirm" = "yes" ] || (echo "Aborted."; exit 1)
	$(COMPOSE) exec -T app php artisan migrate:fresh --force

.PHONY: seed
seed: ## Run database seeders
	$(COMPOSE) exec -T app php artisan db:seed --force

.PHONY: fresh
fresh: ## DESTRUCTIVE: migrate:fresh --seed (guarded)
	@echo "WARNING: This will DROP ALL TABLES and re-seed."
	@echo "Type 'yes' to confirm:"
	@read -r confirm && [ "$$confirm" = "yes" ] || (echo "Aborted."; exit 1)
	$(COMPOSE) exec -T app php artisan migrate:fresh --seed --force

# -----------------------------------------------------------------------------
# Laravel Optimization (production)
# -----------------------------------------------------------------------------
.PHONY: optimize
optimize: ## Run all optimization commands (config:cache, route:cache, view:cache, event:cache)
	$(COMPOSE) exec -T app php artisan config:cache
	$(COMPOSE) exec -T app php artisan route:cache
	$(COMPOSE) exec -T app php artisan view:cache
	$(COMPOSE) exec -T app php artisan event:cache

.PHONY: optimize-clear
optimize-clear: ## Clear all cached optimization artifacts
	$(COMPOSE) exec -T app php artisan config:clear
	$(COMPOSE) exec -T app php artisan route:clear
	$(COMPOSE) exec -T app php artisan view:clear
	$(COMPOSE) exec -T app php artisan event:clear

# -----------------------------------------------------------------------------
# Queue & Scheduler
# -----------------------------------------------------------------------------
.PHONY: queue-restart
queue-restart: ## Signal queue workers to restart (after deploy)
	$(COMPOSE) exec -T app php artisan queue:restart

.PHONY: queue-work
queue-work: ## Run queue worker in foreground (for debugging)
	$(COMPOSE) exec app php artisan queue:work --sleep=1 --tries=3 --timeout=90

.PHONY: schedule-run
schedule-run: ## Run the scheduler once (for debugging)
	$(COMPOSE) exec app php artisan schedule:run

# -----------------------------------------------------------------------------
# Storage & Assets
# -----------------------------------------------------------------------------
.PHONY: storage-link
storage-link: ## Create public/storage symlink
	$(COMPOSE) exec -T app php artisan storage:link --relative

.PHONY: assets-build
assets-build: ## Build frontend assets (vite build)
	npm run build

# -----------------------------------------------------------------------------
# Mailpit
# -----------------------------------------------------------------------------
.PHONY: mail
mail: ## Open Mailpit UI in browser (prints URL)
	@echo "Mailpit UI: http://localhost:8025"

.PHONY: mail-smtp
mail-smtp: ## Show Mailpit SMTP connection info
	@echo "SMTP Host: localhost (or mailpit inside compose)"
	@echo "SMTP Port: 1025"
	@echo "No authentication required"

# -----------------------------------------------------------------------------
# Key Generation
# -----------------------------------------------------------------------------
.PHONY: key-generate
key-generate: ## Generate APP_KEY and print it (run once, store in secret manager)
	$(COMPOSE) run --rm app php artisan key:generate --show

# -----------------------------------------------------------------------------
# Health Check
# -----------------------------------------------------------------------------
.PHONY: health
health: ## Check health endpoints
	@echo "=== nginx (public /) ==="
	@curl -sf http://localhost:8080/ >/dev/null && echo "OK" || echo "FAIL"
	@echo "=== app container (autoload + artisan) ==="
	@$(COMPOSE) exec -T app php -r 'exit(is_readable("/var/www/html/vendor/autoload.php") && is_readable("/var/www/html/artisan") ? 0 : 1);' && echo "OK" || echo "FAIL"
	@echo "=== platform health (requires auth) ==="
	@echo "See /platform/health (protected by can:access-platform)"
