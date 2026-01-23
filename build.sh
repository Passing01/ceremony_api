#!/bin/bash
set -e

echo "=== Démarrage du build ==="

# Installer les dépendances PHP
echo "=== Installation des dépendances PHP ==="
composer install --no-interaction --no-progress --optimize-autoloader

# Installer les dépendances Node et compiler les assets
echo "=== Installation des dépendances Node ==="
npm ci --prefer-offline --no-audit --progress=false

# Compiler les assets pour la production
echo "=== Compilation des assets ==="
npm run build

# Configurer les permissions
echo "=== Configuration des permissions ==="
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Nettoyer les caches
echo "=== Nettoyage des caches ==="
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Créer le fichier .env s'il n'existe pas
if [ ! -f .env ]; then
    echo "=== Création du fichier .env ==="
    cp .env.example .env
    # Générer la clé d'application
    php artisan key:generate
fi

echo "=== Build terminé avec succès ==="
php artisan view:cache

# Créer le lien symbolique pour le stockage
php artisan storage:link