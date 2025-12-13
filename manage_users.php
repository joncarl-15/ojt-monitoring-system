<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}


// Handle POST actions: delete or update user
$manage_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action'])) {
        // ignore
    } else {
        $action = $_POST['action'];
        if ($action === 'delete_user') {
            $del_id = intval($_POST['user_id'] ?? 0);
            if ($del_id === $_SESSION['user_id']) {
                $manage_message = "Cannot delete your own account while logged in.";
            } else {
                $stmt_del = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt_del->bind_param("i", $del_id);
                if ($stmt_del->execute()) {
                    $manage_message = "User deleted successfully.";
                } else {
                    $manage_message = "Error deleting user: " . $stmt_del->error;
                }
            }
        } elseif ($action === 'update_user') {
            $edit_id = intval($_POST['user_id'] ?? 0);
            $edit_username = trim($_POST['username'] ?? '');
            $edit_email = trim($_POST['email'] ?? '');
            $edit_type = $_POST['user_type'] ?? 'student';
            $edit_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

            if (empty($edit_username) || empty($edit_email)) {
                $manage_message = 'Username and email are required.';
            } else {
                // Check uniqueness of username/email excluding this user
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ? LIMIT 1");
                $check_stmt->bind_param("ssi", $edit_username, $edit_email, $edit_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();
                if ($check_res && $check_res->num_rows > 0) {
                    $manage_message = 'Username or email already in use by another account.';
                } else {
                    // Handle File Upload
                    $file_path = null;
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['profile_picture']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed)) {
                            // Create uploads directory if not exists
                            $upload_dir = 'uploads/profile_pictures/';
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            // Generate unique name
                            $new_filename = 'user_' . $edit_id . '_' . time() . '.' . $ext;
                            $dest = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                                $file_path = $dest;
                            } else {
                                $manage_message = 'Error uploading file.';
                            }
                        } else {
                            $manage_message = 'Invalid file type. Only JPG, PNG, GIF allowed.';
                        }
                    }

                    // Update Query
                    if ($manage_message === '' || strpos($manage_message, 'Error') === false) {
                        if ($file_path) {
                            $upd = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ?, is_active = ?, profile_picture = ? WHERE user_id = ?");
                            $upd->bind_param("sssisi", $edit_username, $edit_email, $edit_type, $edit_active, $file_path, $edit_id);
                        } else {
                            $upd = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ?, is_active = ? WHERE user_id = ?");
                            $upd->bind_param("sssii", $edit_username, $edit_email, $edit_type, $edit_active, $edit_id);
                        }

                        if ($upd->execute()) {
                            $manage_message = 'User updated successfully.';
                        } else {
                            $manage_message = 'Error updating user: ' . $upd->error;
                        }
                    }
                }
            }
        }
    }
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
            <a href="index.php?logout=1" class="btn btn-danger"
                style="padding: 8px 16px; font-size: 0.875rem;">Logout</a>
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="card slide-up">
            <?php if (!empty($manage_message)): ?>
                <div style="margin-bottom:1rem; padding:0.75rem; background:#f1f5f9; border-radius:6px;"><?php echo htmlspecialchars($manage_message); ?></div>
            <?php endif; ?>
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
                                <th>Profile</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($u['profile_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($u['profile_picture']); ?>" alt="DP" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:40px; height:40px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:0.75rem; font-weight:bold;">
                                                <?php echo substr(strtoupper($u['username']), 0, 2); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 500; color: var(--text-primary);"><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="status-badge"
                                            style="background-color: <?php echo $u['user_type'] == 'admin' ? '#fee2e2' : ($u['user_type'] == 'coordinator' ? '#fef3c7' : '#e0f2fe'); ?>; color: <?php echo $u['user_type'] == 'admin' ? '#991b1b' : ($u['user_type'] == 'coordinator' ? '#92400e' : '#075985'); ?>;">
                                            <?php echo ucfirst($u['user_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; color: <?php echo $u['is_active'] ? 'var(--success-color)' : 'var(--danger-color)'; ?>; font-weight: 600; font-size: 0.875rem;">
                                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: currentColor;"></span>
                                            <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--text-light);"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex; gap:0.5rem;">
                                            <a class="btn btn-sm" href="edit_user.php?user_id=<?php echo $u['user_id']; ?>">Edit</a>
                                            <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($u['username']); ?>? This cannot be undone.');">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

// Update Logic Removed (Moved to edit_user.php)
    </div>
    </div>
</body>

</html>