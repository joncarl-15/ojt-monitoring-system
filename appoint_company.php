<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $company_name_dropdown = trim($_POST['company_name'] ?? '');
    $company_name_custom = trim($_POST['company_name_custom'] ?? '');
    
    // Use custom name if provided, otherwise use dropdown selection
    $company_name = !empty($company_name_custom) ? $company_name_custom : $company_name_dropdown;
    
    if (empty($student_id) || empty($company_name)) {
        $error = 'Please select a student and enter or select a company name';
    } else {
        // Check if company exists, if not create it
        $stmt = $conn->prepare("SELECT company_id FROM companies WHERE company_name = ?");
        $stmt->bind_param("s", $company_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Company exists, get its ID
            $company = $result->fetch_assoc();
            $company_id = $company['company_id'];
        } else {
            // Company doesn't exist, create it with minimal info
            $stmt = $conn->prepare("INSERT INTO companies (company_name, address, supervisor_name, contact_number) VALUES (?, 'To be updated', 'To be updated', 'To be updated')");
            $stmt->bind_param("s", $company_name);
            $stmt->execute();
            $company_id = $stmt->insert_id;
        }
        
        // Update student's company assignment
        $stmt = $conn->prepare("UPDATE students SET company_id = ? WHERE student_id = ?");
        $stmt->bind_param("ii", $company_id, $student_id);
        
        if ($stmt->execute()) {
            $success = 'Company successfully appointed to student!';
        } else {
            $error = 'Error appointing company. Please try again.';
        }
    }
}

// Get all students (only one per user_id to match student users)
$students_query = "SELECT s.student_id, s.first_name, s.last_name, s.course, s.year_level, c.company_name, u.username
                   FROM students s
                   INNER JOIN (
                       SELECT user_id, MAX(student_id) as latest_student_id
                       FROM students
                       GROUP BY user_id
                   ) latest ON s.student_id = latest.latest_student_id
                   JOIN users u ON s.user_id = u.user_id AND u.user_type = 'student'
                   LEFT JOIN companies c ON s.company_id = c.company_id 
                   ORDER BY s.last_name, s.first_name";
$students = $conn->query($students_query)->fetch_all(MYSQLI_ASSOC);

// Get all existing company names from coordinators (coordinator users)
$companies_query = "SELECT DISTINCT company_name 
                    FROM coordinators c
                    JOIN users u ON c.user_id = u.user_id AND u.user_type = 'coordinator'
                    WHERE company_name IS NOT NULL AND company_name != ''
                    ORDER BY company_name";
$existing_companies = $conn->query($companies_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appoint Company to Student - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="manage_companies.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr; Back</a>
            <h1>Appoint Company to Student</h1>
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
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Assign Company to Student</h2>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_id">Select Student</label>
                    <select id="student_id" name="student_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: var(--border-radius); font-family: inherit;">
                        <option value="">-- Choose a student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                                (<?php echo htmlspecialchars($student['course'] . ' - ' . $student['year_level']); ?>)
                                <?php if ($student['company_name']): ?>
                                    - Currently at: <?php echo htmlspecialchars($student['company_name']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <div style="border: 1px solid #d1d5db; border-radius: 12px; overflow: hidden; background: white; transition: all 0.3s ease;" id="company_container">
                        <select id="company_name" name="company_name" required 
                               style="width: 100%; padding: 0.75rem; border: none; border-bottom: 1px solid #e5e7eb; border-radius: 12px 12px 0 0; font-family: inherit; background: transparent; cursor: pointer; transition: all 0.3s ease; appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"12\" height=\"12\" viewBox=\"0 0 12 12\"><path fill=\"%23374151\" d=\"M6 9L1 4h10z\"/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 12px; padding-right: 2.5rem; outline: none;"
                               onfocus="document.getElementById('company_container').style.borderColor='var(--primary-color)'; document.getElementById('company_container').style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'"
                               onblur="document.getElementById('company_container').style.borderColor='#d1d5db'; document.getElementById('company_container').style.boxShadow='none'">
                            <option value="">-- Select a company --</option>
                            <?php foreach ($existing_companies as $company): ?>
                                <option value="<?php echo htmlspecialchars($company['company_name']); ?>">
                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="company_name_custom" name="company_name_custom" 
                               style="width: 100%; padding: 0.75rem; border: none; border-radius: 0 0 12px 12px; font-family: inherit; outline: none;"
                               placeholder="Or type a new company name"
                               onfocus="document.getElementById('company_container').style.borderColor='var(--primary-color)'; document.getElementById('company_container').style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'"
                               onblur="document.getElementById('company_container').style.borderColor='#d1d5db'; document.getElementById('company_container').style.boxShadow='none'">
                    </div>
                    <small style="color: var(--text-light); font-size: 0.875rem; display: block; margin-top: 0.5rem;">Leave blank if selecting from dropdown above</small>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn" style="flex: 1;">Appoint Company</button>
                    <a href="manage_companies.php" class="btn btn-secondary" style="flex: 1; text-align: center; padding: 0.75rem;">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Current Appointments -->
        <div class="card slide-up" style="margin-top: 2rem;">
            <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">Current Student Appointments</h2>
            
            <?php 
            $appointed_students = array_filter($students, function($s) { return !empty($s['company_name']); });
            ?>
            
            <?php if (empty($appointed_students)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No students have been appointed to companies yet.</div>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($appointed_students as $student): ?>
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                            <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                            </div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($student['course'] . ' - ' . $student['year_level']); ?>
                            </div>
                            <div style="color: var(--primary-dark); font-weight: 600; font-size: 0.95rem;">
                                📍 <?php echo htmlspecialchars($student['company_name']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Make the dropdown and custom input work together smoothly
        const companyDropdown = document.getElementById('company_name');
        const companyCustom = document.getElementById('company_name_custom');
        const companyContainer = document.getElementById('company_container');

        // When dropdown is selected, clear custom input
        companyDropdown.addEventListener('change', function() {
            if (this.value) {
                companyCustom.value = '';
            }
        });

        // When custom input is typed, clear dropdown selection
        companyCustom.addEventListener('input', function() {
            if (this.value) {
                companyDropdown.value = '';
            }
        });

        // Add hover effect to container
        companyContainer.addEventListener('mouseenter', function() {
            if (document.activeElement !== companyDropdown && document.activeElement !== companyCustom) {
                this.style.borderColor = 'var(--primary-color)';
            }
        });

        companyContainer.addEventListener('mouseleave', function() {
            if (document.activeElement !== companyDropdown && document.activeElement !== companyCustom) {
                this.style.borderColor = '#d1d5db';
            }
        });
    </script>
</body>

</html>
