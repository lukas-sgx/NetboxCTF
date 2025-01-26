#!/bin/bash

# Enable IP forwarding
sysctl -w net.ipv4.ip_forward=1

# Get the main interface
IFACE=$(ip -o link show | grep -v "lo" | grep -v "tun" | head -n1 | cut -d: -f2 | tr -d ' ')

# Configure iptables
iptables -t nat -A POSTROUTING -s 10.8.0.0/24 -o $IFACE -j MASQUERADE
iptables -A INPUT -i tun+ -j ACCEPT
iptables -A FORWARD -i tun+ -j ACCEPT
iptables -A FORWARD -i $IFACE -o tun+ -j ACCEPT
iptables -A INPUT -i $IFACE -p udp --dport 1194 -j ACCEPT
