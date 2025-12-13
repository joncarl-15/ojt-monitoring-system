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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Delete
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $ann_id = intval($_POST['announcement_id']);
        
        // Security: Ensure the user owns this announcement
        if ($user['user_type'] == 'admin') {
            $del_stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
            $del_stmt->bind_param("i", $ann_id);
        } else {
            // Coordinator: Only delete own posts
            $del_stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ? AND admin_id = ?");
            $del_stmt->bind_param("ii", $ann_id, $user['user_id']);
        }
        
        if ($del_stmt->execute()) {
            $message = "✓ Announcement deleted successfully";
        } else {
            $error = "Error deleting: " . $conn->error;
        }
    } 
    // Handle Create
    elseif (isset($_POST['title'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $announcement_type = isset($_POST['announcement_type']) ? $_POST['announcement_type'] : 'general';

    if (empty($title) || empty($content)) {
        $error = 'Please fill in all fields';
    } else {
        $company_id_val = null;
        if ($user['user_type'] == 'coordinator') {
            // Find company for this coordinator
            $comp_stmt = $conn->prepare("SELECT company_id FROM companies WHERE user_id = ?");
            $comp_stmt->bind_param("i", $user['user_id']);
            $comp_stmt->execute();
            $c_res = $comp_stmt->get_result();
            if ($row = $c_res->fetch_assoc()) {
                $company_id_val = $row['company_id'];
            } else {
                // FALLBACK: Coordinator exists but no Company record? Sync it!
                // Get coordinator details
                $coord_stmt = $conn->prepare("SELECT company_name, company_address, contact_number, email FROM coordinators WHERE user_id = ?");
                $coord_stmt->bind_param("i", $user['user_id']);
                $coord_stmt->execute();
                $coord_info = $coord_stmt->get_result()->fetch_assoc();
                
                if ($coord_info) {
                    // Start by checking if a company with this name ALREADY exists (created by Admin?)
                    $name_check = $conn->prepare("SELECT company_id FROM companies WHERE company_name = ?");
                    $name_check->bind_param("s", $coord_info['company_name']);
                    $name_check->execute();
                    $nc_res = $name_check->get_result();
                    
                    if ($nc_row = $nc_res->fetch_assoc()) {
                        // Found existing company! Use it.
                        $company_id_val = $nc_row['company_id'];
                        
                        // Optional: Link this coordinator to it for future lookup optimization
                        // We only link if it currently has NO owner to avoid stealing.
                        //$conn->query("UPDATE companies SET user_id = " . $user['user_id'] . " WHERE company_id = $company_id_val AND user_id IS NULL");
                    } else {
                        // Create Company Record
                        $ins_comp = $conn->prepare("INSERT INTO companies (user_id, company_name, address, supervisor_name, contact_number, email) VALUES (?, ?, ?, ?, ?, ?)");
                        // supervisor_name comes from User's Full Name? Or just use Username for now? Or Empty.
                        // Let's use username or "Coordinator".
                        $supervisor_name = $user['username']; 
                        
                        $ins_comp->bind_param("isssss", $user['user_id'], $coord_info['company_name'], $coord_info['company_address'], $supervisor_name, $coord_info['contact_number'], $coord_info['email']);
                        if ($ins_comp->execute()) {
                            $company_id_val = $ins_comp->insert_id;
                        }
                    }
                }
            }
            
            // If still null, we have an issue. Prevent Global Posting by Coordinator.
            if ($company_id_val === null) {
                $error = "Critical Error: Could not link you to a Company. Please contact Admin.";
            }
        }
        
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO announcements (admin_id, title, content, announcement_type, is_active, company_id) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->bind_param("isssi", $user['user_id'], $title, $content, $announcement_type, $company_id_val);
    
            if ($stmt->execute()) {
                $message = "✓ Announcement posted successfully";
                $_POST = [];
            } else {
                $error = "Error posting announcement: " . $stmt->error;
            }
        }
        }
    }
}

// Get announcements (Admin sees all, Coordinator sees theirs)
if ($user['user_type'] == 'admin') {
    $stmt = $conn->prepare("SELECT a.*, u.username, c.company_name FROM announcements a JOIN users u ON a.admin_id = u.user_id LEFT JOIN companies c ON a.company_id = c.company_id ORDER BY posted_at DESC");
} else {
    // Coordinator
    $stmt = $conn->prepare("SELECT a.*, u.username, c.company_name FROM announcements a JOIN users u ON a.admin_id = u.user_id LEFT JOIN companies c ON a.company_id = c.company_id WHERE a.admin_id = ? ORDER BY posted_at DESC"); // Only show own posts? Or all company posts? Let's show own posts for management.
    $stmt->bind_param("i", $user['user_id']);
}
$stmt->execute();
$announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Manage Announcements</h1>
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
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="grid-container" style="grid-template-columns: 1fr 1.5fr;">
            <div class="card slide-up">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Post New Announcement</h2>

                <form method="POST">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required
                            value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                            placeholder="Enter announcement title">
                    </div>

                    <div class="form-group">
                        <label for="announcement_type">Type</label>
                        <select id="announcement_type" name="announcement_type" required>
                            <option value="general">General</option>
                            <option value="event">Event</option>
                            <option value="deadline">Deadline</option>
                            <option value="instruction">Instruction</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" required placeholder="Write your announcement here..."
                            style="min-height: 150px;"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Post Announcement</button>
                </form>
            </div>

            <div class="card slide-up" style="animation-delay: 0.1s;">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Recent Announcements</h2>

                <?php if (empty($announcements)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-light);">No announcements yet.</div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($announcements as $announcement): ?>
                            <div
                                style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h3 style="font-size: 1.1rem; margin: 0; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    <span class="status-badge" style="background-color: var(--primary-color); color: white;">
                                        <?php echo ucfirst($announcement['announcement_type']); ?>
                                    </span>
                                </div>

                                <div style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1rem;">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>

                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: var(--text-light);">
                                    <div>Posted by: <strong
                                            style="color: var(--text-primary);"><?php echo htmlspecialchars($announcement['username']); ?></strong>
                                    </div>
                                    <div><?php echo date('M d, Y h:i A', strtotime($announcement['posted_at'])); ?></div>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this announcement?');" style="margin-left: 1rem;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>