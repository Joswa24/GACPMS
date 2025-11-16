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
        
        // Extract Philippine administrative divisions
        $barangay = $address['suburb'] ?? $address['village'] ?? $address['hamlet'] ?? $address['neighbourhood'] ?? '';
        $municipality = $address['town'] ?? $address['municipality'] ?? $address['city_district'] ?? '';
        $cityProvince = $address['city'] ?? $address['state'] ?? $address['province'] ?? '';
        $country = $address['country'] ?? '';
        
        // Special handling for Philippine addresses
        // If city is present and it's a highly urbanized city, it serves as both city and province
        if (isset($address['city']) && isset($address['state'])) {
            // Check if city and state are different (e.g., Cebu City, Cebu Province)
            if ($address['city'] !== $address['state']) {
                $cityProvince = $address['city'] . ', ' . $address['state'];
            } else {
                $cityProvince = $address['city'];
            }
        }
        
        // Build location string in the requested format: Barangay, Municipality, City/Province, Country
        $parts = [];
        if (!empty($barangay)) $parts[] = $barangay;
        if (!empty($municipality)) $parts[] = $municipality;
        if (!empty($cityProvince)) $parts[] = $cityProvince;
        if (!empty($country)) $parts[] = $country;
        
        return [
            'display_name' => $data['display_name'],
            'address' => $address,
            'specific_location' => implode(', ', $parts),
            'barangay' => $barangay,
            'municipality' => $municipality,
            'city_province' => $cityProvince,
            'country' => $country,
            'formatted_address' => [
                'barangay' => $barangay,
                'municipality' => $municipality,
                'city_province' => $cityProvince,
                'country' => $country
            ]
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
    $locationJson = null;
    
    if ($geoData) {
        // Format IP location in the same structure as GPS location
        $formattedLocation = [
            'barangay' => '',
            'municipality' => $geoData['city'] ?? '',
            'city_province' => $geoData['region'] ?? '',
            'country' => $geoData['country'] ?? ''
        ];
        
        $location = $geoData['city'] . ', ' . $geoData['region'] . ', ' . $geoData['country'];
        $locationJson = json_encode([
            'source' => 'IP',
            'formatted_address' => $formattedLocation
        ] + $geoData);
    } else {
        $locationJson = json_encode(['error' => 'Unable to fetch location']);
    }
    
    $loginTime = date('Y-m-d H:i:s');
    
    try {
        $stmt = $db->prepare("INSERT INTO admin_access_logs 
            (admin_id, username, login_time, ip_address, user_agent, location, location_details, activity, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
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
        .modern-table th:nth-child(1), .modern-table td:nth-child(1) { width: 4%; } /* ID */
        .modern-table th:nth-child(2), .modern-table td:nth-child(2) { width: 9%; } /* Username */
        .modern-table th:nth-child(3), .modern-table td:nth-child(3) { width: 12%; } /* Login Time */
        .modern-table th:nth-child(4), .modern-table td:nth-child(4) { width: 12%; } /* Logout Time */
        .modern-table th:nth-child(5), .modern-table td:nth-child(5) { width: 18%; } /* IP Address */
        .modern-table th:nth-child(6), .modern-table td:nth-child(6) { width: 17%; } /* Location */
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
        .ip-address-column {
            max-width: 150px;
        }

        .ip-display {
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            background: rgba(0, 0, 0, 0.05);
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            font-size: 0.8em;
        }

        .encrypted-ip {
            color: #6c757d;
            font-style: italic;
        }

        .actual-ip {
            color: var(--dark-text);
            font-weight: 500;
        }

        .ip-toggle {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.3em 0.6em;
            font-size: 0.75em;
            transition: var(--transition);
            margin-left: 5px;
        }

        .ip-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(28, 200, 138, 0.3);
            color: white;
        }

        .ip-toggle.btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
        }

        .ip-toggle.btn-warning:hover {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            box-shadow: 0 4px 8px rgba(246, 194, 62, 0.3);
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
        
        /* Location modal styles */
        #locationModal .modal-dialog {
            max-width: 90%; /* Make modal wider for full map view */
        }
        

        #locationMap {
            height: 500px; /* Increase map height */
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
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
                        
                        <div class="modern-table-container">
                            <div class="table-wrapper">
                                <div class="table-responsive">
                                    <table class="modern-table" id="accessLogsTable">
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
                                                    <td colspan="9">
                                                        <div class="empty-state">
                                                            <i class="fas fa-clipboard-list"></i>
                                                            <h5>No access logs found</h5>
                                                            <p class="text-muted">There are no access logs to display at this time.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($logs as $index => $log): ?>
                                                    <tr class="table-<?php echo $log['id'];?>" 
                                                        data-date="<?php echo date('Y-m-d', strtotime($log['login_time'])); ?>" 
                                                        data-username="<?php echo strtolower(htmlspecialchars($log['username'] ?? '')); ?>" 
                                                        data-status="<?php echo $log['status']; ?>" 
                                                        data-activity="<?php echo strtolower(htmlspecialchars($log['activity'] ?? '')); ?>">
                                                        <td class="text-center fw-bold"><?php echo $index + 1; ?></td>
                                                        <td>
                                                            <span class="fw-medium"><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-medium"><?php echo date('M j, Y', strtotime($log['login_time'])); ?></span>
                                                                <small class="text-muted"><?php echo date('g:i A', strtotime($log['login_time'])); ?></small>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php if ($log['logout_time']): ?>
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-medium"><?php echo date('M j, Y', strtotime($log['logout_time'])); ?></span>
                                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($log['logout_time'])); ?></small>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="badge badge-warning">Still Active</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="ip-address-column">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="ip-display encrypted-ip" id="ip-<?php echo $log['id']; ?>">
                                                                    •••••••••••••••••
                                                                </span>
                                                                <button type="button" class="btn btn-sm ip-toggle toggle-ip" 
                                                                        data-ip="<?php echo htmlspecialchars($log['ip_address']); ?>"
                                                                        data-target="ip-<?php echo $log['id']; ?>">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
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
                                                                echo '<i class="fas fa-map-marked-alt"></i> View Map';
                                                                echo '</button>';
                                                                echo '<div class="mt-1"><small class="text-success"><i class="fas fa-satellite-dish"></i> GPS Location</small></div>';
                                                            } else {
                                                                echo '<div class="table-cell-truncate">' . $summaryLocation . '</div>';
                                                                if ($locationData && isset($locationData['source'])) {
                                                                    echo '<div class="mt-1"><small class="text-muted"><i class="fas fa-wifi"></i> IP Location</small></div>';
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
                        <i class="fas fa-globe-americas"></i> Location Map View
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="locationMap"></div>
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
                // Skip empty state row
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

        // Update showLocationModal function

        window.showLocationModal = function(locationJson) {
            try {
                const data = JSON.parse(locationJson);

                // Initialize Leaflet map
                const map = L.map('locationMap').setView([data.lat, data.lon], 15);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 18
                }).addTo(map);
                
                // Add a marker for the location
                const marker = L.marker([data.lat, data.lon]).addTo(map);
                
                // Add a popup to the marker with location details
                if (data.formatted_address) {
                    const { barangay, municipality, city_province, country } = data.formatted_address;
                    
                    const locationParts = [];
                    if (barangay) locationParts.push(barangay);
                    if (municipality) locationParts.push(municipality);
                    if (city_province) locationParts.push(city_province);
                    if (country) locationParts.push(country);
                    
                    const formattedLocation = locationParts.length > 0 ? locationParts.join(', ') : 'Unknown Location';
                    
                    const popupContent = `
                        <div style="font-family: Arial, sans-serif;">
                            <h5>Location Details</h5>
                            <p><strong>Address:</strong> ${formattedLocation}</p>
                            <p><strong>Coordinates:</strong> ${data.lat}, ${data.lon}</p>
                            ${data.accuracy_meters ? `<p><strong>Accuracy:</strong> ±${data.accuracy_meters} meters</p>` : ''}
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                }
                
                // Generate Google Maps link
                const mapsLink = `https://www.google.com/maps?q=${data.lat},${data.lon}`;
                document.getElementById('modalMapsLink').href = mapsLink;

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
    // IP Address toggle function
    $(document).on('click', '.toggle-ip', function() {
        const button = $(this);
        const targetId = button.data('target');
        const actualIp = button.data('ip');
        const ipSpan = $('#' + targetId);
        const icon = button.find('i');
        
        if (ipSpan.hasClass('encrypted-ip')) {
            // Show actual IP
            ipSpan.removeClass('encrypted-ip')
                .addClass('actual-ip')
                .text(actualIp);
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            button.addClass('btn-warning').removeClass('ip-toggle');
            
            // Auto hide after 10 seconds (longer than password for better readability)
            setTimeout(() => {
                if (ipSpan.hasClass('actual-ip')) {
                    ipSpan.removeClass('actual-ip')
                        .addClass('encrypted-ip')
                        .html('••••••••••••••••');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    button.removeClass('btn-warning').addClass('ip-toggle');
                }
            }, 10000);
        } else {
            // Hide IP
            ipSpan.removeClass('actual-ip')
                .addClass('encrypted-ip')
                .html('••••••••••••••••');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            button.removeClass('btn-warning').addClass('ip-toggle');
        }
    });
    </script>
</body>
</html>