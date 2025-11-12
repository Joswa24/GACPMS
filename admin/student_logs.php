<?php
session_start();
include 'header.php';
include '../connection.php';

// Create the dedicated table if it doesn't exist
$createTableQuery = "
CREATE TABLE IF NOT EXISTS instructor_attendance_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT(55) NOT NULL,
    instructor_name VARCHAR(255) NOT NULL,
    subject_name VARCHAR(255),
    year_level VARCHAR(50),
    section VARCHAR(50),
    room VARCHAR(100),
    total_students INT DEFAULT 0,
    present_count INT DEFAULT 0,
    absent_count INT DEFAULT 0,
    attendance_rate DECIMAL(5,2) DEFAULT 0.00,
    time_in DATETIME,
    time_out DATETIME,
    session_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_instructor_date (instructor_name, session_date),
    INDEX idx_date (session_date),
    INDEX idx_instructor (instructor_name)
)";

if ($db->query($createTableQuery) === TRUE) {
    // Table created or already exists
    error_log("Instructor attendance dedicated table ready");
} else {
    error_log("Error creating table: " . $db->error);
}

// Function to populate the dedicated table
function populateDedicatedTable($db, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    // Remove existing records for the date to avoid duplicates
    $deleteQuery = "DELETE FROM instructor_attendance_dedicated WHERE session_date = ?";
    $stmt = $db->prepare($deleteQuery);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stmt->close();
    
    // Insert data from the original summary table
    $insertQuery = "
        INSERT INTO instructor_attendance_dedicated (
            instructor_name, subject_name, year_level, section, room, 
            total_students, present_count, absent_count, attendance_rate,
            time_in, time_out, session_date
        )
        SELECT 
            instructor_name,
            subject_name,
            year_level,
            section,
            room,
            total_students,
            present_count,
            absent_count,
            attendance_rate,
            MIN(time_in) as time_in,
            MAX(CASE WHEN time_out != '0000-00-00 00:00:00' THEN time_out ELSE NULL END) as time_out,
            session_date
        FROM instructor_attendance_admin 
        WHERE session_date = ?
        GROUP BY instructor_name, subject_name, year_level, section, session_date
        ORDER BY instructor_name, time_in
    ";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bind_param("s", $date);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Auto-populate data for today when page loads
populateDedicatedTable($db, date('Y-m-d'));

// Date filter logic
if (isset($_GET['date']) && $_GET['date'] !== '') {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// Add instructor filter logic
$search_instructor = isset($_GET['search_instructor']) ? trim($_GET['search_instructor']) : '';
$search_subject = isset($_GET['search_subject']) ? trim($_GET['search_subject']) : '';

// Manual refresh action
if (isset($_GET['refresh_data'])) {
    if (populateDedicatedTable($db, $selected_date)) {
        $_SESSION['message'] = "Data refreshed successfully!";
    } else {
        $_SESSION['message'] = "Error refreshing data!";
    }
    header('Location: instructor_attendance_summary.php?date=' . $selected_date);
    exit();
}

// Build the query to fetch data from the DEDICATED table
$query = "SELECT * FROM instructor_attendance_admin WHERE session_date = ?";
$params = [$selected_date];
$types = "s";

// Add instructor filter
if ($search_instructor !== '') {
    $query .= " AND instructor_name LIKE ?";
    $params[] = "%$search_instructor%";
    $types .= "s";
}

// Add subject filter
if ($search_subject !== '') {
    $query .= " AND subject_name LIKE ?";
    $params[] = "%$search_subject%";
    $types .= "s";
}

$query .= " ORDER BY time_in DESC";

$stmt = $db->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $db->error);
}

if (!empty($params)) {
    $bind_result = $stmt->bind_param($types, ...$params);
    if ($bind_result === false) {
        die("Error binding parameters: " . $stmt->error);
    }
}

