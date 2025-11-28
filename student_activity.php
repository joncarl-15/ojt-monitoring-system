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
        if ($log['status'] == 'submitted')
            $submitted_count++;
        if ($log['status'] == 'approved')
            $approved_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Activity - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="view_students.php" class="btn btn-secondary"
                style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr; Back to Students</a>
            <h1>Student Activity Logs</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge">
                <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="card slide-up">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">
                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius);">
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); font-size: 0.875rem; margin-bottom: 0.25rem;">Username</strong>
                    <div style="color: var(--text-primary); font-weight: 500;">
                        <?php echo htmlspecialchars($student['username']); ?></div>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); font-size: 0.875rem; margin-bottom: 0.25rem;">Course</strong>
                    <div style="color: var(--text-primary); font-weight: 500;">
                        <?php echo htmlspecialchars($student['course']); ?></div>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); font-size: 0.875rem; margin-bottom: 0.25rem;">Year
                        Level</strong>
                    <div style="color: var(--text-primary); font-weight: 500;">
                        <?php echo htmlspecialchars($student['year_level']); ?></div>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); font-size: 0.875rem; margin-bottom: 0.25rem;">Company</strong>
                    <div style="color: var(--text-primary); font-weight: 500;">
                        <?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?></div>
                </div>
            </div>
        </div>

        <div class="grid-container" style="margin-bottom: 2rem;">
            <div class="stat-card slide-up" style="animation-delay: 0.1s;">
                <h3>Activity Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
            </div>
            <div class="stat-card slide-up" style="animation-delay: 0.2s;">
                <h3>Submitted</h3>
                <div class="stat-value"><?php echo $submitted_count; ?></div>
            </div>
            <div class="stat-card slide-up" style="animation-delay: 0.3s;">
                <h3>Approved</h3>
                <div class="stat-value"><?php echo $approved_count; ?></div>
            </div>
        </div>

        <div class="card slide-up" style="animation-delay: 0.4s;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Activity Logs</h2>

            <?php if (empty($activity_logs)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No activity logs found.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($activity_logs as $log): ?>
                        <div
                            style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="font-size: 1.1rem; margin-bottom: 0.25rem; color: var(--text-primary);">
                                        <?php echo htmlspecialchars(substr($log['task_description'], 0, 100)); ?></h3>
                                    <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                        <?php echo date('M d, Y', strtotime($log['week_starting'])); ?> -
                                        <?php echo date('M d, Y', strtotime($log['week_ending'])); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo $log['status']; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </div>

                            <div style="margin-bottom: 1rem; color: var(--text-secondary); line-height: 1.6;">
                                <strong style="color: var(--primary-dark);">Task:</strong>
                                <?php echo htmlspecialchars($log['task_description']); ?>
                            </div>

                            <?php if (!empty($log['accomplishments'])): ?>
                                <div style="margin-bottom: 1rem; color: var(--text-secondary); line-height: 1.6;">
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
</body>

</html>