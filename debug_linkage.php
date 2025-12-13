<?php
require_once 'config.php';
echo "<h1>Linkage Debug</h1>";

echo "<h3>Companies</h3>";
$res = $conn->query("SELECT result.* FROM (SELECT * FROM companies) result");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['company_id']} | Name: {$row['company_name']} | Owner UserID: {$row['user_id']}<br>";
}

echo "<h3>Students</h3>";
$res = $conn->query("SELECT s.student_id, s.first_name, s.company_id FROM students s");
while($row = $res->fetch_assoc()) {
    echo "Student: {$row['first_name']} (ID: {$row['student_id']}) -> Assigned to CompanyID: <strong>" . ($row['company_id'] ?? 'NULL') . "</strong><br>";
}

echo "<h3>Announcements</h3>";
$res = $conn->query("SELECT a.announcement_id, a.title, a.company_id FROM announcements a");
while($row = $res->fetch_assoc()) {
    echo "Announcement: {$row['title']} -> Linked to CompanyID: <strong>" . ($row['company_id'] ?? 'NULL') . "</strong><br>";
}
?>
