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




// Function to get geolocation data from IP address
function getGeolocation($ip) {
    // Use ip-api.com for geolocation (free tier)
    $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city,zip,lat,lon,timezone,query";
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($data['status'] === 'success') {
        return [
            'country' => $data['country'],
            'region' => $data['regionName'],
            'city' => $data['city'],
            'zip' => $data['zip'],
            'lat' => $data['lat'],
            'lon' => $data['lon'],
            'timezone' => $data['timezone'],
            'ip' => $data['query']
        ];
    }
    
    return null;
}

// Check if this is an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // Get logs without location data
    $sql = "SELECT id, ip_address FROM admin_access_logs WHERE location = 'Unknown' OR location IS NULL OR location = '' LIMIT 50";
    $result = $db->query($sql);
    
    $updated = 0;
    $failed = 0;
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ip = $row['ip_address'];
            $logId = $row['id'];
            
            // Get geolocation data
            $geoData = getGeolocation($ip);
            
            if ($geoData) {
                $location = $geoData['city'] . ', ' . $geoData['region'] . ', ' . $geoData['country'];
                $locationJson = json_encode($geoData);
                
                // Update the log with location data
                $updateSql = "UPDATE admin_access_logs SET location = ?, location_details = ? WHERE id = ?";
                $stmt = $db->prepare($updateSql);
                $stmt->bind_param("ssi", $location, $locationJson, $logId);
                
                if ($stmt->execute()) {
                    $updated++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
    }
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'updated' => $updated,
        'failed' => $failed,
        'message' => "Updated {$updated} logs with location data. Failed to update {$failed} logs."
    ]);
    exit;
}

// If not an AJAX request, redirect back
header('Location: admin_access_log.php');
exit;
?>