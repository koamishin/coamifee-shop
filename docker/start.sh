#!/usr/bin/env sh
set -eu

INIT_FLAG="/var/www/html/storage/.INIT_ENV"

RUN_MIGRATIONS="${RUN_MIGRATIONS:-false}"
RUN_OPTIMIZE="${RUN_OPTIMIZE:-false}"
RUN_SHIELD_GENERATE="${RUN_SHIELD_GENERATE:-false}"
RUN_DB_SEED="${RUN_DB_SEED:-false}"
CREATE_ADMIN_USER="${CREATE_ADMIN_USER:-false}"

DB_WAIT_MAX_SECONDS="${DB_WAIT_MAX_SECONDS:-60}"
DB_WAIT_INTERVAL_SECONDS="${DB_WAIT_INTERVAL_SECONDS:-2}"

FIX_PERMISSIONS="${FIX_PERMISSIONS:-auto}"

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

SHOULD_FIX_PERMISSIONS=false
if [ "$FIX_PERMISSIONS" = "true" ]; then
  SHOULD_FIX_PERMISSIONS=true
elif [ "$FIX_PERMISSIONS" = "auto" ] && [ "$FIRST_RUN" = "true" ]; then
  SHOULD_FIX_PERMISSIONS=true
fi

if [ "$SHOULD_FIX_PERMISSIONS" = "true" ]; then
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

echo "Running Laravel setup..."

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    if [ "$DB_FILE" != ":memory:" ] && [ ! -f "$DB_FILE" ]; then
        echo "Creating SQLite database at $DB_FILE"
        mkdir -p "$(dirname "$DB_FILE")"
        touch "$DB_FILE"
        chown www-data:www-data "$DB_FILE"
    fi
fi

wait_for_database() {
  if [ -z "${DB_CONNECTION:-}" ]; then
    return 0
  fi

  if [ "$DB_CONNECTION" = "sqlite" ]; then
    return 0
  fi

  START_TIME=$(date +%s)

  while :; do
    php -r "
      \$driver = getenv('DB_CONNECTION');
      \$host = getenv('DB_HOST');
      \$port = getenv('DB_PORT') ?: '5432';
      \$database = getenv('DB_DATABASE');
      \$username = getenv('DB_USERNAME');
      \$password = getenv('DB_PASSWORD');

      if (\$driver === 'pgsql') {
        \$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', \$host, \$port, \$database);
        new PDO(\$dsn, \$username, \$password, [PDO::ATTR_TIMEOUT => 2]);
      } elseif (\$driver === 'mysql' || \$driver === 'mariadb') {
        \$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', \$host, \$port, \$database);
        new PDO(\$dsn, \$username, \$password, [PDO::ATTR_TIMEOUT => 2]);
      }
    " >/dev/null 2>&1 && return 0

    NOW=$(date +%s)
    ELAPSED=$((NOW - START_TIME))

    if [ "$ELAPSED" -ge "$DB_WAIT_MAX_SECONDS" ]; then
      echo "ERROR: database not ready after ${DB_WAIT_MAX_SECONDS}s"
      return 1
    fi

    echo "Waiting for database... (${ELAPSED}s/${DB_WAIT_MAX_SECONDS}s)"
    sleep "$DB_WAIT_INTERVAL_SECONDS"
  done
}

if [ "$RUN_MIGRATIONS" = "true" ]; then
  wait_for_database

  php /var/www/html/artisan migrate --force --no-interaction
else
  echo "Skipping migrations (RUN_MIGRATIONS=$RUN_MIGRATIONS)"
fi

OPTIMIZE_PID=""
SHIELD_PID=""

if [ "$FIRST_RUN" = "true" ] && [ "$RUN_OPTIMIZE" = "true" ]; then
  php /var/www/html/artisan optimize:clear >/dev/null 2>&1 || true

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
echo "${APP_NAME:-Coamifee} is running"
if [ "$CREATE_ADMIN_USER" = "true" ]; then
  echo "User: $ADMIN_NAME"
  echo "Email: $ADMIN_EMAIL"
fi

echo "Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
