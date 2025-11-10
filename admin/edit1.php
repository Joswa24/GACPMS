<?php
include('../connection.php');
session_start();

// Function definitions
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

function jsonResponse($status, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Validate required parameters
if (!isset($_GET['edit'])) {
    jsonResponse('error', 'Missing edit parameter');
}

$editType = $_GET['edit'];

// Handle cases that require ID from POST
if ($editType === 'student' || $editType === 'instructor' || $editType === 'personell') {
    if (!isset($_POST['id'])) {
        jsonResponse('error', 'Missing ID parameter');
    }
    $id = intval($_POST['id']);
} else {
    if (!isset($_GET['id'])) {
        jsonResponse('error', 'Missing ID parameter');
    }
    $id = intval($_GET['id']);
}

// Validate ID
if ($id <= 0) {
    jsonResponse('error', 'Invalid ID');
}

// Handle different edit types
switch ($editType) {
    case 'instructor':
        // Validate required fields
        if (empty($_POST['id'])) {
            jsonResponse('error', 'Missing instructor ID');
        }
        if (empty($_POST['fullname'])) {
            jsonResponse('error', 'Full name is required');
        }
        if (empty($_POST['department_id'])) {
            jsonResponse('error', 'Department is required');
        }

        // Sanitize inputs
        $id = intval($_POST['id']);
        $fullname = sanitizeInput($db, trim($_POST['fullname']));
        $id_number = !empty($_POST['id_number']) ? sanitizeInput($db, trim($_POST['id_number'])) : null;
        $department_id = intval($_POST['department_id']);

        // Validate IDs
        if ($id <= 0) {
            jsonResponse('error', 'Invalid instructor ID');
        }
        if ($department_id <= 0) {
            jsonResponse('error', 'Invalid department ID');
        }

        // Validate ID number format if provided (must be 0000-0000 format)
        if ($id_number && !preg_match('/^\d{4}-\d{4}$/', $id_number)) {
            jsonResponse('error', 'Invalid ID format. Must be in 0000-0000 format (four digits, hyphen, four digits)');
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

        // Check if department exists
        $checkDepartment = $db->prepare("SELECT department_id FROM department WHERE department_id = ?");
        $checkDepartment->bind_param("i", $department_id);
        $checkDepartment->execute();
        $checkDepartment->store_result();
        
        if ($checkDepartment->num_rows === 0) {
            jsonResponse('error', 'Department not found');
        }
        $checkDepartment->close();

        // Check ID number uniqueness if provided
        if ($id_number) {
            $checkId = $db->prepare("SELECT id FROM instructor WHERE id_number = ? AND id != ?");
            $checkId->bind_param("si", $id_number, $id);
            $checkId->execute();
            $checkId->store_result();
            
            if ($checkId->num_rows > 0) {
                jsonResponse('error', 'ID number already assigned to another instructor');
            }
            $checkId->close();
        }

        // Update instructor
        $stmt = $db->prepare("UPDATE instructor SET 
                             fullname = ?, 
                             id_number = ?,
                             department_id = ?,
                             updated_at = NOW()
                             WHERE id = ?");
        $stmt->bind_param("ssii", $fullname, $id_number, $department_id, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Instructor updated successfully');
        } else {
            jsonResponse('error', 'Failed to update instructor: ' . $db->error);
        }
        break;

    case 'subject':
    // Get ID from POST instead of GET
    if (!isset($_POST['id'])) {
        jsonResponse('error', 'Missing ID parameter');
    }
    $id = intval($_POST['id']);
    
    // Rest of your subject case code remains the same...
    // Validate required fields
    if (empty($_POST['subject_code']) || empty($_POST['subject_name']) || empty($_POST['year_level'])) {
        jsonResponse('error', 'All fields are required');
    }

    // Sanitize inputs
    $subject_code = sanitizeInput($db, $_POST['subject_code']);
    $subject_name = sanitizeInput($db, $_POST['subject_name']);
    $year_level = sanitizeInput($db, $_POST['year_level']);

    

        // Validate year level format
        $valid_year_levels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        if (!in_array($year_level, $valid_year_levels)) {
            jsonResponse('error', 'Invalid year level');
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

        // Check if subject code already exists (excluding current subject)
        $checkCode = $db->prepare("SELECT id FROM subjects WHERE subject_code = ? AND id != ?");
        $checkCode->bind_param("si", $subject_code, $id);
        $checkCode->execute();
        $checkCode->store_result();
        
        if ($checkCode->num_rows > 0) {
            jsonResponse('error', 'Subject code already exists');
        }
        $checkCode->close();

        // Update subject
        $stmt = $db->prepare("UPDATE subjects SET 
                             subject_code = ?,
                             subject_name = ?,
                             year_level = ?,
                             updated_at = NOW()
                             WHERE id = ?");
        $stmt->bind_param("sssi", $subject_code, $subject_name, $year_level, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Subject updated successfully');
        } else {
            jsonResponse('error', 'Failed to update subject: ' . $db->error);
        }
        break;

    case 'personell':
        // Get data from POST
        $rfid_number = mysqli_real_escape_string($db, $_POST['rfid_number']);
        $last_name = mysqli_real_escape_string($db, $_POST['last_name']);
        $first_name = mysqli_real_escape_string($db, $_POST['first_name']);
        $date_of_birth = mysqli_real_escape_string($db, $_POST['date_of_birth']);
        $status = mysqli_real_escape_string($db, $_POST['status']);
        $role = mysqli_real_escape_string($db, $_POST['role']);
        $category = mysqli_real_escape_string($db, $_POST['category']);
        $department = mysqli_real_escape_string($db, $_POST['e_department']);

        // File upload handling
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            // Check if image file is a actual image
            $check = getimagesize($_FILES["photo"]["tmp_name"]);
            if($check === false) {
                jsonResponse('error', 'File is not an image');
            }
            
            // Check file size (max 2MB)
            if ($_FILES["photo"]["size"] > 2000000) {
                jsonResponse('error', 'File is too large (max 2MB)');
            }
            
            // Allow certain file formats
            $imageFileType = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                jsonResponse('error', 'Only JPG, JPEG, PNG files are allowed');
            }
            
            // Generate unique filename
            $photo = uniqid() . '.' . $imageFileType;
            $target_dir = "uploads/";
            $target_file = $target_dir . $photo;
            
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                jsonResponse('error', 'Error uploading file');
            }
        } else {
            // If no new photo uploaded, keep the existing one
            $photo = basename($_POST['capturedImage']); // Just get the filename
        }

        // Validate RFID number
        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID number must be exactly 10 digits');
        }

        // Check if RFID number already exists for another personnel
        $checkQuery = "SELECT id FROM personell WHERE rfid_number = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("ss", $rfid_number, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'RFID number already exists for another personnel');
        }
        $stmt->close();

        // Prepare the update query
        $query = "UPDATE personell SET 
            photo = ?,
            rfid_number = ?,
            last_name = ?,
            first_name = ?,
            date_of_birth = ?,
            role = ?,
            category = ?,
            department = ?,
            status = ?,
            date_added = NOW()
            WHERE id = ?";

        $stmt = $db->prepare($query);
        $stmt->bind_param(
            "ssssssssss", 
            $photo, $rfid_number, $last_name, $first_name,
            $date_of_birth, $role, $category, $department, 
            $status, $id
        );

        if ($stmt->execute()) {
            // If status changed, update lostcard table
            $status_value = ($status == 'Active') ? 0 : 1;
            $query1 = "UPDATE lostcard SET status = ? WHERE personnel_id = ?";
            $stmt1 = $db->prepare($query1);
            $stmt1->bind_param("is", $status_value, $id);
            $stmt1->execute();
            $stmt1->close();

            $_SESSION['success_message'] = 'Personnel record updated successfully';
            jsonResponse('success', 'Personnel record updated successfully');
        } else {
            jsonResponse('error', 'Failed to update personnel record: ' . $db->error);
        }
        break;

    case 'department':
        // Validate required fields
        $department_name = isset($_POST['dptname']) ? trim($_POST['dptname']) : '';
        $department_desc = isset($_POST['dptdesc']) ? trim($_POST['dptdesc']) : '';

        if (empty($department_name)) {
            jsonResponse('error', 'Department name is required');
        }

        // Check if department exists (excluding current one)
        $checkQuery = "SELECT COUNT(*) FROM department WHERE department_name = ? AND department_id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("si", $department_name, $id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            jsonResponse('error', 'Department name already exists');
        }

        // Update department
        $query = "UPDATE department SET department_name = ?, department_desc = ? WHERE department_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssi", $department_name, $department_desc, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Department updated successfully');
        } else {
            jsonResponse('error', 'Error updating department: ' . $stmt->error);
        }
        break;

    case 'visitor':
        $rfid_number = $_POST['rfid_number'];
        
        if (strlen($rfid_number) !== 10 || !ctype_digit($rfid_number)) {
            jsonResponse('error', 'RFID number must be exactly 10 digits');
        }

        // Check if RFID exists for another visitor
        $checkQuery = "SELECT id FROM visitor WHERE rfid_number = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param('si', $rfid_number, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'RFID number already exists');
        }
        $stmt->close();

        // Update visitor
        $query = "UPDATE visitor SET rfid_number = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('si', $rfid_number, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Visitor updated successfully');
        } else {
            jsonResponse('error', 'Error updating visitor: ' . $stmt->error);
        }
        break;
        
    case 'about':
        // Get form data
        $name = $_POST['name'];
        $address = $_POST['address'];
        $logo1 = basename($_POST['logo1']); // Just get the filename
        $logo2 = basename($_POST['logo2']); // Just get the filename
    
        // Handle logo1 file upload
        if (isset($_FILES['logo1']) && $_FILES['logo1']['error'] == UPLOAD_ERR_OK) {
            $check = getimagesize($_FILES["logo1"]["tmp_name"]);
            if($check === false) {
                jsonResponse('error', 'File is not an image');
            }
            
            if ($_FILES["logo1"]["size"] > 2000000) {
                jsonResponse('error', 'File is too large (max 2MB)');
            }
            
            $imageFileType = strtolower(pathinfo($_FILES["logo1"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                jsonResponse('error', 'Only JPG, JPEG, PNG files are allowed');
            }
            
            $logo1 = uniqid() . '.' . $imageFileType;
            $target_dir = "uploads/";
            $target_file = $target_dir . $logo1;
            
            if (!move_uploaded_file($_FILES["logo1"]["tmp_name"], $target_file)) {
                jsonResponse('error', 'Error uploading logo1');
            }
        }
    
        // Handle logo2 file upload
        if (isset($_FILES['logo2']) && $_FILES['logo2']['error'] == UPLOAD_ERR_OK) {
            $check = getimagesize($_FILES["logo2"]["tmp_name"]);
            if($check === false) {
                jsonResponse('error', 'File is not an image');
            }
            
            if ($_FILES["logo2"]["size"] > 2000000) {
                jsonResponse('error', 'File is too large (max 2MB)');
            }
            
            $imageFileType = strtolower(pathinfo($_FILES["logo2"]["name"], PATHINFO_EXTENSION));
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
                jsonResponse('error', 'Only JPG, JPEG, PNG files are allowed');
            }
            
            $logo2 = uniqid() . '.' . $imageFileType;
            $target_dir = "uploads/";
            $target_file = $target_dir . $logo2;
            
            if (!move_uploaded_file($_FILES["logo2"]["tmp_name"], $target_file)) {
                jsonResponse('error', 'Error uploading logo2');
            }
        }
    
        // Update database
        $query = "UPDATE about SET 
                    name = ?,
                    address = ?,
                    logo1 = ?,
                    logo2 = ?
                  WHERE id = 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssss", $name, $address, $logo1, $logo2);

        if ($stmt->execute()) {
            jsonResponse('success', 'About information updated successfully');
        } else {
            jsonResponse('error', 'Error updating about information: ' . $stmt->error);
        }
        break;

    case 'role':
        $role = $_POST['role'];
    
        // Check if the role already exists
        $checkQuery = "SELECT id FROM role WHERE role = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param('si', $role, $id);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'Role already exists');
        }
        $stmt->close();

        // Update role
        $query = "UPDATE role SET role = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('si', $role, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Role updated successfully');
        } else {
            jsonResponse('error', 'Error updating role: ' . $stmt->error);
        }
        break;

    case 'room':
        // Get the POST data
        $room = $_POST['roomname'];
        $department = $_POST['roomdpt'];
        $descr = $_POST['roomdesc'];
        $role = $_POST['roomrole'];
        $password = $_POST['roompass'];
        
        // Check if the room and department already exist
        $checkQuery = "SELECT id FROM rooms WHERE room = ? AND department = ? AND id != ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->bind_param("ssi", $room, $department, $id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            jsonResponse('error', 'Room already exists in this department');
        }
        $stmt->close();

        // Update room
        $query = "UPDATE rooms SET 
                    room = ?,
                    department = ?,
                    descr = ?,
                    authorized_personnel = ?,
                    password = ?
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssssi", $room, $department, $descr, $role, $password, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Room updated successfully');
        } else {
            jsonResponse('error', 'Error updating room: ' . $stmt->error);
        }
        break;
        
    case 'student':
        // Validate required fields
        $required = ['id', 'department_id', 'id_number', 'fullname', 'section', 'year'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                jsonResponse('error', "Missing required field: $field");
            }
        }

        // Sanitize inputs
        $id = intval($_POST['id']);
        $department_id = intval($_POST['department_id']);
        $id_number = sanitizeInput($db, $_POST['id_number']);
        $fullname = sanitizeInput($db, $_POST['fullname']);
        $section = sanitizeInput($db, $_POST['section']);
        $year = sanitizeInput($db, $_POST['year']);

        // Validate student ID format (YYYY-XXXX)
        if (!preg_match('/^\d{4}-\d{4}$/', $id_number)) {
            jsonResponse('error', 'Invalid student ID format. Must be YYYY-XXXX');
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

        // Check duplicate ID number (excluding current student)
        $checkID = $db->prepare("SELECT id FROM students WHERE id_number = ? AND id != ?");
        $checkID->bind_param("si", $id_number, $id);
        $checkID->execute();
        $checkID->store_result();
        
        if ($checkID->num_rows > 0) {
            jsonResponse('error', 'This ID number already exists for another student');
        }
        $checkID->close();

        // Update student record
        $stmt = $db->prepare("UPDATE students SET 
                             department_id = ?, 
                             id_number = ?, 
                             fullname = ?, 
                             section = ?, 
                             year = ?,
                             updated_at = NOW()
                             WHERE id = ?");
        $stmt->bind_param("sssssi", $department_id, $id_number, $fullname, $section, $year, $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Student updated successfully');
        } else {
            jsonResponse('error', 'Failed to update student: ' . $db->error);
        }
        break;

    case 'schedule':
        // First check if edit parameter exists in either GET or POST
        $editParam = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET['edit'] ?? '' : $_POST['edit'] ?? '';
        if ($editParam !== 'schedule') {
            jsonResponse('error', 'Missing or invalid edit parameter');
        }

        // Check if this is a GET request (for fetching data)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Validate required parameters
            if (!isset($_GET['id'])) {
                jsonResponse('error', 'Missing ID parameter');
            }

            $id = intval($_GET['id']);
            if ($id <= 0) {
                jsonResponse('error', 'Invalid ID');
            }

            $response = ['status' => 'error', 'message' => ''];
            
            try {
                $stmt = $db->prepare("SELECT * FROM room_schedules WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $response['status'] = 'success';
                    $response['data'] = $result->fetch_assoc();
                } else {
                    $response['message'] = 'Schedule not found';
                }
            } catch (Exception $e) {
                $response['message'] = 'Error: ' . $e->getMessage();
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        // Handle POST request - update schedule
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate required fields
            $required = ['id', 'department', 'room_name', 'subject', 'section', 'year_level', 'day', 'instructor', 'start_time', 'end_time'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    jsonResponse('error', "Missing required field: $field");
                }
            }

            // Sanitize inputs
            $id = intval($_POST['id']);
            $department = sanitizeInput($db, $_POST['department']);
            $room_name = sanitizeInput($db, $_POST['room_name']);
            $subject = sanitizeInput($db, $_POST['subject']);
            $section = sanitizeInput($db, $_POST['section']);
            $year_level = sanitizeInput($db, $_POST['year_level']);
            $day = sanitizeInput($db, $_POST['day']);
            $instructor = sanitizeInput($db, $_POST['instructor']);
            $start_time = sanitizeInput($db, $_POST['start_time']);
            $end_time = sanitizeInput($db, $_POST['end_time']);

            // Validate time format
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
                !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                jsonResponse('error', 'Invalid time format (use HH:MM)');
            }

            // Check if end time is after start time
            if (strtotime($end_time) <= strtotime($start_time)) {
                jsonResponse('error', 'End time must be after start time');
            }

            // Check for schedule conflicts (excluding current schedule)
            $conflict_check = $db->prepare("SELECT id FROM room_schedules 
                                           WHERE room_name = ? 
                                           AND day = ? 
                                           AND ((start_time < ? AND end_time > ?) 
                                           OR (start_time < ? AND end_time > ?) 
                                           OR (start_time >= ? AND end_time <= ?))
                                           AND id != ?");
            $conflict_check->bind_param("ssssssssi", 
                $room_name, $day, 
                $end_time, $start_time,
                $start_time, $end_time,
                $start_time, $end_time,
                $id
            );
            $conflict_check->execute();
            $conflict_check->store_result();

            if ($conflict_check->num_rows > 0) {
                jsonResponse('error', 'Schedule conflict: Another class is already scheduled in this room at the same time');
            }
            $conflict_check->close();

            // Update schedule
            $stmt = $db->prepare("UPDATE room_schedules SET 
                                 department = ?,
                                 room_name = ?,
                                 subject = ?,
                                 section = ?,
                                 year_level = ?,
                                 day = ?,
                                 instructor = ?,
                                 start_time = ?,
                                 end_time = ?,
                                 updated_at = NOW()
                                 WHERE id = ?");
            $stmt->bind_param("sssssssssi", 
                $department, $room_name, $subject, $section,
                $year_level, $day, $instructor, $start_time,
                $end_time, $id
            );

            if ($stmt->execute()) {
                jsonResponse('success', 'Schedule updated successfully');
            } else {
                jsonResponse('error', 'Failed to update schedule: ' . $db->error);
            }
        }
        break;

    default:
        jsonResponse('error', 'Invalid edit type specified');
}

// Close database connection
$db->close();
?>