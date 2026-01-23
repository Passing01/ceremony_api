FROM php:8.2-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev \
    unzip \
    git \
    curl \
    gnupg \
    ca-certificates \
    acl \
    && docker-php-ext-install pdo pdo_pgsql pdo_sqlite

# Créer les répertoires nécessaires et configurer les permissions
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/run/php \
    && mkdir -p /var/run/nginx \
    && touch /var/run/nginx.pid \
    && chown -R www-data:www-data /var/log/supervisor \
    && chown -R www-data:www-data /var/run/php \
    && chown -R www-data:www-data /var/run/nginx \
    && chown -R www-data:www-data /var/run/nginx.pid

# Installer Node.js 20.x
RUN mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y nodejs

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier d'abord uniquement les fichiers nécessaires pour l'installation des dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP sans exécuter les scripts
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Copier le reste des fichiers
COPY . .

# Créer les répertoires nécessaires et configurer les permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache public database \
    && chmod -R 775 storage bootstrap/cache

# Installer les dépendances Node et construire les assets
RUN npm install --prefer-offline --no-audit --progress=false && \
    npm run build

# Exécuter les scripts post-install maintenant que tous les fichiers sont en place
RUN composer run-script post-autoload-dump --no-interaction \
    && php artisan storage:link

# Configurer Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Nettoyer le cache
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/*

# Exposer le port
EXPOSE 10000

# Démarrer les services avec les bonnes permissions
CMD ["/bin/sh", "-c", "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf"]