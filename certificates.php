<?php
require_once 'config.php';
require_login();

// Only admin/coordinator
if ($_SESSION['user_type'] == 'student') {
    header("Location: dashboard.php");
    exit;
}

// Fetch students with company info
$stmt = $conn->prepare("SELECT u.user_id, u.username, s.first_name, s.last_name, s.course, c.company_name 
                        FROM users u 
                        JOIN students s ON u.user_id = s.user_id 
                        LEFT JOIN companies c ON s.company_id = c.company_id 
                        ORDER BY c.company_name DESC, s.last_name ASC");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Generator - OJT Monitoring</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">&larr; Back</a>
            <h1>Certificate Generator</h1>
        </div>
    </header>

    <div class="main-content fade-in">
        <form action="generate_certificate.php" method="post" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                
                <!-- Left: Configuration -->
                <div class="card slide-up">
                    <h3>1. Select Students</h3>
                    <p style="color: #64748b; margin-bottom: 1rem;">Hold Ctrl/Cmd to select multiple.</p>
                    
                    <div class="form-group">
                        <div style="height: 300px; width: 100%; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0.5rem; background: #fff;">
                            <?php if(empty($students)): ?>
                                <div style="color: #94a3b8; padding: 0.5rem; text-align: center;">No students found.</div>
                            <?php else: ?>
                                <div id="student-list-container">
                                    <?php 
                                    // Group students by company first
                                    $grouped = [];
                                    foreach($students as $s) {
                                        $comp = !empty($s['company_name']) ? $s['company_name'] : 'No Company Assigned';
                                        $grouped[$comp][] = $s;
                                    }

                                    foreach($grouped as $company_name => $grp_students): 
                                        $safe_comp_id = md5($company_name); // Unique ID for js
                                    ?>
                                        <details class="company-group-details" open style="margin-bottom: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px;">
                                            <summary style="background: #f8fafc; padding: 0.5rem; cursor: pointer; font-weight: 600; color: #475569; outline: none; list-style: none; display: flex; align-items: center; justify-content: space-between;">
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <!-- Per-Company Select All -->
                                                    <!-- Stop propagation to prevent toggling details when clicking checkbox -->
                                                    <input type="checkbox" class="company-select-all" data-target="<?php echo $safe_comp_id; ?>" onclick="event.stopPropagation()">
                                                    <span><?php echo htmlspecialchars($company_name); ?></span>
                                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: normal;">(<?php echo count($grp_students); ?>)</span>
                                                </div>
                                                <span style="font-size: 0.8rem; color: #cbd5e1;">▼</span>
                                            </summary>
                                            
                                            <div class="company-students-list" id="<?php echo $safe_comp_id; ?>" style="padding: 0.5rem;">
                                                <?php foreach($grp_students as $s): ?>
                                                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                                        <input type="checkbox" name="student_ids[]" value="<?php echo $s['user_id']; ?>" class="student-checkbox group-<?php echo $safe_comp_id; ?>">
                                                        <span><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <script>
                            // Per-Company Select All
                            document.querySelectorAll('.company-select-all').forEach(groupCb => {
                                groupCb.addEventListener('change', function() {
                                    const targetId = this.dataset.target;
                                    const children = document.querySelectorAll('.student-checkbox.group-' + targetId);
                                    children.forEach(cb => cb.checked = this.checked);
                                });
                            });

                            // Individual Checkbox Logic (Update parents)
                            const allStudentCbs = document.querySelectorAll('.student-checkbox');
                            allStudentCbs.forEach(cb => {
                                cb.addEventListener('change', function() {
                                    // Update Company Parent
                                    const groupClass = Array.from(this.classList).find(c => c.startsWith('group-'));
                                    if(groupClass) {
                                        const groupId = groupClass.replace('group-', '');
                                        const groupCb = document.querySelector('.company-select-all[data-target="' + groupId + '"]');
                                        const groupSiblings = document.querySelectorAll('.' + groupClass);
                                        const allChecked = Array.from(groupSiblings).every(c => c.checked);
                                        if (groupCb) groupCb.checked = allChecked;
                                    }
                                });
                            });
                        </script>
                    </div>
                </div>

                <!-- Right: Template Upload -->
                <div class="card slide-up" style="animation-delay: 0.1s;">
                    <h3>2. Upload Template</h3>
                    <p style="color: #64748b; margin-bottom: 1rem;">
                        Upload a <strong>.docx</strong> Word file.
                    </p>
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px dashed #cbd5e1; margin-bottom: 1.5rem;">
                        <strong>Instructions:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem; color: #475569; font-size: 0.9rem;">
                            <li>Open Microsoft Word.</li>
                            <li>Design your certificate.</li>
                            <li>Type <code>student_name</code> where you want the name.</li>

                            <li>Save as <strong>.docx</strong>.</li>
                        </ul>
                    </div>
                    
                    <div class="form-group">
                        <label>Template File (.docx)</label>
                        <input type="file" name="template_file" id="template_file" accept=".docx" required>
                        <button type="button" id="btn-verify" class="btn btn-secondary btn-sm" style="margin-top:0.5rem;">Verify Template</button>
                        <div id="verify-feedback" style="margin-top:0.5rem; font-size:0.9rem;"></div>
                    </div>



                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        Generate Certificates
                    </button>
                </div>
            </div>
        </form>
    </div>
</body>
    <script>
        const btnVerify = document.getElementById('btn-verify');
        const fileInput = document.getElementById('template_file');
        const feedback = document.getElementById('verify-feedback');

        btnVerify.addEventListener('click', () => {
            if (fileInput.files.length === 0) {
                feedback.innerHTML = '<span style="color:red">Please select a file first.</span>';
                return;
            }

            const formData = new FormData();
            formData.append('template_file', fileInput.files[0]);

            feedback.innerHTML = '<span style="color:gray">Analyzing...</span>';

            fetch('analyze_template.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedback.innerHTML = `<div style="color:green; font-weight:bold; margin-bottom:0.25rem;">${data.message}</div><div style="padding:0.5rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:4px; color:#166534;">${data.preview}</div>`;
                } else {
                    feedback.innerHTML = `<div style="color:red; font-weight:bold;">${data.message}</div>`;
                }
            })
            .catch(err => {
                feedback.innerHTML = '<span style="color:red">Error verifying file.</span>';
                console.error(err);
            });
        });
    </script>
</body>
</html>
