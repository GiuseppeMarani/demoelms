-- Modern schema for barber_shop database
DROP DATABASE IF EXISTS barber_shop;
CREATE DATABASE barber_shop;
USE barber_shop;

-- Users table with phone number
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'barber') NOT NULL DEFAULT 'customer',
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Simplified services with icons
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    duration INT NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

-- Reservations with status tracking
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reservation (reservation_date, reservation_time)
);

-- Insert basic services
INSERT INTO services (name, icon, duration, price) VALUES
('Taglio', 'cut', 30, 25.00),
('Barba', 'beard', 20, 15.00),
('Taglio + Barba', 'cut-beard', 45, 35.00);

-- Create default barber account
INSERT INTO users (username, phone, email, password, role) VALUES
('barber', '+39123456789', 'barber@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber');
