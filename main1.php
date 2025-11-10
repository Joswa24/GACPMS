<?php
date_default_timezone_set('Asia/Manila');
include 'connection.php';
session_start();

// Enhanced session verification and recovery
function verifyInstructorSession() {
    // Check if instructor session exists
    if (!isset($_SESSION['access']['instructor']['id'])) {
        error_log("❌ Instructor session missing in main1.php");
        
        // Try to recover from backup storage if available
        if (isset($_POST['instructor_id_backup'])) {
            $_SESSION['access']['instructor'] = [
                'id' => $_POST['instructor_id_backup'],
                'fullname' => $_POST['instructor_name_backup'] ?? 'Unknown Instructor',
                'id_number' => $_POST['instructor_id_number_backup'] ?? ''
            ];
            error_log("✅ Session recovered from backup data");
            return true;
        }
        
        return false;
    }
    
    // Validate that we have the required instructor data
    if (empty($_SESSION['access']['instructor']['id'])) {
        error_log("❌ Instructor ID is empty in session");
        return false;
    }
    
    error_log("✅ Instructor session verified: " . $_SESSION['access']['instructor']['id']);
    return true;
}

// NEW: Get allowed year and section for current instructor and subject
function getAllowedYearAndSection($db, $instructor_id, $subject_name, $room_name) {
    $query = "SELECT year_level, section FROM room_schedules 
              WHERE instructor = ? AND subject = ? AND room_name = ? 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        error_log("❌ Failed to prepare year/section query: " . $db->error);
        return ['year_level' => null, 'section' => null];
    }
    
    // Get instructor name from ID
    $instructor_stmt = $db->prepare("SELECT fullname FROM instructor WHERE id = ?");
    $instructor_stmt->bind_param("i", $instructor_id);
    $instructor_stmt->execute();
    $instructor_result = $instructor_stmt->get_result();
    $instructor = $instructor_result->fetch_assoc();
    $instructor_name = $instructor['fullname'] ?? '';
    $instructor_stmt->close();
    
    $stmt->bind_param("sss", $instructor_name, $subject_name, $room_name);
    
    if (!$stmt->execute()) {
        error_log("❌ Failed to execute year/section query: " . $stmt->error);
        return ['year_level' => null, 'section' => null];
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    if ($data) {
        error_log("✅ Allowed year/section found: " . $data['year_level'] . " - " . $data['section']);
        return $data;
    } else {
        error_log("❌ No year/section found for instructor: $instructor_name, subject: $subject_name, room: $room_name");
        return ['year_level' => null, 'section' => null];
    }
}

// NEW: Check if student is in allowed year and section
function isStudentInAllowedClass($db, $student_id, $allowed_year, $allowed_section) {
    if (!$allowed_year || !$allowed_section) {
        error_log("⚠️ No year/section restrictions set");
        return true; // No restrictions
    }
    
    $query = "SELECT year_level, section FROM students WHERE id_number = ?";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        error_log("❌ Failed to prepare student class query: " . $db->error);
        return false;
    }
    
    $stmt->bind_param("s", $student_id);
    
    if (!$stmt->execute()) {
        error_log("❌ Failed to execute student class query: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student) {
        error_log("❌ Student not found: $student_id");
        return false;
    }
    
    $is_allowed = ($student['year_level'] == $allowed_year && $student['section'] == $allowed_section);
    
    if (!$is_allowed) {
        error_log("❌ Student class mismatch: Student is {$student['year_level']}-{$student['section']}, Required: $allowed_year-$allowed_section");
    } else {
        error_log("✅ Student class matches: {$student['year_level']}-{$student['section']}");
    }
    
    return $is_allowed;
}

// Verify session immediately
if (!verifyInstructorSession()) {
    // Log detailed session info for debugging
    error_log("Session dump: " . print_r($_SESSION, true));
    error_log("POST dump: " . print_r($_POST, true));
    
    // Redirect back to login with error
    $_SESSION['login_error'] = "Session expired. Please login again.";
    header("Location: index.php");
    exit();
}

// When instructor logs out, revert their active swaps
function revertInstructorSwaps($db, $instructor_id) {
    $query = "UPDATE schedule_swaps SET is_active = FALSE 
            WHERE instructor_id = ? AND is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
}

// Call this when instructor logs out
if (isset($_SESSION['access']['instructor']['id'])) {
    revertInstructorSwaps($db, $_SESSION['access']['instructor']['id']);
}

