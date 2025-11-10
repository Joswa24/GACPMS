<?php
include 'connection.php';

$search = $_POST['search'] ?? '';

// Query to search students
$query = "SELECT * FROM students 
          WHERE fullname LIKE '%$search%' 
          OR id_number LIKE '%$search%'
          ORDER BY fullname ASC
          LIMIT 10";
$result = mysqli_query($db, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<div class="student-item d-flex justify-content-between align-items-center p-2 border-bottom">';
        echo '<div>';
        echo '<strong>' . $row['id_number'] . '</strong><br>';
        echo $row['fullname'] . ' - ' . $row['section'];
        echo '</div>';
        echo '<button class="btn btn-sm btn-primary" onclick="processManualAttendance(' . $row['id'] . ', \'' . $row['id_number'] . '\')">';
        echo 'Mark Attendance';
        echo '</button>';
        echo '</div>';
    }
} else {
    echo '<div class="text-center p-3">No students found</div>';
}

mysqli_close($db);
?>