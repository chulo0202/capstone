#!/bin/sh
set -eu

echo "Starting FAMS application..."
echo "Database migrations must be run separately after verification."

exec apache2-foreground
