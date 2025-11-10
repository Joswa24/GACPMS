<?php
session_start();
include 'connection.php';

function getStudentPhotoForScanner($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/default.png';

    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Fetch all student photos
$photos_query = "SELECT id_number, photo FROM students WHERE photo IS NOT NULL AND photo != ''";
$photos_result = mysqli_query($db, $photos_query);
$student_photos = [];

while ($row = mysqli_fetch_assoc($photos_result)) {
    $photo_path = getStudentPhotoForScanner($row['photo']);
    $student_photos[$row['id_number']] = $photo_path;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'photos' => $student_photos
]);

mysqli_close($db);
?>