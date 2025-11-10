<?php
// Database credentials from Hostinger
$host = "127.0.0.1";
$username = "u8027114156_rfidgpmsPass";
$password = "1rfidUser2025";
$database = "u8027114156_rfidgpms";
// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set headers for SQL file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="database_backup_' . date('Y-m-d_H-i-s') . '.sql"');
header('Pragma: no-cache');
header('Expires: 0');

// Fetch all table names
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

$backupSql = "-- Database Backup\n";
$backupSql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
$backupSql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    // Drop table if exists
    $backupSql .= "DROP TABLE IF EXISTS `$table`;\n";

    // Get CREATE TABLE statement
    $createTableResult = $conn->query("SHOW CREATE TABLE `$table`");
    $createTableRow = $createTableResult->fetch_array();
    $backupSql .= $createTableRow[1] . ";\n\n";

    // Get data from table
    $dataResult = $conn->query("SELECT * FROM `$table`");
    while ($row = $dataResult->fetch_assoc()) {
        $values = array_map(function($val) use ($conn) {
            if ($val === null) {
                return "NULL";
            }
            return "'" . $conn->real_escape_string($val) . "'";
        }, array_values($row));

        $backupSql .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
    }

    $backupSql .= "\n\n";
}

$backupSql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// Output the SQL content
echo $backupSql;

// Close connection
$conn->close();
?>
