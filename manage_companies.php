<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Get all companies from coordinators (coordinator users)
$stmt = $conn->prepare("
    SELECT c.coordinator_id, c.company_name, c.company_address as address, 
           c.contact_number, c.email, c.department, c.bio,
           u.username as coordinator_username
    FROM coordinators c
    JOIN users u ON c.user_id = u.user_id AND u.user_type = 'coordinator'
    ORDER BY c.created_at DESC
");
$stmt->execute();
$companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Companies - OJT Monitoring System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <header class="dashboard-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="dashboard.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">&larr;
                Back</a>
            <h1>Manage Companies</h1>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: var(--primary-dark); margin: 0;">All Companies</h2>                <a href="appoint_company.php" class="btn" style="padding: 0.75rem 1.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                        <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 11L20 8M20 8L17 11M20 8V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Appoint Company to Student
                </a>

            </div>

            <?php if (empty($companies)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-light);">No companies found.</div>
            <?php else: ?>
                <div class="grid-container">
                    <?php foreach ($companies as $company): ?>
                        <div
                            style="background: #f9fafb; padding: 1.5rem; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color); transition: transform var(--transition-speed); box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; font-size: 1.1rem;">
                                <?php echo htmlspecialchars($company['company_name']); ?></div>
                            <div style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;">
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: var(--primary-dark);">Coordinator:</strong>
                                    <?php echo htmlspecialchars($company['coordinator_username'] ?? 'N/A'); ?>
                                </div>
                                <?php if (!empty($company['department'])): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: var(--primary-dark);">Department:</strong>
                                    <?php echo htmlspecialchars($company['department']); ?>
                                </div>
                                <?php endif; ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: var(--primary-dark);">Contact:</strong>
                                    <?php echo htmlspecialchars($company['contact_number']); ?>
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong style="color: var(--primary-dark);">Email:</strong>
                                    <?php echo htmlspecialchars($company['email'] ?? 'N/A'); ?>
                                </div>
                                <div>
                                    <strong style="color: var(--primary-dark);">Address:</strong>
                                    <?php echo htmlspecialchars($company['address']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>