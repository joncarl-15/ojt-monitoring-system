<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin' && $user['user_type'] != 'coordinator') {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$error = '';

// Handle edit student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'edit') {
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
        $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $course = isset($_POST['course']) ? trim($_POST['course']) : '';
        $year_level = isset($_POST['year_level']) ? $_POST['year_level'] : '';
        $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
        $email_address = isset($_POST['email_address']) ? trim($_POST['email_address']) : '';
        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : null;

        if (!$student_id || empty($first_name) || empty($last_name) || empty($course)) {
            $error = 'Please fill in all required fields';
        } else {
            $company_id_param = $company_id ?: null;
            $update_stmt = $conn->prepare("UPDATE students SET first_name = ?, last_name = ?, course = ?, year_level = ?, contact_number = ?, email_address = ?, company_id = ? WHERE student_id = ?");
            $update_stmt->bind_param("ssssssii", $first_name, $last_name, $course, $year_level, $contact_number, $email_address, $company_id_param, $student_id);

            if ($update_stmt->execute()) {
                $message = "✓ Student information updated successfully";
            } else {
                $error = "Error updating student: " . $update_stmt->error;
            }
        }
    } elseif ($action == 'delete_records') {
        $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

        if (!$student_id) {
            $error = 'Invalid student';
        } else {
            // Delete time records first
            $delete_time = $conn->prepare("DELETE FROM daily_time_records WHERE student_id = ?");
            $delete_time->bind_param("i", $student_id);
            $delete_time->execute();

            // Delete activity logs
            $delete_activity = $conn->prepare("DELETE FROM activity_logs WHERE student_id = ?");
            $delete_activity->bind_param("i", $student_id);
            $delete_activity->execute();

            if ($delete_time && $delete_activity) {
                $message = "✓ All student records deleted successfully";
            } else {
                $error = "Error deleting records";
            }
        }
    }
}

// Get all companies for dropdown
$companies_stmt = $conn->query("SELECT company_id, company_name FROM companies ORDER BY company_name");
$companies = $companies_stmt->fetch_all(MYSQLI_ASSOC);

// Get all students with their info (only one per user_id to match student users)
$stmt = $conn->prepare("
    SELECT s.*, u.username, c.company_name, 
           COUNT(DISTINCT d.dtr_id) as dtr_count,
           COALESCE(SUM(d.daily_hours), 0) as total_hours
    FROM students s
    INNER JOIN (
        SELECT user_id, MAX(student_id) as latest_student_id
        FROM students
        GROUP BY user_id
    ) latest ON s.student_id = latest.latest_student_id
    JOIN users u ON s.user_id = u.user_id AND u.user_type = 'student'
    LEFT JOIN companies c ON s.company_id = c.company_id
    LEFT JOIN daily_time_records d ON s.student_id = d.student_id
    GROUP BY s.student_id
    ORDER BY s.first_name ASC
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>View Students</h1>
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
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card slide-up">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: var(--primary-dark); margin: 0;">All Students</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name, username, or course..."
                        style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); width: 300px; font-size: 0.9rem;">
                </div>
            </div>

            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No students found.</div>
            <?php else: ?>
                <div class="table-container">
                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Company</th>
                                <th>Total Hours</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td style="font-weight: 500; color: var(--text-primary);">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']); ?></td>
                                    <td><?php echo htmlspecialchars($student['company_name'] ?? 'Not assigned'); ?></td>
                                    <td style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo number_format($student['total_hours'], 2); ?> hrs</td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a href="student_time_records.php?student_id=<?php echo $student['student_id']; ?>"
                                                class="btn btn-sm btn-secondary"
                                                style="padding: 0.5rem 1rem; font-size: 0.75rem;">Time Records</a>
                                            <a href="student_activity.php?student_id=<?php echo $student['student_id']; ?>"
                                                class="btn btn-sm btn-secondary"
                                                style="padding: 0.5rem 1rem; font-size: 0.75rem;">Activity</a>
                                            <button type="button" class="btn btn-sm btn-secondary"
                                                onclick="openEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                                style="padding: 0.5rem 1rem; font-size: 0.75rem;">Edit</button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDeleteRecords(<?php echo $student['student_id']; ?>)"
                                                style="padding: 0.5rem 1rem; font-size: 0.75rem;">Delete Records</button>
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

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        });

        function openEditModal(studentData) {
            document.getElementById('edit_student_id').value = studentData.student_id;
            document.getElementById('edit_first_name').value = studentData.first_name;
            document.getElementById('edit_last_name').value = studentData.last_name;
            document.getElementById('edit_course').value = studentData.course;
            document.getElementById('edit_year_level').value = studentData.year_level;
            document.getElementById('edit_contact_number').value = studentData.contact_number || '';
            document.getElementById('edit_email_address').value = studentData.email_address || '';
            document.getElementById('edit_company_id').value = studentData.company_id || '';
            
            document.getElementById('edit-modal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('edit-modal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function confirmDeleteRecords(studentId) {
            document.getElementById('delete_student_id').value = studentId;
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

    <!-- Edit Student Modal -->
    <div class="modal-overlay" id="edit-modal">
        <div class="modal-content slide-up" style="max-width: 600px;">
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
            <div class="login-header">
                <h2>Edit Student Information</h2>
                <p>Update student details</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="student_id" id="edit_student_id" value="">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_course">Course</label>
                    <input type="text" id="edit_course" name="course" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="edit_year_level">Year Level</label>
                        <select id="edit_year_level" name="year_level" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;">
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_company_id">Company</label>
                        <select id="edit_company_id" name="company_id" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;">
                            <option value="">Not assigned</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['company_id']; ?>"><?php echo htmlspecialchars($company['company_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_contact_number">Contact Number</label>
                    <input type="tel" id="edit_contact_number" name="contact_number">
                </div>

                <div class="form-group">
                    <label for="edit_email_address">Email Address</label>
                    <input type="email" id="edit_email_address" name="email_address">
                </div>

                <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Delete Records Confirmation Modal -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal-content slide-up">
            <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            <div class="login-header">
                <h2>Delete Student Records</h2>
                <p>This will delete all time records and activity logs for this student. This action cannot be undone.</p>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete_records">
                <input type="hidden" name="student_id" id="delete_student_id" value="">
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Records</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>