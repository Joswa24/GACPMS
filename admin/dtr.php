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

if (isset($_SESSION['reload_flag'])) {
    // Unset specific session variables
    unset($_SESSION['month']); 
    unset($_SESSION['name']);
    unset($_SESSION['id']);
} 
 $id = 0;
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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<?php include '../connection.php'; ?>
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
        
        .holiday-day {
            background-color: #ffcccc !important;
        }
        
        .suspension-day {
            background-color: #ffffcc !important;
        }
    </style>
            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Generate DTR</h6>
                            </div>
                            <div class="col-3 text-right">
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#holidayModal">
                                    <i class="fa fa-calendar-times"></i> Add Holiday/Suspension
                                </button>
                            </div>
                        </div>
                        <br>
                        <form id="filterForm" method="POST" action="">
                        <div class="row">
                            <div class="col-lg-3">
                                <label>Search:</label>
                                <input type="text" name="pname" class="form-control" id="searchInput" autocomplete="off">
                                <input hidden type="text" id="pername" name="pername" autocomplete="off">
                                <input hidden type="text" id="perid" name="perid" autocomplete="off">
                                <input hidden type="text" id="persontype" name="persontype" autocomplete="off">
                                <div id="suggestions"></div>

                                <script>
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
                                </script>
                            </div>
                            <div class="col-lg-3">
                                <label>Month:</label>
                                <select class="form-control" id="months" name="month">
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
                            <div class="col-lg-3 mt-4">
                                <label></label>
                                <button type="submit" class="btn btn-primary" id="btn_search"><i class="fa fa-search"></i> Search</button>
                            </div>
                            <div class="col-lg-3 mt-4" style="text-align:right;">
                                <label></label>
                                <button onclick="printDiv('container')" type="button" class="btn btn-success" id="btn_print"><i class="fa fa-print"> Print</i></button> 
                            </div>
                        </form>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <style>
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
                                .holiday-day {
                                    background-color: #ffcccc !important;
                                }
                                .suspension-day {
                                    background-color: #ffffcc !important;
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

                            // Process each day's logs according to the new logic
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
                                
                                // Process AM records first
                                foreach ($logs as $log) {
                                    $time_in = !empty($log['time_in']) && $log['time_in'] != '00:00:00' ? $log['time_in'] : null;
                                    $time_out = !empty($log['time_out']) && $log['time_out'] != '00:00:00' ? $log['time_out'] : null;
                                    $period = strtoupper($log['period'] ?? '');
                                    $action = strtoupper($log['action'] ?? '');
                                    
                                    // Convert to 12-hour format
                                    if ($time_in) {
                                        $time_in_12h = date('g:i A', strtotime($time_in));
                                    }
                                    if ($time_out) {
                                        $time_out_12h = date('g:i A', strtotime($time_out));
                                    }
                                    
                                    // CONDITION 1: If Time in (AM) exists, automatically set Time out (AM) to 12:00 PM
                                    if ($time_in && $period === 'AM' && $action === 'IN') {
                                        $daysData[$day]['time_in_am'] = $time_in_12h;
                                        $daysData[$day]['has_in_am'] = true;
                                        
                                        // Automatically set AM departure to 12:00 PM
                                        $daysData[$day]['time_out_am'] = '12:00 AM';
                                        $daysData[$day]['has_out_am'] = true;
                                    }
                                    
                                    // CONDITION 2: If Time out (PM) exists, automatically set Time in (PM) to 1:00 PM
                                    if ($time_out && $period === 'PM' && $action === 'OUT') {
                                        $daysData[$day]['time_out_pm'] = $time_out_12h;
                                        $daysData[$day]['has_out_pm'] = true;
                                        
                                        // Automatically set PM arrival to 1:00 PM
                                        $daysData[$day]['time_in_pm'] = '1:00 PM';
                                        $daysData[$day]['has_in_pm'] = true;
                                    }
                                    
                                    // Handle direct PM time in records (without automatic time out)
                                    if ($time_in && $period === 'PM' && $action === 'IN') {
                                        $daysData[$day]['time_in_pm'] = $time_in_12h;
                                        $daysData[$day]['has_in_pm'] = true;
                                    }
                                    
                                    // Handle direct AM time out records (less common)
                                    if ($time_out && $period === 'AM' && $action === 'OUT') {
                                        $daysData[$day]['time_out_am'] = $time_out_12h;
                                        $daysData[$day]['has_out_am'] = true;
                                    }
                                }
                                
                                // Final validation: Ensure consistency
                                // If we have AM time in but no AM time out, set to 12:00 PM
                                if ($daysData[$day]['has_in_am'] && !$daysData[$day]['has_out_am']) {
                                    $daysData[$day]['time_out_am'] = '12:00 PM';
                                    $daysData[$day]['has_out_am'] = true;
                                }
                                
                                // If we have PM time out but no PM time in, set to 1:00 PM
                                if ($daysData[$day]['has_out_pm'] && !$daysData[$day]['has_in_pm']) {
                                    $daysData[$day]['time_in_pm'] = '1:00 PM';
                                    $daysData[$day]['has_in_pm'] = true;
                                }
                                
                                // If we have PM time in but no PM time out, set to 5:00 PM (standard office hours)
                                if ($daysData[$day]['has_in_pm'] && !$daysData[$day]['has_out_pm']) {
                                    $daysData[$day]['time_out_pm'] = '5:00 PM';
                                    $daysData[$day]['has_out_pm'] = true;
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
                                                echo "HOLIDAY: " . htmlspecialchars($holidays[$day]['description']);
                                            } else {
                                                echo "SUSPENDED: " . htmlspecialchars($holidays[$day]['description']);
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
                        <h5 class="modal-title" id="holidayModalLabel">Add Holiday/Suspension</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
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
                            <button type="submit" name="add_holiday" class="btn btn-primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            $(document).ready(function() {
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
            });
        </script>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="fas fa-arrow-up"></i></a>
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