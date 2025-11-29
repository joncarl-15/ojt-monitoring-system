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

// Get today's DTR record
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? AND record_date = ?");
$stmt->bind_param("is", $student['student_id'], $today);
$stmt->execute();
$today_record = $stmt->get_result()->fetch_assoc();

$message = '';
$error = '';

// Handle time in/out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'time_in' && !$today_record) {
        $time_in = date('Y-m-d H:i:s');
        $status = 'present';

        $stmt = $conn->prepare("INSERT INTO daily_time_records (student_id, record_date, time_in, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $student['student_id'], $today, $time_in, $status);

        if ($stmt->execute()) {
            $message = "✓ Time In recorded at " . date('H:i:s');
            $today_record = ['time_in' => $time_in, 'time_out' => null, 'daily_hours' => null];
        } else {
            $error = "Error recording time in: " . $stmt->error;
        }
    } elseif ($action == 'time_out' && $today_record && !$today_record['time_out']) {
        $time_out = date('Y-m-d H:i:s');
        $time_in = new DateTime($today_record['time_in']);
        $time_out_obj = new DateTime($time_out);
        $interval = $time_in->diff($time_out_obj);
        $daily_hours = ($interval->h + ($interval->i / 60));

        $stmt = $conn->prepare("UPDATE daily_time_records SET time_out = ?, daily_hours = ? WHERE dtr_id = ?");
        $stmt->bind_param("sdi", $time_out, $daily_hours, $today_record['dtr_id']);

        if ($stmt->execute()) {
            $message = "✓ Time Out recorded at " . date('H:i:s') . " | Hours: " . number_format($daily_hours, 2);
            $today_record['time_out'] = $time_out;
            $today_record['daily_hours'] = $daily_hours;
        } else {
            $error = "Error recording time out: " . $stmt->error;
        }
    } elseif ($action == 'edit_record') {
        if (!$is_admin) {
            $error = 'Only administrators can edit records';
        } else {
            $dtr_id = isset($_POST['dtr_id']) ? intval($_POST['dtr_id']) : 0;
            $time_in_edit = isset($_POST['time_in_edit']) ? $_POST['time_in_edit'] : '';
            $time_out_edit = isset($_POST['time_out_edit']) ? $_POST['time_out_edit'] : '';

            if (!$dtr_id || empty($time_in_edit)) {
                $error = 'Invalid record data';
            } else {
                // Verify record belongs to student
                $verify_stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE dtr_id = ? AND student_id = ?");
                $verify_stmt->bind_param("ii", $dtr_id, $student['student_id']);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows == 0) {
                    $error = 'Record not found';
                } else {
                    // Calculate hours if both times are provided
                    $daily_hours = null;
                    if (!empty($time_out_edit)) {
                        $time_in_obj = new DateTime($time_in_edit);
                        $time_out_obj = new DateTime($time_out_edit);
                        
                        if ($time_out_obj <= $time_in_obj) {
                            $error = 'Time Out must be after Time In';
                        } else {
                            $interval = $time_in_obj->diff($time_out_obj);
                            $daily_hours = ($interval->h + ($interval->i / 60));
                        }
                    }

                    if (!$error) {
                        $update_stmt = $conn->prepare("UPDATE daily_time_records SET time_in = ?, time_out = ?, daily_hours = ? WHERE dtr_id = ?");
                        $update_stmt->bind_param("ssdi", $time_in_edit, $time_out_edit, $daily_hours, $dtr_id);

                        if ($update_stmt->execute()) {
                            $message = "✓ Record updated successfully";
                            // Refresh today's record if it was modified
                            $stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? AND record_date = ?");
                            $stmt->bind_param("is", $student['student_id'], $today);
                            $stmt->execute();
                            $today_record = $stmt->get_result()->fetch_assoc();
                        } else {
                            $error = "Error updating record: " . $update_stmt->error;
                        }
                    }
                }
            }
        }
    } elseif ($action == 'delete_record') {
        if (!$is_admin) {
            $error = 'Only administrators can delete records';
        } else {
            $dtr_id = isset($_POST['dtr_id']) ? intval($_POST['dtr_id']) : 0;

            if (!$dtr_id) {
                $error = 'Invalid record';
            } else {
                // Verify record belongs to student
                $verify_stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE dtr_id = ? AND student_id = ?");
                $verify_stmt->bind_param("ii", $dtr_id, $student['student_id']);
                $verify_stmt->execute();
                
                if ($verify_stmt->get_result()->num_rows == 0) {
                    $error = 'Record not found';
                } else {
                    $delete_stmt = $conn->prepare("DELETE FROM daily_time_records WHERE dtr_id = ?");
                    $delete_stmt->bind_param("i", $dtr_id);

                    if ($delete_stmt->execute()) {
                        $message = "✓ Record deleted successfully";
                        // Refresh today's record if it was deleted
                        $stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? AND record_date = ?");
                        $stmt->bind_param("is", $student['student_id'], $today);
                        $stmt->execute();
                        $today_record = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error = "Error deleting record: " . $delete_stmt->error;
                    }
                }
            }
        }
    }
}

