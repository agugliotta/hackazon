#!/usr/bin/env bash
# reset-db.sh — Restore the Hackazon database to its original clean state.
#
# Usage:
#   ./docker/reset-db.sh           # reset only the database (app stays running)
#   ./docker/reset-db.sh --full    # full teardown + rebuild of all containers
#
# After reset, all stored XSS payloads, created accounts, orders, etc. are gone.

set -e

FULL=${1:-""}

if [ "$FULL" = "--full" ]; then
    echo "[reset] Full teardown: removing all containers and volumes..."
    docker compose down -v
    echo "[reset] Starting fresh..."
    docker compose up -d --build
    echo ""
    echo "[reset] Done. Waiting for DB to be ready..."
    sleep 8
    echo "[reset] App is available at http://localhost:8080"
    exit 0
fi

# Fast path: only reset the database volume, leave the app container running
echo "[reset] Stopping database container..."
docker compose stop db
docker compose rm -f db

echo "[reset] Removing database volume (this wipes all data)..."
docker volume rm "$(docker compose config --format json 2>/dev/null | \
    php -r "echo json_decode(file_get_contents('php://stdin'))->name ?? 'hackazon_new';" \
    )_db_data" 2>/dev/null || \
    docker volume rm hackazon_new_db_data 2>/dev/null || true

echo "[reset] Recreating database from SQL dumps..."
docker compose up -d db

echo "[reset] Waiting for MySQL to initialise..."
until docker compose exec -T db mysqladmin ping -u hackazon -phackazon --silent 2>/dev/null; do
    printf '.'
    sleep 2
done
echo ""

echo "[reset] Clearing Laravel cache..."
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

echo ""
echo "[reset] Done. Database restored to clean state."
echo "        App available at http://localhost:8080"
