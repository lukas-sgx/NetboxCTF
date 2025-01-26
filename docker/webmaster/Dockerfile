FROM php:7.4-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libpng-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mysqli

# Activer les modules Apache nécessaires
RUN a2enmod rewrite

# Configuration PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Copier les fichiers de l'application
COPY app/ /var/www/html/

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port 80
EXPOSE 80

# Le conteneur démarrera Apache automatiquement
