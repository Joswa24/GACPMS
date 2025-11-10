<?php
session_start();
include 'connection.php';

$inactive = 1800; // 30 minutes

if (isset($_SESSION['access']['last_activity']) && 
   (time() - $_SESSION['access']['last_activity'] > $inactive)) {
    
    if (isset($_SESSION['access']['instructor']['id'])) {
        $instructorId = $_SESSION['access']['instructor']['id'];
        $currentDateTime = date('Y-m-d H:i:s');
        
        $stmt = $db->prepare("UPDATE instructor_logs 
                             SET time_out = ? 
                             WHERE instructor_id = ? AND time_out IS NULL 
                             ORDER BY time_in DESC LIMIT 1");
        $stmt->bind_param("si", $currentDateTime, $instructorId);
        $stmt->execute();
    }
    
    session_unset();
    session_destroy();
    header("Location: index.php?timeout=1");
    exit();
}

$_SESSION['access']['last_activity'] = time();
?>