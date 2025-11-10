<?php
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
// Include connection
include '../connection.php';
session_start();
date_default_timezone_set('Asia/Manila');
session_start();

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
// Get the action from the request
 $action = isset($_GET['action']) ? $_GET['action'] : '';

// Function to find schedules for swapping
if ($action == 'find_schedules_for_swap') {
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
}

// Function to swap schedules
if ($action == 'swap_schedules') {
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
}
// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Function to handle file uploads
function handleFileUpload($fileInput, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 2 * 1024 * 1024) {
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
    // SIMPLIFIED SWAP SCHEDULE ACTIONS - Only these 6 are needed
    'get_all_rooms', 'get_instructors_by_room', 'get_room_days',
    'get_instructor_schedule', 'swap_time_schedule', 'get_active_swaps', 'revert_swap',
    'find_all_schedules_for_swap'
];

$isAjaxRequest = isset($_GET['action']) && in_array($_GET['action'], $validAjaxActions);

if ($isAjaxRequest) {
    // For AJAX requests, handle specific actions
    switch ($_GET['action']) {
        // ============================
        // DEPARTMENT CRUD OPERATIONS
        // ============================
        case 'add_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

            if (strlen($department_name) > 100) {
                jsonResponse('error', 'Department name must be less than 100 characters');
            }

            if (strlen($department_desc) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            // Check if department exists
            $check = $db->prepare("SELECT COUNT(*) FROM department WHERE department_name = ?");
            $check->bind_param("s", $department_name);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                jsonResponse('error', 'Department already exists');
            }

            // Insert new department
            $stmt = $db->prepare("INSERT INTO department (department_name, department_desc) VALUES (?, ?)");
            $stmt->bind_param("ss", $department_name, $department_desc);

            if ($stmt->execute()) {
                jsonResponse('success', 'Department added successfully');
            } else {
                jsonResponse('error', 'Failed to add department: ' . $db->error);
            }
            break;

        case 'update_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Department ID is required');
            }
            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_id = intval($_POST['id']);
            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department ID');
            }

            // Check if department exists (excluding current one)
            $check = $db->prepare("SELECT COUNT(*) FROM department WHERE department_name = ? AND department_id != ?");
            $check->bind_param("si", $department_name, $department_id);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                jsonResponse('error', 'Department name already exists');
            }

            // Update department
            $stmt = $db->prepare("UPDATE department SET department_name = ?, department_desc = ? WHERE department_id = ?");
            $stmt->bind_param("ssi", $department_name, $department_desc, $department_id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Department updated successfully');
            } else {
                jsonResponse('error', 'Failed to update department: ' . $db->error);
            }
            break;

        case 'delete_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Department ID is required');
            }

            $department_id = intval($_POST['id']);

            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department ID');
            }

            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE department = (SELECT department_name FROM department WHERE department_id = ?)");
            $checkRooms->bind_param("i", $department_id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned rooms');
            }

            // Delete department
            $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
            $stmt->bind_param("i", $department_id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Department deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
            }
            break;

        // ========================
        // ROOM CRUD OPERATIONS
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
                jsonResponse('error', 'Room already exists in this department');
            }
            $check->close();

            // Insert room
            $stmt = $db->prepare("INSERT INTO rooms (room, authorized_personnel, department, password, descr) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $room, $role, $department, $password, $descr);

            if ($stmt->execute()) {
                jsonResponse('success', 'Room added successfully');
            } else {
                jsonResponse('error', 'Failed to add room: ' . $db->error);
            }
            break;

        case 'update_room':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Room ID is required');
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
                jsonResponse('error', 'Room already exists in this department');
            }
            $check->close();

            // Update room
            $stmt = $db->prepare("UPDATE rooms SET room = ?, authorized_personnel = ?, department = ?, password = ?, descr = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $room, $role, $department, $password, $descr, $id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Room updated successfully');
            } else {
                jsonResponse('error', 'Failed to update room: ' . $db->error);
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
                jsonResponse('error', 'Room not found');
            }
            $checkRoom->close();

            // Check for room dependencies (scheduled classes)
            $checkSchedules = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE room_name COLLATE utf8mb4_unicode_ci = (SELECT room COLLATE utf8mb4_unicode_ci FROM rooms WHERE id = ?)");
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
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Room deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete room: ' . $stmt->error);
            }
            break;
           
        // ========================
        // ROLE CRUD OPERATIONS
        // ========================
        case 'add_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (!isset($_POST['role']) || empty(trim($_POST['role']))) {
                jsonResponse('error', 'Role name is required');
            }

            // Sanitize input
            $role = sanitizeInput($db, trim($_POST['role']));

            // Validate length
            if (strlen($role) > 100) {
                jsonResponse('error', 'Role name must be less than 100 characters');
            }

            // Check if role exists (case-insensitive)
            $check = $db->prepare("SELECT id FROM role WHERE LOWER(role) = LOWER(?)");
            if (!$check) {
                jsonResponse('error', 'Database error: ' . $db->error);
            }
            
            $check->bind_param("s", $role);
            if (!$check->execute()) {
                jsonResponse('error', 'Database error: ' . $check->error);
            }
            
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $check->close();
                jsonResponse('error', 'Role already exists');
            }
            $check->close();

            // Insert role with prepared statement
            $stmt = $db->prepare("INSERT INTO role (role) VALUES (?)");
            if (!$stmt) {
                jsonResponse('error', 'Database error: ' . $db->error);
            }
            
            $stmt->bind_param("s", $role);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Role added successfully', [
                    'id' => $stmt->insert_id,
                    'role' => $role
                ]);
            } else {
                jsonResponse('error', 'Failed to add role: ' . $stmt->error);
            }
            break;

        case 'update_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Role ID is required');
            }
            if (!isset($_POST['role']) || empty(trim($_POST['role']))) {
                jsonResponse('error', 'Role name is required');
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $role = sanitizeInput($db, trim($_POST['role']));

            // Validate ID
            if ($id <= 0) {
                jsonResponse('error', 'Invalid role ID');
            }

            // Validate length
            if (strlen($role) > 100) {
                jsonResponse('error', 'Role name must be less than 100 characters');
            }

            // Check if role exists (excluding current one)
            $check = $db->prepare("SELECT id FROM role WHERE LOWER(role) = LOWER(?) AND id != ?");
            $check->bind_param("si", $role, $id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                jsonResponse('error', 'Role name already exists');
            }
            $check->close();

            // Update role
            $stmt = $db->prepare("UPDATE role SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $role, $id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Role updated successfully');
            } else {
                jsonResponse('error', 'Failed to update role: ' . $db->error);
            }
            break;

        case 'delete_role':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Role ID is required');
            }

            // Sanitize input
            $id = intval($_POST['id']);

            if ($id <= 0) {
                jsonResponse('error', 'Invalid role ID');
            }

            try {
                // First, get the role name for checking dependencies
                $getRole = $db->prepare("SELECT role FROM role WHERE id = ?");
                $getRole->bind_param("i", $id);
                $getRole->execute();
                $getRole->bind_result($roleName);
                $getRole->fetch();
                $getRole->close();

                if (empty($roleName)) {
                    jsonResponse('error', 'Role not found');
                }

                // Check if role is assigned to personnel
                $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE role = ?");
                $checkPersonnel->bind_param("s", $roleName);
                $checkPersonnel->execute();
                $checkPersonnel->bind_result($personnelCount);
                $checkPersonnel->fetch();
                $checkPersonnel->close();

                if ($personnelCount > 0) {
                    jsonResponse('error', 'Cannot delete role assigned to personnel. There are ' . $personnelCount . ' personnel with this role.');
                }

                // Check if role is assigned to rooms
                $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE authorized_personnel = ?");
                $checkRooms->bind_param("s", $roleName);
                $checkRooms->execute();
                $checkRooms->bind_result($roomCount);
                $checkRooms->fetch();
                $checkRooms->close();

                if ($roomCount > 0) {
                    jsonResponse('error', 'Cannot delete role assigned to rooms. There are ' . $roomCount . ' rooms with this role.');
                }

                // Proceed with deletion
                $stmt = $db->prepare("DELETE FROM role WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    jsonResponse('success', 'Role deleted successfully');
                } else {
                    jsonResponse('error', 'Failed to delete role: ' . $stmt->error);
                }
            } catch (Exception $e) {
                jsonResponse('error', 'Database error: ' . $e->getMessage());
            }
            break;

        
        // ========================
        // PERSONNEL CRUD OPERATIONS - CORRECTED VERSION (0000-0000 FORMAT)
        // ========================
        case 'add_personnel':
            error_log("=== ADD PERSONNEL STARTED ===");
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("Invalid request method");
                jsonResponse('error', 'Invalid request method');
            }

            // Log all received data
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));

            // Validate required fields
            $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'department'];
            $missing_fields = [];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                error_log("Missing fields: " . implode(', ', $missing_fields));
                jsonResponse('error', "Missing required fields: " . implode(', ', $missing_fields));
            }

            // Sanitize inputs
            $last_name = sanitizeInput($db, $_POST['last_name']);
            $first_name = sanitizeInput($db, $_POST['first_name']);
            $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
            $id_number = sanitizeInput($db, $_POST['id_number']); // Store with 0000-0000 format
            $role = sanitizeInput($db, $_POST['role']);
            $category = sanitizeInput($db, $_POST['category']);
            $department = sanitizeInput($db, $_POST['department']);
            $status = 'Active';

            error_log("Processing: $last_name, $first_name, ID: $id_number");

            // Validate ID Number format - accept and require 0000-0000 format
            if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
                error_log("Invalid ID format: $id_number");
                jsonResponse('error', 'ID Number must be in 0000-0000 format. Received: ' . $id_number);
            }

            // Check if ID Number already exists (exact match with 0000-0000 format)
            $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND deleted = 0");
            if (!$check_id) {
                error_log("Prepare failed: " . $db->error);
                jsonResponse('error', 'Database error: ' . $db->error);
            }
            
            $check_id->bind_param("s", $id_number); // Use exact 0000-0000 format
            if (!$check_id->execute()) {
                error_log("Execute failed: " . $check_id->error);
                jsonResponse('error', 'Database error: ' . $check_id->error);
            }
            
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                error_log("ID already exists: $id_number");
                jsonResponse('error', 'ID Number already exists');
            }
            $check_id->close();

            // Handle file upload
            $photo = 'default.png';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                error_log("Photo file detected, attempting upload");
                $uploadResult = handleFileUpload('photo', '../uploads/personell/');
                if ($uploadResult['success']) {
                    $photo = $uploadResult['filename'];
                    error_log("Photo uploaded successfully: $photo");
                } else {
                    error_log("Photo upload failed: " . $uploadResult['message']);
                    // Continue with default photo instead of failing
                }
            } else {
                error_log("No photo uploaded or upload error, using default.png");
                if (isset($_FILES['photo'])) {
                    error_log("File upload error code: " . $_FILES['photo']['error']);
                }
            }

            // Insert record - Store ID with 0000-0000 format
            $query = "INSERT INTO personell (
                id_number, last_name, first_name, date_of_birth, 
                role, category, department, status, photo, date_added
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            error_log("SQL Query: $query");
            error_log("Values: $id_number, $last_name, $first_name, $date_of_birth, $role, $category, $department, $status, $photo");
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                $error = $db->error;
                error_log("Prepare failed: " . $error);
                jsonResponse('error', 'Database prepare failed: ' . $error);
            }

            $stmt->bind_param(
                "sssssssss", 
                $id_number, $last_name, $first_name, $date_of_birth, // Store with 0000-0000 format
                $role, $category, $department, $status, $photo
            );

            if ($stmt->execute()) {
                error_log("Personnel added successfully with ID: $id_number");
                jsonResponse('success', 'Personnel added successfully');
            } else {
                $error = $stmt->error;
                error_log("Execute failed: " . $error);
                jsonResponse('error', 'Database execute failed: ' . $error);
            }
            break;

        case 'update_personnel':
        error_log("=== UPDATE PERSONNEL STARTED ===");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Log all received data for debugging
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));

        // Validate required fields
        if (empty($_POST['id'])) {
            jsonResponse('error', 'Personnel ID is required');
        }

        $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'department', 'status'];
        $missing_fields = [];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log("Missing fields: " . implode(', ', $missing_fields));
            jsonResponse('error', "Missing required fields: " . implode(', ', $missing_fields));
        }

        // Sanitize inputs
        $id = intval($_POST['id']);
        $last_name = sanitizeInput($db, $_POST['last_name']);
        $first_name = sanitizeInput($db, $_POST['first_name']);
        $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
        $id_number = sanitizeInput($db, $_POST['id_number']); // Store with 0000-0000 format
        $role = sanitizeInput($db, $_POST['role']);
        $category = sanitizeInput($db, $_POST['category']);
        $department = sanitizeInput($db, $_POST['department']);
        $status = sanitizeInput($db, $_POST['status']);

        // Validate ID
        if ($id <= 0) {
            jsonResponse('error', 'Invalid personnel ID');
        }

        // Validate ID Number format - accept and require 0000-0000 format
        if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
            error_log("Invalid ID format: $id_number");
            jsonResponse('error', 'ID Number must be in 0000-0000 format. Received: ' . $id_number);
        }

        // Check if ID Number exists for other personnel (exact match with 0000-0000 format)
        $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND id != ? AND deleted = 0");
        if (!$check_id) {
            error_log("Prepare failed for ID check: " . $db->error);
            jsonResponse('error', 'Database prepare error: ' . $db->error);
        }
        
        $check_id->bind_param("si", $id_number, $id); // Use exact 0000-0000 format
        if (!$check_id->execute()) {
            error_log("Execute failed for ID check: " . $check_id->error);
            jsonResponse('error', 'Database execute error: ' . $check_id->error);
        }
        
        $check_id->store_result();
        
        if ($check_id->num_rows > 0) {
            error_log("ID already exists for another personnel: $id_number");
            jsonResponse('error', 'ID Number already assigned to another personnel');
        }
        $check_id->close();

        // Handle file upload
        $photo_update = '';
        $new_photo = '';
        $update_params = [];
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            error_log("New photo uploaded, processing...");
            $uploadResult = handleFileUpload('photo', '../uploads/personell/');
            if ($uploadResult['success']) {
                $new_photo = $uploadResult['filename'];
                $photo_update = ", photo = ?";
                $update_params[] = $new_photo;
                error_log("New photo will be set to: $new_photo");
            } else {
                error_log("Photo upload failed: " . $uploadResult['message']);
                // Don't fail the entire update if photo upload fails
                // jsonResponse('error', $uploadResult['message']);
            }
        } else {
            error_log("No new photo uploaded, keeping existing photo");
            // Check if we have capturedImage from the form
            if (isset($_POST['capturedImage']) && !empty($_POST['capturedImage'])) {
                $existing_photo = basename($_POST['capturedImage']); // Get just the filename
                error_log("Keeping existing photo: $existing_photo");
            } else {
                error_log("No capturedImage found, using default.png");
                $existing_photo = 'default.png';
            }
        }

        // Build the update query
        $query = "UPDATE personell SET 
            id_number = ?, last_name = ?, first_name = ?, date_of_birth = ?,
            role = ?, category = ?, department = ?, status = ?";
        
        // Add photo update if needed
        if (!empty($photo_update)) {
            $query .= $photo_update;
        }
        
        $query .= " WHERE id = ?";
        
        error_log("Final update query: $query");
        error_log("Parameters: id_number=$id_number, last_name=$last_name, first_name=$first_name, date_of_birth=$date_of_birth, role=$role, category=$category, department=$department, status=$status, id=$id");

        // Prepare the statement
        $stmt = $db->prepare($query);
        if (!$stmt) {
            $error = $db->error;
            error_log("Prepare failed: " . $error);
            jsonResponse('error', 'Database prepare failed: ' . $error);
        }

        // Bind parameters based on whether we're updating photo or not
        if (!empty($photo_update)) {
            // With photo update
            $stmt->bind_param("sssssssssi", 
                $id_number, $last_name, $first_name, $date_of_birth,
                $role, $category, $department, $status, $new_photo, $id
            );
        } else {
            // Without photo update
            $stmt->bind_param("ssssssssi", 
                $id_number, $last_name, $first_name, $date_of_birth,
                $role, $category, $department, $status, $id
            );
        }

        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            error_log("Personnel updated successfully. Affected rows: $affected_rows");
            jsonResponse('success', 'Personnel updated successfully');
        } else {
            $error = $stmt->error;
            error_log("Execute failed: " . $error);
            jsonResponse('error', 'Failed to update personnel: ' . $error);
        }
        break;

        case 'delete_personnel':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Personnel ID is required');
            }

            // Sanitize input
            $id = intval($_POST['id']);

            if ($id <= 0) {
                jsonResponse('error', 'Invalid personnel ID');
            }

            // Check if personnel exists
            $checkPersonnel = $db->prepare("SELECT id, id_number FROM personell WHERE id = ? AND deleted = 0");
            if (!$checkPersonnel) {
                jsonResponse('error', 'Database prepare error');
            }
            
            $checkPersonnel->bind_param("i", $id);
            if (!$checkPersonnel->execute()) {
                jsonResponse('error', 'Database execute error');
            }
            
            $checkPersonnel->store_result();
            
            if ($checkPersonnel->num_rows === 0) {
                jsonResponse('error', 'Personnel not found');
            }
            
            // Get the ID number for logging
            $checkPersonnel->bind_result($personnel_id, $personnel_id_number);
            $checkPersonnel->fetch();
            $checkPersonnel->close();

            // Soft delete personnel (set deleted flag to 1)
            $stmt = $db->prepare("UPDATE personell SET deleted = 1 WHERE id = ?");
            if (!$stmt) {
                jsonResponse('error', 'Database prepare error');
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                error_log("Personnel deleted successfully - ID: $personnel_id_number");
                jsonResponse('success', 'Personnel deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
            }
            break;
            // ========================
            // STUDENT CRUD OPERATIONS
            // ========================
            case 'add_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = ['department_id', 'id_number', 'fullname', 'year', 'section'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $department_id = intval($_POST['department_id']);
            $id_number = sanitizeInput($db, trim($_POST['id_number']));
            $fullname = sanitizeInput($db, trim($_POST['fullname']));
            $year = sanitizeInput($db, $_POST['year']);
            $section = sanitizeInput($db, $_POST['section']);

            // Validate department ID
            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department');
            }

            // Validate ID Number format
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $id_number)) {
                jsonResponse('error', 'Invalid ID Number format. Only letters, numbers, and hyphens are allowed.');
            }

            // Check if ID Number already exists
            $check_id = $db->prepare("SELECT id FROM students WHERE id_number = ?");
            $check_id->bind_param("s", $id_number);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already exists');
            }
            $check_id->close();

            // Handle file upload
            $photo = 'default.png'; // Default photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/students/');
                if (!$uploadResult['success']) {
                    jsonResponse('error', $uploadResult['message']);
                }
                $photo = $uploadResult['filename'];
            }

            // Insert student record
            $query = "INSERT INTO students (department_id, id_number, fullname, year, section, photo, date_added) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                jsonResponse('error', 'Database error: ' . $db->error);
            }

            $stmt->bind_param("isssss", $department_id, $id_number, $fullname, $year, $section, $photo);

            if ($stmt->execute()) {
                jsonResponse('success', 'Student added successfully', [
                    'id' => $stmt->insert_id
                ]);
            } else {
                jsonResponse('error', 'Failed to add student: ' . $stmt->error);
            }
            break;

        case 'update_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Student ID is required');
            }

            $required = ['department_id', 'id_number', 'fullname', 'year', 'section'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $department_id = intval($_POST['department_id']);
            $id_number = sanitizeInput($db, trim($_POST['id_number']));
            $fullname = sanitizeInput($db, trim($_POST['fullname']));
            $year = sanitizeInput($db, $_POST['year']);
            $section = sanitizeInput($db, $_POST['section']);

            // Validate IDs
            if ($id <= 0) {
                jsonResponse('error', 'Invalid student ID');
            }
            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department');
            }

            // Validate ID Number format
            if (!preg_match('/^[A-Za-z0-9\-]+$/', $id_number)) {
                jsonResponse('error', 'Invalid ID Number format. Only letters, numbers, and hyphens are allowed.');
            }

            // Check if ID Number exists for other students
            $check_id = $db->prepare("SELECT id FROM students WHERE id_number = ? AND id != ?");
            $check_id->bind_param("si", $id_number, $id);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already assigned to another student');
            }
            $check_id->close();

            // Handle file upload
            $photo = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/students/');
                if (!$uploadResult['success']) {
                    jsonResponse('error', $uploadResult['message']);
                }
                $photo = $uploadResult['filename'];
            } else {
                // Keep existing photo if no new upload
                $photo = sanitizeInput($db, $_POST['capturedImage']);
            }

            // Update student record
            if (!empty($photo)) {
                $query = "UPDATE students SET 
                    department_id = ?, id_number = ?, fullname = ?, year = ?, section = ?, photo = ?
                    WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("isssssi", $department_id, $id_number, $fullname, $year, $section, $photo, $id);
            } else {
                $query = "UPDATE students SET 
                    department_id = ?, id_number = ?, fullname = ?, year = ?, section = ?
                    WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bind_param("issssi", $department_id, $id_number, $fullname, $year, $section, $id);
            }

            if ($stmt->execute()) {
                jsonResponse('success', 'Student updated successfully');
            } else {
                jsonResponse('error', 'Failed to update student: ' . $stmt->error);
            }
            break;

        case 'delete_student':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required field
            if (empty($_POST['id'])) {
                jsonResponse('error', 'Student ID is required');
            }

            // Sanitize input
            $id = intval($_POST['id']);

            if ($id <= 0) {
                jsonResponse('error', 'Invalid student ID');
            }

            // Check if student exists
            $checkStudent = $db->prepare("SELECT id FROM students WHERE id = ?");
            $checkStudent->bind_param("i", $id);
            $checkStudent->execute();
            $checkStudent->store_result();
            
            if ($checkStudent->num_rows === 0) {
                jsonResponse('error', 'Student not found');
            }
            $checkStudent->close();

            // Check for student dependencies (attendance records, etc.)
            // Uncomment and modify these checks based on your database schema
            
            
            // Example: Check if student has attendance records
            $checkAttendance = $db->prepare("SELECT COUNT(*) FROM attendance_logs WHERE student_id = ?");
            $checkAttendance->bind_param("i", $id);
            $checkAttendance->execute();
            $checkAttendance->bind_result($attendanceCount);
            $checkAttendance->fetch();
            $checkAttendance->close();

            if ($attendanceCount > 0) {
                jsonResponse('error', 'Cannot delete student with attendance records');
            }
            

            // Delete student
            $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Student deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete student: ' . $stmt->error);
            }
            break;

            // Add this helper function if not already present
            function handleFileUpload($fieldName, $uploadPath) {
                if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
                    return ['success' => false, 'message' => 'No file uploaded or upload error'];
                }

                $file = $_FILES[$fieldName];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                // Validate file type
                if (!in_array($file['type'], $allowedTypes)) {
                    return ['success' => false, 'message' => 'Only JPG, JPEG and PNG files are allowed'];
                }

                // Validate file size
                if ($file['size'] > $maxSize) {
                    return ['success' => false, 'message' => 'File size must be less than 2MB'];
                }

                // Create upload directory if it doesn't exist
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                // Generate unique filename
                $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '.' . $fileExtension;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                    return ['success' => true, 'filename' => $filename];
                } else {
                    return ['success' => false, 'message' => 'Failed to move uploaded file'];
                }
            }

