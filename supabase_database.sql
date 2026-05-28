-- MabiniHub Supabase/PostgreSQL schema
-- Paste this whole file in Supabase SQL Editor, then run it once.

CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  lastname VARCHAR(100) NOT NULL,
  firstname VARCHAR(100) NOT NULL,
  mi CHAR(1),
  department VARCHAR(100) NOT NULL,
  position VARCHAR(50) NOT NULL DEFAULT 'Permanent',
  role VARCHAR(50) NOT NULL DEFAULT 'employee',
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  contact_no VARCHAR(50) DEFAULT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  profile_picture TEXT,
  employee_id VARCHAR(100) DEFAULT NULL UNIQUE,
  vacation_leave NUMERIC(10,2) DEFAULT 15.00,
  sick_leave NUMERIC(10,2) DEFAULT 15.00,
  gender CHAR(1) DEFAULT NULL,
  can_apply_leave SMALLINT DEFAULT 1,
  is_archived SMALLINT NOT NULL DEFAULT 0,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  signature_path VARCHAR(255) DEFAULT NULL,
  signature TEXT DEFAULT NULL,
  sig_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT users_position_check CHECK (position IN ('Permanent','Casual','JO','OJT')),
  CONSTRAINT users_role_check CHECK (role IN ('employee','department_head','hr')),
  CONSTRAINT users_status_check CHECK (status IN ('pending','approved','declined')),
  CONSTRAINT users_gender_check CHECK (gender IS NULL OR gender IN ('M','F'))
);

CREATE INDEX IF NOT EXISTS idx_users_employee_id ON users(employee_id);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_department ON users(department);

