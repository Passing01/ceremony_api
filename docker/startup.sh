#!/bin/bash
set -e

cd /var/www/html

# Attendre que la base de données soit prête
until PGPASSWORD=$DB_PASSWORD psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c '\q'; do
  >&2 echo "PostgreSQL n'est pas encore disponible - en attente..."
  sleep 1
done

# Exécuter les migrations et autres commandes de démarrage
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Démarrer le serveur
exec "$@"
