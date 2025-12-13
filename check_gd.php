<?php
echo "GD Status: ";
if (extension_loaded('gd')) {
    echo "ACTIVE";
    echo "\nGD Info: " . print_r(gd_info(), true);
} else {
    echo "INACTIVE";
    echo "\nLoaded Configuration File: " . php_ini_loaded_file();
    echo "\nAdditional .ini files: " . php_ini_scanned_files();
}
?>
