
<?php
// INSTRUCTOR
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start output buffering and session at the very top
ob_start();
session_start();

include '../connection.php';

// Initialize session variables for login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Login configuration
 $maxAttempts = 3;
 $lockoutTime = 30;
 $errorMessage = '';

// reCAPTCHA configuration
 $recaptchaSiteKey = '6Ld2w-QrAAAAAKcWH94dgQumTQ6nQ3EiyQKHUw4_';
 $recaptchaSecretKey = '6Ld2w-QrAAAAAFeIvhKm5V6YBpIsiyHIyzHxeqm-';

// Check if user is currently locked out
 $isLockedOut = ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime);
 $remainingLockoutTime = $isLockedOut ? ($lockoutTime - (time() - $_SESSION['lockout_time'])) : 0;

// Function to verify reCAPTCHA response
function verifyRecaptcha($response, $secretKey) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secretKey,
        'response' => $response
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid request. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Verify reCAPTCHA
        $recaptchaResponse = $_POST['recaptcha_response'] ?? '';
        $recaptchaResult = verifyRecaptcha($recaptchaResponse, $recaptchaSecretKey);
        
        if (!$recaptchaResult['success'] || ($recaptchaResult['score'] ?? 0) < 0.5) {
            $errorMessage = "reCAPTCHA verification failed. Please try again.";
            error_log("reCAPTCHA verification failed: " . print_r($recaptchaResult, true));
        } else {
            // Check if user is currently locked out
            if ($isLockedOut) {
                $errorMessage = "Too many failed attempts. Please wait " . $remainingLockoutTime . " seconds before trying again.";
            } else {
                // Reset attempts if lockout period has expired
                if ((time() - $_SESSION['lockout_time']) >= $lockoutTime && $_SESSION['login_attempts'] >= $maxAttempts) {
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['lockout_time'] = 0;
                }

                // Validate inputs
                $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
                $password = trim($_POST['password']);

                if (empty($username) || empty($password)) {
                    $errorMessage = "Please enter both username and password.";
                } elseif (strlen($username) > 50 || strlen($password) > 255) {
                    $errorMessage = "Invalid input length.";
                } elseif (!$db) {
                    $errorMessage = "Database connection error. Please try again later.";
                } else {
                    // Query to verify instructor credentials
                    $stmt = $db->prepare("
                        SELECT ia.*, i.fullname, i.department_id, d.department_name 
                        FROM instructor_accounts ia 
                        INNER JOIN instructor i ON ia.instructor_id = i.id 
                        LEFT JOIN department d ON i.department_id = d.department_id 
                        WHERE ia.username = ?
                    ");
                    
                    if ($stmt) {
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $user = $result->fetch_assoc();

                            // Verify password
                            if (password_verify($password, $user['password'])) {
                                // Successful login - SET ALL SESSION VARIABLES
                                $_SESSION['login_attempts'] = 0;
                                $_SESSION['lockout_time'] = 0;

                                // Store ALL required session data
                                $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                                $_SESSION['instructor_id'] = (int)$user['instructor_id'];
                                $_SESSION['fullname'] = htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8');
                                $_SESSION['department'] = htmlspecialchars($user['department_name'] ?? 'Not Assigned', ENT_QUOTES, 'UTF-8');
                                $_SESSION['role'] = 'instructor';
                                $_SESSION['logged_in'] = true;
                                $_SESSION['last_activity'] = time();
                                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                                // Update last login timestamp
                                $updateStmt = $db->prepare("UPDATE instructor_accounts SET last_login = NOW() WHERE instructor_id = ?");
                                $updateStmt->bind_param("i", $user['instructor_id']);
                                $updateStmt->execute();
                                $updateStmt->close();

                                // Debug: Log successful login
                                error_log("SUCCESSFUL LOGIN - Instructor ID: " . $user['instructor_id'] . ", Username: " . $username);
                                
                                // Regenerate session ID and redirect
                                session_regenerate_id(true);
                                
                                // Verify session data before redirect
                                error_log("SESSION BEFORE REDIRECT: " . print_r($_SESSION, true));
                                
                                header("Location: dashboard.php");
                                exit();
                            } else {
                                $_SESSION['login_attempts']++;
                                $attemptsLeft = $maxAttempts - $_SESSION['login_attempts'];
                                if ($attemptsLeft > 0) {
                                    $errorMessage = "Invalid username or password. Attempts remaining: " . $attemptsLeft;
                                } else {
                                    $_SESSION['lockout_time'] = time();
                                    $errorMessage = "Too many failed attempts. Please wait 30 seconds before trying again.";
                                }
                            }
                        } else {
                            $_SESSION['login_attempts']++;
                            $attemptsLeft = $maxAttempts - $_SESSION['login_attempts'];
                            if ($attemptsLeft > 0) {
                                $errorMessage = "Invalid username or password. Attempts remaining: " . $attemptsLeft;
                            } else {
                                $_SESSION['lockout_time'] = time();
                                $errorMessage = "Too many failed attempts. Please wait 30 seconds before trying again.";
                            }
                        }
                        $stmt->close();
                    } else {
                        $errorMessage = "Database error. Please try again later.";
                        error_log("Login prepare failed: " . $db->error);
                    }
                }
            }
        }

        // Regenerate CSRF token after processing
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Login - RFID System</title>
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
        
        .login-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
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
        }
        
        .form-control:focus {
            background-color: white;
            box-shadow: none;
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-text);
            cursor: pointer;
            z-index: 5;
            background: white;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-login {
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
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
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
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning-color);
        }
        
        .forgot-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .attempts-counter {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--danger-color);
            margin: 10px 0;
        }
        
        .attempts-warning {
            font-size: 0.9rem;
            color: var(--warning-color);
            font-weight: 600;
            margin-top: 10px;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo-title-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header-logo {
            height: 120px;
            width: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.9);
            padding: 3px;
        }

        .recaptcha-badge {
            position: fixed;
            bottom: 10px;
            right: 10px;
            z-index: 1000;
            opacity: 0.8;
        }

        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-body {
                padding: 20px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-header h3 {
                font-size: 1.5rem;
            }
            
            .logo-title-wrapper {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-logo {
                height: 80px;
                width: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <div class="logo-title-wrapper">
                    <img src="../uploads/it.png" alt="Institution Logo" class="header-logo">
                    <h3><i class="fas fa-chalkboard-teacher me-2"></i>INSTRUCTOR LOGIN</h3>
                </div>
            </div>
        </div>
        
        <div class="login-body">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Lockout Warning -->
            <div class="alert alert-warning <?php echo $isLockedOut ? '' : 'd-none'; ?>" id="lockoutAlert">
                <i class="fas fa-clock me-2"></i>
                <strong>Account Temporarily Locked</strong>
                <div class="countdown-timer" id="countdown">
                    <?php echo $remainingLockoutTime; ?> seconds
                </div>
                <div class="attempts-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Too many failed login attempts. Please wait until the timer expires.
                </div>
            </div>

            <form method="POST" id="loginForm" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="login" value="1">
                <input type="hidden" id="recaptchaResponse" name="recaptcha_response">

                <div class="form-group">
                    <label for="username" class="form-label"><i class="fas fa-user"></i>Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label"><i class="fas fa-lock"></i>Password</label>
                    <div class="input-group password-field">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password"
                               <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                        <span class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                    </div>
                </div>

                <!-- Attempts Counter -->
                <div class="attempts-counter mb-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Attempts: <span id="attemptsCount"><?php echo $_SESSION['login_attempts']; ?></span>/<?php echo $maxAttempts; ?>
                    </small>
                </div>

                <button type="submit" name="login" class="btn btn-login mb-3" id="loginBtn" <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <span id="loginText"><?php echo $isLockedOut ? 'Account Locked' : 'Login'; ?></span>
                    <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>

                <div class="login-footer">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    <div class="text-muted">© <?php echo date('Y'); ?></div>
                </div>
            </form>
        </div>
    </div>

    <div class="recaptcha-badge">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $recaptchaSiteKey; ?>"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.disabled) return;
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission handling with reCAPTCHA
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            
            if (isLockedOut) {
                e.preventDefault();
                return;
            }
            
            e.preventDefault();
            
            // Show loading state
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            
            loginText.textContent = 'Verifying...';
            loginSpinner.classList.remove('d-none');
            loginBtn.disabled = true;
            
            // Execute reCAPTCHA
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo $recaptchaSiteKey; ?>', {action: 'login'}).then(function(token) {
                    // Set the reCAPTCHA response token
                    document.getElementById('recaptchaResponse').value = token;
                    
                    // Update UI
                    loginText.textContent = 'Authenticating...';
                    
                    // Submit the form
                    document.getElementById('loginForm').submit();
                }).catch(function(error) {
                    console.error('reCAPTCHA error:', error);
                    loginText.textContent = 'reCAPTCHA Error';
                    loginSpinner.classList.add('d-none');
                    loginBtn.disabled = false;
                    
                    Swal.fire({
                        title: 'Verification Error',
                        text: 'Unable to verify with reCAPTCHA. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#e74a3b'
                    });
                });
            });
        });

        // Countdown timer for lockout
        function startCountdown(duration) {
            const countdownElement = document.getElementById('countdown');
            const loginForm = document.getElementById('loginForm');
            const inputs = loginForm.querySelectorAll('input, button');
            const lockoutAlert = document.getElementById('lockoutAlert');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            
            // Show lockout alert
            lockoutAlert.classList.remove('d-none');
            
            // Disable form elements
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.disabled = true;
                }
            });
            
            loginBtn.disabled = true;
            loginText.textContent = 'Account Locked';
            
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
                    
                    loginBtn.disabled = false;
                    loginText.textContent = 'Login';
                    
                    // Reset attempts counter display
                    document.getElementById('attemptsCount').textContent = '0';
                    
                    // Show success message
                    Swal.fire({
                        title: 'Ready to Try Again',
                        text: 'You can now attempt to login again.',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            }, 1000);
        }

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

        // Initialize lockout if needed
        <?php if ($isLockedOut): ?>
            const remainingTime = <?php echo $remainingLockoutTime; ?>;
            startCountdown(remainingTime);
        <?php endif; ?>

        // Auto-focus on username field if not locked out
        document.addEventListener('DOMContentLoaded', function() {
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            if (!isLockedOut) {
                document.getElementById('username').focus();
            }
        });

        // Show warning when reaching last attempt
        <?php if ($_SESSION['login_attempts'] == $maxAttempts - 1 && !$isLockedOut): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Warning: Last Attempt',
                text: 'This is your last login attempt. After this, your account will be locked for 30 seconds.',
                icon: 'warning',
                confirmButtonColor: '#f6c23e',
                confirmButtonText: 'I Understand'
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>