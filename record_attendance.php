<?php
session_start();
include 'connection.php';

// Get POST data
$id_number = $_POST['id_number'] ?? '';
$current_department = $_POST['current_department'] ?? '';
$current_location = $_POST['current_location'] ?? '';

// Validate input
if (empty($id_number)) {
    echo json_encode(['error' => 'ID number is required']);
    exit;
}

// Fetch student data
$sql = "SELECT * FROM students WHERE id_number = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

$student = $result->fetch_assoc();

// Determine if it's time in or time out
$sql = "SELECT * FROM attendance_logs 
        WHERE student_id = ? 
        AND department = ? 
        AND location = ? 
        AND DATE(time_in) = CURDATE() 
        ORDER BY time_in DESC 
        LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->bind_param("sss", $student['id'], $current_department, $current_location);
$stmt->execute();
$attendance_result = $stmt->get_result();

$time_in_out = 'Time In';
$alert_class = 'alert-success';
$voice = "Time in recorded for {$student['first_name']}";

if ($attendance_result->num_rows > 0) {
    $attendance = $attendance_result->fetch_assoc();
    if ($attendance['time_out'] === null) {
        // Time Out
        $sql = "UPDATE attendance_logs SET time_out = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $attendance['id']);
        $stmt->execute();
        
        $time_in_out = 'Time Out';
        $alert_class = 'alert-danger';
        $voice = "Time out recorded for {$student['first_name']}";
    } else {
        // Time In (new entry)
        $sql = "INSERT INTO attendance_logs 
                (student_id, department, location, time_in) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("sss", $student['id'], $current_department, $current_location);
        $stmt->execute();
    }
} else {
    // First Time In
    $sql = "INSERT INTO attendance_logs 
            (student_id, department, location, time_in) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sss", $student['id'], $current_department, $current_location);
    $stmt->execute();
}

// Prepare response
$response = [
    //'photo' => $student['photo'],
    'full_name' => $student['first_name'] . ' ' . $student['last_name'],
    'id_number' => $student['id_number'],
    'department' => $student['department'],
    'role' => $student['role'],
    'course' => $student['course'],
    'year_level' => $student['year_level'],
    'time_in_out' => $time_in_out,
    'alert_class' => $alert_class,
    'voice' => $voice
];

echo json_encode($response);
?>