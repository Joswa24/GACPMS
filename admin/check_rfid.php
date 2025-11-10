<?php
include '../connection.php';

if ($db->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get the RFID number from the AJAX request
$rfid_number = $_POST['id_number'] ?? '';

// Prevent SQL injection
$id_number = $db->real_escape_string($id_number);

// Query to check if RFID exists
$sql = "SELECT COUNT(*) as count FROM personell WHERE id_number = '$id_number'";
$result = $db->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo json_encode(['exists' => $row['count'] > 0]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error executing query']);
}

$db->close();
