<?php
// THIS MUST BE THE VERY FIRST LINE - NO EXCEPTIONS!
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_ERRor', 1);

// Check if user is logged in and 2FA verified
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: index.php');
    exit();
}

// Include connection
include '../connection.php';
date_default_timezone_set('Asia/Manila');

// Function to send JSON response
function jsonResponse($status, $message, $data = []) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Function to handle file uploads
function handleFileUpload($fileInput, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 2 * 1024 * 1024) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $file = $_FILES[$fileInput];

    // Check file type
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG and PNG images are allowed'];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Maximum file size is 2MB'];
    }

    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $targetFile = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

// Get the action from the request
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Check if this is an AJAX request for specific operations
$validAjaxActions = [
    'add_department', 'update_department', 'delete_department', 
    'add_room', 'update_room', 'delete_room',
    'add_role', 'update_role', 'delete_role',
    'add_personnel', 'update_personnel', 'delete_personnel',
    'add_student', 'update_student', 'delete_student',
    'add_instructor', 'update_instructor', 'delete_instructor',
    'add_subject', 'update_subject', 'delete_subject',
    'add_schedule', 'update_schedule', 'delete_schedule',
    'add_visitor', 'update_visitor', 'delete_visitor',
    // SIMPLIFIED SWAP SCHEDULE ACTIONS
    'get_all_rooms', 'get_instructors_by_room', 'get_room_days',
    'get_instructor_schedule', 'swap_time_schedule', 'get_active_swaps', 'revert_swap',
    'find_all_schedules_for_swap'
];

$isAjaxRequest = isset($_GET['action']) && in_array($_GET['action'], $validAjaxActions);

