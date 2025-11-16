<?php
date_default_timezone_set('Asia/Manila'); // Change this to your timezone
session_start();
include '../connection.php';

// Function to reverse geocode coordinates to get specific location (from index.php)
function reverseGeocode($lat, $lon) {
    // Using OpenStreetMap's Nominatim API (free, no API key required)
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&zoom=16&addressdetails=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MyAdminAccessLog/1.0'); // Nominatim requires a user agent
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['address'])) {
        $address = $data['address'];
        
        // Try to build a specific location string
        $parts = [];
        if (isset($address['suburb']) || isset($address['town']) || isset($address['village'])) {
            $parts[] = $address['suburb'] ?? $address['town'] ?? $address['village'];
        }
        if (isset($address['city']) || isset($address['city_district'])) {
            $parts[] = $address['city'] ?? $address['city_district'];
        }
        if (isset($address['state']) || isset($address['province'])) {
            $parts[] = $address['state'] ?? $address['province'];
        }
        if (isset($address['country'])) {
            $parts[] = $address['country'];
        }
        
        return [
            'display_name' => $data['display_name'],
            'address' => $address,
            'specific_location' => implode(', ', $parts)
        ];
    }
    
    return null;
}

// Log the logout if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Set MySQL timezone to match PHP timezone
        $db->query("SET time_zone = '+08:00'"); // Adjust offset to match your timezone
        
        // Get current time in the correct timezone
        $currentDateTime = date('Y-m-d H:i:s');
        
        // Get location from IP (using the same approach as index.php)
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $location = 'Unknown';
        $locationJson = null;
        $locationSource = 'IP'; // Track the source of the location data

        // Check for client-side location data (if available from the login)
        if (!empty($_SESSION['user_lat']) && !empty($_SESSION['user_lon'])) {
            $lat = floatval($_SESSION['user_lat']);
            $lon = floatval($_SESSION['user_lon']);
            $accuracy = isset($_SESSION['user_accuracy']) ? floatval($_SESSION['user_accuracy']) : null;

            // Get specific address from coordinates
            $geoData = reverseGeocode($lat, $lon);
            
            if ($geoData) {
                $location = $geoData['specific_location']; // e.g., "Poblacion, Santa Fe, Cebu, Philippines"
                $locationSource = 'GPS';
                $locationJson = json_encode([
                    'source' => 'GPS',
                    'lat' => $lat,
                    'lon' => $lon,
                    'accuracy_meters' => $accuracy,
                    'address' => $geoData['address'],
                    'display_name' => $geoData['display_name']
                ]);
            } else {
                // Fallback if reverse geocoding fails
                $location = "Lat: {$lat}, Lon: {$lon}";
                $locationJson = json_encode(['error' => 'Reverse geocoding failed', 'lat' => $lat, 'lon' => $lon]);
            }
        } else {
            // Fallback to IP-based geolocation (same as index.php)
            if (function_exists('file_get_contents') && !in_array($ipAddress, ['127.0.0.1', '::1'])) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ]
                ]);
                
                $ipData = @file_get_contents("http://ip-api.com/json/{$ipAddress}", false, $context);
                if ($ipData) {
                    $ipInfo = json_decode($ipData);
                    if ($ipInfo && $ipInfo->status === 'success') {
                        $location = $ipInfo->city . ', ' . $ipInfo->regionName . ', ' . $ipInfo->country;
                        $locationJson = json_encode(['source' => 'IP'] + (array)$ipInfo);
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

// Redirect to login page (fixed from "index" to "index.php")
header('Location: index.php');
exit();
?>