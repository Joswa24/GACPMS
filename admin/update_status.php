<?php
// Include connection
include '../connection.php';
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $personnel_id = isset($_POST['personnel_id']) ? intval($_POST['personnel_id']) : 0;

    if ($action === 'block') {
        $lostcard_id = isset($_POST['lostcard_id']) ? intval($_POST['lostcard_id']) : 0;

        // Update the 'lostcard' table status to 1
        $updateLostCard = "UPDATE lostcard SET status = 1 WHERE personnel_id = $personnel_id";
        mysqli_query($db, $updateLostCard);

        // Update the 'personell' table status to 'Block'
        $updatePersonell = "UPDATE personell SET status = 'Block' WHERE id = $personnel_id";
        mysqli_query($db, $updatePersonell);

        if (mysqli_affected_rows($db) > 0) {
            echo "success";
        } else {
            echo "error";
        }
    } elseif ($action === 'delete') {
        // Delete the user from the 'lostcard' table
        $deleteLostCard = "DELETE FROM lostcard WHERE personnel_id = $personnel_id";
        mysqli_query($db, $deleteLostCard);

        if (mysqli_affected_rows($db) > 0) {
            echo "success";
        } else {
            echo "error";
        }
    }
}

$db->close();
?>
