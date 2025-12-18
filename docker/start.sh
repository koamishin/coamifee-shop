#!/usr/bin/env sh
set -eu

INIT_FLAG="/var/www/html/storage/.INIT_ENV"
NAME="${NAME:-test}"
EMAIL="${EMAIL:-test@example.com}"
PASSWORD="${PASSWORD:-password}"

if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set"
  exit 1
fi

mkdir -p /var/www/html/storage/logs /var/www/html/bootstrap/cache

if [ ! -f "$INIT_FLAG" ]; then
  echo "First run detected - initializing..."
  touch "$INIT_FLAG"
  echo "Initialization complete"
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

echo "Running Laravel setup..."

ATTEMPTS=0
until php /var/www/html/artisan migrate --force --isolated; do
  ATTEMPTS=$((ATTEMPTS + 1))
  if [ "$ATTEMPTS" -ge 30 ]; then
    echo "ERROR: database not ready after $ATTEMPTS attempts"
    exit 1
  fi

  echo "Waiting for database... ($ATTEMPTS/30)"
  sleep 2
done

php /var/www/html/artisan optimize:clear >/dev/null 2>&1 || true

php /var/www/html/artisan optimize >/dev/null 2>&1 &
OPTIMIZE_PID=$!

php /var/www/html/artisan shield:generate --all --panel=admin --no-interaction >/dev/null 2>&1 &
SHIELD_PID=$!

if [ "${APP_ENV:-}" = "demo" ]; then
  echo "Demo mode enabled - seeding database..."
  php /var/www/html/artisan db:seed --class=DatabaseSeeder --force
fi

php /var/www/html/artisan user:create "$NAME" "$EMAIL" "$PASSWORD" 2>/dev/null || true
php /var/www/html/artisan shield:super-admin --no-interaction --panel=admin >/dev/null 2>&1

wait "$OPTIMIZE_PID" 2>/dev/null || true
wait "$SHIELD_PID" 2>/dev/null || true

echo ""
echo "Coamifee is running"
echo "User: $NAME"
echo "Email: $EMAIL"

echo "Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
