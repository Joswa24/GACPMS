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
<?php include '../connection.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

        /* Add Department Button */
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

        .btn-confirm {
            background: linear-gradient(135deg, var(--danger-color), #d73525);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 74, 59, 0.3);
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(231, 74, 59, 0.4);
            color: white;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.875rem;
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

        /* SweetAlert customization */
        .swal2-popup {
            border-radius: var(--border-radius) !important;
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
                                <h6 class="mb-4">Manage Department</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#departmentModal">
                                    <i class="fas fa-plus-circle"></i> Add Department
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Department Name</th>
                                        <th scope="col">Department Description</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $results = mysqli_query($db, "SELECT * FROM department"); ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['department_id'];?>">
                                        <td><?php echo $row['department_name']; ?></td>
                                        <td><?php echo $row['department_desc']; ?></td>
                                        <td width="14%">
                                            <div class="action-buttons">
                                                <button department_name="<?php echo $row['department_name'];?>" 
                                                        department_desc="<?php echo $row['department_desc'];?>" 
                                                        data-id="<?php echo $row['department_id'];?>" 
                                                        class="btn btn-sm btn-edit e_department_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button department_name="<?php echo $row['department_name'];?>" 
                                                        department_desc="<?php echo $row['department_desc'];?>"  
                                                        data-id="<?php echo $row['department_id']; ?>" 
                                                        class="btn btn-sm btn-delete d_department_id">
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

                <!-- Add Department Modal -->
                <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">
                                    <i class="fas fa-plus-circle"></i> New Department
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="col-lg-11 mb-2 mt-1" id="mgs-dept" style="margin-left: 4%"></div>
                            <form id="departmentForm">
                                <div class="modal-body">
                                    <div class="col-lg-12 mb-1">
                                        <div class="form-group">
                                            <label for="inputTime"><b>Department Name:</b></label>
                                            <input name="department_name" type="text" id="department_name" class="form-control" autocomplete="off">
                                            <span class="deptname-error" id="deptname-error" style="color:red;font-size:10px;"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="inputTime"><b>Department Description: </b></label>
                                            <textarea name="department_desc" type="text" id="department_description" class="form-control" autocomplete="off"></textarea>
                                            <span class="deptname-desc" id="deptname-desc" style="color:red;font-size:10px;"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-save" id="btn-department">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Department Modal -->
                <div class="modal fade" id="editdepartment-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-edit"></i> Edit Department
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editdept"></div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department Name:</b></label>
                                        <input name="department_name" type="text" id="edit_departmentname" class="form-control edit-name" autocomplete="off">
                                        <span class="deptname-error" id="edeptname-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department Description: </b></label>
                                        <textarea name="department_desc" type="text" id="edit_departmentdescription" class="form-control edit-desc" autocomplete="off"></textarea>
                                        <span class="deptname-error" id="edeptname-desc" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="" id="edit_departmentid">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-update" id="btn-editdepartment">Update</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Department Modal -->
                <div class="modal fade" id="deldepartment-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-trash"></i> Delete Department
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" id="delete-form">
                                <div class="modal-body">
                                    <div class="col-lg-12 mt-1" id="mgs-deldept"></div>
                                    <div class="col-lg-12 mb-1">
                                        <div class="form-group">
                                            <label for="inputTime"><b>Department Name:</b></label>
                                            <input type="text" id="delete_departmentname" class="form-control d-dpt" autocomplete="off" readonly="">
                                            <span class="deptname-error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="" id="delete_departmentid">
                                    <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">No</button>
                                    <button type="button" class="btn btn-confirm remove_id" id="btn-deldepartment">Yes</button>
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

    // Helper function to validate inputs
    function validateInput(input, errorId, message) {
        if (input.value === '') {
            document.getElementById(errorId).innerHTML = message;
            input.focus();
            return false;
        } else {
            document.getElementById(errorId).innerHTML = '';
            return true;
        }
    }

    // Helper function to reset form
    function resetForm() {
        document.getElementById('deptname-error').innerHTML = '';
        document.getElementById('deptname-desc').innerHTML = '';
        document.getElementById('departmentForm').reset();
    }

    // ==============
    // CREATE (ADD)
    // ==============
    $('#departmentForm').submit(function(e) {
        e.preventDefault();
        
        var inputField = document.getElementById('department_name');
        var inputField1 = document.getElementById('department_description');

        // Validate inputs
        if (!validateInput(inputField, 'deptname-error', 'Department name is required') || 
            !validateInput(inputField1, 'deptname-desc', 'Description is required')) {
            return;
        }

        var dptname = $('#department_name').val();
        var dptdesc = $('#department_description').val();
        
        // Show loading state
        $('#btn-department').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-department').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_department",
            data: { dptname: dptname, dptdesc: dptdesc },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-department').html('Save');
                $('#btn-department').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#departmentModal').modal('hide');
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
                $('#btn-department').html('Save');
                $('#btn-department').prop('disabled', false);
                
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
    // READ (EDIT)
    // ==========
    $(document).on('click', '.e_department_id', function() {
        var id = $(this).data('id');
        var name = $(this).attr('department_name');
        var desc = $(this).attr('department_desc');
        
        $('#edit_departmentname').val(name);
        $('#edit_departmentdescription').val(desc);
        $('#edit_departmentid').val(id);
        $('#editdepartment-modal').modal('show');
    });

    // ==========
    // UPDATE
    // ==========
    $('#btn-editdepartment').click(function(e) {
        e.preventDefault();
        var inputField = document.getElementById('edit_departmentname');
        var inputField1 = document.getElementById('edit_departmentdescription');

        // Validate inputs
        if (!validateInput(inputField, 'edeptname-error', 'Department name is required') || 
            !validateInput(inputField1, 'edeptname-desc', 'Description is required')) {
            return;
        }

        var id = $('#edit_departmentid').val();
        var dptname = $('#edit_departmentname').val();
        var dptdesc = $('#edit_departmentdescription').val();
        
        // Show loading state
        $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $(this).prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_department",
            data: { id: id, dptname: dptname, dptdesc: dptdesc },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editdepartment').html('Update');
                $('#btn-editdepartment').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#editdepartment-modal').modal('hide');
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
                $('#btn-editdepartment').html('Update');
                $('#btn-editdepartment').prop('disabled', false);
                
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
    // DELETE
    // ==========
    $(document).on('click', '.d_department_id', function() {
        var id = $(this).data('id');
        var name = $(this).attr('department_name');
        
        $('#delete_departmentname').val(name);
        $('#delete_departmentid').val(id);
        $('#deldepartment-modal').modal('show');
    });

    // Handle the actual deletion when "Yes" is clicked in the modal
    $(document).on('click', '#btn-deldepartment', function() {
        var id = $('#delete_departmentid').val();
        
        // Show loading indicator
        $('#btn-deldepartment').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
        $('#btn-deldepartment').prop('disabled', true);
        
        $.ajax({
            type: 'POST',
            url: 'transac.php?action=delete_department',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-deldepartment').html('Yes');
                $('#btn-deldepartment').prop('disabled', false);
                
                if (response.status === 'success') {
                    // Close the modal
                    $('#deldepartment-modal').modal('hide');
                    
                    // Remove the row from the table
                    dataTable.row($('.table-' + id)).remove().draw();
                    
                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Reset button state
                $('#btn-deldepartment').html('Yes');
                $('#btn-deldepartment').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred: ' + error,
                    icon: 'error'
                });
            }
        });
    });

    // Reset modal when closed
    $('#departmentModal').on('hidden.bs.modal', function () {
        resetForm();
    });
    });
    </script>
</body>
</html>