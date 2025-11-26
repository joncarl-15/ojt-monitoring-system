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
$total_hours = array_sum(array_map(function($r) { return $r['daily_hours'] ?? 0; }, $records));
$present_count = count(array_filter($records, function($r) { return $r['status'] == 'present'; }));
$absent_count = count(array_filter($records, function($r) { return $r['status'] == 'absent'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Time Records - OJT Monitoring System</title>
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
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
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
            .student-info {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 13px;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="view_students.php" class="back-link">← Back to Students</a>
        <h1>Student Time Records</h1>
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
                <h3>Total Hours</h3>
                <div class="stat-value"><?php echo number_format($total_hours, 2); ?></div>
            </div>
            <div class="stat-card">
                <h3>Present Days</h3>
                <div class="stat-value"><?php echo $present_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Absent Days</h3>
                <div class="stat-value"><?php echo $absent_count; ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Daily Time Records</h2>
            <?php if (empty($records)): ?>
                <div class="no-data">No time records found.</div>
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
