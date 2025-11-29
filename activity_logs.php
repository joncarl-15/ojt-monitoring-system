<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);
$student = get_student_info($_SESSION['user_id'], $conn);
$is_admin = $user['user_type'] == 'admin';

if (!$student) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : 'create';
    
    if ($action == 'create') {
        $week_starting = isset($_POST['week_starting']) ? $_POST['week_starting'] : '';
        $week_ending = isset($_POST['week_ending']) ? $_POST['week_ending'] : '';
        $task_description = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
        $hours_rendered = isset($_POST['hours_rendered']) ? floatval($_POST['hours_rendered']) : 0;
        $accomplishments = isset($_POST['accomplishments']) ? trim($_POST['accomplishments']) : '';

        if (empty($week_starting) || empty($week_ending) || empty($task_description) || $hours_rendered <= 0) {
            $error = 'Please fill in all fields correctly';
        } else {
            $stmt = $conn->prepare("INSERT INTO activity_logs (student_id, week_starting, week_ending, task_description, hours_rendered, accomplishments, status) VALUES (?, ?, ?, ?, ?, ?, 'draft')");
            $stmt->bind_param("issdss", $student['student_id'], $week_starting, $week_ending, $task_description, $hours_rendered, $accomplishments);

            if ($stmt->execute()) {
                $message = "✓ Activity log created successfully";
                $_POST = [];
            } else {
                $error = "Error creating activity log: " . $stmt->error;
            }
        }
    } elseif ($action == 'edit') {
        if (!$is_admin) {
            $error = 'Only administrators can edit activity logs';
        } else {
            $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
            $week_starting = isset($_POST['week_starting']) ? $_POST['week_starting'] : '';
            $week_ending = isset($_POST['week_ending']) ? $_POST['week_ending'] : '';
            $task_description = isset($_POST['task_description']) ? trim($_POST['task_description']) : '';
            $hours_rendered = isset($_POST['hours_rendered']) ? floatval($_POST['hours_rendered']) : 0;
            $accomplishments = isset($_POST['accomplishments']) ? trim($_POST['accomplishments']) : '';

            if (!$activity_id || empty($week_starting) || empty($week_ending) || empty($task_description) || $hours_rendered <= 0) {
                $error = 'Please fill in all fields correctly';
            } else {
                // Verify activity log belongs to student and is in draft status
                $verify_stmt = $conn->prepare("SELECT * FROM activity_logs WHERE activity_id = ? AND student_id = ? AND status = 'draft'");
                $verify_stmt->bind_param("ii", $activity_id, $student['student_id']);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows == 0) {
                    $error = 'Cannot edit this log. Only draft logs can be edited.';
                } else {
                    $update_stmt = $conn->prepare("UPDATE activity_logs SET week_starting = ?, week_ending = ?, task_description = ?, hours_rendered = ?, accomplishments = ? WHERE activity_id = ?");
                    $update_stmt->bind_param("ssdssi", $week_starting, $week_ending, $task_description, $hours_rendered, $accomplishments, $activity_id);

                    if ($update_stmt->execute()) {
                        $message = "✓ Activity log updated successfully";
                    } else {
                        $error = "Error updating activity log: " . $update_stmt->error;
                    }
                }
            }
        }
    } elseif ($action == 'delete') {
        if (!$is_admin) {
            $error = 'Only administrators can delete activity logs';
        } else {
            $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;

            if (!$activity_id) {
                $error = 'Invalid activity log';
            } else {
                // Verify activity log belongs to student and is in draft status
                $verify_stmt = $conn->prepare("SELECT * FROM activity_logs WHERE activity_id = ? AND student_id = ? AND status = 'draft'");
                $verify_stmt->bind_param("ii", $activity_id, $student['student_id']);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows == 0) {
                    $error = 'Cannot delete this log. Only draft logs can be deleted.';
                } else {
                    $delete_stmt = $conn->prepare("DELETE FROM activity_logs WHERE activity_id = ?");
                    $delete_stmt->bind_param("i", $activity_id);

                    if ($delete_stmt->execute()) {
                        $message = "✓ Activity log deleted successfully";
                    } else {
                        $error = "Error deleting activity log: " . $delete_stmt->error;
                    }
                }
            }
        }
    }
}

