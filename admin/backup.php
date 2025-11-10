<?php
session_start();
include 'connection.php';
// Include connection
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Check if user is logged in and 2FA verified
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: index.php');
    exit();
}
// Check if connection is successful
if (!$db) {
    header('HTTP/1.1 500 Internal Server Error');
    die("Database connection failed: " . mysqli_connect_error());
}

// Get filename from query parameter or generate one
$filename = $_GET['download'] ?? 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Set headers for file download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Get all table names
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $output = "-- Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: " . mysqli_get_host_info($db) . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Iterate through each table
    foreach ($tables as $table) {
        // Drop table if exists
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get create table statement
        $createTable = $db->query("SHOW CREATE TABLE `$table`");
        $row = $createTable->fetch_row();
        $output .= "\n" . $row[1] . ";\n\n";
        
        // Get table data
        $data = $db->query("SELECT * FROM `$table`");
        $numFields = $data->field_count;
        
        // Insert data
        while ($row = $data->fetch_row()) {
            $output .= "INSERT INTO `$table` VALUES(";
            
            for ($j = 0; $j < $numFields; $j++) {
                $row[$j] = addslashes($row[$j] ?? '');
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                
                if (isset($row[$j])) {
                    $output .= '"' . $row[$j] . '"';
                } else {
                    $output .= '""';
                }
                
                if ($j < ($numFields - 1)) {
                    $output .= ',';
                }
            }
            
            $output .= ");\n";
        }
        
        $output .= "\n";
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $output .= "-- Backup completed successfully\n";

    // Output the SQL file content
    echo $output;

} catch (Exception $e) {
    // If headers already sent, output as JSON for error handling
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit();
?>