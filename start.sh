#!/bin/sh
set -eu

echo "Starting FAMS application..."
echo "Running verified Phase 2 database migrations..."

php artisan migrate --force --no-interaction

echo "Database migrations completed."

if [ "${SEED_DEMO_RSBSA:-}" = "true" ]; then
    echo "Loading demo RSBSA registry records..."
    php artisan db:seed --class=DemoRsbsaFarmerSeeder --force --no-interaction
fi

exec apache2-foreground