$execute_result = $stmt->execute();
if ($execute_result === false) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
$attendance_data = [];
while ($row = $result->fetch_assoc()) {
    $attendance_data[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Attendance Summary - RFIDGPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add SweetAlert CSS -->
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
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
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
            max-height: 600px;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .badge-present {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            color: white;
        }

        .badge-absent {
            background: linear-gradient(135deg, var(--danger-color), #be2617);
            color: white;
        }

        .badge-high-attendance {
            background: linear-gradient(135deg, var(--info-color), #2e59d9);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
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

        /* Filter Button */
        .btn-filter {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            box-shadow: 0 4px 15px rgba(92, 149, 233, 0.3);
        }

        .btn-filter:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
            color: white;
        }

        /* Refresh Button */
        .btn-refresh {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-refresh:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        /* Export Button */
        .btn-export {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            color: white;
            box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
        }

        .btn-export:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
            color: white;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.875rem;
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

        .action-buttons {
            white-space: nowrap;
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .filter-section {
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            transition: var(--transition);
        }

        .duration-badge {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            font-size: 0.75rem;
            padding: 4px 8px;
        }

        /* Button container styling */
        .button-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        /* Loading spinner */
        .spinner-border {
            width: 1rem;
            height: 1rem;
        }

        .record-count {
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-4"><i class="fas fa-chalkboard-teacher me-2"></i>Instructor Attendance Summary</h6>
                            </div>
                        </div>

                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-success mt-3">
                                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form method="get" class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <label for="date" class="form-label fw-bold">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?php echo htmlspecialchars($selected_date); ?>" 
                                           max="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="search_instructor" class="form-label fw-bold">Instructor</label>
                                    <input type="text" class="form-control" id="search_instructor" name="search_instructor" 
                                           placeholder="Search instructor" 
                                           value="<?php echo htmlspecialchars($search_instructor); ?>">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="search_subject" class="form-label fw-bold">Subject</label>
                                    <input type="text" class="form-control" id="search_subject" name="search_subject" 
                                           placeholder="Search subject" 
                                           value="<?php echo htmlspecialchars($search_subject); ?>">
                                </div>
                                <div class="col-lg-3 col-md-6 d-flex align-items-end">
                                    <div class="button-container w-100">
                                        <button type="submit" class="btn btn-filter">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="?date=<?php echo $selected_date; ?>&refresh_data=true" class="btn btn-refresh">
                                            <i class="fas fa-sync"></i> Refresh
                                        </a>
                                        <button type="button" class="btn btn-export" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <h6 class="p-3">
                                        Instructor Attendance Summary for: <span class="text-primary"><?php echo date('F d, Y', strtotime($selected_date)); ?></span>
                                        <span class="badge bg-primary ms-2 record-count"><?php echo count($attendance_data); ?> Records</span>
                                    </h6>
                                    
                                    <!-- Instructor Attendance Summary Table -->
                                    <table class="table table-striped table-hover mb-0" id="instructorAttendanceTable">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-user me-1"></i> Name</th>
                                                <th><i class="fas fa-book me-1"></i> Subject</th>
                                                <th><i class="fas fa-graduation-cap me-1"></i> Year Level</th>
                                                <th><i class="fas fa-users me-1"></i> Section</th>
                                                <th><i class="fas fa-door-open me-1"></i> Room</th>
                                                <th><i class="fas fa-user-friends me-1"></i> Total Students</th>
                                                <th><i class="fas fa-user-check me-1"></i> Present</th>
                                                <th><i class="fas fa-user-times me-1"></i> Absent</th>
                                                <th><i class="fas fa-percentage me-1"></i> Attendance Rate</th>
                                                <th><i class="fas fa-sign-in-alt me-1"></i> Time In</th>
                                                <th><i class="fas fa-sign-out-alt me-1"></i> Time Out</th>
                                                <th><i class="fas fa-clock me-1"></i> Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($attendance_data) > 0): ?>
                                                <?php foreach ($attendance_data as $row): ?>
                                                    <?php
                                                    // Calculate duration
                                                    $duration = 'N/A';
                                                    if ($row['time_in'] && $row['time_out'] && $row['time_out'] != '0000-00-00 00:00:00') {
                                                        $time_in = new DateTime($row['time_in']);
                                                        $time_out = new DateTime($row['time_out']);
                                                        $interval = $time_in->diff($time_out);
                                                        $duration = $interval->format('%h h %i m');
                                                    }
                                                    
                                                    // Determine badge class for attendance rate
                                                    $attendance_badge = '';
                                                    if ($row['attendance_rate'] == 100) {
                                                        $attendance_badge = 'badge-present';
                                                    } elseif ($row['attendance_rate'] >= 80) {
                                                        $attendance_badge = 'badge-high-attendance';
                                                    } elseif ($row['attendance_rate'] > 0) {
                                                        $attendance_badge = 'badge-warning';
                                                    } else {
                                                        $attendance_badge = 'badge-absent';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($row['instructor_name']); ?></strong></td>
                                                        <td><?php echo !empty($row['subject_name']) ? htmlspecialchars($row['subject_name']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                        <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['section']); ?></td>
                                                        <td><?php echo !empty($row['room']) ? htmlspecialchars($row['room']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                        <td><?php echo htmlspecialchars($row['total_students']); ?></td>
                                                        <td><span class="badge badge-present"><?php echo htmlspecialchars($row['present_count']); ?></span></td>
                                                        <td><span class="badge badge-absent"><?php echo htmlspecialchars($row['absent_count']); ?></span></td>
                                                        <td><span class="badge <?php echo $attendance_badge; ?>"><?php echo htmlspecialchars($row['attendance_rate']); ?>%</span></td>
                                                        <td><?php echo $row['time_in'] && $row['time_in'] != '0000-00-00 00:00:00' ? date('h:i A', strtotime($row['time_in'])) : 'N/A'; ?></td>
                                                        <td><?php echo $row['time_out'] && $row['time_out'] != '0000-00-00 00:00:00' ? date('h:i A', strtotime($row['time_out'])) : 'N/A'; ?></td>
                                                        <td><?php echo $duration; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="12" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="fa fa-search fa-2x mb-2"></i><br>
                                                            No instructor attendance records found for <?php echo date('F d, Y', strtotime($selected_date)); ?>
                                                            <br>
                                                            <small class="mt-2 d-block">Click "Refresh" to update data from the main table</small>
                                                        </div>
                                                    </td>
                                                </tr>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Add SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function exportToExcel() {
            Swal.fire({
                title: 'Export to Excel?',
                text: 'This will export all filtered data to an Excel file.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Export',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const table = document.getElementById('instructorAttendanceTable');
                    const ws = XLSX.utils.table_to_sheet(table);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Instructor Attendance Summary");
                    
                    const date = new Date().toISOString().split('T')[0];
                    XLSX.writeFile(wb, `instructor_attendance_${date}.xlsx`);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Exported!',
                        text: 'Data exported successfully to Excel',
                        confirmButtonColor: '#1cc88a',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }

        // Add some custom styling to match your theme
        const style = document.createElement('style');
        style.textContent = `
            .swal2-popup {
                border-radius: 15px;
                font-family: 'Inter', sans-serif;
            }
            .swal2-title {
                color: var(--dark-text);
            }
            .swal2-confirm {
                border-radius: 8px;
                font-weight: 500;
            }
            .swal2-cancel {
                border-radius: 8px;
                font-weight: 500;
            }
        `;
        document.head.appendChild(style);

        // Auto-refresh notification
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('refresh_data')) {
                // Show a subtle notification that data was refreshed
                setTimeout(() => {
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    toast.style.top = '20px';
                    toast.style.right = '20px';
                    toast.style.zIndex = '9999';
                    toast.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        Data refreshed successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(toast);
                    
                    // Remove after 3 seconds
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 3000);
                }, 500);
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($db); ?>