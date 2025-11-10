<?php
session_start();
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Check if user is logged in and 2FA verified
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: index.php');
    exit();
}
// Include connection
include '../connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_POST['log_id']) || empty($_POST['log_id'])) {
    echo json_encode(['success' => false, 'message' => 'Log ID is required']);
    exit;
}

$logId = intval($_POST['log_id']);

try {
    // First, get the log details for logging
    $selectQuery = "SELECT full_name, visitor_id FROM visitor_logs WHERE id = ?";
    $selectStmt = $db->prepare($selectQuery);
    $selectStmt->bind_param('i', $logId);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
        exit;
    }
    
    $logData = $result->fetch_assoc();
    $selectStmt->close();
    
    // Delete the log
    $deleteQuery = "DELETE FROM visitor_logs WHERE id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bind_param('i', $logId);
    
    if ($deleteStmt->execute()) {
        // Log the deletion action
        $adminId = $_SESSION['user_id'];
        $currentTime = date('Y-m-d H:i:s');
        $action = "Deleted visitor log - Name: " . $logData['full_name'] . ", ID: " . $logData['visitor_id'];
        
        $logQuery = "INSERT INTO admin_logs (admin_id, action, timestamp) VALUES (?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bind_param('iss', $adminId, $action, $currentTime);
        $logStmt->execute();
        $logStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Log deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete log']);
    }
    
    $deleteStmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>