<?php
session_start();
// Display success/error messages
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

// Check if connection is successful
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Define essential functions
if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatTime')) {
    function formatTime($time) {
        if (empty($time) || $time == '00:00:00' || $time == '?' || $time == '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            return date('h:i A', strtotime($time));
        } catch (Exception $e) {
            return '-';
        }
    }
}

// =====================================================================
// ENHANCED DASHBOARD STATISTICS - FIXED TIMEZONE ISSUE
// =====================================================================

// Get dashboard statistics - FIXED TO USE MANILA DATE
function getDashboardStats($db) {
    $stats = [
        'total_entrants_today' => 0,
        'total_exits_today' => 0,
        'current_inside' => 0,
        'visitors_today' => 0,
        'students_today' => 0,
        'instructors_today' => 0,
        'personell_today' => 0,
        'total_students' => 0,
        'total_instructors' => 0,
        'total_personell' => 0,
        'total_visitors_today' => 0,
        'avg_daily_entrants' => 0,
        'pending_exits_count' => 0
    ];

    try {
        // Use PHP date instead of MySQL CURDATE() to ensure Manila time
        $manila_today = date('Y-m-d');
        
        // Debug: Log the date being used
        error_log("DEBUG - Using Manila date for filtering: " . $manila_today);
        
        // Total entries today - USE MANILA DATE
        $query = "SELECT COUNT(DISTINCT id_number) as total FROM gate_logs WHERE date = '$manila_today'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_entrants_today'] = $row['total'] ?? 0;
        }

        // Total exits today - USE MANILA DATE
        $query = "SELECT COUNT(*) as total FROM gate_logs WHERE date = '$manila_today' AND direction = 'OUT'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_exits_today'] = $row['total'] ?? 0;
        }

        // Current people inside - USE MANILA DATE
        $query = "SELECT COUNT(*) as total FROM gate_logs 
                 WHERE direction = 'IN' 
                 AND date = '$manila_today' 
                 AND id_number NOT IN (
                     SELECT id_number 
                     FROM gate_logs 
                     WHERE direction = 'OUT' 
                     AND date = '$manila_today'
                 )";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['current_inside'] = $row['total'] ?? 0;
            $stats['pending_exits_count'] = $row['total'] ?? 0;
        }

        // Debug: Let's check what's actually in the database
        error_log("DEBUG - Total unique ID numbers today: " . $stats['total_entrants_today']);
        error_log("DEBUG - Total OUT actions today: " . $stats['total_exits_today']);
        error_log("DEBUG - Current inside: " . $stats['current_inside']);

        // Visitors today - USE MANILA DATE
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                 FROM gate_logs gl
                 WHERE gl.date = '$manila_today' 
                 AND gl.person_type = 'visitor'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['visitors_today'] = $row['total'] ?? 0;
            $stats['total_visitors_today'] = $row['total'] ?? 0;
        }

        // Students present today - USE MANILA DATE
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                 FROM gate_logs gl
                 WHERE gl.person_type = 'student' 
                 AND gl.date = '$manila_today'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['students_today'] = $row['total'] ?? 0;
        }

        // Total students (from students table) - EXCLUDING BLOCKED
        $query = "SELECT COUNT(*) as total FROM students WHERE status != 'Blocked'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_students'] = $row['total'] ?? 0;
        }

        // Instructors present today - USE MANILA DATE
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                 FROM gate_logs gl
                 WHERE gl.person_type = 'instructor' 
                 AND gl.date = '$manila_today'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['instructors_today'] = $row['total'] ?? 0;
        }

        // Total instructors - EXCLUDING BLOCKED
        $query = "SELECT COUNT(*) as total FROM instructor WHERE status != 'Blocked'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_instructors'] = $row['total'] ?? 0;
        }

        // Staff present today - USE MANILA DATE - ENHANCED DEBUGGING
        // Staff present today - USE MANILA DATE - CORRECTED QUERY
        $query = "SELECT COUNT(DISTINCT gl.id_number) as total 
                FROM gate_logs gl
                WHERE gl.date = '$manila_today' 
                AND gl.person_type = 'personell'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['personell_today'] = $row['total'] ?? 0;
            error_log("DEBUG - Personnel today count: " . $stats['personell_today']);
        } else {
            error_log("DEBUG - Personnel query failed: " . $db->error);
            $stats['personell_today'] = 0;
        }

        // Total staff - EXCLUDING BLOCKED - CORRECTED QUERY
        $query = "SELECT COUNT(*) as total FROM personell WHERE status != 'Block' AND deleted = 0";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_personell'] = $row['total'] ?? 0;
            error_log("DEBUG - Total personnel count: " . $stats['total_personell']);
        } else {
            error_log("DEBUG - Total personnel query failed: " . $db->error);
            $stats['total_personell'] = 0;
        }

        // Average daily entrants (last 7 days) - USE MANILA DATE
        $last_week = date('Y-m-d', strtotime('-7 days'));
        $query = "SELECT AVG(daily_total) as avg_daily 
                 FROM (SELECT date, COUNT(DISTINCT id_number) as daily_total 
                       FROM gate_logs 
                       WHERE date >= '$last_week' 
                       GROUP BY date) as daily_totals";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['avg_daily_entrants'] = round($row['avg_daily'] ?? 0);
        }

    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
    }

    return $stats;
}

