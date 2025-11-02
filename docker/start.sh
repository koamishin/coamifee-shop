#!/bin/bash

INIT_FLAG="/var/www/html/storage/.INIT_ENV"
NAME=${NAME:-"test"}
EMAIL=${EMAIL:-"test@example.com"}
PASSWORD=${PASSWORD:-"password"}

# Function to check if a string is 32 characters long
check_length() {
  local key=$1
  if [ ${#key} -ne 32 ]; then
    echo "Invalid APP_KEY"
    exit 1
  fi
}

# Check if APP_KEY is set
if [ -z "$APP_KEY" ]; then
  echo "APP_KEY is not set"
  exit 1
fi

# Check if APP_KEY starts with 'base64:'
if [[ $APP_KEY == base64:* ]]; then
  # Remove 'base64:' prefix and decode the base64 string
  decoded_key=$(echo "${APP_KEY:7}" | base64 --decode 2>/dev/null)

  # Check if decoding was successful
  if [ $? -ne 0 ]; then
    echo "Invalid APP_KEY base64 encoding"
    exit 1
  fi

  # Check the length of the decoded key
  check_length "$decoded_key"
else
  # Check the length of the raw APP_KEY
  check_length "$APP_KEY"
fi

# check if the flag file does not exist, indicating a first run
if [ ! -f "$INIT_FLAG" ]; then
  echo "Initializing..."

  # generate SSH keys
  openssl genpkey -algorithm RSA -out /var/www/html/storage/ssh-private.pem
  chmod 600 /var/www/html/storage/ssh-private.pem
  ssh-keygen -y -f /var/www/html/storage/ssh-private.pem >/var/www/html/storage/ssh-public.key

  # create sqlite database
  touch /var/www/html/storage/database.sqlite

  # create the flag file to indicate completion of initialization tasks
  touch "$INIT_FLAG"
fi

# Create supervisor log directory
mkdir -p /var/log/supervisor

chown -R www-data:www-data /var/www/html &&
  chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure www-data can write to supervisor log directory
chown -R www-data:www-data /var/log/supervisor

# Ensure storage directory is writable for supervisord pid file
chmod -R 775 /var/www/html/storage

service php8.4-fpm start

service redis-server start
service nginx start

php /var/www/html/artisan migrate --force
php /var/www/html/artisan optimize:clear
php /var/www/html/artisan optimize
php /var/www/html/artisan shield:generate --all --panel=admin --no-interaction

# Run database seed if in demo mode
if [ "$APP_ENV" = "demo" ]; then
  echo "Running database seed for demo mode..."
  php /var/www/html/artisan db:seed --class=DatabaseSeeder --force
fi

php /var/www/html/artisan user:create "$NAME" "$EMAIL" "$PASSWORD"
php artisan shield:super-admin --no-interaction --panel=admin

# Add daily cron job to refresh demo database if in demo mode
if [ "$APP_ENV" = "demo" ]; then
  echo "Setting up daily demo database refresh cron job..."
  # Add to crontab to run daily at 2 AM
  (crontab -l 2>/dev/null; echo "0 2 * * * /usr/bin/php /var/www/html/artisan refresh:demo-database >> /var/log/cron.log 2>&1") | crontab -
  echo "Demo database will refresh daily at 2 AM"
fi

cron

echo "Coamifee is running! 🚀"
echo "Your account has been created successfully."
echo "User: $NAME"
echo "Email: $EMAIL"
echo "Password: $PASSWORD"

# Run supervisord as root (worker process will run as www-data)
exec /usr/bin/supervisord
