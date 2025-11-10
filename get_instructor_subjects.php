<?php
// get_instructor_subjects.php - COMPLETELY REWRITTEN

// Turn on all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type first
header('Content-Type: application/json; charset=utf-8');

// Prevent any output that might break JSON
ob_start();

try {
    // Include connection
    require_once 'connection.php';
    
    // Get parameters
    $id_number = isset($_GET['id_number']) ? trim($_GET['id_number']) : '';
    $room_name = isset($_GET['room_name']) ? trim($_GET['room_name']) : '';
    
    // Validate
    if (empty($id_number)) {
        throw new Exception('ID number is required');
    }
    if (empty($room_name)) {
        throw new Exception('Room name is required');
    }
    
    // Log for debugging
    error_log("API Call - ID: $id_number, Room: $room_name");
    
    // Check database connection
    if (!$db || $db->connect_error) {
        throw new Exception('Database connection failed: ' . ($db->connect_error ?? 'Unknown error'));
    }
    
    // 1. Find instructor by ID number (with or without hyphens)
    $instructor_query = "SELECT id, fullname, id_number FROM instructor WHERE REPLACE(id_number, '-', '') = ?";
    $stmt = $db->prepare($instructor_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare instructor query: ' . $db->error);
    }
    
    $stmt->bind_param("s", $id_number);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute instructor query: ' . $stmt->error);
    }
    
    $instructor_result = $stmt->get_result();
    $instructor = $instructor_result->fetch_assoc();
    
    if (!$instructor) {
        // Try with original ID format in case there's a match
        $stmt2 = $db->prepare("SELECT id, fullname, id_number FROM instructor WHERE id_number LIKE ? LIMIT 1");
        $search_id = '%' . $id_number . '%';
        $stmt2->bind_param("s", $search_id);
        $stmt2->execute();
        $instructor = $stmt2->get_result()->fetch_assoc();
        
        if (!$instructor) {
            throw new Exception("No instructor found with ID: $id_number");
        }
    }
    
    // 2. Get schedules for this instructor in the specified room
    $schedule_query = "
        SELECT 
            subject, 
            section, 
            day, 
            start_time, 
            end_time,
            room_name
        FROM room_schedules 
        WHERE instructor = ? 
        AND room_name = ?
        ORDER BY 
            FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            start_time
    ";
    
    $stmt = $db->prepare($schedule_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare schedule query: ' . $db->error);
    }
    
    $stmt->bind_param("ss", $instructor['fullname'], $room_name);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute schedule query: ' . $stmt->error);
    }
    
    $schedule_result = $stmt->get_result();
    $schedules = [];
    
    while ($row = $schedule_result->fetch_assoc()) {
        $schedules[] = $row;
    }
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => $schedules,
        'debug_info' => [
            'instructor_name' => $instructor['fullname'],
            'instructor_id' => $instructor['id_number'],
            'room_requested' => $room_name,
            'schedules_found' => count($schedules)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'received_id' => $id_number ?? 'not set',
            'received_room' => $room_name ?? 'not set'
        ]
    ], JSON_PRETTY_PRINT);
} finally {
    // Ensure no extra output
    if (ob_get_level()) {
        ob_end_clean();
    }
}
?>