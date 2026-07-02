-- Bank Locker Management System Database
-- Run this SQL file to create the database and tables

CREATE DATABASE IF NOT EXISTS bank_locker_db;
USE bank_locker_db;

-- Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(15) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    aadhar_no VARCHAR(12) NOT NULL,
    account_no VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lockers Table
CREATE TABLE IF NOT EXISTS lockers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    locker_number VARCHAR(20) NOT NULL UNIQUE,
    locker_size ENUM('small', 'medium', 'large') NOT NULL,
    annual_rent DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'allocated', 'maintenance') DEFAULT 'available',
    location VARCHAR(100) NOT NULL DEFAULT 'Main Branch Vault',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Locker Allocations Table
CREATE TABLE IF NOT EXISTS allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_no VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    locker_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    rent_paid DECIMAL(10,2) NOT NULL,
    payment_status ENUM('paid', 'pending', 'overdue') DEFAULT 'paid',
    status ENUM('active', 'surrendered') DEFAULT 'active',
    allocated_by VARCHAR(100) DEFAULT 'Admin',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (locker_id) REFERENCES lockers(id)
);

-- Access Log Table
CREATE TABLE IF NOT EXISTS access_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    locker_id INT NOT NULL,
    access_date DATE NOT NULL,
    access_time TIME NOT NULL,
    purpose VARCHAR(255),
    approved_by VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (locker_id) REFERENCES lockers(id)
);

-- Sub Banker Table
CREATE TABLE IF NOT EXISTS sub_banker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Locker Requests Table (customer requests for new locker)
CREATE TABLE IF NOT EXISTS locker_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    locker_size ENUM('small','medium','large') NOT NULL,
    preferred_location VARCHAR(100) DEFAULT '',
    reason TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    handled_by VARCHAR(100) DEFAULT NULL,
    handled_remarks TEXT,
    handled_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Delete Requests Table
CREATE TABLE IF NOT EXISTS delete_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    allocation_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    handled_by VARCHAR(100) DEFAULT NULL,
    handled_remarks TEXT,
    handled_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (allocation_id) REFERENCES allocations(id)
);

-- Contact Messages Table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    reply TEXT DEFAULT NULL,
    replied_by VARCHAR(100) DEFAULT NULL,
    status ENUM('unread','read','replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Password Resets Table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','sub_banker','customer') NOT NULL,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity Log Table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admin (username, password, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bank Administrator');
-- Default admin password: password

-- Insert default sub banker (password: subbanker123)
INSERT INTO sub_banker (username, password, full_name, employee_id, email, phone) VALUES
('subbanker', '$2y$10$YS4rK8qDl8mXn0fQ6rVZpOJvHk3Fv2b8C5nR7mT1wX9zA4eB6gHjy', 'Sub Banker Officer', 'EMP2024001', 'subbanker@securebank.com', '9876543210');
-- Default sub banker password: subbanker123

-- Insert sample lockers
INSERT INTO lockers (locker_number, locker_size, annual_rent, location) VALUES
('L001', 'small', 1500.00, 'Main Branch Vault - Row A'),
('L002', 'small', 1500.00, 'Main Branch Vault - Row A'),
('L003', 'small', 1500.00, 'Main Branch Vault - Row A'),
('L004', 'medium', 2500.00, 'Main Branch Vault - Row B'),
('L005', 'medium', 2500.00, 'Main Branch Vault - Row B'),
('L006', 'medium', 2500.00, 'Main Branch Vault - Row B'),
('L007', 'large', 4000.00, 'Main Branch Vault - Row C'),
('L008', 'large', 4000.00, 'Main Branch Vault - Row C'),
('L009', 'large', 4000.00, 'Main Branch Vault - Row C'),
('L010', 'small', 1500.00, 'Main Branch Vault - Row A'),
('L011', 'medium', 2500.00, 'Main Branch Vault - Row B'),
('L012', 'large', 4000.00, 'Main Branch Vault - Row C');

-- Settings Table
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL
);

-- Default Settings Data
INSERT INTO settings (setting_key, setting_value) VALUES 
('maintenance_mode', 'off'),
('maintenance_message', 'We are currently performing scheduled system upgrades. Please check back soon.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

