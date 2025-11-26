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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
        }

        .logout-btn {
            background: #ff6b6b;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #ff5252;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #666;
            line-height: 1.6;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .card-content {
            color: #333;
            line-height: 1.8;
        }

        .card-content strong {
            display: block;
            margin-bottom: 5px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 150px;
        }

        .stat-card h3 {
            color: white;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .announcements {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .announcements h3 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .announcement-item {
            padding: 15px;
            border-left: 4px solid #667eea;
            background-color: #f9f9f9;
            margin-bottom: 15px;
            border-radius: 3px;
        }

        .announcement-item:last-child {
            margin-bottom: 0;
        }

        .announcement-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .announcement-content {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .announcement-date {
            color: #999;
            font-size: 12px;
            margin-top: 8px;
        }

        .section-title {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            margin-top: 30px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #764ba2;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .user-info {
                flex-direction: column;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>OJT Monitoring System</h1>
        <div class="user-info">
            <div class="user-badge">
                <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
            <a href="index.php?logout=1" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p>You are successfully logged in to the OJT Monitoring System. This dashboard provides you with an overview of your information and activities.</p>
        </div>

        <!-- Student-specific dashboard -->
        <?php if ($user['user_type'] == 'student' && $student): ?>
        <h3 class="section-title">Your Profile</h3>
        <div class="dashboard-grid">
            <div class="card">
                <h3>Personal Information</h3>
                <div class="card-content">
                    <strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?><br>
                    <strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?><br>
                    <strong>Year Level:</strong> <?php echo htmlspecialchars($student['year_level']); ?><br>
                    <strong>Contact:</strong> <?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?>
                </div>
            </div>

            <div class="card">
                <h3>Company Assignment</h3>
                <div class="card-content">
                    <strong>Company:</strong> <?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($student['email_address'] ?? 'N/A'); ?>
                </div>
            </div>

            <div class="card stat-card">
                <h3>Total OJT Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
                <div>hours completed</div>
            </div>
        </div>

        <h3 class="section-title">Quick Actions</h3>
        <div class="action-buttons">
            <a href="daily_time_records.php" class="btn">Time In/Out</a>
            <a href="activity_logs.php" class="btn">Activity Logs</a>
            <a href="view_records.php" class="btn btn-secondary">View Records</a>
        </div>
        <?php endif; ?>

        <!-- Admin/Coordinator dashboard -->
        <?php if ($user['user_type'] == 'admin' || $user['user_type'] == 'coordinator'): ?>
        <h3 class="section-title">Admin Panel</h3>
        <div class="dashboard-grid">
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

        <h3 class="section-title">Quick Actions</h3>
        <div class="action-buttons">
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
        <h3 class="section-title">Recent Announcements</h3>
        <div class="announcements">
            <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-item">
                <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                <div class="announcement-content"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></div>
                <div class="announcement-date">Posted: <?php echo date('M d, Y H:i', strtotime($announcement['posted_at'])); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="announcements">
            <p style="color: #999; text-align: center;">No announcements at this time.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