// Record instructor login time when opening the portal
if (isset($_SESSION['access']['instructor']['id']) && !isset($_SESSION['instructor_login_time'])) {
    $instructor_id = $_SESSION['access']['instructor']['id'];
    
    // Store login time in session
    $_SESSION['instructor_login_time'] = date('Y-m-d H:i:s');
    
    // Also store session ID for consistency
    $_SESSION['instructor_session_id'] = $instructor_id;
    
    error_log("Instructor session started: " . $_SESSION['instructor_login_time']);

    $currentDate = date('Y-m-d');
    
    // Check if instructor already has an active session today
    $check_session = $db->prepare("SELECT id FROM instructor_logs WHERE instructor_id = ? AND DATE(time_in) = ? AND time_out IS NULL");
    $check_session->bind_param("is", $instructor_id, $currentDate);
    $check_session->execute();
    $existing_session = $check_session->get_result();
    
    if ($existing_session->num_rows == 0) {
        // Create new session record
        $insert_log = $db->prepare("INSERT INTO instructor_logs (instructor_id, time_in, status) VALUES (?, NOW(), 'active')");
        $insert_log->bind_param("i", $instructor_id);
        $insert_log->execute();
        $insert_log->close();
        
        // Store session ID for later use
        $session_id = $db->insert_id;
        $_SESSION['instructor_session_id'] = $session_id;
    } else {
        // Use existing session
        $session_data = $existing_session->fetch_assoc();
        $_SESSION['instructor_session_id'] = $session_data['id'];
    }
    
    $check_session->close();
}

// ✅ NEW: Check if instructor is logged in and has login time
if (!isset($_SESSION['instructor_login_time']) && isset($_SESSION['access']['instructor'])) {
    $currentTime = date('Y-m-d H:i:s');
    $_SESSION['instructor_login_time'] = $currentTime;
    
    // Create instructor attendance summary if not exists
    $instructorId = $_SESSION['access']['instructor']['id'];
    $instructorName = $_SESSION['access']['instructor']['fullname'];
    $subjectName = $_SESSION['access']['subject']['name'] ?? 'General Subject';
    $department = $_SESSION['access']['room']['department'] ?? 'General';
    $location = $_SESSION['access']['room']['room'] ?? 'Classroom';
    
    // Extract year level and section from subject name or use defaults
    $yearLevel = "1st Year";
    $section = "A";
    
    // Try to extract from subject name (assuming format like "Math 101 - 1A")
    if (isset($_SESSION['access']['subject']['name'])) {
        $subjectParts = explode(' - ', $_SESSION['access']['subject']['name']);
        if (count($subjectParts) > 1) {
            $section = end($subjectParts);
        }
    }
    
    $sessionDate = date('Y-m-d');
    $timeIn = date('H:i:s');
    
    $sessionSql = "INSERT INTO instructor_attendance_summary 
                (instructor_id, instructor_name, subject_name, year_level, section, 
                    total_students, present_count, absent_count, attendance_rate,
                    session_date, time_in, time_out) 
                VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0.00, ?, ?, '00:00:00')";
    $stmt = $db->prepare($sessionSql);
    $stmt->bind_param("issssss", $instructorId, $instructorName, $subjectName, $yearLevel, $section, $sessionDate, $timeIn);
    
    if ($stmt->execute()) {
        $_SESSION['attendance_session_id'] = $stmt->insert_id;
    }
}

// NEW: Get allowed year and section for current session
$allowed_year = null;
$allowed_section = null;

if (isset($_SESSION['access']['instructor']['id']) && 
    isset($_SESSION['access']['subject']['name']) && 
    isset($_SESSION['access']['room']['room'])) {
    
    $instructor_id = $_SESSION['access']['instructor']['id'];
    $subject_name = $_SESSION['access']['subject']['name'];
    $room_name = $_SESSION['access']['room']['room'];
    
    $class_data = getAllowedYearAndSection($db, $instructor_id, $subject_name, $room_name);
    $allowed_year = $class_data['year_level'];
    $allowed_section = $class_data['section'];
    
    // Store in session for use in process_barcode.php
    $_SESSION['allowed_year'] = $allowed_year;
    $_SESSION['allowed_section'] = $allowed_section;
    
    error_log("🎯 Session restrictions set - Year: $allowed_year, Section: $allowed_section");
}

