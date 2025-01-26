-- Structure de la base de données pour HackLabs CTF

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Base de données : `hacklabs`

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `points` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `machines`
--

CREATE TABLE `machines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `docker_image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `cpu_limit` int NOT NULL DEFAULT 1,
  `memory_limit` int NOT NULL DEFAULT 512,
  `exposed_ports` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `docker_image` (`docker_image`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `machine_id` int(11) NOT NULL,
  `difficulty` enum('easy','medium','hard','insane') NOT NULL,
  `points` int(11) NOT NULL,
  `max_users` int(11) NOT NULL DEFAULT 1,
  `time_limit` int(11) NOT NULL DEFAULT 120,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `room_users`
--

CREATE TABLE `room_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `container_name` varchar(255) DEFAULT NULL,
  `container_ip` varchar(128) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `time_limit` int(11) NOT NULL DEFAULT 60,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `ssh_port` int(11) DEFAULT NULL,
  `web_port` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `room_users_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `room_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `flags`
--

CREATE TABLE `flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `flag_hash` varchar(255) NOT NULL,
  `points` int(11) NOT NULL,
  `hint` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `flags_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `hints`
--

CREATE TABLE `hints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `cost` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `hints_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `vpn_auto_connect` tinyint(1) NOT NULL DEFAULT 0,
  `theme` varchar(20) NOT NULL DEFAULT 'light',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `user_flags`
--

CREATE TABLE `user_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `flag_id` int(11) NOT NULL,
  `validation_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_flag_unique` (`user_id`, `flag_id`),
  KEY `flag_id` (`flag_id`),
  CONSTRAINT `user_flags_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `user_flags_ibfk_2` FOREIGN KEY (`flag_id`) REFERENCES `flags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `vpn_configs`
--

CREATE TABLE `vpn_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `config_file` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `vpn_configs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `vpn_connections`
--

CREATE TABLE `vpn_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `common_name` varchar(255) NOT NULL,
  `virtual_ip` varchar(15) NOT NULL,
  `real_ip` varchar(15) NOT NULL,
  `connected_since` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('connected','disconnected') NOT NULL DEFAULT 'connected',
  PRIMARY KEY (`id`),
  KEY `virtual_ip` (`virtual_ip`),
  KEY `common_name` (`common_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`),
  KEY `attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Données de test
--


-- Insérer les types de machines
INSERT INTO `machines` (`name`, `docker_image`, `description`, `cpu_limit`, `memory_limit`, `exposed_ports`, `is_active`) VALUES
('Binary Ninja', 'ctf/binaryninja:latest', 'Un environnement pour l''analyse de binaires et l''exploitation', 1, 512, '22', 1),
('Web Master', 'ctf/webmaster:latest', 'Un environnement pour les challenges web avec vulnérabilités', 1, 512, '80', 1);

-- Salles de test
INSERT INTO `rooms` (`name`, `description`, `machine_id`, `difficulty`, `points`, `max_users`, `time_limit`, `is_active`) VALUES
('Binary Challenge 1', 'Exploitez un buffer overflow simple pour obtenir le flag', 1, 'Easy', 100, 5, 60, 1),
('Web Challenge 1', 'Trouvez et exploitez une injection SQL pour accéder au panneau d''administration', 2, 'Medium', 200, 5, 60, 1);

-- Flags pour les challenges
INSERT INTO `flags` (`room_id`, `flag_hash`, `points`, `hint`) VALUES
(1, 'CTF{b1n4ry_n1nj4_l3v3l_1}', 100, 'Flag principal du challenge Binary 1'),
(2, 'CTF{w3b_m4st3r_sql1}', 200, 'Flag principal du challenge Web 1');

-- Hints pour les challenges
INSERT INTO `hints` (`room_id`, `content`, `cost`, `is_active`) VALUES
(1, 'Regardez la fonction gets() et la taille du buffer', 10, 1),
(1, 'La fonction win() contient le flag', 20, 1),
(2, 'L''application utilise une requête SQL vulnérable dans le formulaire de connexion', 10, 1),
(2, 'Essayez d''utiliser une injection SQL de type UNION', 20, 1);

CREATE TABLE IF NOT EXISTS vpn_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    server VARCHAR(255) NOT NULL,
    port INT NOT NULL,
    protocol ENUM('udp', 'tcp') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, is_active) VALUES
('admin', 'admin@dockerv2.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
