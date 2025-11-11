<?php
 session_start();
 include '../connection.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Visitor Cards</title>
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

        /* Add Visitor Button */
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

        /* Loading spinner */
        .spinner-border {
            width: 1rem;
            height: 1rem;
        }

        /* DataTables Search Input Styling */
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
            font-weight: 600;
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
                    <div class="col-sm-12 col-xl-12">
                        <div class="bg-light rounded h-100 p-4">
                            <div class="row">
                                <div class="col-9">
                                    <h6 class="mb-4">Manage Visitor Cards</h6>
                                </div>
                                <div class="col-3 d-flex justify-content-end">
                                    <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#visitorModal">
                                        <i class="fas fa-plus-circle"></i> Add Visitor Card
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-border" id="myDataTable">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="text-align:left;">ID Number</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php include '../connection.php'; ?>
                                        <?php $results = mysqli_query($db, "SELECT * FROM visitor"); ?>
                                        <?php while ($row = mysqli_fetch_array($results)) { ?>
                                        <tr class="table-<?php echo $row['id'];?>">
                                            <td style="text-align:left;" class="rfid_number"><?php echo $row['rfid_number']; ?></td>
                                            <td width="14%">
                                                <div class="action-buttons">
                                                    <button rfid_number="<?php echo $row['rfid_number'];?>" 
                                                            data-id="<?php echo $row['id'];?>" 
                                                            class="btn btn-sm btn-edit e_visitor_id">
                                                        <i class="fas fa-edit"></i> Edit 
                                                    </button>
                                                    <button rfid_number="<?php echo $row['rfid_number'];?>" 
                                                            data-id="<?php echo $row['id']; ?>" 
                                                            class="btn btn-sm btn-delete d_visitor_id">
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
            </div>

            <!-- Add Visitor Modal -->
            <div class="modal fade" id="visitorModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Visitor Card
                            </h5>
                            <button type="button" onclick="resetForm()" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="visitorForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-visitor"></div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>ID Number: </b></label>
                                        <input name="rfid_number" type="text" id="rfid_number" class="form-control" 
                                               autocomplete="off" placeholder="0000-0000" 
                                               title="Enter ID in format: 0000-0000" required
                                               maxlength="9">
                                        <span class="visitor-error" id="visitor-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="resetForm()" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-save" id="btn-visitor">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Visitor Modal -->
            <div class="modal fade" id="editVisitorModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Visitor Card
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editVisitorForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editvisitor"></div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>ID Number: </b></label>
                                        <input name="rfid_number" type="text" id="erfid_number" class="form-control e-id" 
                                               autocomplete="off" placeholder="0000-0000"
                                               maxlength="9">
                                        <span class="evisitor-error" id="evisitor-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="id" id="edit_visitorid">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-update" id="btn-editvisitor">Update</button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    

    <!-- Visitor CRUD JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable with search restrictions
        var table = $('#myDataTable').DataTable({
            order: [[0, 'desc']],
            language: {
                search: "Search ID Number:"
            }
        });

        // Restrict search input to numbers and hyphen only
        $('.dataTables_filter input').on('input', function() {
            var value = $(this).val();
            // Remove any non-digit and non-hyphen characters
            var filteredValue = value.replace(/[^\d-]/g, '');
            
            // If value was changed, update the input and trigger search
            if (value !== filteredValue) {
                $(this).val(filteredValue);
                table.search(filteredValue).draw();
            }
        });

        // Prevent paste of non-numeric characters
        $('.dataTables_filter input').on('paste', function(e) {
            var pasteData = e.originalEvent.clipboardData.getData('text');
            // Only allow paste if it contains only numbers and hyphens
            if (!/^[\d-]*$/.test(pasteData)) {
                e.preventDefault();
                // Optional: Show a message to user
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Input',
                    text: 'Only numbers and hyphens are allowed in search',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });

        // Format ID number input (0000-0000 format)
        document.getElementById('erfid_number').addEventListener('input', function(e) {
            formatIDNumber(this);
        });
        
        document.getElementById('rfid_number').addEventListener('input', function(e) {
            formatIDNumber(this);
        });

        // Helper function to format ID number as 0000-0000
        function formatIDNumber(input) {
            let value = input.value.replace(/[^\d]/g, '');
            
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            
            input.value = value;
        }

        // Helper function to reset form
        function resetForm() {
            document.getElementById('visitor-error').innerHTML = '';
            document.getElementById('evisitor-error').innerHTML = '';
            document.getElementById('visitorForm').reset();
        }

        // Validate ID number format (0000-0000)
        function validateIDNumber(idNumber) {
            const idRegex = /^\d{4}-\d{4}$/;
            return idRegex.test(idNumber);
        }

        // ==============
        // CREATE (ADD VISITOR CARD)
        // ==============
        $('#btn-visitor').click(function() {
            // Get and trim input value
            var rfid_number = $('#rfid_number').val().trim();
            var $btn = $(this);
            var $errorField = $('#visitor-error');
            
            // Reset previous errors
            $errorField.text('');
            
            // Validate input
            if (!rfid_number) {
                $errorField.text('ID number is required');
                $('#rfid_number').focus();
                return;
            }
            
            if (!validateIDNumber(rfid_number)) {
                $errorField.text('ID must be in format: 0000-0000');
                $('#rfid_number').focus();
                return;
            }
            
            // Show loading state
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
            $btn.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                type: "POST",
                url: "transac.php?action=add_visitor",
                data: { rfid_number: rfid_number },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Reset form and close modal
                            $('#visitorForm')[0].reset();
                            $('#visitorModal').modal('hide');
                            // Refresh table
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
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while processing your request'
                    });
                },
                complete: function() {
                    // Reset button state
                    $btn.html('Save');
                    $btn.prop('disabled', false);
                }
            });
        });

        // ==========
        // READ (EDIT)
        // ==========
        $(document).on('click', '.e_visitor_id', function() {
            var id = $(this).data('id');
            var rfid_number = $(this).attr('rfid_number');
            
            $('#erfid_number').val(rfid_number);
            $('#edit_visitorid').val(id);
            $('#editVisitorModal').modal('show');
        });

        // ==========
        // UPDATE
        // ==========
        $('#btn-editvisitor').click(function() {
            var inputField = document.getElementById('erfid_number');
            var rfid_number = $('#erfid_number').val().trim();
            
            // Validate input
            if (!rfid_number) {
                document.getElementById('evisitor-error').innerHTML = 'ID number is required';
                inputField.focus();
                return;
            } else if (!validateIDNumber(rfid_number)) {
                document.getElementById('evisitor-error').innerHTML = 'ID must be in format: 0000-0000';
                inputField.focus();
                return;
            } else {
                document.getElementById('evisitor-error').innerHTML = '';
            }

            var id = $('#edit_visitorid').val();
            
            // Show loading state
            $(this).html('<span class="spinner-border spinner-border-sm"></span>');
            $(this).prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "transac.php?action=update_visitor",
                data: { 
                    id: id,
                    rfid_number: rfid_number 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Update the table row without reloading
                            $('.table-' + id + ' .rfid_number').text(rfid_number);
                            $('.table-' + id + ' .btn-edit').attr('rfid_number', rfid_number);
                            $('.table-' + id + ' .btn-del').attr('rfid_number', rfid_number);
                            $('#editVisitorModal').modal('hide');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                    $('#btn-editvisitor').html('Update');
                    $('#btn-editvisitor').prop('disabled', false);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while processing your request'
                    });
                    $('#btn-editvisitor').html('Update');
                    $('#btn-editvisitor').prop('disabled', false);
                }
            });
        });

        // ==========
        // DELETE
        // ==========
        $(document).on('click', '.d_visitor_id', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var rfid_number = $(this).attr('rfid_number');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete visitor card: ${rfid_number}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    var $btn = $(this);
                    $btn.html('<span class="spinner-border spinner-border-sm"></span>');
                    $btn.prop('disabled', true);
                    
                    $.ajax({
                        type: 'POST',
                        url: 'transac.php?action=delete_visitor',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                                $btn.html('<i class="fas fa-trash"></i> Delete');
                                $btn.prop('disabled', false);
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'An error occurred while processing your request', 'error');
                            $btn.html('<i class="fas fa-trash"></i> Delete');
                            $btn.prop('disabled', false);
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>