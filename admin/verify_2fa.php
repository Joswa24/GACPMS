<?php
// admin/verify_2fa.php
include '../connection.php';
include '../security-headers.php';
session_start();

// Additional security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("X-Permitted-Cross-Domain-Policies: none");
header("Cross-Origin-Embedder-Policy: require-corp");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user has verified password
if (!isset($_SESSION['password_verified']) || $_SESSION['password_verified'] !== true) {
    header('Location: index.php');
    exit();
}

// Initialize variables
 $error = '';
 $success = '';
 $maxAttempts = 3;
 $lockoutTime = 30;

// Initialize session variables for 2FA
if (!isset($_SESSION['2fa_attempts'])) {
    $_SESSION['2fa_attempts'] = 0;
    $_SESSION['2fa_lockout_time'] = 0;
}

// Check if user is currently locked out
 $isLockedOut = ($_SESSION['2fa_attempts'] >= $maxAttempts && (time() - $_SESSION['2fa_lockout_time']) < $lockoutTime);
 $remainingLockoutTime = $isLockedOut ? ($lockoutTime - (time() - $_SESSION['2fa_lockout_time'])) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    // Check lockout
    if ($isLockedOut) {
        $error = "Too many failed attempts. Please wait " . $remainingLockoutTime . " seconds before trying again.";
    } else {
        // Reset attempts if lockout expired
        if ((time() - $_SESSION['2fa_lockout_time']) >= $lockoutTime && $_SESSION['2fa_attempts'] >= $maxAttempts) {
            $_SESSION['2fa_attempts'] = 0;
            $_SESSION['2fa_lockout_time'] = 0;
        }

        $verificationCode = trim($_POST['verification_code']);
        
        // Input validation
        if (empty($verificationCode)) {
            $error = "Please enter the verification code.";
        } elseif (!ctype_digit($verificationCode) || strlen($verificationCode) !== 6) {
            $error = "Invalid verification code format. Please enter a 6-digit code.";
        } else {
            try {
                $userId = $_SESSION['temp_user_id'];
                
                // Check if verification code is valid
                $stmt = $db->prepare("SELECT * FROM user_2fa_codes WHERE user_id = ? AND verification_code = ? AND is_used = 0 AND expires_at > NOW()");
                $stmt->bind_param("is", $userId, $verificationCode);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Mark code as used
                    $codeId = $result->fetch_assoc()['id'];
                    $stmt = $db->prepare("UPDATE user_2fa_codes SET is_used = 1 WHERE id = ?");
                    $stmt->bind_param("i", $codeId);
                    $stmt->execute();
                    
                    // Log successful 2FA verification
                    try {
                        $ipAddress = $_SERVER['REMOTE_ADDR'];
                        $location = 'Unknown';
                        
                        // Try to get location from IP
                        if (function_exists('file_get_contents') && $ipAddress !== '127.0.0.1' && $ipAddress !== '::1') {
                            $context = stream_context_create([
                                'http' => [
                                    'timeout' => 5,
                                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                                ]
                            ]);
                            
                            $ipData = @file_get_contents("http://ip-api.com/json/{$ipAddress}", false, $context);
                            if ($ipData) {
                                $ipInfo = json_decode($ipData);
                                if ($ipInfo && $ipInfo->status === 'success') {
                                    $location = $ipInfo->city . ', ' . $ipInfo->regionName . ', ' . $ipInfo->country;
                                }
                            }
                        }
                        
                        $stmt = $db->prepare("INSERT INTO admin_access_logs (admin_id, username, login_time, ip_address, user_agent, location, activity, status) VALUES (?, ?, NOW(), ?, ?, ?, '2FA Verification', 'success')");
                        if ($stmt) {
                            $userAgent = $_SERVER['HTTP_USER_AGENT'];
                            $stmt->bind_param("issss", $userId, $_SESSION['temp_username'], $ipAddress, $userAgent, $location);
                            $stmt->execute();
                        }
                    } catch (Exception $e) {
                        error_log("Failed to log 2FA verification: " . $e->getMessage());
                    }
                    
                    // Complete login process
                    $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                    $_SESSION['username'] = $_SESSION['temp_username'];
                    $_SESSION['email'] = $_SESSION['temp_email'];
                    $_SESSION['logged_in'] = true;
                    
                    // Clear temporary session variables
                    unset($_SESSION['temp_user_id']);
                    unset($_SESSION['temp_username']);
                    unset($_SESSION['temp_email']);
                    unset($_SESSION['password_verified']);
                    unset($_SESSION['2fa_attempts']);
                    unset($_SESSION['2fa_lockout_time']);
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set secure session cookie parameters
                    session_set_cookie_params([
                        'lifetime' => 0,
                        'path' => '/',
                        'domain' => $_SERVER['HTTP_HOST'],
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Log failed 2FA attempt
                    try {
                        $ipAddress = $_SERVER['REMOTE_ADDR'];
                        $location = 'Unknown';
                        
                        if (function_exists('file_get_contents') && $ipAddress !== '127.0.0.1' && $ipAddress !== '::1') {
                            $context = stream_context_create([
                                'http' => [
                                    'timeout' => 5,
                                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                                ]
                            ]);
                            
                            $ipData = @file_get_contents("http://ip-api.com/json/{$ipAddress}", false, $context);
                            if ($ipData) {
                                $ipInfo = json_decode($ipData);
                                if ($ipInfo && $ipInfo->status === 'success') {
                                    $location = $ipInfo->city . ', ' . $ipInfo->regionName . ', ' . $ipInfo->country;
                                }
                            }
                        }
                        
                        $stmt = $db->prepare("INSERT INTO admin_access_logs (admin_id, username, login_time, ip_address, user_agent, location, activity, status) VALUES (?, ?, NOW(), ?, ?, ?, '2FA Verification', 'failed')");
                        if ($stmt) {
                            $userAgent = $_SERVER['HTTP_USER_AGENT'];
                            $stmt->bind_param("issss", $userId, $_SESSION['temp_username'], $ipAddress, $userAgent, $location);
                            $stmt->execute();
                        }
                    } catch (Exception $e) {
                        error_log("Failed to log failed 2FA verification: " . $e->getMessage());
                    }
                    
                    $_SESSION['2fa_attempts']++;
                    $attemptsLeft = $maxAttempts - $_SESSION['2fa_attempts'];
                    
                    if ($attemptsLeft > 0) {
                        $error = "Invalid verification code. Attempts remaining: " . $attemptsLeft;
                    } else {
                        $_SESSION['2fa_lockout_time'] = time();
                        $error = "Too many failed attempts. Please wait 30 seconds before trying again.";
                    }
                }
            } catch (Exception $e) {
                error_log("2FA verification error: " . $e->getMessage());
                $error = "Database error. Please try again.";
            }
        }
    }
}

