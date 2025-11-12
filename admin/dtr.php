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
<?php include 'header.php'; ?>

<head>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }

        .card-modern {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            transition: var(--transition);
        }

        .card-modern:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-modern {
            border-radius: var(--border-radius);
            padding: 10px 20px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary-modern {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary-modern:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .form-control-modern {
            border-radius: var(--border-radius);
            border: 1px solid #e1e5eb;
            padding: 10px 15px;
            transition: var(--transition);
        }

        .form-control-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        .table-modern {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .table-modern th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border: none;
            padding: 12px 15px;
        }

        .table-modern td {
            padding: 12px 15px;
            border-color: #e1e5eb;
        }

        .table-modern tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .holiday-day {
            background-color: #ffebee !important;
            color: #c62828;
            font-weight: 500;
        }

        .suspension-day {
            background-color: #fff8e1 !important;
            color: #f57c00;
            font-weight: 500;
        }

        #suggestions {
            position: absolute;
            z-index: 9999;
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            width: 100%;
            box-shadow: var(--box-shadow);
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            margin-top: -1px;
            border: 1px solid #e1e5eb;
        }

        #suggestions div {
            padding: 10px 15px;
            cursor: pointer;
            transition: var(--transition);
        }

        #suggestions div:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }

        .search-container {
            position: relative;
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .print-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-top: 1.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
            }
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
                    <div class="card-modern h-100 p-4">
                        <div class="row mb-4">
                            <div class="col-8">
                                <h2 class="page-title">Generate DTR</h2>
                            </div>
                            <div class="col-4 text-end">
                                <button type="button" class="btn btn-warning btn-modern" data-bs-toggle="modal" data-bs-target="#holidayModal">
                                    <i class="fa fa-calendar-plus me-2"></i> Add Holiday/Suspension
                                </button>
                            </div>
                        </div>
                        
                        <form id="filterForm" method="POST" action="">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-4">
                                    <label class="form-label fw-medium">Search Employee</label>
                                    <div class="search-container">
                                        <input type="text" name="pname" class="form-control form-control-modern" id="searchInput" autocomplete="off" placeholder="Type to search...">
                                        <input type="hidden" id="pername" name="pername" autocomplete="off">
                                        <input type="hidden" id="perid" name="perid" autocomplete="off">
                                        <input type="hidden" id="persontype" name="persontype" autocomplete="off">
                                        <div id="suggestions"></div>
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label fw-medium">Month</label>
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
                                <div class="col-lg-2">
                                    <button type="submit" class="btn btn-primary-modern btn-modern w-100" id="btn_search">
                                        <i class="fa fa-search me-2"></i> Search
                                    </button>
                                </div>
                                <div class="col-lg-3">
                                    <div class="action-buttons">
                                        <button onclick="printDiv('container')" type="button" class="btn btn-success btn-modern no-print" id="btn_print">
                                            <i class="fa fa-print me-2"></i> Print
                                        </button> 
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="table-responsive">
                            <div class="print-container" id="container">
                                <div class="header text-center mb-4">
                                    <h5 class="mb-1">Civil Service Form No. 48</h5>
                                    <h4 class="mb-3">DAILY TIME RECORD</h4>
                                    <?php if (!empty($name)): ?>
                                        <h2 class="mb-4" style="border-bottom: 2px solid #333; padding-bottom: 10px; display: inline-block;"><?php echo htmlspecialchars($name); ?></h2>
                                    <?php else: ?>
                                        <p class="mb-4" style="border-bottom: 2px solid #333; padding-bottom: 10px; display: inline-block;">(Name)</p>
                                    <?php endif; ?>
                                </div>

                                <table class="table table-borderless mb-3">
                                    <tr>
                                        <th width="30%">For the month of</th>
                                        <td width="25%">
                                            <?php if (!empty($month)): ?>
                                                <?php echo htmlspecialchars($month); ?>
                                            <?php else: ?>
                                                (Month)
                                            <?php endif; ?>
                                        </td>
                                        <td width="25%"><?php echo $currentYear; ?></td>
                                        <td width="20%"></td>
                                    </tr>
                                    <tr>
                                        <th>Official hours of arrival and departure:</th>
                                        <td>Regular Days: <?php echo $regularDays; ?></td>
                                        <td>Saturdays: <?php echo $saturdays; ?></td>
                                        <td></td>
                                    </tr>
                                </table>

                                <table class="table table-modern table-bordered">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="align-middle">Days</th>
                                            <th colspan="2" class="text-center">A.M.</th>
                                            <th colspan="2" class="text-center">P.M.</th>
                                            <th colspan="2" class="text-center">Undertime</th>
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
                                        echo "<td class='fw-medium'>" . $day . "</td>";
                                        
                                        // If it's a holiday or suspension, mark all time fields
                                        if ($isHoliday || $isSuspension) {
                                            // Apply holiday/suspension class to each time cell individually
                                            $cellClass = $isHoliday ? 'holiday-day' : 'suspension-day';
                                            
                                            echo "<td colspan='6' class='{$cellClass}' style='text-align:center;'>";
                                            if ($isHoliday) {
                                                echo "<i class='fa fa-flag me-2'></i> HOLIDAY: " . htmlspecialchars($holidays[$day]['description']);
                                            } else {
                                                echo "<i class='fa fa-ban me-2'></i> SUSPENDED: " . htmlspecialchars($holidays[$day]['description']);
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

                                <div class="footer mt-4">
                                    <p class="mb-4">
                                        I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.
                                    </p>
                                    <div class="in-charge text-end">
                                        <p class="mb-1" style="border-top: 1px solid #333; padding-top: 40px; width: 200px; display: inline-block;">In-Charge</p>
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
                    <form id="holidayForm" method="POST" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="holiday_date" class="form-label">Date</label>
                                <input type="date" class="form-control form-control-modern" id="holiday_date" name="holiday_date" required>
                            </div>
                            <div class="mb-3">
                                <label for="holiday_type" class="form-label">Type</label>
                                <select class="form-select form-control-modern" id="holiday_type" name="holiday_type" required>
                                    <option value="">Select Type</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="suspension">Suspension</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="holiday_description" class="form-label">Description</label>
                                <textarea class="form-control form-control-modern" id="holiday_description" name="holiday_description" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_holiday" class="btn btn-primary-modern btn-modern">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // AJAX for holiday form submission
            document.getElementById('holidayForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('holidayModal'));
                        modal.hide();
                        
                        // Show success message
                        alert('Holiday/Suspension added successfully!');
                        
                        // Reset form
                        document.getElementById('holidayForm').reset();
                        
                        // Reload page to reflect changes
                        location.reload();
                    } else {
                        alert('Error adding holiday/suspension. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding holiday/suspension. Please try again.');
                });
            });

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

            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.innerHTML = '';
                }
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