<?php 
// header("X-Frame-Options: DENY");
// header("X-Content-Type-Options: nosniff");
// header("X-XSS-Protection: 1; mode=block");
// header("Referrer-Policy: strict-origin-when-cross-origin");
// header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
// header("X-Permitted-Cross-Domain-Policies: none");
// header("Cross-Origin-Embedder-Policy: require-corp");
// header("Cross-Origin-Opener-Policy: same-origin");
// header("Cross-Origin-Resource-Policy: same-origin");
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Pragma: no-cache");
// header("Expires: 0");
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
    <!-- <script src="https://www.google.com/recaptcha/api.js?render=6LefppQqAAAAAKunsfzmruPzJe8KcazwN5CtLakp"></script> -->

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
    <!-- <link href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" rel="stylesheet" /> -->
    
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