<?php
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

if (isset($_POST['query'])) {
    $search = $db->real_escape_string($_POST['query']);
    $sql = "SELECT first_name, last_name FROM personell WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' LIMIT 10";
    $result = $db->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p>" . $row['first_name'] . " " . $row['last_name'] . "</p>";
        }
    } else {
        echo "<p>No matches found</p>";
    }
}

$db->close();
?>