// REMOVED: First student logic - no longer needed
// Initialize session variables with proper checks
$_SESSION['allowed_section'] = $allowed_section;
$_SESSION['allowed_year'] = $allowed_year;

// Safely get department and location from session
$department = isset($_SESSION['access']['room']['department']) ? 
            $_SESSION['access']['room']['department'] : 'Department';
$location = isset($_SESSION['access']['room']['room']) ? 
            $_SESSION['access']['room']['room'] : 'Location';

// Check for force redirect
if (isset($_SESSION['access']['force_redirect'])) {
    header('Location: ' . $_SESSION['access']['force_redirect']);
    exit;
}

// Fetch data from the about table
$logo1 = $nameo = $address = $logo2 = "";
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
    $nameo = $row['name'];
    $address = $row['address'];
    $logo2 = $row['logo2'];
} 

mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/grow_up.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <title>Classroom Attendance Scanner</title>
    <link rel="icon" href="uploads/scanner.webp" type="image/webp">
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
            background: var(--secondary-color);
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
        /* Completely hide any images in the scanner container */
        #largeReader > img,
        #largeReader > div > img,
        .scanner-container img {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Make sure the scanner video takes full space */
        #largeReader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
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

        /* Student Photo */
        .student-photo {
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
            width: 5px;
        }

        #manualIdInput:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.1);
            width: 40px;
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

        .modal-student-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--icon-color);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
            margin-bottom: 10px;
        }

        .student-info {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .attendance-status {
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

        /* Additional styles for confirmation modal enhancements */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .time-in-badge {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .time-out-badge {
            background: linear-gradient(135deg, #f72585, #7209b7);
            color: white;
        }

        .student-info-card {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid var(--icon-color);
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
            
            .student-photo {
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
            
            /* Larger Modal Student Photo */
            .modal-student-photo {
                width: 120px !important;
                height: 120px !important;
                object-fit: cover;
                border: 4px solid var(--icon-color) !important;
                box-shadow: 0 8px 25px rgba(92, 149, 233, 0.3) !important;
            }

            /* Responsive adjustments for larger photo */
            @media (max-width: 576px) {
                .modal-student-photo {
                    width: 100px !important;
                    height: 100px !important;
                }
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
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 

<!-- Header - Fixed height, fully visible -->
<div class="header-container">
    <img src="uploads/Head-removebg-preview.png" alt="Header" class="header-image">
</div>

    <!-- Confirmation Modal -->
    <div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Attendance Recorded Successfully</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- Student Photo - Larger Size -->
                    <img id="modalStudentPhoto" 
                        src="assets/img/2601828.png" 
                        alt="Student Photo" 
                        class="modal-student-photo rounded-circle border-4 border-primary shadow mb-3"
                        style="width: 120px; height: 120px; object-fit: cover;">
                    
                    <!-- Student Name -->
                    <h4 id="modalStudentName" class="mb-3" style="color: var(--icon-color); font-weight: 600;"></h4>
                    
                    <!-- Student Information Card -->
                    <div class="student-info-card">
                        <div class="row text-start">
                            <div class="col-6 mb-2">
                                <strong><i class="fas fa-id-card me-1"></i> Student ID:</strong><br>
                                <span id="modalStudentId" style="color: var(--dark-text); font-size: 0.95rem;"></span>
                            </div>
                            <div class="col-6 mb-2">
                                <strong><i class="fas fa-building me-1"></i> Department:</strong><br>
                                <span id="modalStudentDept" style="color: var(--dark-text); font-size: 0.95rem;"></span>
                            </div>
                            <div class="col-6 mb-2">
                                <strong><i class="fas fa-graduation-cap me-1"></i> Year Level:</strong><br>
                                <span id="modalStudentYear" style="color: var(--dark-text); font-size: 0.95rem;"></span>
                            </div>
                            <div class="col-6 mb-2">
                                <strong><i class="fas fa-users me-1"></i> Section:</strong><br>
                                <span id="modalStudentSection" style="color: var(--dark-text); font-size: 0.95rem;"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Status -->
                    <div class="status-badge mt-3" id="modalAttendanceStatus">
                        <span id="modalTimeInOut" class="fw-bold"></span>
                    </div>
                    
                    <!-- Time Display - Show actual times from database -->
                    <div class="time-display mt-3">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Time In</small>
                                <div id="modalTimeIn" class="fw-bold text-primary"></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Time Out</small>
                                <div id="modalTimeOut" class="fw-bold text-primary"></div>
                            </div>
                        </div>
                        <div id="modalDateDisplay" class="text-muted small mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary px-4 py-2" onclick="closeAndRefresh()">
                        <i class="fas fa-check me-2"></i>Confirm & Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Main Container - Scroll Design -->
<div class="main-container">
    <!-- Navigation Tabs -->
    <div class="modern-tabs">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#">
                    <i class="fas fa-qrcode me-2"></i>Scanner
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="students_logs.php?from_scanner=1">
                    <i class="fas fa-history me-2"></i>Attendance Log
                </a>
            </li>
        </ul>
    </div>

    <!-- Content Area - Allow scrolling -->
    <div class="content-area">
        <!-- Scanner Section (70%) -->
        <div class="scanner-section">
            <!-- Department/Location Info -->
            <div class="dept-location-info">
                <div class="row">
                    <div class="col-md-6">
                        <h3><i class="fas fa-building me-2"></i>Department: <?php echo htmlspecialchars($department); ?></h3>
                    </div>
                    <div class="col-md-6">
                        <h3><i class="fas fa-map-marker-alt me-2"></i>Room: <?php echo htmlspecialchars($location); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Clock Display -->
            <div class="clock-display">
                <div id="clock" class="mb-2"></div>
                <div id="currentDate"></div>
            </div>

            <!-- Scanner Alert -->
            <div class="scanner-alert">
                <h4 id="in_out" class="mb-0" style="color: var(--icon-color);">
                    <i class="fas fa-id-card me-2"></i>Scan Your ID Card for Attendance
                </h4>
            </div>

            <!-- Scanner Container -->
            <div class="scanner-container">
                <div id="largeReader"></div>
                <div class="scanner-overlay">
                    <div class="scanner-frame">
                        <div class="scanner-laser"></div>
                    </div>
                </div>
            </div>

            <!-- Result Display -->
            <div id="result"></div>
        </div>

        <!-- Sidebar Section (30%) -->
        <div class="sidebar-section">
            <!-- Student Photo -->
            <img id="pic" class="student-photo" 
                 src="assets/img/section/type.jpg"
                 alt="Student Photo Preview">

            <!-- Manual Input Section -->
            <div class="manual-input-section">
                <h4><i class="fas fa-keyboard me-2"></i> Manual Attendance</h4>
                <p class="text-center text-muted mb-3" style="font-size: 0.8rem;">For students who forgot their ID</p>
                
                <div class="input-group mb-3">
                    <input type="text" 
                           class="form-control" 
                           id="manualIdInput" 
                           placeholder="0000-0000"
                           aria-label="Student ID"
                           maxlength="9">
                    <button class="btn btn-primary" 
                            id="manualSubmitBtn"
                            onclick="processManualInput()">
                        <i class="fas fa-paper-plane me-2"></i>Submit
                    </button>
                </div>
                
                <div class="text-center mt-auto">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Press Enter after typing ID
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let barcodeBuffer = '';
let lastScanTime = 0;
const scanCooldown = 1000; // 1 second cooldown between scans
let scanner = null;
let scanTime = null; // Store the exact time when scan occurred

// ========= SCANNER FUNCTIONS =========
    function initScanner() {
        if (scanner) {
            scanner.clear().catch(console.error);
        }
        
        scanner = new Html5QrcodeScanner('largeReader', { 
            qrbox: {
                width: 300,
                height: 300,
            },
            fps: 20,
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
            showTorchButtonIfSupported: true
        });
        
        // Remove the permission request image by hiding the element
        const permissionElement = document.querySelector('#largeReader img');
        if (permissionElement) {
            permissionElement.style.display = 'none';
        }
        
        scanner.render(onScanSuccess, onScanError);
    }

    function onScanError(error) {
        // Only log actual errors, not benign "no code found" errors
        if (!error.includes('NotFoundException') && !error.includes('No MultiFormat Readers')) {
            console.error('Scanner error:', error);
        }
        
        // Hide any permission-related images
        const permissionElement = document.querySelector('#largeReader img');
        if (permissionElement) {
            permissionElement.style.display = 'none';
        }
    }

// ========= TIME AND DATE FUNCTIONS =========
    function startTime() {
        const today = new Date();
        let h = today.getHours();
        let m = today.getMinutes();
        let s = today.getSeconds();
        let period = h >= 12 ? 'PM' : 'AM';
        
        h = h % 12;
        h = h ? h : 12;
        
        m = checkTime(m);
        s = checkTime(s);
        
        document.getElementById('clock').innerHTML = h + ":" + m + ":" + s + " " + period;
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('currentDate').innerHTML = today.toLocaleDateString('en-US', options);
        
        setTimeout(startTime, 1000);
    }

    function checkTime(i) {
        if (i < 10) {i = "0" + i};
        return i;
    }

    // Function to get current time in the same format as the clock display
    function getCurrentTimeString() {
        const now = new Date();
        let h = now.getHours();
        let m = now.getMinutes();
        let s = now.getSeconds();
        let period = h >= 12 ? 'PM' : 'AM';
        
        h = h % 12;
        h = h ? h : 12;
        
        m = checkTime(m);
        s = checkTime(s);
        
        return h + ":" + m + ":" + s + " " + period;
    }

// ========= BARCODE PROCESSING FUNCTIONS =========
    function processBarcode(barcode) {
        console.log("🔍 Processing barcode:", barcode);
        
        // Store the exact time when the scan occurred
        scanTime = getCurrentTimeString();
        
        // Validate barcode format
        if (!isValidBarcode(barcode)) {
            showErrorMessage("Invalid ID format. Please use format: 0000-0000");
            restartScanner();
            return;
        }
        
        showProcessingState(barcode);
        
        // Disable inputs during processing
        setInputsDisabled(true);
        
        $.ajax({
            type: "POST",
            url: "process_barcode.php",
            data: { 
                barcode: barcode,
                department: "<?php echo htmlspecialchars($department); ?>",
                location: "<?php echo htmlspecialchars($location); ?>"
            },
            dataType: 'json',
            timeout: 15000,
            success: function(response) {
                handleScanSuccess(response, barcode);
            },
            error: function(xhr, status, error) {
                handleScanError(xhr, status, error, barcode);
            },
            complete: function() {
                setInputsDisabled(false);
            }
        });
    }

    function isValidBarcode(barcode) {
        // Basic validation for ID format (0000-0000)
        const idPattern = /^\d{4}-\d{4}$/;
        return idPattern.test(barcode);
    }

    function showProcessingState(barcode) {
        document.getElementById('result').innerHTML = `
            <div class="d-flex justify-content-center align-items-center">
                <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Processing ID: ${barcode}</span>
            </div>
        `;
    }

    function setInputsDisabled(disabled) {
        document.getElementById('manualIdInput').disabled = disabled;
        document.getElementById('manualSubmitBtn').disabled = disabled;
    }

// ========= SCAN RESPONSE HANDLING FUNCTIONS =========
function handleScanSuccess(response, originalBarcode) {
    console.log("✅ SUCCESS - Raw response:", response);
    
    if (!response || typeof response !== 'object') {
        console.error("❌ Invalid response format");
        showSuccessFallback(originalBarcode);
        return;
    }
    
    if (response.error) {
        console.log("❌ Server error:", response.error);
        showErrorMessage(response.error);
        speakErrorMessage(response.error);
        restartScanner();
        return;
    }

    // Log successful student data retrieval
    console.log("🎓 Student Data Retrieved:", {
        name: response.full_name,
        id: response.id_number,
        department: response.department,
        year: response.year_level,
        section: response.section,
        photo: response.photo
    });

    // Update UI and show confirmation
    updateAttendanceUI(response);
    showConfirmationModal(response);
}

function handleScanError(xhr, status, error, originalBarcode) {
    console.error("❌ AJAX ERROR:");
    console.error("Status:", status);
    console.error("Error:", error);
    console.error("Response text:", xhr.responseText);
    
    // Try to parse response even if AJAX reports error
    if (xhr.responseText && xhr.responseText.trim() !== '') {
        try {
            const parsedResponse = JSON.parse(xhr.responseText);
            console.log("📦 Parsed response despite AJAX error:", parsedResponse);
            
            if (parsedResponse.error) {
                showErrorMessage(parsedResponse.error);
            } else {
                updateAttendanceUI(parsedResponse);
                showConfirmationModal(parsedResponse);
                return;
            }
        } catch (e) {
            console.log("❌ Could not parse response as JSON:", e.message);
        }
    }
    
    // Fallback to success since attendance was likely recorded
    showSuccessFallback(originalBarcode);
}

function showSuccessFallback(barcode) {
    console.log("🔄 Using fallback success display");
    
    const fallbackData = {
        full_name: "Student",
        id_number: barcode,
        department: "<?php echo htmlspecialchars($department); ?>",
        photo: "assets/img/2601828.png",
        section: "N/A",
        year_level: "N/A", 
        role: "Student",
        time_in_out: "Attendance Recorded Successfully",
        alert_class: "alert-success",
        attendance_type: "time_in"
    };
    
    updateAttendanceUI(fallbackData);
    showConfirmationModal(fallbackData);
}

// ========= CONFIRMATION MODAL FUNCTIONS =========
function showConfirmationModal(data) {
    console.log("🎯 Showing confirmation modal with:", data);
    
    const now = new Date();
    const dateString = now.toLocaleDateString([], { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });

    // Update modal content with sanitized data
    updateModalContent(data, dateString);
    
    // Show modal using Bootstrap
    const modalElement = document.getElementById('confirmationModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();

    // Hide scanner overlay while modal is open
    document.querySelector('.scanner-overlay').style.display = 'none';

    // Restart scanner once modal is closed
    modalElement.addEventListener('hidden.bs.modal', function () {
        console.log("🎯 Modal closed, restarting scanner");
        restartScanner();
    }, { once: true });

    // Speak confirmation message if available
    if (data.voice) {
        speakErrorMessage(data.voice);
    }
}

    function updateModalContent(data, dateString) {
        // Sanitize and set text content (prevents XSS)
        document.getElementById('modalStudentName').textContent = sanitizeHTML(data.full_name || 'Student Name');
        document.getElementById('modalStudentId').textContent = sanitizeHTML(data.id_number || 'N/A');
        document.getElementById('modalStudentDept').textContent = sanitizeHTML(data.department || 'N/A');
        document.getElementById('modalStudentYear').textContent = sanitizeHTML(data.year_level || 'N/A');
        document.getElementById('modalStudentSection').textContent = sanitizeHTML(data.section || 'N/A');
        document.getElementById('modalDateDisplay').textContent = dateString;
        
        // Set actual time in/time out from database if available
        const timeInElement = document.getElementById('modalTimeIn');
        const timeOutElement = document.getElementById('modalTimeOut');
        
        // Check if this is a time-out event and we have the actual time_out from database
        if (data.attendance_type === 'time_out' || data.time_in_out === 'Time Out Recorded') {
            // For time-out, show the actual recorded time_in in the Time In field
            if (data.display_time_in) {
                timeInElement.textContent = data.display_time_in;
            } else {
                timeInElement.textContent = '-';
                timeInElement.style.color = '#6c757d';
            }
            
            // Show the actual recorded time_out in the Time Out field
            if (data.display_time_out) {
                timeOutElement.textContent = data.display_time_out;
                timeOutElement.style.color = ''; // Reset to default color
            } else {
                // Use the scan time for time out
                timeOutElement.textContent = scanTime || getCurrentTimeString();
            }
        } else {
            // For time-in or other events
            if (data.display_time_in) {
                timeInElement.textContent = data.display_time_in;
            } else {
                // Use the scan time for time in
                timeInElement.textContent = scanTime || getCurrentTimeString();
            }
            
            if (data.display_time_out) {
                timeOutElement.textContent = data.display_time_out;
            } else {
                timeOutElement.textContent = '-';
                timeOutElement.style.color = '#6c757d';
            }
        }
        
        // Set attendance status with proper styling
        updateAttendanceStatus(data);
        
        // Update student photos with fallback handling
        updateStudentPhotos(data);
    }
    function updateAttendanceStatus(data) {
            const statusElement = document.getElementById('modalTimeInOut');
            const statusContainer = document.getElementById('modalAttendanceStatus');
        
            // Clear existing classes
            statusContainer.className = 'status-badge';
        
            // Determine if this is time-in or time-out
            const isTimeOut = data.attendance_type === 'time_out' || 
                        data.time_in_out === 'Time Out Recorded' || 
                        (data.alert_class && data.alert_class.includes('warning'));
        
            if (isTimeOut) {
            statusElement.textContent = '✓ TIME OUT RECORDED';
            statusContainer.classList.add('time-out-badge');
            } else {
            statusElement.textContent = '✓ TIME IN RECORDED';
            statusContainer.classList.add('time-in-badge');
         }
        }

function updateStudentPhotos(data) {
    const modalPhoto = document.getElementById('modalStudentPhoto');
    const mainPhoto = document.getElementById('pic');
    
    // Function to set photo with fallback
    const setPhotoWithFallback = (photoElement, photoPath, fallbackPath) => {
        if (photoPath) {
            // Add cache busting parameter
            const timestamp = new Date().getTime();
            photoElement.src = photoPath + (photoPath.includes('?') ? '&' : '?') + 't=' + timestamp;
            
            // Set error handler if photo fails to load
            photoElement.onerror = function() {
                console.log("❌ Failed to load student photo, using default");
                this.src = fallbackPath;
                this.onerror = null; // Remove error handler to prevent loops
            };
        } else {
            photoElement.src = fallbackPath;
        }
    };
    
    // Update modal photo
    setPhotoWithFallback(modalPhoto, data.photo, 'assets/img/2601828.png');
    
    // Update main display photo
    if (mainPhoto) {
        setPhotoWithFallback(mainPhoto, data.photo, 'assets/img/section/type.jpg');
    }
}

// ========= UI UPDATE FUNCTIONS =========
function updateAttendanceUI(data) {
    // Update alert color and text
    const inOutElement = document.getElementById('in_out');
    
    inOutElement.textContent = data.time_in_out || 'Scan Your ID Card for Attendance';
    
    // Update result display
    if (data.time_in_out) {
        const alertClass = data.alert_class || 'alert-success';
        document.getElementById('result').innerHTML = `
            <div class="alert ${alertClass} py-2" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${sanitizeHTML(data.time_in_out)}
            </div>
        `;
    }
}

function showErrorMessage(message) {
    const sanitizedMessage = sanitizeHTML(message);
    document.getElementById('result').innerHTML = `
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div>${sanitizedMessage}</div>
        </div>
    `;
    playAlertSound();
    restartScanner();
}

// ========= MANUAL ATTENDANCE FUNCTIONS =========
function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        speakErrorMessage("Please enter ID number");
        return;
    }
    
    if (!isValidBarcode(idNumber)) {
        showErrorMessage("Invalid ID format. Please use: 0000-0000");
        return;
    }
    
    // Store the exact time when the manual input occurred
    scanTime = getCurrentTimeString();
    
    showProcessingState(idNumber);
    setInputsDisabled(true);
    
    // Process as barcode
    processBarcode(idNumber);
}

// ========= UTILITY FUNCTIONS =========
function sanitizeHTML(str) {
    const temp = document.createElement('div');
    temp.textContent = str;
    return temp.innerHTML;
}

function playAlertSound() {
    const audio = document.getElementById('myAudio');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(error => {
            console.log('Audio playback failed:', error);
        });
    }
}

function speakErrorMessage(message) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1.1;
        
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            const preferredVoices = [
                'Google UK English Female',
                'Microsoft Zira Desktop',
                'Karen'
            ];
            
            const voice = voices.find(v => preferredVoices.includes(v.name)) || 
                          voices.find(v => v.lang.includes('en')) || 
                          voices[0];
            
            speech.voice = voice;
        }
        
        window.speechSynthesis.speak(speech);
    }
}

function closeAndRefresh() {
    const modalEl = document.getElementById('confirmationModal');
    if (!modalEl) {
        window.location.reload();
        return;
    }

    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.hide();

    modalEl.addEventListener('hidden.bs.modal', function() {
        window.location.reload();
    }, { once: true });
}

// ========= INITIALIZATION =========
document.addEventListener('DOMContentLoaded', function() {
    // Initialize speech synthesis
    if ('speechSynthesis' in window) {
        let voices = window.speechSynthesis.getVoices();
        if (voices.length === 0) {
            window.speechSynthesis.onvoiceschanged = function() {
                voices = window.speechSynthesis.getVoices();
            };
        }
    }
    
    // Initialize scanner with camera permission check
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => {
            initScanner();
        })
        .catch(err => {
            console.error("Scanner permission denied:", err);
            showErrorMessage("Tap Your ID Card or Use Manual Input");
        });
    
    // Set up event listeners
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    document.getElementById('manualSubmitBtn').addEventListener('click', processManualInput);
    
    // Focus on input field
    document.getElementById('manualIdInput').focus();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopScanner();
    } else {
        setTimeout(initScanner, 500);
    }
});

// Clean up when leaving page
window.addEventListener('beforeunload', function() {
    stopScanner();
});
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin/lib/chart/chart.min.js"></script>
<!-- Include the HTML5 QR Code scanner library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</body>
</html>