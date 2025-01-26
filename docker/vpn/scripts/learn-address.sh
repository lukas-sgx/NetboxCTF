#!/bin/bash

# Configuration de la base de données
DB_HOST="db"
DB_USER="hacklabs"
DB_PASS="hacklabs_password"
DB_NAME="hacklabs"

# Fonction pour échapper les caractères spéciaux SQL
escape_sql() {
    echo "$1" | sed 's/[\\"'"'"']/\\&/g'
}

# Les paramètres passés par OpenVPN
action="$1"      # add, update, delete
addr="$2"        # Adresse IP du client
cn="$3"          # Common Name du certificat client

# Obtenir l'IP source réelle du client depuis le fichier de statut OpenVPN
get_real_ip() {
    local virtual_ip=$1
    local real_ip=""
    
    while read -r line; do
        if [[ $line == *"$virtual_ip"* ]]; then
            real_ip=$(echo "$line" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+:[0-9]+' | cut -d: -f1 | head -1)
            break
        fi
    done < /tmp/openvpn-status.log
    
    echo "$real_ip"
}

# Log l'événement
echo "$(date): $action $addr $cn" >> /var/log/openvpn/learn-address.log

case "$action" in
    "add"|"update")
        # Obtenir l'IP réelle
        real_ip=$(get_real_ip "$addr")
        
        if [ -n "$real_ip" ]; then
            # Mettre à jour la base de données
            mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
            INSERT INTO vpn_connections (common_name, virtual_ip, real_ip)
            VALUES ('$(escape_sql "$cn")', '$(escape_sql "$addr")', '$(escape_sql "$real_ip")')
            ON DUPLICATE KEY UPDATE 
            last_seen = CURRENT_TIMESTAMP,
            status = 'connected',
            real_ip = '$(escape_sql "$real_ip")';
EOF
        fi
        ;;
        
    "delete")
        # Marquer la connexion comme déconnectée
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
        UPDATE vpn_connections 
        SET status = 'disconnected'
        WHERE virtual_ip = '$(escape_sql "$addr")'
        AND status = 'connected';
EOF
        ;;
esac

exit 0
