<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <div class="login-box slide-up" style="max-width: 600px;">
            <div class="login-header">
                <h1>System Setup</h1>
                <p>Initializing OJT Monitoring System</p>
            </div>

            <div
                style="text-align: left; margin-bottom: 2rem; background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid #e5e7eb;">
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
                    die("<p style='color: var(--danger-color);'>✗ Connection failed: " . $conn_setup->connect_error . "</p>");
                }

                // Create database if it doesn't exist
                $sql_db = "CREATE DATABASE IF NOT EXISTS " . $db_name;
                if ($conn_setup->query($sql_db) === TRUE) {
                    echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Database created or already exists</p>";
                } else {
                    echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error creating database: " . $conn_setup->error . "</p>";
                }

                // Select the database
                $conn_setup->select_db($db_name);

                // Read the schema file
                $schema_file = __DIR__ . '/database_schema.sql';
                if (!file_exists($schema_file)) {
                    die("<p style='color: var(--danger-color);'>✗ database_schema.sql not found!</p>");
                }

                $schema = file_get_contents($schema_file);

                // Execute schema
                if ($conn_setup->multi_query($schema)) {
                    echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Database schema created successfully</p>";

                    // Consume all results
                    do {
                        if ($result = $conn_setup->store_result()) {
                            $result->free();
                        }
                    } while ($conn_setup->next_result());
                } else {
                    echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error executing schema: " . $conn_setup->error . "</p>";
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
                        echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Created user: " . htmlspecialchars($user[0]) . " (" . $user[3] . ")</p>";
                    } else {
                        if (strpos($stmt->error, 'Duplicate') !== false) {
                            echo "<p style='color: #d97706; margin-bottom: 0.5rem;'>⚠ User already exists: " . htmlspecialchars($user[0]) . "</p>";
                        } else {
                            echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error creating user: " . $stmt->error . "</p>";
                        }
                    }
                }

                // Create a demo student profile (linked to student1)
                $student_stmt = $conn_setup->prepare("INSERT INTO students (user_id, first_name, last_name, middle_name, course, year_level, contact_number, email_address) SELECT user_id, 'John', 'Doe', 'Sample', 'Bachelor of Science in Information Technology', '3rd Year', '09123456789', 'student1@ojt.com' FROM users WHERE username = 'student1' LIMIT 1");

                if ($student_stmt->execute()) {
                    echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Created demo student profile</p>";
                } else {
                    if (strpos($student_stmt->error, 'Duplicate') !== false || strpos($student_stmt->error, 'foreign key') !== false) {
                        echo "<p style='color: #d97706; margin-bottom: 0.5rem;'>⚠ Student profile already exists or configuration issue</p>";
                    }
                }

                $conn_setup->close();
                ?>
            </div>

            <div class="alert alert-success" style="text-align: left; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Setup Complete!</h3>
                <p style="margin-bottom: 1rem;">You can now log in with the following demo credentials:</p>
                <ul style="list-style-type: none; padding: 0;">
                    <li style="margin-bottom: 0.5rem;"><strong>Admin:</strong> admin / password123</li>
                    <li style="margin-bottom: 0.5rem;"><strong>Student:</strong> student1 / password123</li>
                    <li style="margin-bottom: 0.5rem;"><strong>Coordinator:</strong> coordinator1 / password123</li>
                </ul>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="index.php" class="btn">Go to Login</a>
                <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</body>

</html>