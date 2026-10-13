-- database/schema.sql
CREATE DATABASE IF NOT EXISTS `db-kelola-gym`;
USE `db-kelola-gym`;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed akun awal (admin123 & user123)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@gym.com', '$2y$12$4VYuGBsW5Rj9l5R9voytp.yYSqT5Vt0xW6tZscAaq3rkup3nVtCm6', 'admin'),
('Gym Member', 'user@gym.com', '$2y$12$95ZTzdX9ppgJTHTnda7EuO9qRZEBO75xM3JPkUsoRLpFHnEKvJ/ky', 'user')
ON DUPLICATE KEY UPDATE name=VALUES(name);
