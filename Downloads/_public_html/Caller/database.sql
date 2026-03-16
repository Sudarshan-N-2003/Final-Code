-- ================================================================
-- AdmissionConnect - MySQL Database Schema
-- Complete Rewrite for Hostinger Web Hosting
-- Version: 2.0
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reminders;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- USERS TABLE
-- ================================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'telecaller', 'office') DEFAULT 'telecaller',
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    dob DATE,
    is_first_login TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- STUDENTS TABLE
-- ================================================================
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    mobile VARCHAR(20) NOT NULL,
    present_college VARCHAR(255),
    college_type ENUM('PU', 'Diploma', 'Other') DEFAULT 'Other',
    address TEXT,
    status ENUM('pending', 'in_progress', 'accepted', 'rejected', 'callback') DEFAULT 'pending',
    assigned_to INT UNSIGNED,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_mobile (mobile),
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- FEEDBACK TABLE
-- ================================================================
CREATE TABLE feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    telecaller_id INT UNSIGNED NOT NULL,
    call_status ENUM('answered', 'no_answer', 'busy', 'invalid', 'callback') NOT NULL,
    notes TEXT,
    next_action VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (telecaller_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_student (student_id),
    INDEX idx_telecaller (telecaller_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- REMINDERS TABLE
-- ================================================================
CREATE TABLE reminders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    telecaller_id INT UNSIGNED NOT NULL,
    reminder_date DATE NOT NULL,
    reminder_time TIME,
    notes TEXT,
    is_notified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (telecaller_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_student (student_id),
    INDEX idx_telecaller (telecaller_id),
    INDEX idx_date (reminder_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- Insert placeholder admin (password set by setup script)
-- ================================================================
INSERT INTO users (name, email, password, phone, role, is_first_login) 
VALUES ('Admin', 'admin@college.com', 'PLACEHOLDER', '9999999999', 'admin', 0);
