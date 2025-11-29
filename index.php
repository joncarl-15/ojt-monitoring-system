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
    $signup_role = isset($_POST['signup_role']) ? trim($_POST['signup_role']) : 'student';
    $course = isset($_POST['course']) ? trim($_POST['course']) : '';
    $company = isset($_POST['company']) ? trim($_POST['company']) : '';
    $company_address = isset($_POST['company_address']) ? trim($_POST['company_address']) : '';
    $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';

    // Validate required fields based on role
    if ($signup_role == 'student') {
        $user_type = 'student';
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($course)) {
            $signup_error = 'All fields are required';
        }
    } else if ($signup_role == 'coordinator') {
        $user_type = 'coordinator';
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($company) || empty($company_address) || empty($contact_number)) {
            $signup_error = 'All fields are required';
        }
    }

    if (empty($signup_error)) {
        if ($password !== $confirm_password) {
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
                        $stmt_student = $conn->prepare("INSERT INTO students (user_id, first_name, last_name, email_address, course) VALUES (?, ?, ?, ?, ?)");
                        // Use username as placeholder for names initially
                        $stmt_student->bind_param("issss", $user_id, $username, $username, $email, $course);
                        $stmt_student->execute();
                    } else if ($user_type == 'coordinator') {
                        // Create coordinator profile
                        $stmt_coordinator = $conn->prepare("INSERT INTO coordinators (user_id, company_name, company_address, contact_number, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt_coordinator->bind_param("issss", $user_id, $company, $company_address, $contact_number, $email);
                        $stmt_coordinator->execute();
                    }

                    $signup_success = 'Account created successfully! You can now login.';
                } else {
                    $signup_error = 'Error creating account. Please try again.';
                }
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

    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-logo">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </div>
            <div class="loading-text">Please wait...</div>
        </div>
    </div>

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
                <a href="#features" class="nav-link">Features</a>
                <a href="#about" class="nav-link">About</a>
            </nav>

            <div class="nav-panel-section">
                <h4>Announcements</h4>
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $a): ?>
                        <div class="panel-announcement">
                            <div class="panel-announcement-title"><?php echo htmlspecialchars($a['title']); ?></div>
                            <div class="panel-announcement-body"><?php echo htmlspecialchars(substr($a['content'], 0, 120)); ?>
                            </div>
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
                <a href="#" class="btn" id="signup-btn">Get Started</a>
                <a href="#" class="btn btn-secondary" id="login-btn">Login</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Powerful Features</h2>
                <p>Everything you need to manage and monitor OJT programs effectively</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M21 12V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3>Attendance Tracking</h3>
                    <p>Monitor student attendance with real-time updates and automated notifications</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3>Progress Reports</h3>
                    <p>Generate comprehensive reports on student performance and achievements</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3>Multi-User Access</h3>
                    <p>Separate dashboards for students, coordinators, and company supervisors</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10 9H9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Document Management</h3>
                    <p>Upload, organize, and track all OJT-related documents in one secure location</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About the System</h2>
                    <p>The OJT Monitoring System is designed to simplify the management of on-the-job training programs. It provides a centralized platform for tracking student progress, managing attendance, and facilitating communication.</p>
                    <p>Our goal is to ensure that students get the most out of their on-the-job training experience while reducing the administrative burden on coordinators and company supervisors.</p>
                </div>
                <div class="about-image">
                    <div class="about-visual">
                        <div class="visual-circle"></div>
                        <div class="visual-rect">
                            <div class="visual-content" style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 6L9 17L4 12" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-weight: 700; color: #1f2937; font-size: 1.1rem;">Optimized</div>
                                    <div style="color: #6b7280; font-size: 0.9rem;">Workflow</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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

            <!-- Role Selection -->
            <div id="signup-role-selection" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 1rem; font-weight: 600; color: var(--text-primary);">I am a:</label>
                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="signup-role-btn" data-role="student" 
                        style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: var(--border-radius); background: white; cursor: pointer; font-weight: 500; transition: all 0.2s ease;">
                        👨‍🎓 Student
                    </button>
                    <button type="button" class="signup-role-btn" data-role="coordinator" 
                        style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: var(--border-radius); background: white; cursor: pointer; font-weight: 500; transition: all 0.2s ease;">
                        🧑‍💼 Coordinator
                    </button>
                </div>
            </div>

            <!-- Signup Form -->
            <form method="POST" action="" id="signup-form" style="display: none;">
                <input type="hidden" name="action" value="signup">
                <input type="hidden" name="signup_role" id="signup_role_input" value="">

                <!-- Common Fields -->
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

                <!-- Student Fields -->
                <div id="student-fields" style="display: none;">
                    <div class="form-group">
                        <label for="course">Course</label>
                        <input type="text" id="course" name="course" placeholder="Enter your course"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;"
                            value="<?php echo isset($_POST['course']) && $_POST['action'] == 'signup' ? htmlspecialchars($_POST['course']) : ''; ?>">
                    </div>
                </div>

                <!-- Coordinator Fields -->
                <div id="coordinator-fields" style="display: none;">
                    <div class="form-group">
                        <label for="company">Company Name</label>
                        <input type="text" id="company" name="company" placeholder="Enter company name"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;"
                            value="<?php echo isset($_POST['company']) && $_POST['action'] == 'signup' ? htmlspecialchars($_POST['company']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="company_address">Company Address</label>
                        <input type="text" id="company_address" name="company_address" placeholder="Enter company address"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;"
                            value="<?php echo isset($_POST['company_address']) && $_POST['action'] == 'signup' ? htmlspecialchars($_POST['company_address']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" placeholder="Enter contact number"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;"
                            value="<?php echo isset($_POST['contact_number']) && $_POST['action'] == 'signup' ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
                    </div>
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
                <button type="button" class="btn-back-icon" id="back-to-role-selection" title="Back to role selection">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                Already have an account? <a href="#" id="switch-to-login"
                    style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Login</a>
            </div>
        </div>
    </div>

    <script>
        // Handle role selection
        document.querySelectorAll('.signup-role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const role = this.dataset.role;
                document.getElementById('signup_role_input').value = role;
                
                // Update button styles
                document.querySelectorAll('.signup-role-btn').forEach(b => {
                    b.style.borderColor = '#e5e7eb';
                    b.style.backgroundColor = 'white';
                    b.style.color = 'var(--text-primary)';
                });
                this.style.borderColor = 'var(--primary-color)';
                this.style.backgroundColor = 'var(--primary-color)';
                this.style.color = 'white';
                
                // Show form
                document.getElementById('signup-role-selection').style.display = 'none';
                document.getElementById('signup-form').style.display = 'block';
                
                // Show/hide role-specific fields
                if (role === 'student') {
                    document.getElementById('student-fields').style.display = 'block';
                    document.getElementById('coordinator-fields').style.display = 'none';
                    document.getElementById('course').required = true;
                    document.getElementById('company').required = false;
                    document.getElementById('company_address').required = false;
                    document.getElementById('contact_number').required = false;
                } else if (role === 'coordinator') {
                    document.getElementById('student-fields').style.display = 'none';
                    document.getElementById('coordinator-fields').style.display = 'block';
                    document.getElementById('course').required = false;
                    document.getElementById('company').required = true;
                    document.getElementById('company_address').required = true;
                    document.getElementById('contact_number').required = true;
                }
            });
        });
        
        // Back button
        document.getElementById('back-to-role-selection').addEventListener('click', function() {
            document.getElementById('signup-form').style.display = 'none';
            document.getElementById('signup-role-selection').style.display = 'block';
            document.querySelectorAll('.signup-role-btn').forEach(b => {
                b.style.borderColor = '#e5e7eb';
                b.style.backgroundColor = 'white';
                b.style.color = 'var(--text-primary)';
            });
        });
    </script>

    <script src="assets/js/main.js"></script>
</body>

</html>