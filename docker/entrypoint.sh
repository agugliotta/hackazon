#!/usr/bin/env bash
set -e

# Delete any stale bootstrap cache from the image or a previous run
rm -f bootstrap/cache/packages.php \
      bootstrap/cache/services.php \
      bootstrap/cache/config.php \
      bootstrap/cache/routes*.php

# Discover and register service providers (writes a fresh packages.php)
php artisan package:discover --ansi

# Clear any other cached data
php artisan config:clear
php artisan cache:clear

# Wait for MySQL to be reachable before starting Apache
echo "[entrypoint] Waiting for MySQL..."
until php -r "
  try {
    \$pdo = new PDO('mysql:host=db;port=3306;dbname=hackazon', 'hackazon', 'hackazon');
    exit(0);
  } catch (Exception \$e) { exit(1); }
" 2>/dev/null; do
    printf '.'
    sleep 2
done
echo "[entrypoint] MySQL ready."

# Ensure storage permissions are correct
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Hand off to Apache
exec apache2-foreground
