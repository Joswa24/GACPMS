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

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate DTR - RFIDGPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --dark-gradient: linear-gradient(135deg, #434343 0%, #000000 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 25px 50px rgba(0, 0, 0, 0.15);
            --border-radius-xl: 20px;
            --border-radius-lg: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            color: #2d3748;
        }

        .content {
            background: transparent;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .modern-container {
            background: white;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            border: none;
            overflow: hidden;
        }

        .btn-modern {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-success {
            background: var(--success-gradient);
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-warning {
            background: var(--warning-gradient);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
        }

        .btn-info {
            background: var(--info-gradient);
            color: #2d3748;
            box-shadow: 0 4px 15px rgba(168, 237, 234, 0.3);
        }

        .form-control-modern {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            transition: var(--transition);
            background: white;
        }

        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
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
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            margin-top: 5px;
            border: 1px solid #e2e8f0;
        }

        #suggestions div {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f7fafc;
            transition: var(--transition);
        }

        #suggestions div:hover {
            background: #f7fafc;
        }

        #suggestions div:last-child {
            border-bottom: none;
        }

        .dtr-container {
            background: white;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            margin: 0 auto;
            max-width: 1000px;
            border: 1px solid #e2e8f0;
        }

        .dtr-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .dtr-header h5 {
            font-size: 14px;
            color: #718096;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .dtr-header h4 {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0;
        }

        .dtr-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #2d3748;
            text-decoration: underline;
            margin-top: 15px;
        }

        .dtr-info-table {
            width: 100%;
            margin-bottom: 30px;
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
        }

        .dtr-info-table th {
            font-weight: 600;
            color: #4a5568;
            padding: 8px 12px;
            text-align: left;
        }

        .dtr-info-table td {
            padding: 8px 12px;
            color: #2d3748;
            font-weight: 500;
        }

        .dtr-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .dtr-table th {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            padding: 15px 8px;
            text-align: center;
            border: none;
            font-size: 12px;
        }

        .dtr-table td {
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            background: white;
            transition: var(--transition);
        }

        .dtr-table tbody tr:hover td {
            background: #f7fafc;
        }

        .dtr-footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .dtr-footer p {
            font-size: 14px;
            line-height: 1.6;
            color: #4a5568;
            text-align: justify;
        }

        .in-charge {
            text-align: right;
            margin-top: 50px;
        }

        .in-charge p {
            margin: 5px 0;
            color: #2d3748;
            font-weight: 500;
        }

        /* Holiday Styles */
        .holiday-day {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%) !important;
        }

        .suspension-day {
            background: linear-gradient(135deg, #a29bfe 0%, #fd79a8 100%) !important;
        }

        .holiday-badge {
            background: var(--secondary-gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        .suspension-badge {
            background: var(--warning-gradient);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius-xl) var(--border-radius-xl) 0 0;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.2rem;
        }

        /* Toast Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
            padding: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            min-width: 300px;
            border-left: 4px solid;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-success { border-left-color: #48bb78; }
        .toast-error { border-left-color: #f56565; }
        .toast-warning { border-left-color: #ed8936; }

        .toast-icon {
            margin-right: 12px;
            font-size: 20px;
        }

        .toast-success .toast-icon { color: #48bb78; }
        .toast-error .toast-icon { color: #f56565; }
        .toast-warning .toast-icon { color: #ed8936; }

        /* Tab Styles */
        .tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: var(--transition);
            font-weight: 600;
            color: #718096;
        }

        .tab:hover {
            color: #4a5568;
            background: #f7fafc;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f7fafc;
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
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .holiday-item:hover {
            background: #f7fafc;
        }

        .holiday-item:last-child {
            border-bottom: none;
        }

        /* Back to top button */
        .back-to-top {
            background: var(--primary-gradient);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            color: white;
        }

        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            color: white;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dtr-container {
                padding: 20px;
            }
            
            .dtr-table {
                font-size: 10px;
            }
            
            .dtr-table th,
            .dtr-table td {
                padding: 8px 4px;
            }
        }

        /* Print Styles */
        @media print {
            .dtr-container {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .btn-modern,
            .modal,
            .toast-container {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative d-flex p-0">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
            <?php include 'navbar.php'; ?>
            
            <div class="container-fluid pt-4 px-4">
                <div class="modern-container">
                    <div class="p-4">
                        <div class="row align-items-center mb-4">
                            <div class="col-md-8">
                                <h4 class="mb-0 fw-bold text-dark">
                                    <i class="fas fa-file-alt me-3 text-primary"></i>
                                    Generate Daily Time Record
                                </h4>
                                <p class="text-muted mb-0 mt-2">Generate and print professional DTR reports</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#holidayModal">
                                    <i class="fas fa-calendar-plus me-2"></i>Add Holiday
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#holidaysListModal">
                                    <i class="fas fa-list me-2"></i>View Holidays
                                </button>
                            </div>
                        </div>

                        <div class="glass-card p-4 mb-4">
                            <form id="filterForm" method="POST" action="">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-dark mb-2">Search Personnel</label>
                                        <div class="search-container">
                                            <input type="text" name="pname" class="form-control-modern" id="searchInput" autocomplete="off" placeholder="Type name to search...">
                                            <input type="hidden" id="pername" name="pername">
                                            <input type="hidden" id="perid" name="perid">
                                            <input type="hidden" id="persontype" name="persontype">
                                            <div id="suggestions"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold text-dark mb-2">Select Month</label>
                                        <select class="form-control-modern" id="months" name="month">
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
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-modern w-100">
                                            <i class="fas fa-search me-2"></i>Search
                                        </button>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button type="button" class="btn btn-success w-100" onclick="printDiv('dtr-container')">
                                            <i class="fas fa-print me-2"></i>Print DTR
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="dtr-container" id="dtr-container">
                            <div class="dtr-header">
                                <h5>Civil Service Form No. 48</h5>
                                <h4>DAILY TIME RECORD</h4>
                                <?php if (!empty($name)): ?>
                                    <h1><?php echo htmlspecialchars($name); ?></h1>
                                <?php else: ?>
                                    <h1 class="text-muted">(Name)</h1>
                                <?php endif; ?>
                            </div>

                            <table class="dtr-info-table">
                                <tr>
                                    <th>For the month of:</th>
                                    <td>
                                        <?php if (!empty($month)): ?>
                                            <strong><?php echo htmlspecialchars($month); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">(Month)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo date('Y'); ?></strong></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Official hours of arrival and departure:</th>
                                    <td>Regular Days: <strong><?php echo isset($regularDays) ? $regularDays : 'N/A'; ?></strong></td>
                                    <td>Saturdays: <strong><?php echo isset($saturdays) ? $saturdays : 'N/A'; ?></strong></td>
                                    <td></td>
                                </tr>
                            </table>

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
                                if (isset($daysData) && isset($holidays)):
                                for ($day = 1; $day <= 31; $day++) {
                                    $timeData = isset($daysData[$day]) ? $daysData[$day] : [
                                        'time_in_am' => '', 'time_out_am' => '', 'time_in_pm' => '', 'time_out_pm' => ''
                                    ];
                                    
                                    $isHoliday = isset($holidays[$day]) && $holidays[$day]['type'] === 'holiday';
                                    $isSuspension = isset($holidays[$day]) && $holidays[$day]['type'] === 'suspension';
                                
                                    echo "<tr>";
                                    echo "<td><strong>" . $day . "</strong></td>";
                                    
                                    if ($isHoliday || $isSuspension) {
                                        $cellClass = $isHoliday ? 'holiday-day' : 'suspension-day';
                                        echo "<td colspan='6' class='{$cellClass}' style='text-align:center;'>";
                                        if ($isHoliday) {
                                            echo "<span class='holiday-badge me-2'>HOLIDAY</span>" . htmlspecialchars($holidays[$day]['description']);
                                        } else {
                                            echo "<span class='suspension-badge me-2'>SUSPENDED</span>" . htmlspecialchars($holidays[$day]['description']);
                                        }
                                        echo "</td>";
                                    } else {
                                        echo "<td>" . (!empty($timeData['time_in_am']) ? htmlspecialchars($timeData['time_in_am']) : "—") . "</td>";
                                        echo "<td>" . (!empty($timeData['time_out_am']) ? htmlspecialchars($timeData['time_out_am']) : "—") . "</td>";
                                        echo "<td>" . (!empty($timeData['time_in_pm']) ? htmlspecialchars($timeData['time_in_pm']) : "—") . "</td>";
                                        echo "<td>" . (!empty($timeData['time_out_pm']) ? htmlspecialchars($timeData['time_out_pm']) : "—") . "</td>";
                                        echo "<td></td><td></td>";
                                    }
                                    echo "</tr>";
                                }
                                else:
                                    echo "<tr><td colspan='8' class='text-center py-5 text-muted'>
                                        <i class='fas fa-search fa-2x mb-3 d-block'></i>
                                        Please search for personnel to generate DTR
                                    </td></tr>";
                                endif;
                                ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <td colspan="6" class="text-center text-muted">- End of Report -</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="dtr-footer">
                                <p>
                                    I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, 
                                    record of which was made daily at the time of arrival and departure from the office.
                                </p>
                                <div class="in-charge">
                                    <p>__________________________</p>
                                    <p><strong>In-Charge</strong></p>
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
                        <h5 class="modal-title">
                            <i class="fas fa-calendar-plus me-2"></i>Add Holiday/Suspension
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="" id="holidayForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Date</label>
                                <input type="date" class="form-control-modern" id="holiday_date" name="holiday_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Type</label>
                                <select class="form-control-modern" id="holiday_type" name="holiday_type" required>
                                    <option value="">Select Type</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="suspension">Suspension</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea class="form-control-modern" id="holiday_description" name="holiday_description" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_holiday" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Add Holiday
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
                        <h5 class="modal-title">
                            <i class="fas fa-list me-2"></i>Holidays & Suspensions
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="tabs">
                            <div class="tab active" data-tab="upcoming">Upcoming</div>
                            <div class="tab" data-tab="past">Past</div>
                            <div class="tab" data-tab="all">All</div>
                        </div>
                        
                        <div class="tab-content active" id="upcoming">
                            <div class="holiday-list" id="upcoming-holidays"></div>
                        </div>
                        
                        <div class="tab-content" id="past">
                            <div class="holiday-list" id="past-holidays"></div>
                        </div>
                        
                        <div class="tab-content" id="all">
                            <div class="holiday-list" id="all-holidays"></div>
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

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top">
            <i class="fas fa-arrow-up"></i>
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const suggestionsDiv = document.getElementById('suggestions');

            searchInput.addEventListener('input', function() {
                const query = searchInput.value.trim();
                
                if (query.length === 0) {
                    suggestionsDiv.innerHTML = '';
                    return;
                }

                fetch(`search_personnel.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsDiv.innerHTML = '';
                        if (data.error) {
                            suggestionsDiv.innerHTML = '<div class="p-2 text-danger">Error fetching data</div>';
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
            
            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !suggestionsDiv.contains(event.target)) {
                    suggestionsDiv.innerHTML = '';
                }
            });
            
            // Print functionality
            function printDiv(divId) {
                const printContents = document.getElementById(divId).innerHTML;
                const originalContents = document.body.innerHTML;
                
                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;
                location.reload();
            }
            
            window.printDiv = printDiv;

            // Holiday management functions
            $('#holidayForm').on('submit', function(e) {
                const date = $('#holiday_date').val();
                const type = $('#holiday_type').val();
                const description = $('#holiday_description').val();
                
                if (!date || !type || !description) {
                    e.preventDefault();
                    showToast('Please fill in all fields', 'warning');
                    return false;
                }
                
                const selectedDate = new Date(date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    e.preventDefault();
                    showToast('Cannot add holidays for past dates', 'warning');
                    return false;
                }
            });
            
            $('.tab').on('click', function() {
                const tabId = $(this).data('tab');
                $('.tab').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
                loadHolidays(tabId);
            });
            
            $('#holidaysListModal').on('show.bs.modal', function() {
                loadHolidays('upcoming');
            });
            
            function loadHolidays(type) {
                const containerId = type + '-holidays';
                const container = $('#' + containerId);
                
                container.html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</div>');
                
                $.ajax({
                    url: 'get_holidays.php',
                    type: 'GET',
                    data: { type: type },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.holidays.length === 0) {
                                container.html('<div class="text-center p-3 text-muted">No holidays found</div>');
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
                                                <span class="${typeClass} me-2">${typeText}</span>
                                                <strong>${formattedDate}</strong>
                                                <div class="text-muted small mt-1">${holiday.description}</div>
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
                                    showToast('Holiday deleted successfully', 'success');
                                });
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
                }
                
                const toast = $(`
                    <div class="toast ${toastClass}" id="${toastId}">
                        <div class="toast-icon ${iconClass}"></div>
                        <div class="toast-message">${message}</div>
                        <button class="toast-close" onclick="closeToast('${toastId}')">&times;</button>
                    </div>
                `);
                
                toastContainer.append(toast);
                
                setTimeout(function() {
                    closeToast(toastId);
                }, 5000);
            }
            
            window.closeToast = function(toastId) {
                $('#' + toastId).fadeOut(300, function() {
                    $(this).remove();
                });
            };
            
            <?php if (isset($_SESSION['message'])): ?>
                showToast('<?php echo $_SESSION['message']; ?>', 'success');
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>