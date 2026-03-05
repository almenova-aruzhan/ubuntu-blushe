CREATE DATABASE IF NOT EXISTS blushe_db;
USE blushe_db;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO products (name, price, description)
VALUES ('Blushe Lipstick', 4990.00, 'Matte lipstick');

-- Админ логин/пароль (БД-ға)
CREATE USER IF NOT EXISTS 'admin_user'@'%' IDENTIFIED BY 'AdminPass_123!';
GRANT ALL PRIVILEGES ON blushe_db.* TO 'admin_user'@'%';
FLUSH PRIVILEGES;

