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

// Get session data
 $name = $_SESSION['name'] ?? '';
 $month = $_SESSION['month'] ?? '';
 $id = $_SESSION['id'] ?? 0;
 $personType = $_SESSION['persontype'] ?? 'instructor';

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

// Determine which table to query based on person type
if ($personType === 'instructor') {
    $tableName = 'instructor_glogs';
    $idField = 'instructor_id';
} else if ($personType === 'personell') {
    $tableName = 'personell_glogs';
    $idField = 'personell_id';
} else {
    // Default to gate_logs if person type is not recognized
    $tableName = 'gate_logs';
    $idField = 'person_id';
}

// ENHANCED: Updated SQL query to fetch all logs for the person
 $sql = "SELECT date, time_in, time_out, action, period
        FROM $tableName 
        WHERE MONTH(date) = ? AND YEAR(date) = ? 
        AND $idField = ?
        ORDER BY date, time_in";

// Prepare statement
 $stmt = $db->prepare($sql);
 $stmt->bind_param("iii", $monthNumber, $currentYear, $id);
 $stmt->execute();
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

// Process each day's logs to determine time in/out
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

        // LOGIC 1: Afternoon class only (Kung time-in sa hapon)
        if ($hour_in !== null && $hour_in >= 12) {
            // Walay record sa aga
            $daysData[$day]['time_in_am'] = '';
            $daysData[$day]['time_out_am'] = '';
            
            // May record sa hapon
            $daysData[$day]['time_in_pm'] = '1:00 PM'; // Automatic 1 PM login
            if ($time_out) {
                $daysData[$day]['time_out_pm'] = date('g:i A', strtotime($time_out));
            } else {
                $daysData[$day]['time_out_pm'] = '';
            }
        }
        // LOGIC 2: Morning class only (Kung time-out sa aga)
        elseif ($hour_out !== null && $hour_out < 12) {
            // May record sa aga
            if ($time_in) {
                $daysData[$day]['time_in_am'] = date('g:i A', strtotime($time_in));
            } else {
                $daysData[$day]['time_in_am'] = '';
            }
            $daysData[$day]['time_out_am'] = '12:00 PM'; // Automatic 12 PM departure
            
            // Walay record sa hapon
            $daysData[$day]['time_in_pm'] = '';
            $daysData[$day]['time_out_pm'] = '';
        }
        // LOGIC 3: Both morning and afternoon class (Kung time-in sa aga ug time-out sa hapon)
        elseif ($hour_in !== null && $hour_out !== null && $hour_in < 12 && $hour_out >= 12) {
            // May record sa aga
            $daysData[$day]['time_in_am'] = date('g:i A', strtotime($time_in));
            $daysData[$day]['time_out_am'] = '12:00 PM'; // Automatic 12 PM departure
            
            // May record sa hapon
            $daysData[$day]['time_in_pm'] = '1:00 PM'; // Automatic 1 PM login
            $daysData[$day]['time_out_pm'] = date('g:i A', strtotime($time_out));
        }
        // Edge Case: Only time-in exists, no time-out. Assume morning class.
        elseif ($time_in && !$time_out) {
            if ($hour_in < 12) {
                $daysData[$day]['time_in_am'] = date('g:i A', strtotime($time_in));
                $daysData[$day]['time_out_am'] = '12:00 PM';
                $daysData[$day]['time_in_pm'] = '';
                $daysData[$day]['time_out_pm'] = '';
            } else {
                $daysData[$day]['time_in_am'] = '';
                $daysData[$day]['time_out_am'] = '';
                $daysData[$day]['time_in_pm'] = '1:00 PM';
                $daysData[$day]['time_out_pm'] = '';
            }
        }
        // Edge Case: Only time-out exists, no time-in.
        elseif (!$time_in && $time_out) {
            if ($hour_out < 12) {
                // Morning class only
                $daysData[$day]['time_in_am'] = '';
                $daysData[$day]['time_out_am'] = date('g:i A', strtotime($time_out));
                $daysData[$day]['time_in_pm'] = '';
                $daysData[$day]['time_out_pm'] = '';
            } else {
                // Afternoon class only
                $daysData[$day]['time_in_am'] = '';
                $daysData[$day]['time_out_am'] = '';
                $daysData[$day]['time_in_pm'] = '1:00 PM';
                $daysData[$day]['time_out_pm'] = date('g:i A', strtotime($time_out));
            }
        }
    }
}

 $stmt->close();
 $db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
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
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 15px;
            text-decoration: underline;
        }
        .header h3 {
            margin: 2px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table th, .info-table td {
            border: none;
            padding: 1px;
        }
        .info-table th {
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: .5px;
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
</head>
<body>
    <div class="container">
        <div class="header">
            <h5>Civil Service Form No. 48</h5>
            <h4>DAILY TIME RECORD</h4>
            <h1><?php echo htmlspecialchars($name); ?></h1>
        </div>

        <table class="info-table">
            <tr>
                <th>For the month of</th>
                <td><?php echo htmlspecialchars($month); ?></td>
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
</body>
</html>