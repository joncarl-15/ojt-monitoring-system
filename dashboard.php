<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);
$student = null;
$company = null;
$total_hours = 0;

if ($user['user_type'] == 'student') {
    $student = get_student_info($_SESSION['user_id'], $conn);
    if ($student) {
        $total_hours = get_total_hours($student['student_id'], $conn);
    }
} elseif ($user['user_type'] == 'coordinator') {
    $stmt = $conn->prepare("SELECT * FROM coordinators WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
}

// Get announcements
$announcements_query = "SELECT * FROM announcements WHERE is_active = 1 ORDER BY posted_at DESC LIMIT 5";
$announcements = $conn->query($announcements_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <h1>OJT Monitoring System</h1>
        <div class="user-profile">
            <div class="user-badge" style="display:flex; align-items:center; gap:0.5rem;">
                <!-- Profile Link -->
                <a href="profile.php" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:0.5rem;" title="Edit Profile">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border: 2px solid rgba(255,255,255,0.2);">
                    <?php else: ?>
                        <div style="width:32px; height:32px; border-radius:50%; background:rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.8rem;">
                            <?php echo substr(strtoupper($user['username']), 0, 1); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                </a>
            </div>
            <a href="index.php?logout=1" class="btn btn-danger"
                style="padding: 8px 16px; font-size: 0.875rem;">Logout</a>
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="card" style="margin-bottom: 2rem;">
            <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p style="color: var(--text-secondary);">You are successfully logged in to the OJT Monitoring System. This
                dashboard provides you with an overview of your information and activities.</p>
        </div>

        <!-- Student-specific dashboard -->
        <?php if ($user['user_type'] == 'student' && $student): ?>
                <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Your Profile</h3>
                <div class="grid-container">
                    <div class="card">
                        <h3>Personal Information</h3>
                        <div style="line-height: 1.8;">
                            <strong>Name:</strong>
                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                            <strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?><br>
                            <strong>Year Level:</strong> <?php echo htmlspecialchars($student['year_level']); ?><br>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="card">
                        <h3>Company Assignment</h3>
                        <div style="line-height: 1.8;">
                            <strong>Company:</strong>
                            <?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($student['email_address'] ?? 'N/A'); ?>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <h3>Total OJT Hours</h3>
                        <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
                        <div>hours completed</div>
                    </div>
                </div>

                <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Quick Actions</h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                    <a href="daily_time_records.php" class="btn">Time In/Out</a>
                    <a href="activity_logs.php" class="btn">Activity Logs</a>
                    <a href="view_records.php" class="btn btn-secondary">View Records</a>
                    <a href="profile.php" class="btn btn-secondary">My Profile</a>
                </div>
        <?php endif; ?>

        <!-- Coordinator Dashboard -->
        <?php if ($user['user_type'] == 'coordinator' && $company): ?>
                <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Company Profile</h3>
                <div class="grid-container">
                    <div class="card">
                        <h3>Company Information</h3>
                        <div style="line-height: 1.8;">
                            <strong>Name:</strong> <?php echo htmlspecialchars($company['company_name']); ?><br>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($company['contact_number']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($company['email'] ?? 'N/A'); ?><br>
                            <strong>Address:</strong> <?php echo htmlspecialchars($company['company_address']); ?>
                        </div>
                    </div>
                </div>

                <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Quick Actions</h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                    <a href="view_students.php" class="btn">View Students</a>
                    <a href="validate_hours.php" class="btn btn-secondary">Validate Hours</a>
                    <a href="certificates.php" class="btn btn-secondary">Certificates</a>
                    <a href="manage_announcements.php" class="btn btn-secondary">Announcements</a>
                    <a href="profile.php" class="btn btn-secondary">My Profile</a>
                </div>
        <?php endif; ?>

        <!-- Admin Dashboard -->
        <?php if ($user['user_type'] == 'admin'): ?>
                <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Admin Panel</h3>
                <div class="grid-container">
                    <div class="card stat-card">
                        <h3>Total Students</h3>
                        <div class="stat-value">
                            <?php
                            // Count unique student users (one per user_id)
                            $count = $conn->query("SELECT COUNT(DISTINCT s.user_id) as cnt FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.user_type = 'student'")->fetch_assoc();
                            echo $count['cnt'];
                            ?>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <h3>Total Coordinators</h3>
                        <div class="stat-value">
                            <?php
                            // Count companies from coordinators (coordinator users)
                            $count = $conn->query("SELECT COUNT(*) as cnt FROM coordinators c JOIN users u ON c.user_id = u.user_id WHERE u.user_type = 'coordinator'")->fetch_assoc();
                            echo $count['cnt'];
                            ?>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <h3>Users</h3>
                        <div class="stat-value">
                            <?php
                            $count = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc();
                            echo $count['cnt'];
                            ?>
                        </div>
                    </div>
                </div>

                <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Quick Actions</h3>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                    <a href="view_students.php" class="btn">View Students</a>
                    <?php if ($user['user_type'] == 'admin'): ?>
                            <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
                            <a href="certificates.php" class="btn btn-secondary">Certificates</a>
                            <a href="manage_companies.php" class="btn btn-secondary">Manage Companies</a>
                    <?php endif; ?>
                    <?php if ($user['user_type'] == 'coordinator'): ?>
                         <a href="manage_announcements.php" class="btn btn-secondary">Announcements</a>
                    <?php endif; ?>
                </div>
        <?php endif; ?>

    </div>
</body>
</html>