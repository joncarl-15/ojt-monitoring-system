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
            max-width: 900px;
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

        .current-status {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
        }

        .status-item {
            padding: 15px;
            background: white;
            border-radius: 5px;
        }

        .status-item strong {
            display: block;
            color: #667eea;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .status-item span {
            font-size: 18px;
            color: #333;
        }

        .time-buttons {
            display: flex;
            gap: 10px;
        }

        button {
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-time-in {
            background: #28a745;
            color: white;
            flex: 1;
        }

        .btn-time-in:hover:not(:disabled) {
            background: #218838;
        }

        .btn-time-out {
            background: #dc3545;
            color: white;
            flex: 1;
        }

        .btn-time-out:hover:not(:disabled) {
            background: #c82333;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
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
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h1>Time In/Out</h1>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Today's Time Record</h2>
            
            <div class="current-status">
                <div class="status-item">
                    <strong>Time In</strong>
                    <span><?php echo $today_record ? date('H:i:s', strtotime($today_record['time_in'])) : 'Not yet'; ?></span>
                </div>
                <div class="status-item">
                    <strong>Time Out</strong>
                    <span><?php echo $today_record && $today_record['time_out'] ? date('H:i:s', strtotime($today_record['time_out'])) : 'Not yet'; ?></span>
                </div>
                <div class="status-item">
                    <strong>Hours</strong>
                    <span><?php echo $today_record && $today_record['daily_hours'] ? number_format($today_record['daily_hours'], 2) : '-'; ?></span>
                </div>
            </div>

            <form method="POST" class="time-buttons">
                <button type="submit" name="action" value="time_in" class="btn-time-in" 
                    <?php echo $today_record ? 'disabled' : ''; ?>>
                    ▶ TIME IN
                </button>
                <button type="submit" name="action" value="time_out" class="btn-time-out" 
                    <?php echo !$today_record || $today_record['time_out'] ? 'disabled' : ''; ?>>
                    ⏹ TIME OUT
                </button>
            </form>
        </div>

        <div class="card">
            <h2>Recent Records</h2>
            <?php if (empty($recent_records)): ?>
                <p style="color: #666; text-align: center; padding: 20px;">No time records yet.</p>
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
                        <?php foreach ($recent_records as $record): ?>
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
