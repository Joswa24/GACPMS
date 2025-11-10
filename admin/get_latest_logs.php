<?php
// Include connection
include '../connection.php';
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

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
        exit();
    }

    // Get the latest logs
    $logs = [];
    $sql = "SELECT al.*, u.username 
            FROM admin_access_logs al 
            LEFT JOIN user u ON al.admin_id = u.id 
            ORDER BY al.login_time DESC 
            LIMIT 10";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'logs' => $logs]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>