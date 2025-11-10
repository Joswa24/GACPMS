<?php
// Start output buffering and session at the very top
ob_start();
session_start();

include '../connection.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$successMessage = '';
$instructorInfo = null;
$showResetModal = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle ID scan verification
    if (isset($_POST['scan_id'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $errorMessage = "Invalid request. Please try again.";
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            // Validate ID number
            $id_number = trim($_POST['id_number']);
            
            if (empty($id_number)) {
                $errorMessage = "Please scan your ID card.";
            } elseif (strlen($id_number) > 50) {
                $errorMessage = "Invalid ID number length.";
            } elseif (!$db) {
                $errorMessage = "Database connection error. Please try again later.";
            } else {
                // Check if instructor exists with this ID number
                $stmt = $db->prepare("
                    SELECT i.id, i.fullname, i.id_number, ia.id as account_id, ia.username 
                    FROM instructor i 
                    LEFT JOIN instructor_accounts ia ON i.id = ia.instructor_id 
                    WHERE i.id_number = ?
                ");
                
                if ($stmt) {
                    $stmt->bind_param("s", $id_number);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $instructorInfo = $result->fetch_assoc();
                        
                        // Check if instructor has an account
                        if ($instructorInfo['account_id']) {
                            $showResetModal = true;
                            $_SESSION['verified_instructor_id'] = $instructorInfo['id'];
                            $_SESSION['verified_instructor_data'] = $instructorInfo;
                        } else {
                            $errorMessage = "No account found for this ID number. Please contact administrator.";
                        }
                    } else {
                        $errorMessage = "ID number not found in our system.";
                    }
                    $stmt->close();
                } else {
                    $errorMessage = "Database error. Please try again later.";
                    error_log("Password reset prepare failed: " . $db->error);
                }
            }
        }
    }
    
    // Handle password reset confirmation
    if (isset($_POST['confirm_reset'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $errorMessage = "Invalid request. Please try again.";
        } elseif (!isset($_SESSION['verified_instructor_id'])) {
            $errorMessage = "Session expired. Please scan your ID again.";
        } else {
            $instructor_id = $_SESSION['verified_instructor_id'];
            
            // Generate a random temporary password (8 characters)
            $tempPassword = bin2hex(random_bytes(4)); // 8 characters
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            // Update the password in the database
            $updateStmt = $db->prepare("UPDATE instructor_accounts SET password = ? WHERE instructor_id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $instructor_id);
            
            if ($updateStmt->execute()) {
                $successMessage = "Password reset successful! Your temporary password is: <strong>" . $tempPassword . "</strong><br>Please login and change your password immediately.";
                
                // Clear session data after successful reset
                unset($_SESSION['verified_instructor_id']);
                unset($_SESSION['verified_instructor_data']);
                $instructorInfo = null;
                $showResetModal = false;
            } else {
                $errorMessage = "Error resetting password. Please try again.";
            }
            $updateStmt->close();
        }
    }
    
    // Regenerate CSRF token after processing
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if we have verified instructor data from session
if (!$instructorInfo && isset($_SESSION['verified_instructor_data'])) {
    $instructorInfo = $_SESSION['verified_instructor_data'];
    $showResetModal = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <meta name="description" content="Gate and Personnel Management System">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Heebo', sans-serif;
            padding: 20px;
        }
        
        .password-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            transition: transform 0.3s ease;
        }
        
        .password-container:hover {
            transform: translateY(-5px);
        }
        
        .password-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .password-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .password-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .password-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .password-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-right: 8px;
            color: var(--accent-color);
        }
        
        .scan-indicator {
            text-align: center;
            margin: 10px 0;
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .scan-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .instructor-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-color);
        }
        
        .instructor-info h5 {
            color: var(--accent-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(78, 115, 223, 0.1);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .btn-scan {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn-scan:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-scan:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: #d1f7e9;
            color: #0f5132;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid var(--accent-color);
        }
        
        .back-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .instructions {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* Barcode Scanner Box Styles */
        .scanner-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .scanner-container:hover {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        }
        
        .scanner-container.scanning {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-style: solid;
        }
        
        .scanner-container.scanned {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border-style: solid;
        }
        
        .scanner-icon {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .scanner-container.scanning .scanner-icon {
            color: var(--accent-color);
            animation: scan 1s infinite;
        }
        
        .scanner-container.scanned .scanner-icon {
            color: var(--success-color);
        }
        
        @keyframes scan {
            0% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0); }
        }
        
        .scanner-title {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .scanner-instruction {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .barcode-display {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 3px;
            color: #2c3e50;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #ced4da;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            word-break: break-all;
            width: 100%;
            margin-top: 15px;
        }
        
        .barcode-placeholder {
            color: #6c757d;
            font-style: italic;
            font-size: 1rem;
        }
        
        .barcode-value {
            color: var(--success-color);
            animation: highlight 1s ease;
        }
        
        @keyframes highlight {
            0% { 
                background-color: #d1f7e9;
                transform: scale(1.05);
            }
            100% { 
                background-color: white;
                transform: scale(1);
            }
        }

        /* Hidden form field */
        .hidden-field {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            height: 0;
            width: 0;
        }

        /* Modal Styles */
        .reset-modal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .reset-modal .modal-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 20px;
        }
        
        .reset-modal .modal-title {
            font-weight: 700;
        }
        
        .reset-modal .modal-body {
            padding: 25px;
        }
        
        .reset-modal .btn-confirm {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
        }
        
        .reset-modal .btn-cancel {
            background: linear-gradient(135deg, var(--danger-color), #c23b2a);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 20px;
        }
        
        @media (max-width: 576px) {
            .password-container {
                max-width: 100%;
            }
            
            .password-body {
                padding: 20px;
            }
            
            .password-header {
                padding: 20px;
            }
            
            .password-header h3 {
                font-size: 1.5rem;
            }
            
            .barcode-display {
                font-size: 1.2rem;
                letter-spacing: 2px;
            }
            
            .scanner-container {
                padding: 20px;
                min-height: 120px;
            }
            
            .scanner-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <div class="header-content">
                <div class="logo-title-wrapper">
                    <img src="../uploads/it.png" alt="Institution Logo" class="header-logo" style="height: 80px; width: 100px;">
                    <h3><i class="fas fa-key me-2"></i>PASSWORD RECOVERY</h3>
                </div>
            </div>
        </div>
        
        <div class="password-body">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h6><i class="fas fa-info-circle me-2"></i>Instructions:</h6>
                <ul class="mb-0">
                    <li><strong>Click on the scanner box below</strong> to activate the scanner</li>
                    <li>Scan your ID card when the scanner is active</li>
                    <li>Scanned barcode will be displayed automatically</li>
                    <li>If your ID is verified, a reset confirmation will appear</li>
                    <li>You will receive a temporary password to login</li>
                </ul>
            </div>

            <form method="POST" id="scanForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="scan_id" value="1">
                
                <!-- Hidden input field for form submission -->
                <input type="text" 
                       class="hidden-field" 
                       id="id_number" 
                       name="id_number" 
                       required
                       value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : ''; ?>">

                <!-- Scanner Box - This is where users click and scan -->
                <div class="scanner-container" id="scannerBox">
                    <div class="scanner-icon">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <div class="scanner-title" id="scannerTitle">
                        Click to Activate Scanner
                    </div>
                    <div class="scanner-instruction" id="scannerInstruction">
                        Click this box then scan your ID card
                    </div>
                    
                    <!-- Barcode Display Area -->
                    <div class="barcode-display" id="barcodeDisplay">
                        <span class="barcode-placeholder" id="barcodePlaceholder">Barcode will appear here after scanning</span>
                        <span id="barcodeValue" class="d-none"></span>
                    </div>
                </div>

                <div class="scan-indicator scan-animation" id="scanIndicator">
                    <i class="fas fa-rss me-2"></i>Scanner Ready - Click the box above to start scanning
                </div>

                <!-- Display instructor information if found -->
                <?php if ($instructorInfo && !$successMessage): ?>
                    <div class="instructor-info">
                        <h5><i class="fas fa-user-check me-2"></i>Instructor Found</h5>
                        <div class="info-item">
                            <span class="info-label">Full Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['fullname']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['id_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['username']); ?></span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Identity Verified</strong><br>
                        Click "Verify ID" to proceed with password reset.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-scan mb-3" id="verifyBtn" disabled>
                    <i class="fas fa-id-card me-2"></i>
                    <span id="verifyText">Scan ID First</span>
                    <span id="verifySpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>

                <div class="text-center">
                    <a href="index.php" class="back-link">
                        <i class="fas fa-arrow-left me-2"></i>Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade reset-modal" id="resetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Confirm Password Reset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="fw-bold">Are you sure you want to reset your password?</h5>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Important:</strong> This will generate a new temporary password. You must login immediately and change your password.
                    </div>
                    
                    <?php if ($instructorInfo): ?>
                    <div class="instructor-info">
                        <h6><i class="fas fa-user me-2"></i>Account Details</h6>
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['fullname']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ID Number:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['id_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($instructorInfo['username']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="resetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="confirm_reset" value="1">
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-cancel flex-fill" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-confirm flex-fill" id="confirmResetBtn">
                                <i class="fas fa-sync-alt me-2"></i>
                                <span>Reset Password</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" id="confirmSpinner"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Scanner state management
        let isScannerActive = false;
        let scanBuffer = '';
        let scanTimeout;
        let hiddenInput;

        // Initialize scanner
        function initScanner() {
            hiddenInput = document.getElementById('id_number');
            const scannerBox = document.getElementById('scannerBox');
            const scanIndicator = document.getElementById('scanIndicator');
            const verifyBtn = document.getElementById('verifyBtn');
            const verifyText = document.getElementById('verifyText');

            // Click on scanner box to activate
            scannerBox.addEventListener('click', function() {
                if (!isScannerActive) {
                    activateScanner();
                }
            });

            // Listen for key events on the entire document when scanner is active
            document.addEventListener('keydown', handleKeyPress);
        }

        // Activate scanner
        function activateScanner() {
            isScannerActive = true;
            const scannerBox = document.getElementById('scannerBox');
            const scannerTitle = document.getElementById('scannerTitle');
            const scannerInstruction = document.getElementById('scannerInstruction');
            const scanIndicator = document.getElementById('scanIndicator');
            const scannerIcon = scannerBox.querySelector('.scanner-icon i');

            // Update UI for active scanning
            scannerBox.classList.add('scanning');
            scannerBox.classList.remove('scanned');
            scannerTitle.textContent = 'Scanner Active - Scan Now';
            scannerInstruction.textContent = 'Point your barcode scanner and scan the ID card';
            scanIndicator.innerHTML = '<i class="fas fa-barcode me-2"></i>Scanner Active - Ready to receive scan';
            scanIndicator.style.color = 'var(--accent-color)';
            scannerIcon.className = 'fas fa-barcode';

            // Clear any previous scan
            scanBuffer = '';
            clearTimeout(scanTimeout);

            console.log('Scanner activated - ready to scan');
        }

        // Deactivate scanner
        function deactivateScanner() {
            isScannerActive = false;
            const scannerBox = document.getElementById('scannerBox');
            const scanIndicator = document.getElementById('scanIndicator');

            scannerBox.classList.remove('scanning');
            scanIndicator.innerHTML = '<i class="fas fa-rss me-2"></i>Scanner Ready - Click the box to scan again';
            scanIndicator.style.color = 'var(--accent-color)';

            console.log('Scanner deactivated');
        }

        // Handle key presses for scanner input
        function handleKeyPress(e) {
            if (!isScannerActive) return;

            // Prevent default behavior for most keys during scanning
            if (e.key.length === 1 || e.key === 'Enter') {
                e.preventDefault();
            }

            // Clear buffer if it's been too long between keystrokes
            clearTimeout(scanTimeout);

            // If Enter key is pressed, process the scan
            if (e.key === 'Enter') {
                processScan(scanBuffer);
                scanBuffer = '';
                return;
            }

            // Add character to buffer (ignore modifier keys)
            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                scanBuffer += e.key;
                console.log('Scanner input:', e.key, 'Buffer:', scanBuffer);
            }

            // Set timeout to clear buffer if no activity
            scanTimeout = setTimeout(() => {
                console.log('Scanner buffer cleared due to inactivity');
                scanBuffer = '';
            }, 200);
        }

        // Function to format ID number as 0000-0000
        function formatIdNumber(id) {
            // Remove any non-digit characters
            const cleaned = id.replace(/\D/g, '');
            
            // Format as 0000-0000 if we have 8 digits
            if (cleaned.length === 8) {
                return cleaned.substring(0, 4) + '-' + cleaned.substring(4, 8);
            }
            
            // Return original if not 8 digits
            return cleaned;
        }

        // Process the scanned data
        function processScan(data) {
            if (data.trim().length > 0) {
                // Format the scanned data as 0000-0000
                const formattedValue = formatIdNumber(data.trim());
                
                console.log('Raw scan data:', data);
                console.log('Formatted ID:', formattedValue);
                
                // Update the hidden input field
                hiddenInput.value = formattedValue;
                
                // Update barcode display
                updateBarcodeDisplay(formattedValue);
                
                // Update scanner UI
                const scannerBox = document.getElementById('scannerBox');
                const scannerTitle = document.getElementById('scannerTitle');
                const scannerInstruction = document.getElementById('scannerInstruction');
                const scanIndicator = document.getElementById('scanIndicator');
                const verifyBtn = document.getElementById('verifyBtn');
                const verifyText = document.getElementById('verifyText');
                
                scannerBox.classList.remove('scanning');
                scannerBox.classList.add('scanned');
                scannerTitle.textContent = 'ID Scanned Successfully!';
                scannerInstruction.textContent = 'ID: ' + formattedValue;
                scanIndicator.innerHTML = '<i class="fas fa-check-circle me-2"></i>Barcode scanned successfully!';
                scanIndicator.style.color = 'var(--success-color)';
                
                // Enable verify button
                verifyBtn.disabled = false;
                verifyText.textContent = 'Verify ID';
                
                // Auto-submit the form after a short delay
                setTimeout(() => {
                    console.log('Auto-submitting form with ID:', formattedValue);
                    document.getElementById('scanForm').submit();
                }, 1500);
                
                // Deactivate scanner after successful scan
                setTimeout(deactivateScanner, 2000);
            }
        }

        // Update barcode display
        function updateBarcodeDisplay(value) {
            const barcodeDisplay = document.getElementById('barcodeDisplay');
            const barcodePlaceholder = document.getElementById('barcodePlaceholder');
            const barcodeValue = document.getElementById('barcodeValue');
            
            // Hide placeholder and show actual value
            barcodePlaceholder.classList.add('d-none');
            barcodeValue.textContent = value;
            barcodeValue.classList.remove('d-none');
            barcodeValue.classList.add('barcode-value');
            
            // Add visual feedback
            barcodeDisplay.classList.add('barcode-value');
            
            // Remove highlight animation after it completes
            setTimeout(() => {
                barcodeDisplay.classList.remove('barcode-value');
            }, 1000);
        }

        // Form submission handling
        document.getElementById('scanForm').addEventListener('submit', function(e) {
            const idNumber = hiddenInput.value.trim();
            
            if (idNumber === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No ID Scanned',
                    text: 'Please scan your ID card before submitting.'
                });
                return;
            }
            
            // Show loading state
            const verifyBtn = document.getElementById('verifyBtn');
            const verifyText = document.getElementById('verifyText');
            const verifySpinner = document.getElementById('verifySpinner');
            
            verifyText.textContent = 'Verifying...';
            verifySpinner.classList.remove('d-none');
            verifyBtn.disabled = true;
        });

        // Reset form handling
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const confirmBtn = document.getElementById('confirmResetBtn');
            const confirmSpinner = document.getElementById('confirmSpinner');
            
            confirmBtn.disabled = true;
            confirmSpinner.classList.remove('d-none');
        });

        // Security: Disable right-click and developer tools
        document.addEventListener('contextmenu', (e) => e.preventDefault());
            
        document.onkeydown = function(e) {
            if (e.keyCode === 123 || // F12
                (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
                (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
                (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                e.preventDefault();
                Swal.fire({
                    title: 'Restricted Action',
                    text: 'This action is not allowed.',
                    icon: 'warning',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        };

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initScanner();
            
            // Initialize barcode display with any existing value
            const existingValue = hiddenInput.value.trim();
            if (existingValue) {
                updateBarcodeDisplay(existingValue);
                document.getElementById('verifyBtn').disabled = false;
                document.getElementById('verifyText').textContent = 'Verify ID';
            }
            
            console.log('Password recovery page loaded - Scanner system ready');
        });

        // Show reset modal if needed
        <?php if ($showResetModal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const resetModal = new bootstrap.Modal(document.getElementById('resetModal'));
            resetModal.show();
        });
        <?php endif; ?>

        // Show success message with temporary password
        <?php if (!empty($successMessage)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Password Reset Successful!',
                html: `<?php echo addslashes($successMessage); ?>`,
                icon: 'success',
                confirmButtonColor: '#1cc88a',
                confirmButtonText: 'Go to Login'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                }
            });
        });
        <?php endif; ?>

        // Handle modal hidden event
        document.getElementById('resetModal').addEventListener('hidden.bs.modal', function () {
            // Reset scanner state
            deactivateScanner();
            const scannerBox = document.getElementById('scannerBox');
            const scannerTitle = document.getElementById('scannerTitle');
            const scannerInstruction = document.getElementById('scannerInstruction');
            const barcodePlaceholder = document.getElementById('barcodePlaceholder');
            const barcodeValue = document.getElementById('barcodeValue');
            
            scannerBox.classList.remove('scanning', 'scanned');
            scannerTitle.textContent = 'Click to Activate Scanner';
            scannerInstruction.textContent = 'Click this box then scan your ID card';
            barcodePlaceholder.classList.remove('d-none');
            barcodeValue.classList.add('d-none');
            barcodeValue.textContent = '';
            
            // Clear hidden input
            hiddenInput.value = '';
            
            // Reset verify button
            const verifyBtn = document.getElementById('verifyBtn');
            const verifyText = document.getElementById('verifyText');
            const verifySpinner = document.getElementById('verifySpinner');
            
            verifyText.textContent = 'Scan ID First';
            verifySpinner.classList.add('d-none');
            verifyBtn.disabled = true;
        });
    </script>
</body>
</html>