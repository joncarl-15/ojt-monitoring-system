<?php
require_once 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Profile Picture Upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = 'uploads/profile_pictures/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest)) {
                // Update DB
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->bind_param("si", $dest, $user_id);
                if ($stmt->execute()) {
                    $success = "Profile picture updated!";
                } else {
                    $error = "Database error.";
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, GIF allowed.";
        }
    }

    // 2. Change Password (Optional)
    if (!empty($_POST['new_password'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        if ($new_pass === $confirm_pass) {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hash, $user_id);
            if ($stmt->execute()) {
                $success = "Password updated successfully!";
            } else {
                $error = "Error updating password.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    }
}

// Fetch User Info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">&larr; Back</a>
            <h1>My Profile</h1>
        </div>
        <div class="user-profile">
            <div class="user-badge">
            <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" style="width:24px; height:24px; border-radius:50%; vertical-align:middle; margin-right:5px;">
            <?php endif; ?>
            <?php echo htmlspecialchars($user['username']); ?>
            </div>
            <a href="index.php?logout=1" class="btn btn-danger" style="padding: 8px 16px; font-size: 0.875rem;">Logout</a>
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="card slide-up" style="max-width: 600px; margin: 0 auto;">
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem;">
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: #e2e8f0; margin-bottom: 1rem; overflow: hidden; position: relative;">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #94a3b8;">
                                <?php echo substr(strtoupper($user['username']), 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <label for="profile_picture" class="btn btn-secondary" style="cursor: pointer;">
                        Change Picture
                        <input type="file" id="profile_picture" name="profile_picture" style="display: none;" accept="image/*" onchange="this.form.submit()">
                    </label>
                </div>

                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #e2e8f0;">

                <h3>Change Password</h3>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password">
                </div>

                <div style="text-align: right;">
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
