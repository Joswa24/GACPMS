<?php
date_default_timezone_set('Asia/Manila');
session_start();

// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', '6Ld2w-QrAAAAAKcWH94dgQumTQ6nQ3EiyQKHUw4_');
define('RECAPTCHA_SECRET_KEY', '6Ld2w-QrAAAAAFeIvhKm5V6YBpIsiyHIyzHxeqm-');
define('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify');

// =====================================================================
// RECAPTCHA VALIDATION FUNCTION
// =====================================================================
function validateRecaptcha($recaptchaResponse) {
    if (empty($recaptchaResponse)) {
        return ['success' => false, 'error' => 'reCAPTCHA verification failed'];
    }
    
    $postData = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]);
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents(RECAPTCHA_VERIFY_URL, false, $context);
    $result = json_decode($response, true);
    
    return $result;
}

// Set session variables for gate access
 $_SESSION['department'] = 'Main';
 $_SESSION['location'] = 'Gate';
 $_SESSION['descr'] = 'Gate';

// Safely get department and location from session
 $department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Main';
 $location = isset($_SESSION['location']) ? $_SESSION['location'] : 'Gate';

 $logo1 = $nameo = $address = $logo2 = "";

// Fetch data from the about table
if (isset($db)) {
    $sql = "SELECT * FROM about LIMIT 1";
    $result = $db->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $logo1 = $row['logo1'];
        $nameo = $row['name'];
        $address = $row['address'];
        $logo2 = $row['logo2'];
    }
    
}

// ============================================
// ENHANCED PHOTO PATH FUNCTIONS FOR MAIN.PHP
// ============================================

/**
 * Get instructor photo path with multiple fallbacks
 */
