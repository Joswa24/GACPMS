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

// --- CREATE TABLE COLUMNS IF NOT EXISTS (optional, for first-time setup) ---
$db->query("ALTER TABLE room_schedules 
    ADD COLUMN IF NOT EXISTS department VARCHAR(255) NOT NULL AFTER year_level,
    ADD COLUMN IF NOT EXISTS instructor VARCHAR(255) NOT NULL AFTER department");

// --- FETCH FOR EDIT ---
 $edit_mode = false;
 $edit_data = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM room_schedules WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    $edit_data = $edit_result->fetch_assoc();
}

// --- GET ALL SCHEDULES ---
 $schedules = $db->query("SELECT * FROM room_schedules ORDER BY room_name, day, start_time");

// --- GET FILTER OPTIONS ---
 $departments = $db->query("SELECT DISTINCT department FROM room_schedules ORDER BY department");
 $rooms = $db->query("SELECT DISTINCT room_name FROM room_schedules ORDER BY room_name");
 $subjects = $db->query("SELECT DISTINCT subject FROM room_schedules ORDER BY subject");
 $year_levels = $db->query("SELECT DISTINCT year_level FROM room_schedules ORDER BY year_level");
 $days = $db->query("SELECT DISTINCT day FROM room_schedules ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
 $instructors = $db->query("SELECT DISTINCT instructor FROM room_schedules ORDER BY instructor");

// --- GET ALL OPTIONS FOR ADD MODAL ---
 $all_departments = $db->query("SELECT * FROM department WHERE department_name != 'Main' ORDER BY department_name");
 $all_rooms = $db->query("SELECT * FROM rooms ORDER BY room");
 $all_subjects = $db->query("SELECT * FROM subjects ORDER BY subject_name");
 $all_instructors = $db->query("SELECT * FROM instructor ORDER BY fullname");
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Schedules - RFIDGPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
            max-height: 700px; /* Increased from 600px */
            position: relative;
        }

        /* Custom scrollbar for table */
        .table-responsive::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #4361ee, var(--icon-color));
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #17a673);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #be2617);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #2e59d9);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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

        .action-buttons {
            white-space: nowrap;
        }

        .action-buttons .btn {
            margin: 2px;
            padding: 6px 10px;
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

        .time-display {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 140px;
        }

        .time-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .time-icon {
            width: 20px;
            text-align: center;
            font-size: 0.8rem;
        }

        .time-text {
            flex: 1;
        }

        .schedule-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-color);
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .section-header {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            border-left: 4px solid var(--danger-color);
            border-radius: 8px;
        }

        .btn-del {
            transition: all 0.3s ease;
        }

        .btn-del:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .filter-badge {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 5px;
        }

        .option-disabled {
            color: #6c757d;
            font-style: italic;
        }

        .swap-preview {
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            margin-top: 15px;
        }

        .schedule-card {
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius);
            padding: 10px;
            margin-bottom: 10px;
        }

        .schedule-card.highlight {
            border-color: var(--icon-color);
            background-color: rgba(92, 149, 233, 0.05);
        }

        .swap-arrow {
            font-size: 2rem;
            color: var(--icon-color);
            text-align: center;
            margin: 10px 0;
        }

        /* Flip Card Styles */
        .schedule-flip-card {
            background-color: transparent;
            width: 100%;
            height: 180px;
            perspective: 1000px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .schedule-flip-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .schedule-flip-card:hover .schedule-flip-card-inner {
            transform: rotateY(180deg);
        }

        .schedule-flip-card-front, .schedule-flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .schedule-flip-card-front {
            background-color: var(--light-bg);
            color: var(--dark-text);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }

        .schedule-flip-card-back {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            transform: rotateY(180deg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 15px;
        }

        .schedule-flip-card.selected .schedule-flip-card-front {
            border: 2px solid var(--icon-color);
            background-color: rgba(92, 149, 233, 0.1);
        }

        .schedule-flip-card.selected .schedule-flip-card-back {
            background: linear-gradient(135deg, #2e59d9, #4361ee);
        }

        .schedule-info {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: space-between;
        }

        .schedule-info h6 {
            margin-bottom: 10px;
        }

        .schedule-info p {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .time-badge {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .schedule-flip-card.selected .time-badge {
            background: linear-gradient(135deg, #2e59d9, #4361ee);
        }

        .schedule-flip-card-front h6 {
            margin: 5px 0;
            font-weight: bold;
        }

        .schedule-flip-card-front p {
            margin: 3px 0;
            font-size: 0.85rem;
        }

        .select-schedule-btn {
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .select-schedule-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .schedule-card {
            max-height: 400px;
            overflow-y: auto;
        }

        .export-btn {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

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

        .room-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .room-info {
            display: flex;
            align-items: center;
        }

        /* Form validation styles */
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: var(--danger-color);
            background-image: none;
        }

        .form-control.is-valid, .form-select.is-valid {
            border-color: var(--success-color);
            background-image: none;
        }

        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .valid-feedback {
            color: var(--success-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
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
                            <div class="col-7">
                                <h6 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Room Schedules Management</h6>
                            </div>
                            <div class="col-5 text-end">
                                <button type="button" class="btn btn-info m-2" data-bs-toggle="modal" data-bs-target="#swapScheduleModal">
                                    <i class="fas fa-exchange-alt me-2"></i>Swap Schedule
                                </button>
                                <button type="button" class="btn btn-warning m-2" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                    <i class="fas fa-plus-circle me-2"></i>Add Schedule
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                    <label class="fw-bold">Department:</label>
                                        <select class="form-control filter-select" id="filter_department" name="filter_department">
                                            <option value="">All Departments</option>
                                            <?php 
                                            // Reset the pointer to the beginning of the result set
                                            $all_departments->data_seek(0);
                                            while ($dept = $all_departments->fetch_assoc()): 
                                                // Skip the "Main" department
                                                if ($dept['department_name'] !== 'Main'): 
                                            ?>
                                                <option value="<?= htmlspecialchars($dept['department_name']) ?>">
                                                    <?= htmlspecialchars($dept['department_name']) ?>
                                                </option>
                                            <?php 
                                                endif;
                                            endwhile; 
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="fw-bold">Room:</label>
                                        <select class="form-control filter-select" id="filter_room" name="filter_room">
                                            <option value="">All Rooms</option>
                                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($room['room_name']) ?>">
                                                    <?= htmlspecialchars($room['room_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="fw-bold">Subject:</label>
                                        <select class="form-control filter-select" id="filter_subject" name="filter_subject">
                                            <option value="">All Subjects</option>
                                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($subject['subject']) ?>">
                                                    <?= htmlspecialchars($subject['subject']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="fw-bold">Year Level:</label>
                                        <select class="form-control filter-select" id="filter_year_level" name="filter_year_level">
                                            <option value="">All Year Levels</option>
                                            <?php while ($year = $year_levels->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($year['year_level']) ?>">
                                                    <?= htmlspecialchars($year['year_level']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="fw-bold">Day:</label>
                                        <select class="form-control filter-select" id="filter_day" name="filter_day">
                                            <option value="">All Days</option>
                                            <?php while ($day = $days->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($day['day']) ?>">
                                                    <?= htmlspecialchars($day['day']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="fw-bold">Instructor:</label>
                                        <select class="form-control filter-select" id="filter_instructor" name="filter_instructor">
                                            <option value="">All Instructors</option>
                                            <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($instructor['instructor']) ?>">
                                                    <?= htmlspecialchars($instructor['instructor']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="fw-bold">Time Range:</label>
                                        <select class="form-control filter-select" id="filter_time" name="filter_time">
                                            <option value="">All Times</option>
                                            <option value="morning">Morning (6AM-12PM)</option>
                                            <option value="afternoon">Afternoon (12PM-6PM)</option>
                                            <option value="evening">Evening (6PM-10PM)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group filter-btn">
                                        <div class="d-flex gap-2 w-100">
                                            <button type="button" class="btn btn-primary flex-fill" id="applyFilters">
                                                <i class="fas fa-filter me-2"></i>Apply Filters
                                            </button>
                                            <button type="button" class="btn btn-secondary" id="resetFilters">
                                                <i class="fas fa-sync me-2"></i>
                                            </button>
                                            <button type="button" class="btn export-btn" onclick="exportToExcel()">
                                                <i class="fas fa-file-excel me-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div id="activeFilters" class="d-flex flex-wrap gap-2">
                                        <!-- Active filters will appear here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Room Schedules Table -->
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0" id="myDataTable">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-door-open me-1"></i> Room</th>
                                                <th><i class="fas fa-book me-1"></i> Subject</th>
                                                <th><i class="fas fa-users me-1"></i> Section</th>
                                                <th><i class="fas fa-graduation-cap me-1"></i> Year</th>
                                                <th><i class="fas fa-calendar-day me-1"></i> Day</th>
                                                <th><i class="fas fa-chalkboard-teacher me-1"></i> Instructor</th>
                                                <th><i class="fas fa-clock me-1"></i> Time</th>
                                                <th><i class="fas fa-cogs me-1"></i> Actions</th>
                                                <th style="display: none;">Date Added</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $schedules->fetch_assoc()): ?>
                                            <tr class="table-<?= $row['id'] ?>" data-schedule-id="<?= $row['id'] ?>">
                                                <input class="department" type="hidden" value="<?= htmlspecialchars($row['department']) ?>" />
                                                <input class="room_name" type="hidden" value="<?= htmlspecialchars($row['room_name']) ?>" />
                                                <input class="subject" type="hidden" value="<?= htmlspecialchars($row['subject']) ?>" />
                                                <input class="section" type="hidden" value="<?= htmlspecialchars($row['section']) ?>" />
                                                <input class="year_level" type="hidden" value="<?= htmlspecialchars($row['year_level']) ?>" />
                                                <input class="day" type="hidden" value="<?= htmlspecialchars($row['day']) ?>" />
                                                <input class="instructor" type="hidden" value="<?= htmlspecialchars($row['instructor']) ?>" />
                                                <input class="start_time" type="hidden" value="<?= htmlspecialchars($row['start_time']) ?>" />
                                                <input class="end_time" type="hidden" value="<?= htmlspecialchars($row['end_time']) ?>" />
                                                
                                                <td>
                                                    <div class="room-info">
                                                        <div class="room-icon">
                                                            <i class="fas fa-door-open"></i>
                                                        </div>
                                                        <div>
                                                            <strong><?= htmlspecialchars($row['room_name']) ?></strong><br>
                                                            <small class="text-muted"><?= htmlspecialchars($row['department']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                                <td><?= htmlspecialchars($row['section']) ?></td>
                                                <td><?= htmlspecialchars($row['year_level']) ?></td>
                                                <td><?= htmlspecialchars($row['day']) ?></td>
                                                <td><?= htmlspecialchars($row['instructor']) ?></td>
                                                <td>
                                                    <div class="time-display">
                                                        <div class="time-item">
                                                            <div class="time-icon">
                                                                <i class="fas fa-play-circle"></i>
                                                            </div>
                                                            <div class="time-text">
                                                                <?= date("g:i A", strtotime($row['start_time'])) ?>
                                                            </div>
                                                        </div>
                                                        <div class="time-item">
                                                            <div class="time-icon">
                                                                <i class="fas fa-stop-circle"></i>
                                                            </div>
                                                            <div class="time-text">
                                                                <?= date("g:i A", strtotime($row['end_time'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="action-buttons">
                                                    <center>
                                                        <button data-id="<?= $row['id'] ?>" 
                                                                class="btn btn-primary btn-sm btn-edit e_schedule_id">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button schedule_name="<?= htmlspecialchars($row['room_name'] . ' - ' . $row['subject']) ?>" 
                                                                data-id="<?= $row['id'] ?>" 
                                                                class="btn btn-danger btn-sm btn-del d_schedule_id">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </center>
                                                </td>
                                                <td style="display:none;" class="hidden-date">
                                                    <?= isset($row['date_added']) ? $row['date_added'] : date('Y-m-d H:i:s') ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Add Schedule Modal -->
            <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle me-2"></i> New Room Schedule
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="scheduleForm" role="form" method="post" action="">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-schedule"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-schedule"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SCHEDULE INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="schedule-icon mx-auto mb-3">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            </div>

                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Department:</label>
                                                    <select name="department" id="add_department" class="form-control" required>
                                                        <option value="">Select Department</option>
                                                        <?php 
                                                        $all_departments->data_seek(0);
                                                        while ($dept = $all_departments->fetch_assoc()):
                                                        if ($dept['department_name'] !== 'Main'): 
                                                        endif;?>
                                                            <option value="<?= htmlspecialchars($dept['department_name']) ?>">
                                                                <?= htmlspecialchars($dept['department_name']) ?>
                                                            </option>
                                                        <?php
                                                    endwhile; ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="department-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label class="fw-bold">Room Name:</label>
                                                    <select name="room_name" id="add_room_name" class="form-control" required>
                                                        <option value="">Select Room</option>
                                                        <!-- Rooms will be populated dynamically based on department -->
                                                    </select>
                                                    <div class="invalid-feedback" id="room_name-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label class="fw-bold">Year Level:</label>
                                                    <select name="year_level" id="add_year_level" class="form-control" required>
                                                        <option value="">Select Year Level</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <div class="invalid-feedback" id="year_level-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Subject:</label>
                                                    <select name="subject" id="add_subject" class="form-control" required>
                                                        <option value="">Select Subject</option>
                                                        <!-- Subjects will be populated dynamically based on year level -->
                                                    </select>
                                                    <div class="invalid-feedback" id="subject-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Section:</label>
                                                    <input type="text" name="section" id="add_section" class="form-control" required placeholder="Input Section..">
                                                    <div class="invalid-feedback" id="section-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Day:</label>
                                                    <select name="day" id="add_day" class="form-control" required>
                                                        <option value="">Select Day</option>
                                                        <?php
                                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                        foreach ($days as $day) {
                                                            echo "<option value='$day'>$day</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="day-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-8 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Instructor:</label>
                                                    <select name="instructor" id="add_instructor" class="form-control" required>
                                                        <option value="">Select Instructor</option>
                                                        <!-- Instructors will be populated from database -->
                                                        <?php 
                                                        $instructors_modal = $db->query("SELECT * FROM instructor ORDER BY fullname");
                                                        while ($instructor = $instructors_modal->fetch_assoc()): ?>
                                                            <option value="<?= htmlspecialchars($instructor['fullname']) ?>" 
                                                                    data-rfid="<?= htmlspecialchars($instructor['rfid_number']) ?>">
                                                                <?= htmlspecialchars($instructor['fullname']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="instructor-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Start Time:</label>
                                                    <input type="time" name="start_time" id="add_start_time" class="form-control" required>
                                                    <div class="invalid-feedback" id="start_time-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">End Time:</label>
                                                    <input type="time" name="end_time" id="add_end_time" class="form-control" required>
                                                    <div class="invalid-feedback" id="end_time-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-schedule" class="btn btn-warning">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Schedule Modal -->
            <div class="modal fade" id="editscheduleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i> Edit Room Schedule
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editScheduleForm" class="edit-form" role="form" method="post" action="">
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-12 mt-1" id="mgs-editschedule"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editschedule"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SCHEDULE INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="schedule-icon mx-auto mb-3">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            </div>
                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Department:</label>
                                                    <select name="department" id="edepartment" class="form-control" required>
                                                        <option value="">Select Department</option>
                                                        <?php 
                                                        $all_departments->data_seek(0);
                                                        while ($dept = $all_departments->fetch_assoc()):
                                                        if ($dept['department_name'] !== 'Main'): 
                                                        endif;?>
                                                            <option value="<?= htmlspecialchars($dept['department_name']) ?>">
                                                                <?= htmlspecialchars($dept['department_name']) ?>
                                                            </option>
                                                        <?php
                                                    endwhile; ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="edepartment-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label class="fw-bold">Room Name:</label>
                                                    <select name="room_name" id="eroom_name" class="form-control" required>
                                                        <option value="">Select Room</option>
                                                        <?php
                                                        $rooms = $db->query("SELECT * FROM rooms ORDER BY room");
                                                        while ($room = $rooms->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($room['room']).'" data-department="'.htmlspecialchars($room['department']).'">'.htmlspecialchars($room['room']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="eroom_name-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label class="fw-bold">Year Level:</label>
                                                    <select name="year_level" id="eyear_level" class="form-control" required>
                                                        <option value="">Select Year Level</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <div class="invalid-feedback" id="eyear_level-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Subject:</label>
                                                    <select name="subject" id="esubject" class="form-control" required>
                                                        <option value="">Select Subject</option>
                                                        <?php
                                                        $subjects = $db->query("SELECT * FROM subjects ORDER BY subject_name");
                                                        while ($subject = $subjects->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($subject['subject_name']).'" 
                                                                  data-year-level="'.htmlspecialchars($subject['year_level']).'">'
                                                                  .htmlspecialchars($subject['subject_code']).' - '.htmlspecialchars($subject['subject_name'])
                                                                  .'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="esubject-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Section:</label>
                                                    <input type="text" name="section" id="esection" class="form-control" required>
                                                    <div class="invalid-feedback" id="esection-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Day:</label>
                                                    <select name="day" id="eday" class="form-control" required>
                                                        <option value="">Select Day</option>
                                                        <?php
                                                        foreach ($days as $day) {
                                                            echo "<option value='$day'>$day</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="eday-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-8 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Instructor:</label>
                                                    <select name="instructor" id="einstructor" class="form-control" required>
                                                        <option value="">Select Instructor</option>
                                                        <?php
                                                        $instructors = $db->query("SELECT * FROM instructor ORDER BY fullname");
                                                        while ($instructor = $instructors->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($instructor['fullname']).'">'.htmlspecialchars($instructor['fullname']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="einstructor-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Start Time:</label>
                                                    <input type="time" name="start_time" id="estart_time" class="form-control" required>
                                                    <div class="invalid-feedback" id="estart_time-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">End Time:</label>
                                                    <input type="time" name="end_time" id="eend_time" class="form-control" required>
                                                    <div class="invalid-feedback" id="eend_time-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_scheduleid" name="schedule_id" class="edit-id">
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editschedule" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Swap Schedule Modal -->
            <div class="modal fade" id="swapScheduleModal" tabindex="-1" aria-labelledby="swapScheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="swapScheduleModalLabel">
                                <i class="fas fa-exchange-alt me-2"></i> Swap Instructor Schedules
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="section-header p-2 mb-3 rounded">
                                <strong>SELECT SCHEDULES TO SWAP</strong>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-bold">First Instructor:</label>
                                        <select class="form-control" id="swap_instructor1" required>
                                            <option value="">Select First Instructor</option>
                                            <?php 
                                            $all_instructors->data_seek(0);
                                            while ($instructor = $all_instructors->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($instructor['fullname']) ?>">
                                                    <?= htmlspecialchars($instructor['fullname']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback" id="swap_instructor1-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-bold">Second Instructor:</label>
                                        <select class="form-control" id="swap_instructor2" required>
                                            <option value="">Select Second Instructor</option>
                                            <?php 
                                            $all_instructors->data_seek(0);
                                            while ($instructor = $all_instructors->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($instructor['fullname']) ?>">
                                                    <?= htmlspecialchars($instructor['fullname']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback" id="swap_instructor2-error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-bold">Room:</label>
                                        <select class="form-control" id="swap_room" required>
                                            <option value="">Select Room</option>
                                            <?php 
                                            $all_rooms->data_seek(0);
                                            while ($room = $all_rooms->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($room['room']) ?>">
                                                    <?= htmlspecialchars($room['room']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback" id="swap_room-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="fw-bold">Day:</label>
                                        <select class="form-control" id="swap_day" required>
                                            <option value="">Select Day</option>
                                            <?php
                                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                            foreach ($days as $day) {
                                                echo "<option value='$day'>$day</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="invalid-feedback" id="swap_day-error"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12 text-center">
                                    <button type="button" class="btn btn-info" id="findSchedulesBtn">
                                        <i class="fas fa-search me-2"></i> Find Schedules
                                    </button>
                                </div>
                            </div>
                            
                            <div id="swapPreview" class="swap-preview" style="display: none;">
                                <h6 class="mb-3">Schedule Preview</h6>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="schedule-card" id="schedule1Card">
                                            <h6 class="text-center">First Instructor Schedule</h6>
                                            <div id="schedule1Details">
                                                <!-- Schedule details will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="swap-arrow">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="schedule-card" id="schedule2Card">
                                            <h6 class="text-center">Second Instructor Schedule</h6>
                                            <div id="schedule2Details">
                                                <!-- Schedule details will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-info" id="confirmSwapBtn" disabled>
                                <i class="fas fa-exchange-alt me-2"></i> Confirm Swap
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="delschedule-modal" tabindex="-1" aria-labelledby="delscheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="delscheduleModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete schedule: <strong id="delete_schedulename"></strong>?</p>
                            <input type="hidden" id="delete_scheduleid">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="btn-delschedule">Yes, Delete</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="fas fa-arrow-up"></i></a></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
                    const table = document.getElementById('myDataTable');
                    const ws = XLSX.utils.table_to_sheet(table);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Room Schedules");
                    
                    const date = new Date().toISOString().split('T')[0];
                    XLSX.writeFile(wb, `room_schedules_${date}.xlsx`);
                    
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

        $(document).ready(function() {
            // Initialize DataTable with enhanced scrolling
            var dataTable = $('#myDataTable').DataTable({
                order: [[8, 'desc']],
                stateSave: true,
                language: {
                    search: "Search all columns:"
                },
                scrollY: '600px', // Set fixed height for vertical scrolling
                scrollX: true,    // Enable horizontal scrolling
                scrollCollapse: true,
                paging: false,    // Disable pagination to use scrolling instead
                fixedHeader: {
                    header: true,
                    headerOffset: $('#navbar').outerHeight()
                }
            });

            // Store room and subject data for filtering
            let roomData = <?php
                $rooms_data = $db->query("SELECT * FROM rooms ORDER BY room");
                $rooms_array = [];
                while ($room = $rooms_data->fetch_assoc()) {
                    $rooms_array[] = $room;
                }
                echo json_encode($rooms_array);
            ?>;

            let subjectData = <?php
                $subjects_data = $db->query("SELECT * FROM subjects ORDER BY subject_name");
                $subjects_array = [];
                while ($subject = $subjects_data->fetch_assoc()) {
                    $subjects_array[] = $subject;
                }
                echo json_encode($subjects_array);
            ?>;

            // Active filters object
            let activeFilters = {};

            // Function to update active filters display
            function updateActiveFilters() {
                $('#activeFilters').empty();
                let filterCount = 0;
                
                for (const [key, value] of Object.entries(activeFilters)) {
                    if (value) {
                        filterCount++;
                        let filterName = key.replace('filter_', '').replace('_', ' ');
                        filterName = filterName.charAt(0).toUpperCase() + filterName.slice(1);
                        
                        $('#activeFilters').append(`
                            <span class="filter-badge">
                                ${filterName}: ${value}
                                <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" data-filter="${key}"></button>
                            </span>
                        `);
                    }
                }
                
                // Update table info with filter count
                if (filterCount > 0) {
                    $('.dataTables_info').append(` <span class="text-primary">(${filterCount} active filter(s))</span>`);
                }
            }

            // Function to apply all filters
            function applyFilters() {
                dataTable.columns().search('').draw();
                
                // Apply department filter
                if (activeFilters.filter_department) {
                    dataTable.column(0).search(activeFilters.filter_department, true, false);
                }
                
                // Apply room filter
                if (activeFilters.filter_room) {
                    dataTable.column(0).search(activeFilters.filter_room, true, false);
                }
                
                // Apply subject filter
                if (activeFilters.filter_subject) {
                    dataTable.column(1).search(activeFilters.filter_subject, true, false);
                }
                
                // Apply year level filter
                if (activeFilters.filter_year_level) {
                    dataTable.column(3).search(activeFilters.filter_year_level, true, false);
                }
                
                // Apply day filter
                if (activeFilters.filter_day) {
                    dataTable.column(4).search(activeFilters.filter_day, true, false);
                }
                
                // Apply instructor filter
                if (activeFilters.filter_instructor) {
                    dataTable.column(5).search(activeFilters.filter_instructor, true, false);
                }
                
                // Apply time range filter
                if (activeFilters.filter_time) {
                    dataTable.column(6).every(function() {
                        var column = this;
                        var searchTerm = activeFilters.filter_time;
                        
                        column.nodes().each(function(node) {
                            var cellText = $(node).text().toLowerCase();
                            var showRow = false;
                            
                            switch(searchTerm) {
                                case 'morning':
                                    showRow = cellText.includes('am') && !cellText.includes('pm');
                                    break;
                                case 'afternoon':
                                    showRow = cellText.includes('pm') && (
                                        cellText.includes('12:') || cellText.includes('1:') || 
                                        cellText.includes('2:') || cellText.includes('3:') || 
                                        cellText.includes('4:') || cellText.includes('5:')
                                    );
                                    break;
                                case 'evening':
                                    showRow = cellText.includes('pm') && (
                                        cellText.includes('6:') || cellText.includes('7:') || 
                                        cellText.includes('8:') || cellText.includes('9:') || 
                                        cellText.includes('10:')
                                    );
                                    break;
                            }
                            
                            if (!showRow) {
                                $(node).parent().hide();
                            } else {
                                $(node).parent().show();
                            }
                        });
                    });
                }
                
                dataTable.draw();
                updateActiveFilters();
            }

            // Filter change handler
            $('.filter-select').change(function() {
                const filterName = $(this).attr('id');
                const filterValue = $(this).val();
                
                activeFilters[filterName] = filterValue;
            });

            // Apply filters button
            $('#applyFilters').click(function() {
                applyFilters();
            });

            // Remove individual filter
            $(document).on('click', '.filter-badge .btn-close', function() {
                const filterName = $(this).data('filter');
                activeFilters[filterName] = '';
                $('#' + filterName).val('');
                applyFilters();
            });

            // Reset all filters
            $('#resetFilters').click(function() {
                $('.filter-select').val('');
                activeFilters = {};
                applyFilters();
                $('#activeFilters').empty();
            });

            // =============================================
            // ADD MODAL FILTERING FUNCTIONALITY
            // =============================================

            // Function to filter rooms based on selected department
            function filterRoomsByDepartment(department) {
                const roomSelect = $('#add_room_name');
                roomSelect.empty().append('<option value="">Select Room</option>');
                
                if (department) {
                    const filteredRooms = roomData.filter(room => room.department === department);
                    
                    if (filteredRooms.length === 0) {
                        roomSelect.append('<option value="" disabled class="option-disabled">No rooms available for this department</option>');
                    } else {
                        filteredRooms.forEach(room => {
                            roomSelect.append(`<option value="${room.room}">${room.room}</option>`);
                        });
                    }
                } else {
                    // Show all rooms if no department selected
                    roomData.forEach(room => {
                        roomSelect.append(`<option value="${room.room}">${room.room}</option>`);
                    });
                }
            }

            // Function to filter subjects based on selected year level
            function filterSubjectsByYearLevel(yearLevel) {
                const subjectSelect = $('#add_subject');
                subjectSelect.empty().append('<option value="">Select Subject</option>');
                
                if (yearLevel) {
                    const filteredSubjects = subjectData.filter(subject => subject.year_level === yearLevel);
                    
                    if (filteredSubjects.length === 0) {
                        subjectSelect.append('<option value="" disabled class="option-disabled">No subjects available for this year level</option>');
                    } else {
                        filteredSubjects.forEach(subject => {
                            subjectSelect.append(`<option value="${subject.subject_name}">${subject.subject_code} - ${subject.subject_name}</option>`);
                        });
                    }
                } else {
                    // Show all subjects if no year level selected
                    subjectData.forEach(subject => {
                        subjectSelect.append(`<option value="${subject.subject_name}">${subject.subject_code} - ${subject.subject_name}</option>`);
                    });
                }
            }

            // Department change handler for ADD modal
            $('#add_department').change(function() {
                const selectedDepartment = $(this).val();
                filterRoomsByDepartment(selectedDepartment);
                
                // Auto-select room if only one option available
                const roomSelect = $('#add_room_name');
                if (roomSelect.find('option').length === 2 && !roomSelect.find('option:first').hasClass('option-disabled')) {
                    roomSelect.val(roomSelect.find('option:last').val());
                }
            });

            // Year level change handler for ADD modal
            $('#add_year_level').change(function() {
                const selectedYearLevel = $(this).val();
                filterSubjectsByYearLevel(selectedYearLevel);
                
                // Auto-select subject if only one option available
                const subjectSelect = $('#add_subject');
                if (subjectSelect.find('option').length === 2 && !subjectSelect.find('option:first').hasClass('option-disabled')) {
                    subjectSelect.val(subjectSelect.find('option:last').val());
                }
            });

            // Room selection handler to auto-fill department in ADD modal
            $('#add_room_name').change(function() {
                const selectedRoom = $(this).val();
                if (selectedRoom) {
                    const room = roomData.find(r => r.room === selectedRoom);
                    if (room) {
                        $('#add_department').val(room.department);
                    }
                }
            });

            // Subject selection handler to auto-fill year level in ADD modal
            $('#add_subject').change(function() {
                const selectedSubject = $(this).val();
                if (selectedSubject) {
                    const subject = subjectData.find(s => s.subject_name === selectedSubject);
                    if (subject) {
                        $('#add_year_level').val(subject.year_level);
                    }
                }
            });

            // Initialize ADD modal dropdowns when modal is shown
            $('#scheduleModal').on('show.bs.modal', function () {
                // Populate rooms with all options initially
                filterRoomsByDepartment('');
                // Populate subjects with all options initially
                filterSubjectsByYearLevel('');
            });

            // Reset ADD modal when closed
            $('#scheduleModal').on('hidden.bs.modal', function () {
                $(this).find('form')[0].reset();
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
                // Reset dropdowns to show all options
                filterRoomsByDepartment('');
                filterSubjectsByYearLevel('');
            });

            // =============================================
            // EDIT MODAL FILTERING FUNCTIONALITY
            // =============================================

            // Function to filter rooms based on selected department in EDIT modal
            function filterEditRoomsByDepartment(department) {
                const roomSelect = $('#eroom_name');
                roomSelect.empty().append('<option value="">Select Room</option>');
                
                if (department) {
                    const filteredRooms = roomData.filter(room => room.department === department);
                    
                    if (filteredRooms.length === 0) {
                        roomSelect.append('<option value="" disabled class="option-disabled">No rooms available for this department</option>');
                    } else {
                        filteredRooms.forEach(room => {
                            roomSelect.append(`<option value="${room.room}">${room.room}</option>`);
                        });
                    }
                } else {
                    // Show all rooms if no department selected
                    roomData.forEach(room => {
                        roomSelect.append(`<option value="${room.room}">${room.room}</option>`);
                    });
                }
            }

            // Function to filter subjects based on selected year level in EDIT modal
            function filterEditSubjectsByYearLevel(yearLevel) {
                const subjectSelect = $('#esubject');
                subjectSelect.empty().append('<option value="">Select Subject</option>');
                
                if (yearLevel) {
                    const filteredSubjects = subjectData.filter(subject => subject.year_level === yearLevel);
                    
                    if (filteredSubjects.length === 0) {
                        subjectSelect.append('<option value="" disabled class="option-disabled">No subjects available for this year level</option>');
                    } else {
                        filteredSubjects.forEach(subject => {
                            subjectSelect.append(`<option value="${subject.subject_name}">${subject.subject_code} - ${subject.subject_name}</option>`);
                        });
                    }
                } else {
                    // Show all subjects if no year level selected
                    subjectData.forEach(subject => {
                        subjectSelect.append(`<option value="${subject.subject_name}">${subject.subject_code} - ${subject.subject_name}</option>`);
                    });
                }
            }

            // Department change handler for EDIT modal
            $('#edepartment').change(function() {
                const selectedDepartment = $(this).val();
                filterEditRoomsByDepartment(selectedDepartment);
                
                // Auto-select room if only one option available
                const roomSelect = $('#eroom_name');
                if (roomSelect.find('option').length === 2 && !roomSelect.find('option:first').hasClass('option-disabled')) {
                    roomSelect.val(roomSelect.find('option:last').val());
                }
            });

            // Year level change handler for EDIT modal
            $('#eyear_level').change(function() {
                const selectedYearLevel = $(this).val();
                filterEditSubjectsByYearLevel(selectedYearLevel);
                
                // Auto-select subject if only one option available
                const subjectSelect = $('#esubject');
                if (subjectSelect.find('option').length === 2 && !subjectSelect.find('option:first').hasClass('option-disabled')) {
                    subjectSelect.val(subjectSelect.find('option:last').val());
                }
            });

            // Room selection handler to auto-fill department in EDIT modal
            $('#eroom_name').change(function() {
                const selectedRoom = $(this).val();
                if (selectedRoom) {
                    const room = roomData.find(r => r.room === selectedRoom);
                    if (room) {
                        $('#edepartment').val(room.department);
                    }
                }
            });

            // Subject selection handler to auto-fill year level in EDIT modal
            $('#esubject').change(function() {
                const selectedSubject = $(this).val();
                if (selectedSubject) {
                    const subject = subjectData.find(s => s.subject_name === selectedSubject);
                    if (subject) {
                        $('#eyear_level').val(subject.year_level);
                    }
                }
            });

            // ==========
            // READ (EDIT SCHEDULE)
            // ==========
            $(document).on('click', '.e_schedule_id', function() {
                const id = $(this).data('id');
                
                // Retrieve data from the selected row
                const $row = $(this).closest('tr');
                const $getdepartment = $row.find('.department').val();
                const $getroomname = $row.find('.room_name').val();
                const $getyearlevel = $row.find('.year_level').val();
                const $getsubject = $row.find('.subject').val();
                const $getsection = $row.find('.section').val();
                const $getday = $row.find('.day').val();
                const $getinstructor = $row.find('.instructor').val();
                const $getstarttime = $row.find('.start_time').val();
                const $getendtime = $row.find('.end_time').val();

                console.log('Editing schedule:', id, $getroomname, $getsubject);

                // Clear any previous validation messages
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
                
                // Populate edit form
                $('#edit_scheduleid').val(id);
                $('#edepartment').val($getdepartment);
                $('#eroom_name').val($getroomname);
                $('#eyear_level').val($getyearlevel);
                $('#esubject').val($getsubject);
                $('#esection').val($getsection);
                $('#eday').val($getday);
                $('#einstructor').val($getinstructor);
                $('#estart_time').val($getstarttime);
                $('#eend_time').val($getendtime);
                
                // Show modal
                $('#editscheduleModal').modal('show');
            });

            // ==============
            // CREATE (ADD SCHEDULE)
            // ==============
            $('#scheduleForm').submit(function(e) {
                e.preventDefault();
                
                // Clear previous validation messages
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
                
                const department = $('#add_department').val();
                const room_name = $('#add_room_name').val();
                const year_level = $('#add_year_level').val();
                const subject = $('#add_subject').val();
                const section = $('#add_section').val().trim();
                const day = $('#add_day').val();
                const instructor = $('#add_instructor').val();
                const start_time = $('#add_start_time').val();
                const end_time = $('#add_end_time').val();
                
                let isValid = true;

                // Validation
                if (!department) { 
                    $('#add_department').addClass('is-invalid');
                    $('#department-error').text('Department is required'); 
                    isValid = false; 
                } else {
                    $('#add_department').addClass('is-valid');
                }
                
                if (!room_name) { 
                    $('#add_room_name').addClass('is-invalid');
                    $('#room_name-error').text('Room name is required'); 
                    isValid = false; 
                } else {
                    $('#add_room_name').addClass('is-valid');
                }
                
                if (!year_level) { 
                    $('#add_year_level').addClass('is-invalid');
                    $('#year_level-error').text('Year level is required'); 
                    isValid = false; 
                } else {
                    $('#add_year_level').addClass('is-valid');
                }
                
                if (!subject) { 
                    $('#add_subject').addClass('is-invalid');
                    $('#subject-error').text('Subject is required'); 
                    isValid = false; 
                } else {
                    $('#add_subject').addClass('is-valid');
                }
                
                if (!section) { 
                    $('#add_section').addClass('is-invalid');
                    $('#section-error').text('Section is required'); 
                    isValid = false; 
                } else {
                    $('#add_section').addClass('is-valid');
                }
                
                if (!day) { 
                    $('#add_day').addClass('is-invalid');
                    $('#day-error').text('Day is required'); 
                    isValid = false; 
                } else {
                    $('#add_day').addClass('is-valid');
                }
                
                if (!instructor) { 
                    $('#add_instructor').addClass('is-invalid');
                    $('#instructor-error').text('Instructor is required'); 
                    isValid = false; 
                } else {
                    $('#add_instructor').addClass('is-valid');
                }
                
                if (!start_time) { 
                    $('#add_start_time').addClass('is-invalid');
                    $('#start_time-error').text('Start time is required'); 
                    isValid = false; 
                } else {
                    $('#add_start_time').addClass('is-valid');
                }
                
                if (!end_time) { 
                    $('#add_end_time').addClass('is-invalid');
                    $('#end_time-error').text('End time is required'); 
                    isValid = false; 
                } else {
                    $('#add_end_time').addClass('is-valid');
                }
                
                if (start_time && end_time && start_time >= end_time) {
                    $('#add_start_time, #add_end_time').addClass('is-invalid');
                    $('#start_time-error, #end_time-error').text('End time must be after start time'); 
                    isValid = false; 
                }
                
                if (!isValid) return;

                // Show loading state
                $('#btn-schedule').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
                $('#btn-schedule').prop('disabled', true);

                var formData = {
                    department: department,
                    room_name: room_name,
                    year_level: year_level,
                    subject: subject,
                    section: section,
                    day: day,
                    instructor: instructor,
                    start_time: start_time,
                    end_time: end_time
                };

                console.log('Sending data for ADD:', formData);

                $.ajax({
                    type: "POST",
                    url: "transac.php?action=add_schedule",
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        // Reset button state
                        $('#btn-schedule').html('Save');
                        $('#btn-schedule').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: true
                            }).then(() => {
                                $('#scheduleModal').modal('hide');
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
                        $('#btn-schedule').html('Save');
                        $('#btn-schedule').prop('disabled', false);
                        
                        console.error('AJAX Error Details:');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        
                        let errorMessage = 'An error occurred';
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText || error;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage
                        });
                    }
                });
            });

            // ==========
            // UPDATE SCHEDULE
            // ==========
            $('#editScheduleForm').submit(function(e) {
                e.preventDefault();
                
                // Clear previous validation messages
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
                
                const id = $('#edit_scheduleid').val();
                const department = $('#edepartment').val();
                const room_name = $('#eroom_name').val();
                const year_level = $('#eyear_level').val();
                const subject = $('#esubject').val();
                const section = $('#esection').val().trim();
                const day = $('#eday').val();
                const instructor = $('#einstructor').val();
                const start_time = $('#estart_time').val();
                const end_time = $('#eend_time').val();

                // Validation
                let isValid = true;
                
                if (!department) { 
                    $('#edepartment').addClass('is-invalid');
                    $('#edepartment-error').text('Department is required'); 
                    isValid = false; 
                } else {
                    $('#edepartment').addClass('is-valid');
                }
                
                if (!room_name) { 
                    $('#eroom_name').addClass('is-invalid');
                    $('#eroom_name-error').text('Room name is required'); 
                    isValid = false; 
                } else {
                    $('#eroom_name').addClass('is-valid');
                }
                
                if (!year_level) { 
                    $('#eyear_level').addClass('is-invalid');
                    $('#eyear_level-error').text('Year level is required'); 
                    isValid = false; 
                } else {
                    $('#eyear_level').addClass('is-valid');
                }
                
                if (!subject) { 
                    $('#esubject').addClass('is-invalid');
                    $('#esubject-error').text('Subject is required'); 
                    isValid = false; 
                } else {
                    $('#esubject').addClass('is-valid');
                }
                
                if (!section) { 
                    $('#esection').addClass('is-invalid');
                    $('#esection-error').text('Section is required'); 
                    isValid = false; 
                } else {
                    $('#esection').addClass('is-valid');
                }
                
                if (!day) { 
                    $('#eday').addClass('is-invalid');
                    $('#eday-error').text('Day is required'); 
                    isValid = false; 
                } else {
                    $('#eday').addClass('is-valid');
                }
                
                if (!instructor) { 
                    $('#einstructor').addClass('is-invalid');
                    $('#einstructor-error').text('Instructor is required'); 
                    isValid = false; 
                } else {
                    $('#einstructor').addClass('is-valid');
                }
                
                if (!start_time) { 
                    $('#estart_time').addClass('is-invalid');
                    $('#estart_time-error').text('Start time is required'); 
                    isValid = false; 
                } else {
                    $('#estart_time').addClass('is-valid');
                }
                
                if (!end_time) { 
                    $('#eend_time').addClass('is-invalid');
                    $('#eend_time-error').text('End time is required'); 
                    isValid = false; 
                } else {
                    $('#eend_time').addClass('is-valid');
                }
                
                if (start_time && end_time && start_time >= end_time) {
                    $('#estart_time, #eend_time').addClass('is-invalid');
                    $('#estart_time-error, #eend_time-error').text('End time must be after start time'); 
                    isValid = false; 
                }
                
                if (!isValid) return;

                // Show loading state
                $('#btn-editschedule').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
                $('#btn-editschedule').prop('disabled', true);

                var formData = {
                    id: id,
                    department: department,
                    room_name: room_name,
                    year_level: year_level,
                    subject: subject,
                    section: section,
                    day: day,
                    instructor: instructor,
                    start_time: start_time,
                    end_time: end_time
                };

                console.log('Sending data for UPDATE:', formData);

                $.ajax({
                    type: "POST",
                    url: "transac.php?action=update_schedule",
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        // Reset button state
                        $('#btn-editschedule').html('Update');
                        $('#btn-editschedule').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: true
                            }).then(() => {
                                $('#editscheduleModal').modal('hide');
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
                        $('#btn-editschedule').html('Update');
                        $('#btn-editschedule').prop('disabled', false);
                        
                        console.error('AJAX Update Error Details:');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            Swal.fire({
                                title: 'Error!',
                                text: errorResponse.message || 'An error occurred',
                                icon: 'error'
                            });
                        } catch (e) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred: ' + (xhr.responseText || error),
                                icon: 'error'
                            });
                        }
                    }
                });
            });

            // Handle delete button click
            $(document).on('click', '.d_schedule_id', function() {
                var scheduleId = $(this).data('id');
                var scheduleName = $(this).attr('schedule_name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to delete schedule: " + scheduleName,
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
                            url: 'transac.php?action=delete_schedule',
                            type: 'POST',
                            data: { 
                                id: scheduleId 
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

            // Reset edit modal when closed
            $('#editscheduleModal').on('hidden.bs.modal', function () {
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
            });


            // ==========
            // SWAP SCHEDULE FUNCTIONALITY
            // ==========

            // Store schedule data for swap
            let instructor1Schedules = [];
            let instructor2Schedules = [];
            let selectedSchedule1 = null;
            let selectedSchedule2 = null;

            // Find schedules button click handler
            $('#findSchedulesBtn').click(function() {
                // Clear previous results and errors
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
                $('#swapPreview').hide();
                $('#confirmSwapBtn').prop('disabled', true);
                instructor1Schedules = [];
                instructor2Schedules = [];
                selectedSchedule1 = null;
                selectedSchedule2 = null;
                
                // Get form values
                const instructor1 = $('#swap_instructor1').val();
                const instructor2 = $('#swap_instructor2').val();
                const room = $('#swap_room').val();
                const day = $('#swap_day').val();
                
                // Validation
                let isValid = true;
                
                if (!instructor1) {
                    $('#swap_instructor1').addClass('is-invalid');
                    $('#swap_instructor1-error').text('Please select the first instructor');
                    isValid = false;
                } else {
                    $('#swap_instructor1').addClass('is-valid');
                }
                
                if (!instructor2) {
                    $('#swap_instructor2').addClass('is-invalid');
                    $('#swap_instructor2-error').text('Please select the second instructor');
                    isValid = false;
                } else {
                    $('#swap_instructor2').addClass('is-valid');
                }
                
                if (instructor1 === instructor2) {
                    $('#swap_instructor1, #swap_instructor2').addClass('is-invalid');
                    $('#swap_instructor1-error, #swap_instructor2-error').text('Please select two different instructors');
                    isValid = false;
                }
                
                if (!room) {
                    $('#swap_room').addClass('is-invalid');
                    $('#swap_room-error').text('Please select a room');
                    isValid = false;
                } else {
                    $('#swap_room').addClass('is-valid');
                }
                
                if (!day) {
                    $('#swap_day').addClass('is-invalid');
                    $('#swap_day-error').text('Please select a day');
                    isValid = false;
                } else {
                    $('#swap_day').addClass('is-valid');
                }
                
                if (!isValid) return;
                
                // Show loading state
                $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Searching...');
                $(this).prop('disabled', true);
                
                // Find schedules via AJAX
                $.ajax({
                    type: "POST",
                    url: "transac.php?action=find_all_schedules_for_swap",
                    data: {
                        instructor1: instructor1,
                        instructor2: instructor2,
                        room: room,
                        day: day
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Reset button state
                        $('#findSchedulesBtn').html('<i class="fas fa-search"></i> Find Schedules');
                        $('#findSchedulesBtn').prop('disabled', false);
                        
                        if (response.status === 'success') {
                            if (response.instructor1_schedules && response.instructor2_schedules) {
                                instructor1Schedules = response.instructor1_schedules;
                                instructor2Schedules = response.instructor2_schedules;
                                
                                // Display schedule details
                                displayAllSchedules(instructor1Schedules, instructor2Schedules);
                                
                                // Show the preview section
                                $('#swapPreview').slideDown();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Schedules Not Found',
                                    text: 'Could not find schedules for both instructors in the specified room and day.'
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'An error occurred while finding schedules'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Reset button state
                        $('#findSchedulesBtn').html('<i class="fas fa-search"></i> Find Schedules');
                        $('#findSchedulesBtn').prop('disabled', false);
                        
                        console.error('AJAX Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while finding schedules'
                        });
                    }
                });
            });

            // Function to display all schedules in flip cards
            function displayAllSchedules(schedules1, schedules2) {
                // Format time for display
                const formatTime = function(time) {
                    const [hours, minutes] = time.split(':');
                    const h = parseInt(hours);
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const displayHours = h > 12 ? h - 12 : (h === 0 ? 12 : h);
                    return `${displayHours}:${minutes} ${ampm}`;
                };
                
                // Clear previous content
                $('#schedule1Details').empty();
                $('#schedule2Details').empty();
                
                // Create flip cards for instructor 1
                if (schedules1.length > 0) {
                    schedules1.forEach((schedule, index) => {
                        const cardHtml = `
                            <div class="schedule-flip-card ${index === 0 ? 'selected' : ''}" data-schedule-id="${schedule.id}" data-instructor="1">
                                <div class="schedule-flip-card-inner">
                                    <div class="schedule-flip-card-front">
                                        <div class="time-badge">${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}</div>
                                        <h6>${schedule.subject}</h6>
                                        <p>Section: ${schedule.section}</p>
                                        <p>Year: ${schedule.year_level}</p>
                                    </div>
                                    <div class="schedule-flip-card-back">
                                        <div class="schedule-info">
                                            <h6>${schedule.subject}</h6>
                                            <p>Section: ${schedule.section}</p>
                                            <p>Year: ${schedule.year_level}</p>
                                            <p>Time: ${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}</p>
                                            <button class="btn btn-sm btn-outline-primary select-schedule-btn">Select This Schedule</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#schedule1Details').append(cardHtml);
                    });
                    
                    // Select the first schedule by default
                    selectedSchedule1 = schedules1[0];
                } else {
                    $('#schedule1Details').html('<p class="text-muted">No schedules found for this instructor</p>');
                }
                
                // Create flip cards for instructor 2
                if (schedules2.length > 0) {
                    schedules2.forEach((schedule, index) => {
                        const cardHtml = `
                            <div class="schedule-flip-card ${index === 0 ? 'selected' : ''}" data-schedule-id="${schedule.id}" data-instructor="2">
                                <div class="schedule-flip-card-inner">
                                    <div class="schedule-flip-card-front">
                                        <div class="time-badge">${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}</div>
                                        <h6>${schedule.subject}</h6>
                                        <p>Section: ${schedule.section}</p>
                                        <p>Year: ${schedule.year_level}</p>
                                    </div>
                                    <div class="schedule-flip-card-back">
                                        <div class="schedule-info">
                                            <h6>${schedule.subject}</h6>
                                            <p>Section: ${schedule.section}</p>
                                            <p>Year: ${schedule.year_level}</p>
                                            <p>Time: ${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}</p>
                                            <button class="btn btn-sm btn-outline-primary select-schedule-btn">Select This Schedule</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#schedule2Details').append(cardHtml);
                    });
                    
                    // Select the first schedule by default
                    selectedSchedule2 = schedules2[0];
                } else {
                    $('#schedule2Details').html('<p class="text-muted">No schedules found for this instructor</p>');
                }
                
                // Check if we have at least one schedule for each instructor
                if (selectedSchedule1 && selectedSchedule2) {
                    $('#confirmSwapBtn').prop('disabled', false);
                }
            }

            // Handle click on schedule cards
            $(document).on('click', '.schedule-flip-card', function() {
                const instructor = $(this).data('instructor');
                const scheduleId = $(this).data('schedule-id');
                
                // Remove selected class from all cards for this instructor
                $(`.schedule-flip-card[data-instructor="${instructor}"]`).removeClass('selected');
                
                // Add selected class to this card
                $(this).addClass('selected');
                
                // Update selected schedule
                if (instructor == 1) {
                    selectedSchedule1 = instructor1Schedules.find(s => s.id == scheduleId);
                } else {
                    selectedSchedule2 = instructor2Schedules.find(s => s.id == scheduleId);
                }
                
                // Enable confirm button if both schedules are selected
                if (selectedSchedule1 && selectedSchedule2) {
                    $('#confirmSwapBtn').prop('disabled', false);
                }
            });

            // Handle click on select schedule button
            $(document).on('click', '.select-schedule-btn', function(e) {
                e.stopPropagation();
                $(this).closest('.schedule-flip-card').click();
            });

            // Confirm swap button click handler
            $('#confirmSwapBtn').click(function() {
                if (!selectedSchedule1 || !selectedSchedule2) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select schedules for both instructors.'
                    });
                    return;
                }
                
                // Show confirmation dialog
                const formatTime = function(time) {
                    const [hours, minutes] = time.split(':');
                    const h = parseInt(hours);
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const displayHours = h > 12 ? h - 12 : (h === 0 ? 12 : h);
                    return `${displayHours}:${minutes} ${ampm}`;
                };
                
                Swal.fire({
                    title: 'Confirm Schedule Swap',
                    html: `
                        <p>Are you sure you want to swap the time schedules for these instructors?</p>
                        <div class="text-left">
                            <p><strong>${selectedSchedule1.instructor}</strong> will now teach at ${formatTime(selectedSchedule2.start_time)} - ${formatTime(selectedSchedule2.end_time)}</p>
                            <p><strong>${selectedSchedule2.instructor}</strong> will now teach at ${formatTime(selectedSchedule1.start_time)} - ${formatTime(selectedSchedule1.end_time)}</p>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, swap schedules',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state
                        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Swapping...');
                        $(this).prop('disabled', true);
                        
                        // Perform the swap via AJAX
                        $.ajax({
                            type: "POST",
                            url: "transac.php?action=swap_schedules",
                            data: {
                                schedule1_id: selectedSchedule1.id,
                                schedule2_id: selectedSchedule2.id
                            },
                            dataType: 'json',
                            success: function(response) {
                                // Reset button state
                                $('#confirmSwapBtn').html('<i class="fas fa-exchange-alt"></i> Confirm Swap');
                                $('#confirmSwapBtn').prop('disabled', false);
                                
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message,
                                        showConfirmButton: true
                                    }).then(() => {
                                        $('#swapScheduleModal').modal('hide');
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
                                $('#confirmSwapBtn').html('<i class="fas fa-exchange-alt"></i> Confirm Swap');
                                $('#confirmSwapBtn').prop('disabled', false);
                                
                                console.error('AJAX Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occurred while swapping schedules'
                                });
                            }
                        });
                    }
                });
            });

            // Reset swap modal when closed
            $('#swapScheduleModal').on('hidden.bs.modal', function () {
                $(this).find('form')[0].reset();
                $('.invalid-feedback').text('');
                $('.form-control, .form-select').removeClass('is-invalid is-valid');
                $('#swapPreview').hide();
                $('#confirmSwapBtn').prop('disabled', true);
                instructor1Schedules = [];
                instructor2Schedules = [];
                selectedSchedule1 = null;
                selectedSchedule2 = null;
            });
        });
    </script>
</body>
</html>