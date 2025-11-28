<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);
$student = null;
$total_hours = 0;

if ($user['user_type'] == 'student') {
    $student = get_student_info($_SESSION['user_id'], $conn);
    if ($student) {
        $total_hours = get_total_hours($student['student_id'], $conn);
    }
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
            <div class="user-badge">
                <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
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
            </div>
        <?php endif; ?>

        <!-- Admin/Coordinator dashboard -->
        <?php if ($user['user_type'] == 'admin' || $user['user_type'] == 'coordinator'): ?>
            <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Admin Panel</h3>
            <div class="grid-container">
                <div class="card stat-card">
                    <h3>Total Students</h3>
                    <div class="stat-value">
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc();
                        echo $count['cnt'];
                        ?>
                    </div>
                </div>

                <div class="card stat-card">
                    <h3>Total Companies</h3>
                    <div class="stat-value">
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as cnt FROM companies")->fetch_assoc();
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
                <a href="student_activity.php" class="btn">Student Activity</a>
                <a href="student_time_records.php" class="btn">Time Records</a>
                <?php if ($user['user_type'] == 'admin'): ?>
                    <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
                    <a href="manage_companies.php" class="btn btn-secondary">Manage Companies</a>
                    <a href="manage_announcements.php" class="btn btn-secondary">Announcements</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Announcements -->
        <?php if (!empty($announcements)): ?>
            <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">Recent
                Announcements</h3>
            <div>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item slide-up">
                        <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                        <div style="color: var(--text-secondary); margin: 0.5rem 0;">
                            <?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-light);">Posted:
                            <?php echo date('M d, Y H:i', strtotime($announcement['posted_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center; color: var(--text-light);">
                <p>No announcements at this time.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>