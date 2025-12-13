<?php
require_once 'config.php';

// Prevent accidental execution
if (!isset($_GET['confirm']) || $_GET['confirm'] != 'yes') {
    die("<h1>Database Seeder</h1><p>This will insert test data. <a href='?confirm=yes'>Click here to confirm</a>.</p>");
}

// Configuration
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);

$companies_list = [
    ['San Miguel Corporation', 'Mandaluyong City', 'sanmiguel.com.ph'],
    ['Jollibee Foods Corp', 'Pasig City', 'jollibee.com.ph'],
    ['BDO Unibank', 'Makati City', 'bdo.com.ph'],
    ['Ayala Land Inc', 'Makati City', 'ayalaland.com.ph'],
    ['SM Prime Holdings', 'Pasay City', 'smprime.com'],
    ['Globe Telecom', 'Taguig City', 'globe.com.ph'],
    ['PLDT Inc', 'Makati City', 'pldt.com'],
    ['Metropolitan Bank & Trust', 'Makati City', 'metrobank.com.ph'],
    ['JG Summit Holdings', 'Pasig City', 'jgsummit.com.ph'],
    ['Aboitiz Power Corp', 'Taguig City', 'aboitizpower.com'],
    ['Robinsons Retail', 'Quezon City', 'robinsons.com.ph'],
    ['Bank of the Philippine Islands', 'Makati City', 'bpi.com.ph'],
    ['International Container Services', 'Manila', 'ictsi.com'],
    ['Puregold Price Club', 'Manila', 'puregold.com.ph'],
    ['DMCI Holdings', 'Makati City', 'dmci.com']
];

$courses = ['BS Information Technology', 'BS Computer Science', 'BS Computer Engineering', 'BS Information Systems'];
$year_levels = ['3rd Year', '4th Year'];

// Huge list of unique first names to support 100+ unique users
$first_names = [
    'Juan', 'Pedro', 'Maria', 'Jose', 'Ana', 'Luis', 'Carmela', 'Ramon', 'Clara', 'Antonio', 
    'Sofia', 'Miguel', 'Rosa', 'Francisco', 'Elena', 'Carlo', 'Sebastian', 'Gabriel', 'Angel', 'Marco', 
    'Julia', 'Roberto', 'Teresa', 'Paolo', 'Bea', 'Diego', 'Isabel', 'Ricardo', 'Lia', 'Fernando',
    'Patricia', 'Manuel', 'Cecilia', 'Eduardo', 'Bianca', 'Rafael', 'Camila', 'Lorenzo', 'Nina', 'Adriana',
    'Leo', 'Maia', 'Javier', 'Selena', 'Mateo', 'Hannah', 'Lucas', 'Stella', 'Nathan', 'Chloe',
    'Ethan', 'Grace', 'Daniel', 'Zoe', 'Caleb', 'Lily', 'Isaac', 'Ava', 'Sam', 'Mia',
    'Ben', 'Ella', 'Alex', 'Maya', 'Jake', 'Kara', 'Liam', 'Mara', 'Noah', 'Tala',
    'Elias', 'Aria', 'Joaquin', 'Luna', 'Emilio', 'Nica', 'Dante', 'Rina', 'Vince', 'Tina',
    'Gino', 'Liza', 'Rico', 'Gina', 'Tito', 'Mina', 'Nico', 'Rita', 'Pio', 'Dina',
    'Bong', 'Lita', 'Kiko', 'Tita', 'Jojo', 'Gigi', 'Pepe', 'Mimi', 'Nonoy', 'Vicky',
    'Chito', 'Baby', 'Butch', 'Pinky', 'Sonny', 'Lenny', 'Jun', 'Tess', 'Boy', 'Lolly'
];
// Shuffle to randomize assignment
shuffle($first_names);

$last_names = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Gonzales', 'Rivera', 'Castro', 'Villanueva', 'Ramos', 'Aquino', 'DelRosario', 'Lim', 'Tan', 'Magno', 'Pena', 'David', 'Salvador', 'Mercado', 'Pineda', 'DeLeon'];

function get_random($arr) {
    return $arr[array_rand($arr)];
}

echo "<div style='font-family: monospace;'>";
echo "<h2>Starting Seeding...</h2>";

// 1. Insert Companies
$company_ids = []; // name => id
$company_domains = []; // id => domain
$stmt_comp = $conn->prepare("INSERT INTO companies (company_name, address, supervisor_name, contact_number, email) VALUES (?, ?, ?, '09171234567', ?)");

foreach ($companies_list as $comp) {
    $name = $comp[0];
    $addr = $comp[1];
    $domain = $comp[2];
    $sup = "Sup. " . get_random($last_names);
    $email = "info@" . $domain;
    
    $stmt_comp->bind_param("ssss", $name, $addr, $sup, $email);
    if ($stmt_comp->execute()) {
        $cid = $conn->insert_id;
        $company_ids[$name] = $cid;
        $company_domains[$cid] = $domain;
        echo "Created Company: $name<br>";
    }
}

// Global iterator for names to ensure uniqueness
$name_idx = 0;

// 2. Create 50 Coordinators
$stmt_user = $conn->prepare("INSERT INTO users (username, email, password_hash, user_type, is_active) VALUES (?, ?, ?, 'coordinator', 1)");
$stmt_coord = $conn->prepare("INSERT INTO coordinators (user_id, company_name, company_address, contact_number, email) VALUES (?, ?, ?, '09170000000', ?)");

for ($i = 0; $i < 50; $i++) {
    $comp_idx = array_rand($companies_list);
    $comp_data = $companies_list[$comp_idx];
    $comp_name = $comp_data[0];
    $comp_addr = $comp_data[1];
    $comp_domain = $comp_data[2];

    // Use unique first name as username
    $fname = $first_names[$name_idx++];
    $lname = get_random($last_names);
    
    $username = strtolower($fname); // e.g. "carlo"
    $email = $username . "@" . $comp_domain;
    
    // Create User
    $stmt_user->bind_param("sss", $username, $email, $hash);
    if ($stmt_user->execute()) {
        $uid = $conn->insert_id;
        $stmt_coord->bind_param("isss", $uid, $comp_name, $comp_addr, $email);
        $stmt_coord->execute();
        echo "Created Coordinator: $username ($email)<br>";
    }
}

// 3. Create 50 Students
$stmt_user_stud = $conn->prepare("INSERT INTO users (username, email, password_hash, user_type, is_active) VALUES (?, ?, ?, 'student', 1)");
$stmt_stud = $conn->prepare("INSERT INTO students (user_id, first_name, last_name, course, year_level, company_id, email_address) VALUES (?, ?, ?, ?, ?, ?, ?)");

for ($i = 0; $i < 50; $i++) {
    $c_name = array_rand($company_ids);
    $c_id = $company_ids[$c_name];
    
    // Use unique first name as username
    $fname = $first_names[$name_idx++];
    $lname = get_random($last_names);
    
    $username = strtolower($fname); // e.g. "sebastian"
    $email = $username . "@student.edu";
    
    $course = get_random($courses);
    $year = get_random($year_levels);
    
    $stmt_user_stud->bind_param("sss", $username, $email, $hash);
    if ($stmt_user_stud->execute()) {
        $uid = $conn->insert_id;
        $stmt_stud->bind_param("issssis", $uid, $fname, $lname, $course, $year, $c_id, $email);
        $stmt_stud->execute();
        echo "Created Student: $username ($c_name)<br>";
    }
}

echo "<h1>Seeding Completed!</h1>";
echo "<a href='index.php'>Go to Login</a>";
echo "</div>";
?>
