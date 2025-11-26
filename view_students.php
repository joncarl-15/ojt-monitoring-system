<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin' && $user['user_type'] != 'coordinator') {
    header("Location: dashboard.php");
    exit;
}

// Get all students with their info
$stmt = $conn->prepare("
    SELECT s.*, u.username, c.company_name, 
           COUNT(DISTINCT d.dtr_id) as dtr_count,
           COALESCE(SUM(d.daily_hours), 0) as total_hours
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN companies c ON s.company_id = c.company_id
    LEFT JOIN daily_time_records d ON s.student_id = d.student_id
    GROUP BY s.student_id
    ORDER BY s.first_name ASC
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - OJT Monitoring System</title>
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
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

        .action-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .action-link:hover {
            text-decoration: underline;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h1>View Students</h1>
    </header>

    <div class="container">
        <div class="card">
            <h2>All Students</h2>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, username, or course...">
            </div>

            <?php if (empty($students)): ?>
                <div class="no-data">No students found.</div>
            <?php else: ?>
                <table id="studentsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Company</th>
                            <th>Total Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                            <td><?php echo htmlspecialchars($student['course']); ?></td>
                            <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                            <td><?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo number_format($student['total_hours'], 2); ?> hrs</td>
                            <td>
                                <a href="student_time_records.php?student_id=<?php echo $student['student_id']; ?>" class="action-link">View Time Records</a> | 
                                <a href="student_activity.php?student_id=<?php echo $student['student_id']; ?>" class="action-link">View Activity</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        });
    </script>
</body>
</html>
