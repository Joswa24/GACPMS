<?php
date_default_timezone_set('Asia/Manila'); // Change this to your timezone
session_start();
include '../connection.php';

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
         // Set MySQL timezone to match PHP timezone
        $db->query("SET time_zone = '+08:00'"); // Adjust offset to match your timezone
        
        // Get current time in the correct timezone
        $currentDateTime = date('Y-m-d H:i:s');
        
        // Get location from IP
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $location = 'Unknown';
        
        // Try to get location from IP using a free API
        if (function_exists('file_get_contents') && $ipAddress !== '127.0.0.1' && $ipAddress !== '::1') {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            // Try ip-api.com first
            $ipData = @file_get_contents("http://ip-api.com/json/{$ipAddress}", false, $context);
            if ($ipData) {
                $ipInfo = json_decode($ipData);
                if ($ipInfo && $ipInfo->status === 'success') {
                    $location = $ipInfo->city . ', ' . $ipInfo->regionName . ', ' . $ipInfo->country;
                }
            }
            
            // If ip-api.com fails, try ipinfo.io
            if ($location === 'Unknown') {
                $ipData = @file_get_contents("https://ipinfo.io/{$ipAddress}/json", false, $context);
                if ($ipData) {
                    $ipInfo = json_decode($ipData);
                    if ($ipInfo && isset($ipInfo->city)) {
                        $location = $ipInfo->city . ', ' . ($ipInfo->region ?? '') . ', ' . ($ipInfo->country ?? '');
                    }
                }
            }
        }
        
        // Update the most recent login record without logout time
        $stmt = $db->prepare("UPDATE admin_access_logs 
                             SET logout_time = ?, 
                                 location = COALESCE(?, location),
                                 location_details = COALESCE(?, location_details),
                                 activity = 'Logout'
                             WHERE admin_id = ? 
                             AND logout_time IS NULL 
                             ORDER BY login_time DESC 
                             LIMIT 1");
        
        if ($stmt) {
            $stmt->bind_param("sssi", $currentDateTime, $location, $locationJson, $_SESSION['user_id']);
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