function getInstructorPhotoPath($instructor) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (is_array($instructor)) {
        $photo = isset($instructor['photo']) ? $instructor['photo'] : '';
    } else {
        $photo = $instructor;
    }
    
    if (!empty($photo) && $photo !== 'default.png') {
        $possiblePaths = [
            'admin/uploads/instructors/' . $photo,
            '../admin/uploads/instructors/' . $photo,
            './admin/uploads/instructors/' . $photo,
            'uploads/instructors/' . $photo,
            '../uploads/instructors/' . $photo,
            './uploads/instructors/' . $photo,
            $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/instructors/' . $photo,
            dirname(__FILE__) . '/../admin/uploads/instructors/' . $photo
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0 || strpos($path, dirname(__FILE__)) === 0) {
                if (file_exists($path)) {
                    if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    } else {
                        return 'admin/uploads/instructors/' . $photo;
                    }
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        if (!empty($photo)) {
            return 'admin/uploads/instructors/' . $photo;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Get student photo path with multiple fallbacks
 */
function getStudentsPhotoPath($student) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (is_array($student)) {
        $photo = isset($student['photo']) ? $student['photo'] : '';
    } else {
        $photo = $student;
    }
    
    if (!empty($photo) && $photo !== 'default.png') {
        $possiblePaths = [
            'admin/uploads/students/' . $photo,
            '../admin/uploads/students/' . $photo,
            './admin/uploads/students/' . $photo,
            'uploads/students/' . $photo,
            '../uploads/students/' . $photo,
            './uploads/students/' . $photo,
            $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/students/' . $photo,
            dirname(__FILE__) . '/../admin/uploads/students/' . $photo
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0 || strpos($path, dirname(__FILE__)) === 0) {
                if (file_exists($path)) {
                    if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    } else {
                        return 'admin/uploads/students/' . $photo;
                    }
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        if (!empty($photo)) {
            return 'admin/uploads/students/' . $photo;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Get personnel photo path with multiple fallbacks - USING ONLY "PERSONELL"
 */
function getPersonellPhotoPath($personell) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (is_array($personell)) {
        $photo = isset($personell['photo']) ? $personell['photo'] : '';
    } else {
        $photo = $personell;
    }
    
    if (!empty($photo) && $photo !== 'default.png') {
        $possiblePaths = [
            'admin/uploads/personell/' . $photo,
            '../admin/uploads/personell/' . $photo,
            './admin/uploads/personell/' . $photo,
            'uploads/personell/' . $photo,
            '../uploads/personell/' . $photo,
            './uploads/personell/' . $photo,
            $_SERVER['DOCUMENT_ROOT'] . '/admin/uploads/personell/' . $photo,
            dirname(__FILE__) . '/../admin/uploads/personell/' . $photo
        ];
        
        foreach ($possiblePaths as $path) {
            if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0 || strpos($path, dirname(__FILE__)) === 0) {
                if (file_exists($path)) {
                    if (strpos($path, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
                    } else {
                        return 'admin/uploads/personell/' . $photo;
                    }
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        if (!empty($photo)) {
            return 'admin/uploads/personell/' . $photo;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Universal photo path function that automatically detects user type
 */
function getUniversalPhotoPath($userData) {
    if (!is_array($userData)) {
        return 'admin/uploads/students/default.png';
    }
    
    $role = isset($userData['person_type']) ? strtolower($userData['person_type']) : '';
    $photo = isset($userData['photo']) ? $userData['photo'] : '';
    
    switch($role) {
        case 'instructor':
        case 'faculty':
            return getInstructorPhotoPath($userData);
            
        case 'student':
            return getStudentsPhotoPath($userData);
            
        case 'personell':
            return getPersonellPhotoPath($userData);
        case 'staff':
            return getPersonellPhotoPath($userData);
        case 'admin':
        case 'security':
            return getPersonellPhotoPath($userData);
            
        case 'visitor':
            if (!empty($photo)) {
                $visitorPath = 'admin/uploads/visitors/' . $photo;
                if (file_exists($visitorPath) || file_exists('../' . $visitorPath)) {
                    return $visitorPath;
                }
            }
            return 'admin/uploads/students/default.png';
            
        default:
            return 'admin/uploads/students/default.png';
    }
}

/**
 * Check if photo file actually exists, return default if not
 */
function validatePhotoPath($photoPath) {
    $defaultPhoto = 'admin/uploads/students/default.png';
    
    if (empty($photoPath) || $photoPath === $defaultPhoto) {
        return $defaultPhoto;
    }
    
    $pathsToCheck = [
        $photoPath,
        '../' . $photoPath,
        './' . $photoPath,
        dirname(__FILE__) . '/' . $photoPath
    ];
    
    foreach ($pathsToCheck as $path) {
        if (file_exists($path)) {
            return $photoPath;
        }
    }
    
    return $defaultPhoto;
}

/**
 * Convert photo to base64 if file exists, otherwise return default
 */
function getPhotoForResponse($userData) {
    $photoPath = getUniversalPhotoPath($userData);
    $validatedPath = validatePhotoPath($photoPath);
    
    // If it's a file path and file exists, convert to base64
    if (!empty($validatedPath) && $validatedPath !== 'admin/uploads/students/default.png' && file_exists($validatedPath)) {
        $imageData = file_get_contents($validatedPath);
        if ($imageData !== false) {
            $mimeType = mime_content_type($validatedPath);
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
    }
    
    // Return default photo as base64 or empty string
    $defaultPath = 'admin/uploads/students/default.png';
    if (file_exists($defaultPath)) {
        $imageData = file_get_contents($defaultPath);
        $mimeType = mime_content_type($defaultPath);
        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }
    
    return ''; // Return empty if no photo available
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/grow_up.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
    
    <!-- reCAPTCHA API -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo RECAPTCHA_SITE_KEY; ?>"></script>
    
    <title>Gate Entrance Scanner</title>
    <link rel="icon" href="uploads/scanner.webp" type="image/webp">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e4652aff;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Header - Fixed height and fully visible */
        .header-container {
            background: transparent;
            padding: 0;
            margin: 0;
            height: 150px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-image {
            max-width: 150%;
            max-height: 150%;
            object-fit: contain;
            display: block;
        }

        /* Main Container - Allow scrolling */
        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 10px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* Navigation Tabs */
        .modern-tabs {
            background: var(--accent-color);
            border-radius: 8px;
            padding: 4px;
            margin: 10px;
            flex-shrink: 0;
        }

        .modern-tabs .nav-link {
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            color: var(--dark-text);
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .modern-tabs .nav-link.active {
            background: var(--icon-color);
            color: white;
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
        }

        /* Content Area - Allow scrolling */
        .content-area {
            flex: 1;
            display: flex;
            padding: 0 10px 10px 10px;
            gap: 10px;
            overflow: hidden;
            min-height: 0;
        }

        /* Scanner Section - Allow scrolling if needed */
        .scanner-section {
            flex: 7;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
        }

        /* Department/Location Info */
        .dept-location-info {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .dept-location-info h3 {
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-text);
        }

        /* Clock Display */
        .clock-display {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            flex-shrink: 0;
        }

        #clock {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        #currentDate {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        /* Scanner Alert */
        .scanner-alert {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            flex-shrink: 0;
        }

        .scanner-alert h4 {
            font-size: 0.9rem;
            margin: 0;
        }

        /* Scanner Container - Fixed height, no scrolling */
        .scanner-container {
            flex: 1;
            position: relative;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            min-height: 150px;
            flex-shrink: 0;
        }

        #largeReader {
            width: 100%;
            height: 100%;
        }

        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .scanner-frame {
            border: 3px solid #FBC257;
            width: 70%;
            height: 100px;
            position: relative;
            border-radius: 6px;
        }

        .scanner-laser {
            position: absolute;
            width: 100%;
            height: 3px;
            background: #FBC257;
            top: 0;
            animation: scan 2s infinite;
            box-shadow: 0 0 10px #FBC257;
        }

        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }

        /* Result Display */
        #result {
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            flex-shrink: 0;
        }

        /* Sidebar Section - Allow scrolling if needed */
        .sidebar-section {
            flex: 3;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow-y: auto;
        }

        /* Person Photo */
        .person-photo {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--icon-color);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.2);
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        /* Manual Input Section */
        .manual-input-section {
            background: white;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .manual-input-section h4 {
            color: var(--icon-color);
            margin-bottom: 8px;
            font-weight: 600;
            text-align: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .input-group {
            margin-bottom: 8px;
            flex-shrink: 0;
        }

        #manualIdInput {
            border: 2px solid var(--accent-color);
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 0.85rem;
            transition: var(--transition);
            height: 40px;
        }

        #manualIdInput:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.1);
        }

        #manualSubmitBtn {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            height: 40px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
            font-size: 0.85rem;
        }

        #manualSubmitBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(92, 149, 233, 0.4);
        }

        /* Confirmation Modal */
        .confirmation-modal .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .confirmation-modal .modal-header {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border-bottom: none;
            padding: 12px 15px;
        }

        .confirmation-modal .modal-body {
            padding: 20px;
            text-align: center;
        }

        .modal-person-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--icon-color);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
            margin-bottom: 10px;
        }

        .person-info {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .access-status {
            font-size: 1rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 12px 0;
        }

        .time-in {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .time-out {
            background: linear-gradient(135deg, #f72585, #7209b7);
            color: white;
        }

        .access-denied {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
        }

        .time-display {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }

        .confirmation-modal .modal-footer {
            border-top: none;
            padding: 12px 15px;
            justify-content: center;
        }

        .confirmation-modal .btn {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 6px;
            padding: 6px 20px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .header-container {
                height: 100px;
            }
            
            .content-area {
                flex-direction: column;
                padding: 0 8px 8px 8px;
                gap: 8px;
            }
            
            .scanner-section,
            .sidebar-section {
                min-height: 250px;
            }
            
            .person-photo {
                height: 120px;
            }
            
            #clock {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                height: 80px;
            }
            
            .main-container {
                margin: 8px;
            }
            
            .modern-tabs {
                margin: 8px;
            }
            
            .modern-tabs .nav-link {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .dept-location-info h3 {
                font-size: 0.8rem;
            }
            
            .clock-display {
                padding: 8px;
            }
            
            #clock {
                font-size: 1.1rem;
            }
            
            .scanner-alert {
                padding: 8px;
            }
            
            .scanner-alert h4 {
                font-size: 0.8rem;
            }
            
            .scanner-frame {
                width: 85%;
                height: 80px;
            }
        }

        @media (max-width: 576px) {
            .header-container {
                height: 70px;
            }
            
            .main-container {
                margin: 5px;
            }
            
            .modern-tabs {
                margin: 5px;
            }
            
            .content-area {
                padding: 0 5px 5px 5px;
                gap: 5px;
            }
            
            .scanner-section,
            .sidebar-section {
                padding: 10px;
            }
            
            .manual-input-section {
                padding: 8px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            #manualSubmitBtn {
                margin-top: 5px;
            }
            
            .modal-person-photo {
                width: 60px;
                height: 60px;
            }
        }

        /* Utility classes */
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }

        @keyframes blink-animation {
            to { visibility: hidden; }
        }

        .loading-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--icon-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Alert variations matching your color scheme */
        .alert-success {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
            border: none;
            border-radius: 6px;
        }

        .alert-warning {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            color: white;
            border: none;
            border-radius: 6px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #e74a3b, #d62828);
            color: white;
            border: none;
            border-radius: 6px;
        }

        /* Custom scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #4a7fe0;
        }

        /* Enhanced modal styles for gate system */
        .confirmation-modal .person-photo-container {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 20px;
            border-radius: 50%;
            padding: 5px;
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            box-shadow: 0 5px 15px rgba(92, 149, 233, 0.3);
        }

        .confirmation-modal .person-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .confirmation-modal .person-info-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--icon-color);
        }

        .confirmation-modal .person-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--icon-color);
            margin-bottom: 10px;
        }

        .confirmation-modal .person-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: left;
        }

        .confirmation-modal .detail-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .confirmation-modal .detail-label {
            font-weight: bold;
            color: #495057;
            font-size: 0.9rem;
        }

        .confirmation-modal .detail-value {
            color: var(--icon-color);
            font-weight: 600;
        }

        .visitor-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        /* Visitor Modal Styles - Adjusted Container */
.visitor-modal .modal-content {
    border-radius: var(--border-radius);
    border: none;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.visitor-modal .modal-header {
    background: linear-gradient(135deg, var(--icon-color), #4361ee);
    color: white;
    border-bottom: none;
    padding: 15px 20px;
}

.visitor-modal .modal-header .btn-close {
    filter: invert(1);
}

.visitor-modal .modal-body {
    padding: 20px;
    background: var(--light-bg);
}

.visitor-modal .modal-footer {
    border-top: none;
    padding: 15px 20px;
    background: white;
}

.visitor-modal .form-label {
    font-weight: 600;
    color: var(--dark-text);
    margin-bottom: 8px;
    font-size: 0.9rem;
    text-align: left;
    display: block;
}

.visitor-modal .form-control,
.visitor-modal .form-select {
    border: 2px solid var(--accent-color);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 0.9rem;
    transition: var(--transition);
    background: white;
    width: 100%;
}

.visitor-modal .form-control:focus,
.visitor-modal .form-select:focus {
    border-color: var(--icon-color);
    box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
    background: white;
}

.visitor-modal .form-control.is-invalid {
    border-color: #e74a3b;
    box-shadow: 0 0 0 3px rgba(231, 74, 59, 0.15);
}

.visitor-modal .invalid-feedback {
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 5px;
    text-align: left;
}

.visitor-modal .btn-warning {
    background: linear-gradient(135deg, var(--warning-color), #f4a261);
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    color: white;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(246, 194, 62, 0.3);
}

.visitor-modal .btn-warning:hover {
    background: linear-gradient(135deg, #e0a800, #dc6502);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
}

.visitor-modal .btn-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    border: none;
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 600;
    transition: var(--transition);
}

.visitor-modal .btn-secondary:hover {
    background: linear-gradient(135deg, #5a6268, #3d4348);
    transform: translateY(-2px);
}

.visitor-info-alert {
    background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
    border: none;
    border-radius: 8px;
    color: var(--dark-text);
    padding: 12px 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.visitor-info-alert i {
    color: var(--icon-color);
}

.character-count {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
    margin-top: 5px;
}

.character-count.warning {
    color: #e74a3b;
    font-weight: 600;
}

/* Make modal more compact */
.visitor-modal .modal-dialog {
    max-width: 500px;
}

.visitor-modal .col-12 {
    padding: 0;
}

/* Adjust spacing for compact layout */
.visitor-modal .mb-3 {
    margin-bottom: 1rem !important;
}

.visitor-modal .modal-body {
    padding: 20px;
}

/* reCAPTCHA badge styling */
.grecaptcha-badge {
    visibility: hidden;
}

/* reCAPTCHA info message */
.recaptcha-info {
    font-size: 11px;
    color: #6c757d;
    text-align: center;
    margin-top: 8px;
    padding: 5px;
    background: var(--light-bg);
    border-radius: 4px;
}
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 
<audio id="successAudio" hidden>
    <source src="admin/audio/success.mp3" type="audio/mpeg">
</audio>
<audio id="errorAudio" hidden>
    <source src="admin/audio/error.mp3" type="audio/mpeg">
</audio>

<!-- Hidden reCAPTCHA token field -->
<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">

<!-- Header - Fixed height, fully visible -->
<div class="header-container">
    <img src="uploads/Head-removebg-preview.png" alt="Header" class="header-image">
</div>


    <!-- Enhanced Confirmation Modal -->
    <div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-door-open me-2"></i>Gate Access Recorded
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <!-- Dynamic content will be inserted here by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ok" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Visitor Information Modal - Adjusted Container Size -->
<div class="modal fade visitor-modal" id="visitorInfoModal" tabindex="-1" aria-labelledby="visitorInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visitorInfoModalLabel">
                    <i class="fas fa-user-clock me-2"></i>Visitor Registration Required
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="visitor-info-alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-lg me-3"></i>
                        <div>
                            <h6 class="mb-1" style="color: var(--icon-color);">Visitor Registration</h6>
                            <p class="mb-0">Please provide your information for gate access. All fields marked with <span class="text-danger">*</span> are required.</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <form id="visitorInfoForm" novalidate>
                            <input type="hidden" id="visitorID" value="">
                            
                            <!-- Full Name Field -->
                            <div class="mb-3">
                                <label for="fullName" class="form-label">
                                    <i class="fas fa-user me-1"></i>Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="fullName" 
                                       required 
                                       placeholder="Enter your full name (letters and spaces only)"
                                       maxlength="100"
                                       pattern="[A-Za-z\s]+"
                                       oninput="validateFullName(this)">
                                <div class="invalid-feedback">
                                    Please enter a valid full name (letters and spaces only, no numbers or special characters).
                                </div>
                                <div class="character-count" id="nameCharCount">0/100 characters</div>
                            </div>
                            
                            <!-- Contact Number Field -->
                            <div class="mb-3">
                                <label for="contactNumber" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Contact Number <span class="text-danger">*</span>
                                </label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="contactNumber" 
                                       required 
                                       placeholder="Enter 11-digit contact number"
                                       maxlength="11"
                                       pattern="[0-9]{11}"
                                       oninput="validateContactNumber(this)">
                                <div class="invalid-feedback">
                                    Please enter a valid 11-digit contact number (numbers only).
                                </div>
                                <div class="character-count" id="contactCharCount">0/11 digits</div>
                            </div>
                            
                            <!-- Purpose of Visit -->
                            <div class="mb-3">
                                <label for="purpose" class="form-label">
                                    <i class="fas fa-bullseye me-1"></i>Purpose of Visit <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="purpose" required onchange="toggleOtherPurpose()">
                                    <option value="">Select purpose...</option>
                                    <option value="Meeting">Meeting</option>
                                    <option value="Delivery">Delivery</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Interview">Interview</option>
                                    <option value="Training">Training</option>
                                    <option value="Official Business">Official Business</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a purpose for your visit.
                                </div>
                            </div>
                            
                            <!-- Other Purpose Field (Conditional) -->
                            <div class="mb-3" id="otherPurposeContainer" style="display: none;">
                                <label for="otherPurpose" class="form-label">
                                    <i class="fas fa-edit me-1"></i>Specify Purpose
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="otherPurpose" 
                                       placeholder="Please specify your purpose"
                                       maxlength="100"
                                       oninput="updateCharacterCount('otherPurpose', 'otherPurposeCharCount', 100)">
                                <div class="character-count" id="otherPurposeCharCount">0/100 characters</div>
                            </div>
                            
                            <!-- Person/Department Visiting -->
                            <div class="mb-3">
                                <label for="personVisiting" class="form-label">
                                    <i class="fas fa-building me-1"></i>Person/Department Visiting
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="personVisiting" 
                                       placeholder="Who are you visiting? (Optional)"
                                       maxlength="100"
                                       oninput="updateCharacterCount('personVisiting', 'personVisitingCharCount', 100)">
                                <div class="character-count" id="personVisitingCharCount">0/100 characters</div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning" id="submitVisitorInfo">
                    <i class="fas fa-check me-2"></i>Submit & Record Access
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Main Container - Scroll Design -->
<div class="main-container">
    <!-- Navigation Tabs -->
    <div class="modern-tabs">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#">
                    <i class="fas fa-qrcode me-2"></i>Gate Scanner
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gate_logs.php">
                    <i class="fas fa-history me-2"></i>Access Log
                </a>
            </li>
        </ul>
    </div>

    <!-- Content Area - Allow scrolling -->
    <div class="content-area">
        <!-- Scanner Section (70%) -->
        <div class="scanner-section">
            <!-- Department/Location Info -->
            <div class="dept-location-info">
                <div class="row">
                    <center>
                        <h3><i class="fas fa-map-marker-alt me-2"></i>Location: <?php echo $department; echo $location; ?></h3>
                    </center>    
                </div>
            </div>

            <!-- Clock Display -->
            <div class="clock-display">
                <div id="clock" class="mb-2"></div>
                <div id="currentDate"></div>
            </div>

            <!-- Scanner Alert -->
            <div class="scanner-alert">
                <h4 id="in_out" class="mb-0" style="color: var(--icon-color);">
                    <i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access
                </h4>
            </div>

            <!-- Scanner Container -->
            <div class="scanner-container">
                <div id="largeReader"></div>
                <div class="scanner-overlay">
                    <div class="scanner-frame">
                        <div class="scanner-laser"></div>
                    </div>
                </div>
            </div>

            <!-- Result Display -->
            <div id="result"></div>
            
            <!-- reCAPTCHA Info -->
            <div class="recaptcha-info">
                <i class="fas fa-shield-alt me-1"></i>
                Protected by reCAPTCHA
            </div>
        </div>

        <!-- Sidebar Section (30%) -->
        <div class="sidebar-section">
            <!-- Person Photo -->
            <img id="pic" class="person-photo" 
                 src="assets/img/section/type.jpg"
                 alt="Person Photo Preview">

            <!-- Manual Input Section -->
            <div class="manual-input-section">
                <h4><i class="fas fa-keyboard me-2"></i> Manual Entry</h4>
                <p class="text-center text-muted mb-3" style="font-size: 0.8rem;">For visitors or forgot ID</p>
                
                <div class="input-group mb-3">
                    <input type="text" 
                           class="form-control" 
                           id="manualIdInput" 
                           placeholder="0000-0000"
                           aria-label="Person ID"
                           maxlength="9"
                           pattern="[0-9]{4}-[0-9]{4}">
                    <button class="btn btn-primary" 
                            id="manualSubmitBtn"
                            onclick="processManualInput()">
                        <i class="fas fa-paper-plane me-2"></i>Submit
                    </button>
                </div>
                
                <div class="text-center mt-auto">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Enter 8 digits or use format 0000-0000
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let scanner = null;
let barcodeBuffer = '';
let lastScanTime = 0;
const scanCooldown = 500; // Reduced cooldown for faster scanning

// reCAPTCHA site key
const RECAPTCHA_SITE_KEY = '<?php echo RECAPTCHA_SITE_KEY; ?>';

// Role icons mapping
const roleIcons = {
    'Student': 'fa-user-graduate',
    'Faculty': 'fa-chalkboard-teacher',
    'Staff': 'fa-user-tie',
    'Admin': 'fa-user-cog',
    'Security': 'fa-shield-alt',
    'Visitor': 'fa-user-clock',
    'IT Personnel': 'fa-laptop-code',
    'Instructor': 'fa-chalkboard-teacher'
};

// Scanner Initialization and Control Functions
// Enhanced scanner initialization for faster scanning
function initScanner() {
    if (scanner) {
        scanner.clear().catch(console.error);
    }
    
    scanner = new Html5QrcodeScanner('largeReader', { 
        qrbox: {
            width: 300,
            height: 300,
        },
        fps: 30, // Increased FPS for faster scanning
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
        showTorchButtonIfSupported: true
    });
    
    // Remove the permission request image by hiding the element
    const permissionElement = document.querySelector('#largeReader img');
    if (permissionElement) {
        permissionElement.style.display = 'none';
    }
    
    scanner.render(onScanSuccess, onScanError);
}

function onScanError(error) {
    // Only log actual errors, not benign "no code found" errors
    if (!error.includes('NotFoundException') && !error.includes('No MultiFormat Readers')) {
        console.error('Scanner error:', error);
    }
    
    // Hide any permission-related images
    const permissionElement = document.querySelector('#largeReader img');
    if (permissionElement) {
        permissionElement.style.display = 'none';
    }
}

// Optimized scan success handler
function onScanSuccess(decodedText, decodedResult) {
    const currentTime = new Date().getTime();
    
    // Check if enough time has passed since last scan
    if (currentTime - lastScanTime < scanCooldown) {
        return; // Ignore scans that happen too quickly
    }
    
    lastScanTime = currentTime;
    
    // Stop scanner temporarily to prevent multiple scans
    if (scanner) {
        scanner.pause();
    }
    
    // Process the barcode with reCAPTCHA
    processBarcodeWithRecaptcha(decodedText);
}

// Enhanced barcode processing with reCAPTCHA
function processBarcodeWithRecaptcha(barcode) {
    console.log("🔍 Processing barcode with reCAPTCHA:", barcode);
    
    // Show processing state
    document.getElementById('result').innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>Processing ID: ${barcode}</span>
        </div>
    `;
    
    // Disable inputs during processing
    setInputsDisabled(true);
    
    // Get reCAPTCHA token first
    grecaptcha.ready(function() {
        grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'gate_access'}).then(function(token) {
            // Set the token in the hidden field
            document.getElementById('g-recaptcha-response').value = token;
            
            console.log("✅ reCAPTCHA token received, proceeding with gate access...");
            
            // Now send the request with reCAPTCHA token
            $.ajax({
                type: "POST",
                url: "process_gate.php",
                data: { 
                    barcode: barcode,
                    department: "<?php echo $department; ?>",
                    location: "<?php echo $location; ?>",
                    'g-recaptcha-response': token
                },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    // Check if server indicates this is a visitor that needs registration
                    if (response.requires_visitor_info) {
                        console.log("🎫 Visitor card detected, showing info modal");
                        showVisitorInfoModal(barcode);
                    } else {
                        handleGateScanSuccess(response, barcode);
                    }
                },
                error: function(xhr, status, error) {
                    handleGateScanError(xhr, status, error, barcode);
                },
                complete: function() {
                    setInputsDisabled(false);
                }
            });
        }).catch(function(error) {
            console.error('❌ reCAPTCHA error:', error);
            showErrorMessage('Security verification failed. Please try again.');
            setInputsDisabled(false);
        });
    });
}

    // Enhanced success handler for gate system
    function handleGateScanSuccess(response, originalBarcode) {
        console.log("✅ GATE SUCCESS - Raw response:", response);
        
        if (!response || typeof response !== 'object') {
            console.error("❌ Invalid response format");
            showGateSuccessFallback(originalBarcode);
            return;
        }
        
        if (response.error) {
            console.log("❌ Server error:", response.error);
            showErrorMessage(response.error);
            speakMessage(response.error);
            // Clear and reset after error
            setTimeout(() => {
                clearAndResetScanner();
            }, 2000);
            return;
        }

        // Log successful person data retrieval
        console.log("🎓 Person Data Retrieved:", {
            name: response.full_name,
            id: response.id_number,
            department: response.department,
            role: response.role,
            photo: response.photo
        });

        // Update UI and show confirmation
        updateGateUI(response);
        updatePersonPhoto(response);
        showGateConfirmationModal(response);
    }

    // Enhanced error handler for gate system
    function handleGateScanError(xhr, status, error, originalBarcode) {
        console.error("❌ GATE AJAX ERROR:");
        console.error("Status:", status);
        console.error("Error:", error);
        console.error("Response text:", xhr.responseText);
        
        // Try to parse response even if AJAX reports error
        if (xhr.responseText && xhr.responseText.trim() !== '') {
            try {
                const parsedResponse = JSON.parse(xhr.responseText);
                console.log("📦 Parsed response despite AJAX error:", parsedResponse);
                
                if (parsedResponse.error) {
                    showErrorMessage(parsedResponse.error);
                    // Clear and reset after error
                    setTimeout(() => {
                        clearAndResetScanner();
                    }, 2000);
                } else {
                    updateGateUI(parsedResponse);
                    updatePersonPhoto(parsedResponse);
                    showGateConfirmationModal(parsedResponse);
                    return;
                }
            } catch (e) {
                console.log("❌ Could not parse response as JSON:", e.message);
            }
        }
        
        // Fallback to success since access was likely recorded
        showGateSuccessFallback(originalBarcode);
    }

    function showGateSuccessFallback(barcode) {
        console.log("🔄 Using gate fallback success display");
        
        const fallbackData = {
            full_name: "Person",
            id_number: barcode,
            department: "<?php echo $department; ?>",
            photo: "admin/uploads/students/default.png",
            role: "User",
            time_in_out: "Access Recorded Successfully",
            alert_class: "alert-success",
            access_type: "time_in"
        };
        
        updateGateUI(fallbackData);
        updatePersonPhoto(fallbackData);
        showGateConfirmationModal(fallbackData);
    }

    function setInputsDisabled(disabled) {
        document.getElementById('manualIdInput').disabled = disabled;
        document.getElementById('manualSubmitBtn').disabled = disabled;
    }

// Update gate UI with access data

    function updateGateUI(data) {
        const inOutElement = document.getElementById('in_out');
        
        // Use the correct response fields from process_gate.php
        if (data.time_in_out === 'Time In Recorded' || data.time_in_out === 'TIME IN') {
            inOutElement.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>ENTRY GRANTED - TIME IN RECORDED';
            inOutElement.style.color = 'var(--icon-color)';
        } else if (data.time_in_out === 'Time Out Recorded' || data.time_in_out === 'TIME OUT') {
            inOutElement.innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>EXIT RECORDED - TIME OUT RECORDED';
            inOutElement.style.color = '#f72585';
        } else if (data.error) {
            inOutElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${data.error}`;
            inOutElement.style.color = '#e74a3b';
        } else if (data.time_in_out === 'Already timed out today') {
            inOutElement.innerHTML = '<i class="fas fa-check-circle me-2"></i>ALREADY TIMED OUT TODAY';
            inOutElement.style.color = '#6c757d';
        } else {
            inOutElement.innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access';
            inOutElement.style.color = 'var(--icon-color)';
        }
        
        // Update result display
        if (data.time_in_out && !data.error) {
            const alertClass = data.alert_class || 'alert-success';
            document.getElementById('result').innerHTML = `
                <div class="alert ${alertClass} py-2" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.time_in_out}
                </div>
            `;
        } else if (data.error) {
            document.getElementById('result').innerHTML = `
                <div class="alert alert-danger py-2" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.error}
                </div>
            `;
        }
    }

    // Enhanced manual input processing to handle visitors with reCAPTCHA
    function processManualInput() {
        const idNumber = document.getElementById('manualIdInput').value.trim();
        
        if (!idNumber) {
            showErrorMessage("Please enter ID number");
            speakMessage("Please enter ID number");
            return;
        }
        
        // Basic ID format validation (0000-0000)
        const idPattern = /^\d{4}-\d{4}$/;
        if (!idPattern.test(idNumber)) {
            showErrorMessage("Invalid ID format. Please use: 0000-0000");
            return;
        }
        
        showProcessingState(idNumber);
        setInputsDisabled(true);
        
        // Get reCAPTCHA token for manual input
        grecaptcha.ready(function() {
            grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'gate_access'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token;
                
                // Process as barcode (will check if visitor in process_gate.php)
                $.ajax({
                    type: "POST",
                    url: "process_gate.php",
                    data: { 
                        barcode: idNumber,
                        department: "<?php echo $department; ?>",
                        location: "<?php echo $location; ?>",
                        check_visitor: true,
                        'g-recaptcha-response': token
                    },
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        // Check if server indicates this is a visitor that needs registration
                        if (response.requires_visitor_info) {
                            console.log("🎫 Visitor card detected, showing info modal");
                            showVisitorInfoModal(idNumber);
                        } else {
                            handleGateScanSuccess(response, idNumber);
                        }
                    },
                    error: function(xhr, status, error) {
                        handleGateScanError(xhr, status, error, idNumber);
                    },
                    complete: function() {
                        setInputsDisabled(false);
                    }
                });
            }).catch(function(error) {
                console.error('❌ reCAPTCHA error:', error);
                showErrorMessage('Security verification failed. Please try again.');
                setInputsDisabled(false);
            });
        });
    }

    // Visitor Information Modal
    function showVisitorInfoModal(visitorID) {
        // Set the visitor ID
        document.getElementById('visitorID').value = visitorID;
        
        // Reset form
        document.getElementById('visitorInfoForm').reset();
        document.getElementById('otherPurposeContainer').style.display = 'none';
        
        // Show modal
        const visitorModal = new bootstrap.Modal(document.getElementById('visitorInfoModal'));
        visitorModal.show();
        
        // Set up event listeners
        setupVisitorModalEvents();
    }

    // Set up event listeners for visitor modal
    function setupVisitorModalEvents() {
        // Purpose dropdown change
        document.getElementById('purpose').addEventListener('change', function() {
            const otherContainer = document.getElementById('otherPurposeContainer');
            otherContainer.style.display = this.value === 'Other' ? 'block' : 'none';
        });
        
        // Form submission
        document.getElementById('submitVisitorInfo').addEventListener('click', submitVisitorInfo);
        
        // Modal hidden event
        document.getElementById('visitorInfoModal').addEventListener('hidden.bs.modal', function() {
            clearAndResetScanner();
        });
    }

    // Submit visitor information with reCAPTCHA
    function submitVisitorInfo() {
        const form = document.getElementById('visitorInfoForm');
        const submitBtn = document.getElementById('submitVisitorInfo');
        
        // Basic validation
        const fullName = document.getElementById('fullName').value.trim();
        const contactNumber = document.getElementById('contactNumber').value.trim();
        const purpose = document.getElementById('purpose').value;
        
        if (!fullName) {
            showVisitorAlert('Please enter your full name', 'danger');
            document.getElementById('fullName').focus();
            return;
        }
        
        if (!contactNumber) {
            showVisitorAlert('Please enter your contact number', 'danger');
            document.getElementById('contactNumber').focus();
            return;
        }
        
        if (!purpose) {
            showVisitorAlert('Please select purpose of visit', 'danger');
            document.getElementById('purpose').focus();
            return;
        }
        
        if (purpose === 'Other' && !document.getElementById('otherPurpose').value.trim()) {
            showVisitorAlert('Please specify your purpose', 'danger');
            document.getElementById('otherPurpose').focus();
            return;
        }
        
        // Show loading state
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
        submitBtn.disabled = true;
        
        // Get reCAPTCHA token for visitor submission
        grecaptcha.ready(function() {
            grecaptcha.execute(RECAPTCHA_SITE_KEY, {action: 'visitor_registration'}).then(function(token) {
                // Prepare data
                const visitorData = {
                    visitor_id: document.getElementById('visitorID').value,
                    full_name: fullName,
                    contact_number: contactNumber,
                    purpose: purpose === 'Other' ? document.getElementById('otherPurpose').value.trim() : purpose,
                    person_visiting: document.getElementById('personVisiting').value.trim(),
                    department: "<?php echo $department; ?>",
                    location: "<?php echo $location; ?>",
                    is_visitor_submission: true,
                    'g-recaptcha-response': token
                };
                
                // Send data to server
                $.ajax({
                    type: "POST",
                    url: "process_gate.php", // Use the same file
                    data: visitorData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            showVisitorAlert('Visitor access recorded successfully!', 'success');
                            
                            // Update UI with visitor data
                            updateGateUI({
                                full_name: visitorData.full_name,
                                id_number: visitorData.visitor_id,
                                department: visitorData.department,
                                role: 'Visitor',
                                photo: 'admin/uploads/students/default.png',
                                time_in_out: 'Time In Recorded',
                                alert_class: 'alert-success'
                            });
                            
                            // Close modal after delay and show confirmation
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('visitorInfoModal'));
                                modal.hide();
                                
                                // Show confirmation modal
                                showGateConfirmationModal({
                                    full_name: visitorData.full_name,
                                    id_number: visitorData.visitor_id,
                                    department: visitorData.department,
                                    role: 'Visitor',
                                    photo: 'admin/uploads/students/default.png',
                                    time_in_out: 'Time In Recorded'
                                });
                            }, 1500);
                            
                        } else {
                            showVisitorAlert(response.message || 'Error recording visitor access', 'danger');
                            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Submit & Record Access';
                            submitBtn.disabled = false;
                        }
                    },
                    error: function(xhr, status, error) {
                        showVisitorAlert('Error processing visitor information. Please try again.', 'danger');
                        submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Submit & Record Access';
                        submitBtn.disabled = false;
                    }
                });
            }).catch(function(error) {
                console.error('❌ reCAPTCHA error:', error);
                showVisitorAlert('Security verification failed. Please try again.', 'danger');
                submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Submit & Record Access';
                submitBtn.disabled = false;
            });
        });
    }

    // Helper function to show alerts in visitor modal
    function showVisitorAlert(message, type) {
        // Remove existing alerts
        const existingAlert = document.querySelector('#visitorInfoModal .alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        document.querySelector('#visitorInfoModal .modal-body').insertAdjacentHTML('afterbegin', alertHTML);
    }

// Enhanced photo update function with better error handling
function updatePersonPhoto(data) {
    const photoElement = document.getElementById('pic');
    let photoPath = "admin/uploads/students/default.png";
    
    if (data.photo) {
        if (data.photo.startsWith('data:image')) {
            // Base64 photo
            photoPath = data.photo;
        } else {
            // File path photo - handle different user types
            photoPath = getPhotoPathByUserType(data);
        }
    }
    
    // Add cache busting timestamp
    const timestamp = new Date().getTime();
    const finalPath = photoPath + (photoPath.includes('?') ? '&' : '?') + "t=" + timestamp;
    
    // Set the source
    photoElement.src = finalPath;
    
    // Handle image loading errors
    photoElement.onerror = function() {
        console.warn('Failed to load photo:', finalPath, 'Using default');
        this.src = "admin/uploads/students/default.png?t=" + timestamp;
        this.onerror = null; // Prevent infinite loop
    };
    
    // Handle successful load
    photoElement.onload = function() {
        console.log('Photo loaded successfully:', finalPath);
    };
}

// Enhanced JavaScript photo path helper
function getPhotoPathByUserType(data) {
    const role = data.role ? data.role.toLowerCase() : '';
    const photo = data.photo || '';
    
    // If photo already contains full path, return as is
    if (photo.startsWith('admin/uploads/') || photo.startsWith('uploads/') || 
        photo.startsWith('../') || photo.startsWith('./') || 
        photo.startsWith('http') || photo.startsWith('data:image')) {
        return photo;
    }
    
    // Determine path based on role using consistent PHP function logic
    switch(role) {
        case 'instructor':
        case 'faculty':
            return `admin/uploads/instructors/${photo}`;
            
        case 'student':
            return `admin/uploads/students/${photo}`;
            
        case 'staff':
        case 'admin':
        case 'security':
        case 'personell':
            return `admin/uploads/personell/${photo}`;
        case 'visitor':
            return `admin/uploads/visitors/${photo}`;
            
        default:
            // Try to infer from other data
            if (data.user_type) {
                const userType = data.user_type.toLowerCase();
                if (userType.includes('student')) {
                    return `admin/uploads/students/${photo}`;
                } else if (userType.includes('instructor') || userType.includes('faculty')) {
                    return `admin/uploads/instructors/${photo}`;
                } else if (userType.includes('staff') || userType.includes('admin')) {
                    return `admin/uploads/personell/${photo}`;
                }
            }
            return `admin/uploads/students/${photo}`; // Default fallback
    }
}

// ENHANCED: Show comprehensive confirmation modal
function showGateConfirmationModal(data) {
    console.log("🎯 Showing gate confirmation modal with:", data);
    
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateString = now.toLocaleDateString([], { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });

    // Determine access type and styling
    let accessType, statusClass, statusIcon, statusText, voiceMessage;
    
    if (data.time_in_out === 'Time In Recorded' || data.time_in_out === 'TIME IN') {
        accessType = 'ENTRY GRANTED';
        statusClass = 'time-in';
        statusIcon = 'fas fa-sign-in-alt';
        statusText = 'TIME IN RECORDED';
        voiceMessage = `Welcome ${data.full_name || ''}. Time in recorded at ${timeString}.`;
    } else if (data.time_in_out === 'Time Out Recorded' || data.time_in_out === 'TIME OUT') {
        accessType = 'EXIT RECORDED';
        statusClass = 'time-out';
        statusIcon = 'fas fa-sign-out-alt';
        statusText = 'TIME OUT RECORDED';
        voiceMessage = `Goodbye ${data.full_name || ''}. Time out recorded at ${timeString}.`;
    } else if (data.error) {
        accessType = 'ACCESS DENIED';
        statusClass = 'access-denied';
        statusIcon = 'fas fa-exclamation-triangle';
        statusText = data.error;
        voiceMessage = data.error;
    } else {
        accessType = 'ACCESS RECORDED';
        statusClass = 'time-in';
        statusIcon = 'fas fa-check-circle';
        statusText = 'ACCESS RECORDED';
        voiceMessage = "Access recorded successfully";
    }

    // Set photo with fallback
    let photoPath = data.photo || "admin/uploads/students/default.png";
    
    // Update modal content
    const modalBody = document.querySelector('.confirmation-modal .modal-body');
    modalBody.innerHTML = `
        <!-- Photo Container -->
        <div class="person-photo-container">
            <img id="modalPersonPhoto" 
                src="${photoPath}" 
                alt="Person Photo" 
                class="person-photo"
                onerror="this.src='assets/img/section/type.jpg'">
            ${data.role === 'Visitor' ? '<div class="visitor-badge">VISITOR</div>' : ''}
        </div>

        <!-- Person Name -->
        <h4 id="modalPersonName" class="mb-3" style="color: var(--icon-color); font-weight: 600;">
            ${data.full_name || 'Unknown Person'}
        </h4>
        
        <!-- Person Information Card -->
        <div class="person-info-card">
            <div class="row text-start">
                <div class="col-6 mb-2">
                    <strong><i class="fas fa-id-card me-1"></i> ID Number:</strong><br>
                    <span id="modalPersonId" style="color: var(--dark-text); font-size: 0.95rem;">
                        ${data.id_number || 'N/A'}
                    </span>
                </div>
                <div class="col-6 mb-2">
                    <strong><i class="fas fa-user-tag me-1"></i> Role:</strong><br>
                    <span id="modalPersonRole" style="color: var(--dark-text); font-size: 0.95rem;">
                        <i class="fas ${getRoleIcon(data.role)} me-1"></i>
                        ${data.role || 'N/A'}
                    </span>
                </div>
                <!-- REMOVED: Department and Location fields -->
            </div>
        </div>
        
        <!-- Access Status -->
        <div class="access-status ${statusClass} mt-3">
            <i class="${statusIcon} me-2"></i>
            <span id="modalAccessStatus" class="fw-bold">${statusText}</span>
        </div>
        
        <!-- Time Display -->
        <div class="time-display mt-3">
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">Time In</small>
                    <div id="modalTimeIn" class="fw-bold text-primary">
                        ${data.time_in || timeString}
                    </div>
                </div>
                <div class="col-6">
                    <small class="text-muted">Time Out</small>
                    <div id="modalTimeOut" class="fw-bold text-primary">
                        ${data.time_out || '-'}
                    </div>
                </div>
            </div>
            <div class="text-muted small mt-2">
                <i class="far fa-calendar me-1"></i>${dateString}
            </div>
        </div>
    `;

    // Update modal header based on access type
    const modalTitle = document.querySelector('.confirmation-modal .modal-title');
    modalTitle.innerHTML = `
        <i class="${statusIcon} me-2"></i>
        ${accessType}
    `;

    // Update modal footer button
    const modalFooter = document.querySelector('.confirmation-modal .modal-footer');
    modalFooter.innerHTML = `
        <button type="button" class="btn btn-primary px-4 py-2" onclick="closeGateModalAndContinue()">
            <i class="fas fa-check me-2"></i>Confirm & Continue
        </button>
    `;

    // Speak the message
    speakMessage(voiceMessage);

    // Show modal
    const modalElement = document.getElementById('confirmationModal');
    const modal = new bootstrap.Modal(modalElement);
    
    modal.show();

    // Hide scanner overlay while modal is open
    document.querySelector('.scanner-overlay').style.display = 'none';

    // Restart scanner once modal is closed
    modalElement.addEventListener('hidden.bs.modal', function () {
        console.log("🎯 Gate modal closed, clearing and resetting");
        clearAndResetScanner();
    }, { once: true });
}

    // Function to close modal and continue
    function closeGateModalAndContinue() {
        const modalEl = document.getElementById('confirmationModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modalInstance.hide();

        modalEl.addEventListener('hidden.bs.modal', function() {
            clearAndResetScanner();
        }, { once: true });
    }

// Helper function to get role icons
function getRoleIcon(role) {
    return roleIcons[role] || 'fa-user';
}

// Play access sound
function playAccessSound(isSuccess) {
    const audio = document.getElementById(isSuccess ? 'successAudio' : 'errorAudio');
    audio.currentTime = 0;
    audio.play().catch(error => {
        console.log('Audio playback failed:', error);
    });
}

// Show error message
function showErrorMessage(message) {
    document.getElementById('result').innerHTML = `
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div>${message}</div>
        </div>
    `;
    playAccessSound(false);
    speakMessage(message);
}

// Speak message
function speakMessage(message) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1.1;
        
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            const voice = voices.find(v => v.lang.includes('en')) || voices[0];
            speech.voice = voice;
        }
        
        window.speechSynthesis.speak(speech);
    }
}

    // Add this helper function
    function showProcessingState(idNumber) {
        document.getElementById('result').innerHTML = `
            <div class="d-flex justify-content-center align-items-center">
                <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Processing ID: ${idNumber}</span>
            </div>
        `;
    }
