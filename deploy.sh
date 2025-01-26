#!/bin/bash

# Fonction pour afficher les messages avec couleurs
print_message() {
    GREEN='\033[0;32m'
    RED='\033[0;31m'
    NC='\033[0m'
    
    if [ "$2" = "error" ]; then
        echo -e "${RED}$1${NC}"
    else
        echo -e "${GREEN}$1${NC}"
    fi
}

# Vérifier si le script est exécuté en tant que root
if [ "$EUID" -ne 0 ]; then 
    print_message "Ce script doit être exécuté en tant que root" "error"
    exit 1
fi

# Installation des dépendances
print_message "Installation des dépendances..."
pacman -Sy --noconfirm
pacman -S --noconfirm nginx php php-fpm

# Création des répertoires nécessaires
print_message "Création des répertoires..."
mkdir -p /var/log/nginx
mkdir -p /var/run/php
chown lks:lks /var/log/nginx
chown lks:lks /var/run/php

# Configuration de PHP-FPM
print_message "Configuration de PHP-FPM..."
PHP_FPM_CONF="/etc/php/php-fpm.d/www.conf"

# Backup de la configuration originale
if [ -f "$PHP_FPM_CONF" ]; then
    cp "$PHP_FPM_CONF" "$PHP_FPM_CONF.backup"
fi

# Configuration de base de PHP-FPM
cat > "$PHP_FPM_CONF" << 'EOL'
[www]
user = lks
group = lks
listen = /var/run/php/php-fpm.sock
listen.owner = lks
listen.group = lks
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOL

# S'assurer que le répertoire pour le socket existe
mkdir -p /var/run/php
chown lks:lks /var/run/php

# Configuration de Nginx
print_message "Configuration de Nginx..."
NGINX_CONF="/etc/nginx/nginx.conf"

# Backup de la configuration originale
if [ -f "$NGINX_CONF" ]; then
    cp "$NGINX_CONF" "$NGINX_CONF.backup"
fi

# Copie de notre configuration
cp nginx.conf "$NGINX_CONF"

# Ajustement des permissions
print_message "Ajustement des permissions..."
PROJECT_DIR="/home/lks/projects/dockerv2"
chown -R lks:lks "$PROJECT_DIR"
chmod -R 755 "$PROJECT_DIR"

# Vérification de la configuration
print_message "Vérification de la configuration Nginx..."
nginx -t

if [ $? -eq 0 ]; then
    # Redémarrage des services
    print_message "Redémarrage des services..."
    systemctl restart php-fpm
    systemctl restart nginx
    
    print_message "Déploiement terminé avec succès!"
    print_message "Le site devrait être accessible sur http://localhost"
else
    print_message "Erreur dans la configuration Nginx. Veuillez vérifier la syntaxe." "error"
    exit 1
fi

# Vérification des services
if systemctl is-active --quiet nginx && systemctl is-active --quiet php-fpm; then
    print_message "Les services Nginx et PHP-FPM sont actifs et en cours d'exécution."
else
    print_message "Erreur: Un ou plusieurs services ne sont pas en cours d'exécution." "error"
    print_message "Status Nginx:"
    systemctl status nginx
    print_message "Status PHP-FPM:"
    systemctl status php-fpm
    exit 1
fi
