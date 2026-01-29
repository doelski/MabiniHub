-- Create and use a self-contained database named exactly "capstone"
CREATE DATABASE IF NOT EXISTS `capstone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `capstone`;

-- users table: includes role, contact number, normalized status, employee_id, leave credits, gender, leave application toggle, and archiving
CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	lastname VARCHAR(100) NOT NULL,
	firstname VARCHAR(100) NOT NULL,
	mi CHAR(1),
	department VARCHAR(100) NOT NULL,
	position ENUM('Permanent','Casual','JO','OJT') NOT NULL DEFAULT 'Permanent',
	role ENUM('employee','department_head','hr') NOT NULL DEFAULT 'employee',
	status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
	contact_no VARCHAR(50) DEFAULT NULL,
	email VARCHAR(100) NOT NULL UNIQUE,
	password VARCHAR(255) NOT NULL,
	profile_picture MEDIUMTEXT,
	employee_id VARCHAR(100) DEFAULT NULL UNIQUE,
	vacation_leave DECIMAL(10,2) DEFAULT 15.00 COMMENT 'Vacation leave credits',
	sick_leave DECIMAL(10,2) DEFAULT 15.00 COMMENT 'Sick leave credits',
	gender ENUM('M','F') DEFAULT NULL COMMENT 'Employee gender: M (Male) or F (Female)',
	can_apply_leave TINYINT(1) DEFAULT 1 COMMENT 'Controls whether employee can apply for leave: 1=enabled, 0=disabled',
	is_archived TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Soft delete flag: 0=active, 1=archived',
	archived_at DATETIME NULL DEFAULT NULL COMMENT 'Timestamp when user was archived',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time VARCHAR(50),
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persist leave requests
CREATE TABLE IF NOT EXISTS leave_requests (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employee_email VARCHAR(100) NOT NULL,
	dept_head_email VARCHAR(100) NOT NULL,
	leave_type VARCHAR(255) NOT NULL,
	dates VARCHAR(255) NOT NULL,
	reason TEXT,
	signature_path VARCHAR(255) DEFAULT NULL,
	details LONGTEXT DEFAULT NULL COMMENT 'JSON field for dept head and HR signatures, sections, etc.',
	request_token VARCHAR(100) NULL,
	status ENUM('pending','approved','declined','recall') NOT NULL DEFAULT 'pending',
	decline_reason TEXT DEFAULT NULL,
	approved_by_dept_head TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Department Head approval: 0=pending, 1=approved',
	approved_by_hr TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'HR approval: 0=pending, 1=approved',
	approved_by_municipal TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Municipal Admin approval: 0=pending, 1=approved, 2=declined',
	municipal_approval_date DATETIME NULL DEFAULT NULL COMMENT 'Date when municipal admin approved/declined',
	recommendation VARCHAR(50) DEFAULT NULL,
	disapproval_reason1 VARCHAR(255) DEFAULT NULL,
	disapproval_reason2 VARCHAR(255) DEFAULT NULL,
	disapproval_reason3 VARCHAR(255) DEFAULT NULL,
	certification_date DATE DEFAULT NULL COMMENT 'As of date for leave credits certification',
	vl_total_earned DECIMAL(10,2) DEFAULT NULL COMMENT 'Display only: Total VL earned',
	vl_less_this_application DECIMAL(10,2) DEFAULT NULL COMMENT 'Display only: VL days requested',
	vl_balance DECIMAL(10,2) DEFAULT NULL COMMENT 'Display only: Calculated VL balance',
	sl_total_earned DECIMAL(10,2) DEFAULT NULL COMMENT 'Display only: Total SL earned',
	sl_less_this_application DECIMAL(10,2) DEFAULT NULL COMMENT 'Display only: SL days requested',
	sl_balance DECIMAL(10,2) DEFAULT NULL COMMENT 'Display only: Calculated SL balance',
	approved_days_with_pay VARCHAR(50) DEFAULT NULL,
	approved_days_without_pay VARCHAR(50) DEFAULT NULL,
	approved_others VARCHAR(255) DEFAULT NULL,
	disapproved_reason VARCHAR(255) DEFAULT NULL,
	authorized_official VARCHAR(255) DEFAULT NULL,
	applied_at DATETIME NOT NULL,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY uq_request_token (request_token),
	INDEX idx_employee_email (employee_email),
	INDEX idx_status (status),
	INDEX idx_approved_by_hr (approved_by_hr),
	INDEX idx_approved_by_municipal (approved_by_municipal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Override leave credits set by HR users
CREATE TABLE IF NOT EXISTS employee_leave_credits_override (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employee_email VARCHAR(100) NOT NULL,
	leave_type VARCHAR(100) NOT NULL COMMENT 'Leave type: Vacation Leave, Sick Leave, Maternity Leave, Paternity Leave, Calamity Leave, Special Privilege Leave, Solo Parent Leave, Magna Carta for Women, Rehabilitation Privilege',
	override_credits DECIMAL(10,2) NOT NULL COMMENT 'HR-set leave credits override',
	updated_by VARCHAR(100) NOT NULL COMMENT 'HR user email who set the override',
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
	UNIQUE KEY unique_override (employee_email, leave_type) COMMENT 'One override per employee per leave type',
	INDEX idx_employee_email (employee_email),
	INDEX idx_leave_type (leave_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persist employee signature for reuse
CREATE TABLE IF NOT EXISTS employee_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employee_email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persist department head signature for reuse
CREATE TABLE IF NOT EXISTS dept_head_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persist HR signature for reuse
CREATE TABLE IF NOT EXISTS hr_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Persist municipal admin signature for reuse
CREATE TABLE IF NOT EXISTS municipal_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
	id INT AUTO_INCREMENT PRIMARY KEY,
	recipient_email VARCHAR(100),
	recipient_role VARCHAR(100),
	message TEXT NOT NULL,
	type VARCHAR(50) DEFAULT 'recall',
	is_read TINYINT(1) DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks assigned by department heads to employees
CREATE TABLE IF NOT EXISTS tasks (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	description TEXT,
	due_date DATETIME DEFAULT NULL,
	status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
	assigned_to_email VARCHAR(100) NOT NULL,
	assigned_by_email VARCHAR(100) NOT NULL,
	attachment_path VARCHAR(255) DEFAULT NULL,
	submission_file_path VARCHAR(255) DEFAULT NULL,
	submission_note TEXT DEFAULT NULL,
	completed_at DATETIME DEFAULT NULL,
	ack_note TEXT DEFAULT NULL,
	ack_at DATETIME DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_assigned_to (assigned_to_email),
	INDEX idx_assigned_by (assigned_by_email),
	INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance table (standalone; users.employee_id already present above)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time_in DATETIME DEFAULT NULL,
    time_out DATETIME DEFAULT NULL,
	time_in_status ENUM('Present','Late','Undertime','Absent') DEFAULT NULL COMMENT 'Time In Status: Present (6:00 AM - 8:00 AM), Late (8:01 AM - 12:00 PM), Undertime (12:01 PM - 5:00 PM), Absent (after 5:00 PM)',
	time_out_status ENUM('Out','Undertime','Overtime','On-time') DEFAULT NULL COMMENT 'Time Out Status: Undertime (up to 4:59 PM), Out (5:00 PM - 5:59 PM), Overtime (6:00 PM onwards). Includes On-time for backward compatibility.',
    status VARCHAR(20) DEFAULT NULL COMMENT 'Overall daily status: Present, Absent, etc.',
    notes TEXT DEFAULT NULL COMMENT 'Additional notes or remarks',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    UNIQUE KEY unique_attendance (employee_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Department heads table (for managing department head assignments)
CREATE TABLE IF NOT EXISTS department_heads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS fingerprints (
	id INT(11) NOT NULL AUTO_INCREMENT,
	employee_id VARCHAR(100) NOT NULL,
	template LONGBLOB NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX (employee_id),
	CONSTRAINT fk_fingerprints_employee
		FOREIGN KEY (employee_id) REFERENCES users(employee_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
);

-- Create QR tokens table for hosting-compatible token management
-- This table stores QR tokens with automatic 60-second expiration

CREATE TABLE IF NOT EXISTS qr_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    used_by_user_id INT NULL,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add config table for QR secret if it doesn't exist
CREATE TABLE IF NOT EXISTS system_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert QR secret if not exists (will be generated by application if missing)
INSERT IGNORE INTO system_config (config_key, config_value) 
VALUES ('QR_SECRET', '');
