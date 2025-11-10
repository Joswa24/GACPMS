<?php
session_start();
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

// Initialize filtered data array
$filtered_data = [];

// Check if form is submitted via POST or if session data exists
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Same filtering logic as your main page
    $date1 = isset($_POST['date1']) ? $_POST['date1'] : '';
    $date2 = isset($_POST['date2']) ? $_POST['date2'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    
    // Build query with proper filtering
    $sql = "SELECT p.first_name, p.last_name, p.department, p.role, p.photo, 
                   rl.location, rl.time_in, rl.time_out, rl.date_logged 
            FROM personell AS p
            JOIN room_logs AS rl ON p.id = rl.personnel_id";
    
    $where = [];
    $params = [];
    $types = '';
    
    // Add date filter if both dates provided
    if ($date1 && $date2) {
        $where[] = "rl.date_logged BETWEEN ? AND ?";
        $params[] = date('Y-m-d', strtotime($date1));
        $params[] = date('Y-m-d', strtotime($date2));
        $types .= 'ss';
    }
    
    // Add other filters
    if ($location) {
        $where[] = "rl.location = ? COLLATE utf8mb4_unicode_ci";
        $params[] = $location;
        $types .= 's';
    }
    
    if ($role) {
        $where[] = "p.role = ? COLLATE utf8mb4_unicode_ci";
        $params[] = $role;
        $types .= 's';
    }
    
    if ($department) {
        $where[] = "p.department = ? COLLATE utf8mb4_unicode_ci";
        $params[] = $department;
        $types .= 's';
    }
    
    // Combine WHERE clauses
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    $sql .= " ORDER BY rl.date_logged DESC";
    
    // Execute query
    if ($stmt = $db->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $filtered_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} elseif (isset($_SESSION['filtered_data'])) {
    $filtered_data = $_SESSION['filtered_data'];
}

// Generate report title based on filters
$report_title = "Campus Entrance Log Monitoring Report";
if ($date1 && $date2) {
    $report_title .= " (".date('M d, Y', strtotime($date1))." to ".date('M d, Y', strtotime($date2)).")";
}
if ($location) {
    $report_title .= " - Location: ".htmlspecialchars($location);
}
if ($department) {
    $report_title .= " - Department: ".htmlspecialchars($department);
}
if ($role) {
    $report_title .= " - Role: ".htmlspecialchars($role);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Campus Entrance Log Report</title>
    <style>
        @page {
            size: auto;
            margin: 5mm;
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header { 
            text-align: center;
            margin-bottom: 20px;
        }
        .header img { 
            max-width: 100%; 
            height: auto;
        }
        h1 { 
            font-size: 24px;
            margin: 10px 0;
            color: #2c3e50;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            font-size: 12px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left;
        }
        th { 
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .photo-cell { 
            width: 60px;
            text-align: center;
        }
        .photo-cell img { 
            max-width: 50px; 
            max-height: 50px;
            border-radius: 3px;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #777;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 11px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="uploads/header1.png"/>
        <h1><?php echo $report_title; ?></h1>
        <div class="report-date">Generated on: <?php echo date('F j, Y h:i A'); ?></div>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Location</th>
                    <th>Role</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Log Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($filtered_data)): ?>
                    <?php foreach ($filtered_data as $row): ?>
                        <tr>
                            <td class="photo-cell">
                                <?php if (!empty($row['photo']) && file_exists('uploads/'.$row['photo'])): ?>
                                    <img src="uploads/<?php echo $row['photo']; ?>">
                                <?php else: ?>
                                    <span>N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo date("h:i A", strtotime($row['time_in'])); ?></td>
                            <td>
                                <?php echo ($row['time_out'] === '?' || $row['time_out'] === '' || is_null($row['time_out'])) ? 
                                    htmlspecialchars($row['time_out']) : date("h:i A", strtotime($row['time_out'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['date_logged']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-data">No records found matching the selected criteria</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        Report generated by Campus Security System
    </div>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
                // Close the window after printing
                setTimeout(function() {
                    window.close();
                }, 500);
            }, 200);
        };
    </script>
</body>
</html>