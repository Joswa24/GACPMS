<?php
session_start();
// Include your database connection file
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

// Retrieve date1 and date2 from POST data
$date1 = date('Y-m-d', strtotime($_POST['date1']));
$date2 = date('Y-m-d', strtotime($_POST['date2']));

// SQL query to fetch filtered data
$sql = "SELECT * FROM personell_logs WHERE date_logged BETWEEN '$date1' AND '$date2'";
$result = mysqli_query($db, $sql);

// Check if query was successful
if ($result) {
    // Start generating HTML output
    $output = '';

    // Fetch data row by row
    while ($row = mysqli_fetch_array($result)) {
        $output .= '<tr>';
        $output .= '<td><center><img src="uploads/' . $row['photo'] . '" width="50px" height="50px"></center></td>';
        $output .= '<td>' . $row['full_name'] . '</td>';
        $output .= '<td>' . $row['department'] . '</td>';
        $output .= '<td>' . $row['role'] . '</td>';
        $output .= '<td>' . $row['time_in_am'] . '</td>';
        $output .= '<td>' . $row['time_out_am'] . '</td>';
        $output .= '<td>' . $row['time_in_pm'] . '</td>';
        $output .= '<td>' . $row['time_out_pm'] . '</td>';
        $output .= '<td>' . $row['date_logged'] . '</td>';
        $output .= '</tr>';
    }

    // Send the generated HTML back to the main page
    echo $output;
} else {
    // Error handling if query fails
    echo '<tr><td colspan="9">No records found.</td></tr>';
}

// Close database connection
mysqli_close($db);
?>
