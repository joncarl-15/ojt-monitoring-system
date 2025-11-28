<?php
require_once 'config.php';

$error = '';
$success = '';
$signup_error = '';
$signup_success = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        // Query to get user
        $stmt = $conn->prepare("SELECT user_id, username, password_hash, user_type, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if ($user['is_active'] == 0) {
                $error = 'Account is inactive. Please contact administrator.';
            } elseif (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'signup') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    $user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : 'student';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $signup_error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $signup_error = 'Passwords do not match';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $signup_error = 'Username or email already exists';
        } else {
            // Insert new user
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, user_type, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $username, $email, $password_hash, $user_type);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // If student, create student profile
                if ($user_type == 'student') {
                    $stmt_student = $conn->prepare("INSERT INTO students (user_id, first_name, last_name, email_address) VALUES (?, ?, ?, ?)");
                    // Use username as placeholder for names initially
                    $stmt_student->bind_param("isss", $user_id, $username, $username, $email);
                    $stmt_student->execute();
                }

                $signup_success = 'Account created successfully! You can now login.';
            } else {
                $signup_error = 'Error creating account. Please try again.';
            }
        }
    }
}

// Get recent announcements for the menu panel
$announcements = [];
if (isset($conn)) {
    $announcements = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY posted_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <a href="#" class="logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            OJT Monitoring System
        </a>

        <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open navigation">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <div class="nav-links" id="primary-nav" role="navigation" aria-label="Primary">
            <div class="nav-panel-login-wrapper">
                <a href="#" class="btn btn-secondary nav-panel-login" id="panel-login">Login / Sign Up</a>
            </div>

            <nav class="nav-panel-links">
                <a href="#" class="nav-link">Home</a>
                <a href="#" class="nav-link">Features</a>
                <a href="#" class="nav-link">About</a>
            </nav>

            <div class="nav-panel-section">
                <h4>Announcements</h4>
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="panel-announcement">
                            <div class="panel-announcement-title"><?php echo htmlspecialchars($a['title']); ?></div>
                            <div class="panel-announcement-body"><?php echo htmlspecialchars(substr($a['content'],0,120)); ?></div>
                            <div class="panel-announcement-meta"><?php echo date('M d, Y', strtotime($a['posted_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="color:var(--text-secondary);">No announcements available.</div>
                <?php endif; ?>
            </div>
        </div>

        <div id="nav-overlay" class="nav-overlay"></div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Streamline Your OJT Experience</h1>
            <p>Efficiently track hours, manage activities, and monitor progress with our modern OJT Monitoring System.
                Designed for students, coordinators, and companies.</p>
            <div class="hero-buttons">
                <a href="#" class="btn" onclick="document.getElementById('signup-btn').click()">Get Started</a>
                <a href="#" class="btn btn-secondary" onclick="document.getElementById('login-btn').click()">Login</a>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal-overlay <?php echo ($error || $signup_success) ? 'active' : ''; ?>" id="login-modal">
        <div class="modal-content slide-up">
            <button class="close-modal">&times;</button>

            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please enter your credentials to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($signup_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($signup_success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username"
                        value="<?php echo isset($_POST['username']) && $_POST['action'] == 'login' ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                Don't have an account? <a href="#" id="switch-to-signup"
                    style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Sign up</a>
            </div>

            <div class="test-credentials"
                style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; border-radius: 8px; font-size: 0.875rem; color: #4b5563; text-align: left;">
                <strong style="display: block; margin-bottom: 5px; color: #1f2937;">Test Credentials:</strong>
                <p style="margin-bottom: 4px;">Username: admin</p>
                <p>Password: password123</p>
            </div>
        </div>
    </div>

    <!-- Signup Modal -->
    <div class="modal-overlay <?php echo $signup_error ? 'active' : ''; ?>" id="signup-modal">
        <div class="modal-content slide-up">
            <button class="close-modal">&times;</button>

            <div class="login-header">
                <h2>Create Account</h2>
                <p>Join us to start monitoring your OJT progress</p>
            </div>

            <?php if ($signup_error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($signup_error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="signup">
                <div class="form-group">
                    <label for="signup_username">Username</label>
                    <input type="text" id="signup_username" name="username" required placeholder="Choose a username"
                        value="<?php echo isset($_POST['username']) && $_POST['action'] == 'signup' ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="signup_email">Email Address</label>
                    <input type="email" id="signup_email" name="email" required placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) && $_POST['action'] == 'signup' ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="user_type">I am a</label>
                    <select id="user_type" name="user_type" required
                        style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;">
                        <option value="student">Student</option>
                        <option value="company">Company Representative</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="signup_password">Password</label>
                    <input type="password" id="signup_password" name="password" required
                        placeholder="Create a password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        placeholder="Confirm your password">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Sign Up</button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                Already have an account? <a href="#" id="switch-to-login"
                    style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Login</a>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>

</html>
