<?php
require_once 'config.php';

// Database Configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'database_schema';

// Create connection
$conn_setup = new mysqli($db_host, $db_user, $db_pass);

if ($conn_setup->connect_error) {
    die("Connection failed: " . $conn_setup->connect_error);
}

// Create database if it doesn't exist
$sql_db = "CREATE DATABASE IF NOT EXISTS " . $db_name;
if ($conn_setup->query($sql_db) === TRUE) {
    echo "<p style='color: green;'>✓ Database created or already exists</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating database: " . $conn_setup->error . "</p>";
}

// Select the database
$conn_setup->select_db($db_name);

// Read the schema file
$schema_file = __DIR__ . '/database_schema.sql';
if (!file_exists($schema_file)) {
    die("<p style='color: red;'>✗ database_schema.sql not found!</p>");
}

$schema = file_get_contents($schema_file);

// Execute schema
if ($conn_setup->multi_query($schema)) {
    echo "<p style='color: green;'>✓ Database schema created successfully</p>";
    
    // Consume all results
    do {
        if ($result = $conn_setup->store_result()) {
            $result->free();
        }
    } while ($conn_setup->next_result());
} else {
    echo "<p style='color: red;'>✗ Error executing schema: " . $conn_setup->error . "</p>";
}

// Create demo users
$demo_users = [
    ['admin', 'admin@ojt.com', 'password123', 'admin'],
    ['student1', 'student1@ojt.com', 'password123', 'student'],
    ['coordinator1', 'coordinator@ojt.com', 'password123', 'coordinator']
];

$stmt = $conn_setup->prepare("INSERT INTO users (username, email, password_hash, user_type, is_active) VALUES (?, ?, ?, ?, 1)");

foreach ($demo_users as $user) {
    $password_hash = password_hash($user[2], PASSWORD_BCRYPT);
    $stmt->bind_param("ssss", $user[0], $user[1], $password_hash, $user[3]);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Created user: " . htmlspecialchars($user[0]) . " (" . $user[3] . ")</p>";
    } else {
        if (strpos($stmt->error, 'Duplicate') !== false) {
            echo "<p style='color: orange;'>⚠ User already exists: " . htmlspecialchars($user[0]) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating user: " . $stmt->error . "</p>";
        }
    }
}

// Create a demo student profile (linked to student1)
$student_stmt = $conn_setup->prepare("INSERT INTO students (user_id, first_name, last_name, middle_name, course, year_level, contact_number, email_address) SELECT user_id, 'John', 'Doe', 'Sample', 'Bachelor of Science in Information Technology', '3rd Year', '09123456789', 'student1@ojt.com' FROM users WHERE username = 'student1' LIMIT 1");

if ($student_stmt->execute()) {
    echo "<p style='color: green;'>✓ Created demo student profile</p>";
} else {
    if (strpos($student_stmt->error, 'Duplicate') !== false || strpos($student_stmt->error, 'foreign key') !== false) {
        echo "<p style='color: orange;'>⚠ Student profile already exists or configuration issue</p>";
    }
}

$conn_setup->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - OJT Monitoring System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .setup-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #667eea;
        }
        p {
            line-height: 1.6;
        }
        .success-box {
            background-color: #d4edda;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            border: 1px solid #c3e6cb;
        }
        .action-buttons {
            margin-top: 20px;
        }
        a {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>OJT Monitoring System - Setup Complete</h1>
        
        <div class="success-box">
            <h3>Setup Results:</h3>
            <p>The system has been initialized. Check the messages above for details.</p>
            
            <h4>Demo Credentials:</h4>
            <ul>
                <li><strong>Admin:</strong> username: admin | password: password123</li>
                <li><strong>Student:</strong> username: student1 | password: password123</li>
                <li><strong>Coordinator:</strong> username: coordinator1 | password: password123</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="login.php">Go to Login</a>
            <a href="dashboard.php">Go to Dashboard</a>
        </div>
    </div>
</body>
</html>
