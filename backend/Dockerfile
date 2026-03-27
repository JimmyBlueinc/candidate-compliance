# Render Dockerfile for Laravel + Vue
# Multi-stage build: Frontend (Node/Vite) -> Backend (PHP/Laravel)

# ============================================
# Stage 1: Build Frontend Assets with Vite
# ============================================
FROM public.ecr.aws/docker/library/node:20-alpine AS frontend

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install all dependencies (including dev for build tools)
RUN npm ci

# Copy source files
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/
COPY scripts/ ./scripts/

# Build frontend assets
RUN npm run build

# ============================================
# Stage 2: PHP Backend
# ============================================
FROM public.ecr.aws/docker/library/php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    oniguruma-dev \
    libxml2-dev \
    poppler-utils

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Composer
COPY --from=public.ecr.aws/docker/library/composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application files
COPY . .

# Copy migrations to a non-mounted path (ECS volume mounts /var/www/html/database)
RUN rm -rf /var/www/html/migrations && cp -R /var/www/html/database/migrations /var/www/html/migrations

# Copy built frontend assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Run Laravel package discovery
RUN php artisan package:discover --ansi

# CRITICAL: Remove ALL cached Laravel files to force runtime to use environment variables
# This ensures ECS task definition env vars are respected, not cached build-time values
RUN rm -rf bootstrap/cache/*.php \
    && rm -rf storage/framework/cache/* \
    && rm -rf storage/framework/views/* \
    && rm -rf storage/framework/sessions/* \
    && mkdir -p bootstrap/cache \
    && touch bootstrap/cache/.gitkeep

# Render/php-fpm logging override (avoid /proc/self/fd/* permission issues)
RUN printf '[global]\n\
error_log = /dev/stderr\n\
log_level = notice\n\
\n\
[www]\n\
catch_workers_output = yes\n\
access.log = /dev/stdout\n' > /usr/local/etc/php-fpm.d/zz-render-logs.conf

# Create nginx config
RUN mkdir -p /etc/nginx/http.d && \
    echo 'server {\
    listen 80;\
    server_name _;\
    root /var/www/html/public;\
    index index.php;\
\
    client_max_body_size 10M;\
\
    access_log /dev/stdout;\
    error_log /dev/stderr;\
\
    add_header X-Frame-Options "DENY" always;\
    add_header X-Content-Type-Options "nosniff" always;\
\
    gzip on;\
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;\
\
    location /assets/ {\
        alias /var/www/html/public/build/assets/;\
        expires 1y;\
        add_header Cache-Control "public, immutable";\
    }\
\
    location / {\
        try_files $uri $uri/ /index.php?$query_string;\
    }\
\
    location ~ \.php$ {\
        fastcgi_pass 127.0.0.1:9000;\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\
        include fastcgi_params;\
    }\
\
    location ~ /\. {\
        deny all;\
    }\
}' > /etc/nginx/http.d/default.conf

# Create supervisor config
RUN mkdir -p /etc/supervisor/conf.d && printf '[supervisord]\n\
nodaemon=true\n\
logfile=/dev/null\n\
logfile_maxbytes=0\n\
\n\
[program:php-fpm]\n\
command=php-fpm -F\n\
autostart=true\n\
autorestart=true\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off; pid /tmp/nginx.pid;"\n\
autostart=true\n\
autorestart=true\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n' > /etc/supervisor/conf.d/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create startup script
RUN printf '#!/bin/sh\n\
set -e\n\
\n\
# DEBUG: Log the DB_HOST from environment to verify ECS task definition vars are loaded\n\
echo "=== STARTUP DEBUG ===" \n\
echo "DB_HOST from env: $DB_HOST"\n\
echo "DB_USERNAME from env: $DB_USERNAME"\n\
echo "APP_ENV from env: $APP_ENV"\n\
echo "====================="\n\
\n\
# Create empty .env file - Laravel requires this file to exist\n\
# but will use environment variables when .env is empty or missing values\n\
touch /var/www/html/.env\n\
\n\
# Ensure bootstrap/cache directory exists and is writable\n\
mkdir -p /var/www/html/bootstrap/cache\n\
chown -R www-data:www-data /var/www/html/bootstrap/cache\n\
chmod -R 775 /var/www/html/bootstrap/cache\n\
\n\
# Ensure storage directories exist\n\
mkdir -p /var/www/html/storage/framework/cache\n\
mkdir -p /var/www/html/storage/framework/sessions\n\
mkdir -p /var/www/html/storage/framework/views\n\
mkdir -p /var/www/html/storage/logs\n\
chown -R www-data:www-data /var/www/html/storage\n\
chmod -R 775 /var/www/html/storage\n\
\n\
# Create SQLite database file for local fallback (migrations need this)\n\
mkdir -p /var/www/html/database\n\
if [ ! -f /var/www/html/database/database.sqlite ]; then\n\
  touch /var/www/html/database/database.sqlite\n\
fi\n\
chown -R www-data:www-data /var/www/html/database\n\
chmod -R 775 /var/www/html/database\n\
\n\
# Run migrations (will use DB_HOST from environment)\n\
php artisan migrate --force --path=/var/www/html/migrations --realpath || true\n\
\n\
# Create storage link\n\
php artisan storage:link || true\n\
\n\
# Start supervisord\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf\n' > /start.sh \
    && chmod +x /start.sh

# Expose port
EXPOSE 80

# Start
CMD ["/start.sh"]
