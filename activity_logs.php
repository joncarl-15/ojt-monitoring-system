<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);
$student = get_student_info($_SESSION['user_id'], $conn);

if (!$student) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $week_starting = isset($_POST['week_starting']) ? $_POST['week_starting'] : '';
    $week_ending = isset($_POST['week_ending']) ? $_POST['week_ending'] : '';
    $task_description = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
    $hours_rendered = isset($_POST['hours_rendered']) ? floatval($_POST['hours_rendered']) : 0;
    $accomplishments = isset($_POST['accomplishments']) ? trim($_POST['accomplishments']) : '';

    if (empty($week_starting) || empty($week_ending) || empty($task_description) || $hours_rendered <= 0) {
        $error = 'Please fill in all fields correctly';
    } else {
        $stmt = $conn->prepare("INSERT INTO activity_logs (student_id, week_starting, week_ending, task_description, hours_rendered, accomplishments, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
        $stmt->bind_param("issdss", $student['student_id'], $week_starting, $week_ending, $task_description, $hours_rendered, $accomplishments);

        if ($stmt->execute()) {
            $message = "✓ Activity log created successfully";
            $_POST = [];
        } else {
            $error = "Error creating activity log: " . $stmt->error;
        }
    }
}

// Get student's activity logs
$logs_stmt = $conn->prepare("SELECT * FROM activity_logs WHERE student_id = ? ORDER BY week_starting DESC");
$logs_stmt->bind_param("i", $student['student_id']);
$logs_stmt->execute();
$activity_logs = $logs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - OJT Monitoring System</title>
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
            max-width: 1000px;
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

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="date"],
        input[type="number"],
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
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
            align-items: center;
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
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .log-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h1>Activity Logs</h1>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Add New Activity Log</h2>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="week_starting">Week Starting</label>
                        <input type="date" id="week_starting" name="week_starting" required value="<?php echo isset($_POST['week_starting']) ? htmlspecialchars($_POST['week_starting']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="week_ending">Week Ending</label>
                        <input type="date" id="week_ending" name="week_ending" required value="<?php echo isset($_POST['week_ending']) ? htmlspecialchars($_POST['week_ending']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="task_description">Task Description</label>
                    <textarea id="task_description" name="task_description" required placeholder="Describe the tasks you completed during this period..."><?php echo isset($_POST['task_description']) ? htmlspecialchars($_POST['task_description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="hours_rendered">Hours Rendered</label>
                    <input type="number" id="hours_rendered" name="hours_rendered" step="0.25" min="0" required value="<?php echo isset($_POST['hours_rendered']) ? htmlspecialchars($_POST['hours_rendered']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="accomplishments">Accomplishments</label>
                    <textarea id="accomplishments" name="accomplishments" placeholder="What did you accomplish? (Optional)"><?php echo isset($_POST['accomplishments']) ? htmlspecialchars($_POST['accomplishments']) : ''; ?></textarea>
                </div>

                <button type="submit" class="submit-btn">Submit Activity Log</button>
            </form>
        </div>

        <div class="card">
            <h2>Your Activity Logs</h2>
            
            <?php if (empty($activity_logs)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">No activity logs yet.</p>
            <?php else: ?>
                <?php foreach ($activity_logs as $log): ?>
                <div class="activity-log-item">
                    <div class="log-header">
                        <div>
                            <div class="log-title"><?php echo htmlspecialchars($log['task_description']); ?></div>
                            <div class="log-date">
                                <?php echo date('M d, Y', strtotime($log['week_starting'])); ?> - <?php echo date('M d, Y', strtotime($log['week_ending'])); ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $log['status']; ?>">
                            <?php echo ucfirst($log['status']); ?>
                        </span>
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
