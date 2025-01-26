#!/bin/bash

# Activer le forwarding IP
sysctl -w net.ipv4.ip_forward=1
sysctl -w net.ipv6.conf.all.forwarding=1

# Nettoyer les règles existantes
iptables -F
iptables -t nat -F
iptables -X
iptables -t nat -X

# Politique par défaut
iptables -P INPUT ACCEPT
iptables -P FORWARD ACCEPT
iptables -P OUTPUT ACCEPT

# Permettre le trafic établi
iptables -A INPUT -m state --state RELATED,ESTABLISHED -j ACCEPT
iptables -A FORWARD -m state --state RELATED,ESTABLISHED -j ACCEPT

# Permettre le trafic UDP pour OpenVPN
iptables -A INPUT -p udp --dport 1194 -j ACCEPT

# Règles pour Docker
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o docker0 -j MASQUERADE
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o br-* -j MASQUERADE
iptables -A FORWARD -i tun0 -o docker0 -j ACCEPT
iptables -A FORWARD -i docker0 -o tun0 -j ACCEPT
iptables -A FORWARD -i tun0 -o br-* -j ACCEPT
iptables -A FORWARD -i br-* -o tun0 -j ACCEPT

# Permettre le trafic VPN
iptables -A INPUT -i tun+ -j ACCEPT
iptables -A FORWARD -i tun+ -j ACCEPT
iptables -A OUTPUT -o tun+ -j ACCEPT

# Configuration NAT spécifique pour le subnet VPN
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o eth0 -j MASQUERADE
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o wlp2s0 -j MASQUERADE

# Permettre le forwarding pour le subnet VPN
iptables -A FORWARD -s 10.8.0.0/24 -m state --state NEW -j ACCEPT

# Permettre le trafic client-to-client
iptables -A FORWARD -i tun+ -o tun+ -j ACCEPT

# Allow forwarding between VPN and local networks
iptables -A FORWARD -i tun0 -o wlp2s0 -j ACCEPT
iptables -A FORWARD -i wlp2s0 -o tun0 -j ACCEPT

# Masquerade tout le trafic sortant
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -j MASQUERADE