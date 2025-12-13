<?php
require_once 'config.php';
echo "<h1>Debug Data</h1>";

echo "<h2>Companies</h2>";
$res = $conn->query("SELECT * FROM companies");
echo "<table border=1><tr><th>ID</th><th>Name</th><th>User ID (Owner)</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['company_id']}</td><td>{$row['company_name']}</td><td>{$row['user_id']}</td></tr>";
}
echo "</table>";

echo "<h2>Coordinators</h2>";
$res = $conn->query("SELECT * FROM coordinators");
echo "<table border=1><tr><th>ID</th><th>Name</th><th>User ID</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['coordinator_id']}</td><td>{$row['company_name']}</td><td>{$row['user_id']}</td></tr>";
}
echo "</table>";

echo "<h2>Students</h2>";
$res = $conn->query("SELECT s.student_id, s.first_name, s.company_id, c.company_name FROM students s LEFT JOIN companies c ON s.company_id = c.company_id");
echo "<table border=1><tr><th>ID</th><th>Name</th><th>Assigned Company ID</th><th>Company Name</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['student_id']}</td><td>{$row['first_name']}</td><td>{$row['company_id']}</td><td>{$row['company_name']}</td></tr>";
}
echo "</table>";

echo "<h2>Announcements</h2>";
$res = $conn->query("SELECT a.announcement_id, a.title, a.company_id FROM announcements a");
echo "<table border=1><tr><th>ID</th><th>Title</th><th>Linked Company ID</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['announcement_id']}</td><td>{$row['title']}</td><td>{$row['company_id']}</td></tr>";
}
echo "</table>";
?>
