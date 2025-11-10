<?php
// Start session at the very beginning
session_start();

// Initialize database connection
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
// Check if database connection was successful
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to get personnel photo (same as in personell.php)
function getPersonnelPhoto($photo) {
    $basePath = '../uploads/personell/';
    $defaultPhoto = '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg';

    // If no photo or file does not exist → return default
    if (empty($photo) || $photo === 'default.png' || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Initialize filtered data array
$filtered_data = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize filter variables
    $date1 = isset($_POST['date1']) ? $_POST['date1'] : '';
    $date2 = isset($_POST['date2']) ? $_POST['date2'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    
    // Validate dates - either both empty or both provided
    if (($date1 && !$date2) || (!$date1 && $date2)) {
        echo '<script>alert("Please enter both dates or leave both blank.");</script>';
    } else {
        // Build query with proper filtering using the correct table name
        $sql = "SELECT pg.id, pg.personell_id, pg.id_number, pg.name, pg.action, 
                       pg.time_in, pg.time_out, pg.date, pg.period, pg.location, 
                       pg.department, pg.date_logged, pg.created_at,
                       p.first_name, p.last_name, p.photo, p.role
                FROM personell_glogs AS pg
                LEFT JOIN personell AS p ON pg.personell_id = p.id";
        
        $where = [];
        $params = [];
        $types = '';
        
        // Add date filter if both dates provided
        if ($date1 && $date2) {
            $where[] = "pg.date_logged BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime($date1));
            $params[] = date('Y-m-d', strtotime($date2));
            $types .= 'ss';
        }
        
        // Add role filter (excluding Instructor)
        if ($role) {
            $where[] = "p.role = ?";
            $params[] = $role;
            $types .= 's';
        } else {
            // If no role selected, exclude Instructor by default
            $where[] = "p.role != 'Instructor'";
        }
        
        // Combine WHERE clauses
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY pg.date_logged DESC, pg.time_in DESC";
        
        // Prepare and execute query
        if ($stmt = $db->prepare($sql)) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $filtered_data = $result->fetch_all(MYSQLI_ASSOC);
            $_SESSION['filtered_data'] = $filtered_data;
            $stmt->close();
        }
    }
} else {
    // Default query when no filters applied - using the correct table
    $sql = "SELECT pg.id, pg.personell_id, pg.id_number, pg.name, pg.action, 
                   pg.time_in, pg.time_out, pg.date, pg.period, pg.location, 
                   pg.department, pg.date_logged, pg.created_at,
                   p.first_name, p.last_name, p.photo, p.role
            FROM personell_glogs AS pg
            LEFT JOIN personell AS p ON pg.personell_id = p.id
            WHERE p.role != 'Instructor'
            ORDER BY pg.date_logged DESC, pg.time_in DESC";
    
    $result = mysqli_query($db, $sql);
    if ($result) {
        $filtered_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $_SESSION['filtered_data'] = $filtered_data;
    } else {
        die("Query failed: " . mysqli_error($db));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">
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

        /* Filter Button */
        .btn-filter {
            background: linear-gradient(135deg, var(--icon-color), #4a7ec7);
            color: white;
            box-shadow: 0 4px 15px rgba(92, 149, 233, 0.3);
        }

        .btn-filter:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
            color: white;
        }

        /* Reset Button */
        .btn-reset {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-reset:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        /* Print Button */
        .btn-print {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            color: white;
            box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
            color: white;
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

        /* Button container styling */
        .button-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        /* Table action buttons container */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        /* Photo styling */
        .personnel-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        /* Loading spinner */
        .spinner-border {
            width: 1rem;
            height: 1rem;
        }

        /* Action badge styling */
        .badge-in {
            background-color: var(--success-color);
        }
        
        .badge-out {
            background-color: var(--danger-color);
        }

        /* Custom Datepicker Styles */
        .datepicker {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
        }

        .datepicker table {
            width: 100%;
        }

        .datepicker .datepicker-days tbody td,
        .datepicker .datepicker-months tbody td,
        .datepicker .datepicker-years tbody td {
            border-radius: 8px;
            transition: var(--transition);
        }

        .datepicker .datepicker-days tbody td.day:hover,
        .datepicker .datepicker-months tbody td.month:hover,
        .datepicker .datepicker-years tbody td.year:hover {
            background: rgba(92, 149, 233, 0.1);
        }

        .datepicker .datepicker-days tbody td.active,
        .datepicker .datepicker-days tbody td.active:hover,
        .datepicker .datepicker-months tbody td.active,
        .datepicker .datepicker-months tbody td.active:hover,
        .datepicker .datepicker-years tbody td.active,
        .datepicker .datepicker-years tbody td.active:hover {
            background: linear-gradient(135deg, var(--icon-color), #4a7ec7);
            color: white;
            border-radius: 8px;
        }

        .datepicker .datepicker-days tbody td.today {
            background: rgba(92, 149, 233, 0.2);
            color: var(--dark-text);
            border-radius: 8px;
        }

        .datepicker .datepicker-days tbody td.today:hover {
            background: rgba(92, 149, 233, 0.3);
        }

        .datepicker .datepicker-switch,
        .datepicker .prev,
        .datepicker .next {
            color: var(--icon-color);
            font-weight: 600;
        }

        .datepicker .datepicker-switch:hover,
        .datepicker .prev:hover,
        .datepicker .next:hover {
            background: rgba(92, 149, 233, 0.1);
            border-radius: 8px;
        }

        .datepicker .dow {
            color: var(--icon-color);
            font-weight: 600;
        }

        .datepicker .datepicker-days tbody td.disabled,
        .datepicker .datepicker-days tbody td.disabled:hover {
            color: #ccc;
            background: transparent;
            cursor: not-allowed;
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
                            <div class="col-9">
                                <h6 class="mb-4">Personnel Logs</h6>
                            </div>
                        </div>
                        <br>
                        <form id="filterForm" method="POST">
                            <div class="row">
                                <div class="col-lg-3">
                                    <label>Date:</label>
                                    <input type="text" class="form-control" name="date1" placeholder="Start" id="date1" autocomplete="off" />
                                </div>
                                <div class="col-lg-3">
                                    <label>To:</label>
                                    <input type="text" class="form-control" name="date2" placeholder="End" id="date2" autocomplete="off" />
                                </div>
                                <div class="col-lg-2">
                                    <label>Role:</label>
                                    <select class="form-control dept_ID" name="role" id="role" autocomplete="off">
                                        <option value="">All Roles</option>
                                        <?php
                                        $role_result = $db->query("SELECT * FROM role WHERE role != 'Instructor'");
                                        while ($row = $role_result->fetch_assoc()) {
                                            echo "<option value='{$row['role']}'>{$row['role']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-lg-2 mt-4">
                                    <label></label>
                                    <div class="button-container">
                                        <button type="submit" class="btn btn-filter" id="btn_search">
                                            <i class="fa fa-search"></i> Filter
                                        </button>
                                        <button type="button" id="reset" class="btn btn-reset">
                                            <i class="fa fa-sync"></i> Reset
                                        </button>
                                    </div>
                                </div>
                                <div class="col-lg-2 mt-4" style="text-align:right;">
                                    <label></label>
                                    <button type="button" class="btn btn-print" id="btn_print">
                                        <i class="fa fa-print"></i> Print
                                    </button> 
                                </div>
                            </div>
                        </form>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Full Name</th>
                                        <th>ID Number</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="load_data">
                                    <?php
                                    if (!empty($filtered_data)) {
                                        foreach ($filtered_data as $row) {
                                            echo '<tr>';
                                            
                                            // Display photo using the same function as personell.php
                                            $photoPath = getPersonnelPhoto($row['photo']);
                                            echo '<td><center>';
                                            echo '<img class="personnel-photo" src="' . $photoPath . '" ';
                                            echo 'onerror="this.onerror=null; this.src=\'../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg\';" ';
                                            echo 'alt="' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '" ';
                                            echo 'style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6;">';
                                            echo '</center></td>';
                                            
                                            // Display name - prefer name from personell table if available
                                            if (!empty($row['first_name']) && !empty($row['last_name'])) {
                                                echo '<td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>';
                                            } else {
                                                echo '<td>' . $row['name'] . '</td>';
                                            }
                                            
                                            echo '<td>' . $row['id_number'] . '</td>';
                                            
                                            // Display time in
                                            if (!empty($row['time_in']) && $row['time_in'] != '00:00:00') {
                                                echo '<td>' . date("h:i A", strtotime($row['time_in'])) . '</td>';
                                            } else {
                                                echo '<td>-</td>';
                                            }
                                            
                                            // Display time out
                                            if (!empty($row['time_out']) && $row['time_out'] != '00:00:00') {
                                                echo '<td>' . date("h:i A", strtotime($row['time_out'])) . '</td>';
                                            } else {
                                                echo '<td>-</td>';
                                            }
                                            
                                            echo '<td>' . $row['date'] . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="10">No records found.</td></tr>';
                                    }
                                    ?>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.min.js"></script>
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
    $(document).ready(function() {
        // Get today's date
        var today = new Date();
        
        // Initialize datepickers with blue theme and future dates disabled
        $('#date1, #date2').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true,
            endDate: today, // Disable future dates
            templates: {
                leftArrow: '<i class="fa fa-chevron-left text-primary"></i>',
                rightArrow: '<i class="fa fa-chevron-right text-primary"></i>'
            }
        }).on('show', function() {
            // Add custom class to datepicker dropdown for styling
            $('.datepicker').addClass('blue-theme');
        });
        
        // Handle search button click
        $('#btn_search').on('click', function() {
            $date1 = $('#date1').val();
            $date2 = $('#date2').val();
            
            if (($date1 && !$date2) || (!$date1 && $date2)) {
                alert("Please enter both dates or leave both blank.");
                return false;
            }
            
            $('#load_data').empty();
            $loader = $('<tr><td colspan="10"><center><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Searching....</center></td></tr>');
            $loader.appendTo('#load_data');
            
            setTimeout(function() {
                $loader.remove();
                $('#filterForm').submit();
            }, 1000);
        });

        // Handle reset button click
        $('#reset').on('click', function() {
            location.reload();
        });

        // Handle print button click
        $('#btn_print').on('click', function() {
            // Submit the form to print.php in a new window
            $('#filterForm').attr('action', 'print.php').attr('target', '_blank').submit();
            // Reset the form action for normal filtering
            $('#filterForm').attr('action', '').removeAttr('target');
        });
    });
    </script>
</body>
</html>