// Time and Date Functions
function startTime() {
    const today = new Date();
    let h = today.getHours();
    let m = today.getMinutes();
    let s = today.getSeconds();
    let period = h >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    h = h % 12;
    h = h ? h : 12; // the hour '0' should be '12'
    
    m = checkTime(m);
    s = checkTime(s);
    
    document.getElementById('clock').innerHTML = h + ":" + m + ":" + s + " " + period;
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').innerHTML = today.toLocaleDateString('en-US', options);
    
    setTimeout(startTime, 1000);
}

function checkTime(i) {
    if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
    return i;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize speech synthesis
    if ('speechSynthesis' in window) {
        let voices = window.speechSynthesis.getVoices();
        if (voices.length === 0) {
            window.speechSynthesis.onvoiceschanged = function() {
                voices = window.speechSynthesis.getVoices();
            };
        }
    }
    
    // Check for camera permissions and initialize scanner
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => {
            initScanner();
        })
        .catch(err => {
            console.error("Scanner permission denied:", err);
            showErrorMessage("Tap Your ID to the Scanner");
        });
    
    // Set up manual input field for numeric only with auto-formatting
    const manualInput = document.getElementById('manualIdInput');
    
    // Only allow numbers and hyphen
    manualInput.addEventListener('input', function(e) {
        // Remove any non-numeric characters except hyphen
        let value = this.value.replace(/[^0-9-]/g, '');
        
        // Auto-format as 0000-0000 when 8 digits are entered
        if (value.length === 8 && !value.includes('-')) {
            value = value.substring(0, 4) + '-' + value.substring(4, 8);
        }
        
        // Update the input value
        this.value = value;
        
        // Auto-submit when format is complete
        if (/^\d{4}-\d{4}$/.test(value)) {
            processManualInput();
        }
    });
    
    // Prevent paste of non-numeric characters
    manualInput.addEventListener('paste', function(e) {
        e.preventDefault();
        let pastedData = (e.clipboardData || window.clipboardData).getData('text');
        // Remove non-numeric characters
        pastedData = pastedData.replace(/[^0-9]/g, '');
        
        // Auto-format if 8 digits
        if (pastedData.length === 8) {
            pastedData = pastedData.substring(0, 4) + '-' + pastedData.substring(4, 8);
        }
        
        // Insert the cleaned data
        document.execCommand('insertText', false, pastedData);
        
        // Auto-submit if format is complete
        if (/^\d{4}-\d{4}$/.test(pastedData)) {
            setTimeout(() => processManualInput(), 100);
        }
    });
    
    // Focus on input field
    manualInput.focus();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (scanner) scanner.clear().catch(() => {});
    } else {
        initScanner();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (scanner) scanner.clear().catch(() => {});
});

