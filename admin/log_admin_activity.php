<?php
// log_admin_activity.php
function logLogin($db, $admin_id, $username, $status = 'success', $activity = 'Login') {
    try {
        // Get client information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Get location from IP (simplified version)
        $location = getLocationFromIP($ip_address);
        
        $stmt = $db->prepare("
            INSERT INTO admin_access_logs 
            (admin_id, username, login_time, ip_address, user_agent, location, activity, status) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "issssss", 
            $admin_id, 
            $username, 
            $ip_address, 
            $user_agent, 
            $location, 
            $activity, 
            $status
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
        return false;
    }
}

function logLogout($db, $admin_id) {
    try {
        $stmt = $db->prepare("
            UPDATE admin_access_logs 
            SET logout_time = NOW() 
            WHERE admin_id = ? AND logout_time IS NULL 
            ORDER BY login_time DESC 
            LIMIT 1
        ");
        
        $stmt->bind_param("i", $admin_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log logout: " . $e->getMessage());
        return false;
    }
}

function logActivity($db, $admin_id, $activity, $status = 'success') {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $location = getLocationFromIP($ip_address);
        $username = $_SESSION['username'] ?? 'Unknown';
        
        $stmt = $db->prepare("
            INSERT INTO admin_access_logs 
            (admin_id, username, login_time, ip_address, user_agent, location, activity, status) 
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "issssss", 
            $admin_id, 
            $username, 
            $ip_address, 
            $user_agent, 
            $location, 
            $activity, 
            $status
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

function getLocationFromIP($ip) {
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return 'Localhost';
    }
    
    try {
        // Using ipapi.co (free tier available)
        $response = @file_get_contents("http://ipapi.co/{$ip}/json/");
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['city']) && isset($data['country_name'])) {
                return $data['city'] . ', ' . $data['country_name'];
            }
        }
    } catch (Exception $e) {
        // Silent fail - use fallback
    }
    
    // Fallback: Simple IP-based location
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return "IP: {$parts[0]}.{$parts[1]}.*.*";
        }
    }
    
    return 'Unknown';
}
?>