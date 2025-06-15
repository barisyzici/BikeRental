-- Veritabanı oluşturma
CREATE DATABASE IF NOT EXISTS bike_rental 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE bike_rental;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Bisiklet kiralamaları tablosu
CREATE TABLE IF NOT EXISTS bike_rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    bike_brand VARCHAR(50) NOT NULL,
    bike_model VARCHAR(50) NOT NULL,
    bike_color VARCHAR(30) NOT NULL DEFAULT 'Belirtilmemiş',
    price_per_hour DECIMAL(10,2) UNSIGNED NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100) NULL,
    customer_id_number VARCHAR(20) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    total_cost DECIMAL(10,2) UNSIGNED DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_customer_name (customer_name),
    INDEX idx_customer_id (customer_id_number)
) ENGINE=InnoDB;

-- Önce eski admin kullanıcısını sil (eğer varsa)
DELETE FROM users WHERE username = 'admin';

-- Örnek admin kullanıcısı (şifre: admin123)
INSERT INTO users (username, email, password, full_name, phone, is_active) VALUES 
('admin', 'admin@bikerental.com', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
'Sistem Yöneticisi', '0555 123 4567', TRUE);

-- Örnek kiralama verileri
INSERT INTO bike_rentals (
    user_id, bike_brand, bike_model, bike_color, 
    price_per_hour, customer_name, customer_phone, 
    customer_email, customer_id_number, start_date, 
    end_date, status, total_cost, notes
) VALUES 
((SELECT id FROM users WHERE username = 'admin'), 
'Trek', 'FX 3 Disc', 'Mavi', 
25.00, 'Ahmet Yılmaz', '0532 111 2233', 
'ahmet@email.com', '12345678901', 
'2024-12-01 10:00:00', '2024-12-01 15:00:00', 
'completed', 125.00, 'Sorunsuz kiralama'),

((SELECT id FROM users WHERE username = 'admin'), 
'Giant', 'Escape 3', 'Siyah', 
20.00, 'Ayşe Demir', '0533 444 5566', 
'ayse@email.com', '09876543210', 
'2024-12-02 09:00:00', NULL, 
'active', 0.00, 'Hala devam eden kiralama'),

((SELECT id FROM users WHERE username = 'admin'), 
'Specialized', 'Sirrus X 2.0', 'Kırmızı', 
30.00, 'Mehmet Kaya', '0534 777 8899', 
'mehmet@email.com', '11111111111', 
'2024-12-03 14:00:00', '2024-12-03 18:30:00', 
'completed', 135.00, 'Mükemmel durumda teslim edildi');