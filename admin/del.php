<?php
include '../connection.php';
session_start();

// Function to send JSON response
function jsonResponse($status, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if request is valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Invalid request method');
}

if (!isset($_POST['type']) || !isset($_POST['id'])) {
    jsonResponse('error', 'Invalid request parameters');
}

$type = $_POST['type'];
$id = (int)$_POST['id'];

// Validate ID
if ($id <= 0) {
    jsonResponse('error', 'Invalid ID');
}

try {
    switch ($type) {
        case 'department':
            // First check if department has any personnel
            $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE department = 
                (SELECT department_name FROM department WHERE department_id = ?)");
            $checkPersonnel->bind_param("i", $id);
            $checkPersonnel->execute();
            $checkPersonnel->bind_result($personnelCount);
            $checkPersonnel->fetch();
            $checkPersonnel->close();

            if ($personnelCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned personnel');
            }

            // Check if department has any rooms
            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE department = 
                (SELECT department_name FROM department WHERE department_id = ?)");
            $checkRooms->bind_param("i", $id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned rooms');
            }

            // Proceed with deletion
            $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Department deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
            }
            break;

        case 'visitor':
            $stmt = $db->prepare("DELETE FROM visitor WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Visitor card deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete visitor card: ' . $stmt->error);
            }
            break;

        case 'personell':
            // Check for lost cards
            $checkLostCards = $db->prepare("SELECT COUNT(*) FROM lostcard WHERE personnel_id = ?");
            $checkLostCards->bind_param("i", $id);
            $checkLostCards->execute();
            $checkLostCards->bind_result($lostCardCount);
            $checkLostCards->fetch();
            $checkLostCards->close();

            if ($lostCardCount > 0) {
                jsonResponse('error', 'Cannot delete personnel with associated lost card records');
            }

            // Check for access logs
            $checkLogs = $db->prepare("SELECT COUNT(*) FROM personell_logs WHERE personnel_id = ?");
            $checkLogs->bind_param("i", $id);
            $checkLogs->execute();
            $checkLogs->bind_result($logCount);
            $checkLogs->fetch();
            $checkLogs->close();

            if ($logCount > 0) {
                jsonResponse('error', 'Cannot delete personnel with associated access logs');
            }

            // Delete personnel
            $stmt = $db->prepare("DELETE FROM personell WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
            }
            break;

      case 'room':
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
    $checkSchedules = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE room_name COLLATE utf8mb4_unicode_ci = 
        (SELECT room COLLATE utf8mb4_unicode_ci FROM rooms WHERE id = ?)");
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

        case 'role':
            // Check if role is assigned to personnel
            $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE role = 
                (SELECT role FROM role WHERE id = ?)");
            $checkPersonnel->bind_param("i", $id);
            $checkPersonnel->execute();
            $checkPersonnel->bind_result($personnelCount);
            $checkPersonnel->fetch();
            $checkPersonnel->close();

            if ($personnelCount > 0) {
                jsonResponse('error', 'Cannot delete role assigned to personnel');
            }

            // Check if role is assigned to rooms
            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms 
                WHERE FIND_IN_SET(
                    (SELECT role FROM role WHERE id = ?) COLLATE utf8mb4_unicode_ci, 
                    authorized_personnel COLLATE utf8mb4_unicode_ci
                ) > 0");
            $checkRooms->bind_param("i", $id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete role assigned to rooms');
            }

            // Proceed with deletion
            $stmt = $db->prepare("DELETE FROM role WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Role deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete role: ' . $stmt->error);
            }
            break;

        case 'delete_instructor':
            // Check if instructor exists
            $check = $db->prepare("SELECT id FROM instructor WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows === 0) {
                jsonResponse('error', 'Instructor not found');
            }
            $check->close();

            // Delete instructor
            $stmt = $db->prepare("DELETE FROM instructor WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Instructor deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete instructor: ' . $stmt->error);
            }
            break;

        case 'delete_student':
        // Check if student exists
        $check = $db->prepare("SELECT id FROM students WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            jsonResponse('error', 'Student not found');
        }
        $check->close();

        // Delete student
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Student deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete student');
        }
        break;
            case 'schedule':
    // First check if schedule exists
    $check = $db->prepare("SELECT id FROM room_schedules WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows === 0) {
        jsonResponse('error', 'Schedule not found');
    }
    $check->close();

    // Delete schedule
    $stmt = $db->prepare("DELETE FROM room_schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Schedule deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete schedule: ' . $stmt->error);
    }
    break;

        default:
            jsonResponse('error', 'Invalid type specified');
    }
} catch (Exception $e) {
    jsonResponse('error', 'Database error: ' . $e->getMessage());
}

$db->close();
?>
