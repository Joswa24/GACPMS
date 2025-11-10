 
<?php
session_start();
// Display success/error messages
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
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            font-family: 'Inter', sans-serif;
            color: var(--dark-text);
        }

        .content {
            background: transparent;
        }

        .bg-light {
            background-color: var(--light-bg) !important;
            border-radius: var(--border-radius);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: white;
        }

        .table th {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
        }

        .table td {
            padding: 12px;
            border-color: rgba(0,0,0,0.05);
            vertical-align: middle;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
        }

        /* Modern Button Styles */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            padding: 10px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            width: 100%;
        }

        .btn i {
            font-size: 0.9rem;
        }

        /* Add Room Button */
        .btn-add {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        /* Edit Button */
        .btn-edit {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        /* Delete Button */
        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
            color: white;
        }

        /* View Password Button */
        .btn-view {
            background: linear-gradient(135deg, var(--success-color), #17a673);
            color: white;
            box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
            color: white;
        }

        /* Modal Footer Buttons */
        .btn-close-modal {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-close-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--warning-color), #f4b619);
            color: white;
            box-shadow: 0 4px 15px rgba(246, 194, 62, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(246, 194, 62, 0.4);
            color: white;
        }

        .btn-update {
            background: linear-gradient(135deg, var(--info-color), #2c9faf);
            color: white;
            box-shadow: 0 4px 15px rgba(54, 185, 204, 0.3);
        }

        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 185, 204, 0.4);
            color: white;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.875rem;
        }

        .btn-del {
            transition: all 0.3s ease;
        }

        .btn-del:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }

        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border: none;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1);
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #e3e6f0;
            padding: 12px 16px;
            transition: var(--transition);
            background-color: var(--light-bg);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--icon-color);
            box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
            background-color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .alert {
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d1edff;
            color: #0c5460;
            border-left: 4px solid #117a8b;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .back-to-top {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)) !important;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .back-to-top:hover {
            transform: translateY(-3px);
        }

        h6.mb-4 {
            color: var(--dark-text);
            font-weight: 700;
            font-size: 1.25rem;
        }

        hr {
            opacity: 0.1;
            margin: 1.5rem 0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            transition: var(--transition);
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--icon-color);
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--secondary-color);
        }

        /* Password display styles */
        .password-display {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            background: rgba(0, 0, 0, 0.05);
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .encrypted-password {
            color: #6c757d;
            font-style: italic;
        }

        /* SweetAlert customization */
        .swal2-popup {
            border-radius: var(--border-radius) !important;
            font-family: inherit !important;
        }

        /* Button container styling */
        .button-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        /* Table action buttons container */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        /* Password column specific styles */
        .password-column {
            max-width: 200px;
        }
        /* Add to your existing CSS */
        .password-suggest {
            background: linear-gradient(135deg, var(--success-color), #17a673) !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px !important;
            font-size: 0.75rem !important;
            border-radius: 6px !important;
            transition: var(--transition) !important;
        }

        .password-suggest:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(28, 200, 138, 0.4) !important;
            color: white !important;
        }

        .password-field {
            position: relative;
        }

        .password-toggle, .password-suggest {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--icon-color);
            transition: var(--transition);
            z-index: 10;
        }

        .password-toggle {
            right: 10px;
        }

        .password-suggest {
            right: 50px;
        }

        .password-toggle:hover {
            color: var(--secondary-color);
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Manage Rooms</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#roomModal">
                                    <i class="fas fa-plus-circle"></i> Add Room
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Department</th>
                                        <th scope="col">Authorized Role</th>
                                        <th scope="col">Room</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Password</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $results = mysqli_query($db, "SELECT * FROM rooms ORDER BY id"); 
                                    while ($row = mysqli_fetch_array($results)) { 
                                    ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-room-id="<?php echo $row['id'];?>">
                                        <td class="department"><?php echo $row['department']; ?></td>
                                        <td><?php echo $row['authorized_personnel']; ?></td>
                                        <td><?php echo $row['room']; ?></td>
                                        <td><?php echo $row['descr']; ?></td>
                                        <td class="password-column">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="password-display encrypted-password" id="password-<?php echo $row['id']; ?>">
                                                    ••••••••••
                                                </span>
                                                <button type="button" class="btn btn-sm btn-view toggle-password" 
                                                        data-password="<?php echo htmlspecialchars($row['password']); ?>"
                                                        data-target="password-<?php echo $row['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td width="14%">
                                            <div class="action-buttons">
                                                <button authrole="<?php echo $row['authorized_personnel'];?>" 
                                                        descr="<?php echo $row['descr'];?>" 
                                                        pass="<?php echo $row['password'];?>" 
                                                        room="<?php echo $row['room'];?>" 
                                                        department="<?php echo $row['department'];?>" 
                                                        data-id="<?php echo $row['id'];?>" 
                                                        class="btn btn-sm btn-edit e_room_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button authrole="<?php echo $row['authorized_personnel'];?>" 
                                                        descr="<?php echo $row['descr'];?>" 
                                                        pass="<?php echo $row['password'];?>" 
                                                        room="<?php echo $row['room'];?>" 
                                                        department="<?php echo $row['department'];?>"  
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-sm btn-delete d_room_id">
                                                    <i class="fas fa-trash"></i> Delete 
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Room Modal -->
            <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Room
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="roomForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-room"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department: </b></label>
                                        <select class="form-control" name="roomdpt" id="roomdpt" autocomplete="off">
                                            <?php
                                            $sql = "SELECT * FROM department";
                                            $result = $db->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='{$row['department_name']}'>{$row['department_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Authorized Role: </b></label>
                                        <select class="form-control" name="roomrole" id="roomrole" autocomplete="off">
                                            <?php
                                            $sql = "SELECT * FROM role";
                                            $result = $db->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='{$row['role']}'>{$row['role']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Room Name:</b></label>
                                        <input name="roomname" type="text" id="roomname" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roomname-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Description:</b></label>
                                        <input name="roomdesc" type="text" id="roomdesc" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roomdesc-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group password-field">
                                        <label for="inputTime"><b>Password:</b></label>
                                        <input name="roompass" type="password" id="roompass" class="form-control" autocomplete="off">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('roompass', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success password-suggest" onclick="suggestPassword()" style="position: absolute; right: 50px; top: 50%; transform: translateY(-50%); z-index: 10;">
                                            <i class="fas fa-magic"></i> Suggest
                                        </button>
                                        <span class="error-message" id="roompass-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-save" id="btn-room">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Room Modal -->
            <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Room
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editRoomForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editroom"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department: </b></label>
                                        <select class="form-control" name="eroomdpt" id="eroomdpt" autocomplete="off">
                                            <?php
                                            $sql = "SELECT * FROM department";
                                            $result = $db->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='{$row['department_name']}'>{$row['department_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Authorized Role: </b></label>
                                        <select class="form-control" name="eroomrole" id="eroomrole" autocomplete="off">
                                            <?php
                                            $sql = "SELECT * FROM role";
                                            $result = $db->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='{$row['role']}'>{$row['role']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Room Name:</b></label>
                                        <input name="eroomname" type="text" id="eroomname" class="form-control" autocomplete="off">
                                        <span class="error-message" id="eroomname-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Description:</b></label>
                                        <input name="eroomdesc" type="text" id="eroomdesc" class="form-control" autocomplete="off">
                                        <span class="error-message" id="eroomdesc-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group password-field">
                                        <label for="inputTime"><b>Password:</b></label>
                                        <input name="eroompass" type="password" id="eroompass" class="form-control" autocomplete="off">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('eroompass', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <span class="error-message" id="eroompass-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="room_id" id="edit_roomid">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-update" id="btn-editroom">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script>
    $(document).ready(function() {
    // Initialize DataTable
    var dataTable = $('#myDataTable').DataTable({
        order: [[0, 'desc']],
        stateSave: true
    });

    // Password visibility toggle function for modal inputs
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Password toggle function for table view
    $(document).on('click', '.toggle-password', function() {
        const button = $(this);
        const targetId = button.data('target');
        const actualPassword = button.data('password');
        const passwordSpan = $('#' + targetId);
        const icon = button.find('i');
        
        if (passwordSpan.hasClass('encrypted-password')) {
            // Show actual password
            passwordSpan.removeClass('encrypted-password')
                       .addClass('actual-password')
                       .text(actualPassword);
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            button.addClass('btn-warning').removeClass('btn-view');
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                if (passwordSpan.hasClass('actual-password')) {
                    passwordSpan.removeClass('actual-password')
                               .addClass('encrypted-password')
                               .html('••••••••••');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    button.removeClass('btn-warning').addClass('btn-view');
                }
            }, 5000);
        } else {
            // Hide password
            passwordSpan.removeClass('actual-password')
                       .addClass('encrypted-password')
                       .html('••••••••••');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            button.removeClass('btn-warning').addClass('btn-view');
        }
    });

    // Reset form function
    function resetForm() {
        $('.error-message').text('');
        $('#roomForm')[0].reset();
        // Reset password visibility
        const eyeIcons = document.querySelectorAll('.password-toggle i');
        eyeIcons.forEach(icon => {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        });
        const passwordInputs = document.querySelectorAll('input[type="text"][id$="pass"]');
        passwordInputs.forEach(input => {
            input.type = 'password';
        });
    }

    // ==============
    // CREATE (ADD ROOM)
    // ==============
    $('#roomForm').submit(function(e) {
        e.preventDefault();
        
        $('.error-message').text('');
        const roomdpt = $('#roomdpt').val();
        const roomrole = $('#roomrole').val();
        const roomname = $('#roomname').val().trim();
        const roomdesc = $('#roomdesc').val().trim();
        const roompass = $('#roompass').val().trim();
        let isValid = true;

        // Validation
        if (!roomname) { 
            $('#roomname-error').text('Room name is required'); 
            isValid = false; 
        }
        if (!roomdesc) { 
            $('#roomdesc-error').text('Description is required'); 
            isValid = false; 
        }
        if (!roompass) { 
            $('#roompass-error').text('Password is required'); 
            isValid = false; 
        } else if (roompass.length < 6) { 
            $('#roompass-error').text('Password must be at least 6 characters'); 
            isValid = false; 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-room').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-room').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_room",
            data: { roomdpt, roomrole, roomname, roomdesc, roompass },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-room').html('Save');
                $('#btn-room').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#roomModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                // Reset button state
                $('#btn-room').html('Save');
                $('#btn-room').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // ==========
    // READ (EDIT ROOM)
    // ==========
    $(document).on('click', '.e_room_id', function() {
        const id = $(this).data('id');
        const department = $(this).attr('department');
        const role = $(this).attr('authrole');
        const room = $(this).attr('room');
        const descr = $(this).attr('descr');
        const pass = $(this).attr('pass');

        // Populate edit form
        $('#edit_roomid').val(id);
        $('#eroomdpt').val(department);
        $('#eroomrole').val(role);
        $('#eroomname').val(room);
        $('#eroomdesc').val(descr);
        $('#eroompass').val(pass);
        
        // Reset password visibility
        const eyeIcon = document.querySelector('#editRoomModal .password-toggle i');
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
        $('#eroompass').attr('type', 'password');
        
        // Show modal
        $('#editRoomModal').modal('show');
    });

    // ==========
    // UPDATE ROOM
    // ==========
    $('#editRoomForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_roomid').val();
        const roomdpt = $('#eroomdpt').val();
        const roomrole = $('#eroomrole').val();
        const roomname = $('#eroomname').val().trim();
        const roomdesc = $('#eroomdesc').val().trim();
        const roompass = $('#eroompass').val().trim();

        // Validation
        let isValid = true;
        if (!roomname) { 
            $('#eroomname-error').text('Room name is required'); 
            isValid = false; 
        } else { 
            $('#eroomname-error').text(''); 
        }
        if (!roomdesc) { 
            $('#eroomdesc-error').text('Description is required'); 
            isValid = false; 
        } else { 
            $('#eroomdesc-error').text(''); 
        }
        if (!roompass) { 
            $('#eroompass-error').text('Password is required'); 
            isValid = false; 
        } else if (roompass.length < 6) { 
            $('#eroompass-error').text('Password must be at least 6 characters'); 
            isValid = false; 
        } else { 
            $('#eroompass-error').text(''); 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-editroom').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#btn-editroom').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_room",
            data: { 
                id: id,
                roomdpt: roomdpt,
                roomrole: roomrole,
                roomname: roomname,
                roomdesc: roomdesc,
                roompass: roompass
            },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editroom').html('Update');
                $('#btn-editroom').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#editRoomModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                // Reset button state
                $('#btn-editroom').html('Update');
                $('#btn-editroom').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // ==========
// DELETE ROOM (DEBUGGED VERSION)
// ==========
$(document).on('click', '.d_room_id', function() {
    const $button = $(this);
    const id = $button.data('id');
    const roomName = $button.attr('room');
    
    console.log('Delete button clicked - ID:', id, 'Room:', roomName);
    
    Swal.fire({
        title: 'Delete Room?',
        text: `Are you sure you want to delete "${roomName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            $button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $button.prop('disabled', true);
            
            console.log('Sending delete request for ID:', id);
            
            $.ajax({
                type: 'POST',
                url: "transac.php?action=delete_room",
                data: { 
                    id: id,
                    _token: '<?php echo $_SESSION["csrf_token"] ?? ""; ?>' // Add CSRF protection
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Delete response:', response);
                    
                    if (response.status === 'success') {
                        // Remove row from DataTable
                        const row = $button.closest('tr');
                        dataTable.row(row).remove().draw();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to delete room'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Delete AJAX error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'AJAX Error!',
                        text: 'Failed to connect to server. Check console for details.'
                    });
                },
                complete: function() {
                    // Restore button state
                    $button.html('<i class="fas fa-trash"></i> Delete');
                    $button.prop('disabled', false);
                }
            });
        }
    });
});
    // Reset modal when closed
    $('#roomModal').on('hidden.bs.modal', function() {
        resetForm();
    });
    
    $('#editRoomModal').on('hidden.bs.modal', function() {
        $('.error-message').text('');
    });
});
   

    // Generate strong password function
    function generateStrongPassword() {
        const length = 12;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?";
        let password = "";
        
        // Ensure at least one of each required character type
        password += "ABCDEFGHIJKLMNOPQRSTUVWXYZ"[Math.floor(Math.random() * 26)]; // uppercase
        password += "abcdefghijklmnopqrstuvwxyz"[Math.floor(Math.random() * 26)]; // lowercase
        password += "0123456789"[Math.floor(Math.random() * 10)]; // number
        password += "!@#$%^&*()_+-=[]{}|;:,.<>?"[Math.floor(Math.random() * 23)]; // special char
        
        // Fill the rest with random characters
        for (let i = password.length; i < length; i++) {
            password += charset[Math.floor(Math.random() * charset.length)];
        }
        
        // Shuffle the password to make it more random
        password = password.split('').sort(() => 0.5 - Math.random()).join('');
        
        return password;
    }

    // Suggest password function
    function suggestPassword() {
        const suggestedPassword = generateStrongPassword();
        
        // Set the suggested password in the add room modal
        $('#roompass').val(suggestedPassword);
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Strong Password Generated!',
            text: 'A secure password has been suggested. You can use this or create your own.',
            showConfirmButton: false,
            timer: 2000
        });
        
        // Ensure password is visible so user can see the suggestion
        const roompassInput = document.getElementById('roompass');
        const roompassToggle = document.querySelector('#roomModal .password-toggle i');
        if (roompassInput.type === 'password') {
            roompassInput.type = 'text';
            roompassToggle.classList.remove('fa-eye');
            roompassToggle.classList.add('fa-eye-slash');
        }
    }

    // Enhanced password toggle function for both modals
    function togglePasswordVisibility(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Add event listener for password suggestion button (add this to your HTML)
    document.addEventListener('DOMContentLoaded', function() {
        // Add suggest password button to the add room modal
        const roomPassField = document.querySelector('#roomModal .password-field');
        if (roomPassField) {
            const suggestButton = document.createElement('button');
            suggestButton.type = 'button';
            suggestButton.className = 'btn btn-sm btn-success password-suggest';
            suggestButton.innerHTML = '<i class="fas fa-magic"></i> Suggest';
            suggestButton.style.position = 'absolute';
            suggestButton.style.right = '50px';
            suggestButton.style.top = '50%';
            suggestButton.style.transform = 'translateY(-50%)';
            suggestButton.style.zIndex = '10';
            suggestButton.onclick = suggestPassword;
            
            roomPassField.style.position = 'relative';
            roomPassField.appendChild(suggestButton);
        }
    });
    </script>
</body>
</html>