<?php
require_once 'config.php';
echo "<h1>Company Linkage Fixer</h1>";

// 1. Get all Coordinators
$coords = $conn->query("SELECT * FROM coordinators");

while ($coord = $coords->fetch_assoc()) {
    $c_name = $coord['company_name'];
    $u_id = $coord['user_id'];
    
    echo "Processing Coordinator: <strong>{$c_name}</strong> (User ID: $u_id)<br>";
    
    // 2. Search for Company by Name
    $comp_check = $conn->prepare("SELECT * FROM companies WHERE company_name = ?");
    $comp_check->bind_param("s", $c_name);
    $comp_check->execute();
    $res = $comp_check->get_result();
    
    if ($company = $res->fetch_assoc()) {
        echo "Found matching Company record: [ID: {$company['company_id']}] {$company['company_name']}<br>";
        
        // 3. Check ownership
        if ($company['user_id'] == $u_id) {
            echo "<span style='color:green'>✓ Already linked correctly.</span><br><br>";
        } else {
            // 4. Update Ownership
            echo "Current Owner: " . ($company['user_id'] ? $company['user_id'] : "None") . ". Updating to $u_id... ";
            
            $update = $conn->prepare("UPDATE companies SET user_id = ? WHERE company_id = ?");
            $update->bind_param("ii", $u_id, $company['company_id']);
            
            if ($update->execute()) {
                echo "<span style='color:green'>✓ FIXED! Link established.</span><br><br>";
            } else {
                echo "<span style='color:red'>✗ Failed to update: " . $conn->error . "</span><br><br>";
            }
        }
    } else {
        echo "<span style='color:orange'>⚠ No matching Company record found. Creating one...</span><br>";
        // Logic to create if missing (similar to manage_announcements fallback)
        $ins = $conn->prepare("INSERT INTO companies (user_id, company_name, address, supervisor_name, contact_number, email) VALUES (?, ?, ?, ?, ?, ?)");
        $sup_name = "Coordinator";
        $ins->bind_param("isssss", $u_id, $coord['company_name'], $coord['company_address'], $sup_name, $coord['contact_number'], $coord['email']);
        if ($ins->execute()) {
             echo "<span style='color:green'>✓ Created new Company record linked to Coordinator.</span><br><br>";
        } else {
             echo "<span style='color:red'>✗ Failed create: " . $conn->error . "</span><br><br>";
        }
    }
}

echo "<hr><a href='dashboard.php'>Return to Dashboard</a>";
?>
