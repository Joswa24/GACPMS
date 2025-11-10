<?php

// Simple error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
include 'security-headers.php';
include 'connection.php';
// Start session first
session_start();
// Clear any existing output
if (ob_get_level() > 0) {
    ob_clean();
}


// =====================================================================
// MAINTENANCE TASKS - Improved with prepared statements
// =====================================================================
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Update personell_logs with parameterized queries
$sql = "SELECT id, time_in_am, time_in_pm, time_out_am, time_out_pm 
        FROM personell_logs 
        WHERE DATE(date_logged) = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("s", $yesterday);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $updates = [];
    $params = [];
    $types = '';
    
    if (empty($row['time_in_am'])) {
        $updates[] = "time_in_am = ?";
        $params[] = '?';
        $types .= 's';
    }
    if (empty($row['time_in_pm'])) {
        $updates[] = "time_in_pm = ?";
        $params[] = '?';
        $types .= 's';
    }
    if (empty($row['time_out_am'])) {
        $updates[] = "time_out_am = ?";
        $params[] = '?';
        $types .= 's';
    }
    if (empty($row['time_out_pm'])) {
        $updates[] = "time_out_pm = ?";
        $params[] = '?';
        $types .= 's';
    }
    
    if (!empty($updates)) {
        $updateSql = "UPDATE personell_logs SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($updateSql);
        $params[] = $row['id'];
        $types .= 'i';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }
}
 
// Clear output buffer
// =====================================================================
// HELPER FUNCTION - Improved Sanitization
// =====================================================================
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}


