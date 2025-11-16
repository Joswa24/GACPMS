<?php
date_default_timezone_set('Asia/Manila'); // Change this to your timezone
session_start();
include '../connection.php';

// Log logout if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Set MySQL timezone to match PHP timezone
        $db->query("SET time_zone = '+08:00'"); // Adjust offset to match your timezone
        
        // Get current time in the correct timezone
        $currentDateTime = date('Y-m-d H:i:s');
        
        // Update the most recent login record without logout time
        // Only update logout_time and activity, leave location unchanged
        $stmt = $db->prepare("UPDATE admin_access_logs 
                             SET logout_time = ?, 
                                 activity = 'Logout'
                             WHERE admin_id = ? 
                             AND logout_time IS NULL 
                             ORDER BY login_time DESC 
                             LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("si", $currentDateTime, $_SESSION['user_id']);
            $stmt->execute();
            
            // Log the update for debugging
            error_log("Logout recorded for user {$_SESSION['user_id']} at {$currentDateTime} (timezone: " . date_default_timezone_get() . ")");
        } else {
            error_log("Failed to prepare logout statement");
        }
        
    } catch (Exception $e) {
        error_log("Failed to log logout: " . $e->getMessage());
    }
}

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
header('Location: index');
exit();
?>