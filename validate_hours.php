<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'coordinator') {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $dtr_id = intval($_POST['dtr_id'] ?? 0);

    if ($dtr_id) {
        if ($action == 'approve') {
            $stmt = $conn->prepare("UPDATE daily_time_records SET status = 'present' WHERE dtr_id = ?");
            $stmt->bind_param("i", $dtr_id);
            if ($stmt->execute()) {
                $message = "✓ Record approved (marked Present).";
            } else {
                $error = "Error approving: " . $conn->error;
            }
        } elseif ($action == 'reject') {
            // Rejecting sets it to 'absent' or deletes it? 
            // Let's delete it so they can re-submit, or set to absent. 
            // Users usually want to 'fix' it. Deleting lets them re-time-in. 
            // But let's set to 'absent' to keep audit trail? 
            // Actually, if it's 'pending' validation, maybe it's better to just delete or set to 'absent'.
            // Let's set to 'absent' as per Enum options.
            $stmt = $conn->prepare("UPDATE daily_time_records SET status = 'absent' WHERE dtr_id = ?");
            $stmt->bind_param("i", $dtr_id);
             if ($stmt->execute()) {
                $message = "✓ Record rejected (marked Absent).";
            } else {
                $error = "Error rejecting: " . $conn->error;
            }
        }
    }
}

// Fetch Pending Records for this Coordinator's Students
// 1. Get Coordinator's Company Name
$company_name = '';
$c_stmt = $conn->prepare("SELECT company_name FROM coordinators WHERE user_id = ?");
$c_stmt->bind_param("i", $user['user_id']);
$c_stmt->execute();
$c_res = $c_stmt->get_result();
if ($r = $c_res->fetch_assoc()) {
    $company_name = $r['company_name'];
}

$pending_records = [];
if ($company_name) {
    // Robust Query: Match Students by Company Name
    $sql = "
        SELECT d.*, s.first_name, s.last_name, s.course 
        FROM daily_time_records d
        JOIN students s ON d.student_id = s.student_id
        JOIN companies c ON s.company_id = c.company_id
        WHERE d.status = 'pending'
        AND c.company_name = ?
        ORDER BY d.record_date DESC, d.time_in ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $company_name);
    $stmt->execute();
    $pending_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validate Hours - OJT Monitoring</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">&larr; Back</a>
            <h1>Validate Hours</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge"><?php echo ucfirst($user['user_type']); ?> | <?php echo htmlspecialchars($user['username']); ?></div>
        </div>
    </header>

    <div class="main-content fade-in">
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card slide-up">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Pending Time Records</h2>
            
            <?php if (empty($pending_records)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No pending records to validate.</div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_records as $rec): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']); ?></div>
                                        <div style="font-size: 0.8em; color: var(--text-light);"><?php echo htmlspecialchars($rec['course']); ?></div>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($rec['record_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($rec['time_in'])); ?></td>
                                    <td><?php echo $rec['time_out'] ? date('h:i A', strtotime($rec['time_out'])) : '<span style="color:orange">On-going</span>'; ?></td>
                                    <td><?php echo $rec['daily_hours'] ? number_format($rec['daily_hours'], 2) : '-'; ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <form method="POST">
                                                <input type="hidden" name="dtr_id" value="<?php echo $rec['dtr_id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm" style="background-color: var(--success-color); color: white;">Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                            </form>
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
