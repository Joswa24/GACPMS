<?php
date_default_timezone_set('Asia/Manila');
session_start();

// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include 'connection.php';

// Function to get recent gate logs
function getRecentGateLogs($db, $limit = 5) {
    $query = "SELECT 
        gl.*,
        COALESCE(
            s.fullname,
            i.fullname,
            CONCAT_WS(' ', p.first_name, COALESCE(p.middle_name, ''), p.last_name),
            v.name,
            gl.name
        ) as full_name,
        gl.person_type,
        gl.direction,
        gl.department,
        gl.location,
        gl.created_at
    FROM gate_logs gl
    LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
    LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
    LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
    LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
    WHERE DATE(gl.created_at) = CURDATE()
    ORDER BY gl.created_at DESC
    LIMIT ?";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        error_log("Recent logs query failed: " . $db->error);
        return [];
    }
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = [
                'time' => date('h:i A', strtotime($row['created_at'])),
                'id_number' => $row['id_number'],
                'full_name' => $row['full_name'],
                'person_type' => $row['person_type'],
                'direction' => $row['direction']
            ];
        }
    }
    
    return $logs;
}

// Get recent logs
 $recent_logs = getRecentGateLogs($db, 5);

// Return JSON response
header("Content-Type: application/json");
echo json_encode([
    'success' => true,
    'logs' => $recent_logs
]);
?>