// Get today's logs with enhanced data - FIXED TO USE MANILA DATE
function getTodaysLogs($db) {
    $manila_today = date('Y-m-d');
    
    $query = "SELECT 
                gl.id_number,
                COALESCE(
                    s.fullname,
                    i.fullname,
                    CONCAT_WS(' ', p.first_name, p.last_name),
                    v.name,
                    gl.name
                ) as full_name,
                gl.person_type,
                gl.time_in,
                gl.time_out,
                gl.created_at,
                gl.date
              FROM gate_logs gl
              LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
              LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
              LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
              LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
              WHERE gl.date = '$manila_today'
              ORDER BY gl.created_at DESC
              LIMIT 100";
    
    try {
        return $db->query($query);
    } catch (Exception $e) {
        error_log("Today's logs error: " . $e->getMessage());
        return false;
    }
}

// Get weekly entrants data for line chart - FIXED TO USE MANILA DATE
function getWeeklyEntrants($db) {
    $weeklyData = [];
    
    try {
        // Get last 7 days including today with detailed stats
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime($date));
            
            // Get total entrants - USE EXPLICIT DATE
            $query = "SELECT COUNT(DISTINCT id_number) as total FROM gate_logs WHERE date = '$date'";
            $result = $db->query($query);
            $total = 0;
            if ($result) {
                $row = $result->fetch_assoc();
                $total = $row['total'] ?? 0;
            }
            
            // Get breakdown by type - USE EXPLICIT DATE
            $breakdown = [];
            $types = ['student', 'instructor', 'personell', 'visitor'];
            foreach ($types as $type) {
                $typeQuery = "SELECT COUNT(DISTINCT id_number) as count FROM gate_logs 
                             WHERE date = '$date' 
                             AND person_type = '$type'";
                $typeResult = $db->query($typeQuery);
                if ($typeResult) {
                    $typeRow = $typeResult->fetch_assoc();
                    $breakdown[$type] = $typeRow['count'] ?? 0;
                }
            }
            
            $weeklyData[] = [
                'day' => $dayName,
                'date' => $date,
                'total' => $total,
                'breakdown' => $breakdown
            ];
        }
    } catch (Exception $e) {
        error_log("Weekly entrants error: " . $e->getMessage());
    }
    
    return $weeklyData;
}

// Get today's student year level distribution - FIXED TO USE MANILA DATE
function getTodaysStudentYearLevelDistribution($db) {
    $distribution = [];
    
    try {
        $manila_today = date('Y-m-d');
        
        $query = "SELECT 
                    COALESCE(s.year, 'Not Specified') as year_level,
                    COUNT(DISTINCT gl.id_number) as total
                  FROM gate_logs gl
                  LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
                  WHERE gl.date = '$manila_today' 
                  AND gl.person_type = 'student'
                  GROUP BY s.year
                  ORDER BY s.year";
        
        $result = $db->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $yearLevel = $row['year_level'];
                // Format year level for display
                switch($yearLevel) {
                    case '1': $displayLevel = '1st Year'; break;
                    case '2': $displayLevel = '2nd Year'; break;
                    case '3': $displayLevel = '3rd Year'; break;
                    case '4': $displayLevel = '4th Year'; break;
                    case 'Not Specified': $displayLevel = 'Not Specified'; break;
                    default: $displayLevel = $yearLevel;
                }
                
                $distribution[] = [
                    'type' => $displayLevel,
                    'total' => $row['total'] ?? 0,
                    'year_level' => $yearLevel
                ];
            }
        }
        
        // Ensure we have all year levels even if zero
        $allYearLevels = [
            ['type' => '1st Year', 'total' => 0, 'year_level' => '1'],
            ['type' => '2nd Year', 'total' => 0, 'year_level' => '2'],
            ['type' => '3rd Year', 'total' => 0, 'year_level' => '3'],
            ['type' => '4th Year', 'total' => 0, 'year_level' => '4'],
            ['type' => 'Not Specified', 'total' => 0, 'year_level' => 'Not Specified']
        ];
        
        foreach ($allYearLevels as $defaultLevel) {
            $found = false;
            foreach ($distribution as $existing) {
                if ($existing['year_level'] === $defaultLevel['year_level']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $distribution[] = $defaultLevel;
            }
        }
        
        // Sort by year level to maintain consistent order
        usort($distribution, function($a, $b) {
            if ($a['year_level'] == 'Not Specified') return 1;
            if ($b['year_level'] == 'Not Specified') return -1;
            return $a['year_level'] <=> $b['year_level'];
        });
        
    } catch (Exception $e) {
        error_log("Today's student year level distribution error: " . $e->getMessage());
    }
    
    return $distribution;
}

