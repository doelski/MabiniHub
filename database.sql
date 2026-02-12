-- ============================================================================
-- MabiniHub Employee Management System - Database Schema
-- Finalized Version - Ready for Deployment
-- ============================================================================
-- This database schema is production-ready and includes:
-- - User management with role-based access (employee, department_head, hr, municipal admin)
-- - Leave request workflow with multi-level approvals
-- - CSV-based attendance tracking with time range rules
-- - Task assignment system
-- - Event management
-- - Notification system
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `capstone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `capstone`;

-- ============================================================================
-- USERS TABLE
-- ============================================================================
-- Manages all system users including employees, department heads, HR staff
-- Includes leave credits, archiving support, and employee identification
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
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_employee_id (employee_id),
	INDEX idx_email (email),
	INDEX idx_role (role),
	INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- EVENTS TABLE
-- ============================================================================
-- Manages organizational events visible to all employees
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time VARCHAR(50),
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- LEAVE REQUESTS TABLE
-- ============================================================================
-- Handles leave applications with multi-level approval workflow:
-- Employee -> Department Head -> HR -> Municipal Admin (final approval)
-- When approved_by_municipal=1, auto-creates attendance with status='on-leave'
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

-- ============================================================================
-- EMPLOYEE LEAVE CREDITS OVERRIDE TABLE
-- ============================================================================
-- Allows HR to manually set custom leave credits for specific employees
-- Overrides default vacation_leave and sick_leave from users table
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

-- ============================================================================
-- SIGNATURE TABLES
-- ============================================================================
-- Store signature images for reuse across leave applications
-- Each user role has their own signature storage table

-- Employee signatures
CREATE TABLE IF NOT EXISTS employee_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employee_email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Department head signatures
CREATE TABLE IF NOT EXISTS dept_head_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HR signatures
CREATE TABLE IF NOT EXISTS hr_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Municipal admin signatures
CREATE TABLE IF NOT EXISTS municipal_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NOTIFICATIONS TABLE
-- ============================================================================
-- System notifications for leave recalls and other important updates
CREATE TABLE IF NOT EXISTS notifications (
	id INT AUTO_INCREMENT PRIMARY KEY,
	recipient_email VARCHAR(100),
	recipient_role VARCHAR(100),
	message TEXT NOT NULL,
	type VARCHAR(50) DEFAULT 'recall',
	is_read TINYINT(1) DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_recipient_email (recipient_email),
	INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TASKS TABLE
-- ============================================================================
-- Department heads can assign tasks to employees with attachments and deadlines
-- Supports task lifecycle: pending -> in_progress -> completed
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
	INDEX idx_status (status),
	INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ATTENDANCE TABLE
-- ============================================================================
-- CSV-based attendance tracking with finalized time range rules
-- 
-- TIME RANGE RULES (applied during CSV import):
--   TIME IN STATUS:
--     Present:    4:00 AM - 7:00 AM
--     Late:       7:01 AM - 12:00 PM (noon)
--     Absent:     10:00 AM onwards (no time in before 10 AM)
--   
--   TIME OUT STATUS:
--     Undertime:  1:00 PM - 4:59 PM
--     Out:        5:00 PM - 6:00 PM (normal dismissal)
--     Overtime:   6:01 PM - 8:00 PM
-- 
-- SPECIAL STATUS HANDLING:
--   - status='on-leave' is auto-created when Municipal Admin approves leave
--   - When status='on-leave', display shows only "ON LEAVE" badge (purple)
--   - Time In/Out Status columns show dash (—) when on leave
--   - Database stores dates as YYYY-MM-DD, CSV imports as DD/MM/YYYY
-- 
-- UNIQUE CONSTRAINT:
--   - One attendance record per employee per date (employee_id, date)
--   - CSV import uses INSERT...ON DUPLICATE KEY UPDATE for upsert behavior
-- ============================================================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(100) NOT NULL,
    date DATE NOT NULL COMMENT 'Attendance date in YYYY-MM-DD format',
    time_in DATETIME DEFAULT NULL COMMENT 'Full date-time of clock in (not just time)',
    time_out DATETIME DEFAULT NULL COMMENT 'Full date-time of clock out (not just time)',
	time_in_status ENUM('Present','Late','Undertime','Absent') DEFAULT NULL COMMENT 'Time In Status calculated from time ranges: Present (4am-7am), Late (7:01am-12pm), Absent (10am+)',
	time_out_status ENUM('Out','Undertime','Overtime','On-time') DEFAULT NULL COMMENT 'Time Out Status: Undertime (1pm-4:59pm), Out (5pm-6pm), Overtime (6:01pm-8pm). On-time for backward compatibility.',
    status VARCHAR(20) DEFAULT NULL COMMENT 'Special status: on-leave (auto-set when Municipal Admin approves leave), or overall daily status',
    notes TEXT DEFAULT NULL COMMENT 'Additional notes or remarks',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    UNIQUE KEY unique_attendance (employee_id, date) COMMENT 'One record per employee per date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DEPARTMENT HEADS TABLE
-- ============================================================================
-- Manages department head assignments - who heads which department
CREATE TABLE IF NOT EXISTS department_heads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SYSTEM CONFIG TABLE
-- ============================================================================
-- Stores application-wide configuration settings as key-value pairs
CREATE TABLE IF NOT EXISTS system_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- END OF DATABASE SCHEMA
-- ============================================================================
-- This schema is ready for deployment on a fresh system
-- No fingerprint or QR code tables - attendance is CSV-based only
-- All time ranges finalized, leave integration complete
-- ============================================================================
