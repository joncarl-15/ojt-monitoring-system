<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin' && $user['user_type'] != 'coordinator') {
    header("Location: dashboard.php");
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Get student info
$stmt = $conn->prepare("
    SELECT s.*, u.username, c.company_name 
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN companies c ON s.company_id = c.company_id
    WHERE s.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: view_students.php");
    exit;
}

// Get activity logs
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE student_id = ? ORDER BY week_starting DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$activity_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_hours = 0;
$submitted_count = 0;
$approved_count = 0;

foreach ($activity_logs as $log) {
    if ($log['status'] == 'submitted' || $log['status'] == 'approved') {
        $total_hours += $log['hours_rendered'];
        if ($log['status'] == 'submitted') $submitted_count++;
        if ($log['status'] == 'approved') $approved_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Activity - OJT Monitoring System</title>
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 24px;
        }

        .back-link {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            display: inline-block;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .info-item strong {
            display: block;
            color: #667eea;
            margin-bottom: 3px;
        }

        .info-item {
            color: #555;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
        }

        .activity-log-item {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
        }

        .activity-log-item:last-child {
            margin-bottom: 0;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .log-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .log-date {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .log-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .log-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }

        .log-hours {
            color: #667eea;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-draft {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-submitted {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        @media (max-width: 768px) {
            .student-info {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .log-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="view_students.php" class="back-link">← Back to Students</a>
        <h1>Student Activity Logs</h1>
    </header>

    <div class="container">
        <div class="card">
            <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
            
            <div class="student-info">
                <div class="info-item">
                    <strong>Username:</strong>
                    <?php echo htmlspecialchars($student['username']); ?>
                </div>
                <div class="info-item">
                    <strong>Course:</strong>
                    <?php echo htmlspecialchars($student['course']); ?>
                </div>
                <div class="info-item">
                    <strong>Year Level:</strong>
                    <?php echo htmlspecialchars($student['year_level']); ?>
                </div>
                <div class="info-item">
                    <strong>Company:</strong>
                    <?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Activity Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Submitted</h3>
                <div class="stat-value"><?php echo $submitted_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Approved</h3>
                <div class="stat-value"><?php echo $approved_count; ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Activity Logs</h2>
            
            <?php if (empty($activity_logs)): ?>
                <div class="no-data">No activity logs found.</div>
            <?php else: ?>
                <?php foreach ($activity_logs as $log): ?>
                <div class="activity-log-item">
                    <div class="log-header">
                        <div>
                            <div class="log-title"><?php echo htmlspecialchars(substr($log['task_description'], 0, 100)); ?></div>
                            <div class="log-date">
                                <?php echo date('M d, Y', strtotime($log['week_starting'])); ?> - <?php echo date('M d, Y', strtotime($log['week_ending'])); ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $log['status']; ?>">
                            <?php echo ucfirst($log['status']); ?>
                        </span>
                    </div>
                    
                    <div class="log-description">
                        <strong>Task:</strong> <?php echo htmlspecialchars($log['task_description']); ?>
                    </div>

                    <?php if (!empty($log['accomplishments'])): ?>
                    <div class="log-description">
                        <strong>Accomplishments:</strong> <?php echo htmlspecialchars($log['accomplishments']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="log-footer">
                        <div class="log-hours">Hours: <?php echo number_format($log['hours_rendered'], 2); ?></div>
                        <div style="color: #999; font-size: 12px;">
                            Posted: <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