// Get entrants distribution for pie chart
function getEntrantsDistribution($db) {
    return getTodaysStudentYearLevelDistribution($db);
}

// Get real-time activity feed - FIXED TO USE MANILA DATE
function getRecentActivity($db) {
    $activity = [];
    
    try {
        $manila_today = date('Y-m-d');
        
        $query = "SELECT 
                    gl.id_number,
                    COALESCE(
                        s.fullname,
                        i.fullname,
                        CONCAT_WS(' ', p.first_name, p.last_name),
                        v.name,
                        gl.name
                    ) as full_name,
                    gl.person_type as role,
                    gl.direction as action,
                    CASE 
                        WHEN gl.direction = 'IN' THEN gl.time_in
                        WHEN gl.direction = 'OUT' THEN gl.time_out
                        ELSE gl.created_at
                    END as display_time,
                    gl.location
                  FROM gate_logs gl
                  LEFT JOIN students s ON gl.person_type = 'student' AND gl.person_id = s.id
                  LEFT JOIN instructor i ON gl.person_type = 'instructor' AND gl.person_id = i.id
                  LEFT JOIN personell p ON gl.person_type = 'personell' AND gl.person_id = p.id
                  LEFT JOIN visitor v ON gl.person_type = 'visitor' AND gl.person_id = v.id
                  WHERE gl.date = '$manila_today'
                  ORDER BY gl.created_at DESC
                  LIMIT 10";
        
        $result = $db->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $activity[] = [
                    'full_name' => $row['full_name'],
                    'role' => ucfirst($row['role']),
                    'action' => $row['action'],
                    'time' => formatTime($row['display_time']),
                    'location' => $row['location']
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Recent activity error: " . $e->getMessage());
    }
    
    return $activity;
}

// Get data
 $stats = getDashboardStats($db);
 $logsResult = getTodaysLogs($db);
 $weeklyData = getWeeklyEntrants($db);
 $entrantsDistribution = getEntrantsDistribution($db);
 $recentActivity = getRecentActivity($db);

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

// Get current date for display
 $currentDate = date('F j, Y');

// DEBUG: Add debug information to see what's happening
 $debug_info = [
    'current_manila_date' => date('Y-m-d'),
    'total_entrants_today' => $stats['total_entrants_today'],
    'total_logs_found' => $logsResult ? $logsResult->num_rows : 0,
    'recent_activity_count' => count($recentActivity),
    'personell_today' => $stats['personell_today'],
    'total_personell' => $stats['total_personell']
];

error_log("DASHBOARD DEBUG: " . json_encode($debug_info));
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RFIDGPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px 15px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: none;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stats-card.text-info::before { background: linear-gradient(135deg, #36b9cc, #2e59d9); }
        .stats-card.text-primary::before { background: linear-gradient(135deg, #4e73df, #2e59d9); }
        .stats-card.text-danger::before { background: linear-gradient(135deg, #e74a3b, #be2617); }
        .stats-card.text-success::before { background: linear-gradient(135deg, #1cc88a, #17a673); }
        .stats-card.text-warning::before { background: linear-gradient(135deg, #f6c23e, #f4b619); }
        .stats-card.text-secondary::before { background: linear-gradient(135deg, #858796, #6c757d); }
        .stats-card.text-dark::before { background: linear-gradient(135deg, #5a5c69, #373840); }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .stats-content h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-text);
        }

        .stats-content p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .stats-detail {
            font-size: 0.75rem;
            color: #495057;
            margin-top: 5px;
        }

        .stats-trend {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            background: rgba(0,0,0,0.05);
        }

        .table th {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
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
            padding: 6px 10px;
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

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .alert {
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d1edff;
            color: #0c5460;
            border-left: 4px solid #117a8b;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

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

        h6.mb-4 {
            color: var(--dark-text);
            font-weight: 700;
            font-size: 1.25rem;
        }

        hr {
            opacity: 0.1;
            margin: 1.5rem 0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            transition: var(--transition);
        }

        /* Chart container */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 20px;
            height: 400px;
            position: relative;
            overflow: hidden;
        }

        .chart-title {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Enhanced Pie Chart Styles */
        .pie-chart-wrapper {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .pie-chart-container {
            width: 100%;
            height: 70%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 220px;
        }

        .chart-legend {
            position: relative;
            bottom: 0;
            left: 0;
            right: 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 8px 5px;
            max-height: none !important;
            overflow: visible !important;
            margin-top: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            color: var(--dark-text);
            padding: 5px 6px;
            border-radius: 6px;
            transition: all 0.2s ease;
            background: rgba(248, 249, 252, 0.7);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .legend-item:hover {
            background-color: rgba(92, 149, 233, 0.1);
            border-color: rgba(92, 149, 233, 0.3);
        }

        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            flex-shrink: 0;
        }

        .legend-text {
            line-height: 1.2;
            flex: 1;
        }

        .legend-text strong {
            font-size: 0.7rem;
            font-weight: 600;
        }

        .legend-text small {
            font-size: 0.65rem;
            color: #6c757d;
        }

        /* Activity feed */
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .activity-item:hover {
            background-color: rgba(92, 149, 233, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-badge {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .activity-badge.in { background-color: var(--success-color); }
        .activity-badge.out { background-color: var(--warning-color); }

        /* Hover logs */
        .hover-logs {
            display: none;
            position: absolute;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            padding: 15px;
            z-index: 1050;
            max-height: 280px;
            overflow-y: auto;
            width: 320px;
            border: 1px solid var(--accent-color);
        }

        .hover-logs ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hover-logs li {
            padding: 10px 0;
            border-bottom: 1px solid var(--accent-color);
        }

        .hover-logs li:last-child {
            border-bottom: none;
        }

        .hover-logs img {
            border-radius: 50%;
            width: 35px;
            height: 35px;
            object-fit: cover;
            margin-right: 10px;
        }

        /* Chart responsiveness */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
                padding: 15px;
            }
            
            .chart-legend {
                position: relative;
                bottom: auto;
                margin-top: 10px;
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        /* Backup button styles */
#backupBtn {
    transition: all 0.3s ease;
}

#backupBtn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

#backupBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Form switch styling */
.form-check-input:checked {
    background-color: #5c95e9;
    border-color: #5c95e9;
}

/* Backup status styling */
#backupStatus {
    transition: all 0.3s ease;
}

/* Notification styling */
.alert {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Backup section styling */
.backup-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid var(--info-color);
}

.backup-section .card-title {
    color: var(--dark-text);
    font-weight: 600;
    margin-bottom: 10px;
}

.backup-section .card-text {
    color: #6c757d;
    font-size: 0.9rem;
}

.backup-section .btn-primary {
    background: linear-gradient(135deg, var(--info-color), #4361ee);
    border: none;
}

.backup-section .form-switch {
    margin-right: 15px;
}

.backup-section .form-check-label {
    font-size: 0.9rem;
    color: var(--dark-text);
}

/* Backup list styling */
.backup-list {
    max-height: 300px;
    overflow-y: auto;
    margin-top: 15px;
}

.backup-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.backup-item:hover {
    background-color: rgba(92, 149, 233, 0.05);
}

.backup-item:last-child {
    border-bottom: none;
}

.backup-info {
    flex-grow: 1;
}

.backup-name {
    font-weight: 500;
    color: var(--dark-text);
}

.backup-details {
    font-size: 0.8rem;
    color: #6c757d;
}

.backup-actions {
    display: flex;
    gap: 5px;
}

.backup-actions button {
    padding: 5px 10px;
    font-size: 0.8rem;
}

/* Download button */
.btn-download {
    background: linear-gradient(135deg, var(--success-color), #17a673);
    color: white;
    border: none;
}

.btn-download:hover {
    background: linear-gradient(135deg, #17a673, #13855c);
    color: white;
}

/* Delete button */
.btn-delete {
    background: linear-gradient(135deg, var(--danger-color), #d73525);
    color: white;
    border: none;
}

.btn-delete:hover {
    background: linear-gradient(135deg, #d73525, #be2617);
    color: white;
}

/* Loading spinner */
.backup-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
                            <div class="col-12">
                                <h6 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h6>
                                <div class="date-indicator">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    Today's Data: <?php echo $currentDate; ?>
                                </div>
                                <div class="alert alert-info d-flex align-items-center mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span>All statistics and charts reset daily at midnight.</span>
                                </div>
                                
                                <!-- Database Backup Section - MOVED TO TOP -->
                                <div class="backup-section mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="fas fa-database me-2"></i>Database Backup</h5>
                                            <p class="card-text text-muted mb-0">Create backups of your database to prevent data loss</p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="form-check form-switch me-3">
                                                <input class="form-check-input" type="checkbox" id="autoBackupToggle">
                                                <label class="form-check-label" for="autoBackupToggle">
                                                    Auto-backup every 30s
                                                </label>
                                            </div>
                                            <button id="backupBtn" class="btn btn-primary">
                                                <i class="fas fa-download me-2"></i>Backup Now
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light mt-3" id="backupStatus" style="display: none;">
                                        <div class="d-flex align-items-center">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <span id="backupStatusText">Creating backup...</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Backup List Section -->
                                    <div class="mt-3">
                                        <h6 class="mb-2"><i class="fas fa-list me-2"></i>Recent Backups</h6>
                                        <div class="backup-list" id="backupList">
                                            <!-- Backup items will be loaded here via AJAX -->
                                            <div class="text-center text-muted py-3">
                                                <i class="fas fa-spinner fa-spin me-2"></i>Loading backup list...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>

                        <!-- Enhanced Statistics Cards -->
                        <div class="row g-4 mb-4">
                            <!-- Total Entrants - COUNT UNIQUE ID NUMBERS -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-info">
                                    <div class="stats-icon">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['total_entrants_today']); ?></h3>
                                        <p>Total People Today</p>
                                        <div class="stats-detail">
                                            <small class="text-muted">Unique individuals</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructors Count -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-warning">
                                    <div class="stats-icon">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['instructors_today']); ?></h3>
                                        <p>Instructors Today</p>
                                        <div class="stats-detail">
                                            <small class="text-muted">Total: <?php echo sanitizeOutput($stats['total_instructors']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visitors -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-primary">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['visitors_today']); ?></h3>
                                        <p>Visitors Today</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Students -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-success">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['students_today']); ?></h3>
                                        <p>Students Today</p>
                                        <div class="stats-detail">
                                            <small class="text-muted">Total: <?php echo sanitizeOutput($stats['total_students']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Personnel and Staff -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-secondary">
                                    <div class="stats-icon">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['personell_today']); ?></h3>
                                        <p>Personnel & Staff</p>
                                        <div class="stats-detail">
                                            <small class="text-muted">Total: <?php echo sanitizeOutput($stats['total_personell']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Inside Count -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-dark">
                                    <div class="stats-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['current_inside']); ?></h3>
                                        <p>Currently Inside</p>
                                        <div class="stats-detail">
                                            <small class="text-muted">Exits today: <?php echo sanitizeOutput($stats['total_exits_today']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Charts Section -->
                        <div class="row g-4 mb-4">
                            <!-- Weekly People Trend - COUNT UNIQUE ID NUMBERS -->
                            <div class="col-lg-7">
                                <div class="chart-container">
                                    <h5 class="chart-title"><i class="fas fa-chart-line me-2"></i>Weekly People Trend</h5>
                                    <div id="weeklyEntrantsChart" style="height: 100%;"></div>
                                </div>
                            </div>

                            <!-- Today's Student Year Level Distribution - COUNT UNIQUE STUDENTS -->
                            <div class="col-lg-5">
                                <div class="chart-container">
                                    <h5 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Today's Student Distribution by Year Level</h5>
                                    <div class="pie-chart-wrapper">
                                        <div id="entrantsDistributionChart" class="pie-chart-container"></div>
                                        <!-- Custom Legend -->
                                        <div class="chart-legend" id="pieChartLegend"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time Activity & Detailed Logs -->
                        <div class="row g-4">
                            <!-- Recent Activity -->
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-list-alt me-2"></i>Recent Activity</h5>
                                        <hr>
                                        <div class="activity-feed">
                                            <?php if (!empty($recentActivity)): ?>
                                                <?php foreach ($recentActivity as $activity): ?>
                                                    <div class="activity-item">
                                                        <div class="d-flex align-items-center">
                                                            <span class="activity-badge <?php echo strtolower($activity['action']); ?>"></span>
                                                            <div class="flex-grow-1">
                                                                <strong><?php echo sanitizeOutput($activity['full_name']); ?></strong>
                                                                <div class="d-flex justify-content-between">
                                                                    <small class="text-muted"><?php echo sanitizeOutput($activity['role']); ?></small>
                                                                    <small class="text-<?php echo $activity['action'] == 'IN' ? 'success' : 'warning'; ?>">
                                                                        <?php echo $activity['action']; ?>
                                                                    </small>
                                                                </div>
                                                                <small class="text-info"><?php echo sanitizeOutput($activity['location']); ?></small>
                                                                <small class="text-muted d-block"><?php echo $activity['time']; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    No recent activity
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Today's Entrance Logs -->
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Today's Entrance Logs</h5>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="logsTable">
                                                <thead>
                                                    <tr>
                                                        <th>ID Number</th>
                                                        <th>Full Name</th>
                                                        <th>Role</th>
                                                        <th>Time In</th>
                                                        <th>Time Out</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (!$logsResult) {
                                                        echo '<tr><td colspan="5" class="text-danger text-center py-4"><i class="fas fa-exclamation-triangle me-2"></i>Error loading entrance logs</td></tr>';
                                                    } else {
                                                        if ($logsResult->num_rows > 0) {
                                                            while ($row = $logsResult->fetch_assoc()) { 
                                                                $timeIn = formatTime($row['time_in']);
                                                                $timeOut = formatTime($row['time_out']);
                                                    ?>
                                                                <tr>
                                                                    <td><code class="text-primary"><?php echo sanitizeOutput($row['id_number']); ?></code></td>
                                                                    <td><strong><?php echo sanitizeOutput($row['full_name']); ?></strong></td>
                                                                    <td>
                                                                        <span class="badge badge-<?php echo $row['person_type']; ?> rounded-pill">
                                                                            <i class="fas fa-<?php echo getPersonTypeIcon($row['person_type']); ?> me-1"></i>
                                                                            <?php echo ucfirst($row['person_type']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-entry rounded-pill">
                                                                            <i class="fas fa-sign-in-alt me-1"></i>
                                                                            <?php echo $timeIn; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($timeOut != '-'): ?>
                                                                            <span class="badge badge-exit rounded-pill">
                                                                                <i class="fas fa-sign-out-alt me-1"></i>
                                                                                <?php echo $timeOut; ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-warning rounded-pill">
                                                                                <i class="fas fa-clock me-1"></i>
                                                                                Still Inside
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                    <?php 
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No entrance logs found for today.</td></tr>';
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="fas fa-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script type="text/javascript">
        // Load Google Charts
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            drawWeeklyEntrantsChart();
            drawEntrantsDistributionChart();
        }

        function drawWeeklyEntrantsChart() {
            // Weekly people data from PHP - COUNT UNIQUE ID NUMBERS
            const weeklyData = <?php echo json_encode($weeklyData); ?>;
            
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Total People');
            data.addColumn('number', 'Students');
            data.addColumn('number', 'Instructors');
            data.addColumn('number', 'Staff');
            data.addColumn('number', 'Visitors');
            
            weeklyData.forEach(day => {
                data.addRow([
                    day.day, 
                    parseInt(day.total),
                    parseInt(day.breakdown.student || 0),
                    parseInt(day.breakdown.instructor || 0),
                    parseInt(day.breakdown.personell || 0),
                    parseInt(day.breakdown.visitor || 0)
                ]);
            });

            const options = {
                title: '',
                curveType: 'function',
                legend: { position: 'bottom' },
                colors: ['#5c95e9', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'],
                backgroundColor: 'transparent',
                chartArea: {width: '85%', height: '70%', top: 20, bottom: 80},
                hAxis: {
                    textStyle: {color: '#5a5c69', fontSize: 12},
                    gridlines: { color: 'transparent' },
                    baselineColor: '#5a5c69',
                    showTextEvery: 1,
                    slantedText: false
                },
                vAxis: {
                    title: 'Number of People',
                    titleTextStyle: {color: '#5a5c69', bold: true, fontSize: 12},
                    minValue: 0,
                    gridlines: { 
                        color: '#f0f0f0',
                        count: 5
                    },
                    baseline: 0,
                    baselineColor: '#5a5c69',
                    format: '0',
                    viewWindow: {
                        min: 0
                    },
                    textStyle: {color: '#5a5c69', fontSize: 11}
                },
                titleTextStyle: {
                    color: '#5a5c69',
                    fontSize: 16,
                    bold: true
                },
                lineWidth: 3,
                pointSize: 5,
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            const chart = new google.visualization.LineChart(document.getElementById('weeklyEntrantsChart'));
            chart.draw(data, options);
        }

        function drawEntrantsDistributionChart() {
            // TODAY'S student distribution data from PHP - COUNT UNIQUE STUDENTS
            const distributionData = <?php echo json_encode($entrantsDistribution); ?>;
            
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Year Level');
            data.addColumn('number', 'Student Count');
            
            // Calculate total for percentage calculation
            let totalStudents = 0;
            distributionData.forEach(item => {
                totalStudents += parseInt(item.total);
            });
            
            // Filter out zero values and prepare data
            const filteredData = distributionData.filter(item => parseInt(item.total) > 0);
            
            // If all data is zero, show a placeholder
            if (filteredData.length === 0) {
                data.addRow(['No Students Today', 1]);
            } else {
                // Add data with simplified labels
                filteredData.forEach(item => {
                    data.addRow([item.type, parseInt(item.total)]);
                });
            }

            const options = {
                title: '',
                pieHole: 0.3,
                backgroundColor: 'transparent',
                chartArea: {
                    width: '95%', 
                    height: '80%',
                    top: 10, 
                    left: 0,
                    right: 0,
                    bottom: 10
                },
                legend: {
                    position: 'none'
                },
                pieSliceText: 'none',
                colors: ['#5c95e9', '#1cc88a', '#f6c23e', '#e74a3b', '#6c757d', '#8e44ad', '#3498db'],
                pieSliceBorderColor: 'white',
                pieSliceBorderWidth: 2,
                is3D: false,
                pieStartAngle: 0,
                sliceVisibilityThreshold: 0,
                enableInteractivity: true,
                tooltip: { 
                    trigger: 'focus',
                    showColorCode: true,
                    text: 'both',
                    isHtml: true
                }
            };

            const chart = new google.visualization.PieChart(document.getElementById('entrantsDistributionChart'));
            
            // Create custom legend with better layout
            function createCustomLegend() {
                const legendContainer = document.getElementById('pieChartLegend');
                legendContainer.innerHTML = '';
                
                // Use grid layout for better space utilization
                legendContainer.style.display = 'grid';
                legendContainer.style.gridTemplateColumns = 'repeat(2, 1fr)';
                legendContainer.style.gap = '6px';
                legendContainer.style.maxHeight = 'none';
                legendContainer.style.overflow = 'visible';
                legendContainer.style.padding = '5px';
                
                const dataToShow = filteredData.length === 0 ? distributionData : filteredData;
                
                dataToShow.forEach((item, index) => {
                    const percentage = totalStudents > 0 ? ((item.total / totalStudents) * 100).toFixed(1) : '0';
                    const legendItem = document.createElement('div');
                    legendItem.className = 'legend-item';
                    legendItem.style.margin = '1px 0';
                    legendItem.style.padding = '4px 5px';
                    legendItem.style.borderRadius = '5px';
                    legendItem.style.transition = 'all 0.2s ease';
                    
                    legendItem.innerHTML = `
                        <div class="d-flex align-items-center">
                            <span class="legend-color" style="background-color: ${options.colors[index % options.colors.length]}; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; flex-shrink: 0;"></span>
                            <div class="legend-text" style="flex: 1;">
                                <div style="font-size: 0.68rem; font-weight: 600; line-height: 1.1;">${item.type}</div>
                                <div style="font-size: 0.62rem; color: #6c757d; line-height: 1.1;">${item.total} (${percentage}%)</div>
                            </div>
                        </div>
                    `;
                    
                    legendContainer.appendChild(legendItem);
                });
            }
            
            // Enhanced hover functionality
            google.visualization.events.addListener(chart, 'onmouseover', function(e) {
                if (filteredData.length === 0) return;
                
                const sliceIndex = e.row;
                const sliceLabel = filteredData[sliceIndex].type;
                const sliceValue = filteredData[sliceIndex].total;
                const percentage = ((sliceValue / totalStudents) * 100).toFixed(1);
                
                // Highlight corresponding legend item
                const legendItems = document.querySelectorAll('.legend-item');
                legendItems.forEach((item, index) => {
                    if (index === sliceIndex) {
                        item.style.backgroundColor = 'rgba(92, 149, 233, 0.15)';
                        item.style.transform = 'scale(1.02)';
                    }
                });
            });
            
            google.visualization.events.addListener(chart, 'onmouseout', function(e) {
                // Remove highlight from legend items
                const legendItems = document.querySelectorAll('.legend-item');
                legendItems.forEach(item => {
                    item.style.backgroundColor = 'transparent';
                    item.style.transform = 'scale(1)';
                });
            });
            
            // Draw chart and create legend
            chart.draw(data, options);
            createCustomLegend();
        }

        // Redraw charts on window resize
        window.addEventListener('resize', function() {
            drawCharts();
        });
        
        // Check if it's a new day and refresh if needed
        function checkNewDay() {
            const currentDate = new Date().toDateString();
            const storedDate = localStorage.getItem('lastVisitDate');
            
            if (storedDate !== currentDate) {
                localStorage.setItem('lastVisitDate', currentDate);
                // Refresh page to show new day's data
                window.location.reload();
            }
        }
        
        // Check for new day every minute
        setInterval(checkNewDay, 60000);
        checkNewDay(); // Initial check
        
        // Database Backup Functionality
        let autoBackupInterval = null;

        // Load backup list on page load
        loadBackupList();

        // Manual backup on button click
        $('#backupBtn').click(function() {
            createBackup();
        });

        // Toggle auto-backup
        $('#autoBackupToggle').change(function() {
            if ($(this).is(':checked')) {
                // Start auto-backup every 30 seconds
                autoBackupInterval = setInterval(function() {
                    createBackup(true); // true indicates this is an automatic backup
                }, 30000);
                
                // Show notification
                showNotification('Auto-backup enabled. Database will be backed up every 30 seconds.', 'info');
            } else {
                // Stop auto-backup
                if (autoBackupInterval) {
                    clearInterval(autoBackupInterval);
                    autoBackupInterval = null;
                }
                
                // Show notification
                showNotification('Auto-backup disabled.', 'warning');
            }
        });

        // Function to load backup list
        function loadBackupList() {
            $.ajax({
                url: 'backup.php?action=list',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayBackupList(response.backups);
                    } else {
                        $('#backupList').html('<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load backup list</div>');
                    }
                },
                error: function() {
                    $('#backupList').html('<div class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i>Server error loading backup list</div>');
                }
            });
        }

        // Function to display backup list
        function displayBackupList(backups) {
            if (backups.length === 0) {
                $('#backupList').html('<div class="text-center text-muted py-3"><i class="fas fa-info-circle me-2"></i>No backups found</div>');
                return;
            }

            let html = '';
            backups.forEach(function(backup) {
                const fileSize = formatBytes(backup.size);
                const fileDate = new Date(backup.date).toLocaleString();
                
                html += `
                    <div class="backup-item">
                        <div class="backup-info">
                            <div class="backup-name">${backup.name}</div>
                            <div class="backup-details">${fileSize} - ${fileDate}</div>
                        </div>
                        <div class="backup-actions">
                            <button class="btn btn-sm btn-download" onclick="downloadBackup('${backup.name}')">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-delete" onclick="deleteBackup('${backup.name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            $('#backupList').html(html);
        }

        // Function to format bytes
        function formatBytes(size, precision = 2) {
            const base = Math.log(size) / Math.log(1024);
            const suffixes = ['', 'KB', 'MB', 'GB', 'TB'];
            
            return Math.round(Math.pow(1024, base - Math.floor(base)), precision) + ' ' + suffixes[Math.floor(base)];
        }

        // Function to create backup
        function createBackup(isAuto = false) {
            // Show backup status
            $('#backupStatus').show();
            $('#backupStatusText').text('Creating backup...');
            
            // Disable button during backup
            $('#backupBtn').prop('disabled', true);
            
            // Make AJAX request to backup.php
            $.ajax({
                url: 'backup.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#backupStatusText').html(
                            '<i class="fas fa-check-circle text-success me-2"></i>' +
                            'Backup created successfully! File: ' + response.file + 
                            ' (' + response.size + ') at ' + response.time
                        );
                        
                        // Show success notification
                        if (!isAuto) {
                            showNotification('Database backup created successfully!', 'success');
                            
                            // Trigger download of backup file
                            downloadBackup(response.file);
                        }
                        
                        // Refresh backup list
                        loadBackupList();
                        
                        // Hide status after 5 seconds
                        setTimeout(function() {
                            $('#backupStatus').fadeOut();
                        }, 5000);
                    } else {
                        $('#backupStatusText').html(
                            '<i class="fas fa-exclamation-triangle text-danger me-2"></i>' +
                            'Backup failed. Please try again.'
                        );
                        
                        // Show error notification
                        showNotification('Failed to create database backup!', 'danger');
                    }
                },
                error: function() {
                    $('#backupStatusText').html(
                        '<i class="fas fa-exclamation-triangle text-danger me-2"></i>' +
                        'Backup failed. Server error.'
                    );
                    
                    // Show error notification
                    showNotification('Failed to create database backup! Server error.', 'danger');
                },
                complete: function() {
                    // Re-enable button
                    $('#backupBtn').prop('disabled', false);
                }
            });
        }

        // Function to download backup
        function downloadBackup(filename) {
            // Create a temporary link element
            const link = document.createElement('a');
            link.href = 'backup.php?download=' + filename;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Function to delete backup
        function deleteBackup(filename) {
            Swal.fire({
                title: 'Delete Backup?',
                text: `Are you sure you want to delete "${filename}"? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'backup.php?action=delete&file=' + filename,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                showNotification('Backup deleted successfully!', 'success');
                                loadBackupList(); // Refresh the list
                            } else {
                                showNotification('Failed to delete backup!', 'danger');
                            }
                        },
                        error: function() {
                            showNotification('Server error while deleting backup!', 'danger');
                        }
                    });
                }
            });
        }

        // Function to show notification
        function showNotification(message, type) {
            // Create notification element
            const notification = $(`
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
            
            // Add to body
            $('body').append(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notification.alert('close');
            }, 5000);
        }
    </script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable for logs - FIXED VERSION
        $('#logsTable').DataTable({
            order: [[3, 'desc']], // Order by Time In (newest first) - 4th column (0-based index)
            pageLength: 15,
            responsive: true,
            language: {
                search: "Search logs:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                emptyTable: "No entrance logs found for today.",
                zeroRecords: "No matching records found",
                paginate: {
                    previous: "Previous",
                    next: "Next"
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        // Auto-refresh dashboard every 60 seconds
        setInterval(function() {
            // Optional: Add a visual indicator before refresh
            console.log('Auto-refreshing dashboard...');
            window.location.reload();
        }, 60000);
    });
    </script>
</body>
</html>