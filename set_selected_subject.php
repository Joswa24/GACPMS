<?php
include 'connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = $_POST['id_number'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $room = $_POST['room'] ?? '';
    
    if (empty($id_number) || empty($subject) || empty($room)) {
        http_response_code(400);
        die("Invalid request");
    }
    
    // Store in session (you'll need to retrieve this in main1.php)
    $_SESSION['selected_subject'] = [
        'subject' => $subject,
        'room' => $room,
        'id_number' => $id_number
    ];
    
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(405);
    die("Method not allowed");
}