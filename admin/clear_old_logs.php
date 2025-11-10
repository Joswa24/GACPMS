<?php
session_start();
include '../connection.php';

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


// Set response header
header('Content-Type: application/json');

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request. Please refresh the page and try again.'
    ]);
    exit;
}

// Verify user is logged in and has admin privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You must be logged in to perform this action.'
    ]);
    exit;
}

// Default to 30 days, but allow custom days if provided
 $days = isset($_POST['days']) ? (int)$_POST['days'] : 30;

// Validate days parameter
if ($days < 1) {
    $days = 30;
}

try {
    // Calculate the date threshold
    $thresholdDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    // First, count how many records will be deleted
    $countSql = "SELECT COUNT(*) as count FROM admin_access_logs WHERE login_time < ?";
    $countStmt = $db->prepare($countSql);
    $countStmt->bind_param("s", $thresholdDate);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult->fetch_assoc();
    $recordsToDelete = $countRow['count'];
    
    if ($recordsToDelete == 0) {
        echo json_encode([
            'status' => 'success',
            'message' => "No logs older than {$days} days found to delete."
        ]);
        exit;
    }
    
    // Delete the old logs
    $deleteSql = "DELETE FROM admin_access_logs WHERE login_time < ?";
    $deleteStmt = $db->prepare($deleteSql);
    $deleteStmt->bind_param("s", $thresholdDate);
    
    if ($deleteStmt->execute()) {
        // Log this action
        $adminId = $_SESSION['user_id'] ?? 0;
        $username = $_SESSION['username'] ?? 'Unknown';
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $activity = "Cleared {$recordsToDelete} old access logs (older than {$days} days)";
        
        $logSql = "INSERT INTO admin_access_logs 
            (admin_id, username, login_time, ip_address, user_agent, activity, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'success')";
        
        $logStmt = $db->prepare($logSql);
        $logStmt->bind_param("isssss", 
            $adminId, 
            $username, 
            date('Y-m-d H:i:s'), 
            $ipAddress, 
            $userAgent, 
            $activity
        );
        $logStmt->execute();
        
        echo json_encode([
            'status' => 'success',
            'message' => "Successfully deleted {$recordsToDelete} log entries older than {$days} days."
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete old logs. Please try again.'
        ]);
    }
} catch (Exception $e) {
    error_log("Error clearing old logs: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while clearing old logs. Please try again.'
    ]);
}
?>