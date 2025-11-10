<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

date_default_timezone_set('Asia/Manila');
session_start();

// Enhanced session debugging
error_log("Session in gate_logs: " . print_r($_SESSION, true));

// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    error_log("Access denied - redirecting to index.php");
    header("Location: index.php");
    exit();
}
// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// =====================================================================
// GET PENDING EXITS DETAILS - FIXED VERSION
// =====================================================================
// =====================================================================
// GET PENDING EXITS DETAILS - FIXED VERSION WITH TIME_IN
// =====================================================================
function getPendingExits($db) {
    $query = "SELECT gl.id_number, 
                     COALESCE(s.fullname, i.fullname, CONCAT_WS(' ', p.first_name, p.last_name), v.name, gl.name) as full_name,
                     gl.person_type, 
                     gl.time_in as entry_time
              FROM gate_logs gl
              LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
              LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
              LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
              LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
              WHERE gl.direction = 'IN' 
              AND DATE(gl.date) = CURDATE() 
              AND gl.id_number NOT IN (
                  SELECT id_number 
                  FROM gate_logs 
                  WHERE direction = 'OUT' 
                  AND DATE(date) = CURDATE()
              )
              ORDER BY gl.time_in DESC";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        error_log("Pending exits query failed: " . $db->error);
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}


// =====================================================================
// COMPLETE SESSION DESTRUCTION ON LOGOUT
// =====================================================================
if (isset($_POST['logout_request'])) {
    // Always allow logout, but track if there are pending exits for warning
    $pending_exits = getPendingExits($db);
    $has_pending_exits = count($pending_exits) > 0;
    
    if ($has_pending_exits) {
        // Store pending exits info in session to show warning after logout
        $_SESSION['pending_exits_warning'] = [
            'count' => count($pending_exits),
            'details' => $pending_exits,
            'logout_time' => date('Y-m-d H:i:s')
        ];
    }
    
    // COMPLETELY DESTROY ALL SESSION DATA
    $_SESSION = array(); // Clear all session variables
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
    
    // Redirect to login page with cache prevention headers
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: index.php");
    exit();
}

// =====================================================================
// IMPROVED MAIN LOGS QUERY WITH BETTER NAME RESOLUTION - FIXED
// =====================================================================

