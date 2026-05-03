-- ============================================
-- BOOK MY TURF - Database Schema
-- Run this file in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS turf_booking;
USE turf_booking;

-- ============================================
-- Users Table (for signup/login)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Admin Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admin_users (username, password) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- ============================================
-- Turfs Table
-- ============================================
CREATE TABLE IF NOT EXISTS turfs (
    turf_id INT AUTO_INCREMENT PRIMARY KEY,
    turf_name VARCHAR(100) NOT NULL,
    location VARCHAR(200),
    price_per_hour DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample turfs
INSERT INTO turfs (turf_name, location, price_per_hour) VALUES
('City Cricket Turf', 'Downtown Sports Complex', 1200.00),
('Neighborhood Turf', 'Green Park Area', 900.00),
('Team Practice Turf', 'Stadium Road', 1500.00)
ON DUPLICATE KEY UPDATE turf_name = turf_name;

-- ============================================
-- Bookings Table
-- ============================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    email VARCHAR(150) NOT NULL,
    members_count INT NOT NULL DEFAULT 1,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    turf_name VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
