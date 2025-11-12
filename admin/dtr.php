<?php
session_start();
if (isset($_SESSION['reload_flag'])) {
    // Unset specific session variables
    unset($_SESSION['month']); 
    unset($_SESSION['name']);
    unset($_SESSION['id']);
} 

 $id = 0;
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
?>
<?php
include 'header.php';

// Check if there's a search query
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['query'])) {
    
    $query = trim($_POST['query']);  // Get the search query and remove leading/trailing spaces

    // Search in instructors
    $sql1 = "SELECT id, name, 'instructor' as type 
             FROM instructor_glogs 
             WHERE name LIKE ?";
    
    // Search in personnel
    $sql2 = "SELECT id, name, 'personell' as type 
             FROM personell_glogs 
             WHERE name LIKE ?";
    
    // Use wildcard to match partial strings
    $searchTerm = "%" . $query . "%";  
    
    // Prepare and execute the first query
    $stmt1 = $db->prepare($sql1);
    $stmt1->bind_param("s", $searchTerm);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    
    // Prepare and execute the second query
    $stmt2 = $db->prepare($sql2);
    $stmt2->bind_param("s", $searchTerm);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    // Fetch the results into an array
    $instructors = [];
    while ($row = $result1->fetch_assoc()) {
        $instructors[] = $row;
    }
    
    $personnel = [];
    while ($row = $result2->fetch_assoc()) {
        $personnel[] = $row;
    }
    
    // Merge both results
    $searchResults = array_merge($instructors, $personnel);

    // Close the statements
    $stmt1->close();
    $stmt2->close();
}

