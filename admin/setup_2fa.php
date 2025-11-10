<?php
// setup_2fa.php
include '../connection.php';
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Include PHPGangsta Google Authenticator
require_once 'PHPGangsta/GoogleAuthenticator.php';

$authenticator = new PHPGangsta_GoogleAuthenticator();
$error = '';
$success = '';

// Check if user already has 2FA setup
$stmt = $db->prepare("SELECT * FROM user_2fa WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user2fa = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        // Generate new secret if doesn't exist
        if (!$user2fa) {
            $secret = $authenticator->createSecret();
            $stmt = $db->prepare("INSERT INTO user_2fa (user_id, secret_key) VALUES (?, ?)");
            $stmt->bind_param("is", $_SESSION['user_id'], $secret);
            $stmt->execute();
            $user2fa = ['secret_key' => $secret];
        }
        
        // Generate backup codes
        $backup_codes = [];
        for ($i = 0; $i < 8; $i++) {
            $backup_codes[] = bin2hex(random_bytes(4)); // 8-character codes
        }
        $backup_codes_hashed = password_hash(implode(',', $backup_codes), PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE user_2fa SET backup_codes = ? WHERE user_id = ?");
        $stmt->bind_param("si", $backup_codes_hashed, $_SESSION['user_id']);
        $stmt->execute();
        
        $_SESSION['backup_codes'] = $backup_codes;
        $success = "2FA setup initiated. Scan the QR code below.";
        
    } elseif (isset($_POST['verify_2fa'])) {
        $code = trim($_POST['verification_code']);
        
        if (empty($code)) {
            $error = "Please enter the verification code";
        } else {
            $isValid = $authenticator->verifyCode($user2fa['secret_key'], $code, 2); // 2 = 2*30sec clock tolerance
            
            if ($isValid) {
                // Enable 2FA
                $stmt = $db->prepare("UPDATE user_2fa SET is_enabled = TRUE WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                
                $_SESSION['2fa_verified'] = true;
                $success = "Two-factor authentication enabled successfully!";
                
                // Store backup codes in session to show to user
                $_SESSION['show_backup_codes'] = true;
            } else {
                $error = "Invalid verification code. Please try again.";
            }
        }
    }
}

// Get current secret
if ($user2fa) {
    $secret = $user2fa['secret_key'];
    $qrCodeUrl = $authenticator->getQRCodeGoogleUrl($_SESSION['username'], $secret, 'RFID GPMS');
} else {
    $secret = null;
    $qrCodeUrl = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup 2FA - RFID GPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e1e7f0, #b0caf0);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #4e73df, #b0caf0);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
        }
        .qr-code {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
        }
        .backup-code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 5px 0;
            font-family: monospace;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h3><i class="fas fa-shield-alt me-2"></i>Two-Factor Authentication</h3>
                <p class="mb-0">Add an extra layer of security to your account</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!$user2fa || !$user2fa['is_enabled']): ?>
                    <!-- Setup 2FA Section -->
                    <div class="text-center mb-4">
                        <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                        <h4>Enable Two-Factor Authentication</h4>
                        <p class="text-muted">Protect your account with an extra layer of security</p>
                    </div>

                    <?php if ($secret && $qrCodeUrl): ?>
                        <!-- QR Code for setup -->
                        <div class="text-center mb-4">
                            <p><strong>Step 1:</strong> Scan this QR code with your authenticator app</p>
                            <div class="qr-code mb-3">
                                <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="img-fluid">
                            </div>
                            <p class="text-muted small">
                                <strong>Secret Key:</strong> <code><?php echo chunk_split($secret, 4, ' '); ?></code>
                            </p>
                            <p class="text-muted small">
                                Can't scan? Enter the secret key manually in your authenticator app.
                            </p>
                        </div>

                        <!-- Verification form -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><strong>Step 2:</strong> Enter verification code</label>
                                <input type="text" class="form-control form-control-lg text-center" 
                                       name="verification_code" placeholder="000000" maxlength="6" 
                                       pattern="[0-9]{6}" required>
                                <div class="form-text">Enter the 6-digit code from your authenticator app</div>
                            </div>
                            <button type="submit" name="verify_2fa" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-check-circle me-2"></i>Verify & Enable 2FA
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Initial setup button -->
                        <form method="POST">
                            <div class="text-center">
                                <button type="submit" name="enable_2fa" class="btn btn-primary btn-lg">
                                    <i class="fas fa-shield-alt me-2"></i>Setup Two-Factor Authentication
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- 2FA Already Enabled -->
                    <div class="text-center">
                        <i class="fas fa-shield-check fa-3x text-success mb-3"></i>
                        <h4 class="text-success">Two-Factor Authentication Enabled</h4>
                        <p class="text-muted">Your account is protected with 2FA</p>
                        
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                            </a>
                            <a href="disable_2fa.php" class="btn btn-outline-danger">
                                <i class="fas fa-times me-2"></i>Disable 2FA
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Backup Codes Modal -->
        <?php if (isset($_SESSION['show_backup_codes']) && $_SESSION['show_backup_codes']): ?>
        <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Save Your Backup Codes
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p><strong>Important:</strong> Save these backup codes in a secure location. You can use them to access your account if you lose your authenticator app.</p>
                        
                        <div class="backup-codes-container">
                            <?php foreach ($_SESSION['backup_codes'] as $code): ?>
                                <div class="backup-code text-center"><?php echo strtoupper($code); ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Each backup code can only be used once.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Codes
                        </button>
                        <form method="POST" action="dashboard.php">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>I've Saved These Codes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            unset($_SESSION['show_backup_codes']);
            unset($_SESSION['backup_codes']);
        ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>