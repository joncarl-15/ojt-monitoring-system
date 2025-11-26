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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #667eea;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
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
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-present {
            background-color: #d4edda;
            color: #155724;
        }

        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-late {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-pending {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h1>Your Records</h1>
    </header>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 1); ?></div>
                <div class="stat-label">from daily records</div>
            </div>
            <div class="stat-card">
                <h3>Present Days</h3>
                <div class="stat-value"><?php echo $present_count; ?></div>
                <div class="stat-label">days attended</div>
            </div>
            <div class="stat-card">
                <h3>Absent Days</h3>
                <div class="stat-value"><?php echo $absent_count; ?></div>
                <div class="stat-label">days absent</div>
            </div>
            <div class="stat-card">
                <h3>Activity Hours</h3>
                <div class="stat-value"><?php echo number_format($activity_hours, 1); ?></div>
                <div class="stat-label">from activity logs</div>
            </div>
        </div>

        <div class="card">
            <h2>Daily Time Records</h2>
            <?php if (empty($records)): ?>
                <div class="no-data">No time records yet.</div>
            <?php else: ?>
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
                            <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                            <td><?php echo $record['time_in'] ? date('H:i:s', strtotime($record['time_in'])) : '-'; ?></td>
                            <td><?php echo $record['time_out'] ? date('H:i:s', strtotime($record['time_out'])) : '-'; ?></td>
                            <td><?php echo $record['daily_hours'] ? number_format($record['daily_hours'], 2) : '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
