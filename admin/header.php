<?php
// Security Headers Configuration for RFID-GPMS
function setSecurityHeaders() {
    // Remove PHP version header
    header_remove('X-Powered-By');
    
    // Enhanced Content Security Policy with reCAPTCHA
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://www.google.com https://www.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://fonts.googleapis.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self' https://www.google.com; frame-ancestors 'none';");

    //Other security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
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

    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    
    // Content Security Policy
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://ajax.googleapis.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
        "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "media-src 'self'"
    ];
    
    header("Content-Security-Policy: " . implode("; ", $csp));
    
    // Cache control for dynamic pages
    if (in_array(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_EXTENSION), ['php', 'html'])) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    
}

// Call this function at the beginning of every PHP file
setSecurityHeaders();
?>
<head>
    <meta charset="utf-8">
    <title>RFIDGACPMS</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    <link rel="icon" href="uploads/logo.jpg" type="image/jpg">

    <!-- Favicon -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=6Ld2w-QrAAAAAKcWH94dgQumTQ6nQ3EiyQKHUw4_"></script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons & Styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" rel="stylesheet" /> 
    
    <!-- jQuery & UI -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    
    <!-- Additional Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/elevatezoom/2.2.3/jquery.elevatezoom.js" integrity="sha512-EjW7LChk2bIML+/kvj1NDrPSKHqfQ+zxJGBUKcopijd85cGwAX8ojz+781Rc0e7huwyI3j5Bn6rkctL3Gy61qw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS Variables -->
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
        
        body {
            font-family: 'Inter', sans-serif !important;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--dark-text);
        }
    </style>
  
    <style type="text/css">
        @media (max-width: 576px) and (max-width: 768px) {
            #lnamez {
                margin-top: 30%;
                display: block;
            }
            #up_img {
                position: relative;
                margin-top: 4%;
                display: block;
            }
        }
        @media (max-width: 992px) and (max-width: 1200px) {
            #lnamez {
                margin-top: 30%;
                display: block;
            }
            #up_img {
                position: relative;
                margin-top: 4%;
                display: block;
            }
        }
    </style>
</head>
<!-- <script>
// function executeRecaptcha() {
//     grecaptcha.ready(function() {
//         grecaptcha.execute('6Ld2w-QrAAAAAKcWH94dgQumTQ6nQ3EiyQKHUw4_', {action: 'submit'}).then(function(token) {
//             document.getElementById('g-recaptcha-response').value = token;
//         });
//     });
// }

// Execute reCAPTCHA when form is about to be submitted
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            executeRecaptcha();
        });
    });
});
</script> -->
