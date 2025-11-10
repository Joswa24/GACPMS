<style>
    .footer {
        background: white !important;
        border-top: 1px solid var(--accent-color);
        margin-top: 30px;
    }
    
    .footer-content {
        background: var(--light-bg) !important;
        border-radius: var(--border-radius);
        box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--accent-color);
    }
    
    .footer a {
        color: var(--icon-color) !important;
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
    }
    
    .footer a:hover {
        color: var(--dark-text) !important;
        transform: translateY(-1px);
    }
    
    .footer .row {
        align-items: center;
    }
</style>

<div class="container-fluid pt-4 px-4 footer">
    <div class="footer-content p-4">
        <div class="row">
            <div class="col-12 col-sm-6 text-center text-sm-start">
                &copy; <a href="dashboard.php">2025 RFID System</a>, All Rights Reserved.
            </div>
            <div class="col-12 col-sm-6 text-center text-sm-end">
                Created By <a href="dashboard.php">RFID-Based Gate and Classroom Pass Management System</a>
            </div>
        </div>
    </div>
</div>