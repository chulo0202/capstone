#!/bin/sh
set -eu

echo "Starting FAMS application..."
echo "Running verified Phase 2 database migrations..."

php artisan migrate --force --no-interaction

echo "Database migrations completed."
exec apache2-foreground