// Get filters from GET with defaults
$date_filter = $_GET['date'] ?? date('Y-m-d');
$type_filter = $_GET['type'] ?? 'all';
$direction_filter = $_GET['direction'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Build the main query with improved JOIN conditions - FIXED no middle_name
$query = "SELECT 
    gl.*,
    COALESCE(
        s.fullname,
        i.fullname,
        CONCAT_WS(' ', p.first_name, p.last_name),
        v.name,
        gl.name
    ) as full_name,
    gl.person_type,
    gl.direction,
    gl.department,
    gl.location,
    gl.time_in,
    gl.time_out,
    gl.date
FROM gate_logs gl
LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
WHERE 1=1";

$params = [];
$types = '';

// Apply filters safely
if (!empty($date_filter)) {
    $query .= " AND DATE(gl.date) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if ($type_filter !== 'all') {
    $query .= " AND gl.person_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if ($direction_filter !== 'all') {
    $query .= " AND gl.direction = ?";
    $params[] = $direction_filter;
    $types .= 's';
}

if (!empty($search_term)) {
    $query .= " AND (gl.id_number LIKE ? OR gl.name LIKE ? OR COALESCE(s.fullname, i.fullname, CONCAT_WS(' ', p.first_name, p.last_name), v.name, gl.name) LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$query .= " ORDER BY gl.date DESC";

// Execute query with improved error handling
$logs = [];
$query_success = false;

try {
    // First, let's test the connection and basic query
    if (!$db || $db->connect_error) {
        throw new Exception("Database connection failed: " . ($db->connect_error ?? 'Unknown error'));
    }

    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $db->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $execute_result = $stmt->execute();
    if (!$execute_result) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result set: " . $stmt->error);
    }
    
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $query_success = true;
    
    // Debug: Log the number of records found and sample data
    error_log("Gate logs query successful. Records found: " . count($logs));
    
    // Debug: Log first few records to see what's happening
    if (count($logs) > 0) {
        error_log("Sample log record: " . json_encode($logs[0]));
    }
    
} catch (Exception $e) {
    error_log("GATE LOGS ERROR: " . $e->getMessage());
    error_log("Query: " . $query);
    error_log("Params: " . json_encode($params));
    
    // Fallback: try a simple query to see if we can get any data
    try {
        $fallback_query = "SELECT * FROM gate_logs ORDER BY date DESC LIMIT 50";
        $fallback_result = $db->query($fallback_query);
        if ($fallback_result) {
            $logs = $fallback_result->fetch_all(MYSQLI_ASSOC);
            error_log("Fallback query successful. Records: " . count($logs));
        }
    } catch (Exception $fallback_e) {
        error_log("Fallback query also failed: " . $fallback_e->getMessage());
    }
}

// =====================================================================
// HELPER: GATE STATISTICS (single reusable function)
// =====================================================================
/**
 * Get gate statistics for a given date and optional department/location.
 *
 * Returns array with keys:
 *  - stats: ['total_entries','entries_in','entries_out','unique_people']
 *  - breakdown: ['student'=>int,'instructor'=>int,...]
 *  - pending_exits_count: int
 */
function getGateStats($db, $date, $department = null, $location = null) {
    $response = [
        'stats' => ['total_entries' => 0, 'entries_in' => 0, 'entries_out' => 0, 'unique_people' => 0],
        'breakdown' => [],
        'pending_exits_count' => 0
    ];

    try {
        // Base filters
        $where = "WHERE DATE(date) = ?";
        $types = "s";
        $params = [$date];

        if (!empty($department)) {
            $where .= " AND department = ?";
            $types .= "s";
            $params[] = $department;
        }
        if (!empty($location)) {
            $where .= " AND location = ?";
            $types .= "s";
            $params[] = $location;
        }

        // Totals
        $stats_sql = "SELECT 
            COUNT(*) as total_entries,
            SUM(CASE WHEN direction = 'IN' THEN 1 ELSE 0 END) as entries_in,
            SUM(CASE WHEN direction = 'OUT' THEN 1 ELSE 0 END) as entries_out,
            COUNT(DISTINCT id_number) as unique_people
            FROM gate_logs
            $where";
        $stmt = $db->prepare($stats_sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                $row = $res->fetch_assoc();
                if ($row) $response['stats'] = $row;
            }
            $stmt->close();
        }

        // Breakdown by person_type
        $break_sql = "SELECT person_type, COUNT(*) as cnt FROM gate_logs $where GROUP BY person_type";
        $stmt = $db->prepare($break_sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $response['breakdown'][$r['person_type']] = (int)$r['cnt'];
                }
            }
            $stmt->close();
        }

        // Pending exits count (entrants without corresponding exit same day)
        // Build pending subquery filters similarly
        $pending_where = "WHERE direction = 'IN' AND DATE(date) = ?";
        $pending_types = "s";
        $pending_params = [$date];
        if (!empty($department)) {
            $pending_where .= " AND department = ?";
            $pending_types .= "s";
            $pending_params[] = $department;
        }
        if (!empty($location)) {
            $pending_where .= " AND location = ?";
            $pending_types .= "s";
            $pending_params[] = $location;
        }

        $pending_sql = "SELECT COUNT(*) as pending_exits
            FROM gate_logs gl
            $pending_where
            AND gl.id_number NOT IN (
                SELECT id_number FROM gate_logs WHERE direction = 'OUT' AND DATE(date) = ?" .
                (empty($department) ? "" : " AND department = ?") .
                (empty($location) ? "" : " AND location = ?") .
            ")";
        // Build pending bind params (IN filters then OUT filters)
        $bindParams = [$date];
        if (!empty($department)) $bindParams[] = $department;
        if (!empty($location)) $bindParams[] = $location;
        // then OUT filters
        $bindParams[] = $date;
        if (!empty($department)) $bindParams[] = $department;
        if (!empty($location)) $bindParams[] = $location;

        $types_for_pending = str_repeat('s', count($bindParams));
        $stmt = $db->prepare($pending_sql);
        if ($stmt) {
            $stmt->bind_param($types_for_pending, ...$bindParams);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                $r = $res->fetch_assoc();
                $response['pending_exits_count'] = (int)($r['pending_exits'] ?? 0);
            }
            $stmt->close();
        }

    } catch (Exception $e) {
        error_log("getGateStats error: " . $e->getMessage());
    }

    return $response;
}

