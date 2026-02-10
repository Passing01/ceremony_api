#!/bin/bash

# Afficher les variables d'environnement pour le débogage
echo "=== Variables d'environnement ==="
echo "DB_CONNECTION=$DB_CONNECTION"
echo "DB_HOST=$DB_HOST"
echo "DB_PORT=$DB_PORT"
echo "DB_DATABASE=$DB_DATABASE"
echo "DB_USERNAME=$DB_USERNAME"

# Installer les dépendances
echo "=== Installation des dépendances ==="
composer install --no-interaction --no-progress

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
    # Configurer la base de données PostgreSQL
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    sed -i "s/DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE}/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME}/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" .env
    # Générer la clé d'application
    php artisan key:generate
fi

# Afficher la configuration de la base de données
echo "=== Configuration de la base de données ==="
cat .env | grep DB_

# Tester la connexion à la base de données
echo "=== Test de connexion à la base de données ==="
if php artisan db:show; then
    echo "Connexion à la base de données réussie!"
else
    echo "ERREUR: Impossible de se connecter à la base de données!"
    exit 1
fi

# Afficher les migrations existantes
echo "=== Liste des migrations ==="
php artisan migrate:status

# Exécuter les migrations
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