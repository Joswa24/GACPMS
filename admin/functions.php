<?php
// functions.php
function validateSession() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: index.php');
        exit();
    }
    
    // Validate session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired
        session_unset();
        session_destroy();
        header('Location: index.php?error=session_expired');
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

function getDashboardStats($db) {
    $today = date('Y-m-d');
    $stats = [];
    
    // Function to get count from database
    $getCount = function($query) use ($db) {
        $result = $db->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row["count"];
        }
        return 0;
    };
    
    // Total entrants today (from gate logs)
    $stats['total_entrants_today'] = $getCount("
        SELECT COUNT(*) AS count FROM (
            SELECT id FROM students_glogs WHERE date = '$today'
            UNION ALL
            SELECT id FROM instructor_glogs WHERE date = '$today'
            UNION ALL
            SELECT id FROM personell_glogs WHERE date = '$today'
            UNION ALL
            SELECT id FROM visitor_glogs WHERE date = '$today'
        ) AS combined_logs
    ");
    
    // Students
    $stats['total_students'] = $getCount("SELECT COUNT(*) AS count FROM students");
    $stats['students_today'] = $getCount("
        SELECT COUNT(DISTINCT student_id) AS count 
        FROM students_glogs 
        WHERE date = '$today'
    ");
    
    // Instructors
    $stats['total_instructors'] = $getCount("SELECT COUNT(*) AS count FROM instructor");
    $stats['instructors_today'] = $getCount("
        SELECT COUNT(DISTINCT instructor_id) AS count 
        FROM instructor_glogs 
        WHERE date = '$today'
    ");
    
    // Staff (personell with specific roles)
    $stats['total_staff'] = $getCount("SELECT COUNT(*) AS count FROM personell WHERE role IN ('Staff', 'Security Personnel', 'Executive', 'Data Analyst', 'Developer', 'Designer', 'Service Manager', 'Operator')");
    $stats['staff_today'] = $getCount("
        SELECT COUNT(DISTINCT personnel_id) AS count 
        FROM personell_glogs 
        WHERE date = '$today'
    ");
    
    // Visitors and Blocked (personell with status = 'Block' - but status column doesn't exist in your schema)
    $stats['visitors_today'] = $getCount("SELECT COUNT(DISTINCT visitor_id) AS count FROM visitor_glogs WHERE date = '$today'");
    $stats['blocked'] = 0; // No status column in personell table
    
    return $stats;
}

function getTodaysLogs($db) {
    $today = date('Y-m-d');
    
    $query = "
    -- Student logs from gate
    SELECT 
        s.photo,
        s.department_id as department,
        s.id_number,
        'Student' as role,
        s.fullname as full_name,
        sg.time_in,
        sg.time_out,
        sg.location,
        sg.date
    FROM students_glogs sg
    JOIN students s ON sg.student_id = s.id
    WHERE sg.date = '$today'
    
    UNION ALL
    
    -- Instructor logs from gate
    SELECT 
        '' as photo, -- instructors don't have photos in your schema
        i.department_id as department,
        i.id_number,
        'Instructor' as role,
        i.fullname as full_name,
        ig.time_in,
        ig.time_out,
        ig.location,
        ig.date
    FROM instructor_glogs ig
    JOIN instructor i ON ig.instructor_id = i.id
    WHERE ig.date = '$today'
    
    UNION ALL
    
    -- Personell logs from gate
    SELECT 
        p.photo,
        p.department,
        p.id_number,
        p.role,
        CONCAT(p.first_name, ' ', p.last_name) as full_name,
        pg.time_in,
        pg.time_out,
        pg.location,
        pg.date
    FROM personell_glogs pg
    JOIN personell p ON pg.personnel_id = p.id
    WHERE pg.date = '$today'
    
    UNION ALL
    
    -- Visitor logs from gate
    SELECT 
        v.photo,
        v.department,
        v.rfid_number as id_number,
        'Visitor' as role,
        v.name as full_name,
        vg.time as time_in,
        '00:00:00' as time_out, -- visitors might not have time_out in your schema
        vg.location,
        vg.date
    FROM visitor_glogs vg
    JOIN visitor v ON vg.visitor_id = v.id
    WHERE vg.date = '$today'
    
    ORDER BY 
        CASE 
            WHEN time_out != '00:00:00' THEN time_out 
            ELSE time_in 
        END DESC
    ";
    
    return mysqli_query($db, $query);
}

function getHoverLogs($db, $type, $limit = 10) {
    $today = date('Y-m-d');
    
    switch ($type) {
        case 'visitors':
            $sql = "
            SELECT 
                v.photo,
                v.name AS full_name,
                v.department,
                vg.time as time_in
            FROM visitor_glogs vg
            JOIN visitor v ON vg.visitor_id = v.id
            WHERE vg.date = '$today'
            ORDER BY vg.time DESC
            LIMIT $limit";
            break;
            
        case 'blocked':
            // Since there's no status column, return empty or handle differently
            $sql = "
            SELECT 
                photo,
                CONCAT(first_name, ' ', last_name) AS full_name,
                role,
                department
            FROM personell 
            WHERE 1=0  -- No blocked functionality in current schema
            LIMIT $limit";
            break;
            
        case 'entrants':
            $sql = "
            SELECT 
                p.photo,
                CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                p.role,
                pg.time_in
            FROM personell_glogs pg
            JOIN personell p ON pg.personnel_id = p.id
            WHERE pg.date = '$today'
            
            UNION ALL
            
            SELECT 
                v.photo,
                v.name AS full_name,
                'Visitor' AS role,
                vg.time as time_in
            FROM visitor_glogs vg
            JOIN visitor v ON vg.visitor_id = v.id
            WHERE vg.date = '$today'
            
            UNION ALL
            
            SELECT 
                s.photo,
                s.fullname AS full_name,
                'Student' AS role,
                sg.time_in
            FROM students_glogs sg
            JOIN students s ON sg.student_id = s.id
            WHERE sg.date = '$today'
            
            UNION ALL
            
            SELECT 
                '' as photo,
                i.fullname AS full_name,
                'Instructor' AS role,
                ig.time_in
            FROM instructor_glogs ig
            JOIN instructor i ON ig.instructor_id = i.id
            WHERE ig.date = '$today'
            
            ORDER BY time_in DESC
            LIMIT $limit";
            break;
            
        case 'students':
            $sql = "
            SELECT 
                s.photo,
                s.fullname AS full_name,
                d.department_name as department,
                sg.time_in,
                CASE 
                    WHEN sg.date = '$today' THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM students s
            LEFT JOIN students_glogs sg ON sg.student_id = s.id AND sg.date = '$today'
            LEFT JOIN department d ON s.department_id = d.department_id
            ORDER BY s.fullname
            LIMIT $limit";
            break;
            
        case 'instructors':
            $sql = "
            SELECT 
                '' as photo, -- instructors don't have photos in your schema
                i.fullname AS full_name,
                d.department_name as department,
                ig.time_in,
                CASE 
                    WHEN ig.date = '$today' THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM instructor i
            LEFT JOIN instructor_glogs ig ON ig.instructor_id = i.id AND ig.date = '$today'
            LEFT JOIN department d ON i.department_id = d.department_id
            ORDER BY i.fullname
            LIMIT $limit";
            break;
            
        case 'staff':
            $sql = "
            SELECT 
                p.photo,
                CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                p.department,
                p.role,
                pg.time_in,
                CASE 
                    WHEN pg.date = '$today' THEN 'Present'
                    ELSE 'Absent'
                END as status
            FROM personell p
            LEFT JOIN personell_glogs pg ON pg.personnel_id = p.id AND pg.date = '$today'
            WHERE p.role IN ('Staff', 'Security Personnel', 'Executive', 'Data Analyst', 'Developer', 'Designer', 'Service Manager', 'Operator')
            ORDER BY p.first_name, p.last_name
            LIMIT $limit";
            break;
            
        default:
            return [];
    }
    
    $result = $db->query($sql);
    $logs = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    return $logs;
}

function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function formatTime($time) {
    if (!$time || $time == '00:00:00') {
        return '-';
    }
    return date('h:i A', strtotime($time));
}
?>