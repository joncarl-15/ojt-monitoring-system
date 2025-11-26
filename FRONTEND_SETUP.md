# OJT Monitoring System - Frontend Setup Guide

## Quick Start

### 1. Initial Setup
Visit `http://localhost/ojt-monitoring-system-project/setup.php` to:
- Create the database and tables
- Add demo user accounts

**Demo Credentials:**
- **Admin**: username: `admin` | password: `password123`
- **Student**: username: `student1` | password: `password123`
- **Coordinator**: username: `coordinator1` | password: `password123`

### 2. Login
Navigate to `http://localhost/ojt-monitoring-system-project/login.php`

## File Structure

```
ojt-monitoring-system-project/
├── config.php                    # Database configuration & helper functions
├── login.php                     # Login page
├── dashboard.php                 # Main dashboard (role-based)
├── daily_time_records.php        # Time In/Out functionality
├── activity_logs.php             # Activity log submission
├── view_records.php              # Student records summary
├── manage_users.php              # Admin: View all users
├── manage_companies.php          # Admin: View all companies
├── manage_announcements.php      # Admin: Create & view announcements
├── setup.php                     # Initial setup (creates DB & demo users)
└── database_schema.sql           # Database schema
```

## Features

### Student Portal
- **Time In/Out**: Record daily working hours
- **Activity Logs**: Submit weekly activity reports
- **View Records**: See attendance and hours summary

### Admin Panel
- **Manage Users**: View all system users
- **Manage Companies**: View company information
- **Announcements**: Post announcements to all users

### Security
- Password hashing using `password_hash()` (BCRYPT)
- Session-based authentication
- User type validation for restricted pages

## Database Configuration

Edit `config.php` if you need to change:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ojt_monitoring_system');
```

## Testing the System

1. Run setup.php first to initialize everything
2. Login with any demo account
3. Student accounts can:
   - Time in/out
   - Submit activity logs
   - View their records
4. Admin accounts can:
   - Manage users
   - View company info
   - Post announcements

## Requirements

- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Troubleshooting

- **Connection Error**: Check DB_HOST, DB_USER, DB_PASS in config.php
- **404 Errors**: Ensure files are in `c:\xampp\htdocs\ojt-monitoring-system-project\`
- **Login Issues**: Run setup.php again to reset demo users

## Next Steps

- Customize styling in CSS sections
- Add more admin features (edit users, delete announcements, etc.)
- Implement email notifications
- Add more comprehensive reports
