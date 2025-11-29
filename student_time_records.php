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

// Get daily time records
$stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? ORDER BY record_date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_hours = array_sum(array_map(function ($r) {
    return $r['daily_hours'] ?? 0; }, $records));
$present_count = count(array_filter($records, function ($r) {
    return $r['status'] == 'present'; }));
$absent_count = count(array_filter($records, function ($r) {
    return $r['status'] == 'absent'; }));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Time Records - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="view_students.php" class="btn btn-secondary"
                style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr; Back to Students</a>
            <h1>Student Time Records</h1>
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
                <h3>Total Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
            </div>
            <div class="stat-card slide-up" style="animation-delay: 0.2s;">
                <h3>Present Days</h3>
                <div class="stat-value"><?php echo $present_count; ?></div>
            </div>
            <div class="stat-card slide-up" style="animation-delay: 0.3s;">
                <h3>Absent Days</h3>
                <div class="stat-value"><?php echo $absent_count; ?></div>
            </div>
        </div>

        <div class="card slide-up" style="animation-delay: 0.4s;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Daily Time Records</h2>
            <?php if (empty($records)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No time records found.</div>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td style="font-weight: 500; color: var(--text-primary);">
                                        <?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                    <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?>
                                    </td>
                                    <td style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo $record['daily_hours'] ? number_format($record['daily_hours'], 2) : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
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