// Add these cases to your existing switch statement in transac.php:

            case 'add_instructor':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = ['department_id', 'id_number', 'fullname'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $department_id = intval($_POST['department_id']);
            $id_number = sanitizeInput($db, trim($_POST['id_number']));
            $fullname = sanitizeInput($db, trim($_POST['fullname']));

            // Validate department ID
            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department');
            }

            // Validate ID Number format (0000-0000 format)
            if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
                jsonResponse('error', 'Invalid ID Number format. Must be in 0000-0000 format.');
            }

            // Check if ID Number already exists
            $check_id = $db->prepare("SELECT id FROM instructor WHERE id_number = ?");
            $check_id->bind_param("s", $id_number);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already exists');
            }
            $check_id->close();

            // SIMPLIFIED PHOTO UPLOAD - Same as students
            $photo = 'default.png'; // Default photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/instructors/');
                if (!$uploadResult['success']) {
                    jsonResponse('error', $uploadResult['message']);
                }
                $photo = $uploadResult['filename'];
            }

            // Insert instructor record
            $query = "INSERT INTO instructor (department_id, id_number, fullname, photo) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                jsonResponse('error', 'Database error: ' . $db->error);
            }

            $stmt->bind_param("isss", $department_id, $id_number, $fullname, $photo);

            if ($stmt->execute()) {
                jsonResponse('success', 'Instructor added successfully', [
                    'id' => $stmt->insert_id
                ]);
            } else {
                jsonResponse('error', 'Failed to add instructor: ' . $stmt->error);
            }
            break;

        case 'update_instructor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required fields
        if (empty($_POST['instructor_id'])) {
            jsonResponse('error', 'Instructor ID is required');
        }

        $required = ['department_id', 'id_number', 'fullname'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
            }
        }

        // Sanitize inputs
        $id = intval($_POST['instructor_id']);
        $department_id = intval($_POST['department_id']);
        $id_number = sanitizeInput($db, trim($_POST['id_number']));
        $fullname = sanitizeInput($db, trim($_POST['fullname']));

        // Validate IDs
        if ($id <= 0) {
            jsonResponse('error', 'Invalid instructor ID');
        }
        if ($department_id <= 0) {
            jsonResponse('error', 'Invalid department');
        }

        // Validate ID Number format
        if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
            jsonResponse('error', 'Invalid ID Number format. Must be in 0000-0000 format.');
        }

        // Check if ID Number exists for other instructors
        $check_id = $db->prepare("SELECT id FROM instructor WHERE id_number = ? AND id != ?");
        $check_id->bind_param("si", $id_number, $id);
        $check_id->execute();
        $check_id->store_result();
        
        if ($check_id->num_rows > 0) {
            jsonResponse('error', 'ID Number already assigned to another instructor');
        }
        $check_id->close();

        // SIMPLIFIED PHOTO UPLOAD - Same as students
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleFileUpload('photo', '../uploads/instructors/');
            if (!$uploadResult['success']) {
                jsonResponse('error', $uploadResult['message']);
            }
            $photo = $uploadResult['filename'];
        } else {
            // Keep existing photo if no new upload - SIMPLIFIED
            $photo = sanitizeInput($db, $_POST['existing_photo'] ?? 'img\2601828.png');
        }

        // Update instructor record
        if (!empty($photo)) {
            $query = "UPDATE instructor SET 
                department_id = ?, id_number = ?, fullname = ?, photo = ?
                WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("isssi", $department_id, $id_number, $fullname, $photo, $id);
        } else {
            $query = "UPDATE instructor SET 
                department_id = ?, id_number = ?, fullname = ?
                WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("issi", $department_id, $id_number, $fullname, $id);
        }

        if ($stmt->execute()) {
            jsonResponse('success', 'Instructor updated successfully');
        } else {
            jsonResponse('error', 'Failed to update instructor: ' . $stmt->error);
        }
        break;

    case 'delete_instructor':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse('error', 'Invalid request method');
        }

        // Validate required field
        if (empty($_POST['id'])) {
            jsonResponse('error', 'Instructor ID is required');
        }

        // Sanitize input
        $id = intval($_POST['id']);

        if ($id <= 0) {
            jsonResponse('error', 'Invalid instructor ID');
        }

        // Check if instructor exists
        $checkInstructor = $db->prepare("SELECT id FROM instructor WHERE id = ?");
        $checkInstructor->bind_param("i", $id);
        $checkInstructor->execute();
        $checkInstructor->store_result();
        
        if ($checkInstructor->num_rows === 0) {
            jsonResponse('error', 'Instructor not found');
        }
        $checkInstructor->close();

        // Check for instructor dependencies (courses, classes, etc.)
        // Example: Check if instructor has assigned courses
        // $checkCourses = $db->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
        // $checkCourses->bind_param("i", $id);
        // $checkCourses->execute();
        // $checkCourses->bind_result($courseCount);
        // $checkCourses->fetch();
        // $checkCourses->close();

        // if ($courseCount > 0) {
        //     jsonResponse('error', 'Cannot delete instructor with assigned courses');
        // }

        // Example: Check if instructor has class assignments
        $checkClasses = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE instructor_id = ?");
        $checkClasses->bind_param("i", $id);
        $checkClasses->execute();
        $checkClasses->bind_result($classCount);
        $checkClasses->fetch();
        $checkClasses->close();

        if ($classCount > 0) {
            jsonResponse('error', 'Cannot delete instructor with class assignments');
        }

        // Delete instructor
        $stmt = $db->prepare("DELETE FROM instructor WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            jsonResponse('success', 'Instructor deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete instructor: ' . $stmt->error);
        }
        break;

                // ========================
                // SUBJECT CRUD OPERATIONS
                // ========================
                case 'add_subject':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required fields
                    $required = ['subject_code', 'subject_name', 'year_level'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                        }
                    }

                    // Sanitize inputs
                    $subject_code = sanitizeInput($db, trim($_POST['subject_code']));
                    $subject_name = sanitizeInput($db, trim($_POST['subject_name']));
                    $year_level = sanitizeInput($db, $_POST['year_level']);

                    // Validate lengths
                    if (strlen($subject_code) > 50) {
                        jsonResponse('error', 'Subject code must be less than 50 characters');
                    }

                    if (strlen($subject_name) > 255) {
                        jsonResponse('error', 'Subject name must be less than 255 characters');
                    }

                    // Check if subject code already exists
                    $check_code = $db->prepare("SELECT id FROM subjects WHERE subject_code = ?");
                    $check_code->bind_param("s", $subject_code);
                    $check_code->execute();
                    $check_code->store_result();
                    
                    if ($check_code->num_rows > 0) {
                        jsonResponse('error', 'Subject code already exists');
                    }
                    $check_code->close();

                    // Insert subject record
                    $query = "INSERT INTO subjects (subject_code, subject_name, year_level) 
                            VALUES (?, ?, ?)";
                    
                    $stmt = $db->prepare($query);
                    if (!$stmt) {
                        jsonResponse('error', 'Database error: ' . $db->error);
                    }

                    $stmt->bind_param("sss", $subject_code, $subject_name, $year_level);

                    if ($stmt->execute()) {
                        jsonResponse('success', 'Subject added successfully', [
                            'id' => $stmt->insert_id
                        ]);
                    } else {
                        jsonResponse('error', 'Failed to add subject: ' . $stmt->error);
                    }
                    break;

                case 'update_subject':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required fields
                    if (empty($_POST['id'])) {
                        jsonResponse('error', 'Subject ID is required');
                    }

                    $required = ['subject_code', 'subject_name', 'year_level'];
                    foreach ($required as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                        }
                    }

                    // Sanitize inputs
                    $id = intval($_POST['id']);
                    $subject_code = sanitizeInput($db, trim($_POST['subject_code']));
                    $subject_name = sanitizeInput($db, trim($_POST['subject_name']));
                    $year_level = sanitizeInput($db, $_POST['year_level']);

                    // Validate ID
                    if ($id <= 0) {
                        jsonResponse('error', 'Invalid subject ID');
                    }

                    // Validate lengths
                    if (strlen($subject_code) > 50) {
                        jsonResponse('error', 'Subject code must be less than 50 characters');
                    }

                    if (strlen($subject_name) > 255) {
                        jsonResponse('error', 'Subject name must be less than 255 characters');
                    }

                    // Check if subject code exists for other subjects
                    $check_code = $db->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
                    $check_code->bind_param("si", $subject_code, $id);
                    $check_code->execute();
                    $check_code->store_result();
                    
                    if ($check_code->num_rows > 0) {
                        jsonResponse('error', 'Subject code already assigned to another subject');
                    }
                    $check_code->close();

                    // Check if subject name exists for same year level (excluding current subject)
                    $check_name = $db->prepare("SELECT id FROM subjects WHERE subject_name = ? AND year_level = ? AND id != ?");
                    $check_name->bind_param("ssi", $subject_name, $year_level, $id);
                    $check_name->execute();
                    $check_name->store_result();
                    
                    if ($check_name->num_rows > 0) {
                        jsonResponse('error', 'Subject name already exists for this year level');
                    }
                    $check_name->close();

                    // Update subject record
                    $query = "UPDATE subjects SET 
                        subject_code = ?, subject_name = ?, year_level = ?
                        WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->bind_param("sssi", $subject_code, $subject_name, $year_level, $id);

                    if ($stmt->execute()) {
                        jsonResponse('success', 'Subject updated successfully');
                    } else {
                        jsonResponse('error', 'Failed to update subject: ' . $stmt->error);
                    }
                    break;

                case 'delete_subject':
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        jsonResponse('error', 'Invalid request method');
                    }

                    // Validate required field
                    if (empty($_POST['id'])) {
                        jsonResponse('error', 'Subject ID is required');
                    }

                    // Sanitize input
                    $id = intval($_POST['id']);

                    if ($id <= 0) {
                        jsonResponse('error', 'Invalid subject ID');
                    }

                    // Check if subject exists
                    $checkSubject = $db->prepare("SELECT id FROM subjects WHERE id = ?");
                    $checkSubject->bind_param("i", $id);
                    $checkSubject->execute();
                    $checkSubject->store_result();
                    
                    if ($checkSubject->num_rows === 0) {
                        jsonResponse('error', 'Subject not found');
                    }
                    $checkSubject->close();

                    // Check for subject dependencies (classes, schedules, etc.)
                    // Add dependency checks based on your database schema
                    
                    
                    // Example: Check if subject has assigned classes
                    $checkClasses = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE subject = ?");
                    $checkClasses->bind_param("i", $id);
                    $checkClasses->execute();
                    $checkClasses->bind_result($classCount);
                    $checkClasses->fetch();
                    $checkClasses->close();

                    if ($classCount > 0) {
                        jsonResponse('error', 'Cannot delete subject with assigned classes');
                    }
                    

                    // Delete subject
                    $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        jsonResponse('success', 'Subject deleted successfully');
                    } else {
                        jsonResponse('error', 'Failed to delete subject: ' . $stmt->error);
                    }
                    break;
                    // ========================
                    // ROOM SCHEDULE CRUD OPERATIONS
                    // ========================
                    case 'add_schedule':
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            jsonResponse('error', 'Invalid request method');
                        }

                        // Validate required fields
                        $required = ['department', 'room_name', 'year_level', 'subject', 'section', 'day', 'instructor', 'start_time', 'end_time'];
                        foreach ($required as $field) {
                            if (empty($_POST[$field])) {
                                jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                            }
                        }

                        // Sanitize inputs
                        $department = sanitizeInput($db, trim($_POST['department']));
                        $room_name = sanitizeInput($db, trim($_POST['room_name']));
                        $year_level = sanitizeInput($db, $_POST['year_level']);
                        $subject = sanitizeInput($db, trim($_POST['subject']));
                        $section = sanitizeInput($db, trim($_POST['section']));
                        $day = sanitizeInput($db, $_POST['day']);
                        $instructor = sanitizeInput($db, trim($_POST['instructor']));
                        $start_time = sanitizeInput($db, $_POST['start_time']);
                        $end_time = sanitizeInput($db, $_POST['end_time']);

                        // Validate time logic
                        if ($start_time >= $end_time) {
                            jsonResponse('error', 'End time must be after start time');
                        }

                        // Check for schedule conflicts (same room, same day, overlapping time)
                        $check_conflict = $db->prepare("SELECT id FROM room_schedules 
                            WHERE room_name = ? AND day = ? 
                            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
                        $check_conflict->bind_param("ssssssss", $room_name, $day, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
                        $check_conflict->execute();
                        $check_conflict->store_result();
                        
                        if ($check_conflict->num_rows > 0) {
                            jsonResponse('error', 'Schedule conflict: Room is already booked during this time slot');
                        }
                        $check_conflict->close();

                        // Check if instructor has schedule conflict
                        $check_instructor_conflict = $db->prepare("SELECT id FROM room_schedules 
                            WHERE instructor = ? AND day = ? 
                            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
                        $check_instructor_conflict->bind_param("ssssssss", $instructor, $day, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
                        $check_instructor_conflict->execute();
                        $check_instructor_conflict->store_result();
                        
                        if ($check_instructor_conflict->num_rows > 0) {
                            jsonResponse('error', 'Schedule conflict: Instructor already has a class during this time slot');
                        }
                        $check_instructor_conflict->close();

                        // Insert schedule 
                        $query = "INSERT INTO room_schedules (department, room_name, year_level, subject, section, day, instructor, start_time, end_time) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $db->prepare($query);
                        if (!$stmt) {
                            jsonResponse('error', 'Database error: ' . $db->error);
                        }

                        $stmt->bind_param("sssssssss", $department, $room_name, $year_level, $subject, $section, $day, $instructor, $start_time, $end_time);

                        if ($stmt->execute()) {
                            jsonResponse('success', 'Room schedule added successfully', [
                                'id' => $stmt->insert_id
                            ]);
                        } else {
                            jsonResponse('error', 'Failed to add room schedule: ' . $stmt->error);
                        }
                        break;

                    case 'update_schedule':
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            jsonResponse('error', 'Invalid request method');
                        }

                        // Validate required fields
                        if (empty($_POST['id'])) {
                            jsonResponse('error', 'Schedule ID is required');
                        }

                        $required = ['department', 'room_name', 'year_level', 'subject', 'section', 'day', 'instructor', 'start_time', 'end_time'];
                        foreach ($required as $field) {
                            if (empty($_POST[$field])) {
                                jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                            }
                        }

                        // Sanitize inputs
                        $id = intval($_POST['id']);
                        $department = sanitizeInput($db, trim($_POST['department']));
                        $room_name = sanitizeInput($db, trim($_POST['room_name']));
                        $year_level = sanitizeInput($db, $_POST['year_level']);
                        $subject = sanitizeInput($db, trim($_POST['subject']));
                        $section = sanitizeInput($db, trim($_POST['section']));
                        $day = sanitizeInput($db, $_POST['day']);
                        $instructor = sanitizeInput($db, trim($_POST['instructor']));
                        $start_time = sanitizeInput($db, $_POST['start_time']);
                        $end_time = sanitizeInput($db, $_POST['end_time']);

                        // Validate ID
                        if ($id <= 0) {
                            jsonResponse('error', 'Invalid schedule ID');
                        }

                        // Validate time logic
                        if ($start_time >= $end_time) {
                            jsonResponse('error', 'End time must be after start time');
                        }

                        // Check for schedule conflicts (excluding current schedule)
                        $check_conflict = $db->prepare("SELECT id FROM room_schedules 
                            WHERE room_name = ? AND day = ? AND id != ?
                            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
                        $check_conflict->bind_param("ssissssss", $room_name, $day, $id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
                        $check_conflict->execute();
                        $check_conflict->store_result();
                        
                        if ($check_conflict->num_rows > 0) {
                            jsonResponse('error', 'Schedule conflict: Room is already booked during this time slot');
                        }
                        $check_conflict->close();

                        // Check if instructor has schedule conflict (excluding current schedule)
                        $check_instructor_conflict = $db->prepare("SELECT id FROM room_schedules 
                            WHERE instructor = ? AND day = ? AND id != ?
                            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
                        $check_instructor_conflict->bind_param("ssissssss", $instructor, $day, $id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time);
                        $check_instructor_conflict->execute();
                        $check_instructor_conflict->store_result();
                        
                        if ($check_instructor_conflict->num_rows > 0) {
                            jsonResponse('error', 'Schedule conflict: Instructor already has a class during this time slot');
                        }
                        $check_instructor_conflict->close();

                        // Update schedule record
                        $query = "UPDATE room_schedules SET 
                            department = ?, room_name = ?, year_level = ?, subject = ?, section = ?, 
                            day = ?, instructor = ?, start_time = ?, end_time = ?
                            WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param("sssssssssi", $department, $room_name, $year_level, $subject, $section, $day, $instructor, $start_time, $end_time, $id);

                        if ($stmt->execute()) {
                            jsonResponse('success', 'Room schedule updated successfully');
                        } else {
                            jsonResponse('error', 'Failed to update room schedule: ' . $stmt->error);
                        }
                        break;

                    case 'delete_schedule':
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            jsonResponse('error', 'Invalid request method');
                        }

                        // Validate required field
                        if (empty($_POST['id'])) {
                            jsonResponse('error', 'Schedule ID is required');
                        }

                        // Sanitize input
                        $id = intval($_POST['id']);

                        if ($id <= 0) {
                            jsonResponse('error', 'Invalid schedule ID');
                        }

                        // Check if schedule exists
                        $checkSchedule = $db->prepare("SELECT id FROM room_schedules WHERE id = ?");
                        $checkSchedule->bind_param("i", $id);
                        $checkSchedule->execute();
                        $checkSchedule->store_result();
                        
                        if ($checkSchedule->num_rows === 0) {
                            jsonResponse('error', 'Schedule not found');
                        }
                        $checkSchedule->close();

                        // Delete schedule
                        $stmt = $db->prepare("DELETE FROM room_schedules WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        
                        if ($stmt->execute()) {
                            jsonResponse('success', 'Room schedule deleted successfully');
                        } else {
                            jsonResponse('error', 'Failed to delete room schedule: ' . $stmt->error);
                        }
                        break;
                        // ========================
                        // VISITOR CRUD OPERATIONS
                        // ========================
                    case 'add_visitor':
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            jsonResponse('error', 'Invalid request method');
                        }

                        // Validate required field
                        if (!isset($_POST['rfid_number']) || empty(trim($_POST['rfid_number']))) {
                            jsonResponse('error', 'ID number is required');
                        }

                        // Sanitize input
                        $rfid_number = sanitizeInput($db, trim($_POST['rfid_number']));

                        // Validate ID number format (0000-0000)
                        if (!preg_match('/^\d{4}-\d{4}$/', $rfid_number)) {
                            jsonResponse('error', 'ID number must be in format: 0000-0000');
                        }

                        // Check if visitor ID already exists
                        $check = $db->prepare("SELECT COUNT(*) FROM visitor WHERE rfid_number = ?");
                        $check->bind_param("s", $rfid_number);
                        $check->execute();
                        $check->bind_result($count);
                        $check->fetch();
                        $check->close();

                        if ($count > 0) {
                            jsonResponse('error', 'Visitor ID number already exists');
                        }

                        // Insert new visitor
                        $stmt = $db->prepare("INSERT INTO visitor (rfid_number) VALUES (?)");
                        $stmt->bind_param("s", $rfid_number);

                        if ($stmt->execute()) {
                            jsonResponse('success', 'Visitor card added successfully');
                        } else {
                            jsonResponse('error', 'Failed to add visitor card: ' . $db->error);
                        }
                        break;

                    case 'update_visitor':
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            jsonResponse('error', 'Invalid request method');
                        }

                        // Validate required fields
                        if (!isset($_POST['id']) || empty($_POST['id'])) {
                            jsonResponse('error', 'Visitor ID is required');
                        }
                        if (!isset($_POST['rfid_number']) || empty(trim($_POST['rfid_number']))) {
                            jsonResponse('error', 'ID number is required');
                        }

                        // Sanitize inputs
                        $id = intval($_POST['id']);
                        $rfid_number = sanitizeInput($db, trim($_POST['rfid_number']));

                        // Validate ID
                        if ($id <= 0) {
                            jsonResponse('error', 'Invalid visitor ID');
                        }

                        // Validate ID number format (0000-0000)
                        if (!preg_match('/^\d{4}-\d{4}$/', $rfid_number)) {
                            jsonResponse('error', 'ID number must be in format: 0000-0000');
                        }

                        // Check if visitor ID exists for other visitors
                        $check = $db->prepare("SELECT COUNT(*) FROM visitor WHERE rfid_number = ? AND id != ?");
                        $check->bind_param("si", $rfid_number, $id);
                        $check->execute();
                        $check->bind_result($count);
                        $check->fetch();
                        $check->close();

                        if ($count > 0) {
                            jsonResponse('error', 'ID number already assigned to another visitor');
                        }

                        // Update visitor
                        $stmt = $db->prepare("UPDATE visitor SET rfid_number = ? WHERE id = ?");
                        $stmt->bind_param("si", $rfid_number, $id);

                        if ($stmt->execute()) {
                            jsonResponse('success', 'Visitor card updated successfully');
                        } else {
                            jsonResponse('error', 'Failed to update visitor card: ' . $db->error);
                        }
                        break;

                    case 'delete_visitor':
                        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                            jsonResponse('error', 'Invalid request method');
                        }

                        // Validate required field
                        if (!isset($_POST['id']) || empty($_POST['id'])) {
                            jsonResponse('error', 'Visitor ID is required');
                        }

                        // Sanitize input
                        $id = intval($_POST['id']);

                        if ($id <= 0) {
                            jsonResponse('error', 'Invalid visitor ID');
                        }

                        // Check if visitor exists
                        $checkVisitor = $db->prepare("SELECT id FROM visitor WHERE id = ?");
                        $checkVisitor->bind_param("i", $id);
                        $checkVisitor->execute();
                        $checkVisitor->store_result();
                        
                        if ($checkVisitor->num_rows === 0) {
                            jsonResponse('error', 'Visitor not found');
                        }
                        $checkVisitor->close();

                        // Check for visitor dependencies (gate logs, etc.)
                        $checkGateLogs = $db->prepare("SELECT COUNT(*) FROM gate_logs WHERE person_type = 'visitor' AND person_id = ?");
                        $checkGateLogs->bind_param("i", $id);
                        $checkGateLogs->execute();
                        $checkGateLogs->bind_result($gateLogsCount);
                        $checkGateLogs->fetch();
                        $checkGateLogs->close();

                        if ($gateLogsCount > 0) {
                            jsonResponse('error', 'Cannot delete visitor with gate access records');
                        }

                        // Delete visitor
                        $stmt = $db->prepare("DELETE FROM visitor WHERE id = ?");
                        $stmt->bind_param("i", $id);
                        
                        if ($stmt->execute()) {
                            jsonResponse('success', 'Visitor card deleted successfully');
                        } else {
                            jsonResponse('error', 'Failed to delete visitor card: ' . $stmt->error);
                        }
                        break;

                // ===================================
        // SWAP SCHEDULE OPERATIONS - SIMPLIFIED
        // ===================================
        case 'get_all_rooms':
            // Get all rooms that have schedules
            $query = "SELECT DISTINCT rs.room_name 
                      FROM room_schedules rs 
                      ORDER BY rs.room_name";
            
            $result = $db->query($query);
            $rooms = [];
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
            
            jsonResponse('success', 'Rooms retrieved successfully', $rooms);
            break;

        case 'get_instructors_by_room':
            $room_name = sanitizeInput($db, $_GET['room_name']);
            
            $query = "SELECT DISTINCT i.id as instructor_id, rs.instructor as instructor_name 
                      FROM room_schedules rs 
                      JOIN instructor i ON rs.instructor = i.fullname 
                      WHERE rs.room_name = ? 
                      ORDER BY rs.instructor";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $room_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $instructors = [];
            while ($row = $result->fetch_assoc()) {
                $instructors[] = $row;
            }
            
            jsonResponse('success', 'Instructors retrieved successfully', $instructors);
            break;

        case 'get_room_days':
            $room_name = sanitizeInput($db, $_GET['room_name']);
            
            $query = "SELECT DISTINCT day 
                      FROM room_schedules 
                      WHERE room_name = ? 
                      ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $room_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $days = [];
            while ($row = $result->fetch_assoc()) {
                $days[] = $row;
            }
            
            jsonResponse('success', 'Days retrieved successfully', $days);
            break;

        case 'get_instructor_schedule':
            $instructor_id = (int)$_GET['instructor_id'];
            $room_name = sanitizeInput($db, $_GET['room_name']);
            $day = sanitizeInput($db, $_GET['day']);
            
            // Get instructor name first
            $instructor_query = "SELECT fullname FROM instructor WHERE id = ?";
            $instructor_stmt = $db->prepare($instructor_query);
            $instructor_stmt->bind_param("i", $instructor_id);
            $instructor_stmt->execute();
            $instructor_result = $instructor_stmt->get_result();
            $instructor = $instructor_result->fetch_assoc();
            
            if (!$instructor) {
                jsonResponse('error', 'Instructor not found');
            }
            
            $query = "SELECT rs.*, i.fullname as instructor_fullname 
                      FROM room_schedules rs 
                      JOIN instructor i ON rs.instructor = i.fullname 
                      WHERE i.id = ? AND rs.room_name = ? AND rs.day = ? 
                      LIMIT 1";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iss", $instructor_id, $room_name, $day);
            $stmt->execute();
            $result = $stmt->get_result();
            $schedule = $result->fetch_assoc();
            
            if ($schedule) {
                jsonResponse('success', 'Schedule retrieved successfully', $schedule);
            } else {
                jsonResponse('error', 'Schedule not found for the selected criteria');
            }
            break;

        case 'swap_time_schedule':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = [
                'instructor1_id', 'instructor2_id', 'room_name', 'day',
                'instructor1_original_start', 'instructor1_original_end',
                'instructor2_original_start', 'instructor2_original_end',
                'swap_date', 'expires_hours'
            ];
            
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: " . str_replace('_', ' ', $field));
                }
            }

            // Sanitize inputs
            $instructor1_id = (int)$_POST['instructor1_id'];
            $instructor2_id = (int)$_POST['instructor2_id'];
            $room_name = sanitizeInput($db, $_POST['room_name']);
            $day = sanitizeInput($db, $_POST['day']);
            $instructor1_original_start = sanitizeInput($db, $_POST['instructor1_original_start']);
            $instructor1_original_end = sanitizeInput($db, $_POST['instructor1_original_end']);
            $instructor2_original_start = sanitizeInput($db, $_POST['instructor2_original_start']);
            $instructor2_original_end = sanitizeInput($db, $_POST['instructor2_original_end']);
            $swap_date = sanitizeInput($db, $_POST['swap_date']);
            $expires_hours = (int)$_POST['expires_hours'];

            // Validate instructors are different
            if ($instructor1_id === $instructor2_id) {
                jsonResponse('error', 'Cannot swap schedule with the same instructor');
            }

            // Get instructor names
            $instructor_query = "SELECT fullname FROM instructor WHERE id IN (?, ?)";
            $instructor_stmt = $db->prepare($instructor_query);
            $instructor_stmt->bind_param("ii", $instructor1_id, $instructor2_id);
            $instructor_stmt->execute();
            $instructor_result = $instructor_stmt->get_result();
            
            $instructors = [];
            while ($row = $instructor_result->fetch_assoc()) {
                $instructors[] = $row['fullname'];
            }
            
            if (count($instructors) !== 2) {
                jsonResponse('error', 'One or both instructors not found');
            }

            $instructor1_name = $instructors[0];
            $instructor2_name = $instructors[1];

            // Get original schedule details for both instructors
            $schedule_query = "SELECT * FROM room_schedules 
                              WHERE instructor = ? AND room_name = ? AND day = ? 
                              AND start_time = ? AND end_time = ?";
            
            // Get instructor1 schedule
            $stmt1 = $db->prepare($schedule_query);
            $stmt1->bind_param("sssss", $instructor1_name, $room_name, $day, $instructor1_original_start, $instructor1_original_end);
            $stmt1->execute();
            $instructor1_schedule = $stmt1->get_result()->fetch_assoc();
            
            if (!$instructor1_schedule) {
                jsonResponse('error', 'Instructor 1 schedule not found');
            }

            // Get instructor2 schedule
            $stmt2 = $db->prepare($schedule_query);
            $stmt2->bind_param("sssss", $instructor2_name, $room_name, $day, $instructor2_original_start, $instructor2_original_end);
            $stmt2->execute();
            $instructor2_schedule = $stmt2->get_result()->fetch_assoc();
            
            if (!$instructor2_schedule) {
                jsonResponse('error', 'Instructor 2 schedule not found');
            }

            // Calculate expiration time
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_hours hours"));

            // Create swap record
            $swap_query = "INSERT INTO schedule_swaps 
                          (instructor1_id, instructor2_id, instructor1_name, instructor2_name,
                           room_name, day,
                           instructor1_original_start, instructor1_original_end,
                           instructor2_original_start, instructor2_original_end,
                           instructor1_subject, instructor2_subject,
                           instructor1_section, instructor2_section,
                           instructor1_year_level, instructor2_year_level,
                           swap_date, expires_at, is_active, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, NOW())";
            
            $swap_stmt = $db->prepare($swap_query);
            $swap_stmt->bind_param("iissssssssssssssss",
                $instructor1_id, $instructor2_id, $instructor1_name, $instructor2_name,
                $room_name, $day,
                $instructor1_original_start, $instructor1_original_end,
                $instructor2_original_start, $instructor2_original_end,
                $instructor1_schedule['subject'], $instructor2_schedule['subject'],
                $instructor1_schedule['section'], $instructor2_schedule['section'],
                $instructor1_schedule['year_level'], $instructor2_schedule['year_level'],
                $swap_date, $expires_at
            );

            if ($swap_stmt->execute()) {
                jsonResponse('success', 'Time schedules swapped successfully! The swap will be active until ' . date('M j, Y g:i A', strtotime($expires_at)));
            } else {
                jsonResponse('error', 'Failed to swap time schedules: ' . $swap_stmt->error);
            }
            break;

        case 'get_active_swaps':
            $query = "SELECT ss.*, 
                             TIMEDIFF(ss.expires_at, NOW()) as time_remaining
                      FROM schedule_swaps ss 
                      WHERE ss.is_active = TRUE AND ss.expires_at > NOW() 
                      ORDER BY ss.expires_at ASC";
            
            $result = $db->query($query);
            $swaps = [];
            
            while ($row = $result->fetch_assoc()) {
                $swaps[] = $row;
            }
            
            jsonResponse('success', 'Active swaps retrieved successfully', $swaps);
            break;

        case 'revert_swap':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            $swap_id = (int)$_POST['swap_id'];
            
            // Verify swap exists and is active
            $check_stmt = $db->prepare("SELECT id FROM schedule_swaps WHERE id = ? AND is_active = TRUE");
            $check_stmt->bind_param("i", $swap_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                jsonResponse('error', 'Active swap not found');
            }

            // Deactivate the swap
            $stmt = $db->prepare("UPDATE schedule_swaps SET is_active = FALSE WHERE id = ?");
            $stmt->bind_param("i", $swap_id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Schedule swap reverted successfully');
            } else {
                jsonResponse('error', 'Failed to revert schedule swap: ' . $stmt->error);
            }
            break;

        // Legacy swap functions - keep for compatibility but mark as deprecated
        case 'get_available_schedules':
        case 'swap_schedule':
        case 'get_instructor_rooms_legacy':
        case 'get_available_days':
        case 'get_available_instructors':
        case 'quick_swap_schedule':
            jsonResponse('error', 'This function is deprecated. Please use the new time swap functionality.');
            break;
            // Add this new case to your transac.php file
        case 'find_all_schedules_for_swap':
            $instructor1 = $_POST['instructor1'];
            $instructor2 = $_POST['instructor2'];
            $room = $_POST['room'];
            $day = $_POST['day'];
            
            // Get all schedules for instructor 1
            $stmt1 = $db->prepare("SELECT * FROM room_schedules WHERE instructor = ? AND room_name = ? AND day = ? ORDER BY start_time");
            $stmt1->bind_param("sss", $instructor1, $room, $day);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $instructor1_schedules = [];
            while ($row = $result1->fetch_assoc()) {
                $instructor1_schedules[] = $row;
            }
            
            // Get all schedules for instructor 2
            $stmt2 = $db->prepare("SELECT * FROM room_schedules WHERE instructor = ? AND room_name = ? AND day = ? ORDER BY start_time");
            $stmt2->bind_param("sss", $instructor2, $room, $day);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $instructor2_schedules = [];
            while ($row = $result2->fetch_assoc()) {
                $instructor2_schedules[] = $row;
            }
            
            if (empty($instructor1_schedules) || empty($instructor2_schedules)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'One or both instructors do not have schedules in the selected room and day.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'instructor1_schedules' => $instructor1_schedules,
                    'instructor2_schedules' => $instructor2_schedules
                ]);
            }
            break;

        default:
            jsonResponse('error', 'Invalid action');
    }
} else {
    // Handle other actions (your existing code)
    if (!isset($_GET['action'])) {
        jsonResponse('error', 'No action specified');
    }
}

$db->close();
?>