// Handle holiday/suspension form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_holiday'])) {
    $holidayDate = $_POST['holiday_date'];
    $holidayType = $_POST['holiday_type'];
    $holidayDescription = $_POST['holiday_description'];
    
    // Insert into holidays table
    $sql = "INSERT INTO holidays (date, type, description) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sss", $holidayDate, $holidayType, $holidayDescription);
    $stmt->execute();
    $stmt->close();
    
    // Set a success message
    $_SESSION['message'] = "Holiday/Suspension added successfully!";
    
    // Redirect to prevent form resubmission
    header("Location: dtr.php" );
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTR System | Modern</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --danger-color: #e63946;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }
        
        .card-modern {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .card-header-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1.5rem;
            border: none;
        }
        
        .btn-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
        }
        
        .btn-warning-modern {
            background: linear-gradient(135deg, #ff9e00, #ff6b00);
            color: white;
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, #06d6a0, #04a777);
            color: white;
        }
        
        .form-control-modern {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .form-control-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .table-modern {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .table-modern thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .table-modern th {
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .table-modern td {
            padding: 0.75rem 1rem;
            border-color: #f0f0f0;
        }
        
        .table-modern tbody tr {
            transition: var(--transition);
        }
        
        .table-modern tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .holiday-day {
            background-color: rgba(255, 107, 0, 0.1) !important;
            color: #ff6b00;
            font-weight: 500;
        }
        
        .suspension-day {
            background-color: rgba(230, 57, 70, 0.1) !important;
            color: #e63946;
            font-weight: 500;
        }
        
        .search-container {
            position: relative;
        }
        
        #suggestions {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 5px;
        }
        
        #suggestions div {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }
        
        #suggestions div:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .modal-modern .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .modal-modern .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border: none;
        }
        
        .modal-modern .modal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 1.5rem;
        }
        
        .dtr-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .dtr-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .dtr-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .dtr-header h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .dtr-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .dtr-table th, .dtr-table td {
            border: 1px solid #e0e0e0;
            padding: 0.75rem;
            text-align: center;
        }
        
        .dtr-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .dtr-footer {
            margin-top: 2rem;
        }
        
        .dtr-footer p {
            font-size: 0.9rem;
            text-align: justify;
            margin-bottom: 1.5rem;
        }
        
        .dtr-signature {
            text-align: right;
            margin-top: 3rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stats-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stats-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .nav-tabs-modern {
            border-bottom: 2px solid #e9ecef;
        }
        
        .nav-tabs-modern .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px 8px 0 0;
            transition: var(--transition);
        }
        
        .nav-tabs-modern .nav-link.active {
            color: var(--primary-color);
            background-color: white;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs-modern .nav-link:hover {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .holiday-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .holiday-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }
        
        .holiday-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .holiday-date {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .holiday-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .holiday-type.holiday {
            background-color: rgba(255, 107, 0, 0.1);
            color: #ff6b00;
        }
        
        .holiday-type.suspension {
            background-color: rgba(230, 57, 70, 0.1);
            color: #e63946;
        }
        
        .holiday-actions button {
            background: none;
            border: none;
            color: #6c757d;
            transition: var(--transition);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        .holiday-actions button:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .dtr-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-clock me-2"></i>DTR System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-users me-1"></i> Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i> Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 mb-4 no-print">
                <div class="card-modern">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action active">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-user-clock me-2"></i> Time Records
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-users me-2"></i> Employees
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-calendar-alt me-2"></i> Schedule
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-chart-bar me-2"></i> Reports
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="card-modern mt-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Monthly Overview</h6>
                        <div class="stats-card">
                            <div class="number">24</div>
                            <div class="label">Working Days</div>
                        </div>
                        <div class="stats-card">
                            <div class="number">3</div>
                            <div class="label">Holidays</div>
                        </div>
                        <div class="stats-card">
                            <div class="number">98%</div>
                            <div class="label">Attendance Rate</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <h2 class="h4 fw-bold mb-0">Daily Time Record</h2>
                        <p class="text-muted mb-0">Generate and manage employee time records</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-warning-modern me-2" data-bs-toggle="modal" data-bs-target="#holidayModal">
                            <i class="fas fa-calendar-plus me-1"></i> Manage Holidays
                        </button>
                        <button onclick="printDiv('dtr-container')" type="button" class="btn btn-success-modern">
                            <i class="fas fa-print me-1"></i> Print DTR
                        </button>
                    </div>
                </div>

                <!-- Search and Filter Card -->
                <div class="card-modern mb-4 no-print">
                    <div class="card-header-modern">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i> Search Employee</h5>
                    </div>
                    <div class="card-body">
                        <form id="filterForm" method="POST" action="">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label class="form-label fw-semibold">Employee Name</label>
                                    <div class="search-container">
                                        <input type="text" name="pname" class="form-control form-control-modern" id="searchInput" autocomplete="off" placeholder="Search by name...">
                                        <input hidden type="text" id="pername" name="pername" autocomplete="off">
                                        <input hidden type="text" id="perid" name="perid" autocomplete="off">
                                        <input hidden type="text" id="persontype" name="persontype" autocomplete="off">
                                        <div id="suggestions"></div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label fw-semibold">Month</label>
                                    <select class="form-control form-control-modern" id="months" name="month">
                                        <option value="<?php echo date('F'); ?>" selected><?php echo date('F'); ?></option>
                                        <option value="January">January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-modern w-100" id="btn_search">
                                        <i class="fas fa-search me-1"></i> Generate DTR
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- DTR Display -->
                <div class="dtr-container" id="dtr-container">
                    <div class="dtr-header">
                        <h5>Civil Service Form No. 48</h5>
                        <h4>DAILY TIME RECORD</h4>
                        <?php if (!empty($name)): ?>
                            <h1><?php echo htmlspecialchars($name); ?></h1>
                        <?php else: ?>
                            <h1 class="text-muted">(Employee Name)</h1>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between border-bottom pb-2">
                                <span class="fw-semibold">For the month of:</span>
                                <span><?php if (!empty($month)): ?>
                                    <?php echo htmlspecialchars($month); ?>
                                <?php else: ?>
                                    <span class="text-muted">(Month)</span>
                                <?php endif; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between border-bottom pb-2">
                                <span class="fw-semibold">Year:</span>
                                <span><?php echo $currentYear; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between border-bottom pb-2">
                                <span class="fw-semibold">Regular Days:</span>
                                <span><?php echo $regularDays; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="dtr-table">
                            <thead>
                                <tr>
                                    <th rowspan="2">Days</th>
                                    <th colspan="2">A.M.</th>
                                    <th colspan="2">P.M.</th>
                                    <th colspan="2">Undertime</th>
                                </tr>
                                <tr>
                                    <th>Arrival</th>
                                    <th>Departure</th>
                                    <th>Arrival</th>
                                    <th>Departure</th>
                                    <th>Hours</th>
                                    <th>Minutes</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Loop through all the days of the month (1 to 31)
                            for ($day = 1; $day <= 31; $day++) {
                                // Check if time data exists for this day
                                $timeData = isset($daysData[$day]) ? $daysData[$day] : [
                                    'time_in_am' => '',
                                    'time_out_am' => '',
                                    'time_in_pm' => '',
                                    'time_out_pm' => '',
                                    'has_in_am' => false,
                                    'has_out_am' => false,
                                    'has_in_pm' => false,
                                    'has_out_pm' => false
                                ];
                                
                                // Check if this day is a holiday or suspension
                                $isHoliday = isset($holidays[$day]) && $holidays[$day]['type'] === 'holiday';
                                $isSuspension = isset($holidays[$day]) && $holidays[$day]['type'] === 'suspension';
                            
                                // Display the row for each day
                                echo "<tr>";
                                echo "<td>" . $day . "</td>";
                                
                                // If it's a holiday or suspension, mark all time fields
                                if ($isHoliday || $isSuspension) {
                                    // Apply holiday/suspension class to each time cell individually
                                    $cellClass = $isHoliday ? 'holiday-day' : 'suspension-day';
                                    
                                    echo "<td colspan='6' class='{$cellClass}' style='text-align:center;'>";
                                    if ($isHoliday) {
                                        echo "<i class='fas fa-flag me-1'></i> HOLIDAY: " . htmlspecialchars($holidays[$day]['description']);
                                    } else {
                                        echo "<i class='fas fa-ban me-1'></i> SUSPENDED: " . htmlspecialchars($holidays[$day]['description']);
                                    }
                                    echo "</td>";
                                } else {
                                    // AM Arrival
                                    if ($timeData['time_in_am']) {
                                        echo "<td>" . htmlspecialchars($timeData['time_in_am']) . "</td>";
                                    } else {
                                        echo "<td>—</td>";
                                    }
                                    
                                    // AM Departure
                                    if ($timeData['time_out_am']) {
                                        echo "<td>" . htmlspecialchars($timeData['time_out_am']) . "</td>";
                                    } else {
                                        echo "<td>—</td>";
                                    }
                                    
                                    // PM Arrival
                                    if ($timeData['time_in_pm']) {
                                        echo "<td>" . htmlspecialchars($timeData['time_in_pm']) . "</td>";
                                    } else {
                                        echo "<td>—</td>";
                                    }
                                    
                                    // PM Departure
                                    if ($timeData['time_out_pm']) {
                                        echo "<td>" . htmlspecialchars($timeData['time_out_pm']) . "</td>";
                                    } else {
                                        echo "<td>—</td>";
                                    }
                                    
                                    echo "<td></td>"; // Placeholder for undertime
                                    echo "<td></td>"; // Placeholder for undertime
                                }
                                echo "</tr>";
                            }
                            
                            ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="dtr-footer">
                        <p>
                            I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.
                        </p>
                        <div class="dtr-signature">
                            <p>__________________________</p>
                            <p class="fw-semibold">In-Charge</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Holiday/Suspension Modal -->
    <div class="modal fade modal-modern" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="holidayModalLabel"><i class="fas fa-calendar-alt me-2"></i> Manage Holidays & Suspensions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-tabs-modern px-3 pt-3" id="holidayTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">
                                <i class="fas fa-plus-circle me-1"></i> Add New
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
                                <i class="fas fa-list me-1"></i> Current List
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3" id="holidayTabsContent">
                        <!-- Add Holiday Tab -->
                        <div class="tab-pane fade show active" id="add" role="tabpanel">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="holiday_date" class="form-label fw-semibold">Date</label>
                                        <input type="date" class="form-control form-control-modern" id="holiday_date" name="holiday_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="holiday_type" class="form-label fw-semibold">Type</label>
                                        <select class="form-select form-control-modern" id="holiday_type" name="holiday_type" required>
                                            <option value="">Select Type</option>
                                            <option value="holiday">Holiday</option>
                                            <option value="suspension">Suspension</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="holiday_description" class="form-label fw-semibold">Description</label>
                                        <textarea class="form-control form-control-modern" id="holiday_description" name="holiday_description" rows="3" placeholder="Enter description..." required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer px-0 pb-0">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="add_holiday" class="btn btn-modern">Add Holiday/Suspension</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- List Holidays Tab -->
                        <div class="tab-pane fade" id="list" role="tabpanel">
                            <div class="holiday-list">
                                <!-- Sample holiday items - in a real app, these would be dynamically generated -->
                                <div class="holiday-item">
                                    <div>
                                        <div class="holiday-date">January 1, 2023</div>
                                        <div class="text-muted small">New Year's Day</div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="holiday-type holiday me-3">Holiday</span>
                                        <div class="holiday-actions">
                                            <button type="button" class="me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="holiday-item">
                                    <div>
                                        <div class="holiday-date">April 9, 2023</div>
                                        <div class="text-muted small">Araw ng Kagitingan</div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="holiday-type holiday me-3">Holiday</span>
                                        <div class="holiday-actions">
                                            <button type="button" class="me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="holiday-item">
                                    <div>
                                        <div class="holiday-date">May 15, 2023</div>
                                        <div class="text-muted small">System Maintenance</div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="holiday-type suspension me-3">Suspension</span>
                                        <div class="holiday-actions">
                                            <button type="button" class="me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const suggestionsDiv = document.getElementById('suggestions');

        // Event listener for input field
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim();
            
            // Clear suggestions if input is empty
            if (query.length === 0) {
                suggestionsDiv.innerHTML = '';
                return;
            }

            // Send request to the PHP script
            fetch(`search_personnel.php?query=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    suggestionsDiv.innerHTML = '';
                    if (data.error) {
                        suggestionsDiv.innerHTML = '<div>Error fetching data</div>';
                        console.error(data.error);
                    } else if (data.length > 0) {
                        data.forEach(person => {
                            const div = document.createElement('div');
                            div.textContent = person.fullname;
                            div.addEventListener('click', () => {
                                searchInput.value = person.fullname;
                                suggestionsDiv.innerHTML = '';
                                document.getElementById('pername').value = person.fullname;
                                document.getElementById('perid').value = person.id;
                                document.getElementById('persontype').value = person.type;
                            });
                            suggestionsDiv.appendChild(div);
                        });
                    } else {
                        suggestionsDiv.innerHTML = '<div>No matches found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        });

        // Print function
        function printDiv(divId) {
            const printContents = document.getElementById(divId).innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>