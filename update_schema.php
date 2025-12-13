<?php
require_once 'config.php';

// Add company_id to announcements
try {
    $conn->query("ALTER TABLE announcements ADD COLUMN company_id INT DEFAULT NULL");
    $conn->query("ALTER TABLE announcements ADD CONSTRAINT fk_announcement_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE");
    echo "Schema updated successfully: company_id added to announcements.";
} catch (Exception $e) {
    echo "Schema update info: " . $e->getMessage();
}
?>
