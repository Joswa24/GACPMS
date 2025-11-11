<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ✅ Session & Role Check - MUST BE AT THE VERY TOP
session_start();

// Debug session data
error_log("=== DASHBOARD ACCESS ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// Check if essential session variables exist
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' ||
    !isset($_SESSION['instructor_id'])) {
    
    error_log("SESSION VALIDATION FAILED - Redirecting to index");
    header("Location: index.php");
    exit();
}

error_log("SESSION VALIDATION PASSED - Loading dashboard");

// ✅ Timeout (15 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: index.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// ✅ Hijack Prevention
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} else {
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: index.php?hijack=1");
        exit();
    }
}

// Now include other files
include '../connection.php';
include '../header.php';

// Check database connection
if (!$db || $db->connect_error) {
    die("Database connection failed: " . ($db->connect_error ?? 'Unknown error'));
}

// ✅ Fetch Updated Instructor Information
$instructor_info = null;
$instructor_id = $_SESSION['instructor_id'];

// FIXED QUERY: Removed email and contact_number columns
$stmt = $db->prepare("
    SELECT i.fullname, i.id_number, d.department_name
    FROM instructor i 
    LEFT JOIN department d ON i.department_id = d.department_id 
    WHERE i.id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $instructor_info = $result->fetch_assoc();
        
        // Update session variables with fresh data from database
        $_SESSION['fullname'] = $instructor_info['fullname'];
        $_SESSION['department'] = $instructor_info['department_name'] ?? 'Not Assigned';
        $_SESSION['id_number'] = $instructor_info['id_number'] ?? '';
        
    } else {
        // Instructor not found in database - logout user
        session_unset();
        session_destroy();
        header("Location: index.php?error=instructor_not_found");
        exit();
    }
    $stmt->close();
} else {
    die("Database error: " . $db->error);
}

// ✅ Fetch Instructor Schedules (UPDATED TO USE INSTRUCTOR NAME INSTEAD OF ID)
$today_classes = [];
$upcoming_classes = [];

// Get instructor's fullname
$instructor_name = $_SESSION['fullname'];

