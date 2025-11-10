<?php

// DEBUGGING - Add this at the very top of transac.php
error_log("=== TRANSAC.PHP CALLED ===");
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET PARAMS: " . print_r($_GET, true));
error_log("POST PARAMS: " . print_r($_POST, true));
error_log("REQUEST URI: " . $_SERVER['REQUEST_URI']);




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

// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Function to handle file uploads
function handleFileUpload($fileInput, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 2 * 1024 * 1024) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => 'default.png'];
    }

    $file = $_FILES[$fileInput];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
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
        // PERSONNEL CRUD OPERATIONS
        // ========================
        case 'add_personnel':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            // Validate required fields
            $required = ['last_name', 'first_name', 'date_of_birth', 'id_number', 'role', 'category', 'department'];
            $missing_fields = [];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                jsonResponse('error', "Missing required fields: " . implode(', ', $missing_fields));
            }

            // Sanitize inputs
            $last_name = sanitizeInput($db, $_POST['last_name']);
            $first_name = sanitizeInput($db, $_POST['first_name']);
            $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
            $id_number = sanitizeInput($db, $_POST['id_number']);
            $role = sanitizeInput($db, $_POST['role']);
            $category = sanitizeInput($db, $_POST['category']);
            $department = sanitizeInput($db, $_POST['department']);
            $status = 'Active';

            // Validate ID Number format
            if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
                jsonResponse('error', 'ID Number must be in 0000-0000 format');
            }

            // Check if ID Number already exists
            $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND deleted = 0");
            $check_id->bind_param("s", $id_number);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already exists');
            }
            $check_id->close();

            // Handle file upload
            $photo = 'default.png';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/personell/');
                if ($uploadResult['success']) {
                    $photo = $uploadResult['filename'];
                }
            }

            // Insert record
            $query = "INSERT INTO personell (
                id_number, last_name, first_name, date_of_birth, 
                role, category, department, status, photo, date_added
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param(
                "sssssssss", 
                $id_number, $last_name, $first_name, $date_of_birth,
                $role, $category, $department, $status, $photo
            );

            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel added successfully');
            } else {
                jsonResponse('error', 'Failed to add personnel: ' . $stmt->error);
            }
            break;

        case 'update_personnel':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

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
                jsonResponse('error', "Missing required fields: " . implode(', ', $missing_fields));
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $last_name = sanitizeInput($db, $_POST['last_name']);
            $first_name = sanitizeInput($db, $_POST['first_name']);
            $date_of_birth = sanitizeInput($db, $_POST['date_of_birth']);
            $id_number = sanitizeInput($db, $_POST['id_number']);
            $role = sanitizeInput($db, $_POST['role']);
            $category = sanitizeInput($db, $_POST['category']);
            $department = sanitizeInput($db, $_POST['department']);
            $status = sanitizeInput($db, $_POST['status']);

            // Validate ID
            if ($id <= 0) {
                jsonResponse('error', 'Invalid personnel ID');
            }

            // Validate ID Number format
            if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
                jsonResponse('error', 'ID Number must be in 0000-0000 format');
            }

            // Check if ID Number exists for other personnel
            $check_id = $db->prepare("SELECT id FROM personell WHERE id_number = ? AND id != ? AND deleted = 0");
            $check_id->bind_param("si", $id_number, $id);
            $check_id->execute();
            $check_id->store_result();
            
            if ($check_id->num_rows > 0) {
                jsonResponse('error', 'ID Number already assigned to another personnel');
            }
            $check_id->close();

            // Handle file upload
            $photo_update = '';
            $new_photo = '';
            
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload('photo', '../uploads/personell/');
                if ($uploadResult['success']) {
                    $new_photo = $uploadResult['filename'];
                    $photo_update = ", photo = ?";
                }
            }

            // Build the update query
            $query = "UPDATE personell SET 
                id_number = ?, last_name = ?, first_name = ?, date_of_birth = ?,
                role = ?, category = ?, department = ?, status = ?";
            
            if (!empty($photo_update)) {
                $query .= $photo_update;
            }
            
            $query .= " WHERE id = ?";
            
            // Prepare the statement
            $stmt = $db->prepare($query);
            if (!$stmt) {
                jsonResponse('error', 'Database prepare failed: ' . $db->error);
            }

            // Bind parameters based on whether we're updating photo or not
            if (!empty($photo_update)) {
                $stmt->bind_param("sssssssssi", 
                    $id_number, $last_name, $first_name, $date_of_birth,
                    $role, $category, $department, $status, $new_photo, $id
                );
            } else {
                $stmt->bind_param("ssssssssi", 
                    $id_number, $last_name, $first_name, $date_of_birth,
                    $role, $category, $department, $status, $id
                );
            }

            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel updated successfully');
            } else {
                jsonResponse('error', 'Failed to update personnel: ' . $stmt->error);
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
            $checkPersonnel->bind_param("i", $id);
            $checkPersonnel->execute();
            $checkPersonnel->store_result();
            
            if ($checkPersonnel->num_rows === 0) {
                jsonResponse('error', 'Personnel not found');
            }
            
            $checkPersonnel->bind_result($personnel_id, $personnel_id_number);
            $checkPersonnel->fetch();
            $checkPersonnel->close();

            // Soft delete personnel
            $stmt = $db->prepare("UPDATE personell SET deleted = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
            }
            break;

        // Add other CRUD operations here as needed...

        default:
            jsonResponse('error', 'Invalid action: ' . $_GET['action']);
    }
} else {
    jsonResponse('error', 'Invalid request');
}

$db->close();
?>