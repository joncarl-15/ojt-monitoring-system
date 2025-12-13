<?php
require_once 'config.php';

echo "Starting Schema Repair...<br>";

function addColumnIfNotExists($conn, $table, $column, $definition) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check->num_rows == 0) {
            echo "Adding column `$column` to table `$table`... ";
            if ($conn->query("ALTER TABLE `$table` ADD COLUMN $column $definition")) {
                echo "Done.<br>";
                return true;
            } else {
                echo "Failed: " . $conn->error . "<br>";
                return false;
            }
        } else {
            echo "Column `$column` already exists in `$table`.<br>";
            return true;
        }
    } catch (Exception $e) {
        echo "Error checking column `$column`: " . $e->getMessage() . "<br>";
        return false;
    }
}

function addIndexIfNotExists($conn, $table, $indexName, $column) {
    try {
        $check = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        if ($check->num_rows == 0) {
            echo "Adding index `$indexName` to table `$table`... ";
            if ($conn->query("CREATE INDEX `$indexName` ON `$table`($column)")) {
                echo "Done.<br>";
            } else {
                echo "Failed: " . $conn->error . "<br>";
            }
        } else {
            echo "Index `$indexName` already exists.<br>";
        }
    } catch (Exception $e) {
        echo "Error checking index `$indexName`: " . $e->getMessage() . "<br>";
    }
}

function addConstraintIfNotExists($conn, $table, $constraintName, $sql) {
    try {
        // Checking constraints is harder in pure SQL across versions, looking for FK usage usually implies checking information_schema
        $check = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '$table' AND CONSTRAINT_NAME = '$constraintName' AND TABLE_SCHEMA = DATABASE()");
        if ($check && $check->num_rows == 0) {
             echo "Adding constraint `$constraintName` to `$table`... ";
             if ($conn->query("ALTER TABLE `$table` ADD CONSTRAINT `$constraintName` $sql")) {
                 echo "Done.<br>";
             } else {
                 echo "Failed: " . $conn->error . "<br>";
             }
        } else {
            echo "Constraint `$constraintName` already exists (or cannot verify easily).<br>";
        }
    } catch (Exception $e) {
        echo "Error checking constraint `$constraintName`: " . $e->getMessage() . "<br>";
    }
}

// 1. Add company_id to announcements
if (addColumnIfNotExists($conn, 'announcements', 'company_id', 'INT DEFAULT NULL')) {
    // 2. Add FK
    addConstraintIfNotExists($conn, 'announcements', 'fk_announcement_company', 'FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE');
    // 3. Add Index
    addIndexIfNotExists($conn, 'announcements', 'idx_announcements_company_id', 'company_id');
}

// 4. Ensure Users has profile_picture
addColumnIfNotExists($conn, 'users', 'profile_picture', 'VARCHAR(255) DEFAULT NULL');

echo "Schema Repair Completed.";
?>
