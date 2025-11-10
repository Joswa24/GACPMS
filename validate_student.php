<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone and include connection
date_default_timezone_set('Asia/Manila');
include 'connection.php';

// Set MySQL timezone to match PHP
mysqli_query($db, "SET time_zone = '+08:00'");

// Set content type to JSON
header('Content-Type: application/json');

// Check if instructor is logged in
if (!isset($_SESSION['access']['instructor']['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Instructor not logged in'
    ]);
    exit();
}

// Get instructor ID and current schedule
 $instructor_id = $_SESSION['access']['instructor']['id'];
 $current_date = date('Y-m-d');
 $current_day = date('l'); // e.g., Monday, Tuesday, etc.

// Get instructor's current schedule
 $schedule_query = "SELECT year_level, section, subject, room_name, start_time, end_time
                  FROM room_schedules 
                  WHERE instructor_id = ? 
                  AND day = ?
                  AND ? BETWEEN start_time AND end_time";
                  
 $schedule_stmt = $db->prepare($schedule_query);
 $current_time = date('H:i:s');
 $schedule_stmt->bind_param("iss", $instructor_id, $current_day, $current_time);
 $schedule_stmt->execute();
 $schedule_result = $schedule_stmt->get_result();

if ($schedule_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'No active schedule found for this time'
    ]);
    exit();
}

 $schedule = $schedule_result->fetch_assoc();
 $allowed_year = $schedule['year_level'];
 $allowed_section = $schedule['section'];
 $subject = $schedule['subject'];
 $room = $schedule['room_name'];

// Get student information from the scanned ID
if (!isset($_POST['barcode'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Student ID not provided'
    ]);
    exit();
}

 $barcode = $_POST['barcode'];

// Remove any formatting from barcode (e.g., dashes)
 $clean_barcode = preg_replace('/[^0-9]/', '', $barcode);

// Query to get student information
 $student_query = "SELECT s.id, s.id_number, s.fullname, s.year, s.section, 
                 d.department_name, s.photo
                 FROM students s
                 LEFT JOIN department d ON s.department_id = d.department_id
                 WHERE s.id_number LIKE ? OR s.rfid_number = ?";
                 
 $student_stmt = $db->prepare($student_query);
 $barcode_pattern = "%$clean_barcode%";
 $student_stmt->bind_param("ss", $barcode_pattern, $clean_barcode);
 $student_stmt->execute();
 $student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Student not found in the database'
    ]);
    exit();
}

 $student = $student_result->fetch_assoc();
 $student_year = $student['year'];
 $student_section = $student['section'];

// Check if student belongs to the scheduled class
 $is_valid_student = ($student_year === $allowed_year && $student_section === $allowed_section);

if (!$is_valid_student) {
    echo json_encode([
        'success' => false,
        'error' => "This student is not enrolled in this class. 
                   Student: {$student_year} - {$student_section}, 
                   Required: {$allowed_year} - {$allowed_section}",
        'student_data' => [
            'fullname' => $student['fullname'],
            'year' => $student_year,
            'section' => $student_section,
            'required_year' => $allowed_year,
            'required_section' => $allowed_section
        ]
    ]);
    exit();
}

// Student is valid, return success with student data
echo json_encode([
    'success' => true,
    'message' => 'Student validated successfully',
    'student_data' => [
        'id' => $student['id'],
        'id_number' => $student['id_number'],
        'fullname' => $student['fullname'],
        'year' => $student['year'],
        'section' => $student['section'],
        'department' => $student['department_name'],
        'photo' => $student['photo'],
        'schedule_info' => [
            'subject' => $subject,
            'room' => $room,
            'year_level' => $allowed_year,
            'section' => $allowed_section
        ]
    ]
]);

 $student_stmt->close();
 $schedule_stmt->close();
 $db->close();
?>