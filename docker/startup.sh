#!/bin/bash
set -e

cd /var/www/html

# Créer un script PHP pour vérifier la connexion à la base de données
cat > /tmp/check-db.php << 'EOL'
<?php
$maxTries = 30;
$tries = 0;
$connected = false;

while (!$connected && $tries < $maxTries) {
    try {
        $pdo = new PDO(
            "pgsql:host=" . getenv('DB_HOST') . ";port=5432;dbname=" . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        $connected = true;
        echo "Connexion à la base de données réussie!\n";
    } catch (PDOException $e) {
        $tries++;
        echo "Tentative $tries/$maxTries - La base de données n'est pas encore disponible : " . $e->getMessage() . "\n";
        if ($tries < $maxTries) {
            sleep(1);
        } else {
            echo "Échec de la connexion après $maxTries tentatives.\n";
            exit(1);
        }
    }
}
?>
EOL

# Attendre que la base de données soit prête
php /tmp/check-db.php
rm /tmp/check-db.php

# Exécuter les migrations et autres commandes de démarrage
echo "Réinitialisation complète de la base de données..."
php artisan migrate:fresh --force

echo "Exécution des seeds..."
php artisan db:seed --force

echo "Mise en cache de la configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Démarrage du serveur..."
exec "$@"
