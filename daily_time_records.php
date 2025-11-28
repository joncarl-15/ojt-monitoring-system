<?php
require_once 'config.php';
require_login();

$student = get_student_info($_SESSION['user_id'], $conn);

if (!$student) {
    header("Location: dashboard.php");
    exit;
}

// Get today's DTR record
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? AND record_date = ?");
$stmt->bind_param("is", $student['student_id'], $today);
$stmt->execute();
$today_record = $stmt->get_result()->fetch_assoc();

$message = '';
$error = '';

// Handle time in/out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'time_in' && !$today_record) {
        $time_in = date('Y-m-d H:i:s');
        $status = 'present';

        $stmt = $conn->prepare("INSERT INTO daily_time_records (student_id, record_date, time_in, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $student['student_id'], $today, $time_in, $status);

        if ($stmt->execute()) {
            $message = "✓ Time In recorded at " . date('H:i:s');
            $today_record = ['time_in' => $time_in, 'time_out' => null, 'daily_hours' => null];
        } else {
            $error = "Error recording time in: " . $stmt->error;
        }
    } elseif ($action == 'time_out' && $today_record && !$today_record['time_out']) {
        $time_out = date('Y-m-d H:i:s');
        $time_in = new DateTime($today_record['time_in']);
        $time_out_obj = new DateTime($time_out);
        $interval = $time_in->diff($time_out_obj);
        $daily_hours = ($interval->h + ($interval->i / 60));

        $stmt = $conn->prepare("UPDATE daily_time_records SET time_out = ?, daily_hours = ? WHERE dtr_id = ?");
        $stmt->bind_param("sdi", $time_out, $daily_hours, $today_record['dtr_id']);

        if ($stmt->execute()) {
            $message = "✓ Time Out recorded at " . date('H:i:s') . " | Hours: " . number_format($daily_hours, 2);
            $today_record['time_out'] = $time_out;
            $today_record['daily_hours'] = $daily_hours;
        } else {
            $error = "Error recording time out: " . $stmt->error;
        }
    }
}

// Get recent records
$recent_stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? ORDER BY record_date DESC LIMIT 10");
$recent_stmt->bind_param("i", $student['student_id']);
$recent_stmt->execute();
$recent_records = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time In/Out - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Time In/Out</h1>
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

        <div class="card slide-up" style="margin-bottom: 2rem;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Today's Time Record</h2>

            <div
                style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; text-align: center;">
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-size: 0.875rem; text-transform: uppercase;">Time
                        In</strong>
                    <span
                        style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $today_record ? date('h:i A', strtotime($today_record['time_in'])) : 'Not yet'; ?></span>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-size: 0.875rem; text-transform: uppercase;">Time
                        Out</strong>
                    <span
                        style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $today_record && $today_record['time_out'] ? date('h:i A', strtotime($today_record['time_out'])) : 'Not yet'; ?></span>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-size: 0.875rem; text-transform: uppercase;">Hours</strong>
                    <span
                        style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $today_record && $today_record['daily_hours'] ? number_format($today_record['daily_hours'], 2) : '-'; ?></span>
                </div>
            </div>

            <form method="POST" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" name="action" value="time_in" class="btn"
                    style="flex: 1; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                    <?php echo $today_record ? 'disabled' : ''; ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 12L19 12M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    TIME IN
                </button>
                <button type="submit" name="action" value="time_out" class="btn btn-danger"
                    style="flex: 1; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                    <?php echo !$today_record || $today_record['time_out'] ? 'disabled' : ''; ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 12H3M3 12L10 5M3 12L10 19" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    TIME OUT
                </button>
            </form>
        </div>

        <div class="card slide-up" style="animation-delay: 0.1s;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Recent Records</h2>
            <?php if (empty($recent_records)): ?>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                    <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $record['daily_hours'] ? number_format($record['daily_hours'], 2) : '-'; ?>
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