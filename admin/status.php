<?php
include '../connection.php';

// Get total number of personnel (students, instructors, staff)
$total_personnel = 0;
$roles = ['Student', 'Instructor', 'Staff', 'Security Personnel', 'Administrator'];

foreach ($roles as $role) {
    $sql = "SELECT COUNT(*) as count FROM personell WHERE role = '$role' AND status != 'Block'";
    $result = $db->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $total_personnel += $row['count'];
    }
}

// Get number of personnel who entered today
$arrived_today = 0;
$sql = "SELECT COUNT(DISTINCT personnel_id) as count FROM personell_logs WHERE date_logged = CURDATE()";
$result = $db->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $arrived_today = $row['count'];
}

// Calculate not arrived
$not_arrived = $total_personnel - $arrived_today;

// Return data as JSON
echo json_encode([
    'arrived' => $arrived_today,
    'not_arrived' => $not_arrived > 0 ? $not_arrived : 0
]);

mysqli_close($db);
?>