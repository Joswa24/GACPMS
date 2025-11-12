<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        /* Modern Table Styles */
        .modern-table-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            background: white;
            position: relative;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--icon-color) #f1f1f1;
        }

        /* Custom scrollbar for webkit browsers */
        .table-responsive::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        .modern-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            min-width: 1200px; /* Ensure minimum width for all columns */
        }

        .modern-table thead th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            border: none;
            padding: 18px 15px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .modern-table thead th:first-child {
            border-top-left-radius: var(--border-radius);
        }

        .modern-table thead th:last-child {
            border-top-right-radius: var(--border-radius);
        }

        .modern-table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .modern-table tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .modern-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: var(--border-radius);
        }

        .modern-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: var(--border-radius);
        }

        .modern-table td {
            padding: 15px;
            border: none;
            vertical-align: middle;
            white-space: nowrap;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
            padding: 0.5em 0.8em;
            font-weight: 500;
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

        /* Clear Button */
        .btn-clear {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-clear:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
            color: white;
        }

        /* Stats Cards */
        .stats-card {
            transition: var(--transition);
            border-radius: var(--border-radius);
            overflow: hidden;
            position: relative;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stats-card:hover::before {
            opacity: 1;
        }

        .stats-card .card-body {
            position: relative;
            z-index: 1;
        }

        /* Form Controls */
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

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            border: none;
            padding: 20px 25px;
        }

        /* Back to Top Button */
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

        /* SweetAlert customization */
        .swal2-popup {
            border-radius: var(--border-radius) !important;
        }

        /* Loading Spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* Status Badge Colors */
        .badge-success {
            background: linear-gradient(135deg, var(--success-color), #17a673);
        }

        .badge-danger {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
        }

        .badge-warning {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
        }

        .badge-info {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
        }

        .badge-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        /* Empty State */
        .text-center.text-muted {
            padding: 2rem;
        }

        /* Location link style */
        .location-link {
            color: var(--icon-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .location-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 0.875rem;
            }
            
            /* Show scroll indicator on mobile */
            .table-responsive::after {
                content: '→';
                position: absolute;
                bottom: 10px;
                right: 10px;
                background: var(--icon-color);
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                z-index: 5;
                animation: pulse 2s infinite;
            }
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-info:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }
        
        /* Table scroll indicator animation */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(92, 149, 233, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(92, 149, 233, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(92, 149, 233, 0);
            }
        }
        
        /* Table cell truncation for long text */
        .table-cell-truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* IP address badge styling */
        .ip-badge {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            background: linear-gradient(135deg, #6c757d, #5a6268);
            padding: 0.4em 0.7em;
        }
        
        /* Location button styling */
        .location-btn {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.4em 0.8em;
            font-size: 0.85em;
            transition: var(--transition);
        }
        
        .location-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(54, 185, 204, 0.3);
        }
        
        /* Status badge animation */
        .badge {
            position: relative;
            overflow: hidden;
        }
        
        .badge::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            transition: all 0.5s;
            opacity: 0;
        }
        
        .badge:hover::after {
            animation: shine 0.5s ease-in-out;
        }
        
        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Admin Access Log</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button class="btn btn-sm btn-clear" onclick="clearOldLogs()">
                                    <i class="fas fa-trash"></i> Clear Old Logs
                                </button>
                            </div>
                        </div>
                        <hr>
                        
                        <!-- Filters -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="dateFilter" class="form-label">Date Range</label>
                                <input type="date" class="form-control" id="dateFilter" onchange="filterLogs()">
                            </div>
                            <div class="col-md-3">
                                <label for="userFilter" class="form-label">Username</label>
                                <input type="text" class="form-control" id="userFilter" placeholder="Filter by username" onkeyup="filterLogs()">
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-control" id="statusFilter" onchange="filterLogs()">
                                    <option value="">All Status</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="activityFilter" class="form-label">Activity</label>
                                <input type="text" class="form-control" id="activityFilter" placeholder="Filter by activity" onkeyup="filterLogs()">
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Total Logins</h6>
                                                <h4 class="mb-0"><?php echo count($logs); ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Successful</h6>
                                                <h4 class="mb-0"><?php echo count(array_filter($logs, function($log) { return $log['status'] === 'success'; })); ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Failed</h6>
                                                <h4 class="mb-0"><?php echo count(array_filter($logs, function($log) { return $log['status'] === 'failed'; })); ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white stats-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">Active Today</h6>
                                                <h4 class="mb-0"><?php 
                                                    $today = date('Y-m-d');
                                                    echo count(array_filter($logs, function($log) use ($today) { 
                                                        return date('Y-m-d', strtotime($log['login_time'])) === $today; 
                                                    })); 
                                                ?></h4>
                                            </div>
                                            <div class="fs-1 opacity-50">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Logs Table -->
                        <div class="modern-table-container">
                            <div class="table-responsive">
                                <table class="modern-table" id="accessLogsTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Username</th>
                                            <th scope="col">Login Time</th>
                                            <th scope="col">Logout Time</th>
                                            <th scope="col">IP Address</th>
                                            <th scope="col">Location</th>
                                            <th scope="col">Activity</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Duration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($logs)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class="fas fa-clipboard-list text-muted mb-2" style="font-size: 2rem;"></i>
                                                        <p class="text-muted">No access logs found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($logs as $index => $log): ?>
                                                <tr class="table-<?php echo $log['id'];?>" data-date="<?php echo date('Y-m-d', strtotime($log['login_time'])); ?>" data-username="<?php echo strtolower(htmlspecialchars($log['username'] ?? '')); ?>" data-status="<?php echo $log['status']; ?>" data-activity="<?php echo strtolower(htmlspecialchars($log['activity'] ?? '')); ?>">
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y g:i A', strtotime($log['login_time'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $log['logout_time'] ? date('M j, Y g:i A', strtotime($log['logout_time'])) : 'Still Active'; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge ip-badge"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $summaryLocation = htmlspecialchars($log['location'] ?? 'Unknown Location');
                                                        $locationDetailsJson = htmlspecialchars($log['location_details'] ?? '{}');

                                                        // Check if location details are available and valid
                                                        $locationData = json_decode($log['location_details'], true);
                                                        if ($locationData && isset($locationData['status']) && $locationData['status'] === 'success') {
                                                            echo '<div class="table-cell-truncate mb-1">' . $summaryLocation . '</div>';
                                                            echo '<button class="btn btn-sm location-btn" onclick="showLocationModal(\'' . $locationDetailsJson . '\')">';
                                                            echo '<i class="fas fa-map-marked-alt"></i> View';
                                                            echo '</button>';
                                                        } else {
                                                            echo '<div class="table-cell-truncate">' . $summaryLocation . '</div>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($log['activity'] ?? 'Login'); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $log['status'] === 'success' ? 'badge-success' : 'badge-danger'; ?>">
                                                            <?php echo ucfirst($log['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($log['logout_time']) {
                                                            $login = new DateTime($log['login_time']);
                                                            $logout = new DateTime($log['logout_time']);
                                                            $interval = $login->diff($logout);
                                                            echo '<span class="badge bg-secondary">' . $interval->format('%hh %im %ss') . '</span>';
                                                        } else {
                                                            echo '<span class="badge badge-warning">Active</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- Location Details Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationModalLabel">
                        <i class="fas fa-globe-americas"></i> Detailed Location Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Geolocation Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>IP Address:</strong></td>
                                    <td id="modalIp">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Country:</strong></td>
                                    <td id="modalCountry">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Region/State:</strong></td>
                                    <td id="modalRegion">-</td>
                                </tr>
                                <tr>
                                    <td><strong>City:</strong></td>
                                    <td id="modalCity">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Zip Code:</strong></td>
                                    <td id="modalZip">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Timezone:</strong></td>
                                    <td id="modalTimezone">-</td>
                                </tr>
                                <tr>
                                    <td><strong>Coordinates:</strong></td>
                                    <td id="modalCoords">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Map View</h6>
                            <div id="mapContainer">
                                <img id="modalMap" src="" alt="Location Map" class="img-fluid rounded" style="width: 100%;">
                                <div id="mapError" class="alert alert-warning mt-2" style="display: none;">
                                    <i class="fas fa-exclamation-triangle"></i> Map could not be loaded. Please check the Google Maps API key in the JavaScript.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="modalMapsLink" href="" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Open in Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script>
    $(document).ready(function() {
        // Filter logs function
        window.filterLogs = function() {
            const dateFilter = document.getElementById('dateFilter').value;
            const userFilter = document.getElementById('userFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const activityFilter = document.getElementById('activityFilter').value.toLowerCase();
            
            const rows = document.querySelectorAll('#accessLogsTable tbody tr');
            
            rows.forEach(row => {
                // Skip the empty state row
                if (row.cells.length === 1) return;
                
                const dateValue = row.getAttribute('data-date');
                const usernameValue = row.getAttribute('data-username');
                const statusValue = row.getAttribute('data-status');
                const activityValue = row.getAttribute('data-activity');
                
                let showRow = true;
                
                if (dateFilter && dateValue !== dateFilter) {
                    showRow = false;
                }
                
                if (userFilter && !usernameValue.includes(userFilter)) {
                    showRow = false;
                }
                
                if (statusFilter && statusValue !== statusFilter) {
                    showRow = false;
                }
                
                if (activityFilter && !activityValue.includes(activityFilter)) {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }

        // Clear old logs (older than specified days)
        window.clearOldLogs = function() {
            Swal.fire({
                title: 'Clear Old Logs',
                html: `
                    <p>Select how many days of logs to keep:</p>
                    <div class="form-group">
                        <select id="daysSelect" class="form-control">
                            <option value="7">Keep last 7 days</option>
                            <option value="15">Keep last 15 days</option>
                            <option value="30" selected>Keep last 30 days</option>
                            <option value="60">Keep last 60 days</option>
                            <option value="90">Keep last 90 days</option>
                        </select>
                    </div>
                    <p class="mt-3 text-warning">This action cannot be undone!</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Clear Logs',
                preConfirm: () => {
                    return document.getElementById('daysSelect').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const days = result.value;
                    
                    // Show loading state
                    Swal.fire({
                        title: 'Clearing logs...',
                        html: `Please wait while we clear logs older than ${days} days`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: window.location.href,
                        type: 'POST',
                        data: { 
                            action: 'clear_old_logs',
                            days: days,
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to clear old logs. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        // Auto-refresh logs every 30 seconds
        setInterval(() => {
            $.ajax({
                url: window.location.href,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // You can implement dynamic update here if needed
                        console.log('Logs updated');
                    }
                },
                error: function() {
                    console.log('Failed to update logs');
                }
            });
        }, 30000);
        
        // Show location modal function
        window.showLocationModal = function(locationJson) {
            try {
                const data = JSON.parse(locationJson);

                // Check if the data is valid
                if (data.status !== 'success' || !data.lat || !data.lon) {
                    // Populate with error or N/A data
                    document.getElementById('modalIp').textContent = data.query || 'N/A';
                    document.getElementById('modalCountry').textContent = 'N/A';
                    document.getElementById('modalRegion').textContent = 'N/A';
                    document.getElementById('modalCity').textContent = 'N/A';
                    document.getElementById('modalZip').textContent = 'N/A';
                    document.getElementById('modalTimezone').textContent = 'N/A';
                    document.getElementById('modalCoords').textContent = 'N/A';
                    document.getElementById('modalMap').style.display = 'none';
                    document.getElementById('mapError').style.display = 'block';
                    document.getElementById('modalMapsLink').style.display = 'none';
                } else {
                    // Populate the modal with data
                    document.getElementById('modalIp').textContent = data.query || 'N/A';
                    document.getElementById('modalCountry').textContent = data.country || 'N/A';
                    document.getElementById('modalRegion').textContent = data.regionName || 'N/A';
                    document.getElementById('modalCity').textContent = data.city || 'N/A';
                    document.getElementById('modalZip').textContent = data.zip || 'N/A';
                    document.getElementById('modalTimezone').textContent = data.timezone || 'N/A';
                    document.getElementById('modalCoords').textContent = `${data.lat}, ${data.lon}`;

                    // --- IMPORTANT: SET YOUR GOOGLE MAPS API KEY ---
                    // Get a free key from: https://developers.google.com/maps/documentation/javascript/get-api-key
                    const googleMapsApiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // <-- REPLACE THIS

                    const mapUrl = `https://maps.googleapis.com/maps/api/staticmap?center=${data.lat},${data.lon}&zoom=13&size=600x300&markers=color:red|${data.lat},${data.lon}&key=${googleMapsApiKey}`;
                    const mapsLink = `https://www.google.com/maps?q=${data.lat},${data.lon}`;

                    const mapImg = document.getElementById('modalMap');
                    mapImg.src = mapUrl;
                    mapImg.style.display = 'block';
                    document.getElementById('mapError').style.display = 'none';
                    
                    const linkElement = document.getElementById('modalMapsLink');
                    linkElement.href = mapsLink;
                    linkElement.style.display = 'inline-flex';
                }

                // Show the modal
                const locationModal = new bootstrap.Modal(document.getElementById('locationModal'));
                locationModal.show();

            } catch (e) {
                console.error("Error parsing location data:", e);
                Swal.fire({
                    icon: 'error',
                    title: 'Data Error',
                    text: 'Could not display location details.'
                });
            }
        }
    });
    </script>
</body>
</html>