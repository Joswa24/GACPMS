<?php
date_default_timezone_set('Asia/Manila');
session_start();
include 'connection.php';

// Enhanced session verification
function verifyInstructorSession() {
    if (!isset($_SESSION['access']['instructor']['id'])) {
        error_log("❌ Instructor session missing in process_barcode.php");
        return false;
    }
    
    if (empty($_SESSION['access']['instructor']['id'])) {
        error_log("❌ Instructor ID is empty in session");
        return false;
    }
    
    error_log("✅ Instructor session verified: " . $_SESSION['access']['instructor']['id']);
    return true;
}

// Check if student is in allowed year and section
function isStudentInAllowedClass($db, $student_id, $allowed_year, $allowed_section) {
    if (!$allowed_year || !$allowed_section) {
        error_log("⚠️ No year/section restrictions set - allowing all students");
        return true; // No restrictions
    }
    
    $query = "SELECT year, section FROM students WHERE id_number = ?";
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
    
    $is_allowed = ($student['year'] == $allowed_year && $student['section'] == $allowed_section);
    
    if (!$is_allowed) {
        error_log("❌ Student class mismatch: Student is {$student['year']}-{$student['section']}, Required: $allowed_year-$allowed_section");
    } else {
        error_log("✅ Student class matches: {$student['year']}-{$student['section']}");
    }
    
    return $is_allowed;
}

// Session verification at the start
if (!verifyInstructorSession()) {
    echo json_encode([
        'error' => 'Session expired. Please login again.',
        'session_expired' => true,
        'voice' => 'Session expired. Please login again.'
    ]);
    exit();
}

// Get allowed year and section from session
$allowed_year = $_SESSION['allowed_year'] ?? null;
$allowed_section = $_SESSION['allowed_section'] ?? null;

error_log("🎯 Processing barcode with restrictions - Year: $allowed_year, Section: $allowed_section");

$instructor_id = $_SESSION['access']['instructor']['id'];
$instructor_name = $_SESSION['access']['instructor']['fullname'];

$barcode = $_POST['barcode'] ?? '';
$department = $_POST['department'] ?? '';
$location = $_POST['location'] ?? '';

if (empty($barcode)) {
    echo json_encode([
        'error' => 'No barcode provided',
        'voice' => 'No ID provided'
    ]);
    exit;
}

// Clean the barcode input
$barcode = trim($barcode);

// Validate barcode format
if (!preg_match('/^\d{4}-\d{4}$/', $barcode)) {
    echo json_encode([
        'error' => 'Invalid ID format. Please use format: 0000-0000',
        'voice' => 'Invalid ID format'
    ]);
    exit;
}

