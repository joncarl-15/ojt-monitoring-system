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
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>View Students</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge">
                <?php echo ucfirst($_SESSION['user_type']); ?> | <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="card slide-up">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: var(--primary-dark); margin: 0;">All Students</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name, username, or course..."
                        style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); width: 300px; font-size: 0.9rem;">
                </div>
            </div>

            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No students found.</div>
            <?php else: ?>
                <div class="table-container">
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
                                    <td style="font-weight: 500; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?></td>
                                    <td style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo number_format($student['total_hours'], 2); ?> hrs</td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="student_time_records.php?student_id=<?php echo $student['student_id']; ?>"
                                                class="btn btn-secondary"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Time Records</a>
                                            <a href="student_activity.php?student_id=<?php echo $student['student_id']; ?>"
                                                class="btn btn-secondary"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Activity</a>
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

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function () {
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