#!/bin/sh
set -eu

echo "Running Laravel database migrations..."
php artisan migrate --force --no-interaction

echo "Starting Apache..."
exec apache2-foreground
