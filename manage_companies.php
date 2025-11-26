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
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .company-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .company-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }

        .company-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .company-info {
            color: #666;
            font-size: 13px;
            line-height: 1.8;
        }

        .company-info strong {
            display: block;
            color: #333;
            margin-bottom: 3px;
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
        <h1>Manage Companies</h1>
    </header>

    <div class="container">
        <div class="card">
            <h2>All Companies</h2>
            <?php if (empty($companies)): ?>
                <div class="no-data">No companies found.</div>
            <?php else: ?>
                <div class="company-grid">
                    <?php foreach ($companies as $company): ?>
                    <div class="company-card">
                        <div class="company-name"><?php echo htmlspecialchars($company['company_name']); ?></div>
                        <div class="company-info">
                            <strong>Supervisor:</strong> <?php echo htmlspecialchars($company['supervisor_name']); ?>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($company['contact_number']); ?>
                            <strong>Email:</strong> <?php echo htmlspecialchars($company['email'] ?? 'N/A'); ?>
                            <strong>Address:</strong> <?php echo htmlspecialchars($company['address']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
