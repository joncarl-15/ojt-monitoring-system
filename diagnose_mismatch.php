<?php
require_once 'config.php';
echo "<pre>";

// 1. Get Coordinator's Company Name
$cid = $_SESSION['user_id'] ?? 0; // Check logical user if logged in, else dump all
echo "<h3>Coordinators</h3>";
$coords = $conn->query("SELECT user_id, company_name FROM coordinators");
while($c = $coords->fetch_assoc()) {
    echo "Coord User[{$c['user_id']}]: '" . $c['company_name'] . "' (Len: " . strlen($c['company_name']) . ")\n";
}

// 2. Get Companies
echo "\n<h3>Companies Table</h3>";
$comps = $conn->query("SELECT company_id, company_name, user_id FROM companies");
while($c = $comps->fetch_assoc()) {
    echo "Company[{$c['company_id']}]: '" . $c['company_name'] . "' (Len: " . strlen($c['company_name']) . ") Owner: " . ($c['user_id'] ? $c['user_id'] : 'NULL') . "\n";
}

// 3. Get Students and their Company IDs
echo "\n<h3>Students</h3>";
$studs = $conn->query("SELECT student_id, first_name, company_id FROM students");
while($s = $studs->fetch_assoc()) {
    echo "Student[{$s['student_id']}]: {$s['first_name']} -> CompanyID: " . ($s['company_id'] ? $s['company_id'] : 'NULL') . "\n";
}

echo "</pre>";
?>