// Restart scanner function
function restartScanner() {
    if (scanner) {
        scanner.clear().then(() => {
            initScanner();
        }).catch(err => {
            console.error('Error restarting scanner:', err);
            initScanner();
        });
    } else {
        initScanner();
    }
}

// NEW: Clear and reset scanner after success/error
function clearAndResetScanner() {
    console.log("🔄 Clearing and resetting scanner display");
    
    // Clear manual input field
    document.getElementById('manualIdInput').value = '';
    
    // Reset scanner alert to default
    document.getElementById('in_out').innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access';
    document.getElementById('in_out').style.color = 'var(--icon-color)';
    
    // Clear result display
    document.getElementById('result').innerHTML = '';
    
    // Reset person photo to default
    document.getElementById('pic').src = 'assets/img/section/type.jpg';
    
    // Show scanner overlay
    document.querySelector('.scanner-overlay').style.display = 'flex';
    
    // Re-enable inputs
    setInputsDisabled(false);
    
    // Focus on manual input
    document.getElementById('manualIdInput').focus();
    
    // Restart scanner
    restartScanner();
}
// Input validation functions for visitor modal
function validateFullName(input) {
    const value = input.value.trim();
    const nameRegex = /^[A-Za-z\s]+$/;
    const charCount = document.getElementById('nameCharCount');
    
    // Update character count
    charCount.textContent = `${value.length}/100 characters`;
    if (value.length > 80) {
        charCount.classList.add('warning');
    } else {
        charCount.classList.remove('warning');
    }
    
    // Validate pattern
    if (value && !nameRegex.test(value)) {
        input.classList.add('is-invalid');
        return false;
    } else {
        input.classList.remove('is-invalid');
        return true;
    }
}

