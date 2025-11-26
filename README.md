# OJT Monitoring System - Backend Documentation

## Project Overview

The OJT (On-the-Job Training) Monitoring System is a web-based application designed to facilitate the management and tracking of student on-the-job training activities. It provides tools for students to log their training progress, hours worked, and weekly activities, while enabling administrators and OJT coordinators to monitor and manage all student training records.

## Tech Stack

- **Server**: Apache (XAMPP)
- **Backend Language**: PHP
- **Database**: MySQL (XAMPP)
- **Authentication**: Session-based or simple login validation
- **API Style**: RESTful API with PHP

## Database Schema Overview

### Tables Structure

#### 1. **users** - User Authentication & Authorization
- Stores login credentials and user roles
- Supports three user types: student, admin, coordinator
- Fields: user_id, username, email, password_hash, user_type, created_at, updated_at, is_active

#### 2. **students** - Student Profile Management
- Maintains student personal and enrollment information
- Links to company and user accounts
- Fields: student_id, user_id, first_name, last_name, middle_name, course, year_level, contact_number, email_address, address, company_id

#### 3. **companies** - Company Information
- Stores OJT company details and supervisor information
- Fields: company_id, company_name, address, supervisor_name, contact_number, email

#### 4. **daily_time_records** - Daily Time Record (DTR)
- Tracks daily time-in and time-out records
- Automatically computes daily hours worked
- Fields: dtr_id, student_id, record_date, time_in, time_out, daily_hours, status, notes

#### 5. **activity_logs** - Weekly Activity/Log Records
- Records weekly tasks completed and hours rendered
- Maintains submission and approval status
- Fields: activity_id, student_id, week_starting, week_ending, task_description, hours_rendered, accomplishments, status

#### 6. **announcements** - Admin Announcements
- Allows admins/coordinators to post announcements
- Categorizes announcements (events, deadlines, instructions)
- Fields: announcement_id, admin_id, title, content, announcement_type, posted_at, scheduled_date, is_active

#### 7. **hours_summary** - Quick Access Total Hours
- Maintains aggregated hours for each student
- Optimizes dashboard queries
- Fields: summary_id, student_id, total_hours, last_updated

---

## Key Features & Implementation

### 1. User Login & Registration
- **Endpoints**: 
  - `POST /api/auth/register` - Register new student or admin account
  - `POST /api/auth/login` - Authenticate user and return token
  - `POST /api/auth/logout` - Invalidate session
  
- **Logic**:
  - Student and Admin/OJT Coordinator registration with different workflows
  - Password hashing and secure authentication
  - Role-based access control

### 2. Student Profile Management
- **Endpoints**:
  - `GET /api/students/:student_id` - Retrieve student profile
  - `PUT /api/students/:student_id` - Update student information
  - `GET /api/students/:student_id/company` - Get assigned company
  
- **Features**:
  - Manage name, course, year level
  - Update assigned company
  - Maintain contact details

### 3. Company Information
- **Endpoints**:
  - `GET /api/companies` - List all companies
  - `GET /api/companies/:company_id` - Get company details
  - `POST /api/companies` - Add new company (Admin only)
  - `PUT /api/companies/:company_id` - Update company info (Admin only)
  
- **Features**:
  - Store supervisor and contact information
  - Manage company details

### 4. Daily Time Record (DTR)
- **Endpoints**:
  - `POST /api/dtr/clock-in` - Record time-in
  - `POST /api/dtr/clock-out` - Record time-out
  - `GET /api/dtr/:student_id` - Get DTR records
  - `GET /api/dtr/:student_id/summary` - Get total hours summary
  
- **Features**:
  - Automatic daily hours computation based on time-in and time-out
  - Daily and monthly hours summary
  - Track attendance status (present, absent, late, pending)

### 5. Activity / Weekly Log
- **Endpoints**:
  - `POST /api/activities` - Create weekly activity log
  - `GET /api/activities/:student_id` - Retrieve activities
  - `PUT /api/activities/:activity_id` - Update activity
  - `GET /api/activities/:activity_id/summary` - Get weekly hours summary
  
- **Features**:
  - Students write tasks completed each week
  - Track hours rendered per week
  - Submit for admin approval
  - Status tracking (draft, submitted, approved, rejected)

### 6. Announcements
- **Endpoints**:
  - `POST /api/announcements` - Create announcement (Admin/Coordinator only)
  - `GET /api/announcements` - Retrieve all announcements
  - `GET /api/announcements/:announcement_id` - Get single announcement
  - `PUT /api/announcements/:announcement_id` - Update announcement
  - `DELETE /api/announcements/:announcement_id` - Delete announcement
  
- **Features**:
  - Post announcements for events, deadlines, and instructions
  - Categorize by type (event, deadline, instruction, general)
  - Set active/inactive status
  - Students see announcements on dashboard

### 7. Admin Dashboard
- **Endpoints**:
  - `GET /api/admin/students` - List all students with filters
  - `GET /api/admin/students/:student_id/hours` - View student hours
  - `GET /api/admin/students/:student_id/activities` - View activity logs
  - `GET /api/admin/dashboard/summary` - Dashboard statistics
  
- **Features**:
  - View all students with company assignments
  - Monitor hours worked per student
  - Review weekly activity logs and accomplishments
  - Generate reports and statistics

---

## Database Setup Instructions

### Prerequisites
- MySQL 5.7 or higher
- Database client (MySQL Workbench, DBeaver, or command line)

### Installation Steps

1. **Create Database**
   ```sql
   CREATE DATABASE ojt_monitoring_system;
   USE ojt_monitoring_system;
   ```

2. **Import Schema**
   - Execute the `database_schema.sql` file to create all tables and indexes
   ```bash
   mysql -u root -p ojt_monitoring_system < database_schema.sql
   ```

3. **Verify Tables**
   ```sql
   SHOW TABLES;
   ```

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data here
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error_code": "ERROR_CODE",
  "details": {}
}
```

---

## Authentication & Authorization

- **Authentication Method**: JWT (JSON Web Token) or Session-based
- **Token Expiration**: [Configure as per requirements]
- **User Types**:
  - **Student**: Can view own profile, submit DTR, submit activities, view announcements
  - **Admin**: Full access to all features, can manage users and companies
  - **Coordinator**: Can manage students and approvals

---

## Relationships & Constraints

```
users (1) -------- (1) students
              └---- (1) admin users

students (N) -------- (1) companies
         └---- (N) daily_time_records
         └---- (N) activity_logs

announcements (N) -------- (1) users (admin)
```

---

## Contributors

- **Jon Carlo Marasigan** (Lead)
- **Renz Matthew Simeon**
- **Carlos Velando**
- **Enrico Miguel Martinez**

---

## Contact & Support

For questions or issues related to the OJT Monitoring System, please contact the development team.

**Last Updated**: November 15, 2025
