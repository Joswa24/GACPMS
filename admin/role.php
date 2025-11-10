<?php

session_start();


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

// Check if connection is successful
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles</title>
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

        /* Add Role Button */
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
            .form-control.invalid {
                border-color: var(--danger-color) !important;
                box-shadow: 0 0 0 3px rgba(231, 74, 59, 0.15) !important;
            }

            .valid-feedback, .invalid-feedback {
                display: none;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }

            .valid-feedback {
                color: var(--success-color);
            }

            .invalid-feedback {
                color: var(--danger-color);
            }

            /* Search input styling */
            .dataTables_filter input {
                border-radius: 8px;
                border: 1.5px solid #e3e6f0;
                padding: 8px 12px;
                transition: var(--transition);
                background-color: var(--light-bg);
            }

            .dataTables_filter input:focus {
                border-color: var(--icon-color);
                box-shadow: 0 0 0 3px rgba(92, 149, 233, 0.15);
                background-color: white;
                outline: none;
            }

            .dataTables_filter label {
                font-weight: 500;
                color: var(--dark-text);
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
                                <h6 class="mb-4">Manage Roles</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#roleModal">
                                    <i class="fas fa-plus-circle"></i> Add Role
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Role</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $results = mysqli_query($db, "SELECT * FROM role"); ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-role-id="<?php echo $row['id'];?>">
                                        <td><?php echo $row['role']; ?></td>
                                        <td width="14%">
                                            <div class="action-buttons">
                                                <button role="<?php echo $row['role'];?>" 
                                                        data-id="<?php echo $row['id'];?>" 
                                                        class="btn btn-sm btn-edit e_role_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button role="<?php echo $row['role'];?>"  
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-sm btn-delete d_role_id">
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

            <!-- Add Role Modal -->
            <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Role
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="roleForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-role"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Role:</b></label>
                                        <input name="role" type="text" id="role" class="form-control" autocomplete="off" 
                                            placeholder="Enter role name">
                                        <span class="error-message" id="role-error"></span>
                                        <div class="valid-feedback">Looks good!</div>
                                        <div class="invalid-feedback">Please enter a valid role name using only letters and spaces.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-save" id="btn-role">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>



            <!-- Edit Role Modal -->
            <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Role
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editRoleForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editrole"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Role:</b></label>
                                        <input name="erole" type="text" id="erole" class="form-control" autocomplete="off"
                                            placeholder="Enter role name (letters and spaces only)">
                                        <span class="error-message" id="erole-error"></span>
                                        <div class="valid-feedback">Looks good!</div>
                                        <div class="invalid-feedback">Please enter a valid role name using only letters and spaces.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="role_id" id="edit_roleid">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-update" id="btn-editrole">Update</button>
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

    // Function to validate role input (letters and spaces only)
    function validateRoleInput(input) {
        // Remove any numbers and special characters except spaces and hyphens
        return input.replace(/[^a-zA-Z\s\-]/g, '');
    }

    // Function to prevent invalid key inputs
    function preventInvalidKeys(event) {
        // Allow: backspace, delete, tab, escape, enter, arrow keys, home, end
        if ([8, 9, 13, 27, 37, 38, 39, 40, 46].includes(event.keyCode) ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (event.ctrlKey === true && [65, 67, 86, 88].includes(event.keyCode))) {
            return;
        }
        
        // Prevent numbers and special characters except spaces and hyphens
        const char = String.fromCharCode(event.keyCode || event.which);
        if (!/^[a-zA-Z\s\-]$/.test(char)) {
            event.preventDefault();
            return false;
        }
    }

    // Function to sanitize search input
    function sanitizeSearchInput(input) {
        return input.replace(/[^a-zA-Z\s\-]/g, '');
    }

    // Enhanced validation function
    function validateRole(role) {
        let isValid = true;
        const errors = {};

        if (!role) { 
            errors.role = 'Role name is required';
            isValid = false; 
        } else if (role.length > 100) {
            errors.role = 'Role name must be less than 100 characters';
            isValid = false;
        } else if (!/^[a-zA-Z\s\-]+$/.test(role)) {
            errors.role = 'Role name can only contain letters, spaces, and hyphens';
            isValid = false;
        } else if (role.trim().length === 0) {
            errors.role = 'Role name cannot be only spaces';
            isValid = false;
        }

        return { isValid, errors };
    }


    // Add input validation for role fields
    $('#role').on('input', function() {
        const sanitizedValue = validateRoleInput($(this).val());
        $(this).val(sanitizedValue);
        
        // Update validation feedback
        const role = $(this).val();
        const validation = validateRole(role);
        updateValidationFeedback(this, validation.isValid);
    });

    $('#erole').on('input', function() {
        const sanitizedValue = validateRoleInput($(this).val());
        $(this).val(sanitizedValue);
        
        // Update validation feedback
        const role = $(this).val();
        const validation = validateRole(role);
        updateValidationFeedback(this, validation.isValid);
    });

    // Add keydown event to prevent invalid keys
    $('#role').on('keydown', preventInvalidKeys);
    $('#erole').on('keydown', preventInvalidKeys);

    // Add search input validation for DataTable
    const searchInput = $('.dataTables_filter input');
    if (searchInput.length) {
        searchInput.attr('placeholder', '');
        
        searchInput.on('input', function() {
            const sanitizedValue = sanitizeSearchInput($(this).val());
            $(this).val(sanitizedValue);
            
            // Trigger search with sanitized value
            if ($(this).val() !== sanitizedValue) {
                dataTable.search(sanitizedValue).draw();
            }
        });
        
        searchInput.on('keydown', function(event) {
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13, 46].includes(event.keyCode) ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (event.ctrlKey === true && [65, 67, 86, 88].includes(event.keyCode)) ||
                // Allow: home, end, left, right
                (event.keyCode >= 35 && event.keyCode <= 39)) {
                return;
            }
            
            // Prevent numbers and special characters except spaces and hyphens
            const char = String.fromCharCode(event.keyCode || event.which);
            if (!/^[a-zA-Z\s\-]$/.test(char)) {
                event.preventDefault();
                return false;
            }
        });
    }

    // Reset form function
    function resetForm() {
        $('.error-message').text('');
        $('#roleForm')[0].reset();
        // Reset validation feedback
        $('#role').removeClass('is-valid invalid');
        $('.valid-feedback, .invalid-feedback').hide();
    }

    // Display validation errors
    function displayErrors(errors, prefix = '') {
        $('.error-message').text('');
        Object.keys(errors).forEach(key => {
            $(`#${prefix}${key}-error`).text(errors[key]);
        });
    }

    // ==============
    // CREATE (ADD ROLE)
    // ==============
    $('#roleForm').submit(function(e) {
        e.preventDefault();
        
        const role = $('#role').val().trim();

        // Validation
        const validation = validateRole(role);
        if (!validation.isValid) {
            displayErrors(validation.errors);
            return;
        }

        // Show loading state
        $('#btn-role').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-role').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_role",
            data: { role: role },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-role').html('Save');
                $('#btn-role').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#roleModal').modal('hide');
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
                $('#btn-role').html('Save');
                $('#btn-role').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                // Try to parse error response
                let errorMessage = 'An error occurred while processing your request';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    // If not JSON, use default message
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        });
    });

    // ==========
    // READ (EDIT ROLE)
    // ==========
    $(document).on('click', '.e_role_id', function() {
        const id = $(this).data('id');
        const role = $(this).attr('role');

        // Populate edit form
        $('#edit_roleid').val(id);
        $('#erole').val(role);
        
        // Clear previous errors and validation feedback
        $('.error-message').text('');
        $('#erole').removeClass('is-valid invalid');
        $('.valid-feedback, .invalid-feedback').hide();
        
        // Show modal
        $('#editRoleModal').modal('show');
    });

    // ==========
    // UPDATE ROLE
    // ==========
    $('#editRoleForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_roleid').val();
        const role = $('#erole').val().trim();

        // Validation
        const validation = validateRole(role);
        if (!validation.isValid) {
            displayErrors(validation.errors, 'e');
            return;
        }

        // Show loading state
        $('#btn-editrole').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#btn-editrole').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_role",
            data: { 
                id: id,
                role: role
            },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editrole').html('Update');
                $('#btn-editrole').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#editRoleModal').modal('hide');
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
                $('#btn-editrole').html('Update');
                $('#btn-editrole').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                // Try to parse error response
                let errorMessage = 'An error occurred while processing your request';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    // If not JSON, use default message
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        });
    });

    // ==========
    // DELETE ROLE
    // ==========
    $(document).on('click', '.d_role_id', function() {
        const $button = $(this);
        const id = $button.data('id');
        const roleName = $button.attr('role');
        
        Swal.fire({
            title: 'Delete Role?',
            text: `Are you sure you want to delete "${roleName}"?`,
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
                
                $.ajax({
                    type: 'POST',
                    url: 'transac.php?action=delete_role',
                    data: { 
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Remove row from DataTable
                            dataTable.row($button.closest('tr')).remove().draw();
                            
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
                                title: 'Cannot Delete Role',
                                text: response.message || 'Failed to delete role',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('XHR Response:', xhr.responseText);
                        console.log('Status:', status);
                        console.log('Error:', error);
                        
                        let errorMessage = 'An error occurred while deleting the role';
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMessage = errorResponse.message;
                            }
                        } catch (e) {
                            // If not JSON, show the raw response
                            errorMessage = xhr.responseText || errorMessage;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: `<div style="text-align: left;">
                                   <strong>Error Details:</strong><br>
                                   <small>${errorMessage}</small>
                                   </div>`,
                            confirmButtonText: 'OK'
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
    $('#roleModal').on('hidden.bs.modal', function() {
        resetForm();
    });
    
    $('#editRoleModal').on('hidden.bs.modal', function() {
        $('.error-message').text('');
        $('#erole').removeClass('is-valid invalid');
        $('.valid-feedback, .invalid-feedback').hide();
    });
});
</script>
</body>
</html>