CREATE TABLE IF NOT EXISTS employees (
  id SERIAL PRIMARY KEY,
  email VARCHAR(100) UNIQUE,
  department VARCHAR(100),
  lastname VARCHAR(100),
  firstname VARCHAR(100),
  middlename VARCHAR(100),
  position VARCHAR(100),
  salary NUMERIC(12,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS events (
  id SERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  time VARCHAR(50),
  location VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  is_archived SMALLINT NOT NULL DEFAULT 0,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_events_date ON events(date);

CREATE TABLE IF NOT EXISTS leave_requests (
  id SERIAL PRIMARY KEY,
  employee_email VARCHAR(100) NOT NULL,
  dept_head_email VARCHAR(100) NOT NULL,
  leave_type VARCHAR(255) NOT NULL,
  dates VARCHAR(255) NOT NULL,
  reason TEXT,
  signature_path VARCHAR(255) DEFAULT NULL,
  details TEXT DEFAULT NULL,
  request_token VARCHAR(100) NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  decline_reason TEXT DEFAULT NULL,
  approved_by_dept_head SMALLINT NOT NULL DEFAULT 0,
  approved_by_hr SMALLINT NOT NULL DEFAULT 0,
  approved_by_municipal SMALLINT NOT NULL DEFAULT 0,
  municipal_approval_date TIMESTAMP NULL DEFAULT NULL,
  recommendation VARCHAR(50) DEFAULT NULL,
  disapproval_reason1 VARCHAR(255) DEFAULT NULL,
  disapproval_reason2 VARCHAR(255) DEFAULT NULL,
  disapproval_reason3 VARCHAR(255) DEFAULT NULL,
  certification_date DATE DEFAULT NULL,
  vl_total_earned NUMERIC(10,2) DEFAULT NULL,
  vl_less_this_application NUMERIC(10,2) DEFAULT NULL,
  vl_balance NUMERIC(10,2) DEFAULT NULL,
  sl_total_earned NUMERIC(10,2) DEFAULT NULL,
  sl_less_this_application NUMERIC(10,2) DEFAULT NULL,
  sl_balance NUMERIC(10,2) DEFAULT NULL,
  approved_days_with_pay VARCHAR(50) DEFAULT NULL,
  approved_days_without_pay VARCHAR(50) DEFAULT NULL,
  approved_others VARCHAR(255) DEFAULT NULL,
  disapproved_reason VARCHAR(255) DEFAULT NULL,
  authorized_official VARCHAR(255) DEFAULT NULL,
  applied_at TIMESTAMP NOT NULL,
  is_archived SMALLINT NOT NULL DEFAULT 0,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT uq_request_token UNIQUE (request_token),
  CONSTRAINT leave_requests_status_check CHECK (status IN ('pending','approved','declined','recall'))
);

CREATE INDEX IF NOT EXISTS idx_leave_employee_email ON leave_requests(employee_email);
CREATE INDEX IF NOT EXISTS idx_leave_status ON leave_requests(status);
CREATE INDEX IF NOT EXISTS idx_leave_approved_by_hr ON leave_requests(approved_by_hr);
CREATE INDEX IF NOT EXISTS idx_leave_approved_by_municipal ON leave_requests(approved_by_municipal);

CREATE TABLE IF NOT EXISTS employee_leave_credits_override (
  id SERIAL PRIMARY KEY,
  employee_email VARCHAR(100) NOT NULL,
  leave_type VARCHAR(255) NOT NULL,
  override_credits NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  updated_by VARCHAR(100) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_override UNIQUE (employee_email, leave_type)
);

CREATE INDEX IF NOT EXISTS idx_override_employee_email ON employee_leave_credits_override(employee_email);
CREATE INDEX IF NOT EXISTS idx_override_leave_type ON employee_leave_credits_override(leave_type);

CREATE TABLE IF NOT EXISTS employee_signatures (
  id SERIAL PRIMARY KEY,
  employee_email VARCHAR(100) NOT NULL UNIQUE,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS dept_head_signatures (
  id SERIAL PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS hr_signatures (
  id SERIAL PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS municipal_signatures (
  id SERIAL PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS notifications (
  id SERIAL PRIMARY KEY,
  recipient_email VARCHAR(150),
  recipient_role VARCHAR(100),
  message TEXT NOT NULL,
  type VARCHAR(50) DEFAULT 'recall',
  is_read SMALLINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_notifications_recipient_email ON notifications(recipient_email);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);

CREATE TABLE IF NOT EXISTS tasks (
  id SERIAL PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  due_date TIMESTAMP DEFAULT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  assigned_to_email VARCHAR(100) NOT NULL,
  assigned_by_email VARCHAR(100) NOT NULL,
  attachment_path VARCHAR(255) DEFAULT NULL,
  submission_file_path VARCHAR(255) DEFAULT NULL,
  submission_note TEXT DEFAULT NULL,
  adjustment_note TEXT DEFAULT NULL,
  completed_at TIMESTAMP DEFAULT NULL,
  ack_note TEXT DEFAULT NULL,
  ack_at TIMESTAMP DEFAULT NULL,
  is_archived SMALLINT NOT NULL DEFAULT 0,
  archived_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT tasks_status_check CHECK (status IN ('pending','in_progress','completed','missed'))
);

CREATE INDEX IF NOT EXISTS idx_tasks_assigned_to ON tasks(assigned_to_email);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_by ON tasks(assigned_by_email);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON tasks(due_date);

CREATE TABLE IF NOT EXISTS attendance (
  id SERIAL PRIMARY KEY,
  employee_id VARCHAR(100) NOT NULL,
  date DATE NOT NULL,
  am_in TIMESTAMP DEFAULT NULL,
  am_out TIMESTAMP DEFAULT NULL,
  pm_in TIMESTAMP DEFAULT NULL,
  pm_out TIMESTAMP DEFAULT NULL,
  status VARCHAR(20) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT unique_attendance UNIQUE (employee_id, date)
);

CREATE INDEX IF NOT EXISTS idx_attendance_employee_id ON attendance(employee_id);
CREATE INDEX IF NOT EXISTS idx_attendance_date ON attendance(date);
CREATE INDEX IF NOT EXISTS idx_attendance_status ON attendance(status);

CREATE TABLE IF NOT EXISTS department_heads (
  id SERIAL PRIMARY KEY,
  email VARCHAR(100) NOT NULL UNIQUE,
  department VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_department_heads_department ON department_heads(department);
CREATE INDEX IF NOT EXISTS idx_department_heads_email ON department_heads(email);

CREATE TABLE IF NOT EXISTS system_config (
  config_key VARCHAR(100) PRIMARY KEY,
  config_value TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DO $$
DECLARE
  table_name text;
BEGIN
  FOREACH table_name IN ARRAY ARRAY[
    'users',
    'employees',
    'events',
    'leave_requests',
    'employee_leave_credits_override',
    'employee_signatures',
    'dept_head_signatures',
    'hr_signatures',
    'municipal_signatures',
    'tasks',
    'department_heads',
    'system_config'
  ]
  LOOP
    EXECUTE format('DROP TRIGGER IF EXISTS trg_%I_updated_at ON %I', table_name, table_name);
    EXECUTE format('CREATE TRIGGER trg_%I_updated_at BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION set_updated_at()', table_name, table_name);
  END LOOP;
END $$;
