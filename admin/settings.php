<?php
// Include connection
include '../connection.php';
session_start();
// Add this at the top of settings.php for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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

// Function to reverse geocode coordinates to get specific location
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

// Function to log admin access with geolocation
function logAdminAccess($db, $adminId, $username, $status = 'success', $activity = 'Login') {
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // Get geolocation data
    $geoData = getGeolocation($ipAddress);
    $location = 'Unknown';
    
    if ($geoData) {
        $location = $geoData['city'] . ', ' . $geoData['region'] . ', ' . $geoData['country'];
        
        // Store detailed geolocation in database
        $locationJson = json_encode($geoData);
    } else {
        $locationJson = json_encode(['error' => 'Unable to fetch location']);
    }
    
    $loginTime = date('Y-m-d H:i:s');
    
    try {
        $stmt = $db->prepare("INSERT INTO admin_access_logs 
            (admin_id, username, login_time, ip_address, user_agent, location, location_details, activity, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("issssssss", 
            $adminId, 
            $username, 
            $loginTime, 
            $ipAddress, 
            $userAgent, 
            $location, 
            $locationJson,
            $activity, 
            $status
        );
        
        $stmt->execute();
        return $db->insert_id;
    } catch (Exception $e) {
        error_log("Failed to log admin access: " . $e->getMessage());
        return false;
    }
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Create the admin_access_logs table if it doesn't exist
try {
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS admin_access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        username VARCHAR(255),
        login_time DATETIME,
        logout_time DATETIME,
        ip_address VARCHAR(45),
        user_agent TEXT,
        location VARCHAR(255),
        location_details JSON,
        activity TEXT,
        status ENUM('success', 'failed') DEFAULT 'success'
    )";
    $db->query($createTableSQL);
    
    // Add location_details column if it doesn't exist
    $checkColumn = $db->query("SHOW COLUMNS FROM admin_access_logs LIKE 'location_details'");
    if ($checkColumn->num_rows == 0) {
        $db->query("ALTER TABLE admin_access_logs ADD COLUMN location_details JSON AFTER location");
    }
} catch (Exception $e) {
    error_log("Failed to create admin_access_logs table: " . $e->getMessage());
}

// Log current access if not already logged for this session
if (!isset($_SESSION['access_logged'])) {
    logAdminAccess($db, $_SESSION['user_id'], $_SESSION['username'], 'success', 'Dashboard Access');
    $_SESSION['access_logged'] = true;
}

// Handle clear old logs request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_old_logs') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response = ['status' => 'error', 'message' => 'Invalid request. Please try again.'];
        echo json_encode($response);
        exit();
    }
    
    $days = isset($_POST['days']) ? (int)$_POST['days'] : 30;
    
    try {
        $stmt = $db->prepare("DELETE FROM admin_access_logs WHERE login_time < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->bind_param("i", $days);
        $stmt->execute();
        
        $deletedRows = $stmt->affected_rows;
        
        $response = [
            'status' => 'success', 
            'message' => "Successfully deleted {$deletedRows} log entries older than {$days} days."
        ];
        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => 'Failed to clear old logs: ' . $e->getMessage()];
        echo json_encode($response);
        exit();
    }
}

// Fetch admin access logs
 $logs = [];
