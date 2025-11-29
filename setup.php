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

                // Remove SQL comments and parse statements safely
                $lines = explode("\n", $schema);
                $clean_schema = "";
                foreach ($lines as $line) {
                    // Remove line comments (-- comment)
                    if (strpos(trim($line), '--') === 0) {
                        continue; // Skip comment-only lines
                    }
                    // Remove inline comments
                    $line = preg_replace('/--.*$/', '', $line);
                    $clean_schema .= $line . "\n";
                }

                // Split by semicolon and execute each statement
                $statements = array_filter(array_map('trim', explode(';', $clean_schema)));
                $schema_ok = true;
                foreach ($statements as $statement) {
                    if (empty($statement)) continue;
                    try {
                        $res = $conn_setup->query($statement);
                        if ($res === TRUE) {
                            // statement executed successfully
                            continue;
                        } else {
                            // Ignore table/DB exists errors, report others
                            $errno = $conn_setup->errno;
                            $error = $conn_setup->error;
                            if ($errno == 1050 || stripos($error, 'already exists') !== false || $errno == 1007) {
                                // table or database already exists - not fatal
                                continue;
                            } else {
                                echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error executing statement: " . htmlspecialchars($error) . "</p>";
                                $schema_ok = false;
                            }
                        }
                    } catch (mysqli_sql_exception $e) {
                        // Catch exceptions from query() and check if they are 'already exists' or 'duplicate key' errors
                        $code = $e->getCode();
                        $msg = $e->getMessage();
                        if ($code == 1050 || stripos($msg, 'already exists') !== false || $code == 1061 || stripos($msg, 'duplicate key') !== false) {
                            // table/database already exists or index/key duplicate - not fatal
                            continue;
                        } else {
                            echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Exception: " . htmlspecialchars($msg) . "</p>";
                            $schema_ok = false;
                        }
                    }
                }
                if ($schema_ok) {
                    echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Database schema processed (existing objects ignored)</p>";
                }

                // Add user_id column to companies table if it doesn't exist (migration)
                $check_column = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'companies' AND COLUMN_NAME = 'user_id' AND TABLE_SCHEMA = '" . $db_name . "'";
                $result = $conn_setup->query($check_column);
                if ($result->num_rows == 0) {
                    // Column doesn't exist, add it
                    $alter_sql = "ALTER TABLE companies ADD COLUMN user_id INT AFTER company_id, ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE";
                    if ($conn_setup->query($alter_sql)) {
                        echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Added user_id column to companies table</p>";
                    } else {
                        echo "<p style='color: #d97706; margin-bottom: 0.5rem;'>⚠ user_id column already exists or migration issue: " . $conn_setup->error . "</p>";
                    }
                }

                // Create coordinators table if it doesn't exist (migration)
                $check_table = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'coordinators' AND TABLE_SCHEMA = '" . $db_name . "'";
                $result = $conn_setup->query($check_table);
                if ($result->num_rows == 0) {
                    // Table doesn't exist, create it
                    $create_coordinators = "CREATE TABLE coordinators (
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
                    )";
                    if ($conn_setup->query($create_coordinators)) {
                        echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Created coordinators table</p>";
                    } else {
                        echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error creating coordinators table: " . $conn_setup->error . "</p>";
                    }
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

                    try {
                        if ($stmt->execute()) {
                            echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Created user: " . htmlspecialchars($user[0]) . " (" . $user[3] . ")</p>";
                        } else {
                            if (strpos($stmt->error, 'Duplicate') !== false) {
                                echo "<p style='color: #d97706; margin-bottom: 0.5rem;'>⚠ User already exists: " . htmlspecialchars($user[0]) . "</p>";
                            } else {
                                echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error creating user: " . $stmt->error . "</p>";
                            }
                        }
                    } catch (mysqli_sql_exception $e) {
                        if (stripos($e->getMessage(), 'duplicate') !== false) {
                            echo "<p style='color: #d97706; margin-bottom: 0.5rem;'>⚠ User already exists: " . htmlspecialchars($user[0]) . "</p>";
                        } else {
                            echo "<p style='color: var(--danger-color); margin-bottom: 0.5rem;'>✗ Error creating user: " . htmlspecialchars($e->getMessage()) . "</p>";
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

                // Create a demo company for the coordinator
                $coordinator_stmt = $conn_setup->prepare("INSERT INTO coordinators (user_id, company_name, company_address, contact_number, email) SELECT user_id, 'Tech Solutions Inc.', '123 Business Street, City, Country', '09987654321', 'coordinator@ojt.com' FROM users WHERE username = 'coordinator1' LIMIT 1");

                if ($coordinator_stmt->execute()) {
                    echo "<p style='color: var(--success-color); margin-bottom: 0.5rem;'>✓ Created demo coordinator profile</p>";
                } else {
                    if (strpos($coordinator_stmt->error, 'Duplicate') !== false || strpos($coordinator_stmt->error, 'foreign key') !== false) {
                        echo "<p style='color: #d97706; margin-bottom: 0.5rem;'>⚠ Coordinator profile already exists or configuration issue</p>";
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