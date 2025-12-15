# ========================
# Stage 1: Base Image
# ========================
FROM php:8.1-fpm as base

# Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    # Outils de base
    zip \
    unzip \
    git \
    curl \
    vim \
    nano \
    supervisor \
    # Bibliothèques pour les extensions PHP
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libssl-dev \
    # Bibliothèques pour GD
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    # Nettoyage
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configuration et installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    xml \
    opcache

# Installation de Redis extension (utile pour cache et sessions)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Configuration PHP pour la production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configuration PHP personnalisée
RUN { \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
    echo 'max_execution_time=300'; \
    echo 'memory_limit=512M'; \
    echo 'date.timezone=UTC'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Copier Composer depuis son image officielle
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Créer l'utilisateur www-data avec les bonnes permissions
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Définir le répertoire de travail
WORKDIR /var/www

# Autoriser Composer à s'exécuter en tant que root
ENV COMPOSER_ALLOW_SUPERUSER=1

# ========================
# Stage 2: Development
# ========================
FROM base as development

# Installer Xdebug pour le développement
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configuration Xdebug
RUN { \
    echo 'xdebug.mode=debug,coverage'; \
    echo 'xdebug.start_with_request=yes'; \
    echo 'xdebug.client_host=host.docker.internal'; \
    echo 'xdebug.client_port=9003'; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# Copier les fichiers de configuration composer
COPY composer.json composer.lock ./

# Installer toutes les dépendances (y compris dev)
RUN composer install --prefer-dist --no-interaction --no-progress

# Copier le reste du projet
COPY . .

# Fixer les permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Exposer le port PHP-FPM
EXPOSE 9000

# Copier et activer le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]

# ========================
# Stage 3: Production
# ========================
FROM base as production

# Copier uniquement les fichiers nécessaires pour l'installation
COPY composer.json composer.lock ./

# Installer les dépendances sans dev
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# Copier le reste de l'application
COPY . .

# Optimisations Laravel pour la production
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan cache:clear

# Fixer les permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Exposer le port PHP-FPM
EXPOSE 9000

# Copier et activer le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]