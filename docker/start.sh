#!/bin/bash
set -e  # Exit on error

# Configuration
INIT_FLAG="/var/www/html/storage/.INIT_ENV"
NAME=${NAME:-"test"}
EMAIL=${EMAIL:-"test@example.com"}
PASSWORD=${PASSWORD:-"password"}

# Validate APP_KEY (simplified - Laravel will validate the actual key format)
if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY is not set"
  exit 1
fi

# One-time initialization tasks
if [ ! -f "$INIT_FLAG" ]; then
  echo "First run detected - initializing..."

  # Create SQLite database
  touch /var/www/html/storage/database.sqlite

  touch "$INIT_FLAG"
  echo "Initialization complete"
fi

# Create necessary directories (fast operation)
mkdir -p /var/log/supervisor /var/www/html/storage/logs

# Set all permissions in one pass (major performance improvement)
chown -R www-data:www-data /var/www/html/storage /var/log/supervisor
chmod -R 775 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache
chmod 664 /var/www/html/storage/database.sqlite 2>/dev/null || true

# Start services early (so they're ready while Laravel commands run)
echo "Starting services..."
service php8.4-fpm start >/dev/null 2>&1
service nginx start >/dev/null 2>&1
cron >/dev/null 2>&1

# Laravel setup commands (these are the slow parts)
echo "Running Laravel setup..."
php /var/www/html/artisan migrate --force --isolated

# Clear cache before optimize (avoids potential conflicts)
php /var/www/html/artisan optimize:clear >/dev/null 2>&1

# Run optimize and shield:generate in background for faster startup
php /var/www/html/artisan optimize >/dev/null 2>&1 &
OPTIMIZE_PID=$!

php /var/www/html/artisan shield:generate --all --panel=admin --no-interaction >/dev/null 2>&1 &
SHIELD_PID=$!

# Demo mode setup
if [ "$APP_ENV" = "demo" ]; then
  echo "Demo mode enabled - seeding database..."
  php /var/www/html/artisan db:seed --class=DatabaseSeeder --force

  # Setup cron for demo refresh (only if not already set)
  if ! crontab -l 2>/dev/null | grep -q "refresh:demo-database"; then
    (crontab -l 2>/dev/null; echo "0 2 * * * /usr/bin/php /var/www/html/artisan refresh:demo-database >> /var/log/cron.log 2>&1") | crontab -
  fi
fi

# Create user (suppress output if user exists)
php /var/www/html/artisan user:create "$NAME" "$EMAIL" "$PASSWORD" 2>/dev/null || true
php /var/www/html/artisan shield:super-admin --no-interaction --panel=admin >/dev/null 2>&1

# Wait for background jobs to complete
wait $OPTIMIZE_PID 2>/dev/null || true
wait $SHIELD_PID 2>/dev/null || true

echo ""
echo "✅ Coamifee is running!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "User: $NAME"
echo "Email: $EMAIL"
echo "Password: $PASSWORD"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Run supervisord as root (worker process will run as www-data)
exec /usr/bin/supervisord
