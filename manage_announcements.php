<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $announcement_type = isset($_POST['announcement_type']) ? $_POST['announcement_type'] : 'general';

    if (empty($title) || empty($content)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("INSERT INTO announcements (admin_id, title, content, announcement_type, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("isss", $user['user_id'], $title, $content, $announcement_type);

        if ($stmt->execute()) {
            $message = "✓ Announcement posted successfully";
            $_POST = [];
        } else {
            $error = "Error posting announcement: " . $stmt->error;
        }
    }
}

// Get all announcements
$stmt = $conn->prepare("SELECT a.*, u.username FROM announcements a JOIN users u ON a.admin_id = u.user_id ORDER BY posted_at DESC");
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