try {
    $sql = "SELECT al.*, u.username 
            FROM admin_access_logs al 
            LEFT JOIN user u ON al.admin_id = u.id 
            ORDER BY al.login_time DESC 
            LIMIT 100";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Parse location details if available
            if (!empty($row['location_details'])) {
                $locationDetails = json_decode($row['location_details'], true);
                if (isset($locationDetails['lat']) && isset($locationDetails['lon'])) {
                    $row['map_link'] = "https://www.google.com/maps?q={$locationDetails['lat']},{$locationDetails['lon']}";
                }
            }
            $logs[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Failed to fetch admin access logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            font-family: 'Inter', sans-serif;
            color: var(--dark-text);
        }

        .content {
            background: transparent;
        }

        .bg-light {
            background-color: var(--light-bg) !important;
            border-radius: var(--border-radius);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: white;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        /* Modern Table Styles */
        .modern-table-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            background: white;
            position: relative;
            width: 100%;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow-x: hidden; /* Remove horizontal scrolling */
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--icon-color) #f1f1f1;
        }

        .modern-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            table-layout: fixed; /* Fixed layout to control column widths */
        }

        .modern-table thead th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 8px; /* Reduced padding */
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.85rem; /* Smaller font size */
        }

        .modern-table thead th:first-child {
            border-top-left-radius: var(--border-radius);
        }

        .modern-table thead th:last-child {
            border-top-right-radius: var(--border-radius);
        }

        .modern-table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .modern-table tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .modern-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: var(--border-radius);
        }

        .modern-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: var(--border-radius);
        }

        .modern-table td {
            padding: 10px 8px; /* Reduced padding */
            border: none;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.85rem; /* Smaller font size */
        }

        /* Optimized column widths to fit all columns */
        .modern-table th:nth-child(1), .modern-table td:nth-child(1) { width: 5%; } /* ID */
        .modern-table th:nth-child(2), .modern-table td:nth-child(2) { width: 12%; } /* Username */
        .modern-table th:nth-child(3), .modern-table td:nth-child(3) { width: 15%; } /* Login Time */
        .modern-table th:nth-child(4), .modern-table td:nth-child(4) { width: 15%; } /* Logout Time */
        .modern-table th:nth-child(5), .modern-table td:nth-child(5) { width: 10%; } /* IP Address */
        .modern-table th:nth-child(6), .modern-table td:nth-child(6) { width: 15%; } /* Location */
        .modern-table th:nth-child(7), .modern-table td:nth-child(7) { width: 10%; } /* Activity */
        .modern-table th:nth-child(8), .modern-table td:nth-child(8) { width: 8%; } /* Status */
        .modern-table th:nth-child(9), .modern-table td:nth-child(9) { width: 10%; } /* Duration */

        .badge {
            font-size: 0.75em; /* Smaller badge */
            border-radius: 8px;
            padding: 0.4em 0.6em; /* Smaller padding */
            font-weight: 500;
        }

        /* Modern Button Styles */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            padding: 8px 15px; /* Smaller padding */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-size: 0.85rem; /* Smaller font */
        }

        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn i {
            font-size: 0.8rem; /* Smaller icon */
        }

        /* Clear Button */
        .btn-clear {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-clear:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
            color: white;
        }

        /* Stats Cards */
        .stats-card {
            transition: var(--transition);
            border-radius: var(--border-radius);
            overflow: hidden;
            position: relative;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stats-card:hover::before {
            opacity: 1;
        }

        .stats-card .card-body {
            position: relative;
            z-index: 1;
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #e3e6f0;
            padding: 10px 12px; /* Smaller padding */
            transition: var(--transition);
            background-color: var(--light-bg);
            font-size: 0.85rem; /* Smaller font */
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
            background-color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 6px; /* Smaller margin */
            font-size: 0.85rem; /* Smaller font */
        }

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border: none;
            padding: 15px 20px; /* Smaller padding */
        }

        /* Back to Top Button */
        .back-to-top {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)) !important;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .back-to-top:hover {
            transform: translateY(-3px);
        }

        /* SweetAlert customization */
        .swal2-popup {
            border-radius: var(--border-radius) !important;
        }

        /* Loading Spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* Status Badge Colors */
        .badge-success {
            background: linear-gradient(135deg, var(--success-color), #17a673);
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
        }

        .badge-info {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
        }

        .badge-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        /* Empty State */
        .text-center.text-muted {
            padding: 2rem;
        }

        /* Location link style */
        .location-link {
            color: var(--icon-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .location-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 0.75rem;
            }
            
            .form-control, .form-select {
                padding: 8px 10px;
                font-size: 0.75rem;
            }
            
            .modern-table th, .modern-table td {
                padding: 8px 5px;
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.65em;
                padding: 0.3em 0.5em;
            }
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-info:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }
        
        /* Table cell truncation for long text */
        .table-cell-truncate {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* IP address badge styling */
        .ip-badge {
            font-family: 'Courier New', monospace;
            font-size: 0.75em; /* Smaller font */
            background: linear-gradient(135deg, #6c757d, #5a6268);
            padding: 0.3em 0.5em; /* Smaller padding */
        }
        
        /* Location button styling */
        .location-btn {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            border: none;
            border-radius: 6px; /* Smaller border radius */
            padding: 0.3em 0.6em; /* Smaller padding */
            font-size: 0.75em; /* Smaller font */
            transition: var(--transition);
        }
        
        .location-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(54, 185, 204, 0.3);
        }
        
        /* Status badge animation */
        .badge {
            position: relative;
            overflow: hidden;
        }
        
        .badge::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s;
            opacity: 0;
        }
        
        .badge:hover::after {
            animation: shine 0.5s ease-in-out;
        }
        
        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
                opacity: 0;
            }
        }
        
        /* Table container fix */
        .table-wrapper {
            width: 100%;
            overflow: hidden; /* Changed from overflow-x: auto */
            margin-bottom: 1rem;
        }
        
        /* Ensure table headers are always visible */
        .modern-table thead {
            display: table-header-group;
        }
        
        .modern-table thead th {
            visibility: visible !important;
            display: table-cell !important;
        }
        
        /* Location modal styles */
        #locationModal .modal-dialog {
            max-width: 800px;
        }
        
        #locationMap {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .location-detail-item {
            display: flex;
            margin-bottom: 8px;
        }
        
        .location-detail-label {
            font-weight: 600;
            width: 120px;
            color: var(--dark-text);
            font-size: 0.85rem; /* Smaller font */
        }
        
        .location-detail-value {
            flex: 1;
            font-size: 0.85rem; /* Smaller font */
        }
        
        /* Modern card design with glassmorphism effect */
        .modern-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(31, 38, 135, 0.2);
        }
        
        /* Enhanced table design */
        .enhanced-table {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            background: white;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .enhanced-table::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--icon-color), var(--secondary-color));
            z-index: 1;
        }
        
        /* Compact table design */
        .compact-table {
            font-size: 0.8rem;
        }
        
        .compact-table th {
            padding: 8px 6px;
            font-size: 0.8rem;
        }
        
        .compact-table td {
            padding: 8px 6px;
            font-size: 0.8rem;
        }
        
        /* Responsive table adjustments */
        @media (max-width: 1200px) {
            .modern-table th:nth-child(1), .modern-table td:nth-child(1) { width: 5%; }
            .modern-table th:nth-child(2), .modern-table td:nth-child(2) { width: 12%; }
            .modern-table th:nth-child(3), .modern-table td:nth-child(3) { width: 14%; }
            .modern-table th:nth-child(4), .modern-table td:nth-child(4) { width: 14%; }
            .modern-table th:nth-child(5), .modern-table td:nth-child(5) { width: 10%; }
            .modern-table th:nth-child(6), .modern-table td:nth-child(6) { width: 15%; }
            .modern-table th:nth-child(7), .modern-table td:nth-child(7) { width: 10%; }
            .modern-table th:nth-child(8), .modern-table td:nth-child(8) { width: 10%; }
            .modern-table th:nth-child(9), .modern-table td:nth-child(9) { width: 10%; }
        }
        
        @media (max-width: 992px) {
            .modern-table th:nth-child(1), .modern-table td:nth-child(1) { width: 5%; }
            .modern-table th:nth-child(2), .modern-table td:nth-child(2) { width: 12%; }
            .modern-table th:nth-child(3), .modern-table td:nth-child(3) { width: 13%; }
            .modern-table th:nth-child(4), .modern-table td:nth-child(4) { width: 13%; }
            .modern-table th:nth-child(5), .modern-table td:nth-child(5) { width: 10%; }
            .modern-table th:nth-child(6), .modern-table td:nth-child(6) { width: 17%; }
            .modern-table th:nth-child(7), .modern-table td:nth-child(7) { width: 10%; }
            .modern-table th:nth-child(8), .modern-table td:nth-child(8) { width: 10%; }
            .modern-table th:nth-child(9), .modern-table td:nth-child(9) { width: 10%; }
        }
        
        @media (max-width: 768px) {
            .modern-table th:nth-child(1), .modern-table td:nth-child(1) { width: 5%; }
            .modern-table th:nth-child(2), .modern-table td:nth-child(2) { width: 12%; }
            .modern-table th:nth-child(3), .modern-table td:nth-child(3) { width: 12%; }
            .modern-table th:nth-child(4), .modern-table td:nth-child(4) { width: 12%; }
            .modern-table th:nth-child(5), .modern-table td:nth-child(5) { width: 10%; }
            .modern-table th:nth-child(6), .modern-table td:nth-child(6) { width: 17%; }
            .modern-table th:nth-child(7), .modern-table td:nth-child(7) { width: 10%; }
            .modern-table th:nth-child(8), .modern-table td:nth-child(8) { width: 11%; }
            .modern-table th:nth-child(9), .modern-table td:nth-child(9) { width: 11%; }
        }
        
        /* Table container with gradient border */
        .gradient-border-table {
            position: relative;
            border-radius: var(--border-radius);
            background: white;
            padding: 2px;
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
        }
        
        .gradient-border-table-inner {
            background: white;
            border-radius: calc(var(--border-radius) - 2px);
            overflow: hidden;
        }
        
        /* Enhanced hover effect for table rows */
        .modern-table tbody tr {
            position: relative;
        }
        
        .modern-table tbody tr::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--icon-color), var(--secondary-color));
            transition: width 0.3s ease;
        }
        
        .modern-table tbody tr:hover::after {
            width: 100%;
        }
        
        /* Modern scrollbar */
        .modern-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .modern-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .modern-scrollbar::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 10px;
        }
        
        .modern-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4 modern-card">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Admin Access Log</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button class="btn btn-sm btn-clear" onclick="clearOldLogs()">
                                    <i class="fas fa-trash"></i> Clear Old Logs
                                </button>
                            </div>
                        </div>
                        <hr>
                        
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="dateFilter" class="form-label">Date Range</label>
                                <input type="date" class="form-control" id="dateFilter" onchange="filterLogs()">
                            </div>
                            <div class="col-md-3">
                                <label for="userFilter" class="form-label">Username</label>
                                <input type="text" class="form-control" id="userFilter" placeholder="Filter by username" onkeyup="filterLogs()">
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-control" id="statusFilter" onchange="filterLogs()">
                                    <option value="">All Status</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="activityFilter" class="form-label">Activity</label>
                                <input type="text" class="form-control" id="activityFilter" placeholder="Filter by activity" onkeyup="filterLogs()">
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Total Logins</h6>
                                                <h4 class="mb-0"><?php echo count($logs); ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Successful</h6>
                                                <h4 class="mb-0"><?php echo count(array_filter($logs, function($log) { return $log['status'] === 'success'; })); ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Failed</h6>
                                                <h4 class="mb-0"><?php echo count(array_filter($logs, function($log) { return $log['status'] === 'failed'; })); ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Active Today</h6>
                                                <h4 class="mb-0"><?php 
                                                    $today = date('Y-m-d');
                                                    echo count(array_filter($logs, function($log) use ($today) { 
                                                        return date('Y-m-d', strtotime($log['login_time'])) === $today; 
                                                    })); 
                                                ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Logs Table -->
                        <div class="gradient-border-table">
                            <div class="gradient-border-table-inner">
                                <div class="table-wrapper modern-scrollbar">
                                    <div class="table-responsive">
                                        <table class="modern-table compact-table" id="accessLogsTable">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Username</th>
                                                    <th scope="col">Login Time</th>
                                                    <th scope="col">Logout Time</th>
                                                    <th scope="col">IP Address</th>
                                                    <th scope="col">Location</th>
                                                    <th scope="col">Activity</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Duration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($logs)): ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center py-4">
                                                            <div class="d-flex flex-column align-items-center">
                                                                <i class="fas fa-clipboard-list text-muted mb-2" style="font-size: 2rem;"></i>
                                                                <p class="text-muted">No access logs found</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($logs as $index => $log): ?>
                                                        <tr class="table-<?php echo $log['id'];?>" data-date="<?php echo date('Y-m-d', strtotime($log['login_time'])); ?>" data-username="<?php echo strtolower(htmlspecialchars($log['username'] ?? '')); ?>" data-status="<?php echo $log['status']; ?>" data-activity="<?php echo strtolower(htmlspecialchars($log['activity'] ?? '')); ?>">
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></strong>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M j, Y g:i A', strtotime($log['login_time'])); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo $log['logout_time'] ? date('M j, Y g:i A', strtotime($log['logout_time'])) : 'Still Active'; ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge ip-badge"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                $summaryLocation = htmlspecialchars($log['location'] ?? 'Unknown Location');
                                                                $locationDetailsJson = htmlspecialchars($log['location_details'] ?? '{}');

                                                                // Check if location details are available and valid
                                                                $locationData = json_decode($log['location_details'], true);
                                                                if ($locationData && isset($locationData['source']) && $locationData['source'] === 'GPS') {
                                                                    echo '<div class="table-cell-truncate mb-1">' . $summaryLocation . '</div>';
                                                                    echo '<button class="btn btn-sm location-btn" onclick="showLocationModal(\'' . $locationDetailsJson . '\')">';
                                                                    echo '<i class="fas fa-map-marked-alt"></i> View';
                                                                    echo '</button>';
                                                                    echo '<div class="mt-1"><small class="text-success"><i class="fas fa-satellite-dish"></i> GPS</small></div>';
                                                                } else {
                                                                    echo '<div class="table-cell-truncate">' . $summaryLocation . '</div>';
                                                                    if ($locationData && isset($locationData['source'])) {
                                                                        echo '<div class="mt-1"><small class="text-muted"><i class="fas fa-wifi"></i> IP</small></div>';
                                                                    }
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info"><?php echo htmlspecialchars($log['activity'] ?? 'Login'); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge <?php echo $log['status'] === 'success' ? 'badge-success' : 'badge-danger'; ?>">
                                                                    <?php echo ucfirst($log['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                if ($log['logout_time']) {
                                                                    $login = new DateTime($log['login_time']);
                                                                    $logout = new DateTime($log['logout_time']);
                                                                    $interval = $login->diff($logout);
                                                                    echo '<span class="badge bg-secondary">' . $interval->format('%hh %im %ss') . '</span>';
                                                                } else {
                                                                    echo '<span class="badge badge-warning">Active</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- Location Details Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationModalLabel">
                        <i class="fas fa-globe-americas"></i> Detailed Location Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Geolocation Details</h6>
                            <div class="location-details-container">
                                <div class="location-detail-item">
                                    <div class="location-detail-label">Source:</div>
                                    <div class="location-detail-value" id="modalSource">-</div>
                                </div>
                                <div class="location-detail-item">
                                    <div class="location-detail-label">Coordinates:</div>
                                    <div class="location-detail-value" id="modalCoords">-</div>
                                </div>
                                <div class="location-detail-item">
                                    <div class="location-detail-label">Accuracy:</div>
                                    <div class="location-detail-value" id="modalAccuracy">-</div>
                                </div>
                                <div class="location-detail-item">
                                    <div class="location-detail-label">Country:</div>
                                    <div class="location-detail-value" id="modalCountry">-</div>
                                </div>
                                <div class="location-detail-item">
                                    <div class="location-detail-label">City/Province:</div>
                                    <div class="location-detail-value" id="modalRegion">-</div>
                                </div>
                                <div class="location-detail-item">
                                    <div class="location-detail-label">Municipality:</div>
                                    <div class="location-detail-value" id="modalCity">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Map View</h6>
                            <div id="locationMap"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="modalMapsLink" href="" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Open in Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
    $(document).ready(function() {
        // Filter logs function
        window.filterLogs = function() {
            const dateFilter = document.getElementById('dateFilter').value;
            const userFilter = document.getElementById('userFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const activityFilter = document.getElementById('activityFilter').value.toLowerCase();
            
            const rows = document.querySelectorAll('#accessLogsTable tbody tr');
            
            rows.forEach(row => {
                // Skip the empty state row
                if (row.cells.length === 1) return;
                
                const dateValue = row.getAttribute('data-date');
                const usernameValue = row.getAttribute('data-username');
                const statusValue = row.getAttribute('data-status');
                const activityValue = row.getAttribute('data-activity');
                
                let showRow = true;
                
                if (dateFilter && dateValue !== dateFilter) {
                    showRow = false;
                }
                
                if (userFilter && !usernameValue.includes(userFilter)) {
                    showRow = false;
                }
                
                if (statusFilter && statusValue !== statusFilter) {
                    showRow = false;
                }
                
                if (activityFilter && !activityValue.includes(activityFilter)) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Clear old logs (older than specified days)
        window.clearOldLogs = function() {
            Swal.fire({
                title: 'Clear Old Logs',
                html: `
                    <p>Select how many days of logs to keep:</p>
                    <div class="form-group">
                        <select id="daysSelect" class="form-control">
                            <option value="7">Keep last 7 days</option>
                            <option value="15">Keep last 15 days</option>
                            <option value="30" selected>Keep last 30 days</option>
                            <option value="60">Keep last 60 days</option>
                            <option value="90">Keep last 90 days</option>
                        </select>
                    </div>
                    <p class="mt-3 text-warning">This action cannot be undone!</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Clear Logs',
                preConfirm: () => {
                    return document.getElementById('daysSelect').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const days = result.value;
                    
                    // Show loading state
                    Swal.fire({
                        title: 'Clearing logs...',
                        html: `Please wait while we clear logs older than ${days} days`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: { 
                            action: 'clear_old_logs',
                            days: days,
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to clear old logs. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        // Show location modal function
        window.showLocationModal = function(locationJson) {
            try {
                const data = JSON.parse(locationJson);

                // Populate the modal with data
                document.getElementById('modalSource').textContent = data.source || 'Unknown';
                
                if (data.lat && data.lon) {
                    document.getElementById('modalCoords').textContent = `${data.lat}, ${data.lon}`;
                    
                    if (data.accuracy_meters) {
                        document.getElementById('modalAccuracy').textContent = `±${data.accuracy_meters} meters`;
                    } else {
                        document.getElementById('modalAccuracy').textContent = 'N/A';
                    }
                    
                    // Initialize Leaflet map
                    const map = L.map('locationMap').setView([data.lat, data.lon], 15);
                    
                    // Add OpenStreetMap tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 18
                    }).addTo(map);
                    
                    // Add a marker for the location
                    const marker = L.marker([data.lat, data.lon]).addTo(map);
                    
                    // Add a popup to the marker
                    if (data.address) {
                        const popupContent = `
                            <div style="font-family: Arial, sans-serif;">
                                <h5>Location Details</h5>
                                <p><strong>Address:</strong> ${data.display_name || 'N/A'}</p>
                                <p><strong>Coordinates:</strong> ${data.lat}, ${data.lon}</p>
                            </div>
                        `;
                        marker.bindPopup(popupContent);
                    }
                    
                    // Generate Google Maps link
                    const mapsLink = `https://www.google.com/maps?q=${data.lat},${data.lon}`;
                    document.getElementById('modalMapsLink').href = mapsLink;
                } else {
                    document.getElementById('modalCoords').textContent = 'N/A';
                    document.getElementById('modalAccuracy').textContent = 'N/A';
                    document.getElementById('modalMapsLink').style.display = 'none';
                    
                    // Clear any existing map
                    document.getElementById('locationMap').innerHTML = '<div class="alert alert-warning">No location coordinates available</div>';
                }
                
                if (data.address) {
                    const addr = data.address;
                    document.getElementById('modalCountry').textContent = addr.country || 'N/A';
                    document.getElementById('modalRegion').textContent = addr.state || addr.province || 'N/A';
                    document.getElementById('modalCity').textContent = addr.city || addr.town || 'N/A';
                } else {
                    document.getElementById('modalCountry').textContent = 'N/A';
                    document.getElementById('modalRegion').textContent = 'N/A';
                    document.getElementById('modalCity').textContent = 'N/A';
                }

                // Show the modal
                const locationModal = new bootstrap.Modal(document.getElementById('locationModal'));
                locationModal.show();

            } catch (e) {
                console.error("Error parsing location data:", e);
                Swal.fire({
                    icon: 'error',
                    title: 'Data Error',
                    text: 'Could not display location details.'
                });
            }
        }
        
        // Auto-refresh logs every 30 seconds
        setInterval(() => {
            $.ajax({
                url: window.location.href,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // You can implement dynamic update here if needed
                        console.log('Logs updated');
                    }
                },
                error: function() {
                    console.log('Failed to update logs');
                }
            });
        }, 30000);
    });
    </script>
</body>
</html>