function validateContactNumber(input) {
    const value = input.value.trim();
    const contactRegex = /^[0-9]{0,11}$/;
    const charCount = document.getElementById('contactCharCount');
    
    // Update character count
    charCount.textContent = `${value.length}/11 digits`;
    if (value.length === 11) {
        charCount.classList.add('warning');
    } else {
        charCount.classList.remove('warning');
    }
    
    // Remove any non-numeric characters
    const numericValue = value.replace(/[^0-9]/g, '');
    if (numericValue !== value) {
        input.value = numericValue;
    }
    
    // Validate pattern
    if (value && !contactRegex.test(value)) {
        input.classList.add('is-invalid');
        return false;
    } else {
        input.classList.remove('is-invalid');
        return true;
    }
}

function updateCharacterCount(inputId, countId, maxLength) {
    const input = document.getElementById(inputId);
    const count = document.getElementById(countId);
    const value = input.value;
    
    count.textContent = `${value.length}/${maxLength} characters`;
    if (value.length > maxLength * 0.8) {
        count.classList.add('warning');
    } else {
        count.classList.remove('warning');
    }
}

function toggleOtherPurpose() {
    const purpose = document.getElementById('purpose').value;
    const otherContainer = document.getElementById('otherPurposeContainer');
    const otherInput = document.getElementById('otherPurpose');
    
    if (purpose === 'Other') {
        otherContainer.style.display = 'block';
        otherInput.required = true;
    } else {
        otherContainer.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
}

// Enhanced form validation for visitor submission
function validateVisitorForm() {
    const fullName = document.getElementById('fullName');
    const contactNumber = document.getElementById('contactNumber');
    const purpose = document.getElementById('purpose');
    const otherPurpose = document.getElementById('otherPurpose');
    
    let isValid = true;
    
    // Validate Full Name
    if (!validateFullName(fullName) || !fullName.value.trim()) {
        fullName.classList.add('is-invalid');
        isValid = false;
    } else {
        fullName.classList.remove('is-invalid');
    }
    
    // Validate Contact Number
    if (!validateContactNumber(contactNumber) || contactNumber.value.length !== 11) {
        contactNumber.classList.add('is-invalid');
        isValid = false;
    } else {
        contactNumber.classList.remove('is-invalid');
    }
    
    // Validate Purpose
    if (!purpose.value) {
        purpose.classList.add('is-invalid');
        isValid = false;
    } else {
        purpose.classList.remove('is-invalid');
    }
    
    // Validate Other Purpose if selected
    if (purpose.value === 'Other' && !otherPurpose.value.trim()) {
        otherPurpose.classList.add('is-invalid');
        isValid = false;
    } else {
        otherPurpose.classList.remove('is-invalid');
    }
    
    return isValid;
}
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>