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
$currentTime = date('Y-m-d H:i:s');

try {
    // First, check if the log exists and hasn't been timed out yet
    $checkQuery = "SELECT id, full_name FROM visitor_logs WHERE id = ? AND time_out IS NULL";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bind_param('i', $logId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Log not found or already checked out']);
        exit;
    }
    
    $visitorData = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    // Update the time_out
    $updateQuery = "UPDATE visitor_logs SET time_out = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bind_param('si', $currentTime, $logId);
    
    if ($updateStmt->execute()) {
        // Log the action
        $adminId = $_SESSION['user_id'];
        $action = "Forced time out for visitor: " . $visitorData['full_name'];
        $logQuery = "INSERT INTO admin_logs (admin_id, action, timestamp) VALUES (?, ?, ?)";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bind_param('iss', $adminId, $action, $currentTime);
        $logStmt->execute();
        $logStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Time out recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update time out']);
    }
    
    $updateStmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>