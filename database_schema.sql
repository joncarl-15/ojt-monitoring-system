-- OJT Monitoring System Database Schema
-- Created: November 15, 2025

-- Create Users Table (for login & authentication)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('student', 'admin', 'coordinator') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    profile_picture VARCHAR(255) DEFAULT NULL
);

-- Create Companies Table (Company Information) - MUST BE BEFORE STUDENTS
CREATE TABLE companies (
    company_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    company_name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    supervisor_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create Coordinators Table (Coordinator Profile Management)
CREATE TABLE coordinators (
    coordinator_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    company_address TEXT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    department VARCHAR(100),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create Students Table (Student Profile Management)
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    course VARCHAR(150) NOT NULL,
    year_level ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NOT NULL,
    contact_number VARCHAR(20),
    email_address VARCHAR(100),
    address TEXT,
    company_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(company_id)
);

-- Create Daily Time Records Table (DTR)
CREATE TABLE daily_time_records (
    dtr_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    record_date DATE NOT NULL,
    time_in DATETIME,
    time_out DATETIME,
    daily_hours DECIMAL(5, 2),
    status ENUM('present', 'absent', 'late', 'pending') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_date (student_id, record_date)
);

-- Create Activity/Weekly Log Table (Activity Logs)
CREATE TABLE activity_logs (
    activity_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    week_starting DATE NOT NULL,
    week_ending DATE NOT NULL,
    task_description TEXT NOT NULL,
    hours_rendered DECIMAL(5, 2) NOT NULL,
    accomplishments TEXT,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Create Announcements Table
CREATE TABLE announcements (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('event', 'deadline', 'instruction', 'general') DEFAULT 'general',
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    company_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
);

-- Create Hours Summary Table (for quick access to total hours)
CREATE TABLE hours_summary (
    summary_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL UNIQUE,
    total_hours DECIMAL(8, 2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Create indexes for better query performance
CREATE INDEX idx_students_user_id ON students(user_id);
CREATE INDEX idx_students_company_id ON students(company_id);
CREATE INDEX idx_dtr_student_id ON daily_time_records(student_id);
CREATE INDEX idx_dtr_record_date ON daily_time_records(record_date);
CREATE INDEX idx_activity_student_id ON activity_logs(student_id);
CREATE INDEX idx_activity_week_date ON activity_logs(week_starting);
CREATE INDEX idx_announcements_admin_id ON announcements(admin_id);
CREATE INDEX idx_announcements_posted_at ON announcements(posted_at);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_user_type ON users(user_type);
CREATE INDEX idx_coordinators_user_id ON coordinators(user_id);
<<<<<<< HEAD
CREATE INDEX idx_announcements_company_id ON announcements(company_id);
=======

-- Insert a sample coordinator user
INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `user_type`, `created_at`, `updated_at`, `is_active`) VALUES
(4, 'coor', 'coor@example.com', '$2y$10$E/GR.I2.gvpjg2pG1c9wz.VC28sWzHw7T3jXp8E5XwQvV8bE3c3eC', 'coordinator', '2025-11-20 04:54:19', '2025-11-20 04:54:19', 1);

-- Insert a sample coordinator profile
INSERT INTO `coordinators` (`coordinator_id`, `user_id`, `company_name`, `company_address`, `contact_number`, `email`, `department`, `bio`, `created_at`, `updated_at`) VALUES
(1, 4, 'Sample Company', '123 Sample St, Sample City', '123-456-7890', 'coor@example.com', 'HR', 'Sample bio', '2025-11-20 04:54:19', '2025-11-20 04:54:19');
>>>>>>> bfade69d3773c5421359d484017e6090c4cd58d6
