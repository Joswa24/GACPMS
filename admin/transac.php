<?php
include('../connection.php');
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

// Debug logging
error_log("=== TRANSAC.PHP CALLED ===");
error_log("Action: " . $action);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Function to handle file uploads
function handleFileUpload($fileInput, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 2 * 1024 * 1024) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => 'default.png']; // No file uploaded is okay
    }

    $file = $_FILES[$fileInput];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }

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
    'get_all_rooms', 'get_instructors_by_room', 'get_room_days',
    'get_instructor_schedule', 'swap_time_schedule', 'get_active_swaps', 'revert_swap',
    'find_all_schedules_for_swap'
];

$isAjaxRequest = isset($_GET['action']) && in_array($_GET['action'], $validAjaxActions);

if ($isAjaxRequest) {
    error_log("Processing AJAX action: " . $_GET['action']);
    
    // For AJAX requests, handle specific actions
    switch ($_GET['action']) {
        // ============================
        // DEPARTMENT CRUD OPERATIONS
        // ============================
        case 'add_department':
            error_log("=== ADD DEPARTMENT STARTED ===");
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method. Expected POST, got ' . $_SERVER['REQUEST_METHOD']);
            }

            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

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

        // Add other cases here... but let's test with just department first

        default:
            jsonResponse('error', 'Invalid action: ' . $_GET['action']);
    }
} else {
    error_log("Not an AJAX request or invalid action");
    jsonResponse('error', 'Invalid request');
}

$db->close();
?>