// Get student's activity logs
$logs_stmt = $conn->prepare("SELECT * FROM activity_logs WHERE student_id = ? ORDER BY week_starting DESC");
$logs_stmt->bind_param("i", $student['student_id']);
$logs_stmt->execute();
$activity_logs = $logs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Activity Logs</h1>
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
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Add New Activity Log</h2>

                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="week_starting">Week Starting</label>
                            <input type="date" id="week_starting" name="week_starting" required
                                value="<?php echo isset($_POST['week_starting']) ? htmlspecialchars($_POST['week_starting']) : ''; ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="week_ending">Week Ending</label>
                            <input type="date" id="week_ending" name="week_ending" required
                                value="<?php echo isset($_POST['week_ending']) ? htmlspecialchars($_POST['week_ending']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="task_description">Task Description</label>
                        <textarea id="task_description" name="task_description" required
                            placeholder="Describe the tasks you completed during this period..."
                            style="min-height: 100px;"><?php echo isset($_POST['task_description']) ? htmlspecialchars($_POST['task_description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="hours_rendered">Hours Rendered</label>
                        <input type="number" id="hours_rendered" name="hours_rendered" step="0.25" min="0" required
                            value="<?php echo isset($_POST['hours_rendered']) ? htmlspecialchars($_POST['hours_rendered']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="accomplishments">Accomplishments</label>
                        <textarea id="accomplishments" name="accomplishments"
                            placeholder="What did you accomplish? (Optional)"
                            style="min-height: 80px;"><?php echo isset($_POST['accomplishments']) ? htmlspecialchars($_POST['accomplishments']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Submit Activity Log</button>
                </form>
            </div>

            <div class="card slide-up" style="animation-delay: 0.1s;">
                <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Your Activity Logs</h2>

                <?php if (empty($activity_logs)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-light);">No activity logs yet.</div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($activity_logs as $log): ?>
                            <div
                                style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="font-size: 1.1rem; margin-bottom: 0.25rem; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($log['task_description']); ?></h3>
                                        <div style="color: var(--text-secondary); font-size: 0.875rem;">
                                            <?php echo date('M d, Y', strtotime($log['week_starting'])); ?> -
                                            <?php echo date('M d, Y', strtotime($log['week_ending'])); ?>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $log['status']; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </div>

                                <?php if (!empty($log['accomplishments'])): ?>
                                    <div style="margin-bottom: 1rem; color: var(--text-secondary); font-size: 0.95rem;">
                                        <strong style="color: var(--primary-dark);">Accomplishments:</strong>
                                        <?php echo htmlspecialchars($log['accomplishments']); ?>
                                    </div>
                                <?php endif; ?>

                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem;">
                                    <div style="color: var(--primary-color); font-weight: 600;">Hours:
                                        <?php echo number_format($log['hours_rendered'], 2); ?></div>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div style="color: var(--text-light);">
                                            Posted: <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                        </div>
                                        <?php if ($log['status'] == 'draft' && $is_admin): ?>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($log)); ?>)" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $log['activity_id']; ?>)" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                                                    Delete
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-content slide-up" style="max-width: 600px;">
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
            <div class="login-header">
                <h2>Edit Activity Log</h2>
                <p>Update your activity log details</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="activity_id" id="edit_activity_id" value="">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_week_starting">Week Starting</label>
                        <input type="date" id="edit_week_starting" name="week_starting" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_week_ending">Week Ending</label>
                        <input type="date" id="edit_week_ending" name="week_ending" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_task_description">Task Description</label>
                    <textarea id="edit_task_description" name="task_description" required style="min-height: 100px;"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_hours_rendered">Hours Rendered</label>
                    <input type="number" id="edit_hours_rendered" name="hours_rendered" step="0.25" min="0" required>
                </div>

                <div class="form-group">
                    <label for="edit_accomplishments">Accomplishments</label>
                    <textarea id="edit_accomplishments" name="accomplishments" style="min-height: 80px;"></textarea>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal-content slide-up">
            <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            <div class="login-header">
                <h2>Delete Activity Log</h2>
                <p>Are you sure you want to delete this activity log? This action cannot be undone.</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="activity_id" id="delete_activity_id" value="">
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(logData) {
            document.getElementById('edit_activity_id').value = logData.activity_id;
            document.getElementById('edit_week_starting').value = logData.week_starting;
            document.getElementById('edit_week_ending').value = logData.week_ending;
            document.getElementById('edit_task_description').value = logData.task_description;
            document.getElementById('edit_hours_rendered').value = logData.hours_rendered;
            document.getElementById('edit_accomplishments').value = logData.accomplishments || '';
            
            document.getElementById('edit-modal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function confirmDelete(activityId) {
            document.getElementById('delete_activity_id').value = activityId;
            document.getElementById('delete-modal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const editModal = document.getElementById('edit-modal');
            const deleteModal = document.getElementById('delete-modal');
            
            if (e.target === editModal) {
                closeEditModal();
            }
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
        });
    </script>