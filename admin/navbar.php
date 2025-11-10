<?php
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

$username = "";
// Fetch data from the about table
$sql1 = "SELECT * FROM user LIMIT 1";
$result1 = $db->query($sql1);

if ($result1->num_rows > 0) {
    // Output data of each row
    $row = $result1->fetch_assoc();
    $username = $row['username'];
} 
?>

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
    .navbar-top {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 12px 20px !important;
    }
    
    .sidebar-toggler {
        color: #4e73df !important;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        transition: all 0.3s ease;
    }
    
    .sidebar-toggler:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.05);
    }
    
    .nav-link.dropdown-toggle {
        color: white !important;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 8px 15px !important;
        transition: all 0.3s ease;
        border: none;
    }
    
    .nav-link.dropdown-toggle:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
    }
    
    .nav-link.dropdown-toggle img {
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .dropdown-menu {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        background: white;
        padding: 10px 0;
        margin-top: 10px !important;
    }
    
    .dropdown-item {
        padding: 10px 20px;
        color: #5a5c69;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .dropdown-item:hover {
        background: #f3f5fc;
        color: #5c95e9;
        transform: translateX(5px);
    }
    
    .dropdown-item i {
        margin-right: 8px;
        color: #5c95e9;
    }
    
    .user-greeting {
        color: white;
        font-weight: 600;
        margin-right: 15px;
    }
    
    @media (max-width: 768px) {
        .user-greeting {
            display: none;
        }
        
        .nav-link.dropdown-toggle span {
            display: none;
        }
    }
</style>

<!-- Navbar Start -->
<nav class="navbar navbar-expand navbar-light sticky-top px-4 py-0 navbar-top">
    <a href="dashboard.php" class="navbar-brand d-flex d-lg-none me-4">
        <h2 class="text-white mb-0"><i class="fas fa-id-card-alt me-2"></i>GACPMS</h2>
    </a>
    
    <a href="#" class="sidebar-toggler flex-shrink-0">
        <i class="fa fa-bars"></i>
    </a>

    <div class="navbar-nav align-items-center ms-auto">
        <span class="user-greeting d-none d-md-inline">Welcome,</span>
        
        <div class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img class="rounded-circle me-lg-2" src="img\2601828.png" alt="Profile" style="width: 38px; height: 38px;">
                <span class="d-none d-lg-inline-flex"><?php echo $username; ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-end border-0 rounded-0 m-0">
                <a href="settings.php" class="dropdown-item">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</nav>
<!-- Navbar End -->