// Get recent records
$recent_stmt = $conn->prepare("SELECT * FROM daily_time_records WHERE student_id = ? ORDER BY record_date DESC LIMIT 10");
$recent_stmt->bind_param("i", $student['student_id']);
$recent_stmt->execute();
$recent_records = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time In/Out - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Time In/Out</h1>
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

        <div class="card slide-up" style="margin-bottom: 2rem;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Today's Time Record</h2>

            <div
                style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; text-align: center;">
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-size: 0.875rem; text-transform: uppercase;">Time
                        In</strong>
                    <span
                        style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $today_record ? date('h:i A', strtotime($today_record['time_in'])) : 'Not yet'; ?></span>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-size: 0.875rem; text-transform: uppercase;">Time
                        Out</strong>
                    <span
                        style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $today_record && $today_record['time_out'] ? date('h:i A', strtotime($today_record['time_out'])) : 'Not yet'; ?></span>
                </div>
                <div>
                    <strong
                        style="display: block; color: var(--primary-color); margin-bottom: 0.5rem; font-size: 0.875rem; text-transform: uppercase;">Hours</strong>
                    <span
                        style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $today_record && $today_record['daily_hours'] ? number_format($today_record['daily_hours'], 2) : '-'; ?></span>
                </div>
            </div>

            <form method="POST" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" name="action" value="time_in" class="btn"
                    style="flex: 1; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                    <?php echo $today_record ? 'disabled' : ''; ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 12L19 12M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    TIME IN
                </button>
                <button type="submit" name="action" value="time_out" class="btn btn-danger"
                    style="flex: 1; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                    <?php echo !$today_record || $today_record['time_out'] ? 'disabled' : ''; ?>>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 12H3M3 12L10 5M3 12L10 19" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    TIME OUT
                </button>
            </form>
        </div>

        <div class="card slide-up" style="animation-delay: 0.1s;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Recent Records</h2>
            <?php if (empty($recent_records)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No time records yet.</div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                    <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?>
                                    </td>
                                    <td><?php echo $record['daily_hours'] ? number_format($record['daily_hours'], 2) : '-'; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <?php if ($is_admin): ?>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="openEditModal(<?php echo $record['dtr_id']; ?>, '<?php echo htmlspecialchars($record['time_in']); ?>', '<?php echo htmlspecialchars($record['time_out'] ?? ''); ?>')" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $record['dtr_id']; ?>)" style="padding: 0.5rem 1rem; font-size: 0.75rem;">
                                                    Delete
                                                </button>
                                            <?php else: ?>
                                                <span style="color: var(--text-light); font-size: 0.875rem;">No actions available</span>
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
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-content slide-up">
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
            <div class="login-header">
                <h2>Edit Time Record</h2>
                <p>Modify the time in and time out values</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_record">
                <input type="hidden" name="dtr_id" id="edit_dtr_id" value="">
                
                <div class="form-group">
                    <label for="time_in_edit">Time In</label>
                    <input type="datetime-local" id="time_in_edit" name="time_in_edit" required>
                </div>

                <div class="form-group">
                    <label for="time_out_edit">Time Out</label>
                    <input type="datetime-local" id="time_out_edit" name="time_out_edit">
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
                <h2>Delete Record</h2>
                <p>Are you sure you want to delete this record? This action cannot be undone.</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_record">
                <input type="hidden" name="dtr_id" id="delete_dtr_id" value="">
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(dtrId, timeIn, timeOut) {
            // Convert datetime format for input
            const timeInFormatted = new Date(timeIn).toISOString().slice(0, 16);
            const timeOutFormatted = timeOut ? new Date(timeOut).toISOString().slice(0, 16) : '';
            
            document.getElementById('edit_dtr_id').value = dtrId;
            document.getElementById('time_in_edit').value = timeInFormatted;
            document.getElementById('time_out_edit').value = timeOutFormatted;
            
            document.getElementById('edit-modal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function confirmDelete(dtrId) {
            document.getElementById('delete_dtr_id').value = dtrId;
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
</body>
</html>