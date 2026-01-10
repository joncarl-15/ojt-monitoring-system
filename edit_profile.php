<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'coordinator') {
    header("Location: dashboard.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM coordinators WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$coordinator = $stmt->get_result()->fetch_assoc();

$manage_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    if (empty($company_name) || empty($company_address) || empty($contact_number)) {
        $manage_message = 'All fields are required.';
    } else {
        $upd_stmt = $conn->prepare("UPDATE coordinators SET company_name = ?, company_address = ?, contact_number = ? WHERE user_id = ?");
        $upd_stmt->bind_param("sssi", $company_name, $company_address, $contact_number, $_SESSION['user_id']);
        if ($upd_stmt->execute()) {
            $manage_message = 'Profile updated successfully.';
            // Refresh coordinator data
            $stmt->execute();
            $coordinator = $stmt->get_result()->fetch_assoc();
        } else {
            $manage_message = 'Error updating profile: ' . $upd_stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Edit Profile</h1>
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
            
            <form method="post">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" value="<?php echo htmlspecialchars($coordinator['company_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Company Address</label>
                    <input type="text" name="company_address" value="<?php echo htmlspecialchars($coordinator['company_address'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?php echo htmlspecialchars($coordinator['contact_number'] ?? ''); ?>" required>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <button class="btn" type="submit">Save</button>
                    <a class="btn btn-secondary" href="dashboard.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>