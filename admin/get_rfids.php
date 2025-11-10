<?php
include '../connection.php';
header('Content-Type: application/json');

$sql = "SELECT rfid_number FROM instructor WHERE rfid_number IS NOT NULL AND rfid_number != ''";
$result = $db->query($sql);

$rfids = [];
while ($row = $result->fetch_assoc()) {
    $rfids[] = $row['rfid_number'];
}

echo json_encode([
    'status' => 'success',
    'data' => $rfids
]);
?>