// Handle resend code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    try {
        $userId = $_SESSION['temp_user_id'];
        $email = $_SESSION['temp_email'];
        
        // Generate and send new 2FA code
        $verificationCode = generate2FACode($userId, $email);
        
        if ($verificationCode) {
            $success = "A new verification code has been sent to your email.";
        } else {
            $error = "Failed to send verification code. Please try again.";
        }
    } catch (Exception $e) {
        error_log("Error resending 2FA code: " . $e->getMessage());
        $error = "Error sending verification code. Please try again.";
    }
}

// Function to generate and send 2FA code
function generate2FACode($userId, $email) {
    global $db;
    
    try {
        // Generate a 6-digit verification code
        $verificationCode = sprintf('%06d', mt_rand(0, 999999));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes')); // Code expires in 10 minutes
        
        // Delete any existing codes for this user
        $stmt = $db->prepare("DELETE FROM user_2fa_codes WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Insert new verification code
        $stmt = $db->prepare("INSERT INTO user_2fa_codes (user_id, verification_code, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $verificationCode, $expiresAt);
        
        if ($stmt->execute()) {
            // Send the code via email
            if (send2FACodeEmail($email, $verificationCode)) {
                return $verificationCode;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error generating 2FA code: " . $e->getMessage());
        return false;
    }
}

// Function to send 2FA code via email
function send2FACodeEmail($email, $verificationCode) {
    try {
        // Load PHPMailer classes
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        require_once 'PHPMailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuapastorpide10@gmail.com'; // Your Gmail
        $mail->Password = 'bmnvognbjqcpxcyf'; // Your App Password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('joshuapastorpide10@gmail.com', 'RFID GPMS Admin');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Two-Factor Authentication Code - RFID GPMS';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4e73df; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fc; }
                .code { background: #e1e7f0; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RFID GPMS Admin Portal</h2>
                </div>
                <div class='content'>
                    <h3>Two-Factor Authentication</h3>
                    <p>You have successfully entered your password. To complete the login process, please use the following verification code:</p>
                    <div class='code'>$verificationCode</div>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't request this code, please secure your account immediately.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Two-Factor Authentication\n\nYour verification code is: $verificationCode\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please secure your account immediately.";
        
        $mail->send();
        error_log("2FA code sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send 2FA code: " . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - RFID System</title>
    
    <!-- Security Meta Tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="description" content="Gate and Personnel Management System">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
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
        
        .verification-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            transition: transform 0.3s ease;
        }
        
        .verification-container:hover {
            transform: translateY(-5px);
        }
        
        .verification-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .verification-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .verification-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .verification-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .verification-body {
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
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .input-group-text {
            background-color: var(--light-bg);
            border: none;
            padding: 0.75rem 1rem;
            color: var(--accent-color);
        }
        
        .form-control {
            border: none;
            padding: 0.75rem 1rem;
            background-color: var(--light-bg);
            transition: all 0.3s ease;
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 5px;
            font-weight: bold;
        }
        
        .form-control:focus {
            background-color: white;
            box-shadow: none;
        }
        
        .btn-verify {
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
        
        .btn-verify:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-verify:active {
            transform: translateY(0);
        }
        
        .btn-verify:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-resend {
            background: transparent;
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-resend:hover:not(:disabled) {
            background-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-resend:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning-color);
        }
        
        .info-box {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-box i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .info-box p {
            margin: 0;
            color: var(--dark-text);
        }
        
        .attempts-counter {
            text-align: center;
            margin-top: 15px;
        }
        
        .attempts-counter small {
            color: var(--dark-text);
        }
        
        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--danger-color);
            margin: 10px 0;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e3e6f0;
        }
        
        .back-to-login a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-to-login a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .verification-container {
                max-width: 100%;
            }
            
            .verification-body {
                padding: 20px;
            }
            
            .verification-header {
                padding: 20px;
            }
            
            .verification-header h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-header">
            <h3><i class="fas fa-shield-alt me-2"></i>TWO-FACTOR AUTHENTICATION</h3>
            <p class="mb-0">Enter the verification code sent to your email</p>
        </div>
        
        <div class="verification-body">
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Lockout Warning -->
            <div class="alert alert-warning <?php echo $isLockedOut ? '' : 'd-none'; ?>" id="lockoutAlert">
                <i class="fas fa-clock me-2"></i>
                <strong>Verification Temporarily Locked</strong>
                <div class="countdown-timer" id="countdown">
                    <?php echo $remainingLockoutTime; ?> seconds
                </div>
                <div class="attempts-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Too many failed attempts. Please wait until the timer expires.
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <i class="fas fa-envelope"></i>
                <p>A verification code has been sent to your email address: <strong><?php echo htmlspecialchars($_SESSION['temp_email']); ?></strong></p>
                <p class="mb-0">The code will expire in 10 minutes.</p>
            </div>

            <form method="POST" id="verificationForm">
                <div class="form-group">
                    <label for="verification_code" class="form-label"><i class="fas fa-key"></i>Verification Code</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="text" class="form-control" id="verification_code" name="verification_code" 
                            placeholder="000000" required maxlength="6" pattern="[0-9]{6}"
                            autocomplete="one-time-code"
                            <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <!-- Attempts Counter -->
                <div class="attempts-counter mb-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Attempts: <span id="attemptsCount"><?php echo $_SESSION['2fa_attempts']; ?></span>/<?php echo $maxAttempts; ?>
                    </small>
                </div>

                <button type="submit" name="verify_code" class="btn btn-verify mb-3" id="verifyBtn" <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="verifyText"><?php echo $isLockedOut ? 'Verification Locked' : 'Verify Code'; ?></span>
                    <span id="verifySpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>
            </form>

            <!-- Resend Code Form -->
            <form method="POST" id="resendForm" class="mb-3">
                <button type="submit" name="resend_code" class="btn btn-resend" id="resendBtn" <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    <i class="fas fa-redo me-2"></i>
                    <span id="resendText">Resend Code</span>
                    <span id="resendSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>
            </form>

            <!-- Back to Login -->
            <div class="back-to-login">
                <a href="index.php">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('verificationForm').addEventListener('submit', function(e) {
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            
            if (isLockedOut) {
                e.preventDefault();
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

        document.getElementById('resendForm').addEventListener('submit', function(e) {
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            
            if (isLockedOut) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            const resendBtn = document.getElementById('resendBtn');
            const resendText = document.getElementById('resendText');
            const resendSpinner = document.getElementById('resendSpinner');
            
            resendText.textContent = 'Sending...';
            resendSpinner.classList.remove('d-none');
            resendBtn.disabled = true;
        });

        // Auto-focus on verification code field
        document.addEventListener('DOMContentLoaded', function() {
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            if (!isLockedOut) {
                document.getElementById('verification_code').focus();
            }
        });

        // Countdown timer for lockout
        function startCountdown(duration) {
            const countdownElement = document.getElementById('countdown');
            const verificationForm = document.getElementById('verificationForm');
            const resendForm = document.getElementById('resendForm');
            const inputs = verificationForm.querySelectorAll('input, button');
            const resendBtn = document.getElementById('resendBtn');
            const lockoutAlert = document.getElementById('lockoutAlert');
            const verifyBtn = document.getElementById('verifyBtn');
            const verifyText = document.getElementById('verifyText');
            
            // Show lockout alert
            lockoutAlert.classList.remove('d-none');
            
            // Disable form elements
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.disabled = true;
                }
            });
            
            resendBtn.disabled = true;
            verifyBtn.disabled = true;
            verifyText.textContent = 'Verification Locked';
            
            let timer = duration;
            
            const interval = setInterval(() => {
                countdownElement.textContent = timer + ' seconds';
                
                if (--timer < 0) {
                    clearInterval(interval);
                    lockoutAlert.classList.add('d-none');
                    
                    // Enable form elements
                    inputs.forEach(input => {
                        if (input.type !== 'hidden') {
                            input.disabled = false;
                        }
                    });
                    
                    resendBtn.disabled = false;
                    verifyBtn.disabled = false;
                    verifyText.textContent = 'Verify Code';
                    
                    // Reset attempts counter display
                    document.getElementById('attemptsCount').textContent = '0';
                    
                    // Show success message
                    Swal.fire({
                        title: 'Ready to Try Again',
                        text: 'You can now attempt to verify the code again.',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            }, 1000);
        }

        // Initialize lockout if needed
        <?php if ($isLockedOut): ?>
            const remainingTime = <?php echo $remainingLockoutTime; ?>;
            startCountdown(remainingTime);
        <?php endif; ?>

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

        // Show warning when reaching last attempt
        <?php if ($_SESSION['2fa_attempts'] == $maxAttempts - 1 && !$isLockedOut): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Warning: Last Attempt',
                text: 'This is your last verification attempt. After this, verification will be locked for 30 seconds.',
                icon: 'warning',
                confirmButtonColor: '#f6c23e',
                confirmButtonText: 'I Understand'
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>