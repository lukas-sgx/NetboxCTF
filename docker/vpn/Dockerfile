FROM ubuntu:20.04

# Installation des dépendances
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    openvpn \
    easy-rsa \
    iptables \
    net-tools \
    && rm -rf /var/lib/apt/lists/*

# Configurer easy-rsa
RUN mkdir -p /etc/openvpn/easy-rsa
COPY config/vars /etc/openvpn/easy-rsa/
RUN ln -s /usr/share/easy-rsa/* /etc/openvpn/easy-rsa/

# Pré-générer les clés DH
RUN cd /etc/openvpn/easy-rsa && \
    ./easyrsa init-pki && \
    openssl dhparam -out /etc/openvpn/dh2048.pem 2048

# Copier les configurations et scripts
COPY config/server.conf /etc/openvpn/server/server.conf
COPY config/client.conf.template /etc/openvpn/client.conf.template
COPY scripts/setup-routes.sh /etc/openvpn/scripts/setup-routes.sh
RUN chmod +x /etc/openvpn/scripts/setup-routes.sh

# Créer les répertoires nécessaires
RUN mkdir -p /etc/openvpn/client \
    && mkdir -p /var/log/openvpn \
    && mkdir -p /etc/openvpn/server

# Exposer le port UDP
EXPOSE 1194/udp

# Ajouter les capacités réseau nécessaires
ENV CAPABILITIES="--cap-add=NET_ADMIN --cap-add=NET_RAW --privileged"

# Script de démarrage
COPY scripts/start-vpn.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start-vpn.sh

ENTRYPOINT ["/usr/local/bin/start-vpn.sh"]
