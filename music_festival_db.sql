-- ============================================================
-- Music Festival Management System - Database Schema
-- ============================================================
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS music_festival_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE music_festival_db;

-- ============================================================
-- USERS TABLE - For all system users (Admin, Judge, Participant)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','participant','judge') DEFAULT 'participant',
  phone VARCHAR(20),
  address VARCHAR(255),
  city VARCHAR(50),
  state VARCHAR(50),
  country VARCHAR(50),
  bio TEXT,
  profile_pic VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  last_login DATETIME,
  date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY unique_email (email),
  KEY idx_role (role),
  KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CLASSES TABLE - Music festival performance classes
-- ============================================================
CREATE TABLE IF NOT EXISTS classes (
  class_id INT(11) NOT NULL AUTO_INCREMENT,
  class_name VARCHAR(100) NOT NULL,
  description TEXT,
  category VARCHAR(50),
  max_participants INT(11),
  entry_fee DECIMAL(10,2),
  duration_minutes INT(11),
  rules TEXT,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (class_id),
  KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REGISTRATION TABLE - Participant class registrations
-- ============================================================
CREATE TABLE IF NOT EXISTS registration (
  reg_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  class_id INT(11) NOT NULL,
  performance_title VARCHAR(150) NOT NULL,
  performance_description TEXT,
  duration_minutes INT(11),
  song_artist VARCHAR(100),
  genre VARCHAR(50),
  reg_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  rejection_reason VARCHAR(255),
  notes TEXT,
  PRIMARY KEY (reg_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_user_id (user_id),
  KEY idx_class_id (class_id),
  KEY idx_status (status),
  KEY idx_reg_date (reg_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- RESULTS TABLE - Performance scores and rankings
-- ============================================================
CREATE TABLE IF NOT EXISTS results (
  result_id INT(11) NOT NULL AUTO_INCREMENT,
  reg_id INT(11) NOT NULL,
  judge_id INT(11),
  score DECIMAL(5,2),
  position VARCHAR(10),
  rank INT(11),
  remarks TEXT,
  technical_score DECIMAL(5,2),
  performance_score DECIMAL(5,2),
  presentation_score DECIMAL(5,2),
  date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (result_id),
  FOREIGN KEY (reg_id) REFERENCES registration(reg_id) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_reg_id (reg_id),
  KEY idx_position (position),
  KEY idx_date_recorded (date_recorded)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- JUDGES TABLE - Judge information and specializations
-- ============================================================
CREATE TABLE IF NOT EXISTS judges (
  judge_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11),
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  phone VARCHAR(20),
  specialization VARCHAR(100),
  experience_years INT(11),
  bio TEXT,
  profile_pic VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (judge_id),
  KEY idx_user_id (user_id),
  KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AUDIT LOG TABLE - Track system activities
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
  log_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11),
  action VARCHAR(50),
  description TEXT,
  table_name VARCHAR(50),
  record_id INT(11),
  old_value TEXT,
  new_value TEXT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id),
  KEY idx_user_id (user_id),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA - For testing
-- ============================================================

-- Insert admin user (password: admin@123)
INSERT INTO users (full_name, email, password, role) VALUES
('Admin User', 'admin@musicfest.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWDeS5FxQDlulKx2', 'admin')
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Insert judge user (password: judge@123)
INSERT INTO users (full_name, email, password, role, phone) VALUES
('Chief Judge', 'judge@musicfest.com', '$2y$10$V1H6/HXd7K7vLd6h7K8q5uN9qo8uLOickgx2ZMRZoMyeIjZAgcg7b', 'judge', '+254701234567')
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Insert sample participants (password: user@123)
INSERT INTO users (full_name, email, password, role, phone) VALUES
('Ivy Novareen', 'ivy@musicfest.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWDeS5FxQDlulKx2', 'participant', '+254701234568'),
('John Musician', 'john@musicfest.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWDeS5FxQDlulKx2', 'participant', '+254701234569'),
('Sarah Vocalist', 'sarah@musicfest.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWDeS5FxQDlulKx2', 'participant', '+254701234570')
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Insert sample classes
INSERT INTO classes (class_name, description, category, max_participants, duration_minutes) VALUES
('Solo Voice', 'Solo singing performance by individual', 'Vocal', 50, 5),
('Choir Performance', 'Group choir singing performance', 'Vocal', 30, 10),
('Instrumental Solo', 'Solo performance with musical instrument', 'Instrumental', 40, 5),
('Dance Performance', 'Solo or group dance performance', 'Dance', 20, 8)
ON DUPLICATE KEY UPDATE class_id = class_id;

-- Insert sample registrations
INSERT INTO registration (user_id, class_id, performance_title, status) VALUES
(3, 1, 'Amazing Grace - Solo', 'Approved'),
(4, 1, 'Hallelujah Rendition', 'Pending'),
(5, 2, 'Gospel Medley', 'Approved')
ON DUPLICATE KEY UPDATE reg_id = reg_id;

-- Insert sample results
INSERT INTO results (reg_id, judge_id, score, position) VALUES
(1, 2, 85.50, '1st'),
(3, 2, 80.00, '2nd')
ON DUPLICATE KEY UPDATE result_id = result_id;

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================
CREATE INDEX idx_registration_user_class ON registration(user_id, class_id);
CREATE INDEX idx_results_reg_id ON results(reg_id);
CREATE INDEX idx_judges_active ON judges(is_active);
CREATE INDEX idx_users_active ON users(is_active);