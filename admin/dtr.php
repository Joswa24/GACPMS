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

// Handle delete holiday
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_holiday'])) {
    $holidayId = $_POST['holiday_id'];
    
    // Delete from holidays table
    $sql = "DELETE FROM holidays WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $holidayId);
    $stmt->execute();
    $stmt->close();
    
    // Set a success message
    $_SESSION['message'] = "Holiday/Suspension deleted successfully!";
    
    // Redirect to prevent form resubmission
    header("Location: dtr.php" );
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
        <?php
        include 'navbar.php';
        ?>
        <style>
        /* Modern UI Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
            color: #333;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 1rem 1.25rem;
        }
        
        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .table th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #5a5c69;
            border-top: none;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        
        .toast {
            border-radius: 8px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* DTR Specific Styles */
        .dtr-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .dtr-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e3e6f0;
        }
        
        .dtr-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.5rem;
        }
        
        .dtr-subtitle {
            font-size: 1.1rem;
            color: #858796;
            margin-bottom: 0;
        }
        
        .dtr-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }
        
        .dtr-info-table {
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .dtr-table {
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .dtr-table th {
            background-color: #f8f9fc;
            font-weight: 600;
            color: #5a5c69;
            padding: 12px 8px;
            font-size: 0.9rem;
        }
        
        .dtr-table td {
            padding: 12px 8px;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .dtr-footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e3e6f0;
            font-size: 0.9rem;
            color: #5a5c69;
        }
        
        .holiday-day {
            background-color: #ffcccc !important;
        }
        
        .suspension-day {
            background-color: #ffffcc !important;
        }
        
        .holiday-badge {
            background-color: #e74a3b;
            color: white;
        }
        
        .suspension-badge {
            background-color: #f6c23e;
            color: white;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Search Suggestions */
        #suggestions {
            position: absolute;
            z-index: 9999;
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            width: 100%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            margin-top: 5px;
        }

        #suggestions div {
            padding: 12px 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #suggestions div:hover {
            background-color: #f8f9fc;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dtr-container {
                padding: 1rem;
            }
            
            .dtr-table {
                font-size: 0.8rem;
            }
            
            .dtr-table th, .dtr-table td {
                padding: 8px 4px;
            }
        }
        </style>
        <div>
            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Generate DTR</h6>
                            <div>
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#holidayModal">
                                    <i class="fa fa-calendar-times me-2"></i>Add Holiday
                                </button>
                                <button type="button" class="btn btn-info btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#holidaysListModal">
                                    <i class="fas fa-list me-2"></i>View Holidays
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="filterForm" method="POST" action="">
                            <div class="row mb-4">
                                <div class="col-lg-4">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <input type="text" name="pname" class="form-control" id="searchInput" autocomplete="off">
                                        <input hidden type="text" id="pername" name="pername" autocomplete="off">
                                        <input hidden type="text" id="perid" name="perid" autocomplete="off">
                                        <input hidden type="text" id="persontype" name="persontype" autocomplete="off">
                                        <div id="suggestions"></div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label">Month</label>
                                    <select class="form-select" id="months" name="month">
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
                                <div class="col-lg-5 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search me-2"></i>Search
                                    </button>
                                    <button onclick="printDiv('container')" type="button" class="btn btn-success ms-2">
                                        <i class="fa fa-print me-2"></i>Print
                                    </button> 
                                </div>
                            </div>
                            </form>
                            
                            <div class="table-responsive mt-4">
                            <?php

                            // Check if the form was submitted
                            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            // Get the values from the form
                            $name = $_POST['pername'] ?? '';
                            $month = $_POST['month'] ?? '';
                            $id = $_POST['perid'] ?? '';
                            $personType = $_POST['persontype'] ?? '';
                            
                            $_SESSION['id'] = $id;
                            $_SESSION['name'] = $name;
                            $_SESSION['month'] = $month;
                            $_SESSION['persontype'] = $personType;

                            // Determine which table to query based on person type
                            if ($personType === 'instructor') {
                                $tableName = 'instructor_glogs';
                                $idField = 'instructor_id';
                                
                                // Query to fetch name for the given instructor ID
                                $sql = "SELECT name FROM instructor_glogs WHERE instructor_id = ? LIMIT 1";
                            } else if ($personType === 'personell') {
                                $tableName = 'personell_glogs';
                                $idField = 'personell_id';
                                
                                // Query to fetch name for the given personnel ID
                                $sql = "SELECT name FROM personell_glogs WHERE personell_id = ? LIMIT 1";
                            } else {
                                echo "Invalid person type.";
                                exit;
                            }

                            // Prepare and execute the query
                            $stmt = $db->prepare($sql);
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            // Fetch the person data
                            $person = [];
                            if ($row = $result->fetch_assoc()) {
                                $person = $row;
                            }

                            // Close the statement
                            $stmt->close();

                            // Check if person data is available
                            if (empty($person)) {
                                echo "No record found for the given ID.";
                                exit;
                            }

                            // Get current year and month number
                            $currentYear = date('Y');
                            $monthNumber = date('m', strtotime($month)); 

                            // Count regular days and Saturdays in the month
                            $regularDays = 0;
                            $saturdays = 0;
                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNumber, $currentYear);
                            
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $dayOfWeek = date('N', strtotime("$currentYear-$monthNumber-$day"));
                                if ($dayOfWeek <= 5) { // Monday to Friday
                                    $regularDays++;
                                } else if ($dayOfWeek == 6) { // Saturday
                                    $saturdays++;
                                }
                            }

                            // Get holidays for the month
                            $holidays = [];
                            $sql = "SELECT date, type, description FROM holidays WHERE MONTH(date) = ? AND YEAR(date) = ?";
                            $stmt = $db->prepare($sql);
                            $stmt->bind_param("ii", $monthNumber, $currentYear);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()) {
                                $day = (int)date('d', strtotime($row['date']));
                                $holidays[$day] = [
                                    'type' => $row['type'],
                                    'description' => $row['description']
                                ];
                            }
                            $stmt->close();

                            // Initialize the array to store the data for each day
                            $daysData = [];

                            // SQL query to fetch logs based on person type
                            $sql = "SELECT date, time_in, time_out, action, period 
                                    FROM $tableName 
                                    WHERE MONTH(date) = ? AND YEAR(date) = ? 
                                    AND $idField = ? 
                                    ORDER BY date, time_in";

                            // Prepare statement
                            $stmt = $db->prepare($sql);

                            if (!$stmt) {
                                die("Error preparing statement: " . $db->error);
                            }

                            // Bind parameters (current month, current year, and person ID)
                            $stmt->bind_param("iii", $monthNumber, $currentYear, $id);

                            // Execute the statement
                            if (!$stmt->execute()) {
                                die("Error executing query: " . $stmt->error);
                            }

                            // Get the result
                            $result = $stmt->get_result();

                            // First, collect all logs for each day
                            $dailyLogs = [];
                            while ($row = $result->fetch_assoc()) {
                                $day = (int)date('d', strtotime($row['date']));
                                if (!isset($dailyLogs[$day])) {
                                    $dailyLogs[$day] = [];
                                }
                                $dailyLogs[$day][] = $row;
                            }

                            // Process each day's logs with the new logic
                            foreach ($dailyLogs as $day => $logs) {
                                // Initialize day data
                                $daysData[$day] = [
                                    'time_in_am' => '',
                                    'time_out_am' => '',
                                    'time_in_pm' => '',
                                    'time_out_pm' => '',
                                    'has_in_am' => false,
                                    'has_out_am' => false,
                                    'has_in_pm' => false,
                                    'has_out_pm' => false
                                ];
                                
                                // Special logic for both instructors and personnel based on the new rules
                                if (!empty($logs)) {
                                    // Get the first log entry for the day
                                    $log = $logs[0];
                                    
                                    $time_in = !empty($log['time_in']) && $log['time_in'] != '00:00:00' ? $log['time_in'] : null;
                                    $time_out = !empty($log['time_out']) && $log['time_out'] != '00:00:00' ? $log['time_out'] : null;

                                    // Get hour in 24-hour format for easy comparison
                                    $hour_in = $time_in ? (int)date('H', strtotime($time_in)) : null;
                                    $hour_out = $time_out ? (int)date('H', strtotime($time_out)) : null;

                                    // CASE 1: Time in is in the morning (1:00am-11:59am) with NO time out
                                    if ($time_in && !$time_out && $hour_in < 12) {
                                        // Set morning record
                                        $daysData[$day]['time_in_am'] = date('g:i A', strtotime($time_in));
                                        $daysData[$day]['time_out_am'] = '12:00 PM'; // Automatic 12:00 PM departure
                                        // Keep afternoon blank
                                        $daysData[$day]['time_in_pm'] = '';
                                        $daysData[$day]['time_out_pm'] = '';
                                    }
                                    // CASE 2: Time in is in the afternoon (12:01pm-11:59pm) with NO time out
                                    elseif ($time_in && !$time_out && $hour_in >= 12) {
                                        // Keep morning blank
                                        $daysData[$day]['time_in_am'] = '';
                                        $daysData[$day]['time_out_am'] = '';
                                        // Set afternoon record
                                        $daysData[$day]['time_in_pm'] = date('g:i A', strtotime($time_in));
                                        $daysData[$day]['time_out_pm'] = '5:00 PM'; // Automatic 5:00 PM departure
                                    }
                                    // CASE 3: Time in and time out are both in the morning (1:00am-11:59am)
                                    elseif ($time_in && $time_out && $hour_in < 12 && $hour_out < 12) {
                                        // Set morning record
                                        $daysData[$day]['time_in_am'] = date('g:i A', strtotime($time_in));
                                        $daysData[$day]['time_out_am'] = date('g:i A', strtotime($time_out)); // Actual time out
                                        // Keep afternoon blank
                                        $daysData[$day]['time_in_pm'] = '';
                                        $daysData[$day]['time_out_pm'] = '';
                                    }
                                    // CASE 4: Time in and time out are both in the afternoon (12:01pm-11:59pm)
                                    elseif ($time_in && $time_out && $hour_in >= 12 && $hour_out >= 12) {
                                        // Keep morning blank
                                        $daysData[$day]['time_in_am'] = '';
                                        $daysData[$day]['time_out_am'] = '';
                                        // Set afternoon record
                                        $daysData[$day]['time_in_pm'] = date('g:i A', strtotime($time_in));
                                        $daysData[$day]['time_out_pm'] = date('g:i A', strtotime($time_out)); // Actual time out
                                    }
                                    // CASE 5: Time in is in the morning (1:00am-11:59am) and time out is in the afternoon (1:00pm-11:59pm)
                                    elseif ($time_in && $time_out && $hour_in < 12 && $hour_out >= 12) {
                                        // Set morning record
                                        $daysData[$day]['time_in_am'] = date('g:i A', strtotime($time_in));
                                        $daysData[$day]['time_out_am'] = '12:00 PM'; // Automatic 12:00 PM departure
                                        // Set afternoon record
                                        $daysData[$day]['time_in_pm'] = '1:00 PM'; // Automatic 1:00 PM arrival
                                        $daysData[$day]['time_out_pm'] = date('g:i A', strtotime($time_out)); // Actual time out
                                    }
                                }
                            }

                            // Close the statement
                            $stmt->close();
                            }
                            ?>
                                <div class="dtr-container fade-in" id="container">
                                    <div class="dtr-header">
                                        <div class="dtr-title">Civil Service Form No. 48</div>
                                        <div class="dtr-subtitle">DAILY TIME RECORD</div>
                                        <?php if (!empty($name)): ?>
                                            <div class="dtr-name"><?php echo htmlspecialchars($name); ?></div>
                                        <?php else: ?>
                                            <div class="dtr-name">(Name)</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="dtr-info-table">
                                        <div class="row">
                                            <div class="col-6">
                                                <div>For the month of</div>
                                            </div>
                                            <div class="col-3">
                                                <div><?php if (!empty($month)): ?>
                                                    <?php echo htmlspecialchars($month); ?>
                                                <?php else: ?>
                                                    (Month)
                                                <?php endif; ?></div>
                                            </div>
                                            <div class="col-3">
                                                <div><?php echo $currentYear; ?></div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <div>Official hours of arrival and departure:</div>
                                            </div>
                                            <div class="col-3">
                                                <div>Regular Days: <?php echo $regularDays; ?></div>
                                            </div>
                                            <div class="col-3">
                                                <div>Saturdays: <?php echo $saturdays; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table dtr-table">
                                            <thead>
                                                <tr>
                                                    <th rowspan="2" class="text-center">Days</th>
                                                    <th colspan="2" class="text-center">A.M.</th>
                                                    <th colspan="2" class="text-center">P.M.</th>
                                                    <th colspan="2" class="text-center">Undertime</th>
                                                </tr>
                                                <tr>
                                                    <th class="text-center">Arrival</th>
                                                    <th class="text-center">Departure</th>
                                                    <th class="text-center">Arrival</th>
                                                    <th class="text-center">Departure</th>
                                                    <th class="text-center">Hours</th>
                                                    <th class="text-center">Minutes</th>
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
                                                echo "<td class='text-center fw-bold'>" . $day . "</td>";
                                                
                                                // If it's a holiday or suspension, mark all time fields
                                                if ($isHoliday || $isSuspension) {
                                                    // Apply holiday/suspension class to each time cell individually
                                                    $cellClass = $isHoliday ? 'holiday-day' : 'suspension-day';
                                                    
                                                    echo "<td colspan='6' class='{$cellClass} text-center' style='padding: 12px;'>";
                                                    if ($isHoliday) {
                                                        echo "<span class='badge holiday-badge me-2'>HOLIDAY</span> " . htmlspecialchars($holidays[$day]['description']);
                                                    } else {
                                                        echo "<span class='badge suspension-badge me-2'>SUSPENDED</span> " . htmlspecialchars($holidays[$day]['description']);
                                                    }
                                                    echo "</td>";
                                                } else {
                                                    // AM Arrival
                                                    if (!empty($timeData['time_in_am'])) {
                                                        echo "<td>" . htmlspecialchars($timeData['time_in_am']) . "</td>";
                                                    } else {
                                                        echo "<td>—</td>";
                                                    }
                                                    
                                                    // AM Departure
                                                    if (!empty($timeData['time_out_am'])) {
                                                        echo "<td>" . htmlspecialchars($timeData['time_out_am']) . "</td>";
                                                    } else {
                                                        echo "<td>—</td>";
                                                    }
                                                    
                                                    // PM Arrival
                                                    if (!empty($timeData['time_in_pm'])) {
                                                        echo "<td>" . htmlspecialchars($timeData['time_in_pm']) . "</td>";
                                                    } else {
                                                        echo "<td>—</td>";
                                                    }
                                                    
                                                    // PM Departure
                                                    if (!empty($timeData['time_out_pm'])) {
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
                                            <tfoot>
                                                <tr>
                                                    <th class="text-center">Total</th>
                                                    <td colspan="6"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    <div class="dtr-footer">
                                        <p>
                                            I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.
                                        </p>
                                        <div class="text-end mt-4">
                                            <p>__________________________</p>
                                            <p>In-Charge</p>
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

        <!-- Holiday/Suspension Modal -->
        <div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="holidayModalLabel">
                            <i class="fas fa-calendar-times me-2"></i>Add Holiday/Suspension
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="" id="holidayForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="holiday_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="holiday_type" class="form-label">Type</label>
                                <select class="form-select" id="holiday_type" name="holiday_type" required>
                                    <option value="">Select Type</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="suspension">Suspension</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="holiday_description" class="form-label">Description</label>
                                <textarea class="form-control" id="holiday_description" name="holiday_description" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_holiday" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Add
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Holidays List Modal -->
        <div class="modal fade" id="holidaysListModal" tabindex="-1" aria-labelledby="holidaysListModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="holidaysListModalLabel">
                            <i class="fas fa-list me-2"></i>Holidays & Suspensions
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-3" id="holidayTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">Upcoming</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">Past</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="false">All</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="holidayTabContent">
                            <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                                <div class="holiday-list" id="upcoming-holidays">
                                    <!-- Holidays will be loaded here -->
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                                <div class="holiday-list" id="past-holidays">
                                    <!-- Holidays will be loaded here -->
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="all" role="tabpanel" aria-labelledby="all-tab">
                                <div class="holiday-list" id="all-holidays">
                                    <!-- Holidays will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Toast Container -->
        <div class="toast-container" id="toastContainer"></div>

        <script type="text/javascript">
            $(document).ready(function() {
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
                                suggestionsDiv.innerHTML = '<div class="p-2 text-danger">Error fetching data</div>';
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
                                suggestionsDiv.innerHTML = '<div class="p-2">No matches found</div>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching data:', error);
                            suggestionsDiv.innerHTML = '<div class="p-2 text-danger">Error fetching data</div>';
                        });
                });
                
                // Click outside to close suggestions
                document.addEventListener('click', function(event) {
                    if (!searchInput.contains(event.target) && !suggestionsDiv.contains(event.target)) {
                        suggestionsDiv.innerHTML = '';
                    }
                });
                
                // Print functionality
                $('#btn_print').on('click', function() {
                    // Load print.php content into a hidden iframe
                    var iframe = $('<iframe>', {
                        id: 'printFrame',
                        style: 'visibility:hidden; display:none'
                    }).appendTo('body');

                    // Set iframe source to print.php
                    iframe.attr('src', 'dtr_print.php');

                    // Wait for iframe to load
                    iframe.on('load', function() {
                        // Call print function of the iframe content
                        this.contentWindow.print();

                        // Remove the iframe after printing
                        setTimeout(function() {
                            iframe.remove();
                        }, 1000);
                    });
                });
                
                // Holiday form validation
                $('#holidayForm').on('submit', function(e) {
                    const date = $('#holiday_date').val();
                    const type = $('#holiday_type').val();
                    const description = $('#holiday_description').val();
                    
                    if (!date || !type || !description) {
                        e.preventDefault();
                        showToast('Please fill in all fields', 'warning');
                        return false;
                    }
                    
                    // Check if date is in the past
                    const selectedDate = new Date(date);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (selectedDate < today) {
                        e.preventDefault();
                        showToast('Cannot add holidays for past dates', 'warning');
                        return false;
                    }
                });
                
                // Tab functionality for holidays modal
                $('.nav-link').on('click', function() {
                    const tabId = $(this).attr('data-bs-target');
                    
                    // Update active tab
                    $('.nav-link').removeClass('active');
                    $(this).addClass('active');
                    
                    // Update active content
                    $('.tab-pane').removeClass('show active');
                    $(tabId).addClass('show active');
                    
                    // Load holidays based on tab
                    loadHolidays(tabId.substring(1)); // Remove the # from the target
                });
                
                // Load holidays when modal opens
                $('#holidaysListModal').on('show.bs.modal', function() {
                    loadHolidays('upcoming');
                });
                
                // Function to load holidays
                function loadHolidays(type) {
                    const containerId = type + '-holidays';
                    const container = $('#' + containerId);
                    
                    // Show loading
                    container.html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
                    
                    // Fetch holidays
                    $.ajax({
                        url: 'get_holidays.php',
                        type: 'GET',
                        data: { type: type },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                if (response.holidays.length === 0) {
                                    container.html('<div class="text-center p-3">No holidays found</div>');
                                } else {
                                    let html = '';
                                    response.holidays.forEach(function(holiday) {
                                        const typeClass = holiday.type === 'holiday' ? 'holiday-badge' : 'suspension-badge';
                                        const typeText = holiday.type === 'holiday' ? 'HOLIDAY' : 'SUSPENSION';
                                        const date = new Date(holiday.date);
                                        const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                                        
                                        html += `
                                            <div class="holiday-item d-flex justify-content-between align-items-center p-3 mb-2">
                                                <div>
                                                    <span class="badge ${typeClass} me-2">${typeText}</span>
                                                    <strong>${formattedDate}</strong>
                                                    <div class="text-muted small">${holiday.description}</div>
                                                </div>
                                                <button class="btn btn-sm btn-outline-danger delete-holiday" data-id="${holiday.id}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        `;
                                    });
                                    container.html(html);
                                }
                            } else {
                                container.html('<div class="text-center p-3 text-danger">Error loading holidays</div>');
                            }
                        },
                        error: function() {
                            container.html('<div class="text-center p-3 text-danger">Error loading holidays</div>');
                        }
                    });
                }
                
                // Delete holiday
                $(document).on('click', '.delete-holiday', function() {
                    const holidayId = $(this).data('id');
                    const holidayItem = $(this).closest('.holiday-item');
                    
                    if (confirm('Are you sure you want to delete this holiday?')) {
                        $.ajax({
                            url: 'dtr.php',
                            type: 'POST',
                            data: { delete_holiday: true, holiday_id: holidayId },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    holidayItem.fadeOut(300, function() {
                                        $(this).remove();
                                    });
                                    showToast('Holiday deleted successfully', 'success');
                                } else {
                                    showToast('Error deleting holiday', 'error');
                                }
                            },
                            error: function() {
                                showToast('Error deleting holiday', 'error');
                            }
                        });
                    }
                });
                
                // Show toast notification
                function showToast(message, type) {
                    const toastContainer = $('#toastContainer');
                    const toastId = 'toast-' + Date.now();
                    
                    let iconClass = '';
                    let toastClass = '';
                    
                    switch(type) {
                        case 'success':
                            iconClass = 'fas fa-check-circle';
                            toastClass = 'toast-success';
                            break;
                        case 'error':
                            iconClass = 'fas fa-exclamation-circle';
                            toastClass = 'toast-error';
                            break;
                        case 'warning':
                            iconClass = 'fas fa-exclamation-triangle';
                            toastClass = 'toast-warning';
                            break;
                        default:
                            iconClass = 'fas fa-info-circle';
                            toastClass = 'toast-info';
                    }
                    
                    const toast = `
                        <div class="toast ${toastClass}" id="${toastId}">
                            <div class="toast-body d-flex align-items-center">
                                <div class="toast-icon ${iconClass} me-2"></div>
                                <div class="toast-message">${message}</div>
                                <button class="btn-close ms-auto" onclick="closeToast('${toastId}')">&times;</button>
                            </div>
                        </div>
                    `;
                    
                    toastContainer.append(toast);
                    
                    // Auto close after 5 seconds
                    setTimeout(function() {
                        closeToast(toastId);
                    }, 5000);
                }
                
                // Close toast
                window.closeToast = function(toastId) {
                    const toast = $('#' + toastId);
                    toast.fadeOut(300, function() {
                        $(this).remove();
                    });
                };
                
                // Show session messages as toast
                <?php if (isset($_SESSION['message'])): ?>
                    showToast('<?php echo $_SESSION['message']; ?>', 'success');
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
            });
        </script>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>
</html>