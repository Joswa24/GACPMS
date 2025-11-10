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

if (!isset($_GET['room_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Room name not provided']);
    exit;
}

$roomName = $_GET['room_name'];
$stmt = $db->prepare("SELECT * FROM rooms WHERE room = ?");
$stmt->bind_param("s", $roomName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'data' => [
            'department' => $room['department'],
            'password' => $room['password'],
            'location' => $room['room']
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Room not found']);
}
?>