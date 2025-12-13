<?php
require_once 'config.php';
echo "Debug Counts:<br>";

$tables = ['users', 'students', 'coordinators', 'companies', 'announcements'];
foreach($tables as $t) {
    $res = $conn->query("SELECT COUNT(*) as c FROM $t");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "$t: " . $row['c'] . "<br>";
    } else {
        echo "$t: Error - " . $conn->error . "<br>";
    }
}

echo "<br>User Details (First 5):<br>";
$res = $conn->query("SELECT user_id, username, user_type FROM users LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['user_id']}, Name: {$row['username']}, Type: {$row['user_type']}<br>";
}
?>
