<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);
$student = get_student_info($_SESSION['user_id'], $conn);

if (!$student) {
    header("Location: dashboard.php");
    exit;
}

// Get statistics
$total_hours = get_total_hours($student['student_id'], $conn);

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM daily_time_records WHERE student_id = ? AND status = 'present'");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$present_count = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM daily_time_records WHERE student_id = ? AND status = 'absent'");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$absent_count = $stmt->get_result()->fetch_assoc()['total'];

// Get all records
$stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? ORDER BY record_date DESC");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get activity logs summary
$stmt = $conn->prepare("SELECT SUM(hours_rendered) as total FROM activity_logs WHERE student_id = ? AND status IN ('submitted', 'approved')");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$activity_hours = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Your Records</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge">
                <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
            <a href="index.php?logout=1" class="btn btn-danger"
                style="padding: 8px 16px; font-size: 0.875rem;">Logout</a>
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="grid-container">
            <div class="card stat-card">
                <h3>Total Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">from daily records</div>
            </div>
            <div class="card stat-card"
                style="background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-dark) 100%);">
                <h3>Present Days</h3>
                <div class="stat-value"><?php echo $present_count; ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">days attended</div>
            </div>
            <div class="card stat-card"
                style="background: linear-gradient(135deg, var(--danger-color) 0%, #b91c1c 100%);">
                <h3>Absent Days</h3>
                <div class="stat-value"><?php echo $absent_count; ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">days absent</div>
            </div>
            <div class="card stat-card"
                style="background: linear-gradient(135deg, var(--warning-color) 0%, #b45309 100%);">
                <h3>Activity Hours</h3>
                <div class="stat-value"><?php echo number_format($activity_hours, 2); ?></div>
                <div style="font-size: 0.875rem; opacity: 0.9;">from activity logs</div>
            </div>
        </div>

        <div class="card slide-up">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Daily Time Records</h2>
            <?php if (empty($records)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No time records yet.</div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                    <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?>
                                    </td>
                                    <td style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo $record['daily_hours'] ? number_format($record['daily_hours'], 2) . ' hrs' : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="daily_time_records.php" class="btn btn-sm btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.75rem; text-decoration: none;">
                                                Manage
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>