// Today's classes (UPDATED QUERY)
$today_day = date("l");
$stmt = $db->prepare("
    SELECT subject, room_name, section, start_time, end_time, day, year_level
    FROM room_schedules
    WHERE instructor = ? AND day = ?
    ORDER BY start_time ASC
");

if ($stmt) {
    $stmt->bind_param("ss", $instructor_name, $today_day);
    $stmt->execute();
    $today_classes = $stmt->get_result();
    $stmt->close();
}

// Upcoming classes (week overview) - UPDATED QUERY
$stmt = $db->prepare("
    SELECT subject, room_name, section, start_time, end_time, day, year_level
    FROM room_schedules
    WHERE instructor = ?
    ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
             start_time ASC
");

if ($stmt) {
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $upcoming_classes = $stmt->get_result();
    $stmt->close();
}

// ✅ Fetch today's attendance summary from archived_attendance_logs
$today_attendance_summary = [];
$today_date = date('Y-m-d');

// Query to get attendance summary by class for today
$attendance_summary_query = "
    SELECT 
        year_level as year,
        section,
        subject_name as subject,
        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
        COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent_count,
        COUNT(*) as total_students,
        ROUND((COUNT(CASE WHEN status = 'Present' THEN 1 END) / COUNT(*) * 100), 1) as attendance_rate
    FROM archived_attendance_logs 
    WHERE instructor_id = ? AND session_date = ?
    GROUP BY year_level, section, subject_name
    ORDER BY year_level, section
";

$attendance_stmt = $db->prepare($attendance_summary_query);
if ($attendance_stmt) {
    $attendance_stmt->bind_param("ss", $instructor_id, $today_date);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    
    while ($attendance_row = $attendance_result->fetch_assoc()) {
        $today_attendance_summary[] = $attendance_row;
    }
    $attendance_stmt->close();
}

// ✅ Fetch recent attendance activity from archived_attendance_logs
$recent_attendance_activity = [];
$recent_activity_query = "
    SELECT 
        student_id,
        id_number,
        fullname,
        department,
        location,
        time_in,
        time_out,
        status,
        subject_name,
        room,
        session_date,
        archived_at
    FROM archived_attendance_logs 
    WHERE instructor_id = ? 
    ORDER BY archived_at DESC 
    LIMIT 10
";

$recent_stmt = $db->prepare($recent_activity_query);
if ($recent_stmt) {
    $recent_stmt->bind_param("i", $instructor_id);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    
    while ($recent_row = $recent_result->fetch_assoc()) {
        $recent_attendance_activity[] = $recent_row;
    }
    $recent_stmt->close();
}

// ✅ Fetch weekly attendance data for charts
$weekly_attendance_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dayName = date('D', strtotime($date));
    
    $weekly_query = "
        SELECT 
            COUNT(CASE WHEN status = 'Present' THEN 1 END) as total_present,
            COUNT(CASE WHEN status = 'Absent' THEN 1 END) as total_absent,
            COUNT(*) as total_students
        FROM archived_attendance_logs 
        WHERE instructor_id = ? AND session_date = ?
    ";
    
    $weekly_stmt = $db->prepare($weekly_query);
    if ($weekly_stmt) {
        $weekly_stmt->bind_param("ss", $instructor_id, $date);
        $weekly_stmt->execute();
        $weekly_result = $weekly_stmt->get_result();
        
        $present = 0;
        $absent = 0;
        $total = 0;
        
        if ($weekly_result && $row = $weekly_result->fetch_assoc()) {
            $present = $row['total_present'] ?? 0;
            $absent = $row['total_absent'] ?? 0;
            $total = $row['total_students'] ?? 0;
        }
        
        $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;
        
        $weekly_attendance_data[] = [
            'day' => $dayName,
            'date' => $date,
            'present' => $present,
            'absent' => $absent,
            'total' => $total,
            'rate' => $rate
        ];
        
        $weekly_stmt->close();
    }
}

// Get current date for display
$currentDate = date('F j, Y');

// Calculate overall statistics
$total_present = 0;
$total_absent = 0;
$total_students = 0;

foreach ($today_attendance_summary as $summary) {
    $total_present += $summary['present_count'];
    $total_absent += $summary['absent_count'];
    $total_students += $summary['total_students'];
}

$overall_attendance_rate = $total_students > 0 ? round(($total_present / $total_students) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #54555cff;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --sidebar-bg: #79abf7ff;
            --sidebar-hover: #b2d7fdff;
            --sidebar-active: #4361ee;
        }

        body {
            background: linear-gradient(135deg, var(--icon-color), var(--secondary-color));
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

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
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

        /* Modern Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, var(--icon-color), var(--secondary-color));
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 1000;
            padding-top: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
        }

        .sidebar-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .sidebar-subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 5px 0 0;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .sidebar .nav {
            flex-direction: column;
            padding: 0 15px;
        }

        .sidebar .nav-item {
            margin-bottom: 5px;
            position: relative;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            margin: 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .sidebar .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--sidebar-active), #2e59d9);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: white;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-footer {
            padding: 15px;
        }

        .sidebar-profile {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }

        .sidebar-profile:hover {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--icon-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 12px;
        }

        .sidebar-profile-info {
            flex: 1;
        }

        .sidebar-profile-name {
            color: white;
            font-size: 14px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-profile-role {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 0;
        }

        .sidebar-profile-toggle {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .sidebar-profile-toggle:hover {
            color: white;
        }

        /* Mobile sidebar toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--sidebar-bg);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            box-shadow: var(--box-shadow);
            cursor: pointer;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .navbar {
            background: linear-gradient(180deg,  #1a252f 100%,var(--icon-color) 0%,);
            padding: 10px 20px;
            margin-left: 260px;
            width: calc(100% - 260px);
            position: fixed;
            top: 0;
            z-index: 998;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            margin-top: 56px;
            min-height: calc(100vh - 56px);
        }

        .welcome-header {
            background: linear-gradient(135deg, var(--icon-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            z-index: 1;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        .instructor-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .info-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-right: 10px;
            margin-bottom: 5px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid #e9ecef;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .attendance-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 10px;
        }
        
        .attendance-progress {
            height: 6px;
            margin-top: 5px;
        }
        
        .class-status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-in-progress {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }
        
        .status-upcoming {
            background-color: #17a2b8;
        }
        
        .status-completed {
            background-color: #6c757d;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
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

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .btn-outline-primary {
            border-color: var(--icon-color);
            color: var(--icon-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--icon-color);
            border-color: var(--icon-color);
            color: white;
        }

        .time-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .attendance-stats {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .view-attendance-btn {
            transition: all 0.3s ease;
        }
        
        .view-attendance-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .navbar, .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .welcome-header {
                padding: 20px;
            }
            
            .instructor-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Modern Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/it.png" alt="Institution Logo" class="header-logo me-3" style="width: 80px; height: 80px; border-radius: 10px; border: 3px solid rgba(255,255,255,0.3);">
            </div>
            <h5 class="sidebar-title">Instructor Portal</h5>
            <p class="sidebar-subtitle">RFID Attendance System</p>
        </div>
        
        <div class="sidebar-nav">
            <ul class="nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="schedule.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Schedule</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Instructor'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid pt-4 px-4">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <!-- Enhanced Welcome Header -->
                    <div class="welcome-header">
                        <div class="welcome-content">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-3">
                                        <div>
                                            <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></h2>
                                            <p class="mb-0"><?php echo htmlspecialchars($_SESSION['department']); ?> Instructor</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="info-badge">
                                            <i class="fas fa-id-card me-1"></i>
                                            ID: <?php echo htmlspecialchars($_SESSION['id_number'] ?? 'N/A'); ?>
                                        </span>
                                        <span class="info-badge">
                                            <i class="fas fa-building me-1"></i>
                                            Department: <?php echo htmlspecialchars($_SESSION['department']); ?>
                                        </span>
                                        <?php if (!empty($today_attendance_summary)): ?>
                                        <span class="info-badge">
                                            <i class="fas fa-clipboard-check me-1"></i>
                                            <?php echo count($today_attendance_summary); ?> Classes Tracked Today
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0"><i class="fas fa-calendar-day me-2"></i>Today is <?php echo date('l, F j, Y'); ?></p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="card bg-light bg-opacity-50 border-0">
                                        <div class="card-body text-dark">
                                            <h6 class="card-title"><i class="fas fa-clock me-2"></i>Current Time</h6>
                                            <h4 id="current-time" class="mb-0"><?php echo date('g:i A'); ?></h4>
                                            <small id="current-date"><?php echo date('M j, Y'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <!-- Today's Classes -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-info">
                                <div class="stats-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="stats-content">
                                    <h3><?php echo $today_classes ? $today_classes->num_rows : 0; ?></h3>
                                    <p>Today's Classes</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">Scheduled for today</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Students Present -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-success">
                                <div class="stats-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="stats-content">
                                    <h3>
                                        <?php 
                                        $total_present = 0;
                                        foreach ($today_attendance_summary as $summary) {
                                            $total_present += $summary['present_count'];
                                        }
                                        echo $total_present;
                                        ?>
                                    </h3>
                                    <p>Students Present</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">Across all classes</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Students Absent -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-danger">
                                <div class="stats-icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="stats-content">
                                    <h3>
                                        <?php 
                                        $total_absent = 0;
                                        foreach ($today_attendance_summary as $summary) {
                                            $total_absent += $summary['absent_count'];
                                        }
                                        echo $total_absent;
                                        ?>
                                    </h3>
                                    <p>Students Absent</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">Across all classes</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Average Attendance Rate -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-warning">
                                <div class="stats-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stats-content">
                                    <h3>
                                        <?php 
                                        $total_students = 0;
                                        $total_present = 0;
                                        foreach ($today_attendance_summary as $summary) {
                                            $total_students += $summary['total_students'];
                                            $total_present += $summary['present_count'];
                                        }
                                        $overall_rate = $total_students > 0 ? round(($total_present / $total_students) * 100, 1) : 0;
                                        echo $overall_rate . '%';
                                        ?>
                                    </h3>
                                    <p>Avg. Attendance</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">Today's rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Charts Section -->
                    <div class="row g-4 mb-4">
                        <!-- Weekly Attendance Trend -->
                        <div class="col-lg-7">
                            <div class="chart-container">
                                <h5 class="chart-title"><i class="fas fa-chart-line me-2"></i>Weekly Attendance Trend</h5>
                                <div id="weeklyAttendanceChart" style="height: 100%;"></div>
                            </div>
                        </div>

                        <!-- Today's Attendance by Class -->
                        <div class="col-lg-5">
                            <div class="chart-container">
                                <h5 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Today's Attendance by Class</h5>
                                <div id="attendanceByClassChart" style="height: 100%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Classes & Upcoming Classes -->
                    <div class="row g-4">
                        <!-- Today's Classes -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header bg-primary-custom d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Classes</h5>
                                    <span class="badge bg-light text-dark"><?php echo $today_classes ? $today_classes->num_rows : 0; ?> classes</span>
                                </div>
                                <div class="card-body">
                                    <?php if ($today_classes && $today_classes->num_rows > 0): ?>
                                        <div class="list-group">
                                            <?php while ($class = $today_classes->fetch_assoc()): 
                                                $start_time = date("g:i A", strtotime($class['start_time']));
                                                $end_time = date("g:i A", strtotime($class['end_time']));
                                                $current_time = time();
                                                $class_start = strtotime($class['start_time']);
                                                $class_end = strtotime($class['end_time']);
                                                
                                                // Determine class status
                                                if ($current_time >= $class_start && $current_time <= $class_end) {
                                                    $status = 'In Progress';
                                                    $status_class = 'status-in-progress';
                                                    $badge_class = 'bg-success';
                                                } elseif ($current_time < $class_start) {
                                                    $status = 'Upcoming';
                                                    $status_class = 'status-upcoming';
                                                    $badge_class = 'bg-info';
                                                } else {
                                                    $status = 'Completed';
                                                    $status_class = 'status-completed';
                                                    $badge_class = 'bg-secondary';
                                                }
                                                
                                                // Check if attendance data exists for this class today
                                                $attendance_data = null;
                                                foreach ($today_attendance_summary as $attendance) {
                                                    if ($attendance['year'] == $class['year_level'] && 
                                                        $attendance['section'] == $class['section'] &&
                                                        $attendance['subject'] == $class['subject']) {
                                                        $attendance_data = $attendance;
                                                        break;
                                                    }
                                                }
                                            ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="mb-1">
                                                            <span class="class-status-indicator <?php echo $status_class; ?>"></span>
                                                            <?php echo htmlspecialchars($class['subject']); ?>
                                                        </h6>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                                    </div>
                                                    
                                                    <p class="mb-1">
                                                        <i class="fas fa-door-open me-1 text-muted"></i>
                                                        Room: <?php echo htmlspecialchars($class['room_name']); ?>
                                                    </p>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-users me-1"></i>
                                                            Section: <?php echo htmlspecialchars($class['section']); ?> | 
                                                            Year: <?php echo htmlspecialchars($class['year_level'] ?? 'N/A'); ?>
                                                        </small>
                                                        <span class="time-badge">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo $start_time . ' - ' . $end_time; ?>
                                                        </span>
                                                    </div>

                                                    <!-- Attendance Summary -->
                                                    <?php if ($attendance_data): ?>
                                                    <div class="attendance-summary mt-2 p-2 bg-light rounded">
                                                        <div class="row text-center">
                                                            <div class="col-4">
                                                                <small class="text-success fw-bold"><?php echo $attendance_data['present_count']; ?></small>
                                                                <br><small class="text-muted">Present</small>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-danger fw-bold"><?php echo $attendance_data['absent_count']; ?></small>
                                                                <br><small class="text-muted">Absent</small>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-primary fw-bold"><?php echo $attendance_data['attendance_rate']; ?>%</small>
                                                                <br><small class="text-muted">Rate</small>
                                                            </div>
                                                        </div>
                                                        <div class="progress attendance-progress mt-1">
                                                            <div class="progress-bar bg-success" style="width: <?php echo $attendance_data['attendance_rate']; ?>%"></div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>

                                                    <div class="mt-3">
                                                        <a href="attendance.php?year=<?php echo urlencode($class['year_level']); ?>&section=<?php echo urlencode($class['section']); ?>&subject=<?php echo urlencode($class['subject']); ?>&date=<?php echo urlencode($today_date); ?>" 
                                                           class="btn btn-sm btn-outline-primary view-attendance-btn w-100">
                                                            <i class="fas fa-chart-bar me-1"></i>
                                                            <?php echo $attendance_data ? 'View Detailed Attendance' : 'View Attendance Records'; ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 3rem;"></i>
                                            <h5 class="text-muted">No Classes Today</h5>
                                            <p class="text-muted">Enjoy your day off! No classes scheduled for today.</p>
                                            <a href="schedule.php" class="btn btn-outline-primary">
                                                <i class="fas fa-calendar-alt me-2"></i>View Full Schedule
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Classes -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header bg-primary-custom d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-calendar-week me-2"></i>Upcoming Classes This Week</h5>
                                    <span class="badge bg-light text-dark"><?php echo $upcoming_classes ? $upcoming_classes->num_rows : 0; ?> total</span>
                                </div>
                                <div class="card-body">
                                    <?php if ($upcoming_classes && $upcoming_classes->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Day</th>
                                                        <th>Time</th>
                                                        <th>Subject</th>
                                                        <th>Room</th>
                                                        <th>Year Level</th>
                                                        <th>Section</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($class = $upcoming_classes->fetch_assoc()): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($class['day']); ?></strong>
                                                                <?php if ($class['day'] === $today_day): ?>
                                                                    <span class="badge bg-success ms-1">Today</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <span class="time-badge">
                                                                    <?php echo date("g:i A", strtotime($class['start_time'])) . ' - ' . date("g:i A", strtotime($class['end_time'])); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                                            <td><?php echo htmlspecialchars($class['room_name']); ?></td>                                       
                                                            <td><?php echo isset($class['year_level']) ? htmlspecialchars($class['year_level']) : '-'; ?></td>
                                                            <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                            <td>
                                                                <a href="attendance.php?year=<?php echo urlencode($class['year_level']); ?>&section=<?php echo urlencode($class['section']); ?>&subject=<?php echo urlencode($class['subject']); ?>" 
                                                                   class="btn btn-sm btn-outline-primary">
                                                                   <i class="fas fa-eye me-1"></i> View Records
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-plus text-muted mb-3" style="font-size: 3rem;"></i>
                                            <h5 class="text-muted">No Upcoming Classes</h5>
                                            <p class="text-muted">No classes scheduled for the rest of the week.</p>
                                            <a href="schedule.php" class="btn btn-primary">
                                                <i class="fas fa-calendar-alt me-2"></i>View Full Schedule
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript">
    // Load Google Charts
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawCharts);

    function drawCharts() {
        drawWeeklyAttendanceChart();
        drawAttendanceByClassChart();
    }

    function drawWeeklyAttendanceChart() {
        // Weekly attendance data from PHP
        const weeklyData = <?php echo json_encode($weekly_attendance_data); ?>;
        
        const data = new google.visualization.DataTable();
        data.addColumn('string', 'Day');
        data.addColumn('number', 'Attendance Rate (%)');
        data.addColumn('number', 'Present');
        data.addColumn('number', 'Absent');
        
        weeklyData.forEach(day => {
            data.addRow([
                day.day, 
                parseFloat(day.rate),
                parseInt(day.present),
                parseInt(day.absent)
            ]);
        });

        const options = {
            title: '',
            curveType: 'function',
            legend: { position: 'bottom' },
            colors: ['#5c95e9', '#1cc88a', '#e74a3b'],
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
                title: 'Attendance Rate (%)',
                titleTextStyle: {color: '#5a5c69', bold: true, fontSize: 12},
                minValue: 0,
                maxValue: 100,
                gridlines: { 
                    color: '#f0f0f0',
                    count: 5
                },
                baseline: 0,
                baselineColor: '#5a5c69',
                format: '0',
                viewWindow: {
                    min: 0,
                    max: 100
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
            },
            series: {
                0: { type: 'line', pointSize: 6 },
                1: { type: 'bars' },
                2: { type: 'bars' }
            }
        };

        const chart = new google.visualization.ComboChart(document.getElementById('weeklyAttendanceChart'));
        chart.draw(data, options);
    }

    function drawAttendanceByClassChart() {
        // Today's attendance by class data from PHP
        const attendanceData = <?php 
            $attendance_by_class = [];
            foreach ($today_attendance_summary as $summary) {
                $attendance_by_class[] = [
                    'class' => $summary['subject'] . ' (' . $summary['year'] . '-' . $summary['section'] . ')',
                    'present' => $summary['present_count'],
                    'absent' => $summary['absent_count'],
                    'rate' => $summary['attendance_rate']
                ];
            }
            echo json_encode($attendance_by_class);
        ?>;
        
        const data = new google.visualization.DataTable();
        data.addColumn('string', 'Class');
        data.addColumn('number', 'Present');
        data.addColumn('number', 'Absent');
        
        attendanceData.forEach(item => {
            data.addRow([
                item.class, 
                parseInt(item.present),
                parseInt(item.absent)
            ]);
        });

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
                position: 'bottom'
            },
            pieSliceText: 'percentage',
            colors: ['#1cc88a', '#e74a3b'],
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

        const chart = new google.visualization.PieChart(document.getElementById('attendanceByClassChart'));
        chart.draw(data, options);
    }

    // Redraw charts on window resize
    window.addEventListener('resize', function() {
        drawCharts();
    });
</script>

    <script>
        // Update current time every second
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            const dateString = now.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
            
            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
        }

        // Update time immediately and every second
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);

        // Auto-refresh page every 5 minutes to prevent timeout
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes

        // Sidebar toggle functionality for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });

        // Add smooth scrolling for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for view attendance buttons
            const viewButtons = document.querySelectorAll('.view-attendance-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Optional: Add loading state
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Loading...';
                    this.disabled = true;
                    
                    // Revert after 2 seconds if still on same page
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                });
            });
            
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.transition = 'background-color 0.2s ease';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($db); ?>