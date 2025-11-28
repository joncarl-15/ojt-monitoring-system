<?php
require_once 'config.php';
require_login();

$user = get_user_info($_SESSION['user_id'], $conn);

if ($user['user_type'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Get all companies
$stmt = $conn->prepare("SELECT * FROM companies ORDER BY created_at DESC");
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
        </div>
    </header>

    <div class="main-content fade-in">
        <div class="card slide-up">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: var(--primary-dark); margin: 0;">All Companies</h2>
                <!-- Placeholder for Add Company button if needed in future -->
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
                                    <strong style="color: var(--primary-dark);">Supervisor:</strong>
                                    <?php echo htmlspecialchars($company['supervisor_name']); ?>
                                </div>
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