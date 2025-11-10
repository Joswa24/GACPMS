<?php
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
// Include connection
include '../connection.php';

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    $searchTerm = "%" . $query . "%";
    
    // Search in instructor_glogs table
    $sql1 = "SELECT DISTINCT instructor_id as id, name, 'instructor' as type 
             FROM instructor_glogs 
             WHERE name LIKE ?";
    
    // Search in personell_glogs table  
    $sql2 = "SELECT DISTINCT personell_id as id, name, 'personell' as type 
             FROM personell_glogs 
             WHERE name LIKE ?";
    
    $stmt1 = $db->prepare($sql1);
    $stmt1->bind_param("s", $searchTerm);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    
    $stmt2 = $db->prepare($sql2);
    $stmt2->bind_param("s", $searchTerm);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    $results = [];
    
    // Add instructors to results
    while ($row = $result1->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'fullname' => $row['name'],
            'type' => $row['type']
        ];
    }
    
    // Add personnel to results
    while ($row = $result2->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'fullname' => $row['name'],
            'type' => $row['type']
        ];
    }
    
    $stmt1->close();
    $stmt2->close();
    $db->close();
    
    header('Content-Type: application/json');
    echo json_encode($results);
} else {
    echo json_encode([]);
}
?>