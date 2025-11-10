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

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6Ld2w-QrAAAAAKcWH94dgQumTQ6nQ3EiyQKHUw4_');
define('RECAPTCHA_SECRET_KEY', '6Ld2w-QrAAAAAFeIvhKm5V6YBpIsiyHIyzHxeqm-');
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');

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
// RECAPTCHA VALIDATION FUNCTION
// =====================================================================
function validateRecaptcha($recaptchaResponse) {
    if (empty($recaptchaResponse)) {
        return ['success' => false, 'error' => 'reCAPTCHA verification failed'];
    }
    
    $postData = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents(RECAPTCHA_VERIFY_URL, false, $context);
    $result = json_decode($response, true);
    
    return $result;
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
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // Validate reCAPTCHA first
    $recaptchaResult = validateRecaptcha($recaptcha_response);
    if (!$recaptchaResult['success']) {
        http_response_code(400);
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error', 
            'message' => 'reCAPTCHA verification failed. Please try again.'
        ]));
    }

    // Optional: Check reCAPTCHA score (0.5 is typical threshold)
    $score = $recaptchaResult['score'] ?? 0;
    if ($score < 0.5) {
        http_response_code(400);
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error', 
            'message' => 'Suspicious activity detected. Please try again.'
        ]));
    }

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
    echo json_encode([
        'status' => 'success',
        'redirect' => $redirectUrl,
        'message' => 'Login successful',
        'user_type' => $userType,
        'instructor_id' => $userData['id'],
        'instructor_name' => $userData['fullname']
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
    
    <!-- CORRECTED Content Security Policy - Added reCAPTCHA domains -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; 
    script-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://fonts.googleapis.com https://www.google.com https://www.gstatic.com 'unsafe-inline' 'unsafe-eval'; 
    style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://www.google.com 'unsafe-inline'; 
    font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com; 
    img-src 'self' data: https:; 
    connect-src 'self' https://www.google.com; 
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
    
    <!-- reCAPTCHA API -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
    
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e4652aff;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Header - Fixed height and fully visible */
        .header-container {
            background: transparent;
            padding: 0;
            margin: 0;
            height: 150px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-image {
            max-width: 150%;
            max-height: 150%;
            object-fit: contain;
            display: block;
        }

        /* Main Container - Allow scrolling */
        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 10px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* Navigation Tabs */
        .modern-tabs {
            background: var(--accent-color);
            border-radius: 8px;
            padding: 4px;
            margin: 10px;
            flex-shrink: 0;
        }

        .modern-tabs .nav-link {
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            color: var(--dark-text);
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .modern-tabs .nav-link.active {
            background: var(--icon-color);
            color: white;
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
        }

        /* Content Area - Allow scrolling */
        .content-area {
            flex: 1;
            display: flex;
            padding: 0 10px 10px 10px;
            gap: 10px;
            overflow: hidden;
            min-height: 0;
        }

        /* Scanner Section - Allow scrolling if needed */
        .scanner-section {
            flex: 7;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
        }

        /* Department/Location Info */
        .dept-location-info {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .dept-location-info h3 {
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-text);
        }

        /* Clock Display */
        .clock-display {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            flex-shrink: 0;
        }

        #clock {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        #currentDate {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* Scanner Alert */
        .scanner-alert {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            flex-shrink: 0;
        }

        .scanner-alert h4 {
            font-size: 0.9rem;
            margin: 0;
        }

        /* Scanner Container - Fixed height, no scrolling */
        .scanner-container {
            flex: 1;
            position: relative;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-height: 150px;
            flex-shrink: 0;
        }

        #largeReader {
            width: 100%;
            height: 100%;
        }

        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .scanner-frame {
            border: 3px solid #FBC257;
            width: 70%;
            height: 100px;
            position: relative;
            border-radius: 6px;
        }

        .scanner-laser {
            position: absolute;
            width: 100%;
            height: 3px;
            background: #FBC257;
            top: 0;
            animation: scan 2s infinite;
            box-shadow: 0 0 10px #FBC257;
        }

        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }

        /* Result Display */
        #result {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            flex-shrink: 0;
        }

        /* Sidebar Section - Allow scrolling if needed */
        .sidebar-section {
            flex: 3;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
        }

        /* Person Photo */
        .person-photo {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--icon-color);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.2);
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        /* Manual Input Section */
        .manual-input-section {
            background: white;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .manual-input-section h4 {
            color: var(--icon-color);
            margin-bottom: 8px;
            font-weight: 600;
            text-align: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .input-group {
            margin-bottom: 8px;
            flex-shrink: 0;
        }

        #manualIdInput {
            border: 2px solid var(--accent-color);
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 0.85rem;
            transition: var(--transition);
            height: 40px;
        }

        #manualIdInput:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.1);
        }

        #manualSubmitBtn {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            height: 40px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
            font-size: 0.85rem;
        }

        #manualSubmitBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(92, 149, 233, 0.4);
        }

        /* Confirmation Modal */
        .confirmation-modal .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .confirmation-modal .modal-header {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border-bottom: none;
            padding: 12px 15px;
        }

        .confirmation-modal .modal-body {
            padding: 20px;
            text-align: center;
        }

        .modal-person-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--icon-color);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
            margin-bottom: 10px;
        }

        .person-info {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .access-status {
            font-size: 1rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 12px 0;
        }

        .time-in {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .time-out {
            background: linear-gradient(135deg, #f72585, #7209b7);
            color: white;
        }

        .access-denied {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
        }

        .time-display {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }

        .confirmation-modal .modal-footer {
            border-top: none;
            padding: 12px 15px;
            justify-content: center;
        }

        .confirmation-modal .btn {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 6px;
            padding: 6px 20px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .header-container {
                height: 100px;
            }
            
            .content-area {
                flex-direction: column;
                padding: 0 8px 8px 8px;
                gap: 8px;
            }
            
            .scanner-section,
            .sidebar-section {
                min-height: 250px;
            }
            
            .person-photo {
                height: 120px;
            }
            
            #clock {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                height: 80px;
            }
            
            .main-container {
                margin: 8px;
            }
            
            .modern-tabs {
                margin: 8px;
            }
            
            .modern-tabs .nav-link {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .dept-location-info h3 {
                font-size: 0.8rem;
            }
            
            .clock-display {
                padding: 8px;
            }
            
            #clock {
                font-size: 1.1rem;
            }
            
            .scanner-alert {
                padding: 8px;
            }
            
            .scanner-alert h4 {
                font-size: 0.8rem;
            }
            
            .scanner-frame {
                width: 85%;
                height: 80px;
            }
        }

        @media (max-width: 576px) {
            .header-container {
                height: 70px;
            }
            
            .main-container {
                margin: 5px;
            }
            
            .modern-tabs {
                margin: 5px;
            }
            
            .content-area {
                padding: 0 5px 5px 5px;
                gap: 5px;
            }
            
            .scanner-section,
            .sidebar-section {
                padding: 10px;
            }
            
            .manual-input-section {
                padding: 8px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            #manualSubmitBtn {
                margin-top: 5px;
            }
            
            .modal-person-photo {
                width: 60px;
                height: 60px;
            }
        }

        /* Utility classes */
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }

        @keyframes blink-animation {
            to { visibility: hidden; }
        }

        .loading-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--icon-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Alert variations matching your color scheme */
        .alert-success {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
            border: none;
            border-radius: 6px;
        }

        .alert-warning {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            color: white;
            border: none;
            border-radius: 6px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
            border: none;
            border-radius: 6px;
        }

        /* Custom scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #4a7fe0;
        }

        /* Enhanced modal styles for gate system */
        .confirmation-modal .person-photo-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 20px;
            border-radius: 50%;
            padding: 5px;
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            box-shadow: 0 5px 15px rgba(92, 149, 233, 0.3);
        }

        .confirmation-modal .person-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .confirmation-modal .person-info-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--icon-color);
        }

        .confirmation-modal .person-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--icon-color);
            margin-bottom: 10px;
        }

        .confirmation-modal .person-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: left;
        }

        .confirmation-modal .detail-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .confirmation-modal .detail-label {
            font-weight: bold;
            color: #495057;
            font-size: 0.9rem;
        }

        .confirmation-modal .detail-value {
            color: var(--icon-color);
            font-weight: 600;
        }

        .visitor-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        /* Visitor Modal Styles - Adjusted Container */
