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


// Determine if we're viewing current or archived logs
$view = isset($_GET['view']) ? $_GET['view'] : 'current';

// Date filter logic
if (isset($_GET['date']) && $_GET['date'] !== '') {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// Add year and section filter logic
$search_year = isset($_GET['search_year']) ? trim($_GET['search_year']) : '';
$search_section = isset($_GET['search_section']) ? trim($_GET['search_section']) : '';

// Handle Save Attendance action
if (isset($_POST['save_attendance'])) {
    $createTableQuery = "CREATE TABLE IF NOT EXISTS archived_attendance_logs LIKE attendance_logs";
    $db->query($createTableQuery);

    $archiveQuery = "INSERT INTO archived_attendance_logs 
                    SELECT * FROM attendance_logs 
                    WHERE DATE(time_in) = CURDATE()";
    $db->query($archiveQuery);

    $clearQuery = "DELETE FROM attendance_logs WHERE DATE(time_in) = CURDATE()";
    $db->query($clearQuery);

    $_SESSION['message'] = "Today's attendance data has been archived successfully!";
    header("Location: student_logs.php?view=current");
    exit();
}
?>

<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid pt-4 px-4">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <div class="row">
                        <div class="col-9">
                            <h6 class="mb-4">Student Attendance Logs</h6>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success mt-3">
                            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>
                    <form method="get" class="row mb-3">
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                        <div class="col-lg-3">
                            <label>Date:</label>
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-lg-2">
                            <label>Year:</label>
                            <input type="text" class="form-control" name="search_year" placeholder="e.g. 1st" value="<?php echo htmlspecialchars($search_year); ?>">
                        </div>
                        <div class="col-lg-2">
                            <label>Section:</label>
                            <input type="text" class="form-control" name="search_section" placeholder="e.g. A" value="<?php echo htmlspecialchars($search_section); ?>">
                        </div>
                        <div class="col-lg-2 mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                            <a href="student_logs.php" class="btn btn-warning"><i class="fa fa-sync"></i> Reset</a>
                        </div>
                        <div class="col-lg-3 mt-4" style="text-align:right;">
                            <?php if ($view == 'current'): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to archive today\'s attendance data?');">
                                    <button type="submit" name="save_attendance" class="btn btn-success">
                                        <i class="fas fa-save"></i> Archive Today's Data
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </form>
                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($view == 'current') ? 'active' : ''; ?>" href="student_logs.php?view=current">Current Logs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($view == 'archived') ? 'active' : ''; ?>" href="student_logs.php?view=archived">Archived Logs</a>
                        </li>
                    </ul>
                    <div class="table-responsive">
                        <h6>Attendance for: <span class="text-primary"><?php echo date('F d, Y', strtotime($selected_date)); ?></span></h6>
                        <table class="table table-striped">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Section</th>
                                    <th>Year</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $table = ($view == 'archived') ? 'archived_attendance_logs' : 'attendance_logs';
                                $query = "SELECT l.*, s.fullname, s.section, s.year 
                                          FROM $table l
                                          JOIN students s ON l.student_id = s.id
                                          WHERE DATE(l.time_in) = ?";
                                $params = [$selected_date];
                                $types = "s";
                                if ($search_year !== '') {
                                    $query .= " AND s.year LIKE ?";
                                    $params[] = "%$search_year%";
                                    $types .= "s";
                                }
                                if ($search_section !== '') {
                                    $query .= " AND s.section LIKE ?";
                                    $params[] = "%$search_section%";
                                    $types .= "s";
                                }
                                $query .= " ORDER BY s.year ASC, s.section ASC, l.time_in DESC";
                                $stmt = $db->prepare($query);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                $lastYear = $lastSection = null;
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        if ($lastYear !== $row['year'] || $lastSection !== $row['section']) {
                                            echo '<tr class="table-primary"><td colspan="9"><b>Year: ' . htmlspecialchars($row['year']) . ' | Section: ' . htmlspecialchars($row['section']) . '</b></td></tr>';
                                            $lastYear = $row['year'];
                                            $lastSection = $row['section'];
                                        }
                                        echo '<tr>';
                                        echo '<td>'.$row['id_number'].'</td>';
                                        echo '<td>'.$row['fullname'].'</td>';
                                        echo '<td>'.$row['section'].'</td>';
                                        echo '<td>'.$row['year'].'</td>';
                                        echo '<td>'.($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : 'N/A').'</td>';
                                        echo '<td>'.($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'N/A').'</td>';
                                        echo '<td>'.$row['department'].'</td>';
                                        echo '<td>'.$row['location'].'</td>';
                                        echo '<td>'.date('m/d/Y', strtotime($row['time_in'])).'</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="9" class="text-center">No attendance records found for this date</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>
</div>
<?php mysqli_close($db); ?>