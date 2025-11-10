<?php
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
session_start();
// Add this at the top of settings.php for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

        .table th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
        }

        .table td {
            padding: 12px;
            border-color: rgba(0,0,0,0.05);
            vertical-align: middle;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
        }

        /* Modern Button Styles */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            z-index: 1;
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
            font-size: 0.9rem;
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
            padding: 12px 16px;
            transition: var(--transition);
            background-color: var(--light-bg);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
            background-color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        /* Table Hover Effects */
        .table-hover tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            transition: var(--transition);
        }

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border: none;
            padding: 20px 25px;
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

        /* Badge Customization */
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
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

        /* Table Head */
        .table-dark {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
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
                padding: 8px 15px;
                font-size: 0.875rem;
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
                    <div class="bg-light rounded h-100 p-4">
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

                        <!-- Logs Table -->
                        <div class="table-responsive">
                            <table class="table table-border table-hover" id="accessLogsTable">
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
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $location = htmlspecialchars($log['location'] ?? 'Unknown');
                                                    if (!empty($log['map_link'])) {
                                                        echo '<a href="' . $log['map_link'] . '" target="_blank" class="location-link">' . $location . ' <i class="fas fa-map-marker-alt"></i></a>';
                                                    } else {
                                                        echo $location;
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($log['activity'] ?? 'Login'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $log['status'] === 'success' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst($log['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($log['logout_time']) {
                                                        $login = new DateTime($log['login_time']);
                                                        $logout = new DateTime($log['logout_time']);
                                                        $interval = $login->diff($logout);
                                                        echo $interval->format('%hh %im %ss');
                                                    } else {
                                                        echo '<span class="badge bg-warning">Active</span>';
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

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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