try {
    // Get current time in Asia/Manila timezone
    $currentDateTime = date('Y-m-d H:i:s');
    $currentTime = date('h:i A');
    $currentDate = date('Y-m-d');

    // Query student information
    $sql = "SELECT s.*, d.department_name
            FROM students s
            LEFT JOIN department d ON s.department_id = d.department_id
            WHERE s.id_number = ?";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'error' => 'Student ID not found.',
            'voice' => 'Student ID not found'
        ]);
        exit;
    }

    $student = $result->fetch_assoc();
    $photoPath = getStudentPhoto($student['photo']);

    error_log("🎓 Student found: {$student['fullname']} ({$student['year']}-{$student['section']})");

    // Check year and section restrictions
    if (!isStudentInAllowedClass($db, $barcode, $allowed_year, $allowed_section)) {
        echo json_encode([
            'error' => "Access denied. This class is for $allowed_year-$allowed_section only.",
            'voice' => "Access denied. This class is for $allowed_year $allowed_section only."
        ]);
        exit;
    }

    // IMPROVED LOGIC: Check for today's attendance records
    $checkQuery = "SELECT id, time_in, time_out 
                  FROM attendance_logs 
                  WHERE student_id = ? 
                  AND DATE(time_in) = ? 
                  ORDER BY time_in DESC 
                  LIMIT 1";
    $stmtCheck = $db->prepare($checkQuery);
    $stmtCheck->bind_param("is", $student['id'], $currentDate);
    $stmtCheck->execute();
    $existingResult = $stmtCheck->get_result();

    $response = [];

    if ($existingResult->num_rows > 0) {
        // Student has existing record today
        $existing = $existingResult->fetch_assoc();
        
        if ($existing['time_out'] === null || empty($existing['time_out'])) {
            // TIME OUT: Student has time-in but no time-out → record time-out
            $updateQuery = "UPDATE attendance_logs 
                          SET time_out = ?, instructor_id_out = ?
                          WHERE id = ?";
            $stmtUpdate = $db->prepare($updateQuery);
            $stmtUpdate->bind_param("sii", $currentDateTime, $instructor_id, $existing['id']);
            
            if ($stmtUpdate->execute()) {
                $response = [
                    'full_name' => $student['fullname'],
                    'id_number' => $student['id_number'],
                    'department' => $student['department_name'],
                    'year_level' => $student['year'], // FIXED: Changed from 'year' to 'year_level'
                    'section' => $student['section'],
                    'photo' => $photoPath,
                    'time_in_out' => 'Time Out Recorded Successfully',
                    'alert_class' => 'alert-warning',
                    'attendance_type' => 'time_out',
                    'role' => 'Student',
                    'voice' => 'Time out recorded for ' . $student['fullname'],
                    'status' => 'success',
                    'display_time_in' => date('h:i A', strtotime($existing['time_in'])),
                    'display_time_out' => $currentTime,
                    'actual_time_in' => $existing['time_in'],
                    'actual_time_out' => $currentDateTime
                ];
                error_log("✅ Time Out recorded for {$student['fullname']}");
            } else {
                throw new Exception("Failed to record time out");
            }
            $stmtUpdate->close();
        } else {
            // TIME IN: Student already has both time-in and time-out today → create new time-in
            $insertQuery = "INSERT INTO attendance_logs 
                           (student_id, id_number, time_in, department, location, instructor_id) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsert = $db->prepare($insertQuery);
            $stmtInsert->bind_param("issssi", 
                $student['id'], 
                $student['id_number'],
                $currentDateTime, 
                $department, 
                $location,
                $instructor_id
            );
            
            if ($stmtInsert->execute()) {
                $response = [
                    'full_name' => $student['fullname'],
                    'id_number' => $student['id_number'],
                    'department' => $student['department_name'],
                    'year_level' => $student['year'], // FIXED: Changed from 'year' to 'year_level'
                    'section' => $student['section'],
                    'photo' => $photoPath,
                    'time_in_out' => 'Time In Recorded Successfully',
                    'alert_class' => 'alert-success',
                    'attendance_type' => 'time_in',
                    'role' => 'Student',
                    'voice' => 'Time in recorded for ' . $student['fullname'],
                    'status' => 'success',
                    'display_time_in' => $currentTime,
                    'display_time_out' => null,
                    'actual_time_in' => $currentDateTime,
                    'actual_time_out' => null
                ];
                error_log("✅ Time In recorded for {$student['fullname']}");
            } else {
                throw new Exception("Failed to record time in");
            }
            $stmtInsert->close();
        }
    } else {
        // TIME IN: No records today → create first time-in
        $insertQuery = "INSERT INTO attendance_logs 
                       (student_id, id_number, time_in, department, location, instructor_id) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsert = $db->prepare($insertQuery);
        $stmtInsert->bind_param("issssi", 
            $student['id'], 
            $student['id_number'],
            $currentDateTime, 
            $department, 
            $location,
            $instructor_id
        );
        
        if ($stmtInsert->execute()) {
            $response = [
                'full_name' => $student['fullname'],
                'id_number' => $student['id_number'],
                'department' => $student['department_name'],
                'year_level' => $student['year'], // FIXED: Changed from 'year' to 'year_level'
                'section' => $student['section'],
                'photo' => $photoPath,
                'time_in_out' => 'Time In Recorded Successfully',
                'alert_class' => 'alert-success',
                'attendance_type' => 'time_in',
                'role' => 'Student',
                'voice' => 'Time in recorded for ' . $student['fullname'],
                'status' => 'success',
                'display_time_in' => $currentTime,
                'display_time_out' => null,
                'actual_time_in' => $currentDateTime,
                'actual_time_out' => null
            ];
            error_log("✅ Time In recorded for {$student['fullname']}");
        } else {
            throw new Exception("Failed to record time in");
        }
        $stmtInsert->close();
    }

    // Update instructor attendance summary
    updateInstructorAttendanceSummary($db, $instructor_id, $instructor_name, $allowed_year, $allowed_section);

    echo json_encode($response);

} catch (Exception $e) {
    error_log("❌ Attendance system error: " . $e->getMessage());
    echo json_encode([
        'error' => 'System error: ' . $e->getMessage(),
        'voice' => 'System error occurred'
    ]);
}

// Helper function to get student photo
function getStudentPhoto($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/2601828.png';

    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Update instructor attendance summary
function updateInstructorAttendanceSummary($db, $instructor_id, $instructor_name, $year, $section) {
    $current_date = date('Y-m-d');
    
    // Check if summary exists for today
    $check_query = "SELECT id FROM instructor_attendance_summary 
                   WHERE instructor_id = ? AND session_date = ?";
    $stmt = $db->prepare($check_query);
    $stmt->bind_param("is", $instructor_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new summary
        $subject_name = $_SESSION['access']['subject']['name'] ?? 'General Subject';
        $insert_query = "INSERT INTO instructor_attendance_summary 
                        (instructor_id, instructor_name, subject_name, year_level, section,
                         total_students, present_count, absent_count, attendance_rate,
                         session_date, time_in, time_out) 
                        VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0.00, ?, NOW(), '00:00:00')";
        $stmt2 = $db->prepare($insert_query);
        $stmt2->bind_param("isssss", $instructor_id, $instructor_name, $subject_name, $year, $section, $current_date);
        $stmt2->execute();
        $stmt2->close();
    }
    
    $stmt->close();
    
    // Update counts
    $update_query = "UPDATE instructor_attendance_summary 
                    SET present_count = present_count + 1,
                        attendance_rate = (present_count / GREATEST(total_students, 1)) * 100
                    WHERE instructor_id = ? AND session_date = ?";
    $stmt3 = $db->prepare($update_query);
    $stmt3->bind_param("is", $instructor_id, $current_date);
    $stmt3->execute();
    $stmt3->close();
    
    error_log("📊 Updated instructor attendance summary for $instructor_name");
}

// Close database connection
if (isset($db)) {
    $db->close();
}
?>