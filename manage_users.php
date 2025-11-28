<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Get all users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Manage Users</h1>
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
                <h2 style="color: var(--primary-dark); margin: 0;">All Users</h2>
                <!-- Placeholder for Add User button if needed in future -->
            </div>

            <?php if (empty($users)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No users found.</div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td style="font-weight: 500; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="status-badge"
                                            style="background-color: <?php echo $u['user_type'] == 'admin' ? '#fee2e2' : ($u['user_type'] == 'coordinator' ? '#fef3c7' : '#e0f2fe'); ?>; color: <?php echo $u['user_type'] == 'admin' ? '#991b1b' : ($u['user_type'] == 'coordinator' ? '#92400e' : '#075985'); ?>;">
                                            <?php echo ucfirst($u['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            style="display: inline-flex; align-items: center; gap: 0.25rem; color: <?php echo $u['is_active'] ? 'var(--success-color)' : 'var(--danger-color)'; ?>; font-weight: 600; font-size: 0.875rem;">
                                            <span
                                                style="width: 8px; height: 8px; border-radius: 50%; background-color: currentColor;"></span>
                                            <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-light);">
                                        <?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
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