// =====================================================================
// LOGIN PROCESSING - Enhanced with Rate Limiting
// =====================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Additional security: Validate CSRF token if implemented
    // Additional security: Check request origin
    $allowed_origins = ['https://yourdomain.com', 'http://localhost']; // Add your domains
    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
        header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'self'));
    }
    
    $department = sanitizeInput($_POST['roomdpt'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $password = $_POST['Ppassword'] ?? ''; // Don't sanitize passwords
    $id_number = sanitizeInput($_POST['Pid_number'] ?? '');
    $selected_subject = sanitizeInput($_POST['selected_subject'] ?? '');
    $selected_room = sanitizeInput($_POST['selected_room'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($department)) $errors[] = "Department is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($id_number)) $errors[] = "ID number is required";
    
    if (!empty($errors)) {
        http_response_code(400);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => implode("<br>", $errors)]));
    }

    // Check if this is a gate access request (Main department + Gate location)
    if ($department === 'Main' && $location === 'Gate') {
        // Remove hyphen from ID for database search
        $clean_id = str_replace('-', '', $id_number);
        
        // Use the correct column name: id_no instead of id_number
        $stmt = $db->prepare("SELECT * FROM personell WHERE id_number = ? AND department = 'Main'");
        if (!$stmt) {
            error_log("Prepare failed: " . $db->error);
            header('Content-Type: application/json');
            die(json_encode(['status' => 'error', 'message' => "Database error. Please check server logs."]));
        }
        
        $stmt->bind_param("s", $clean_id);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            header('Content-Type: application/json');
            die(json_encode(['status' => 'error', 'message' => "Database query failed."]));
        }
        
        $securityResult = $stmt->get_result();

        if ($securityResult->num_rows === 0) {
            sleep(2);
            
            // Try rfid_number as fallback (also without hyphen)
            $stmt2 = $db->prepare("SELECT * FROM personell WHERE id_number = ? AND role = 'Security Personnel'");
            if ($stmt2) {
                $stmt2->bind_param("s", $clean_id);
                $stmt2->execute();
                $securityResult = $stmt2->get_result();
            }
        }

        if ($securityResult->num_rows === 0) {
            sleep(2);
            
            // Debug: Check what IDs actually exist
            $debugStmt = $db->prepare("SELECT id_number, first_name, last_name, role FROM personell WHERE (role LIKE '%Security Personnel%' OR role LIKE '%Guard%')");
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            
            $availablePersonnel = [];
            while ($row = $debugResult->fetch_assoc()) {
                $availablePersonnel[] = " RFID:{$row['id_number']}, Name:{$row['first_name']} {$row['last_name']}";
            }
            
            die("Unauthorized access. Security personnel not found with ID: $id_number");
        }

        $securityGuard = $securityResult->fetch_assoc();
        
        // Check if they have security role
        $role = strtolower($securityGuard['role'] ?? '');
        $isSecurity = stripos($role, 'security') !== false || stripos($role, 'guard') !== false;
        
        if (!$isSecurity) {
            sleep(2);
            header('Content-Type: application/json');
            die(json_encode([ 
                'message' => "Unauthorized access. User found but not security personnel. Role: " . ($securityGuard['role'] ?? 'Unknown')
            ]));
        }

        // Verify room credentials for gate
        $stmt = $db->prepare("SELECT * FROM rooms WHERE department = ? AND room = ?");
        $stmt->bind_param("ss", $department, $location);
        $stmt->execute();
        $roomResult = $stmt->get_result();

        if ($roomResult->num_rows === 0) {
            sleep(2);
            header('Content-Type: application/json');
            die(json_encode(['status' => 'error', 'message' => "Gate access not configured."]));
        }

        $room = $roomResult->fetch_assoc();

        // Verify gate password
        $stmt = $db->prepare("SELECT * FROM rooms WHERE password=? AND department='Main' AND room='Gate'");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        $passwordResult = $stmt->get_result();

        if ($passwordResult->num_rows === 0) {
            sleep(2);
            header('Content-Type: application/json');
            die(json_encode(['status' => 'error', 'message' => "Invalid Gate Password."]));
        }

        // Gate login successful - set session data
        $_SESSION['access'] = [
            'security' => [
                'id' => $securityGuard['id'],
                'fullname' => $securityGuard['first_name'] . ' ' . $securityGuard['last_name'],
                'id_number' => $securityGuard['id_number'],
                'role' => $securityGuard['role']
            ],
            'gate' => [
                'department' => 'Main',
                'location' => 'Gate'
            ],
            'last_activity' => time()
        ];

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Clear any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');

        // Return JSON response for gate access - REDIRECT TO MAIN.PHP
        echo json_encode([
            'status' => 'success',
            'redirect' => 'main.php', // Security personnel go to main.php
            'message' => 'Gate access granted'
        ]);
        exit;
    }

    // Regular instructor login process (for non-gate access)
    // Verify ID number against instructor table with rate limiting
    $stmt = $db->prepare("SELECT * FROM instructor WHERE id_number = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $instructorResult = $stmt->get_result();

    if ($instructorResult->num_rows === 0) {
        sleep(2); // Slow down brute force attempts
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => "Invalid ID number. Instructor not found."]));
    }

    $instructor = $instructorResult->fetch_assoc();

    // Verify room credentials
    $stmt = $db->prepare("SELECT * FROM rooms WHERE department = ? AND room = ?");
    $stmt->bind_param("ss", $department, $location);
    $stmt->execute();
    $roomResult = $stmt->get_result();

    if ($roomResult->num_rows === 0) {
        sleep(2);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => "Room not found."]));
    }

    $room = $roomResult->fetch_assoc();

    $stmt = $db->prepare("SELECT * FROM rooms WHERE password=?");
    $stmt->bind_param("s", $password);
    $stmt->execute();
    $password = $stmt->get_result();

    if ($password->num_rows === 0) {
        sleep(2);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => "Invalid Password."]));
    }

    // Login successful - set session data
    $_SESSION['access'] = [
        'instructor' => [
            'id' => $instructor['id'],
            'fullname' => $instructor['fullname'],
            'id_number' => $instructor['id_number']
        ],
        'room' => [
            'id' => $room['id'],
            'department' => $room['department'],
            'room' => $room['room'],
            'desc' => $room['desc'],
            'descr' => $room['descr'],
            'authorized_personnel' => $room['authorized_personnel']
        ],
        'subject' => [
            'name' => $selected_subject,
            'room' => $selected_room,
            'time' => $_POST['selected_time'] // Add this line
        ],
        'last_activity' => time()
    ];

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Clear output buffer before JSON response
    if ($password->num_rows === 0) {
        sleep(2);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => 'Invalid Password.']));
    }

    // Clear any existing output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set proper headers
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    // Return JSON response
    echo json_encode([
        'status' => 'success',
        'redirect' => 'main1.php'
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GACPMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Gate and Personnel Management System">
    <meta name="robots" content="noindex, nofollow"> <!-- Prevent search engine indexing -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://fonts.googleapis.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none';">
    
    <!-- Security Meta Tags -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css">
    <link rel="stylesheet" href="admin/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin/css/style.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .mb-3, .mb-4 {
            transition: all 0.3s ease;
        }
        
        /* Security guard specific styles */
        .gate-access-info {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .subject-radio:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .modal-subject-row:hover {
            background-color: #f8f9fa;
        }
        
        /* Additional security styling */
        body {
            position: relative;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-sm-5">
                    <form id="logform" method="POST" novalidate autocomplete="on">
                        <div id="alert-container" class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h3 class="text-primary mb-0">GACPMS</h3>
                            <h5 class="text-muted mb-0">Location</h5>
                        </div>
                        
                        <div class="mb-3">
                            <label for="roomdpt" class="form-label">Department</label>
                            <select class="form-select" name="roomdpt" id="roomdpt" required autocomplete="organization">
                                <option value="Main" selected>Main</option>
                                <?php
                                $sql = "SELECT department_name FROM department WHERE department_name != 'Main'";
                                $result = $db->query($sql);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <option value="<?= htmlspecialchars($row['department_name']) ?>">
                                    <?= htmlspecialchars($row['department_name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <select class="form-select" name="location" id="location" required autocomplete="organization-title">
                                <option value="Gate" selected>Gate</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="Ppassword" required autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="id-input" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="id-input" name="Pid_number" 
                                   placeholder="0000-0000" required autocomplete="username"
                                   pattern="[0-9]{4}-[0-9]{4}" 
                                   title="Please enter ID in format: 0000-0000">
                            <div class="form-text">Scan your ID barcode or type manually (format: 0000-0000)</div>
                        </div>
                        
                        <!-- Gate access information -->
                        <div id="gateAccessInfo" class="gate-access-info d-none">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Gate Access Mode:</strong> Security personnel only
                        </div>
                        
                        <!-- Hidden fields for selected subject -->
                        <input type="hidden" name="selected_subject" id="selected_subject" value="">
                        <input type="hidden" name="selected_room" id="selected_room" value="">
                        <input type="hidden" name="selected_time" id="selected_time" value="">
                        
                        <!-- Security: Add CSRF token if needed -->
                        <!-- <input type="hidden" name="csrf_token" value="<?php echo bin2hex(random_bytes(32)); ?>"> -->
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" id="loginButton">Login</button>
                        
                        <div class="text-end">
                            <a href="terms.php" class="terms-link">Terms and Conditions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subject Selection Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Select Your Subject for <span id="modalRoomName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Please select the subject you're currently teaching in this room and click "Confirm Selection".
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="subjectTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">Select</th>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="subjectList">
                                <!-- Subjects will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelSubject">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSubject" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="confirmSpinner" role="status" aria-hidden="true"></span>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="admin/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    // Security: Prevent console access in production
    if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        console.log = function() {};
        console.warn = function() {};
        console.error = function() {};
    }

    $(document).ready(function() {
        // Security: Add integrity checks for external resources (if needed)
        
        // Password visibility toggle
        $('#togglePassword').click(function() {
            const icon = $(this).find('i');
            const passwordField = $('#password');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // ID Number Input Handling with Formatting
        const idInput = $('#id-input');

        idInput.on('input', function(e) {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            $(this).val(value);
        });
        
        // Show/hide gate access info based on department selection
        $('#roomdpt, #location').change(function() {
            const department = $('#roomdpt').val();
            const location = $('#location').val();
            
            if (department === 'Main' && location === 'Gate') {
                $('#gateAccessInfo').removeClass('d-none');
            } else {
                $('#gateAccessInfo').addClass('d-none');
            }
        });

        // Initial check
        $('#roomdpt').trigger('change');

        // Form submission handler - UPDATED LOGIC
        $('#logform').on('submit', function(e) {
            e.preventDefault();
            
            const idNumber = $('#id-input').val();
            const password = $('#password').val();
            const department = $('#roomdpt').val();
            const selectedRoom = $('#location').val();
            
            // Validate ID format
            if (!/^\d{4}-\d{4}$/.test(idNumber)) {
                showAlert('Please enter a valid ID number (format: 0000-0000)');
                idInput.focus();
                return;
            }
            
            if (!password) {
                showAlert('Please enter your password');
                $('#password').focus();
                return;
            }
            
            // For Main department + Gate location, proceed directly to gate access
            if (department === 'Main' && selectedRoom === 'Gate') {
                submitLoginForm();
            } 
            // For instructors, show subject selection if no subject is selected yet
            else if (!$('#selected_subject').val()) {
                showSubjectSelectionModal();
            }
            // If subject is already selected, proceed with login
            else {
                submitLoginForm();
            }
        });

        // Show subject selection modal
        function showSubjectSelectionModal() {
            const idNumber = $('#id-input').val();
            const selectedRoom = $('#location').val();
            
            if (!idNumber || !selectedRoom) {
                showAlert('Please select a location first');
                return;
            }
            
            // Clear previous selections
            $('#selected_subject').val('');
            $('#selected_room').val('');
            $('#selected_time').val('');
            $('.subject-radio').prop('checked', false);
            $('#confirmSubject').prop('disabled', true);
            
            $('#subjectList').html(`
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading subjects...</span>
                        </div>
                        <div class="mt-2 text-muted">Loading subjects for ${selectedRoom}...</div>
                    </td>
                </tr>
            `);
            
            const subjectModal = new bootstrap.Modal(document.getElementById('subjectModal'));
            subjectModal.show();
            
            $('#modalRoomName').text(selectedRoom);
            loadInstructorSubjects(idNumber, selectedRoom);
        }

        // Load subjects for instructor with enhanced error handling
        function loadInstructorSubjects(idNumber, selectedRoom) {
            // Clean the ID number by removing hyphens
            const cleanId = idNumber.replace(/-/g, '');
            
            console.log('🔍 Loading subjects for:', {
                idNumber: idNumber,
                cleanId: cleanId,
                room: selectedRoom
            });
            
            $('#subjectList').html(`
                <tr>
                    <td colspan="5" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading subjects...</span>
                        </div>
                        <div class="mt-2 text-muted">Loading subjects for ${selectedRoom}...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: 'get_instructor_subjects.php',
                type: 'GET',
                data: { 
                    id_number: cleanId,
                    room_name: selectedRoom
                },
                dataType: 'text', // Change to text to see raw response first
                timeout: 15000,
                success: function(rawResponse) {
                    console.log('📨 Raw API Response:', rawResponse);
                    
                    let data;
                    try {
                        data = JSON.parse(rawResponse);
                        console.log('✅ Parsed JSON:', data);
                    } catch (e) {
                        console.error('❌ JSON Parse Error:', e);
                        console.error('Raw response that failed to parse:', rawResponse);
                        
                        $('#subjectList').html(`
                            <tr>
                                <td colspan="5" class="text-center text-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Server returned invalid JSON format
                                    <br><small class="text-muted">Check browser console for details</small>
                                    <br><small class="text-muted">Response: ${rawResponse.substring(0, 100)}...</small>
                                </td>
                            </tr>
                        `);
                        return;
                    }
                    
                    // Now handle the parsed JSON
                    if (data.status === 'success') {
                        if (data.data && data.data.length > 0) {
                            displaySubjects(data.data, selectedRoom);
                        } else {
                            $('#subjectList').html(`
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No scheduled subjects found for ${selectedRoom}
                                            ${data.debug_info ? `<br><small>Instructor: ${data.debug_info.instructor_name}</small>` : ''}
                                        </div>
                                    </td>
                                </tr>
                            `);
                        }
                    } else {
                        $('#subjectList').html(`
                            <tr>
                                <td colspan="5" class="text-center text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    ${data.message || 'Unknown error occurred'}
                                    ${data.debug ? `<br><small class="text-muted">Debug: ${JSON.stringify(data.debug)}</small>` : ''}
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('🚨 AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status,
                        readyState: xhr.readyState
                    });
                    
                    let errorMessage = 'Failed to load subjects. ';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out after 15 seconds.';
                    } else if (status === 'parsererror') {
                        errorMessage = 'Server returned invalid data format.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'API endpoint not found.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server internal error.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Cannot connect to server. Check if server is running.';
                    } else {
                        errorMessage = `Network error: ${error}`;
                    }
                    
                    $('#subjectList').html(`
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${errorMessage}
                                <br><small class="text-muted">Status: ${xhr.status} - ${status}</small>
                                <br><small class="text-muted">Check browser console for details</small>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function showSubjectError(message) {
            $('#subjectList').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${message}
                        <br><small class="text-muted">Check browser console for details</small>
                    </td>
                </tr>
            `);
        }

        // Display subjects in the modal table
        function displaySubjects(schedules, selectedRoom) {
            let html = '';
            const now = new Date();
            const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' });
            const currentTimeMinutes = now.getHours() * 60 + now.getMinutes();
            
            let hasAvailableSubjects = false;
            
            schedules.forEach(schedule => {
                const isToday = schedule.day === currentDay;
                
                // Parse subject start time into minutes
                let startMinutes = null;
                let endMinutes = null;
                
                if (schedule.start_time) {
                    const [hour, minute, second] = schedule.start_time.split(':');
                    startMinutes = parseInt(hour, 10) * 60 + parseInt(minute, 10);
                }
                
                if (schedule.end_time) {
                    const [hour, minute, second] = schedule.end_time.split(':');
                    endMinutes = parseInt(hour, 10) * 60 + parseInt(minute, 10);
                }
                
                // Determine if subject is available for selection
                // Available if it's today and current time is before or during the class
                const isEnabled = isToday && startMinutes !== null && 
                                 (currentTimeMinutes <= endMinutes);
                
                const startTimeFormatted = schedule.start_time ? 
                    new Date(`1970-01-01T${schedule.start_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                    'N/A';
                    
                const endTimeFormatted = schedule.end_time ? 
                    new Date(`1970-01-01T${schedule.end_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                    'N/A';
                
                // Determine row styling
                let rowClass = '';
                let statusBadge = '';
                
                if (!isToday) {
                    rowClass = 'table-secondary';
                    statusBadge = '<span class="badge bg-secondary ms-1">Not Today</span>';
                } else if (!isEnabled) {
                    rowClass = 'table-warning';
                    statusBadge = '<span class="badge bg-warning ms-1">Class Ended</span>';
                } else {
                    hasAvailableSubjects = true;
                    statusBadge = '<span class="badge bg-success ms-1">Available</span>';
                }
                
                html += `
                    <tr class="modal-subject-row ${rowClass}">
                        <td>
                            <input type="radio" class="form-check-input subject-radio" 
                                   name="selectedSubject"
                                   data-subject="${schedule.subject || ''}"
                                   data-room="${schedule.room_name || selectedRoom}"
                                   data-time="${startTimeFormatted} - ${endTimeFormatted}"
                                   ${!isEnabled ? 'disabled' : ''}>
                        </td>
                        <td>
                            ${schedule.subject || 'N/A'}
                            ${statusBadge}
                        </td>
                        <td>${schedule.section || 'N/A'}</td>
                        <td>${schedule.day || 'N/A'}</td>
                        <td>${startTimeFormatted} - ${endTimeFormatted}</td>
                    </tr>`;
            });
            
            // Add header message about availability
            if (!hasAvailableSubjects && schedules.length > 0) {
                html = `
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                No available subjects at this time. Subjects are only available on their scheduled day.
                            </div>
                        </td>
                    </tr>
                ` + html;
            }
            
            $('#subjectList').html(html);
        }

        // Handle subject selection with radio buttons (single selection)
        $(document).on('change', '.subject-radio', function() {
            if ($(this).is(':checked') && !$(this).is(':disabled')) {
                $('#selected_subject').val($(this).data('subject'));
                $('#selected_room').val($(this).data('room'));
                $('#selected_time').val($(this).data('time'));
                $('#confirmSubject').prop('disabled', false);
            }
        });

        // Confirm subject selection
        $('#confirmSubject').click(function() {
            const subject = $('#selected_subject').val();
            const room = $('#selected_room').val();
            
            if (!subject || !room) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Subject Selected',
                    text: 'Please select a subject first.'
                });
                return;
            }
            
            // Close modal and submit form
            $('#subjectModal').modal('hide');
            submitLoginForm();
        });

        // Cancel subject selection - go back to login form
        $('#cancelSubject').click(function() {
            $('#subjectModal').modal('hide');
            // Clear any selections
            $('#selected_subject').val('');
            $('#selected_room').val('');
            $('#selected_time').val('');
            $('.subject-radio').prop('checked', false);
        });

        // Handle modal hidden event
        $('#subjectModal').on('hidden.bs.modal', function() {
            // If no subject was selected, focus back on ID input
            if (!$('#selected_subject').val()) {
                $('#id-input').focus();
            }
        });

        // Submit login form to server
        function submitLoginForm() {
            const formData = $('#logform').serialize();
            
            Swal.fire({
                title: 'Logging in...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '', // same PHP page
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: response.message || 'Redirecting...',
                            timer: 1500,
                            showConfirmButton: false,
                            willClose: () => {
                                window.location.href = response.redirect;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: response.message || 'Invalid credentials'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    
                    // Try to parse the response as text first to see what's coming back
                    let errorMessage = 'Login request failed. Please try again.';
                    
                    try {
                        // If it's JSON, parse it
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // If it's not JSON, show the raw response for debugging
                        errorMessage = xhr.responseText || errorMessage;
                        if (errorMessage.length > 100) {
                            errorMessage = errorMessage.substring(0, 100) + '...';
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                    
                    console.error('Login error:', xhr.responseText);
                }
            });
        }

        // Utility alert (used earlier)
        function showAlert(message) {
            $('#alert-container').removeClass('d-none').text(message);
        }

        // Fetch rooms when department changes
        $('#roomdpt').change(function() {
            const department = $(this).val();
            if (department === "Main") {
                $('#location').html('<option value="Gate" selected>Gate</option>');
                return;
            }
            
            $.get('get_rooms.php', { department: department })
                .done(function(data) {
                    $('#location').html(data);
                })
                .fail(function() {
                    $('#location').html('<option value="">Error loading rooms</option>');
                });
        });

        // Initial focus
        setTimeout(function() {
            idInput.focus();
        }, 300);
    });
    </script>
</body>
</html>