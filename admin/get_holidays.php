<?php
// Include connection
include '../connection.php';

// Get the type parameter (upcoming, past, all)
 $type = $_GET['type'] ?? 'upcoming';

// Get current date
 $today = date('Y-m-d');

// Build the query based on type
switch ($type) {
    case 'upcoming':
        $sql = "SELECT * FROM holidays WHERE date >= ? ORDER BY date ASC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $today);
        break;
    case 'past':
        $sql = "SELECT * FROM holidays WHERE date < ? ORDER BY date DESC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $today);
        break;
    case 'all':
    default:
        $sql = "SELECT * FROM holidays ORDER BY date DESC";
        $stmt = $db->prepare($sql);
        break;
}

 $stmt->execute();
 $result = $stmt->get_result();

 $holidays = [];
while ($row = $result->fetch_assoc()) {
    $holidays[] = $row;
}

 $stmt->close();
 $db->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'holidays' => $holidays
]);
?>