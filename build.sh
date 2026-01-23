#!/bin/bash

# Installer les dépendances
composer install --no-interaction --no-progress

# Configurer les permissions
chmod -R 775 storage bootstrap/cache

# Nettoyer les caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Afficher les migrations existantes
echo "=== Liste des migrations ==="
php artisan migrate:status

# Forcer la réexécution des migrations
echo "=== Exécution des migrations ==="
php artisan migrate:fresh --force

# Vérifier que la table sessions existe
echo "=== Vérification de la table sessions ==="
php artisan tinker --execute="dd(Schema::hasTable('sessions'));"

# Mettre en cache la configuration pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique pour le stockage
php artisan storage:link