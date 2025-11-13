<?php

// Simple error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
include 'security-headers.php';
include 'connection.php';

// Start session first
session_start();
// Clear any existing output
if (ob_get_level() > 0) {
    ob_clean();
}


// =====================================================================
// HELPER FUNCTION - Improved Sanitization
// =====================================================================
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// =====================================================================
// PASSWORD VALIDATION FUNCTION - Universal for all rooms
// =====================================================================
function validateRoomPassword($db, $department, $location, $password, $id_number) {
    $errors = [];
    
    // Step 1: Validate room exists and get room details
    $stmt = $db->prepare("SELECT * FROM rooms WHERE department = ? AND room = ?");
    $stmt->bind_param("ss", $department, $location);
    $stmt->execute();
    $roomResult = $stmt->get_result();

    if ($roomResult->num_rows === 0) {
        $errors[] = "Room not found in this department.";
        return ['success' => false, 'errors' => $errors];
    }

    $room = $roomResult->fetch_assoc();

    // Step 2: Verify password for this specific room - THIS IS THE KEY FIX
    $stmt = $db->prepare("SELECT * FROM rooms WHERE department = ? AND room = ? AND password = ?");
    $stmt->bind_param("sss", $department, $location, $password);
    $stmt->execute();
    $passwordResult = $stmt->get_result();

    // THIS CHECK MUST HAPPEN FOR ALL ROOMS, NOT JUST GATE
    if ($passwordResult->num_rows === 0) {
        $errors[] = "Invalid password for this room.";
        return ['success' => false, 'errors' => $errors];
    }

    // Step 3: Check user authorization based on room type
    $authorizedPersonnel = $room['authorized_personnel'] ?? '';
    
    // Gate access - Security personnel only
    if ($department === 'Main' && $location === 'Gate') {
        return validateSecurityPersonnel($db, $id_number, $room);
    }
    
    // Classroom access - Instructors only (default for academic rooms)
    if (empty($authorizedPersonnel) || 
        stripos($authorizedPersonnel, 'Instructor') !== false || 
        stripos($authorizedPersonnel, 'Faculty') !== false) {
        return validateInstructor($db, $id_number, $room);
    }
    
    // Other specialized rooms - Check specific personnel types
    return validateOtherPersonnel($db, $id_number, $room, $authorizedPersonnel);
}

// =====================================================================
// SECURITY PERSONNEL VALIDATION
// =====================================================================
function validateSecurityPersonnel($db, $id_number, $room) {
    $clean_id =( $id_number);
    
    // Check personell table for security personnel
    $stmt = $db->prepare("SELECT * FROM personell WHERE id_number = ? AND department = 'Main'");
    $stmt->bind_param("s", $clean_id);
    $stmt->execute();
    $securityResult = $stmt->get_result();

    if ($securityResult->num_rows === 0) {
        // Try with role-based search
        $stmt = $db->prepare("SELECT * FROM personell WHERE id_number = ? AND role LIKE '%Security%'");
        $stmt->bind_param("s", $clean_id);
        $stmt->execute();
        $securityResult = $stmt->get_result();
    }

    if ($securityResult->num_rows === 0) {
        return [
            'success' => false, 
            'errors' => ['Security personnel not found with this ID.']
        ];
    }

    $securityGuard = $securityResult->fetch_assoc();
    
    // Check if they have security role
    $role = strtolower($securityGuard['role'] ?? '');
    $isSecurity = stripos($role, 'security') !== false || stripos($role, 'guard') !== false;
    
    if (!$isSecurity) {
        return [
            'success' => false, 
            'errors' => ["Unauthorized access. User found but not security personnel. Role: " . ($securityGuard['role'] ?? 'Unknown')]
        ];
    }

    return [
        'success' => true,
        'user_type' => 'security',
        'user_data' => [
            'id' => $securityGuard['id'],
            'fullname' => $securityGuard['first_name'] . ' ' . $securityGuard['last_name'],
            'id_number' => $securityGuard['id_number'],
            'role' => $securityGuard['role']
        ],
        'room_data' => $room
    ];
}