if ($isAjaxRequest) {
    // For AJAX requests, handle specific actions
    switch ($_GET['action']) {
        // ========================
        // ROOM CRUD OPERATIONS - FIXED
        // ========================
        case 'add_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = ['roomdpt', 'roomrole', 'roomname', 'roomdesc', 'roompass'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('room', '', $field));
                }
            }

            // Sanitize inputs
            $department = sanitizeInput($db, $_POST['roomdpt']);
            $role = sanitizeInput($db, $_POST['roomrole']);
            $room = sanitizeInput($db, $_POST['roomname']);
            $descr = sanitizeInput($db, $_POST['roomdesc']);
            $password = sanitizeInput($db, $_POST['roompass']);

            // Validate lengths
            if (strlen($room) > 100) {
                jsonResponse('error', 'Room name must be less than 100 characters');
            }

            if (strlen($descr) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            if (strlen($password) < 6) {
                jsonResponse('error', 'Password must be at least 6 characters');
            }

            // Check if room exists in department
            $check = $db->prepare("SELECT id FROM rooms WHERE room = ? AND department = ?");
            $check->bind_param("ss", $room, $department);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $check->close();
                jsonResponse('error', 'Room already exists in this department');
            }
            $check->close();

            // Insert room
            $stmt = $db->prepare("INSERT INTO rooms (room, authorized_personnel, department, password, descr) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                jsonResponse('error', 'Database prepare failed: ' . $db->error);
            }
            
            $stmt->bind_param("sssss", $room, $role, $department, $password, $descr);

            if ($stmt->execute()) {
                jsonResponse('success', 'Room added successfully');
            } else {
                jsonResponse('error', 'Failed to add room: ' . $stmt->error);
            }
            break;

        case 'update_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

           

            $required = ['roomdpt', 'roomrole', 'roomname', 'roomdesc', 'roompass'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('room', '', $field));
                }
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $department = sanitizeInput($db, $_POST['roomdpt']);
            $role = sanitizeInput($db, $_POST['roomrole']);
            $room = sanitizeInput($db, $_POST['roomname']);
            $descr = sanitizeInput($db, $_POST['roomdesc']);
            $password = sanitizeInput($db, $_POST['roompass']);

            // Validate ID
            if ($id <= 0) {
                jsonResponse('error', 'Invalid room ID');
            }

            // Validate lengths
            if (strlen($room) > 100) {
                jsonResponse('error', 'Room name must be less than 100 characters');
            }

            if (strlen($descr) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            if (strlen($password) < 6) {
                jsonResponse('error', 'Password must be at least 6 characters');
            }

            // Check if room exists in department (excluding current room)
            $check = $db->prepare("SELECT id FROM rooms WHERE room = ? AND department = ? AND id != ?");
            $check->bind_param("ssi", $room, $department, $id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $check->close();
                jsonResponse('error', 'Room already exists in this department');
            }
            $check->close();

            // Update room
            $stmt = $db->prepare("UPDATE rooms SET room = ?, authorized_personnel = ?, department = ?, password = ?, descr = ? WHERE id = ?");
            if (!$stmt) {
                jsonResponse('error', 'Database prepare failed: ' . $db->error);
            }
            
            $stmt->bind_param("sssssi", $room, $role, $department, $password, $descr, $id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Room updated successfully');
            } else {
                jsonResponse('error', 'Failed to update room: ' . $stmt->error);
            }
            break;

        case 'delete_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Room ID is required');
            }

            // Sanitize input
            $id = intval($_POST['id']);

            if ($id <= 0) {
                jsonResponse('error', 'Invalid room ID');
            }

            // Check if room exists first
            $checkRoom = $db->prepare("SELECT id FROM rooms WHERE id = ?");
            $checkRoom->bind_param("i", $id);
            $checkRoom->execute();
            $checkRoom->store_result();
            
            if ($checkRoom->num_rows === 0) {
                $checkRoom->close();
                jsonResponse('error', 'Room not found');
            }
            $checkRoom->close();

            // Check for room dependencies (scheduled classes)
            $checkSchedules = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE room_name = (SELECT room FROM rooms WHERE id = ?)");
            $checkSchedules->bind_param("i", $id);
            $checkSchedules->execute();
            $checkSchedules->bind_result($scheduleCount);
            $checkSchedules->fetch();
            $checkSchedules->close();

            if ($scheduleCount > 0) {
                jsonResponse('error', 'Cannot delete room with scheduled classes');
            }

            // Delete room
            $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
            if (!$stmt) {
                jsonResponse('error', 'Database prepare failed: ' . $db->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Room deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete room: ' . $stmt->error);
            }
            break;

        // Add other cases here as needed...

        default:
            jsonResponse('error', 'Invalid action');
    }
} else {
    // Handle non-AJAX actions (your existing standalone functions)
    switch ($action) {
        case 'find_schedules_for_swap':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
                exit;
            }

            $instructor1 = $_POST['instructor1'];
            $instructor2 = $_POST['instructor2'];
            $room = $_POST['room'];
            $day = $_POST['day'];
            
            // Find schedule for first instructor
            $stmt1 = $db->prepare("SELECT * FROM room_schedules WHERE instructor = ? AND room_name = ? AND day = ?");
            $stmt1->bind_param("sss", $instructor1, $room, $day);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $schedule1 = $result1->fetch_assoc();
            
            // Find schedule for second instructor
            $stmt2 = $db->prepare("SELECT * FROM room_schedules WHERE instructor = ? AND room_name = ? AND day = ?");
            $stmt2->bind_param("sss", $instructor2, $room, $day);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $schedule2 = $result2->fetch_assoc();
            
            if ($schedule1 && $schedule2) {
                echo json_encode([
                    'status' => 'success',
                    'schedule1' => $schedule1,
                    'schedule2' => $schedule2
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Could not find schedules for both instructors in the specified room and day.'
                ]);
            }
            break;

        case 'swap_schedules':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
                exit;
            }

            $schedule1_id = $_POST['schedule1_id'];
            $schedule2_id = $_POST['schedule2_id'];
            
            // Get the schedules
            $stmt1 = $db->prepare("SELECT * FROM room_schedules WHERE id = ?");
            $stmt1->bind_param("i", $schedule1_id);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $schedule1 = $result1->fetch_assoc();
            
            $stmt2 = $db->prepare("SELECT * FROM room_schedules WHERE id = ?");
            $stmt2->bind_param("i", $schedule2_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $schedule2 = $result2->fetch_assoc();
            
            if (!$schedule1 || !$schedule2) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'One or both schedules not found.'
                ]);
                exit;
            }
            
            // Store the original times
            $schedule1_start_time = $schedule1['start_time'];
            $schedule1_end_time = $schedule1['end_time'];
            $schedule2_start_time = $schedule2['start_time'];
            $schedule2_end_time = $schedule2['end_time'];
            
            // Begin transaction
            $db->begin_transaction();
            
            try {
                // Update schedule 1 with schedule 2's time
                $stmt = $db->prepare("UPDATE room_schedules SET start_time = ?, end_time = ? WHERE id = ?");
                $stmt->bind_param("ssi", $schedule2_start_time, $schedule2_end_time, $schedule1_id);
                $stmt->execute();
                
                // Update schedule 2 with schedule 1's time
                $stmt = $db->prepare("UPDATE room_schedules SET start_time = ?, end_time = ? WHERE id = ?");
                $stmt->bind_param("ssi", $schedule1_start_time, $schedule1_end_time, $schedule2_id);
                $stmt->execute();
                
                // Commit the transaction
                $db->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Schedules swapped successfully!'
                ]);
            } catch (Exception $e) {
                // Rollback the transaction if something went wrong
                $db->rollback();
                
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to swap schedules: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            if (!empty($action)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid action specified']);
            }
    }
}

$db->close();
?>