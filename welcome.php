<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);
$announcements = [];

// Determine Company ID based on user type
$company_id = null;
$company_name = null;

if ($user['user_type'] == 'student') {
    $stmt = $conn->prepare("SELECT s.company_id, c.company_name FROM students s LEFT JOIN companies c ON s.company_id = c.company_id WHERE s.user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $company_id = $row['company_id'];
        $company_name = $row['company_name'];
    }
} elseif ($user['user_type'] == 'coordinator') {
    $stmt = $conn->prepare("SELECT company_id, company_name FROM companies WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $company_id = $row['company_id'];
        $company_name = $row['company_name'];
    }
}

// Fetch Announcements
// Logic:
// 1. All users see Global (company_id IS NULL) - OR Admin only? 
//    User said "global announcement will be for the admin itself". 
//    Maybe means created by Admin? I'll show Global to everyone for now as "System Announcements".
// 2. Students see matched company_id.
// 3. Coordinators see matched company_id.
// 4. Admin sees ALL.

$sql = "SELECT a.*, u.username, u.profile_picture FROM announcements a JOIN users u ON a.admin_id = u.user_id WHERE a.is_active = 1";
$params = [];
$types = "";

if ($user['user_type'] == 'student' || $user['user_type'] == 'coordinator') {
    if ($company_id) {
        // Robust Match: ID OR Name
        if (!empty($company_name)) {
            $sql .= " AND (a.company_id IS NULL OR a.company_id = ? OR a.company_id IN (SELECT company_id FROM companies WHERE company_name = ?))";
            $params[] = $company_id;
            $params[] = $company_name;
            $types .= "is";
        } else {
            $sql .= " AND (a.company_id IS NULL OR a.company_id = ?)";
            $params[] = $company_id;
            $types .= "i";
        }
    } else {
        // No company assigned? Show only Global
        $sql .= " AND a.company_id IS NULL";
    }
}
// Admin sees everything (no extra filter)

$sql .= " ORDER BY a.posted_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .welcome-container { max-width: 800px; width: 100%; }
        .welcome-header { text-align: center; margin-bottom: 2rem; }
        .welcome-header h1 { color: var(--primary-dark); margin-bottom: 0.5rem; font-size: 2rem; }
        .welcome-header p { color: var(--text-secondary); }
        .announcement-list { display: flex; flex-direction: column; gap: 1rem; }
    </style>
</head>
<body>
    <div class="welcome-container fade-in">
        <div class="welcome-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
            <p>Please check the latest announcements before proceeding.</p>
        </div>

        <div class="card slide-up">
            <h2 style="margin-bottom: 1.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">Announcements</h2>
            
            <?php if (empty($announcements)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No new announcements.</div>
            <?php else: ?>
                <div class="announcement-list">
                    <?php foreach ($announcements as $a): ?>
                        <div class="announcement-item" style="border-left: 4px solid <?php echo $a['company_id'] ? 'var(--primary-color)' : '#64748b'; ?>; background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if (!empty($a['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($a['profile_picture']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b;">
                                            <?php echo strtoupper(substr($a['username'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-primary);"><?php echo htmlspecialchars($a['title']); ?></h3>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            Posted by <strong><?php echo htmlspecialchars($a['username']); ?></strong>
                                            <span style="font-size: 0.75rem; color: #94a3b8; margin-left: 0.5rem;">• <?php echo date('M d, Y h:i A', strtotime($a['posted_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <span style="font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 99px; background: <?php echo $a['company_id'] ? '#ecfdf5' : '#f1f5f9'; ?>; color: <?php echo $a['company_id'] ? '#059669' : '#64748b'; ?>; font-weight: 600;">
                                    <?php echo $a['company_id'] ? 'Company' : 'System'; ?>
                                </span>
                            </div>
                            
                            <div style="color: var(--text-secondary); line-height: 1.6; margin-left: 3.25rem;">
                                <?php echo nl2br(htmlspecialchars($a['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                <a href="dashboard.php" class="btn" style="padding: 0.75rem 2rem; font-size: 1rem;">Proceed to Dashboard &rarr;</a>
                <a href="index.php?logout=1" class="btn btn-danger" style="padding: 0.75rem 2rem; font-size: 1rem;">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
