#!/usr/bin/env sh
set -eu

INIT_FLAG="/var/www/html/storage/.INIT_ENV"

RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
RUN_OPTIMIZE="${RUN_OPTIMIZE:-false}"
RUN_SHIELD_GENERATE="${RUN_SHIELD_GENERATE:-false}"
RUN_DB_SEED="${RUN_DB_SEED:-false}"
CREATE_ADMIN_USER="${CREATE_ADMIN_USER:-false}"

MIGRATE_ISOLATED="${MIGRATE_ISOLATED:-auto}"
MIGRATION_CACHE_STORE="${MIGRATION_CACHE_STORE:-}"

ADMIN_NAME="${ADMIN_NAME:-${NAME:-}}"
ADMIN_EMAIL="${ADMIN_EMAIL:-${EMAIL:-}}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-${PASSWORD:-}}"

if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set"
  exit 1
fi

mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache

FIRST_RUN=false
if [ ! -f "$INIT_FLAG" ]; then
  FIRST_RUN=true
  echo "First run detected - initializing..."
  touch "$INIT_FLAG"
  echo "Initialization complete"
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "Running Laravel setup..."

if [ "$RUN_MIGRATIONS" = "true" ]; then
  CACHE_STORE_CURRENT="${CACHE_STORE:-${CACHE_DRIVER:-}}"
  USE_ISOLATED=false

  if [ "$MIGRATE_ISOLATED" = "true" ]; then
    USE_ISOLATED=true
  elif [ "$MIGRATE_ISOLATED" = "false" ]; then
    USE_ISOLATED=false
  else
    if [ -z "$CACHE_STORE_CURRENT" ] || [ "$CACHE_STORE_CURRENT" = "database" ]; then
      USE_ISOLATED=false
    else
      USE_ISOLATED=true
    fi
  fi

  MIGRATE_ARGS="migrate --force"
  if [ "$USE_ISOLATED" = "true" ]; then
    MIGRATE_ARGS="$MIGRATE_ARGS --isolated"
  fi

  ATTEMPTS=0
  until (if [ -n "$MIGRATION_CACHE_STORE" ]; then CACHE_STORE="$MIGRATION_CACHE_STORE" php /var/www/html/artisan $MIGRATE_ARGS; else php /var/www/html/artisan $MIGRATE_ARGS; fi); do
    ATTEMPTS=$((ATTEMPTS + 1))
    if [ "$ATTEMPTS" -ge 30 ]; then
      echo "ERROR: database not ready after $ATTEMPTS attempts"
      exit 1
    fi

    echo "Waiting for database... ($ATTEMPTS/30)"
    sleep 2
  done
else
  echo "Skipping migrations (RUN_MIGRATIONS=$RUN_MIGRATIONS)"
fi

php /var/www/html/artisan optimize:clear >/dev/null 2>&1 || true

OPTIMIZE_PID=""
SHIELD_PID=""

if [ "$FIRST_RUN" = "true" ] && [ "$RUN_OPTIMIZE" = "true" ]; then
  php /var/www/html/artisan optimize >/dev/null 2>&1 &
  OPTIMIZE_PID=$!
fi

if [ "$FIRST_RUN" = "true" ] && [ "$RUN_SHIELD_GENERATE" = "true" ]; then
  php /var/www/html/artisan shield:generate --all --panel=admin --no-interaction >/dev/null 2>&1 &
  SHIELD_PID=$!
fi

if [ "$FIRST_RUN" = "true" ] && { [ "${APP_ENV:-}" = "demo" ] || [ "$RUN_DB_SEED" = "true" ]; }; then
  echo "Seeding database..."
  php /var/www/html/artisan db:seed --class=DatabaseSeeder --force
fi

if [ "$FIRST_RUN" = "true" ] && [ "$CREATE_ADMIN_USER" = "true" ]; then
  if [ -z "$ADMIN_NAME" ] || [ -z "$ADMIN_EMAIL" ] || [ -z "$ADMIN_PASSWORD" ]; then
    echo "ERROR: CREATE_ADMIN_USER=true requires ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD"
    exit 1
  fi

  php /var/www/html/artisan user:create "$ADMIN_NAME" "$ADMIN_EMAIL" "$ADMIN_PASSWORD"
  php /var/www/html/artisan shield:super-admin --no-interaction --panel=admin >/dev/null 2>&1 || true
fi

if [ -n "${OPTIMIZE_PID:-}" ]; then
  wait "$OPTIMIZE_PID" 2>/dev/null || true
fi

if [ -n "${SHIELD_PID:-}" ]; then
  wait "$SHIELD_PID" 2>/dev/null || true
fi

echo ""
echo "Coamifee is running"
if [ "$CREATE_ADMIN_USER" = "true" ]; then
  echo "User: $ADMIN_NAME"
  echo "Email: $ADMIN_EMAIL"
fi

echo "Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
