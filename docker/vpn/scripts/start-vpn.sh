#!/bin/bash

# Activer le mode debug pour voir les commandes exécutées
set -x

# Fonction de logging
log() {
    echo "[$(date)] $1" | tee -a /var/log/openvpn/startup.log
}

# Créer les répertoires nécessaires avec les bonnes permissions
log "Création des répertoires..."
mkdir -p /etc/openvpn/server
mkdir -p /etc/openvpn/client
mkdir -p /var/log/openvpn
chmod -R 777 /var/log/openvpn

# Initialiser les clés si elles n'existent pas
if [ ! -f "/etc/openvpn/server/server.key" ]; then
    log "Génération des certificats..."
    cd /etc/openvpn/easy-rsa || exit 1
    
    # Initialiser la PKI
    ./easyrsa --batch init-pki
    EASYRSA_BATCH=1 ./easyrsa --batch build-ca nopass
    EASYRSA_BATCH=1 ./easyrsa --batch build-server-full server nopass
    EASYRSA_BATCH=1 ./easyrsa --batch build-client-full client1 nopass
    
    # Copier les fichiers générés
    log "Copie des certificats..."
    mkdir -p /etc/openvpn/server
    cp pki/ca.crt /etc/openvpn/server/
    cp pki/issued/server.crt /etc/openvpn/server/
    cp pki/private/server.key /etc/openvpn/server/
    cp /etc/openvpn/dh2048.pem /etc/openvpn/server/dh.pem
    
    # Copier les fichiers client
    mkdir -p /etc/openvpn/client
    cp pki/ca.crt /etc/openvpn/client/
    cp pki/issued/client1.crt /etc/openvpn/client/
    cp pki/private/client1.key /etc/openvpn/client/
    
    # Générer une clé TLS-Auth
    cd /etc/openvpn/server || exit 1
    log "Génération de la clé TLS-Auth..."
    openvpn --genkey --secret ta.key
    cp ta.key /etc/openvpn/client/
fi

# Définir les permissions appropriées
log "Configuration des permissions..."
chmod 600 /etc/openvpn/server/server.key
chmod 600 /etc/openvpn/server/ta.key
chown nobody:nogroup /etc/openvpn/server/ta.key || true
chmod 644 /etc/openvpn/server/ca.crt
chmod 644 /etc/openvpn/server/server.crt
chmod 644 /etc/openvpn/server/dh.pem

# Générer la configuration client
log "Génération de la configuration client..."
cat > /etc/openvpn/client/client.conf << EOL
client
dev tun
proto udp
remote SERVER_IP 1194
resolv-retry infinite
nobind
persist-key
persist-tun

<ca>
$(cat /etc/openvpn/client/ca.crt)
</ca>

<cert>
$(cat /etc/openvpn/client/client1.crt)
</cert>

<key>
$(cat /etc/openvpn/client/client1.key)
</key>

<tls-auth>
$(cat /etc/openvpn/client/ta.key)
</tls-auth>
key-direction 1

cipher AES-256-GCM
auth SHA256
verb 3
EOL

chmod 644 /etc/openvpn/client/client.conf

# Configurer le réseau
log "Configuration du réseau..."
mkdir -p /dev/net
if [ ! -c /dev/net/tun ]; then
    log "Création du périphérique TUN..."
    mknod /dev/net/tun c 10 200 || log "Erreur lors de la création du périphérique TUN"
fi
chmod 600 /dev/net/tun

# Configurer le forwarding
log "Configuration du forwarding IP..."
echo 1 > /proc/sys/net/ipv4/ip_forward || log "Erreur lors de l'activation du forwarding IPv4"
echo 1 > /proc/sys/net/ipv6/conf/all/forwarding || log "Erreur lors de l'activation du forwarding IPv6"

# S'assurer que le répertoire des logs est accessible
log "Configuration des permissions des logs..."
chown -R nobody:nogroup /var/log/openvpn || true

# Configurer les routes
log "Configuration des routes..."
/etc/openvpn/scripts/setup-routes.sh

# Vérifier que les fichiers nécessaires existent
log "Vérification des fichiers critiques..."
for file in /etc/openvpn/server/server.conf /etc/openvpn/server/ca.crt /etc/openvpn/server/server.crt /etc/openvpn/server/server.key /etc/openvpn/server/dh.pem /etc/openvpn/server/ta.key; do
    if [ ! -f "$file" ]; then
        log "ERREUR: Fichier manquant: $file"
        exit 1
    fi
done

# Démarrer OpenVPN avec les bons droits
log "Démarrage d'OpenVPN..."
exec openvpn --config /etc/openvpn/server/server.conf --log-append /var/log/openvpn/openvpn.log