.visitor-modal .modal-content {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.visitor-modal .modal-header {
    background: linear-gradient(135deg, var(--icon-color), #4361ee);
    color: white;
    border-bottom: none;
    padding: 15px 20px;
}

.visitor-modal .modal-header .btn-close {
    filter: invert(1);
}

.visitor-modal .modal-body {
    padding: 20px;
    background: var(--light-bg);
}

.visitor-modal .modal-footer {
    border-top: none;
    padding: 15px 20px;
    background: white;
}

.visitor-modal .form-label {
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 8px;
    font-size: 0.9rem;
    text-align: left;
    display: block;
}

.visitor-modal .form-control,
.visitor-modal .form-select {
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 0.9rem;
    transition: var(--transition);
    background: white;
    width: 100%;
}

.visitor-modal .form-control:focus,
.visitor-modal .form-select:focus {
    border-color: var(--icon-color);
    box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
    background: white;
}

.visitor-modal .form-control.is-invalid {
    border-color: #e74a3b;
    box-shadow: 0 0 0 3px rgba(231, 74, 59, 0.15);
}

.visitor-modal .invalid-feedback {
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 5px;
    text-align: left;
}

.visitor-modal .btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #f4a261);
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    color: white;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(246, 194, 62, 0.3);
}

.visitor-modal .btn-warning:hover {
    background: linear-gradient(135deg, #e0a800, #dc6502);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
}

.visitor-modal .btn-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    transition: var(--transition);
}

.visitor-modal .btn-secondary:hover {
    background: linear-gradient(135deg, #5a6268, #3d4348);
    transform: translateY(-2px);
}

.visitor-info-alert {
    background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
    border: none;
    border-radius: 8px;
    color: var(--dark-text);
    padding: 12px 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.visitor-info-alert i {
    color: var(--icon-color);
}

.character-count {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
    margin-top: 5px;
}

.character-count.warning {
    color: #e74a3b;
    font-weight: 600;
}

/* Make modal more compact */
.visitor-modal .modal-dialog {
    max-width: 500px;
}

.visitor-modal .col-12 {
    padding: 0;
}

/* Adjust spacing for compact layout */
.visitor-modal .mb-3 {
    margin-bottom: 1rem !important;
}

.visitor-modal .modal-body {
    padding: 20px;
}

        /* reCAPTCHA badge styling */
        .grecaptcha-badge {
            visibility: hidden;
        }

        /* Optional: Custom recaptcha info message */
        .recaptcha-info {
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            margin-top: 10px;
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
                
                <!-- Hidden reCAPTCHA token field -->
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                
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
                
                <!-- reCAPTCHA info -->
                <div class="recaptcha-info">
                    <i class="fas fa-shield-alt me-1"></i>
                    Protected by reCAPTCHA
                </div>
                
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
    // reCAPTCHA site key
    const RECAPTCHA_SITE_KEY = '<?php echo RECAPTCHA_SITE_KEY; ?>';
    
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

        // Form submission handler with reCAPTCHA
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
            
            console.log('🔄 Getting reCAPTCHA token...');
            
            // Get reCAPTCHA token first
            grecaptcha.ready(function() {
                grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'login'}).then(function(token) {
                    // Set the token in the hidden field
                    $('#g-recaptcha-response').val(token);
                    
                    console.log('✅ reCAPTCHA token received, proceeding with login...');
                    
                    // Now validate password and proceed
                    validateRoomPasswordBeforeSubject(department, selectedRoom, password, idNumber);
                }).catch(function(error) {
                    console.error('❌ reCAPTCHA error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Security Check Failed',
                        text: 'Unable to verify reCAPTCHA. Please refresh the page and try again.'
                    });
                });
            });
        });

        // Validate password BEFORE showing subject modal
        function validateRoomPasswordBeforeSubject(department, location, password, idNumber) {
            // Show loading state
            $('#loginButton').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validating...');
            $('#loginButton').prop('disabled', true);
            
            // Create form data with reCAPTCHA token
            const formData = {
                roomdpt: department,
                location: location,
                Ppassword: password,
                Pid_number: idNumber,
                'g-recaptcha-response': $('#g-recaptcha-response').val(),
                validate_only: 'true'
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
                dataType: 'text',
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

        // Display subjects in the modal table
        function displaySubjects(schedules, selectedRoom) {
            let html = '';
            const now = new Date();
            const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' });
            const currentTimeMinutes = now.getHours() * 60 + now.getMinutes();
            
            let hasAvailableSubjects = false;
            
            schedules.forEach(schedule => {
                const isToday = schedule.day === currentDay;
                
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
                
                const isEnabled = isToday && startMinutes !== null && 
                                 (currentTimeMinutes <= endMinutes);
                
                const startTimeFormatted = schedule.start_time ? 
                    new Date(`1970-01-01T${schedule.start_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                    'N/A';
                    
                const endTimeFormatted = schedule.end_time ? 
                    new Date(`1970-01-01T${schedule.end_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                    'N/A';
                
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

        // Handle subject selection with radio buttons
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

        // Cancel subject selection
        $('#cancelSubject').click(function() {
            $('#subjectModal').modal('hide');
            $('#selected_subject').val('');
            $('#selected_room').val('');
            $('#selected_time').val('');
            $('.subject-radio').prop('checked', false);
        });

        // Handle modal hidden event
        $('#subjectModal').on('hidden.bs.modal', function() {
            if (!$('#selected_subject').val()) {
                activateScanner();
            }
        });

        // Submit login form to server with reCAPTCHA
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
                    
                    let errorMessage = 'Login request failed. Please try again.';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
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

        // Utility alert
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
    // SCANNER FUNCTIONALITY
    // =====================================================================
    function initScanner() {
        const scannerBox = document.getElementById('scannerBox');
        const scanIndicator = document.getElementById('scanIndicator');

        scannerBox.addEventListener('click', function() {
            if (!isScannerActive) {
                activateScanner();
            }
        });

        document.addEventListener('keydown', handleKeyPress);
    }

    function activateScanner() {
        isScannerActive = true;
        const scannerBox = document.getElementById('scannerBox');
        const scannerTitle = document.getElementById('scannerTitle');
        const scannerInstruction = document.getElementById('scannerInstruction');
        const scanIndicator = document.getElementById('scanIndicator');
        const scannerIcon = scannerBox.querySelector('.scanner-icon i');

        scannerBox.classList.add('scanning');
        scannerBox.classList.remove('scanned');
        scannerTitle.textContent = 'Scanner Active - Scan Now';
        scannerInstruction.textContent = 'Point your barcode scanner and scan the ID card';
        scanIndicator.innerHTML = '<i class="fas fa-barcode me-2"></i>Scanner Active - Ready to receive scan';
        scanIndicator.style.color = 'var(--accent-color)';
        scannerIcon.className = 'fas fa-barcode';

        scanBuffer = '';
        clearTimeout(scanTimeout);

        console.log('Scanner activated - ready to scan');
    }

    function deactivateScanner() {
        isScannerActive = false;
        const scannerBox = document.getElementById('scannerBox');
        const scanIndicator = document.getElementById('scanIndicator');

        scannerBox.classList.remove('scanning');
        scanIndicator.innerHTML = '<i class="fas fa-rss me-2"></i>Scanner Ready - Click the box to scan again';
        scanIndicator.style.color = 'var(--accent-color)';

        console.log('Scanner deactivated');
    }

    function handleKeyPress(e) {
        if (!isScannerActive || isTypingInFormField(e)) {
            return;
        }

        clearTimeout(scanTimeout);

        if (e.key === 'Enter') {
            e.preventDefault();
            processScan(scanBuffer);
            scanBuffer = '';
            return;
        }

        if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            e.preventDefault();
            scanBuffer += e.key;
            console.log('Scanner input:', e.key, 'Buffer:', scanBuffer);
        }

        scanTimeout = setTimeout(() => {
            console.log('Scanner buffer cleared due to inactivity');
            scanBuffer = '';
        }, 200);
    }

    function isTypingInFormField(e) {
        const activeElement = document.activeElement;
        const formFields = ['INPUT', 'TEXTAREA', 'SELECT'];
        
        if (formFields.includes(activeElement.tagName)) {
            return true;
        }
        
        return false;
    }

    function formatIdNumber(id) {
        const cleaned = id.replace(/\D/g, '');
        
        if (cleaned.length === 8) {
            return cleaned.substring(0, 4) + '-' + cleaned.substring(4, 8);
        }
        
        return cleaned;
    }

    function processScan(data) {
        if (data.trim().length > 0) {
            const formattedValue = formatIdNumber(data.trim());
            
            console.log('Raw scan data:', data);
            console.log('Formatted ID:', formattedValue);
            
            $('#scan-id-input').val(formattedValue);
            
            updateBarcodeDisplay(formattedValue);
            
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
            
            setTimeout(() => {
                console.log('Auto-validating scanned ID:', formattedValue);
                $('#logform').trigger('submit');
            }, 1000);
            
            setTimeout(deactivateScanner, 2000);
        }
    }

    function updateBarcodeDisplay(value) {
        const barcodeDisplay = document.getElementById('barcodeDisplay');
        const barcodePlaceholder = document.getElementById('barcodePlaceholder');
        const barcodeValue = document.getElementById('barcodeValue');
        
        barcodePlaceholder.classList.add('d-none');
        barcodeValue.textContent = value;
        barcodeValue.classList.remove('d-none');
        barcodeValue.classList.add('barcode-value');
        
        barcodeDisplay.classList.add('barcode-value');
        
        setTimeout(() => {
            barcodeDisplay.classList.remove('barcode-value');
        }, 1000);
    }

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
        
        deactivateScanner();
    }
    </script>
</body>
</html>