#!/bin/sh

# Render sets PORT environment variable
# Default to 10000 if not set
PORT=${PORT:-10000}

echo "Starting container with PORT=${PORT}"

# Ensure log directories exist (Railway containers can start with empty /var/log)
mkdir -p /var/log/supervisor
mkdir -p /var/log/nginx

# Update nginx config with dynamic port
sed -i "s/listen 10000;/listen ${PORT};/g" /etc/nginx/http.d/default.conf
sed -i "s/listen 10000/listen ${PORT}/g" /etc/nginx/http.d/default.conf

# Force-clear Laravel caches (guards against stale bootstrap/cache/*.php causing 404s)
rm -f /var/www/html/bootstrap/cache/*.php || true

# Run migrations (Render will handle this, but good to have as fallback)
php artisan migrate --force --no-interaction || true

# Clear and cache config for production
export LOG_CHANNEL=stderr
php artisan optimize:clear || true
php artisan config:cache || true

# Start supervisor (manages nginx and php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

