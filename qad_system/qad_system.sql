CREATE DATABASE IF NOT EXISTS qad_system;
USE qad_system;

-- 1. RESET TABLES
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. CREATE USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'sdo', 'school') DEFAULT 'school',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. CREATE REPORTS TABLE
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. INSERT ACCOUNTS (Password is 'admin123' for all)
-- Admin
INSERT INTO users (name, email, password, role) VALUES 
('System Admin', 'admin@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'admin');

-- All 13 SDOs of Region VIII
INSERT INTO users (name, email, password, role) VALUES 
('SDO Baybay City', 'sdo.baybay@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Biliran', 'sdo.biliran@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Borongan City', 'sdo.borongan@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Calbayog City', 'sdo.calbayog@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Catbalogan City', 'sdo.catbalogan@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Eastern Samar', 'sdo.easternsamar@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Leyte', 'sdo.leyte@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Maasin City', 'sdo.maasin@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Northern Samar', 'sdo.northernsamar@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Ormoc City', 'sdo.ormoc@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Samar', 'sdo.samar@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Southern Leyte', 'sdo.southernleyte@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo'),
('SDO Tacloban City', 'sdo.tacloban@deped.gov.ph', '$2y$10$ThH4.0v.Xv/gLzDk.u.xZO/G.hE6fG.hE6fG.hE6fG.hE6fG.hE6', 'sdo');

USE qad_system;

-- This table is required for the "Shopee-style" history
CREATE TABLE IF NOT EXISTS report_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    remarks TEXT,
    changed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
);

-- Track status changes and user activity
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Store official school records
CREATE TABLE IF NOT EXISTS schools (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    level VARCHAR(50),
    division VARCHAR(100),
    status VARCHAR(50) DEFAULT 'Compliant'
);

-- Add a column to reports for disapproval remarks if not exists
ALTER TABLE reports ADD COLUMN IF NOT EXISTS remarks TEXT AFTER status;
