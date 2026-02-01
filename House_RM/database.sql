CREATE DATABASE IF NOT EXISTS house_rm;
USE house_rm;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('admin', 'landlord', 'tenant') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

CREATE TABLE properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    landlord_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    type VARCHAR(50),
    address TEXT,
    city VARCHAR(50),
    price DECIMAL(10,2),
    bedrooms INT,
    bathrooms INT,
    area DECIMAL(10,2),
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (landlord_id) REFERENCES users(id)
);

CREATE TABLE property_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    image_path VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (property_id) REFERENCES properties(id)
);

CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    tenant_id INT,
    start_date DATE,
    end_date DATE,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (tenant_id) REFERENCES users(id)
);

CREATE TABLE maintenance_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT,
    tenant_id INT,
    title VARCHAR(100),
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (tenant_id) REFERENCES users(id)
);

CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    receiver_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);
