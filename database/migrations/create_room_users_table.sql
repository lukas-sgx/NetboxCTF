CREATE TABLE IF NOT EXISTS room_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    container_name VARCHAR(255) NOT NULL,
    container_ip VARCHAR(45) NOT NULL,
    start_time DATETIME NOT NULL,
    time_limit INT NOT NULL,
    active BOOLEAN DEFAULT 1,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_room_user (room_id, user_id),
    INDEX idx_active (active)
);