// =====================================================================
// INSTRUCTOR VALIDATION
// =====================================================================
function validateInstructor($db, $id_number, $room) {
    // Verify ID number against instructor table
    $stmt = $db->prepare("SELECT * FROM instructor WHERE id_number = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $instructorResult = $stmt->get_result();

    if ($instructorResult->num_rows === 0) {
        return [
            'success' => false, 
            'errors' => ['Instructor not found with this ID number.']
        ];
    }

    $instructor = $instructorResult->fetch_assoc();

    return [
        'success' => true,
        'user_type' => 'instructor',
        'user_data' => [
            'id' => $instructor['id'],
            'fullname' => $instructor['fullname'],
            'id_number' => $instructor['id_number']
        ],
        'room_data' => $room
    ];
    
}

// =====================================================================
// OTHER PERSONNEL VALIDATION
// =====================================================================
function validateOtherPersonnel($db, $id_number, $room, $authorizedPersonnel) {
    $clean_id = str_replace('-', '', $id_number);
    
    // Check personell table
    $stmt = $db->prepare("SELECT * FROM personell WHERE id_number = ?");
    $stmt->bind_param("s", $clean_id);
    $stmt->execute();
    $personnelResult = $stmt->get_result();

    if ($personnelResult->num_rows === 0) {
        return [
            'success' => false, 
            'errors' => ['Personnel not found with this ID.']
        ];
    }

    $personnel = $personnelResult->fetch_assoc();
    
    // Check if personnel role matches authorized personnel for this room
    $personnelRole = strtolower($personnel['role'] ?? '');
    $requiredRole = strtolower($authorizedPersonnel);
    
    if (stripos($personnelRole, $requiredRole) === false) {
        return [
            'success' => false, 
            'errors' => ["Unauthorized access. Your role '{$personnel['role']}' does not match required role '{$authorizedPersonnel}' for this room."]
        ];
    }

    return [
        'success' => true,
        'user_type' => 'personnel',
        'user_data' => [
            'id' => $personnel['id'],
            'fullname' => $personnel['first_name'] . ' ' . $personnel['last_name'],
            'id_number' => $personnel['id_number'],
            'role' => $personnel['role']
        ],
        'room_data' => $room
    ];
}
function getSubjectDetails($db, $subject, $room) {
    $stmt = $db->prepare("SELECT year_level, section FROM room_schedules WHERE subject = ? AND room_name = ? LIMIT 1");
    $stmt->bind_param("ss", $subject, $room);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? ['year_level' => '1st Year', 'section' => 'A'];
}
// =====================================================================
// MAIN LOGIN PROCESSING
// =====================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $department = sanitizeInput($_POST['roomdpt'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $password = $_POST['Ppassword'] ?? '';
    $id_number = sanitizeInput($_POST['Pid_number'] ?? '');
    $selected_subject = sanitizeInput($_POST['selected_subject'] ?? '');
    $selected_room = sanitizeInput($_POST['selected_room'] ?? '');

    // Validate required inputs
    $errors = [];
    if (empty($department)) $errors[] = "Department is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($id_number)) $errors[] = "ID number is required";
    
    if (!empty($errors)) {
        http_response_code(400);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => implode("<br>", $errors)]));
    }

    // Universal password validation for all rooms
    $validationResult = validateRoomPassword($db, $department, $location, $password, $id_number);
    
    if (!$validationResult['success']) {
        sleep(2); // Rate limiting
        http_response_code(401);
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error', 
            'message' => implode("<br>", $validationResult['errors'])
        ]));
    }

    // Login successful - set session data based on user type
    $userType = $validationResult['user_type'];
    $userData = $validationResult['user_data'];
    $roomData = $validationResult['room_data'];

    $_SESSION['access'] = [
        'user_type' => $userType,
        'last_activity' => time()
    ];

    // Set user-specific session data
    if ($userType === 'security') {
        $_SESSION['access']['security'] = $userData;
        $_SESSION['access']['room'] = $roomData;
        $redirectUrl = 'main.php';
        
    } elseif ($userType === 'instructor') {
        $_SESSION['access']['instructor'] = $userData;
        $_SESSION['access']['room'] = $roomData;
        $_SESSION['access']['subject'] = [
            'name' => $selected_subject,
            'room' => $selected_room,
            'time' => $_POST['selected_time'] ?? ''
        ];
        $redirectUrl = 'main1.php';
                

        // Record instructor session start time
        $currentTime = date('Y-m-d H:i:s');
        $_SESSION['instructor_login_time'] = $currentTime;
        $_SESSION['instructor_session_started'] = true;

        // Get year_level and section from the selected subject
        $subjectDetails = getSubjectDetails($db, $selected_subject, $selected_room);
        $yearLevel = $subjectDetails['year_level'] ?? "1st Year";
        $section = $subjectDetails['section'] ?? "A";

        // Create instructor attendance summary record
        $instructorId = $userData['id'];
        $instructorName = $userData['fullname'];
        $subjectName = $selected_subject;
        $sessionDate = date('Y-m-d');
        $timeIn = date('H:i:s');

        $sessionSql = "INSERT INTO instructor_attendance_summary 
                    (instructor_id, instructor_name, subject_name, year_level, section, 
                        total_students, present_count, absent_count, attendance_rate,
                        session_date, time_in, time_out) 
                    VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0.00, ?, ?, '00:00:00')";
        $stmt = $db->prepare($sessionSql);
        $stmt->bind_param("issssss", $instructorId, $instructorName, $subjectName, $yearLevel, $section, $sessionDate, $timeIn);
        $stmt->execute();
        $_SESSION['attendance_session_id'] = $stmt->insert_id;

    }
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Clear any existing output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set proper headers
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    // Return success response
    // In the login success section of index.php, update the response:
    echo json_encode([
        'status' => 'success',
        'redirect' => $redirectUrl,
        'message' => 'Login successful',
        'user_type' => $userType,
        'instructor_id' => $userData['id'], // Add this
        'instructor_name' => $userData['fullname'] // Add this
    ]);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GACPMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Gate and Personnel Management System">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- CORRECTED Content Security Policy -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
    script-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://fonts.googleapis.com 'unsafe-inline' 'unsafe-eval'; 
    style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; 
    font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com; 
    img-src 'self' data: https:; 
    connect-src 'self'; 
    frame-ancestors 'none';">
    
    <!-- Security Meta Tags -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin/css/bootstrap.min.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
            line-height: 1.6;
            color: var(--dark-text);
        }

        .login-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo-title-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header-logo {
            height: 80px;
            width: 100px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.9);
            padding: 3px;
        }

        .system-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .location-indicator {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-text);
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 8px;
            color: var(--icon-color);
        }

        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .input-group-text {
            background-color: var(--light-bg);
            border: none;
            padding: 0.75rem 1rem;
            color: var(--accent-color);
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--gray-300);
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background-color: var(--white);
            border: none;
            background-color: var(--light-bg);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
            background-color: white;
            box-shadow: none;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            transition: var(--transition);
            padding: 5px;
            border-radius: 4px;
            z-index: 5;
            background: white;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--primary);
            background-color: var(--gray-100);
        }

        .gate-access-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f7ff 100%);
            border-left: 4px solid var(--accent-color);
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .gate-access-info i {
            color: var(--accent-color);
            margin-right: 10px;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 14px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .terms-link {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition);
        }

        .terms-link:hover {
            color: var(--icon-color);
        }

        /* Alert Styles */
        .alert-container {
            margin-bottom: 20px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            border: none;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning-color);
        }

        /* Scanner Container Styles */
        .scanner-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .scanner-container:hover {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        }
        
        .scanner-container.scanning {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-style: solid;
        }
        
        .scanner-container.scanned {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-style: solid;
        }
        
        .scanner-icon {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .scanner-container.scanning .scanner-icon {
            color: var(--accent-color);
            animation: scan 1s infinite;
        }
        
        .scanner-container.scanned .scanner-icon {
            color: var(--success-color);
        }
        
        @keyframes scan {
            0% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0); }
        }
        
        .scanner-title {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .scanner-instruction {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .barcode-display {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 3px;
            color: #2c3e50;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #ced4da;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            word-break: break-all;
            width: 100%;
            margin-top: 15px;
        }
        
        .barcode-placeholder {
            color: #6c757d;
            font-style: italic;
            font-size: 1rem;
        }
        
        .barcode-value {
            color: var(--success-color);
            animation: highlight 1s ease;
        }
        
        @keyframes highlight {
            0% { 
                background-color: #d1f7e9;
                transform: scale(1.05);
            }
            100% { 
                background-color: white;
                transform: scale(1);
            }
        }

        .scan-indicator {
            text-align: center;
            margin: 10px 0;
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .scan-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .hidden-field {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            height: 0;
            width: 0;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 20px 25px;
            border: none;
        }

        .modal-title {
            font-weight: 600;
            font-size: 18px;
        }

        .btn-close-white {
            filter: invert(1);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--gray-200);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        .subject-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .subject-table th {
            background-color: var(--gray-100);
            font-weight: 600;
            font-size: 14px;
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-300);
        }

        .subject-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
        }

        .subject-table tr:last-child td {
            border-bottom: none;
        }

        .subject-table tr:hover {
            background-color: var(--gray-50);
        }

        .badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Security and additional elements */
        .attempts-counter {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--danger-color);
            margin: 10px 0;
        }
        
        .attempts-warning {
            font-size: 0.9rem;
            color: var(--warning-color);
            font-weight: 600;
            margin-top: 10px;
        }

        .login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .system-title {
                font-size: 22px;
            }
            
            .logo-title-wrapper {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-logo {
                height: 70px;
                width: 90px;
            }

            .scanner-container {
                padding: 20px;
                min-height: 120px;
            }
            
            .scanner-icon {
                font-size: 2.5rem;
            }
            
            .barcode-display {
                font-size: 1.2rem;
                letter-spacing: 2px;
            }
        }

        /* Animation for form elements */
        .form-group {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
            transform: translateY(10px);
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading animation */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        .form-text {
            font-size: 13px;
            margin-top: 5px;
        }

        /* ID Input Mode Toggle */
        .input-mode-toggle {
            display: flex;
            background: var(--light-bg);
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 15px;
        }

        .mode-btn {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .mode-btn.active {
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <div class="d-flex align-items-center justify-content-between mb-4">
                            <h3 class="text-primary mb-0">GACPMS</h3>
                            <h5 class="text-muted mb-0">Location</h5>
                        </div>
            </div>
        </div>
        
        <div class="card-body">
            <form id="logform" method="POST" novalidate autocomplete="on">
                <div id="alert-container" class="alert alert-danger d-none" role="alert"></div>
                
                <div class="form-group">
                    <label for="roomdpt" class="form-label"><i class="fas fa-building"></i>Department</label>
                    <select class="form-select" name="roomdpt" id="roomdpt" required autocomplete="organization">
                        <option value="Main" selected>Main</option>
                        <?php
                        $sql = "SELECT department_name FROM department WHERE department_name != 'Main'";
                        $result = $db->query($sql);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <option value="<?= htmlspecialchars($row['department_name']) ?>">
                            <?= htmlspecialchars($row['department_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="location" class="form-label"><i class="fas fa-map-marker-alt"></i>Location</label>
                    <select class="form-select" name="location" id="location" required autocomplete="organization-title">
                        <option value="Gate" selected>Gate</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label"><i class="fas fa-lock"></i>Password</label>
                    <div class="input-group password-field">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="Ppassword" required autocomplete="current-password">
                        <button class="password-toggle" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- ID Input Mode Toggle - REMOVED since we only want Scan Only -->
                
                <!-- Option 2: Scan Only -->
                <div class="form-group" id="scanInputGroup">
                    <label class="form-label"><i class="fas fa-barcode"></i>Scan ID Card</label>
                    
                    <!-- Scanner Box - This is where users click and scan -->
                    <div class="scanner-container" id="scannerBox">
                        <div class="scanner-icon">
                            <i class="fas fa-barcode"></i>
                        </div>
                        <div class="scanner-title" id="scannerTitle">
                            Click to Activate Scanner
                        </div>
                        <div class="scanner-instruction" id="scannerInstruction">
                            Click this box then scan your ID card
                        </div>
                        
                        <!-- Barcode Display Area -->
                        <div class="barcode-display" id="barcodeDisplay">
                            <span class="barcode-placeholder" id="barcodePlaceholder">Barcode will appear here after scanning</span>
                            <span id="barcodeValue" class="d-none"></span>
                        </div>
                    </div>

                    <div class="scan-indicator scan-animation" id="scanIndicator">
                        <i class="fas fa-rss me-2"></i>Scanner Ready - Click the box above to start scanning
                    </div>
                </div>
                
                <!-- Hidden field for scan mode -->
                <input type="text" class="hidden-field" id="scan-id-input" name="Pid_number" required>
                
                <!-- Gate access information -->
                <div id="gateAccessInfo" class="gate-access-info d-none">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong>Gate Access Mode:</strong> Security personnel only
                    </div>
                </div>
                
                <!-- Hidden fields for selected subject -->
                <input type="hidden" name="selected_subject" id="selected_subject" value="">
                <input type="hidden" name="selected_room" id="selected_room" value="">
                <input type="hidden" name="selected_time" id="selected_time" value="">
                
                <button type="submit" class="btn btn-primary mb-3" id="loginButton">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
                
                <div class="login-footer">
                    <a href="terms.php" class="terms-link">Terms and Conditions</a>
                    <div class="text-muted">© <?php echo date('Y'); ?></div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Subject Selection Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Your Subject for <span id="modalRoomName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Please select the subject you're currently teaching in this room and click "Confirm Selection".
                    </div>
                    <div class="table-responsive">
                        <table class="table subject-table" id="subjectTable">
                            <thead>
                                <tr>
                                    <th width="5%">Select</th>
                                    <th>Subject</th>
                                    <th>Year Level</th>
                                    <th>Section</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="subjectList">
                                <!-- Subjects will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelSubject">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSubject" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="confirmSpinner" role="status" aria-hidden="true"></span>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="admin/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    // Security: Prevent console access in production
    if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        console.log = function() {};
        console.warn = function() {};
        console.error = function() {};
    }

    // Scanner state management
    let isScannerActive = false;
    let scanBuffer = '';
    let scanTimeout;

    $(document).ready(function() {
        // Initialize scanner functionality
        initScanner();
        
        // Password visibility toggle
        $('#togglePassword').click(function() {
            const icon = $(this).find('i');
            const passwordField = $('#password');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Show/hide gate access info based on department selection
        $('#roomdpt, #location').change(function() {
            const department = $('#roomdpt').val();
            const location = $('#location').val();
            
            if (department === 'Main' && location === 'Gate') {
                $('#gateAccessInfo').removeClass('d-none');
            } else {
                $('#gateAccessInfo').addClass('d-none');
            }
        });

        // Initial check
        $('#roomdpt').trigger('change');

        // Form submission handler
        $('#logform').on('submit', function(e) {
            e.preventDefault();
            
            const idNumber = $('#scan-id-input').val();
            const password = $('#password').val();
            const department = $('#roomdpt').val();
            const selectedRoom = $('#location').val();
            
            // Validate ID format
            if (!/^\d{4}-\d{4}$/.test(idNumber)) {
                showAlert('Please scan a valid ID card (format: 0000-0000)');
                activateScanner();
                return;
            }
            
            if (!password) {
                showAlert('Please enter your password');
                $('#password').focus();
                return;
            }
            
            console.log('🔄 Proceeding with login logic...');
            
            // FIRST validate password for the room, THEN handle subject selection
            validateRoomPasswordBeforeSubject(department, selectedRoom, password, idNumber);
        });

        // NEW FUNCTION: Validate password BEFORE showing subject modal
        function validateRoomPasswordBeforeSubject(department, location, password, idNumber) {
            // Show loading state
            $('#loginButton').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validating...');
            $('#loginButton').prop('disabled', true);
            
            // Create a minimal form data for password validation
            const formData = {
                roomdpt: department,
                location: location,
                Ppassword: password,
                Pid_number: idNumber,
                validate_only: 'true' // Add a flag to indicate this is just password validation
            };
            
            $.ajax({
                url: '', // same PHP page
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#loginButton').html('<i class="fas fa-sign-in-alt me-2"></i>Login');
                    $('#loginButton').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        // Password is valid, now check if we need subject selection
                        if (department === 'Main' && location === 'Gate') {
                            // Gate access - submit directly
                            submitLoginForm();
                        } else if (!$('#selected_subject').val()) {
                            // Classroom access - show subject selection
                            showSubjectSelectionModal();
                        } else {
                            // Subject already selected - submit directly
                            submitLoginForm();
                        }
                    } else {
                        // Password validation failed
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: response.message || 'Invalid password or credentials'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state
                    $('#loginButton').html('<i class="fas fa-sign-in-alt me-2"></i>Login');
                    $('#loginButton').prop('disabled', false);
                    
                    let errorMessage = 'Password validation failed. Please try again.';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        errorMessage = xhr.responseText || errorMessage;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        }

        // Show subject selection modal
        function showSubjectSelectionModal() {
            const idNumber = $('#scan-id-input').val();
            const selectedRoom = $('#location').val();
            
            if (!idNumber || !selectedRoom) {
                showAlert('Please select a location first');
                return;
            }
            
            // Clear previous selections
            $('#selected_subject').val('');
            $('#selected_room').val('');
            $('#selected_time').val('');
            $('.subject-radio').prop('checked', false);
            $('#confirmSubject').prop('disabled', true);
            
            $('#subjectList').html(`
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading subjects...</span>
                        </div>
                        <div class="mt-2 text-muted">Loading subjects for ${selectedRoom}...</div>
                    </td>
                </tr>
            `);
            
            const subjectModal = new bootstrap.Modal(document.getElementById('subjectModal'));
            subjectModal.show();
            
            $('#modalRoomName').text(selectedRoom);
            loadInstructorSubjects(idNumber, selectedRoom);
        }

        // Load subjects for instructor with enhanced error handling
        function loadInstructorSubjects(idNumber, selectedRoom) {
            // Clean the ID number by removing hyphens
            const cleanId = idNumber.replace(/-/g, '');
            
            console.log('🔍 Loading subjects for:', {
                idNumber: idNumber,
                cleanId: cleanId,
                room: selectedRoom
            });
            
            $('#subjectList').html(`
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading subjects...</span>
                        </div>
                        <div class="mt-2 text-muted">Loading subjects for ${selectedRoom}...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: 'get_instructor_subjects.php',
                type: 'GET',
                data: { 
                    id_number: cleanId,
                    room_name: selectedRoom
                },
                dataType: 'text', // Change to text to see raw response first
                timeout: 15000,
                success: function(rawResponse) {
                    console.log('📨 Raw API Response:', rawResponse);
                    
                    let data;
                    try {
                        data = JSON.parse(rawResponse);
                        console.log('✅ Parsed JSON:', data);
                    } catch (e) {
                        console.error('❌ JSON Parse Error:', e);
                        console.error('Raw response that failed to parse:', rawResponse);
                        
                        $('#subjectList').html(`
                            <tr>
                                <td colspan="5" class="text-center text-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Server returned invalid JSON format
                                    <br><small class="text-muted">Check browser console for details</small>
                                    <br><small class="text-muted">Response: ${rawResponse.substring(0, 100)}...</small>
                                </td>
                            </tr>
                        `);
                        return;
                    }
                    
                    // Now handle the parsed JSON
                    if (data.status === 'success') {
                        if (data.data && data.data.length > 0) {
                            displaySubjects(data.data, selectedRoom);
                        } else {
                            $('#subjectList').html(`
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No scheduled subjects found for ${selectedRoom}
                                            ${data.debug_info ? `<br><small>Instructor: ${data.debug_info.instructor_name}</small>` : ''}
                                        </div>
                                    </td>
                                </tr>
                            `);
                        }
                    } else {
                        $('#subjectList').html(`
                            <tr>
                                <td colspan="5" class="text-center text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    ${data.message || 'Unknown error occurred'}
                                    ${data.debug ? `<br><small class="text-muted">Debug: ${JSON.stringify(data.debug)}</small>` : ''}
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('🚨 AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status,
                        readyState: xhr.readyState
                    });
                    
                    let errorMessage = 'Failed to load subjects. ';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out after 15 seconds.';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Server returned invalid data format.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'API endpoint not found.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server internal error.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Cannot connect to server. Check if server is running.';
                    } else {
                        errorMessage = `Network error: ${error}`;
                    }
                    
                    $('#subjectList').html(`
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${errorMessage}
                                <br><small class="text-muted">Status: ${xhr.status} - ${status}</small>
                                <br><small class="text-muted">Check browser console for details</small>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function showSubjectError(message) {
            $('#subjectList').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${message}
                        <br><small class="text-muted">Check browser console for details</small>
                    </td>
                </tr>
            `);
        }

        // Display subjects in the modal table - FIXED VERSION
        function displaySubjects(schedules, selectedRoom) {
            let html = '';
            const now = new Date();
            const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' });
            const currentTimeMinutes = now.getHours() * 60 + now.getMinutes();
            
            let hasAvailableSubjects = false;
            
            // Clear existing content and start building rows
            schedules.forEach(schedule => {
                const isToday = schedule.day === currentDay;
                
                // Parse subject start time into minutes
                let startMinutes = null;
                let endMinutes = null;
                
                if (schedule.start_time) {
                    const [hour, minute, second] = schedule.start_time.split(':');
                    startMinutes = parseInt(hour, 10) * 60 + parseInt(minute, 10);
                }
                
                if (schedule.end_time) {
                    const [hour, minute, second] = schedule.end_time.split(':');
                    endMinutes = parseInt(hour, 10) * 60 + parseInt(minute, 10);
                }
                
                // Determine if subject is available for selection
                const isEnabled = isToday && startMinutes !== null && 
                                 (currentTimeMinutes <= endMinutes);
                
                const startTimeFormatted = schedule.start_time ? 
                    new Date(`1970-01-01T${schedule.start_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                    'N/A';
                    
                const endTimeFormatted = schedule.end_time ? 
                    new Date(`1970-01-01T${schedule.end_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                    'N/A';
                
                // Determine row styling
                let rowClass = '';
                let statusBadge = '';
                
                if (!isToday) {
                    rowClass = 'table-secondary';
                    statusBadge = '<span class="badge bg-secondary ms-1">Not Today</span>';
                } else if (!isEnabled) {
                    rowClass = 'table-warning';
                    statusBadge = '<span class="badge bg-warning ms-1">Class Ended</span>';
                } else {
                    hasAvailableSubjects = true;
                    statusBadge = '<span class="badge bg-success ms-1">Available</span>';
                }
                
                html += `
                    <tr class="modal-subject-row ${rowClass}">
                        <td>
                            <input type="radio" class="form-check-input subject-radio" 
                                   name="selectedSubject"
                                   data-subject="${schedule.subject || ''}"
                                   data-room="${schedule.room_name || selectedRoom}"
                                   data-time="${startTimeFormatted} - ${endTimeFormatted}"
                                   data-year-level="${schedule.year_level || ''}"
                                   data-section="${schedule.section || ''}"
                                   ${!isEnabled ? 'disabled' : ''}>
                        </td>
                        <td>
                            ${schedule.subject || 'N/A'}
                            ${statusBadge}
                        </td>
                        <td>${schedule.year_level || 'N/A'}</td>
                        <td>${schedule.section || 'N/A'}</td>
                        <td>${schedule.day || 'N/A'}</td>
                        <td>${startTimeFormatted} - ${endTimeFormatted}</td>
                    </tr>`;
            });
            
            // If no available subjects but we have schedules, show message
            if (!hasAvailableSubjects && schedules.length > 0) {
                html = `
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                No available subjects at this time. Subjects are only available on their scheduled day.
                            </div>
                        </td>
                    </tr>
                ` + html;
            }
            
            // If no schedules at all
            if (schedules.length === 0) {
                html = `
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                No subjects found for this room.
                            </div>
                        </td>
                    </tr>
                `;
            }
            
            $('#subjectList').html(html);
        }

        // Handle subject selection with radio buttons (single selection)
        $(document).on('change', '.subject-radio', function() {
            if ($(this).is(':checked') && !$(this).is(':disabled')) {
                $('#selected_subject').val($(this).data('subject'));
                $('#selected_room').val($(this).data('room'));
                $('#selected_time').val($(this).data('time'));
                $('#confirmSubject').prop('disabled', false);
            }
        });

        // Confirm subject selection
        $('#confirmSubject').click(function() {
            const subject = $('#selected_subject').val();
            const room = $('#selected_room').val();
            
            if (!subject || !room) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Subject Selected',
                    text: 'Please select a subject first.'
                });
                return;
            }
            
            // Close modal and submit form
            $('#subjectModal').modal('hide');
            submitLoginForm();
        });

        // Cancel subject selection - go back to login form
        $('#cancelSubject').click(function() {
            $('#subjectModal').modal('hide');
            // Clear any selections
            $('#selected_subject').val('');
            $('#selected_room').val('');
            $('#selected_time').val('');
            $('.subject-radio').prop('checked', false);
        });

        // Handle modal hidden event
        $('#subjectModal').on('hidden.bs.modal', function() {
            // If no subject was selected, focus back on scanner
            if (!$('#selected_subject').val()) {
                activateScanner();
            }
        });

        // Submit login form to server
        function submitLoginForm() {
            const formData = $('#logform').serialize();
            
            Swal.fire({
                title: 'Logging in...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '', // same PHP page
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        // Store critical session data in localStorage as backup
                        localStorage.setItem('instructor_id', response.instructor_id || '');
                        localStorage.setItem('instructor_name', response.instructor_name || '');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: response.message || 'Redirecting...',
                            timer: 1500,
                            showConfirmButton: false,
                            willClose: () => {
                                window.location.href = response.redirect;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: response.message || 'Invalid credentials'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    
                    // Try to parse the response as text first to see what's coming back
                    let errorMessage = 'Login request failed. Please try again.';
                    
                    try {
                        // If it's JSON, parse it
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // If it's not JSON, show the raw response for debugging
                        errorMessage = xhr.responseText || errorMessage;
                        if (errorMessage.length > 100) {
                            errorMessage = errorMessage.substring(0, 100) + '...';
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                    
                    console.error('Login error:', xhr.responseText);
                }
            });
        }

        // Utility alert (used earlier)
        function showAlert(message) {
            $('#alert-container').removeClass('d-none').text(message);
        }

        // Fetch rooms when department changes
        $('#roomdpt').change(function() {
            const department = $(this).val();
            if (department === "Main") {
                $('#location').html('<option value="Gate" selected>Gate</option>');
                return;
            }
            
            $.get('get_rooms.php', { department: department })
                .done(function(data) {
                    $('#location').html(data);
                })
                .fail(function() {
                    $('#location').html('<option value="">Error loading rooms</option>');
                });
        });

        // Initial focus - activate scanner by default
        setTimeout(function() {
            activateScanner();
        }, 300);
    });

    // =====================================================================
    // SCANNER FUNCTIONALITY - FIXED VERSION
    // =====================================================================
    function initScanner() {
        const scannerBox = document.getElementById('scannerBox');
        const scanIndicator = document.getElementById('scanIndicator');

        // Click on scanner box to activate
        scannerBox.addEventListener('click', function() {
            if (!isScannerActive) {
                activateScanner();
            }
        });

        // Listen for key events on the entire document when scanner is active
        document.addEventListener('keydown', handleKeyPress);
    }

    // Activate scanner
    function activateScanner() {
        isScannerActive = true;
        const scannerBox = document.getElementById('scannerBox');
        const scannerTitle = document.getElementById('scannerTitle');
        const scannerInstruction = document.getElementById('scannerInstruction');
        const scanIndicator = document.getElementById('scanIndicator');
        const scannerIcon = scannerBox.querySelector('.scanner-icon i');

        // Update UI for active scanning
        scannerBox.classList.add('scanning');
        scannerBox.classList.remove('scanned');
        scannerTitle.textContent = 'Scanner Active - Scan Now';
        scannerInstruction.textContent = 'Point your barcode scanner and scan the ID card';
        scanIndicator.innerHTML = '<i class="fas fa-barcode me-2"></i>Scanner Active - Ready to receive scan';
        scanIndicator.style.color = 'var(--accent-color)';
        scannerIcon.className = 'fas fa-barcode';

        // Clear any previous scan
        scanBuffer = '';
        clearTimeout(scanTimeout);

        console.log('Scanner activated - ready to scan');
    }

    // Deactivate scanner
    function deactivateScanner() {
        isScannerActive = false;
        const scannerBox = document.getElementById('scannerBox');
        const scanIndicator = document.getElementById('scanIndicator');

        scannerBox.classList.remove('scanning');
        scanIndicator.innerHTML = '<i class="fas fa-rss me-2"></i>Scanner Ready - Click the box to scan again';
        scanIndicator.style.color = 'var(--accent-color)';

        console.log('Scanner deactivated');
    }

    // Handle key presses for scanner input - FIXED VERSION
    function handleKeyPress(e) {
        // Only process scanner input if scanner is active AND we're not in a form field
        if (!isScannerActive || isTypingInFormField(e)) {
            return;
        }

        // Clear buffer if it's been too long between keystrokes
        clearTimeout(scanTimeout);

        // If Enter key is pressed, process the scan
        if (e.key === 'Enter') {
            e.preventDefault();
            processScan(scanBuffer);
            scanBuffer = '';
            return;
        }

        // Add character to buffer (ignore modifier keys and special keys)
        if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            e.preventDefault();
            scanBuffer += e.key;
            console.log('Scanner input:', e.key, 'Buffer:', scanBuffer);
        }

        // Set timeout to clear buffer if no activity
        scanTimeout = setTimeout(() => {
            console.log('Scanner buffer cleared due to inactivity');
            scanBuffer = '';
        }, 200);
    }

    // Check if user is typing in a form field (password field, etc.)
    function isTypingInFormField(e) {
        const activeElement = document.activeElement;
        const formFields = ['INPUT', 'TEXTAREA', 'SELECT'];
        
        if (formFields.includes(activeElement.tagName)) {
            // Allow typing in password field and other form fields
            return true;
        }
        
        return false;
    }

    // Function to format ID number as 0000-0000
    function formatIdNumber(id) {
        // Remove any non-digit characters
        const cleaned = id.replace(/\D/g, '');
        
        // Format as 0000-0000 if we have 8 digits
        if (cleaned.length === 8) {
            return cleaned.substring(0, 4) + '-' + cleaned.substring(4, 8);
        }
        
        // Return original if not 8 digits
        return cleaned;
    }

    // Process the scanned data
    function processScan(data) {
        if (data.trim().length > 0) {
            // Format the scanned data as 0000-0000
            const formattedValue = formatIdNumber(data.trim());
            
            console.log('Raw scan data:', data);
            console.log('Formatted ID:', formattedValue);
            
            // Update the hidden input field
            $('#scan-id-input').val(formattedValue);
            
            // Update barcode display
            updateBarcodeDisplay(formattedValue);
            
            // Update scanner UI
            const scannerBox = document.getElementById('scannerBox');
            const scannerTitle = document.getElementById('scannerTitle');
            const scannerInstruction = document.getElementById('scannerInstruction');
            const scanIndicator = document.getElementById('scanIndicator');
            
            scannerBox.classList.remove('scanning');
            scannerBox.classList.add('scanned');
            scannerTitle.textContent = 'ID Scanned Successfully!';
            scannerInstruction.textContent = 'ID: ' + formattedValue;
            scanIndicator.innerHTML = '<i class="fas fa-check-circle me-2"></i>Barcode scanned successfully!';
            scanIndicator.style.color = 'var(--success-color)';
            
            // Auto-submit the form after a short delay
            setTimeout(() => {
                console.log('Auto-validating scanned ID:', formattedValue);
                // Trigger form validation
                $('#logform').trigger('submit');
            }, 1000);
            
            // Deactivate scanner after successful scan
            setTimeout(deactivateScanner, 2000);
        }
    }

    // Update barcode display
    function updateBarcodeDisplay(value) {
        const barcodeDisplay = document.getElementById('barcodeDisplay');
        const barcodePlaceholder = document.getElementById('barcodePlaceholder');
        const barcodeValue = document.getElementById('barcodeValue');
        
        // Hide placeholder and show actual value
        barcodePlaceholder.classList.add('d-none');
        barcodeValue.textContent = value;
        barcodeValue.classList.remove('d-none');
        barcodeValue.classList.add('barcode-value');
        
        // Add visual feedback
        barcodeDisplay.classList.add('barcode-value');
        
        // Remove highlight animation after it completes
        setTimeout(() => {
            barcodeDisplay.classList.remove('barcode-value');
        }, 1000);
    }

    // Reset scanner UI to initial state
    function resetScannerUI() {
        const scannerBox = document.getElementById('scannerBox');
        const scannerTitle = document.getElementById('scannerTitle');
        const scannerInstruction = document.getElementById('scannerInstruction');
        const scanIndicator = document.getElementById('scanIndicator');
        const barcodePlaceholder = document.getElementById('barcodePlaceholder');
        const barcodeValue = document.getElementById('barcodeValue');
        
        scannerBox.classList.remove('scanning', 'scanned');
        scannerTitle.textContent = 'Click to Activate Scanner';
        scannerInstruction.textContent = 'Click this box then scan your ID card';
        scanIndicator.innerHTML = '<i class="fas fa-rss me-2"></i>Scanner Ready - Click the box above to start scanning';
        scanIndicator.style.color = 'var(--accent-color)';
        barcodePlaceholder.classList.remove('d-none');
        barcodeValue.classList.add('d-none');
        barcodeValue.textContent = '';
        
        // Deactivate scanner
        deactivateScanner();
    }
    </script>
</body>
</html>