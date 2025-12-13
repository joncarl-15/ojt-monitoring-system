<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

// Admin only access
if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

$edit_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = intval($_POST['user_id'] ?? 0);
    $edit_username = trim($_POST['username'] ?? '');
    $edit_email = trim($_POST['email'] ?? '');
    $edit_type = $_POST['user_type'] ?? 'student';
    $edit_active = isset($_POST['is_active']) && $_POST['is_active'] == '1' ? 1 : 0;

    if (empty($edit_username) || empty($edit_email)) {
        $error = 'Username and email are required.';
    } else {
        // Check uniqueness
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ? LIMIT 1");
        $check_stmt->bind_param("ssi", $edit_username, $edit_email, $edit_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Username or email already in use.';
        } else {
            // File Upload
            $file_path = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $upload_dir = 'uploads/profile_pictures/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    $new_filename = 'user_' . $edit_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                        $file_path = $upload_dir . $new_filename;
                    } else {
                        $error = 'Error uploading file.';
                    }
                } else {
                    $error = 'Invalid file type.';
                }
            }

            if (!$error) {
                if ($file_path) {
                    $upd = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ?, is_active = ?, profile_picture = ? WHERE user_id = ?");
                    $upd->bind_param("sssisi", $edit_username, $edit_email, $edit_type, $edit_active, $file_path, $edit_id);
                } else {
                    $upd = $conn->prepare("UPDATE users SET username = ?, email = ?, user_type = ?, is_active = ? WHERE user_id = ?");
                    $upd->bind_param("sssii", $edit_username, $edit_email, $edit_type, $edit_active, $edit_id);
                }
                
                if ($upd->execute()) {
                    $message = 'User updated successfully.';
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        }
    }
}

// Fetch User Data
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
}

if (!$edit_user) {
    header("Location: manage_users.php"); // Redirect if invalid ID
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - OJT Monitoring</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="manage_users.php" class="btn btn-secondary">&larr; Back</a>
            <h1>Edit User</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge"><?php echo ucfirst($user['user_type']); ?> | <?php echo htmlspecialchars($user['username']); ?></div>
        </div>
    </header>

    <div class="main-content fade-in">
        <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card slide-up" style="max-width: 600px; margin: 0 auto;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Editing: <?php echo htmlspecialchars($edit_user['username']); ?></h2>
            
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>User Type</label>
                    <select name="user_type">
                        <option value="student" <?php echo $edit_user['user_type']=='student' ? 'selected' : ''; ?>>Student</option>
                        <option value="coordinator" <?php echo $edit_user['user_type']=='coordinator' ? 'selected' : ''; ?>>Coordinator</option>
                        <option value="admin" <?php echo $edit_user['user_type']=='admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Profile Picture</label>
                    <?php if (!empty($edit_user['profile_picture'])): ?>
                        <div style="margin-bottom:0.5rem;">
                            <img src="<?php echo htmlspecialchars($edit_user['profile_picture']); ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="profile_picture" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Active Status</label>
                    <select name="is_active">
                        <option value="1" <?php echo $edit_user['is_active'] ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo !$edit_user['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div style="display:flex; gap:1rem; margin-top:2rem;">
                    <button class="btn" type="submit" style="flex:1;">Save Changes</button>
                    <a class="btn btn-secondary" href="manage_users.php" style="flex:1; text-align:center;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
