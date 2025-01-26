FROM ubuntu:22.04

# Éviter les prompts interactifs pendant l'installation
ENV DEBIAN_FRONTEND=noninteractive

# Mettre à jour et installer les paquets de base
RUN apt-get update && apt-get install -y \
    openssh-server \
    python3 \
    python3-pip \
    gdb \
    gcc \
    make \
    vim \
    netcat \
    && rm -rf /var/lib/apt/lists/*

# Configurer SSH
RUN mkdir /var/run/sshd
RUN echo 'root:toor' | chpasswd
RUN sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config

# Créer un utilisateur ctf
RUN useradd -m -s /bin/bash ctf
RUN echo 'ctf:ctf' | chpasswd

# Copier les fichiers du challenge
COPY challenge /home/ctf/challenge
RUN chown -R root:root /home/ctf/challenge
RUN chmod -R 755 /home/ctf/challenge

# Exposer le port SSH
EXPOSE 22

# Démarrer SSH
CMD ["/usr/sbin/sshd", "-D"]
