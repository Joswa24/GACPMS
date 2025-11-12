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
        .instructor-list {
            list-style-type: none;
            padding: 0;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .instructor-list li {
            padding: 8px;
            cursor: pointer;
        }
        .instructor-list li:hover {
            background-color: #f0f0f0;
        }
        
        /* Holiday/Suspension Styles */
        .holiday-day {
            background-color: #ffcccc !important;
        }
        
        .suspension-day {
            background-color: #ffffcc !important;
        }
        
        .holiday-badge {
            background-color: #e74a3b;
        }
        
        .suspension-badge {
            background-color: #f6c23e;
        }
        
        /* Toast Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .toast {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-success {
            border-left: 4px solid #1cc88a;
        }
        
        .toast-error {
            border-left: 4px solid #e74a3b;
        }
        
        .toast-warning {
            border-left: 4px solid #f6c23e;
        }
        
        .toast-icon {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .toast-success .toast-icon {
            color: #1cc88a;
        }
        
        .toast-error .toast-icon {
            color: #e74a3b;
        }
        
        .toast-warning .toast-icon {
            color: #f6c23e;
        }
        
        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: #858796;
        }
        
        /* Tab Styles */
        .tabs {
            display: flex;
            border-bottom: 1px solid #e3e6f0;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .tab:hover {
            background-color: #f8f9fc;
        }
        
        .tab.active {
            border-bottom: 2px solid #4e73df;
            color: #4e73df;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .holiday-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .holiday-item {
            padding: 10px;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .holiday-item:last-child {
            border-bottom: none;
        }
    </style>
    <style>
         #suggestions {
            position: absolute;
            z-index: 9999; /* Ensure it appears on top */
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 5px;
        }

        #suggestions div {
            padding: 10px;
            cursor: pointer;
            background-color: #f9f9f9;
        }

        #suggestions div:hover {
            background-color: #e0e0e0;
        }
        </style>
                <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="card modern-card">
                        <div class="card-header modern-card-header d-flex justify-content-between align-items-center">
                            <div class="header-content">
                                <h4 class="mb-1"><i class="fas fa-file-alt me-2"></i>Generate DTR</h4>
                                <p class="mb-0 text-muted small">Generate and print daily time records</p>
                            </div>
                            <div class="header-actions">
                                <button type="button" class="btn btn-warning btn-modern" data-bs-toggle="modal" data-bs-target="#holidayModal">
                                    <i class="fas fa-calendar-plus me-2"></i>Add Holiday
                                </button>
                                <button type="button" class="btn btn-info btn-modern ms-2" data-bs-toggle="modal" data-bs-target="#holidaysListModal">
                                    <i class="fas fa-list me-2"></i>View Holidays
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="filterForm" method="POST" action="">
                                <div class="row g-4 align-items-end">
                                    <div class="col-lg-4">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold mb-2">Search Personnel</label>
                                            <div class="search-container">
                                                <div class="input-group input-group-modern">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                    <input type="text" name="pname" class="form-control form-control-modern" 
                                                        id="searchInput" autocomplete="off" placeholder="Type name to search...">
                                                    <input hidden type="text" id="pername" name="pername">
                                                    <input hidden type="text" id="perid" name="perid">
                                                    <input hidden type="text" id="persontype" name="persontype">
                                                </div>
                                                <div id="suggestions" class="suggestions-dropdown"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
                                            <label class="form-label fw-semibold mb-2">Select Month</label>
                                            <div class="input-group input-group-modern">
                                                <span class="input-group-text">
                                                    <i class="fas fa-calendar"></i>
                                                </span>
                                                <select class="form-select form-select-modern" id="months" name="month">
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
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="d-flex gap-3 justify-content-end">
                                            <button type="submit" class="btn btn-primary btn-action">
                                                <i class="fas fa-search me-2"></i>Search Records
                                            </button>
                                            <button onclick="printDiv('container')" type="button" class="btn btn-success btn-action">
                                                <i class="fas fa-print me-2"></i>Print DTR
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
                        <div class="table-responsive">
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
                                .container {
                                    width: 100%;
                                    max-width: 800px;
                                    margin: 0 auto;
                                    border: 1px solid #000;
                                    padding: 20px;
                                    box-sizing: border-box;
                                }
                                .header {
                                    text-align: center;
                                    margin-bottom: 20px;
                                }
                                .header h1 {
                                    font-size: 20px;
                                    text-decoration: underline;
                                }
                                .header h3 {
                                    margin: 5px 0;
                                }
                                .info-table {
                                    width: 100%;
                                    margin-bottom: 10px;
                                }
                                .info-table th, .info-table td {
                                    border: none;
                                    padding: 5px;
                                }
                                .info-table th {
                                    text-align: left;
                                }
                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin-bottom: 20px;
                                }
                                th, td {
                                    border: 1px solid #000;
                                    padding: 5px;
                                    text-align: center;
                                }
                                .footer {
                                    margin-top: 20px;
                                }
                                .footer p {
                                    font-size: 14px;
                                    text-align: justify;
                                }
                                .footer .in-charge {
                                    text-align: right;
                                    margin-top: 30px;
                                }
                                .modern-card {
                                border: none;
                                border-radius: 16px;
                                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
                                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                                overflow: hidden;
                                transition: all 0.3s ease;
                            }

                            .modern-card:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
                            }

                            .modern-card-header {
                                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                                color: white;
                                border: none;
                                padding: 1.5rem 2rem;
                            }

                            .modern-card-header .header-content h4 {
                                font-weight: 700;
                                font-size: 1.4rem;
                                margin-bottom: 0.25rem;
                            }

                            .modern-card-header .header-content p {
                                font-size: 0.9rem;
                                opacity: 0.9;
                            }

                            .card-body {
                                padding: 2rem;
                            }

                            /* Modern Button Styles */
                            .btn-modern {
                                border: none;
                                border-radius: 12px;
                                padding: 12px 24px;
                                font-weight: 600;
                                font-size: 0.9rem;
                                transition: all 0.3s ease;
                                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                                position: relative;
                                overflow: hidden;
                            }

                            .btn-modern::before {
                                content: '';
                                position: absolute;
                                top: 0;
                                left: -100%;
                                width: 100%;
                                height: 100%;
                                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                                transition: left 0.5s;
                            }

                            .btn-modern:hover::before {
                                left: 100%;
                            }

                            .btn-modern:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                            }

                            .btn-warning {
                                background: linear-gradient(135deg, #f6c23e 0%, #f4b619 100%);
                                color: #fff;
                            }

                            .btn-warning:hover {
                                background: linear-gradient(135deg, #f4b619 0%, #f2a900 100%);
                                color: #fff;
                            }

                            .btn-info {
                                background: linear-gradient(135deg, #36b9cc 0%, #2e59d9 100%);
                                color: #fff;
                            }

                            .btn-info:hover {
                                background: linear-gradient(135deg, #2e59d9 0%, #1e40af 100%);
                                color: #fff;
                            }

                            /* Action Buttons */
                            .btn-action {
                                border: none;
                                border-radius: 12px;
                                padding: 14px 28px;
                                font-weight: 600;
                                font-size: 0.95rem;
                                transition: all 0.3s ease;
                                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                                min-width: 160px;
                            }

                            .btn-primary {
                                background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
                                border: none;
                            }

                            .btn-primary:hover {
                                background: linear-gradient(135deg, #2e59d9 0%, #1e40af 100%);
                                transform: translateY(-2px);
                                box-shadow: 0 8px 25px rgba(78, 115, 223, 0.3);
                            }

                            .btn-success {
                                background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
                                border: none;
                            }

                            .btn-success:hover {
                                background: linear-gradient(135deg, #17a673 0%, #13855c 100%);
                                transform: translateY(-2px);
                                box-shadow: 0 8px 25px rgba(28, 200, 138, 0.3);
                            }

                            /* Modern Form Controls */
                            .form-control-modern, .form-select-modern {
                                border: 2px solid #e2e8f0;
                                border-radius: 12px;
                                padding: 12px 16px;
                                font-size: 0.95rem;
                                transition: all 0.3s ease;
                                background: #ffffff;
                            }

                            .form-control-modern:focus, .form-select-modern:focus {
                                border-color: #667eea;
                                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                                background: #ffffff;
                            }

                            .input-group-modern {
                                border-radius: 12px;
                                overflow: hidden;
                            }

                            .input-group-modern .input-group-text {
                                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                                border: none;
                                color: #5c95e9ff;
                                font-weight: 600;
                                padding: 12px 16px;
                            }

                            .input-group-modern .form-control, .input-group-modern .form-select {
                                border: 2px solid #e2e8f0;
                                border-left: none;
                                border-radius: 0 12px 12px 0;
                            }

                            .input-group-modern .form-control:focus, .input-group-modern .form-select:focus {
                                border-color: #667eea;
                                box-shadow: none;
                            }

                            /* Search Suggestions */
                            .search-container {
                                position: relative;
                            }

                            .suggestions-dropdown {
                                position: absolute;
                                top: 100%;
                                left: 0;
                                right: 0;
                                z-index: 1000;
                                background: white;
                                border-radius: 12px;
                                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                                margin-top: 8px;
                                max-height: 200px;
                                overflow-y: auto;
                                border: 1px solid #e2e8f0;
                            }

                            .suggestions-dropdown div {
                                padding: 12px 16px;
                                cursor: pointer;
                                border-bottom: 1px solid #f1f5f9;
                                transition: all 0.2s ease;
                                font-size: 0.9rem;
                            }

                            .suggestions-dropdown div:hover {
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                color: white;
                            }

                            .suggestions-dropdown div:last-child {
                                border-bottom: none;
                            }

                            /* Form Labels */
                            .form-label {
                                font-weight: 600;
                                color: #4a5568;
                                margin-bottom: 8px;
                                font-size: 0.9rem;
                            }

                            /* Responsive Design */
                            @media (max-width: 768px) {
                                .modern-card-header {
                                    padding: 1rem 1.5rem;
                                    text-align: center;
                                }
                                
                                .modern-card-header .header-content h4 {
                                    font-size: 1.2rem;
                                }
                                
                                .card-body {
                                    padding: 1.5rem;
                                }
                                
                                .btn-action {
                                    min-width: auto;
                                    padding: 12px 20px;
                                    font-size: 0.9rem;
                                }
                                
                                .header-actions {
                                    margin-top: 1rem;
                                    justify-content: center;
                                }
                                
                                .header-actions .btn {
                                    margin: 0.25rem;
                                }
                            }

                            /* Animation for buttons */
                            @keyframes pulse {
                                0% {
                                    box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
                                }
                                70% {
                                    box-shadow: 0 0 0 10px rgba(102, 126, 234, 0);
                                }
                                100% {
                                    box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
                                }
                            }

                            .btn-modern:focus {
                                animation: pulse 1.5s infinite;
                            }
                            </style>
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
                                    $daysData[$day]['time_out_pm'] = '5:00 PM'; // Automatic 1:00 PM departure
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
                            <div class="container" id="container">
                                <div class="header">
                                    <h5>Civil Service Form No. 48</h5>
                                    <h4>DAILY TIME RECORD</h4>
                                    <?php if (!empty($name)): ?>
                                        <h1><?php echo htmlspecialchars($name); ?></h1>
                                    <?php else: ?>
                                        <p>(Name)</p>
                                    <?php endif; ?>
                                </div>

                                <table class="info-table">
                                    <tr>
                                        <th>For the month of</th>
                                        <td><?php if (!empty($month)): ?>
                                            <?php echo htmlspecialchars($month); ?>
                                        <?php else: ?>
                                            <p>(Month)</p>
                                        <?php endif; ?></td>
                                        <td><?php echo $currentYear; ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th>Official hours of arrival and departure:</th>
                                        <td>Regular Days: <?php echo $regularDays; ?></td>
                                        <td>Saturdays: <?php echo $saturdays; ?></td>
                                        <td></td>
                                    </tr>
                                </table>

                                <table>
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
                                                echo "<span class='badge holiday-badge me-1'>HOLIDAY</span> " . htmlspecialchars($holidays[$day]['description']);
                                            } else {
                                                echo "<span class='badge suspension-badge me-1'>SUSPENDED</span> " . htmlspecialchars($holidays[$day]['description']);
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
                                            <th>Total</th>
                                            <td colspan="6"></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <div class="footer">
                                    <p>
                                        I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.
                                    </p>
                                    <div class="in-charge">
                                        <p>__________________________</p>
                                        <p>In-Charge</p>
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
                                <i class="fas fa-save me-1"></i> Add
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
                        <div class="tabs">
                            <div class="tab active" data-tab="upcoming">Upcoming</div>
                            <div class="tab" data-tab="past">Past</div>
                            <div class="tab" data-tab="all">All</div>
                        </div>
                        
                        <div class="tab-content active" id="upcoming">
                            <div class="holiday-list" id="upcoming-holidays">
                                <!-- Holidays will be loaded here -->
                            </div>
                        </div>
                        
                        <div class="tab-content" id="past">
                            <div class="holiday-list" id="past-holidays">
                                <!-- Holidays will be loaded here -->
                            </div>
                        </div>
                        
                        <div class="tab-content" id="all">
                            <div class="holiday-list" id="all-holidays">
                                <!-- Holidays will be loaded here -->
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
                $('.tab').on('click', function() {
                    const tabId = $(this).data('tab');
                    
                    // Update active tab
                    $('.tab').removeClass('active');
                    $(this).addClass('active');
                    
                    // Update active content
                    $('.tab-content').removeClass('active');
                    $('#' + tabId).addClass('active');
                    
                    // Load holidays based on tab
                    loadHolidays(tabId);
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
                                            <div class="holiday-item">
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
                            <div class="toast-icon ${iconClass}"></div>
                            <div class="toast-message">${message}</div>
                            <button class="toast-close" onclick="closeToast('${toastId}')">&times;</button>
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
                    toast.fadeOut(600, function() {
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