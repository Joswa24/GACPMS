<?php
session_start();
// Display success/error messages
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

function getStudentPhoto($photo) {
    $basePath = '../uploads/students/';
    $defaultPhoto = '../assets/img/2601828.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Get statistics for summary cards
 $totalStudents = mysqli_num_rows(mysqli_query($db, "SELECT id FROM students"));
 $firstYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '1st Year'"))['count'];
 $secondYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '2nd Year'"))['count'];
 $thirdYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '3rd Year'"))['count'];
 $fourthYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '4th Year'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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

        .section-header {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .student-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .upload-img-btn {
            cursor: pointer;
            display: block;
            position: relative;
        }

        .preview-1 {
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .preview-1:hover {
            opacity: 0.8;
        }

        .file-uploader {
            position: relative;
            margin-bottom: 15px;
        }
        
        /* Modern Summary Cards - Matching Dashboard Style */
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stats-card.text-info::before { background: linear-gradient(135deg, #36b9cc, #2e59d9); }
        .stats-card.text-primary::before { background: linear-gradient(135deg, #4e73df, #2e59d9); }
        .stats-card.text-danger::before { background: linear-gradient(135deg, #e74a3b, #be2617); }
        .stats-card.text-success::before { background: linear-gradient(135deg, #1cc88a, #17a673); }
        .stats-card.text-warning::before { background: linear-gradient(135deg, #f6c23e, #f4b619); }
        .stats-card.text-secondary::before { background: linear-gradient(135deg, #858796, #6c757d); }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .stats-content h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #5a5c69;
        }

        .stats-content p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .stats-detail {
            font-size: 0.75rem;
            color: #495057;
            margin-top: 5px;
        }

        /* Ensure cards fit in one row */
        .summary-cards-row .col-xl {
            padding: 0 8px;
        }

        @media (max-width: 1400px) {
            .summary-cards-row .col-xl {
                flex: 0 0 20%;
                max-width: 20%;
            }
        }

        @media (max-width: 1200px) {
            .summary-cards-row .col-xl {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }

        @media (max-width: 992px) {
            .summary-cards-row .col-xl {
                flex: 0 0 33.333%;
                max-width: 33.333%;
            }
        }

        @media (max-width: 768px) {
            .summary-cards-row .col-xl {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (max-width: 576px) {
            .summary-cards-row .col-xl {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
        }
        .group-header {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .group-header:hover {
            background-color: #0b5ed7 !important;
        }
        .view-toggle-btn.active {
            background-color: #4e73df;
            color: white;
        }
        .student-count-badge {
            font-size: 0.8em;
            padding: 0.25em 0.6em;
        }
        @media (max-width: 768px) {
            .filter-section .col-md-3,
            .filter-section .col-md-2 {
                margin-bottom: 1rem;
            }
            .btn-group .btn {
                margin-bottom: 0.5rem;
            }
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

        /* Add Student Button */
        .btn-add {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
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

        /* Print Button */
        .btn-print {
            background: linear-gradient(135deg, var(--info-color), #2e59d9);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        /* Edit Button */
        .btn-edit {
            background: linear-gradient(135deg, var(--info-color), #2e59d9);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        /* Delete Button */
        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #be2617);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
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

        /* View Toggle Button */
        .btn-view-toggle {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            box-shadow: 0 4px 15px rgba(92, 149, 233, 0.3);
        }

        .btn-view-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
            color: white;
        }

        .btn-view-toggle.active {
            background: linear-gradient(135deg, #4361ee, var(--icon-color));
        }

        /* Modal Footer Buttons */
        .btn-close-modal {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-close-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        .btn-update {
            background: linear-gradient(135deg, var(--info-color), #2e59d9);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.875rem;
        }

        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border: none;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1);
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
            gap: 5px;
            justify-content: center;
        }

        /* Loading spinner */
        .spinner-border {
            width: 1rem;
            height: 1rem;
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
                        <div class="row mb-4">
                            <div class="col-6">
                                <h4 class="mb-0">Manage Students</h4>
                                <p class="text-muted">View and manage all student records</p>
                            </div>
                            <div class="col-6 text-end">
                                <div class="button-container">
                                    <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#studentModal">
                                        <i class="fas fa-plus-circle"></i> Add Student
                                    </button>
                                    <button type="button" class="btn btn-export" id="exportBtn">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button type="button" class="btn btn-print" id="printBtn">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Summary Cards -->
                        <div class="row g-4 mb-4 summary-cards-row">
                            <!-- Total Students -->
                            <div class="col-xl">
                                <div class="stats-card text-primary">
                                    <div class="stats-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo $totalStudents; ?></h3>
                                        <p>Total Students</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 1st Year -->
                            <div class="col-xl">
                                <div class="stats-card text-info">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo $firstYearCount; ?></h3>
                                        <p>1st Year Students</p>
                                        <div class="stats-detail">
                                            <?php echo $firstYearCount > 0 ? round(($firstYearCount / $totalStudents) * 100, 1) : 0; ?>% of total
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2nd Year -->
                            <div class="col-xl">
                                <div class="stats-card text-success">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo $secondYearCount; ?></h3>
                                        <p>2nd Year Students</p>
                                        <div class="stats-detail">
                                            <?php echo $secondYearCount > 0 ? round(($secondYearCount / $totalStudents) * 100, 1) : 0; ?>% of total
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3rd Year -->
                            <div class="col-xl">
                                <div class="stats-card text-warning">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo $thirdYearCount; ?></h3>
                                        <p>3rd Year Students</p>
                                        <div class="stats-detail">
                                            <?php echo $thirdYearCount > 0 ? round(($thirdYearCount / $totalStudents) * 100, 1) : 0; ?>% of total
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4th Year -->
                            <div class="col-xl">
                                <div class="stats-card text-danger">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo $fourthYearCount; ?></h3>
                                        <p>4th Year Students</p>
                                        <div class="stats-detail">
                                            <?php echo $fourthYearCount > 0 ? round(($fourthYearCount / $totalStudents) * 100, 1) : 0; ?>% of total
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="button-container">
                                    <button type="button" class="btn btn-view-toggle active" data-view="table">
                                        <i class="fas fa-table"></i> Table View
                                    </button>
                                    <button type="button" class="btn btn-view-toggle" data-view="grouped">
                                        <i class="fas fa-layer-group"></i> Grouped View
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-2">
                                    <label><strong>Filter by Year:</strong></label>
                                    <select class="form-control" id="filterYear">
                                        <option value="">All Years</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label><strong>Filter by Section:</strong></label>
                                    <select class="form-control" id="filterSection">
                                        <option value="">All Sections</option>
                                        <option value="West">West</option>
                                        <option value="North">North</option>
                                        <option value="East">East</option>
                                        <option value="South">South</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label><strong>Search:</strong></label>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or ID...">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-reset w-100" id="resetFilters">
                                        <i class="fas fa-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>


                        <!-- Table View -->
                        <div class="view-content" id="tableView">
                            <div class="table-responsive">
                                <table class="table table-border" id="myDataTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Photo</th>
                                            <th scope="col">ID Number</th>
                                            <th scope="col">Full Name</th>
                                            <th scope="col">Department</th>
                                            <th scope="col">Year</th>
                                            <th scope="col">Section</th>
                                            <th scope="col">Action</th>
                                            <th style="display: none;">Date Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $results = mysqli_query($db, "SELECT s.*, d.department_name 
                                                                    FROM students s 
                                                                    LEFT JOIN department d 
                                                                    ON s.department_id = d.department_id 
                                                                    WHERE d.department_name != 'Main'
                                                                    ORDER BY s.year, s.section, s.fullname"); 
                                        
                                        if ($results === false) {
                                            die("Query failed: " . mysqli_error($db));
                                        }
                                        ?>
                                        <?php while ($row = mysqli_fetch_array($results)) { 
                                            $photoPath = getStudentPhoto($row['photo']);
                                        ?>
                                        <tr class="table-<?php echo $row['id'];?>" data-student-id="<?php echo $row['id'];?>">
                                            <input class="department_id" type="hidden" value="<?php echo $row['department_id']; ?>" />
                                            <input class="id_number" type="hidden" value="<?php echo $row['id_number']; ?>" />
                                            <input class="fullname" type="hidden" value="<?php echo $row['fullname']; ?>" />
                                            <input class="section" type="hidden" value="<?php echo $row['section']; ?>" />
                                            <input class="year" type="hidden" value="<?php echo $row['year']; ?>" />
                                            <?php if (isset($row['date_added'])): ?>
                                            <input class="date_added" type="hidden" value="<?php echo $row['date_added']; ?>" />
                                            <?php endif; ?>

                                            <td>
                                                <center>
                                                    <img class="photo student-photo" src="<?php echo $photoPath; ?>" 
                                                         onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';">
                                                </center>
                                            </td>
                                            <td class="student_id"><?php echo $row['id_number']; ?></td>
                                            <td><?php echo $row['fullname']; ?></td>
                                            <td><?php echo $row['department_name']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $row['year']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $row['section']; ?></span></td>
                                            <td width="14%">
                                                <div class="action-buttons">
                                                    <button data-id="<?php echo $row['id'];?>" 
                                                            class="btn btn-sm btn-edit e_student_id">
                                                        <i class="fas fa-edit"></i> 
                                                    </button>
                                                    <button student_name="<?php echo $row['fullname']; ?>" 
                                                            data-id="<?php echo $row['id']; ?>" 
                                                            class="btn btn-sm btn-delete d_student_id">
                                                        <i class="fas fa-trash"></i> 
                                                    </button>
                                                </div>
                                            </td>
                                            <?php if (isset($row['date_added'])): ?>
                                            <td style="display:none;" class="hidden-date"><?php echo $row['date_added']; ?></td>
                                            <?php else: ?>
                                            <td style="display:none;" class="hidden-date"><?php echo date('Y-m-d H:i:s'); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Grouped View -->
                        <div class="view-content" id="groupedView" style="display: none;">
                            <?php
                            // Group students by year and section
                            $groupedQuery = mysqli_query($db, 
                                "SELECT s.*, d.department_name 
                                 FROM students s 
                                 LEFT JOIN department d ON s.department_id = d.department_id 
                                 WHERE d.department_name != 'Main'
                                 ORDER BY s.year, s.section, s.fullname");
                            
                            $groupedStudents = [];
                            while ($row = mysqli_fetch_array($groupedQuery)) {
                                $groupKey = $row['year'] . '_' . $row['section'];
                                $groupedStudents[$groupKey][] = $row;
                            }
                            
                            foreach ($groupedStudents as $groupKey => $students) {
                                list($year, $section) = explode('_', $groupKey);
                                $studentCount = count($students);
                            ?>
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center group-header" 
                                     data-bs-toggle="collapse" data-bs-target="#collapse<?php echo str_replace(' ', '', $year.$section); ?>">
                                    <h6 class="mb-0">
                                        <i class="fas fa-users me-2"></i>
                                        <?php echo $year ?> - <?php echo $section ?> Section
                                        <span class="badge bg-light text-dark ms-2 student-count-badge"><?php echo $studentCount ?> students</span>
                                    </h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-light expand-all me-1" title="Expand/Collapse">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="collapse show" id="collapse<?php echo str_replace(' ', '', $year.$section); ?>">
                                    <div class="card-body p-0">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Photo</th>
                                                    <th>ID Number</th>
                                                    <th>Full Name</th>
                                                    <th>Department</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student) { 
                                                    $photoPath = getStudentPhoto($student['photo']);
                                                ?>
                                                <tr data-student-id="<?php echo $student['id']; ?>">
                                                    <td>
                                                        <center>
                                                            <img class="student-photo photo" src="<?php echo $photoPath; ?>" 
                                                                 onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';">
                                                        </center>
                                                    </td>
                                                    <td class="student_id"><?php echo $student['id_number']; ?></td>
                                                    <td><?php echo $student['fullname']; ?></td>
                                                    <td><?php echo $student['department_name']; ?></td>
                                                    <td width="14%">
                                                        <div class="action-buttons">
                                                            <button data-id="<?php echo $student['id'];?>" 
                                                                    class="btn btn-sm btn-edit e_student_id">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button student_name="<?php echo $student['fullname']; ?>" 
                                                                    data-id="<?php echo $student['id']; ?>" 
                                                                    class="btn btn-sm btn-delete d_student_id">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Student Modal -->
            <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="studentForm" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-student"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-student"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>STUDENT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label for="photo" class="upload-img-btn" style="cursor: pointer;">
                                                        <img class="preview-1" src="../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg"
                                                            style="width: 140px!important; height: 130px!important; position: absolute; border: 1px solid gray; top: 25%;"
                                                            title="Upload Photo.." />
                                                    </label>
                                                    <input type="file" id="photo" name="photo" class="upload-field-1" 
                                                            style="opacity: 0; position: absolute; z-index: -1;" accept="image/*" required>
                                                    <span class="error-message" id="photo-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select required class="form-control dept_ID" name="department_id" id="department_id" autocomplete="off">
                                                        <option value="">Select Department</option>
                                                        <?php
                                                            $sql = "SELECT * FROM department WHERE department_name != 'Main' ORDER BY department_name";
                                                            $result = $db->query($sql);
                                                            while ($dept = $result->fetch_assoc()) {
                                                                echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="department_id-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label><b>ID Number:</b></label>
                                                    <input required type="text" class="form-control" name="id_number" id="id_number" autocomplete="off" 
                                                           maxlength="9" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="Format: 0000-0000">
                                                    <span class="error-message" id="id_number-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Full Name:</b></label>
                                                    <input required type="text" class="form-control" name="fullname" id="fullname" autocomplete="off"
                                                           pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed">
                                                    <span class="error-message" id="fullname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year:</b></label>
                                                    <select required class="form-control" name="year" id="year" autocomplete="off">
                                                        <option value="">Select Year</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="year-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <select required class="form-control" name="section" id="section" autocomplete="off">
                                                        <option value="">Select Section</option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="error-message" id="section-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-student" class="btn btn-save">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Student Modal -->
            <!-- Edit Student Modal -->
<div class="modal fade" id="editstudentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Student
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStudentForm" class="edit-form" role="form" method="post" action="" enctype="multipart/form-data">
                <div class="modal-body" id="editModal">
                    <div class="col-lg-12 mt-1" id="mgs-editstudent"></div>
                    <div class="row justify-content-md-center">
                        <div id="msg-editstudent"></div>
                        <div class="col-sm-12 col-md-12 col-lg-10">
                            <div class="section-header p-2 mb-3 rounded">
                                <strong>STUDENT INFORMATION</strong>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                    <div class="file-uploader">
                                        <label name="upload-label" class="upload-img-btn">
                                            <input type="file" id="editPhoto" name="photo" class="upload-field-1" style="display:none;" accept="image/*" title="Upload Photo.."/>
                                            <input type="hidden" id="capturedImage" name="capturedImage" class="capturedImage">
                                            <img class="preview-1 edit-photo" src="" style="width: 140px!important;height: 130px!important;position: absolute;border: 1px solid gray;top: 25%" title="Upload Photo.." />
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label><b>Department:</b></label>
                                        <select class="form-control dept_ID" name="department_id" id="edepartment_id" autocomplete="off">
                                            <option class="edit-dept-val" value=""></option>
                                            <?php
                                                $sql = "SELECT * FROM department WHERE department_name != 'Main' ORDER BY department_name";
                                                $result = $db->query($sql);
                                                while ($dept = $result->fetch_assoc()) {
                                                    echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
                                                }
                                            ?>
                                        </select>
                                        <span class="error-message" id="edepartment_id-error"></span>
                                    </div>
                                </div>

                                <!-- FIXED: Single ID Number Field -->
                                <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                    <div class="form-group">
                                        <label><b>ID Number:</b></label>
                                        <input required type="text" class="form-control edit-idnumber" name="id_number" id="eid_number" autocomplete="off"
                                            maxlength="9" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="Format: 0000-0000">
                                        <span class="error-message" id="eid_number-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 mt-1">
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <!-- empty -->
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12 mt-1">
                                    <div class="form-group">
                                        <label><b>Full Name:</b></label>
                                        <input type="text" class="form-control edit-fullname" name="fullname" id="efullname" autocomplete="off"
                                            pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed">
                                        <span class="error-message" id="efullname-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                    <div class="form-group">
                                        <label><b>Year:</b></label>
                                        <select class="form-control" name="year" id="eyear" autocomplete="off">
                                            <option class="edit-year-val" value=""></option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                        <span class="error-message" id="eyear-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                    <div class="form-group">
                                        <label><b>Section:</b></label>
                                        <select class="form-control" name="section" id="esection" autocomplete="off">
                                            <option class="edit-section-val" value=""></option>
                                            <option value="West">West</option>
                                            <option value="North">North</option>
                                            <option value="East">East</option>
                                            <option value="South">South</option>
                                        </select>
                                        <span class="error-message" id="esection-error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="edit_studentid" name="student_id" class="edit-id">
                    <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="btn-editstudent" class="btn btn-update">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
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
        // ============================================
        // INPUT VALIDATION FUNCTIONS
        // ============================================

        // Format ID Number as 0000-0000
        // Format ID Number as 0000-0000
function formatIDNumber(input) {
    // Only format if it's a direct input event, not when setting values programmatically
    if (!input._isProgrammatic) {
        let value = input.value.replace(/[^\d]/g, '');
        
        if (value.length > 4) {
            value = value.substring(0, 4) + '-' + value.substring(4, 8);
        }
        
        input.value = value;
    }
}

// For programmatic setting, use this method
function setIDNumberValue(field, value) {
    field._isProgrammatic = true;
    field.value = value;
    setTimeout(() => {
        field._isProgrammatic = false;
    }, 100);
}

        // Validate ID Number format
        function validateIDNumber(idNumber) {
            const idRegex = /^\d{4}-\d{4}$/;
            return idRegex.test(idNumber);
        }

        // Restrict ID Number input to numbers only
        function restrictIDNumberInput(event) {
            const key = event.key;
            // Allow: backspace, delete, tab, escape, enter, and numbers
            if ([8, 9, 13, 27, 46].includes(event.keyCode) || 
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (event.ctrlKey === true && [65, 67, 86, 88].includes(event.keyCode)) ||
                // Allow: home, end, left, right
                (event.keyCode >= 35 && event.keyCode <= 39)) {
                return;
            }
            
            // Ensure it's a number
            if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && 
                (event.keyCode < 96 || event.keyCode > 105)) {
                event.preventDefault();
            }
        }

        // Restrict Fullname input to letters and spaces only
        function restrictFullnameInput(event) {
            const key = event.key;
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 13, 27, 32, 46].includes(event.keyCode) || 
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (event.ctrlKey === true && [65, 67, 86, 88].includes(event.keyCode)) ||
                // Allow: home, end, left, right
                (event.keyCode >= 35 && event.keyCode <= 39)) {
                return;
            }
            
            // Ensure it's a letter
            if (!/^[a-zA-Z\s]$/.test(key)) {
                event.preventDefault();
            }
        }

        // Prevent paste of invalid characters in ID Number
        function preventInvalidPasteID(event) {
            const pasteData = event.originalEvent.clipboardData.getData('text');
            if (!/^[\d]*$/.test(pasteData)) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: 'Only numbers are allowed in ID Number',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }

        // Prevent paste of invalid characters in Fullname
        function preventInvalidPasteFullname(event) {
            const pasteData = event.originalEvent.clipboardData.getData('text');
            if (!/^[a-zA-Z\s]*$/.test(pasteData)) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: 'Only letters and spaces are allowed in Full Name',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }

        // Initialize input validations
        function initializeInputValidations() {
            // ID Number validation for add form
            $('#id_number').on('input', function() {
                formatIDNumber(this);
            }).on('keydown', restrictIDNumberInput)
              .on('paste', preventInvalidPasteID);

            // ID Number validation for edit form
            $('#eid_number').on('input', function() {
                formatIDNumber(this);
            }).on('keydown', restrictIDNumberInput)
              .on('paste', preventInvalidPasteID);

            // Fullname validation for add form
            $('#fullname').on('keydown', restrictFullnameInput)
                         .on('paste', preventInvalidPasteFullname);

            // Fullname validation for edit form
            $('#efullname').on('keydown', restrictFullnameInput)
                          .on('paste', preventInvalidPasteFullname);
        }

        // ============================================
        // DATATABLE AND VIEW MANAGEMENT
        // ============================================

        // Initialize DataTable with export buttons
        var dataTable = $('#myDataTable').DataTable({
            order: [[7, 'desc']],
            stateSave: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            responsive: true
        });

        // View Toggle Functionality
        $('.btn-view-toggle').on('click', function() {
            const viewType = $(this).data('view');
            
            // Update active button
            $('.btn-view-toggle').removeClass('active');
            $(this).addClass('active');
            
            // Show selected view, hide others
            $('.view-content').hide();
            $(`#${viewType}View`).show();
            
            // Reinitialize DataTable when switching back to table view
            if (viewType === 'table') {
                dataTable.columns.adjust().responsive.recalc();
            }
            
            // Apply current filters to the new view
            applyFilters();
        });

        // Filter Functionality
        function initializeFilters() {
            const filterYear = $('#filterYear');
            const filterSection = $('#filterSection');
            const searchInput = $('#searchInput');
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                filterYear.val('');
                filterSection.val('');
                searchInput.val('');
                applyFilters();
            });
        }

        // Main filter function
        function applyFilters() {
            const yearVal = $('#filterYear').val();
            const sectionVal = $('#filterSection').val();
            const searchVal = $('#searchInput').val().toLowerCase();
            
            // Get current active view
            const activeView = $('.btn-view-toggle.active').data('view');
            
            if (activeView === 'table') {
                filterTableView(yearVal, sectionVal, searchVal);
            } else if (activeView === 'grouped') {
                filterGroupedView(yearVal, sectionVal, searchVal);
            }
        }

        // Filter table view
        function filterTableView(yearVal, sectionVal, searchVal) {
            // Combine all filters for DataTable
            dataTable.columns().search(''); // Clear all column searches
            
            // Apply year filter (column 4)
            if (yearVal) {
                dataTable.column(4).search('^' + yearVal + '$', true, false).draw();
            } else {
                dataTable.column(4).search('').draw();
            }
            
            // Apply section filter (column 5)
            if (sectionVal) {
                dataTable.column(5).search('^' + sectionVal + '$', true, false).draw();
            } else {
                dataTable.column(5).search('').draw();
            }
            
            // Apply global search
            dataTable.search(searchVal).draw();
        }

        // Filter grouped view
        // Replace your existing filterGroupedView function with this improved version:

// Filter grouped view
function filterGroupedView(yearVal, sectionVal, searchVal) {
    let visibleGroups = 0;
    
    $('.card.mb-3').each(function() {
        const $group = $(this);
        const groupHeader = $group.find('.card-header h6').text().toLowerCase();
        const groupYear = $group.find('.card-header h6').text().split(' - ')[0]?.trim();
        const groupSection = $group.find('.card-header h6').text().split(' - ')[1]?.replace('Section', '').trim();
        
        let visibleRows = 0;
        
        // Filter rows within this group
        $group.find('tbody tr').each(function() {
            const $row = $(this);
            const rowText = $row.text().toLowerCase();
            const rowYear = $row.find('td:nth-child(5)').text().trim() || groupYear;
            const rowSection = $row.find('td:nth-child(6)').text().trim() || groupSection;
            
            const yearMatch = !yearVal || rowYear === yearVal;
            const sectionMatch = !sectionVal || rowSection === sectionVal;
            const searchMatch = !searchVal || rowText.includes(searchVal);
            
            if (yearMatch && sectionMatch && searchMatch) {
                $row.show();
                visibleRows++;
            } else {
                $row.hide();
            }
        });
        
        // Show/hide group based on visible rows
        if (visibleRows > 0) {
            $group.show();
            visibleGroups++;
            
            // Update student count badge
            const $badge = $group.find('.student-count-badge');
            $badge.text(visibleRows + ' students');
        } else {
            $group.hide();
        }
    });
    
    // Show message if no groups are visible
    if (visibleGroups === 0) {
        showNoResultsMessage();
    } else {
        hideNoResultsMessage();
    }
}

// Also update your applyFilters function to ensure it properly calls filterGroupedView:

// Main filter function
function applyFilters() {
    const yearVal = $('#filterYear').val();
    const sectionVal = $('#filterSection').val();
    const searchVal = $('#searchInput').val().toLowerCase();
    
    // Get current active view
    const activeView = $('.btn-view-toggle.active').data('view');
    
    if (activeView === 'table') {
        filterTableView(yearVal, sectionVal, searchVal);
    } else if (activeView === 'grouped') {
        filterGroupedView(yearVal, sectionVal, searchVal);
    }
}

// Add these helper functions if they don't exist:

// Show no results message
function showNoResultsMessage() {
    if (!$('#noResultsMessage').length) {
        const message = `
            <div id="noResultsMessage" class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                No students found matching the current filters.
            </div>
        `;
        $('#groupedView').prepend(message);
    }
}

// Hide no results message
function hideNoResultsMessage() {
    $('#noResultsMessage').remove();
}

// Finally, make sure your event listeners for the filters are set up correctly:

// Event listeners for filters
 $('#filterYear, #filterSection').on('change', applyFilters);
 $('#searchInput').on('keyup', function() {
    clearTimeout($(this).data('timeout'));
    $(this).data('timeout', setTimeout(applyFilters, 500));
});

        // Show no results message
        function showNoResultsMessage() {
            if (!$('#noResultsMessage').length) {
                const message = `
                    <div id="noResultsMessage" class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No students found matching the current filters.
                    </div>
                `;
                $('#groupedView').prepend(message);
            }
        }

        // Hide no results message
        function hideNoResultsMessage() {
            $('#noResultsMessage').remove();
        }

        // Event listeners for filters
        $('#filterYear, #filterSection').on('change', applyFilters);
        $('#searchInput').on('keyup', function() {
            clearTimeout($(this).data('timeout'));
            $(this).data('timeout', setTimeout(applyFilters, 500));
        });

        // Initialize filters
        initializeFilters();

        // Export functionality
        $('#exportBtn').on('click', function() {
            dataTable.button('.buttons-excel').trigger();
        });

        // Print functionality
        $('#printBtn').on('click', function() {
            dataTable.button('.buttons-print').trigger();
        });

        // Expand/Collapse all groups functionality
        let allExpanded = true;
        
        $(document).on('click', '.expand-all', function(e) {
            e.stopPropagation();
            
            const $header = $(this).closest('.card-header');
            const $target = $($header.data('bs-target'));
            
            $target.collapse('toggle');
            
            // Update icon
            const $icon = $(this).find('i');
            if ($target.hasClass('show')) {
                $icon.removeClass('fa-compress').addClass('fa-expand');
            } else {
                $icon.removeClass('fa-expand').addClass('fa-compress');
            }
        });

        // Expand/Collapse all groups button
        $(document).on('click', '.expand-all-groups', function() {
            const $icon = $(this).find('i');
            
            if (allExpanded) {
                // Collapse all
                $('.collapse.show').collapse('hide');
                $icon.removeClass('fa-compress').addClass('fa-expand');
                $(this).find('span').text('Expand All');
            } else {
                // Expand all
                $('.collapse').collapse('show');
                $icon.removeClass('fa-expand').addClass('fa-compress');
                $(this).find('span').text('Collapse All');
            }
            
            allExpanded = !allExpanded;
        });

        // Add Expand/Collapse All button to grouped view
        function addExpandAllButton() {
            if (!$('#expandAllBtn').length) {
                const expandBtn = `
                    <div class="row mb-3" id="expandAllBtn">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm expand-all-groups">
                                <i class="fas fa-expand me-1"></i>
                                <span>Expand All</span>
                            </button>
                        </div>
                    </div>
                `;
                $('#groupedView').prepend(expandBtn);
            }
        }

        // Remove Expand/Collapse All button when switching views
        function removeExpandAllButton() {
            $('#expandAllBtn').remove();
        }

        // Enhanced view toggle with additional setup
        $('.btn-view-toggle').on('click', function() {
            const viewType = $(this).data('view');
            
            // Update active button
            $('.btn-view-toggle').removeClass('active');
            $(this).addClass('active');
            
            // Show selected view, hide others
            $('.view-content').hide();
            $(`#${viewType}View`).show();
            
            // Handle view-specific setup
            if (viewType === 'table') {
                dataTable.columns.adjust().responsive.recalc();
                removeExpandAllButton();
            } else if (viewType === 'grouped') {
                addExpandAllButton();
                // Ensure all groups are expanded by default
                $('.collapse').collapse('show');
                allExpanded = true;
            }
            
            // Apply current filters to the new view
            applyFilters();
        });

        // Initialize with table view active
        $('#tableView').show();
        $('#groupedView').hide();
        removeExpandAllButton();

        // ============================================
        // STUDENT CRUD OPERATIONS
        // ============================================

        // Reset form function
        function resetForm() {
            $('.error-message').text('');
            $('#studentForm')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
        }

        // ==========
// READ (EDIT STUDENT) - Works for both Table and Grouped views
// ==========
$(document).on('click', '.e_student_id', function() {
    const id = $(this).data('id');
    
    // Find the student row in either table or grouped view
    let $row;
    if ($('#tableView').is(':visible')) {
        $row = $(`tr[data-student-id="${id}"]`);
    } else {
        $row = $(`tr[data-student-id="${id}"]`);
    }

    if ($row.length === 0) {
        console.error('Student row not found for ID:', id);
        return;
    }

    const $getphoto = $row.find('.photo').attr('src');
    const $getidnumber = $row.find('.student_id').text();
    const $getdept = $row.find('.department_id').val();
    const $getfullname = $row.find('.fullname').val();
    const $getyear = $row.find('.year').val();
    const $getsection = $row.find('.section').val();

    console.log('Editing student data:', {
        id: id,
        id_number: $getidnumber,
        fullname: $getfullname,
        department: $getdept,
        year: $getyear,
        section: $getsection
    });

    // Clear the ID number field first to prevent duplication
    $('#eid_number').val('');
    
    // Populate edit form with proper formatting
    $('#edit_studentid').val(id);
    $('.edit-photo').attr('src', $getphoto);
    
    // Format and set ID number safely
    const formattedId = formatIDNumberForEdit($getidnumber);
    $('#eid_number').val(formattedId);
    
    $('#edepartment_id').val($getdept);
    $('#efullname').val($getfullname);
    $('#eyear').val($getyear);
    $('#esection').val($getsection);
    $('.capturedImage').val($getphoto);
    
    // Clear any previous error messages
    $('.error-message').text('');
    
    // Debug: Check what value was actually set
    console.log('ID Number field value after setting:', $('#eid_number').val());
    
    // Show modal
    $('#editstudentModal').modal('show');
});

// Helper function to safely format ID number for edit
function formatIDNumberForEdit(idNumber) {
    if (!idNumber) return '';
    
    // Remove any existing formatting and non-numeric characters
    let cleanId = idNumber.replace(/[^\d]/g, '');
    
    // If it's exactly 8 digits, format as 0000-0000
    if (cleanId.length === 8) {
        return cleanId.substring(0, 4) + '-' + cleanId.substring(4, 8);
    }
    
    // If it's already formatted correctly, return as is
    if (/^\d{4}-\d{4}$/.test(idNumber)) {
        return idNumber;
    }
    
    // For any other case, take first 8 digits and format
    cleanId = cleanId.substring(0, 8);
    if (cleanId.length >= 8) {
        return cleanId.substring(0, 4) + '-' + cleanId.substring(4, 8);
    }
    
    return idNumber; // Return original if can't format
}

        // ==============
        // CREATE (ADD STUDENT)
        // ==============
        $('#studentForm').submit(function(e) {
            e.preventDefault();
            
            $('.error-message').text('');
            const department_id = $('#department_id').val();
            const id_number = $('#id_number').val().trim();
            const fullname = $('#fullname').val().trim();
            const year = $('#year').val();
            const section = $('#section').val();
            const photo = $('#photo')[0].files[0];
            let isValid = true;

            // Enhanced Validation
            if (!department_id) { 
                $('#department_id-error').text('Department is required'); 
                isValid = false; 
            }
            if (!id_number || !validateIDNumber(id_number)) { 
                $('#id_number-error').text('Valid ID Number in format 0000-0000 is required'); 
                isValid = false; 
            }
            if (!fullname || !/^[A-Za-z\s]+$/.test(fullname)) { 
                $('#fullname-error').text('Valid full name (letters and spaces only) is required'); 
                isValid = false; 
            }
            if (!year) { 
                $('#year-error').text('Year is required'); 
                isValid = false; 
            }
            if (!section) { 
                $('#section-error').text('Section is required'); 
                isValid = false; 
            }
            if (!photo) { 
                $('#photo-error').text('Photo is required'); 
                isValid = false; 
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-student').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-student').prop('disabled', true);

            var formData = new FormData(this);

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_student",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-student').html('Save');
                    $('#btn-student').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#studentModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state
                    $('#btn-student').html('Save');
                    $('#btn-student').prop('disabled', false);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred: ' + error
                    });
                }
            });
        });

        // ==========
        // UPDATE STUDENT
        // ==========
        $('#editStudentForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#edit_studentid').val();
            const department_id = $('#edepartment_id').val();
            const id_number = $('#eid_number').val().trim();
            const fullname = $('#efullname').val().trim();
            const year = $('#eyear').val();
            const section = $('#esection').val();

            // Enhanced Validation
            let isValid = true;
            if (!department_id) { 
                $('#edepartment_id-error').text('Department is required'); 
                isValid = false; 
            } else { 
                $('#edepartment_id-error').text(''); 
            }
            if (!id_number || !validateIDNumber(id_number)) { 
                $('#eid_number-error').text('Valid ID Number in format 0000-0000 is required'); 
                isValid = false; 
            } else { 
                $('#eid_number-error').text(''); 
            }
            if (!fullname || !/^[A-Za-z\s]+$/.test(fullname)) { 
                $('#efullname-error').text('Valid full name (letters and spaces only) is required'); 
                isValid = false; 
            } else { 
                $('#efullname-error').text(''); 
            }
            if (!year) { 
                $('#eyear-error').text('Year is required'); 
                isValid = false; 
            } else { 
                $('#eyear-error').text(''); 
            }
            if (!section) { 
                $('#esection-error').text('Section is required'); 
                isValid = false; 
            } else { 
                $('#esection-error').text(''); 
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-editstudent').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $('#btn-editstudent').prop('disabled', true);

            var formData = new FormData(this);
            formData.append('id', id);

            $.ajax({
                type: "POST",
                url: "transac.php?action=update_student",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editstudent').html('Update');
                    $('#btn-editstudent').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#editstudentModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state
                    $('#btn-editstudent').html('Update');
                    $('#btn-editstudent').prop('disabled', false);
                    
                    try {
                        // Try to parse the error response as JSON
                        var errorResponse = JSON.parse(xhr.responseText);
                        Swal.fire({
                            title: 'Error!',
                            text: errorResponse.message || 'An error occurred',
                            icon: 'error'
                        });
                    } catch (e) {
                        // If not JSON, show raw response
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred: ' + xhr.responseText,
                            icon: 'error'
                        });
                    }
                }
            });
        });

        // ==========
        // DELETE STUDENT - Works for both Table and Grouped views
        // ==========
        $(document).on('click', '.d_student_id', function() {
            var studentId = $(this).data('id');
            var studentName = $(this).attr('student_name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete student: " + studentName,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "transac.php?action=delete_student",
                        type: 'POST',
                        data: { 
                            id: studentId 
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred: ' + error,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Reset modal when closed
        $('#studentModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
            $('.error-message').text('');
        });

        $('#editstudentModal').on('hidden.bs.modal', function () {
            $('.error-message').text('');
        });

        // Image preview functionality for both forms
        $(document).on('change', '[class^=upload-field-]', function() {
            readURL(this);
        });

        // Click handler for edit photo upload
        $(document).on('click', '.edit-photo', function() {
            $('#editPhoto').click();
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validFormats = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                // Validate file format
                if (!validFormats.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Format',
                        text: 'Only JPG and PNG formats are allowed.',
                    });
                    input.value = ''; // Reset the input
                    return;
                }

                // Validate file size
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Maximum file size is 2MB.',
                    });
                    input.value = ''; // Reset the input
                    return;
                }

                // Preview the image
                var reader = new FileReader();
                reader.onload = function (e) {
                    // Find the closest preview image
                    $(input).closest('.file-uploader').find('.preview-1').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }

        // Initialize input validations
        initializeInputValidations();
    });
    </script>
</body>
</html>