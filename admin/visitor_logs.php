<?php
session_start();
include 'header.php';
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
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs - RFIDGPMS</title>
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
            position: sticky;
            top: 0;
            z-index: 10;
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
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--icon-color) var(--light-bg);
        }

        /* Custom scrollbar for webkit browsers */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #4361ee;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .badge-in {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .badge-out {
            background: linear-gradient(135deg, #f72585, #7209b7);
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

        /* Reset Button */
        .btn-reset {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-reset:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
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

        /* Action Buttons */
        .btn-action-timeout {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-action-timeout:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        .btn-action-view {
            background: linear-gradient(135deg, var(--info-color), #2e59d9);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-action-view:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        .btn-action-delete {
            background: linear-gradient(135deg, var(--danger-color), #be2617);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-action-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
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

        .photo-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--accent-color);
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

        .purpose-cell {
            max-width: 200px;
            word-wrap: break-word;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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

        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(92, 149, 233, 0.3);
        }

        .scroll-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
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
                                <h6 class="mb-4"><i class="fas fa-users me-2"></i>Visitor Logs Management</h6>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form id="filterForm" method="GET" class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <label for="date_from" class="form-label fw-bold">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo $_GET['date_from'] ?? ''; ?>">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="date_to" class="form-label fw-bold">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to"
                                           value="<?php echo $_GET['date_to'] ?? ''; ?>">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label for="visitor_id" class="form-label fw-bold">Visitor ID</label>
                                    <input type="text" class="form-control" id="visitor_id" name="visitor_id"
                                           value="<?php echo $_GET['visitor_id'] ?? ''; ?>" placeholder="Search ID">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label for="status" class="form-label fw-bold">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="in" <?php echo (($_GET['status'] ?? '') == 'in') ? 'selected' : ''; ?>>Checked In</option>
                                        <option value="out" <?php echo (($_GET['status'] ?? '') == 'out') ? 'selected' : ''; ?>>Checked Out</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-12 d-flex align-items-end">
                                    <div class="button-container w-100">
                                        <button type="submit" class="btn btn-filter">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <button type="button" class="btn btn-reset" onclick="resetFilters()">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                        <button type="button" class="btn btn-export" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Visitor Logs Table -->
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive" id="tableContainer">
                                    <table class="table table-striped table-hover mb-0" id="visitorLogsTable">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-calendar me-1"></i> Date</th>
                                                <th><i class="fas fa-user me-1"></i> Full Name</th>
                                                <th><i class="fas fa-phone me-1"></i> Contact Number</th>
                                                <th><i class="fas fa-bullseye me-1"></i> Purpose</th>
                                                <th><i class="fas fa-user-tag me-1"></i> Person Visiting</th>
                                                <th><i class="fas fa-sign-in-alt me-1"></i> Time In</th>
                                                <th><i class="fas fa-sign-out-alt me-1"></i> Time Out</th>
                                                <th><i class="fas fa-clock me-1"></i> Duration</th>
                                                <th><i class="fas fa-cogs me-1"></i> Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Build query with filters
                                            $whereConditions = [];
                                            $params = [];
                                            $types = '';

                                            if (!empty($_GET['date_from'])) {
                                                $whereConditions[] = "DATE(time_in) >= ?";
                                                $params[] = $_GET['date_from'];
                                                $types .= 's';
                                            }

                                            if (!empty($_GET['date_to'])) {
                                                $whereConditions[] = "DATE(time_in) <= ?";
                                                $params[] = $_GET['date_to'];
                                                $types .= 's';
                                            }

                                            if (!empty($_GET['visitor_id'])) {
                                                $whereConditions[] = "visitor_id LIKE ?";
                                                $params[] = '%' . $_GET['visitor_id'] . '%';
                                                $types .= 's';
                                            }

                                            if (!empty($_GET['status'])) {
                                                if ($_GET['status'] == 'in') {
                                                    $whereConditions[] = "time_out IS NULL";
                                                } elseif ($_GET['status'] == 'out') {
                                                    $whereConditions[] = "time_out IS NOT NULL";
                                                }
                                            }

                                            $whereClause = '';
                                            if (!empty($whereConditions)) {
                                                $whereClause = "WHERE " . implode(' AND ', $whereConditions);
                                            }

                                            $query = "SELECT * FROM visitor_logs $whereClause ORDER BY time_in DESC";
                                            $stmt = $db->prepare($query);

                                            if (!empty($params)) {
                                                $stmt->bind_param($types, ...$params);
                                            }

                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $status = $row['time_out'] ? 'out' : 'in';
                                                    $statusText = $row['time_out'] ? 'Checked Out' : 'Checked In';
                                                    $statusClass = $row['time_out'] ? 'badge-out' : 'badge-in';

                                                    // Calculate duration
                                                    $duration = '';
                                                    if ($row['time_out']) {
                                                        $timeIn = new DateTime($row['time_in']);
                                                        $timeOut = new DateTime($row['time_out']);
                                                        $interval = $timeIn->diff($timeOut);
                                                        $duration = $interval->format('%hh %im');
                                                    }

                                                    // Format times and date
                                                    $dateFormatted = date('M j, Y', strtotime($row['time_in']));
                                                    $timeInFormatted = date('g:i A', strtotime($row['time_in']));
                                                    $timeOutFormatted = $row['time_out'] ? 
                                                        date('g:i A', strtotime($row['time_out'])) : '-';
                                            ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo $dateFormatted; ?></strong>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div>
                                                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong>
                                                                    <br>
                                                                    <small class="text-muted">ID: <?php echo htmlspecialchars($row['visitor_id']); ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <code class="text-primary"><?php echo htmlspecialchars($row['contact_number']); ?></code>
                                                        </td>
                                                        <td class="purpose-cell">
                                                            <span class="fw-bold"><?php echo htmlspecialchars($row['purpose']); ?></span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                                <?php echo htmlspecialchars($row['location']); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($row['person_visiting'] ?? 'N/A'); ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-in rounded-pill">
                                                                <i class="fas fa-sign-in-alt me-1"></i>
                                                                <?php echo $timeInFormatted; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($row['time_out']): ?>
                                                                <span class="badge badge-out rounded-pill">
                                                                    <i class="fas fa-sign-out-alt me-1"></i>
                                                                    <?php echo $timeOutFormatted; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge badge-warning rounded-pill">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    Still Inside
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($duration): ?>
                                                                <span class="duration-badge rounded-pill">
                                                                    <i class="fas fa-stopwatch me-1"></i>
                                                                    <?php echo $duration; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="action-buttons">
                                                            <?php if (!$row['time_out']): ?>
                                                                <button class="btn btn-sm btn-action-timeout" 
                                                                        onclick="forceTimeOut(<?php echo $row['id']; ?>, '<?php echo $row['full_name']; ?>')"
                                                                        title="Force Time Out">
                                                                    <i class="fas fa-sign-out-alt"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button class="btn btn-sm btn-action-view" 
                                                                    onclick="viewDetails(<?php echo $row['id']; ?>)"
                                                                    title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-action-delete" 
                                                                    onclick="deleteLog(<?php echo $row['id']; ?>, '<?php echo $row['full_name']; ?>')"
                                                                    title="Delete Log">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                            <?php
                                                }
                                            } else {
                                                echo '<tr><td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                                    No visitor logs found
                                                </td></tr>';
                                            }
                                            
                                            $stmt->close();
                                            ?>
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
    </div>

    <!-- Scroll to Top Button -->
    <div class="scroll-to-top" id="scrollToTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-circle me-2"></i>Visitor Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Add SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Set max date to today for date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_from').setAttribute('max', today);
            document.getElementById('date_to').setAttribute('max', today);
            
            // Initialize scroll functionality
            initScrollFunction();
        });

        function resetFilters() {
            document.getElementById('filterForm').reset();
            window.location.href = 'visitor_logs.php';
        }

        function viewDetails(logId) {
            // Show loading SweetAlert
            Swal.fire({
                title: 'Loading...',
                text: 'Please wait while we fetch visitor details',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`get_visitor_details.php?id=${logId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        const details = data.data;
                        const modalContent = `
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img src="../admin/uploads/visitors/${details.photo || '../admin/uploads/students/default.png'}" 
                                         alt="Visitor Photo" class="img-fluid rounded mb-3"
                                         style="max-height: 200px; border: 3px solid var(--icon-color)"
                                         onerror="this.src='../admin/uploads/students/default.png'">
                                    <div class="badge ${details.time_out ? 'badge-out' : 'badge-in'} fs-6">
                                        ${details.time_out ? 'Checked Out' : 'Currently Checked In'}
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-6 mb-2">
                                            <strong>Visitor ID:</strong><br>
                                            <span class="text-primary">${details.visitor_id}</span>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <strong>Contact Number:</strong><br>
                                            <span>${details.contact_number}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Full Name:</strong><br>
                                            <span class="fs-5">${details.full_name}</span>
                                        </div>
                                        <div class="col-12 mb-2">
                                            <strong>Purpose:</strong><br>
                                            <span class="fw-bold">${details.purpose}</span>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <strong>Person Visiting:</strong><br>
                                            <span>${details.person_visiting || 'N/A'}</span>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <strong>Location:</strong><br>
                                            <span><i class="fas fa-map-marker-alt me-1"></i>${details.location}</span>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <strong>Time In:</strong><br>
                                            <span class="badge badge-in">${new Date(details.time_in).toLocaleString()}</span>
                                        </div>
                                        ${details.time_out ? `
                                        <div class="col-6 mb-2">
                                            <strong>Time Out:</strong><br>
                                            <span class="badge badge-out">${new Date(details.time_out).toLocaleString()}</span>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <strong>Duration:</strong><br>
                                            <span class="duration-badge">${calculateDuration(details.time_in, details.time_out)}</span>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        document.getElementById('detailsContent').innerHTML = modalContent;
                        new bootstrap.Modal(document.getElementById('detailsModal')).show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error loading details',
                            confirmButtonColor: '#e74a3b'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error loading visitor details. Please try again.',
                        confirmButtonColor: '#e74a3b'
                    });
                });
        }

        function calculateDuration(timeIn, timeOut) {
            const start = new Date(timeIn);
            const end = new Date(timeOut);
            const diff = end - start;
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            return `${hours}h ${minutes}m`;
        }

        function forceTimeOut(logId, visitorName) {
            Swal.fire({
                title: 'Force Time Out?',
                html: `Are you sure you want to force time out for <strong>${visitorName}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f6c23e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Force Time Out!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Recording time out',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('force_timeout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `log_id=${logId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Time out recorded successfully',
                                confirmButtonColor: '#1cc88a',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Error recording time out',
                                confirmButtonColor: '#e74a3b'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error recording time out. Please try again.',
                            confirmButtonColor: '#e74a3b'
                        });
                    });
                }
            });
        }

        function deleteLog(logId, visitorName) {
            Swal.fire({
                title: 'Delete Log?',
                html: `Are you sure you want to delete the log for <strong>${visitorName}</strong>?<br><br>
                      <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>This action cannot be undone!</span>`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusConfirm: false,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the log',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('delete_visitor_log.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `log_id=${logId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Log deleted successfully',
                                confirmButtonColor: '#1cc88a',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Error deleting log',
                                confirmButtonColor: '#e74a3b'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error deleting log. Please try again.',
                            confirmButtonColor: '#e74a3b'
                        });
                    });
                }
            });
        }

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
                    const table = document.getElementById('visitorLogsTable');
                    const ws = XLSX.utils.table_to_sheet(table);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Visitor Logs");
                    
                    const date = new Date().toISOString().split('T')[0];
                    XLSX.writeFile(wb, `visitor_logs_${date}.xlsx`);
                    
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

        // Auto-refresh every 30 seconds for real-time updates
        setInterval(() => {
            if (!document.hidden) {
                // Show refresh notification
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: 'info',
                    title: 'Refreshing data...'
                }).then(() => {
                    location.reload();
                });
            }
        }, 30000);

        // Scroll functionality
        function initScrollFunction() {
            const tableContainer = document.getElementById('tableContainer');
            const scrollToTopBtn = document.getElementById('scrollToTop');
            
            // Show/hide scroll to top button based on scroll position
            tableContainer.addEventListener('scroll', function() {
                if (tableContainer.scrollTop > 100) {
                    scrollToTopBtn.classList.add('visible');
                } else {
                    scrollToTopBtn.classList.remove('visible');
                }
            });
            
            // Scroll to top when button is clicked
            scrollToTopBtn.addEventListener('click', function() {
                tableContainer.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
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
    </script>
</body>
</html>