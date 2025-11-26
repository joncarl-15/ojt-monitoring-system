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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 24px;
        }

        .back-link {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            display: inline-block;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.1);
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .announcement-item {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
        }

        .announcement-item:last-child {
            margin-bottom: 0;
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .announcement-title {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .announcement-type {
            display: inline-block;
            padding: 3px 8px;
            background: #667eea;
            color: white;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }

        .announcement-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .announcement-meta {
            color: #999;
            font-size: 12px;
            border-top: 1px solid #e0e0e0;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
        }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h1>Manage Announcements</h1>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Post New Announcement</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
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
                    <textarea id="content" name="content" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                </div>

                <button type="submit" class="submit-btn">Post Announcement</button>
            </form>
        </div>

        <div class="card">
            <h2>Recent Announcements</h2>
            
            <?php if (empty($announcements)): ?>
                <div class="no-data">No announcements yet.</div>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item">
                    <div class="announcement-header">
                        <div>
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                        </div>
                        <span class="announcement-type"><?php echo ucfirst($announcement['announcement_type']); ?></span>
                    </div>
                    
                    <div class="announcement-content">
                        <?php echo htmlspecialchars($announcement['content']); ?>
                    </div>

                    <div class="announcement-meta">
                        Posted by: <strong><?php echo htmlspecialchars($announcement['username']); ?></strong> | 
                        <?php echo date('M d, Y H:i', strtotime($announcement['posted_at'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
