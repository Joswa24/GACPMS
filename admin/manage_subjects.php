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
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
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

        /* Add Subject Button */
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

        /* Subject icon styling */
        .subject-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
            background: linear-gradient(135deg, var(--icon-color), #4a7ec7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 8px rgba(92, 149, 233, 0.3);
        }

        /* Section header styling */
        .section-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border-left: 4px solid var(--icon-color);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        /* Error message styling */
        .error-message {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
                                <h6 class="mb-4">Manage Subjects</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#subjectModal">
                                    <i class="fas fa-plus-circle"></i> Add Subject
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Icon</th>
                                        <th scope="col">Subject Code</th>
                                        <th scope="col">Subject Name</th>
                                        <th scope="col">Year Level</th>
                                        <th scope="col">Action</th>
                                        <th style="display: none;">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $results = mysqli_query($db, "SELECT * FROM subjects ORDER BY id DESC"); 
                                    
                                    if ($results === false) {
                                        die("Query failed: " . mysqli_error($db));
                                    }
                                    ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-subject-id="<?php echo $row['id'];?>">
                                        <input class="subject_code" type="hidden" value="fas fa-book<?php echo $row['subject_code']; ?>" />
                                        <input class="subject_name" type="hidden" value="<?php echo $row['subject_name']; ?>" />
                                        <input class="year_level" type="hidden" value="<?php echo $row['year_level']; ?>" />
                                        <?php if (isset($row['date_added'])): ?>
                                        <input class="date_added" type="hidden" value="<?php echo $row['date_added']; ?>" />
                                        <?php endif; ?>

                                        <td>
                                            <center>
                                                <div class="subject-icon">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            </center>
                                        </td>
                                        <td class="subject_code_display"><?php echo $row['subject_code']; ?></td>
                                        <td><?php echo $row['subject_name']; ?></td>
                                        <td><?php echo $row['year_level']; ?></td>
                                        <td width="14%">
                                            <div class="action-buttons">
                                                <button data-id="<?php echo $row['id'];?>" 
                                                        class="btn btn-sm btn-edit e_subject_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button subject_name="<?php echo $row['subject_name']; ?>" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-sm btn-delete d_subject_id">
                                                    <i class="fas fa-trash"></i> Delete 
                                                </button>
                                            </div>
                                        </td>
                                        <?php if (isset($row['date_added'])): ?>
                                        <td style="display:none;" class="hidden-date"><?php echo $row['date_added']; ?></td>
                                        <?php else: ?>
                                        <td style="display:none;" class="hidden-date"><?php echo date('Y-m-d H:i:s'); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Subject Modal -->
            <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Subject
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="subjectForm" role="form" method="post" action="">
                            <div class="modal-body">
                                <div class="col-lg-11 mb-2 mt-1" id="mgs-subject" style="margin-left: 4%"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-subject"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SUBJECT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="subject-icon mx-auto mb-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            </div>

                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="subject_code"><b>Subject Code:</b></label>
                                                    <input required type="text" class="form-control" name="subject_code" id="subject_code" autocomplete="off">
                                                    <span class="error-message" id="subject_code-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="subject_name"><b>Subject Name:</b></label>
                                                    <input required type="text" class="form-control" name="subject_name" id="subject_name" autocomplete="off">
                                                    <span class="error-message" id="subject_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="year_level"><b>Year Level:</b></label>
                                                    <select required class="form-control" name="year_level" id="year_level" autocomplete="off">
                                                        <option value="">Select Year Level</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="year_level-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-subject" class="btn btn-save">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Subject Modal -->
            <div class="modal fade" id="editsubjectModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Subject
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editSubjectForm" class="edit-form" role="form" method="post" action="">
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-11 mb-2 mt-1" id="mgs-editsubject" style="margin-left: 4%"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editsubject"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SUBJECT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="subject-icon mx-auto mb-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            </div>
                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="esubject_code"><b>Subject Code:</b></label>
                                                    <input required type="text" class="form-control edit-subjectcode" name="subject_code" id="esubject_code" autocomplete="off">
                                                    <span class="error-message" id="esubject_code-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="esubject_name"><b>Subject Name:</b></label>
                                                    <input type="text" class="form-control edit-subjectname" name="subject_name" id="esubject_name" autocomplete="off">
                                                    <span class="error-message" id="esubject_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="eyear_level"><b>Year Level:</b></label>
                                                    <select class="form-control" name="year_level" id="eyear_level" autocomplete="off">
                                                        <option class="edit-yearlevel-val" value=""></option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                        <option value="5th Year">5th Year</option>
                                                    </select>
                                                    <span class="error-message" id="eyear_level-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_subjectid" name="subject_id" class="edit-id">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editsubject" class="btn btn-update">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="delsubject-modal" tabindex="-1" aria-labelledby="delsubjectModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="delsubjectModalLabel">
                                <i class="fas fa-trash"></i> Delete Subject
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="delete-form">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-delsubject"></div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="delete_subjectname"><b>Subject Name:</b></label>
                                        <input type="text" id="delete_subjectname" class="form-control d-subj" autocomplete="off" readonly="">
                                        <span class="error-message"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="" id="delete_subjectid">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">No</button>
                                <button type="button" class="btn btn-confirm remove_id" id="btn-delsubject">Yes</button>
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
            order: [[5, 'desc']],
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
            document.getElementById('subject_code-error').innerHTML = '';
            document.getElementById('subject_name-error').innerHTML = '';
            document.getElementById('year_level-error').innerHTML = '';
            document.getElementById('subjectForm').reset();
        }

        // ==============
        // CREATE (ADD)
        // ==============
        $('#subjectForm').submit(function(e) {
            e.preventDefault();
            
            var inputField = document.getElementById('subject_code');
            var inputField1 = document.getElementById('subject_name');
            var inputField2 = document.getElementById('year_level');

            // Validate inputs
            if (!validateInput(inputField, 'subject_code-error', 'Subject code is required') || 
                !validateInput(inputField1, 'subject_name-error', 'Subject name is required') ||
                !validateInput(inputField2, 'year_level-error', 'Year level is required')) {
                return;
            }

            var subject_code = $('#subject_code').val();
            var subject_name = $('#subject_name').val();
            var year_level = $('#year_level').val();
            
            // Show loading state
            $('#btn-subject').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-subject').prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_subject",
                data: { subject_code: subject_code, subject_name: subject_name, year_level: year_level },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-subject').html('Save');
                    $('#btn-subject').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            $('#subjectModal').modal('hide');
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
                    $('#btn-subject').html('Save');
                    $('#btn-subject').prop('disabled', false);
                    
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
        $(document).on('click', '.e_subject_id', function() {
            var id = $(this).data('id');
            var subject_code = $(this).closest('tr').find('.subject_code_display').text();
            var subject_name = $(this).closest('tr').find('.subject_name').val();
            var year_level = $(this).closest('tr').find('.year_level').val();
            
            $('#esubject_code').val(subject_code);
            $('#esubject_name').val(subject_name);
            $('#eyear_level').val(year_level);
            $('#edit_subjectid').val(id);
            $('#editsubjectModal').modal('show');
        });

        // ==========
        // UPDATE
        // ==========
        $('#btn-editsubject').click(function(e) {
            e.preventDefault();
            var inputField = document.getElementById('esubject_code');
            var inputField1 = document.getElementById('esubject_name');
            var inputField2 = document.getElementById('eyear_level');

            // Validate inputs
            if (!validateInput(inputField, 'esubject_code-error', 'Subject code is required') || 
                !validateInput(inputField1, 'esubject_name-error', 'Subject name is required') ||
                !validateInput(inputField2, 'eyear_level-error', 'Year level is required')) {
                return;
            }

            var id = $('#edit_subjectid').val();
            var subject_code = $('#esubject_code').val();
            var subject_name = $('#esubject_name').val();
            var year_level = $('#eyear_level').val();
            
            // Show loading state
            $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $(this).prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "transac.php?action=update_subject",
                data: { id: id, subject_code: subject_code, subject_name: subject_name, year_level: year_level },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editsubject').html('Update');
                    $('#btn-editsubject').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            $('#editsubjectModal').modal('hide');
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
                    $('#btn-editsubject').html('Update');
                    $('#btn-editsubject').prop('disabled', false);
                    
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
        $(document).on('click', '.d_subject_id', function() {
            var id = $(this).data('id');
            var name = $(this).attr('subject_name');
            
            $('#delete_subjectname').val(name);
            $('#delete_subjectid').val(id);
            $('#delsubject-modal').modal('show');
        });

        // Handle the actual deletion when "Yes" is clicked in the modal
        $(document).on('click', '#btn-delsubject', function() {
            var id = $('#delete_subjectid').val();
            
            // Show loading indicator
            $('#btn-delsubject').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
            $('#btn-delsubject').prop('disabled', true);
            
            $.ajax({
                type: 'POST',
                url: 'transac.php?action=delete_subject',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-delsubject').html('Yes');
                    $('#btn-delsubject').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        // Close the modal
                        $('#delsubject-modal').modal('hide');
                        
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
                    $('#btn-delsubject').html('Yes');
                    $('#btn-delsubject').prop('disabled', false);
                    
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
        $('#subjectModal').on('hidden.bs.modal', function () {
            resetForm();
        });
    });
    </script>
</body>
</html>