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
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Activity Logs</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge">
                <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        </div>
    </header>

    <div class="main-content fade-in">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="grid-container" style="grid-template-columns: 1fr 1.5fr;">
            <div class="card slide-up">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Add New Activity Log</h2>

                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="week_starting">Week Starting</label>
                            <input type="date" id="week_starting" name="week_starting" required
                                value="<?php echo isset($_POST['week_starting']) ? htmlspecialchars($_POST['week_starting']) : ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="week_ending">Week Ending</label>
                            <input type="date" id="week_ending" name="week_ending" required
                                value="<?php echo isset($_POST['week_ending']) ? htmlspecialchars($_POST['week_ending']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="task_description">Task Description</label>
                        <textarea id="task_description" name="task_description" required
                            placeholder="Describe the tasks you completed during this period..."
                            style="min-height: 100px;"><?php echo isset($_POST['task_description']) ? htmlspecialchars($_POST['task_description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="hours_rendered">Hours Rendered</label>
                        <input type="number" id="hours_rendered" name="hours_rendered" step="0.25" min="0" required
                            value="<?php echo isset($_POST['hours_rendered']) ? htmlspecialchars($_POST['hours_rendered']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="accomplishments">Accomplishments</label>
                        <textarea id="accomplishments" name="accomplishments"
                            placeholder="What did you accomplish? (Optional)"
                            style="min-height: 80px;"><?php echo isset($_POST['accomplishments']) ? htmlspecialchars($_POST['accomplishments']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Submit Activity Log</button>
                </form>
            </div>

            <div class="card slide-up" style="animation-delay: 0.1s;">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Your Activity Logs</h2>

                <?php if (empty($activity_logs)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-light);">No activity logs yet.</div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($activity_logs as $log): ?>
                            <div
                                style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="font-size: 1.1rem; margin-bottom: 0.25rem; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($log['task_description']); ?></h3>
                                        <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($log['week_starting'])); ?> -
                                            <?php echo date('M d, Y', strtotime($log['week_ending'])); ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $log['status']; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </div>

                                <?php if (!empty($log['accomplishments'])): ?>
                                    <div style="margin-bottom: 1rem; color: var(--text-secondary); font-size: 0.95rem;">
                                        <strong style="color: var(--primary-dark);">Accomplishments:</strong>
                                        <?php echo htmlspecialchars($log['accomplishments']); ?>
                                    </div>
                                <?php endif; ?>

                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem;">
                                    <div style="color: var(--primary-color); font-weight: 600;">Hours:
                                        <?php echo number_format($log['hours_rendered'], 2); ?></div>
                                    <div style="color: var(--text-light);">
                                        Posted: <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>