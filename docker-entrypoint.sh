#!/bin/sh
set -e

# Wait for database to be ready
echo "Waiting for database..."
until php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is up - executing migrations"

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Clear cache
php bin/console cache:clear

echo "Application is ready!"

# Execute the main container command
exec "$@"

