#!/usr/bin/env bash
set -euo pipefail
cd /app
php deploy/prepare.php
php artisan config:clear
php artisan pass:check-production --railway
php artisan migrate --force
php artisan db:seed --class=ProductionReferenceSeeder --force
export RAILPACK_SKIP_MIGRATIONS=true
exec /start-container.sh
