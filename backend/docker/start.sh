#!/bin/sh

# Render sets PORT environment variable
# Default to 10000 if not set
PORT=${PORT:-10000}

# Update nginx config with dynamic port
sed -i "s/listen 10000/listen $PORT/g" /etc/nginx/http.d/default.conf

# Run migrations (Render will handle this, but good to have as fallback)
php artisan migrate --force --no-interaction || true

# Clear and cache config for production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start supervisor (manages nginx and php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

