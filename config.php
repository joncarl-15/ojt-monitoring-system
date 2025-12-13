<?php
// ---------------------------
// Database Configuration
// ---------------------------

$servername = "localhost";   // XAMPP MySQL Host
$username   = "root";        // XAMPP default MySQL user
$password   = "";            // XAMPP default: empty password for root
$database   = "database_schema";      // Local database name — change if different

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ---------------------------
// Session + Auth Functions
// ---------------------------

session_start();

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

// Get user info
function get_user_info($user_id, $conn) {
    $stmt = $conn->prepare("
        SELECT user_id, username, email, user_type, profile_picture
        FROM users
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get student info
function get_student_info($user_id, $conn) {
    $stmt = $conn->prepare("
        SELECT s.*, c.company_name
        FROM students s
        LEFT JOIN companies c ON s.company_id = c.company_id
        WHERE s.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get total hours
function get_total_hours($student_id, $conn) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(daily_hours), 0) AS total
        FROM daily_time_records
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'];
}
?>
