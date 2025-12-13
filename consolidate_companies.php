<?php
require_once 'config.php';
echo "<h1>Consolidating Duplicate Companies</h1>";

// 1. Get all Coordinators
$coords = $conn->query("SELECT user_id, company_name FROM coordinators");

while ($coord = $coords->fetch_assoc()) {
    $name = $coord['company_name'];
    $uid = $coord['user_id'];
    
    echo "Processing <strong>$name</strong> (Coordinator UserID: $uid)<br>";
    
    // Find all companies with this name
    $stmt = $conn->prepare("SELECT * FROM companies WHERE company_name = ? ORDER BY company_id ASC");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $duplicates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (count($duplicates) > 1) {
        echo "Found " . count($duplicates) . " records. Merging...<br>";
        
        // Determine Master: Prefer the one with user_id = Coordinator
        $master_id = null;
        foreach ($duplicates as $d) {
            if ($d['user_id'] == $uid) {
                $master_id = $d['company_id'];
                break;
            }
        }
        
        // If no owner match, pick the first one
        if (!$master_id) {
            $master_id = $duplicates[0]['company_id'];
            // Check if we need to set owner
             $conn->query("UPDATE companies SET user_id = $uid WHERE company_id = $master_id");
        }
        
        echo "Master Company ID: <strong>$master_id</strong><br>";
        
        // Merge others
        foreach ($duplicates as $d) {
            if ($d['company_id'] != $master_id) {
                $dup_id = $d['company_id'];
                echo "Merging Duplicate ID: $dup_id... ";
                
                // Move Students
                $conn->query("UPDATE students SET company_id = $master_id WHERE company_id = $dup_id");
                
                // Move Announcements
                $conn->query("UPDATE announcements SET company_id = $master_id WHERE company_id = $dup_id");
                
                // Delete Duplicate
                if ($conn->query("DELETE FROM companies WHERE company_id = $dup_id")) {
                    echo "Merged & Deleted.<br>";
                } else {
                    echo "Failed to delete: " . $conn->error . "<br>";
                }
            }
        }
        echo "<span style='color:green'>✓ Consolidation Complete.</span><br><br>";
        
    } else {
        echo "No duplicates found.<br><br>";
    }
}
echo "<hr><a href='view_students.php'>Check View Students</a>";
?>