// ============================================
// STATISTICS QUERIES (REPLACED BY HELPER)
// ============================================
 $today = date('Y-m-d');

// Use helper to get stats, breakdown and pending count (no department/location filters for overview)
 $gateStats = getGateStats($db, $today);
 $stats = $gateStats['stats'] ?? ['total_entries' => 0, 'entries_in' => 0, 'entries_out' => 0, 'unique_people' => 0];
 $breakdown = $gateStats['breakdown'] ?? [];
 $pending_exits_count = $gateStats['pending_exits_count'] ?? 0;

// Get pending exits details for the modal (function already exists)
 $pending_exits_details = getPendingExits($db);

// Function to sanitize output
function sanitizeOutput($output) {
    return htmlspecialchars($output ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper function to get icons for person types
function getPersonTypeIcon($type) {
    $icons = [
        'student' => 'user-graduate',
        'instructor' => 'chalkboard-teacher',
        'personell' => 'user-tie',
        'visitor' => 'user-clock'
    ];
    return $icons[$type] ?? 'user';
}

// Function to format time correctly (fixing the 3-hour time difference)
function formatTime($time) {
    if (empty($time) || $time == '00:00:00' || $time == '?' || $time == '0000-00-00 00:00:00') {
        return '-';
    }
    try {
        // Convert to Manila time (already set in date_default_timezone_set)
        $dateTime = new DateTime($time);
        return $dateTime->format('h:i A');
    } catch (Exception $e) {
        return '-';
    }
}

// Debug function to check name resolution
function debugNameResolution($log) {
    $debug_info = [
        'id_number' => $log['id_number'] ?? 'N/A',
        'person_type' => $log['person_type'] ?? 'N/A',
        'person_id' => $log['person_id'] ?? 'N/A',
        'full_name' => $log['full_name'] ?? 'N/A',
        'gl_name' => $log['name'] ?? 'N/A'
    ];
    return $debug_info;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Gate Access Logs</title>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e4652aff;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 20px;
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            color: var(--icon-color);
        }

        .stat-card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: none;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .filter-section {
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-container {
            margin: 20px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: var(--icon-color);
            border: none;
            padding: 15px;
            font-weight: 600;
        }

        .table td {
            padding: 12px 15px;
            border-color: #f0f0f0;
        }

        .log-row {
            transition: var(--transition);
        }

        .log-row:hover {
            background-color: var(--light-bg);
        }

        .badge-entry {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .badge-exit {
            background: linear-gradient(135deg, #f72585, #7209b7);
            color: white;
        }

        .badge-student {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .badge-instructor {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            color: white;
        }

        .badge-personell {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
        }

        .badge-visitor {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .badge-pending {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
            animation: pulse 2s infinite;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            color: white;
        }

        /* Time column specific styles */
        .time-column {
            text-align: center;
            min-width: 120px;
        }

        .time-header {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .time-layout {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .time-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .time-divider {
            width: 1px;
            height: 20px;
            background-color: #dee2e6;
            margin: 0 8px;
        }

        .time-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            min-width: 30px;
        }

        .time-value {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .time-in {
            color: #198754;
        }

        .time-out {
            color: #fd7e14;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid var(--accent-color);
            padding: 10px 15px;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.1);
        }

        .breakdown-item {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin: 5px;
            flex: 1;
            min-width: 120px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--dark-text);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .pending-alert {
            border-left: 4px solid #e74a3b;
            background-color: #f8d7da;
        }

        .warning-alert {
            border-left: 4px solid #f6c23e;
            background-color: #fff3cd;
            color: #856404;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
            }
            
            .header-section {
                padding: 20px;
            }
            
            .filter-section {
                margin: 10px;
                padding: 20px;
            }
            
            .table-container {
                margin: 10px;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
            
            .time-column {
                min-width: 100px;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h2 mb-2"><i class="fas fa-door-open me-2"></i>Gate Access Logs</h1>
                <p class="mb-0 opacity-75">Comprehensive tracking of all gate entries and exits</p>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-flex justify-content-end gap-2">
                    <a href="main.php" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Back to Scanner
                    </a>
                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Exits Warning -->
    <?php if ($pending_exits_count > 0): ?>
    <div class="alert alert-warning warning-alert mx-4 mt-4 d-flex align-items-center">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">Pending Exits Detected</h5>
            <p class="mb-0">There are <strong><?php echo $pending_exits_count; ?> person(s)</strong> who entered but haven't exited yet. 
            You can still logout, but please ensure these individuals exit properly.</p>
        </div>
        <span class="badge badge-warning fs-6"><?php echo $pending_exits_count; ?> PENDING</span>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row p-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Entries</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['total_entries'] ?? 0; ?></h2>
                            <small>Today's activity</small>
                        </div>
                        <i class="fas fa-sign-in-alt stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Entries In</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['entries_in'] ?? 0; ?></h2>
                            <small>Campus entries</small>
                        </div>
                        <i class="fas fa-arrow-right-to-bracket stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Entries Out</h6>
                            <h2 class="card-text mb-0"><?php echo $stats['entries_out'] ?? 0; ?></h2>
                            <small>Campus exits</small>
                        </div>
                        <i class="fas fa-arrow-right-from-bracket stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Pending Exits</h6>
                            <h2 class="card-text mb-0"><?php echo $pending_exits_count; ?></h2>
                            <small>Haven't exited yet</small>
                        </div>
                        <i class="fas fa-exclamation-triangle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Section -->
    <div class="row px-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Today's Breakdown</h5>
                    <?php if ($pending_exits_count > 0): ?>
                    <span class="badge badge-warning">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        <?php echo $pending_exits_count; ?> pending exits
                    </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-center">
                        <div class="breakdown-item">
                            <div class="fw-bold text-primary fs-4"><?php echo $breakdown['student'] ?? 0; ?></div>
                            <div class="text-muted">Students</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="fw-bold text-warning fs-4"><?php echo $breakdown['instructor'] ?? 0; ?></div>
                            <div class="text-muted">Instructors</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="fw-bold text-danger fs-4"><?php echo $breakdown['personell'] ?? 0; ?></div>
                            <div class="text-muted">Personnel</div>
                        </div>
                        <div class="breakdown-item">
                            <div class="fw-bold text-secondary fs-4"><?php echo $breakdown['visitor'] ?? 0; ?></div>
                            <div class="text-muted">Visitors</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-lg-3 col-md-6">
                <label for="date" class="form-label fw-bold">Date</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?php echo sanitizeOutput($date_filter); ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="type" class="form-label fw-bold">Person Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="student" <?php echo $type_filter === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="instructor" <?php echo $type_filter === 'instructor' ? 'selected' : ''; ?>>Instructors</option>
                    <option value="personell" <?php echo $type_filter === 'personell' ? 'selected' : ''; ?>>Personnel</option>
                    <option value="visitor" <?php echo $type_filter === 'visitor' ? 'selected' : ''; ?>>Visitors</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label for="direction" class="form-label fw-bold">Direction</label>
                <select class="form-select" id="direction" name="direction">
                    <option value="all" <?php echo $direction_filter === 'all' ? 'selected' : ''; ?>>Both Directions</option>
                    <option value="IN" <?php echo $direction_filter === 'IN' ? 'selected' : ''; ?>>Entry Only</option>
                    <option value="OUT" <?php echo $direction_filter === 'OUT' ? 'selected' : ''; ?>>Exit Only</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="search" class="form-label fw-bold">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Search by name or ID..." value="<?php echo sanitizeOutput($search_term); ?>">
            </div>
            <div class="col-lg-2 col-md-12 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="table-container">
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Access Logs</h5>
            <span class="badge bg-primary fs-6"><?php echo count($logs); ?> records found</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                    <tr>
                        <th><i class="fas fa-calendar me-1"></i>Date</th>
                        <th><i class="fas fa-id-card me-1"></i>ID Number</th>
                        <th><i class="fas fa-user me-1"></i>Name</th>
                        <th><i class="fas fa-tag me-1"></i>Type</th>
                        <th class="text-center"><i class="fas fa-clock me-1"></i>Time</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h5 class="mt-3">No logs found</h5>
                                    <p class="text-muted">Try adjusting your filters or search criteria</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="log-row">
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                                $timeValue = $log['date'] ?? null;
                                                echo $timeValue 
                                                    ? date('M j, Y', strtotime($timeValue)) 
                                                    : 'N/A';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <code class="text-primary"><?php echo sanitizeOutput($log['id_number']); ?></code>
                                    </td>
                                    <td>
                                        <strong>
                                            <?php 
                                            // Use the resolved full_name, fallback to gl.name, then 'Unknown'
                                            $displayName = !empty($log['full_name']) && $log['full_name'] !== 'N/A' 
                                                ? $log['full_name'] 
                                                : (!empty($log['name']) 
                                                    ? $log['name'] 
                                                    : 'Unknown Person');
                                            echo sanitizeOutput($displayName);
                                            ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $log['person_type']; ?> rounded-pill">
                                            <i class="fas fa-<?php echo getPersonTypeIcon($log['person_type']); ?> me-1"></i>
                                            <?php echo ucfirst($log['person_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-center d-flex justify-content-center">
                                            <!-- Entrance Column -->
                                            <div class="text-center pe-2 border-end">
                                                <span class="badge badge-entry rounded-pill d-block mb-1">
                                                    <i class="fas fa-sign-in-alt me-1"></i>IN
                                                </span>
                                                <small class="text-muted d-block">
                                                    <?php 
                                                        $timeIn = $log['time_in'] ?? null;
                                                        echo $timeIn && $timeIn != '00:00:00' && $timeIn != '?' 
                                                            ? date('h:i A', strtotime($timeIn)) 
                                                            : '-';
                                                    ?>
                                                </small>
                                            </div>
                                            <!-- Exit Column -->
                                            <div class="text-center ps-2">
                                                <span class="badge badge-exit rounded-pill d-block mb-1">
                                                    <i class="fas fa-sign-out-alt me-1"></i>OUT
                                                </span>
                                                <small class="text-muted d-block">
                                                    <?php 
                                                        $timeOut = $log['time_out'] ?? null;
                                                        echo $timeOut && $timeOut != '00:00:00' && $timeOut != '?' 
                                                            ? date('h:i A', strtotime($timeOut)) 
                                                            : '-';
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
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

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt me-2"></i>Security Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($pending_exits_count > 0): ?>
                    <!-- Warning Logout - Pending Exits -->
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading">Warning: Pending Exits Detected</h5>
                                <p class="mb-2">There are <strong><?php echo $pending_exits_count; ?> person(s)</strong> who entered but haven't exited yet.</p>
                                <p class="mb-0">You can still logout, but please ensure these individuals exit properly.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6 class="mb-3">Pending Exits Details:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-warning">
                                    <tr>
                                        <th>ID Number</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Entry Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_exits_details as $pending): ?>
                                    <tr>
                                        <td><code><?php echo sanitizeOutput($pending['id_number']); ?></code></td>
                                        <td><strong><?php echo sanitizeOutput($pending['full_name']); ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $pending['person_type']; ?> rounded-pill">
                                                <?php echo ucfirst($pending['person_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                            <small class="text-muted">
                                <?php 
                                    $entryTime = $pending['entry_time'] ?? null;
                                    echo $entryTime && $entryTime != '00:00:00' && $entryTime != '?' 
                                        ? date('h:i A', strtotime($entryTime)) 
                                        : '-';
                                ?>
                            </small>
                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> You are responsible for ensuring all individuals exit the campus before ending your shift.
                    </div>
                <?php else: ?>
                    <!-- Safe Logout -->
                    <div class="alert alert-success">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading">Safe to Logout</h5>
                                <p class="mb-0">All entrants have exited the campus. You can safely logout.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">You are logged in as: <strong><?php echo $_SESSION['access']['security']['fullname'] ?? 'Security Personnel'; ?></strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="logout_request" value="1">
                    <button type="submit" class="btn <?php echo $pending_exits_count > 0 ? 'btn-warning' : 'btn-danger'; ?>">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        <?php echo $pending_exits_count > 0 ? 'Logout Anyway' : 'Confirm Logout'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-submit form when date changes
    document.getElementById('date').addEventListener('change', function() {
        this.form.submit();
    });

    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight today's date in filter
        const today = new Date().toISOString().split('T')[0];
        if (document.getElementById('date').value === today) {
            document.getElementById('date').classList.add('border-primary', 'border-2');
        }

        // Add loading states
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                    submitBtn.disabled = true;
                }
            });
        });

        // Auto-refresh the page every 30 seconds to update pending exits count
        setInterval(function() {
            if (<?php echo $pending_exits_count; ?> > 0) {
                window.location.reload();
            }
        }, 30000);
    });
</script>
</body>
</html>