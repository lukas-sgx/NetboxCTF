#!/bin/bash

# Enable logging
set -x

# Get the default interface
DEFAULT_IFACE=$(ip route | grep default | awk '{print $5}')
echo "Default interface is: $DEFAULT_IFACE"

# Enable IP forwarding
echo 1 > /proc/sys/net/ipv4/ip_forward
echo "IP forwarding enabled"

# Configure iptables
iptables -F
iptables -t nat -F
iptables -P FORWARD ACCEPT

# Add MASQUERADE rules for VPN subnet
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o $DEFAULT_IFACE -j MASQUERADE

# Allow forwarding
iptables -A FORWARD -i tun0 -j ACCEPT
iptables -A FORWARD -i tun0 -o $DEFAULT_IFACE -m state --state RELATED,ESTABLISHED -j ACCEPT
iptables -A FORWARD -i $DEFAULT_IFACE -o tun0 -m state --state RELATED,ESTABLISHED -j ACCEPT

# Allow traffic between local network and VPN
iptables -A FORWARD -s 192.168.86.0/24 -d 10.8.0.0/24 -j ACCEPT
iptables -A FORWARD -s 10.8.0.0/24 -d 192.168.86.0/24 -j ACCEPT

echo "iptables rules configured"
iptables -L -v -n
echo "NAT rules:"
iptables -t nat -L -v -n

# Créer les répertoires nécessaires
mkdir -p /etc/openvpn/server/certs
mkdir -p /etc/openvpn/client
mkdir -p /var/log/openvpn
cd /etc/openvpn/easy-rsa

# Initialiser PKI
easyrsa init-pki
echo "yes" | easyrsa build-ca nopass
echo "yes" | easyrsa build-server-full server nopass
echo "yes" | easyrsa build-client-full client1 nopass

# Copier les paramètres DH pré-générés
cp /etc/openvpn/dh2048.pem /etc/openvpn/server/dh.pem

# Générer la clé TLS
openvpn --genkey secret /etc/openvpn/server/ta.key

# Copier les certificats et clés pour le serveur
cp pki/ca.crt /etc/openvpn/server/
cp pki/issued/server.crt /etc/openvpn/server/
cp pki/private/server.key /etc/openvpn/server/

# Copier les certificats et clés pour le client
cp pki/ca.crt /etc/openvpn/client/
cp pki/issued/client1.crt /etc/openvpn/client/
cp pki/private/client1.key /etc/openvpn/client/
cp /etc/openvpn/server/ta.key /etc/openvpn/client/

# Obtenir l'IP du serveur (plusieurs méthodes)
SERVER_IP=$(ip route get 8.8.8.8 2>/dev/null | grep -oP '(?<=src\s)\d+(\.\d+){3}' || \
            hostname -i 2>/dev/null || \
            echo "127.0.0.1")

echo "Detected Server IP: ${SERVER_IP}"

# Créer la configuration client
cat > /etc/openvpn/client/client.conf << EOF
client
dev tun
proto udp
remote ${SERVER_IP} 1194
resolv-retry infinite
nobind
persist-key
persist-tun
remote-cert-tls server
data-ciphers AES-256-GCM:AES-128-GCM:CHACHA20-POLY1305
data-ciphers-fallback AES-256-GCM
auth SHA256
compress lz4-v2
verb 3

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
EOF

# Configuration du pare-feu (utiliser l'interface disponible)
IFACE=$(ip -o link show | grep -v "lo" | head -n1 | cut -d: -f2 | tr -d ' ')
if [ ! -z "$IFACE" ]; then
    iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o $IFACE -j MASQUERADE || true
    iptables -A INPUT -i tun0 -j ACCEPT || true
    iptables -A FORWARD -i tun0 -j ACCEPT || true
    iptables -A FORWARD -i $IFACE -o tun0 -j ACCEPT || true
    iptables -A INPUT -i $IFACE -p udp --dport 1194 -j ACCEPT || true
fi

# Activer le forwarding IP (ignorer si en lecture seule)
echo 1 > /proc/sys/net/ipv4/ip_forward || true

# Démarrer OpenVPN avec plus de verbosité pour le débogage
exec openvpn --config /etc/openvpn/server.conf --verb 4
