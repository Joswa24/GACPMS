<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

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
date_default_timezone_set('Asia/Manila');

// Function to send JSON response
function jsonResponse($status, $message, $data = []) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Function to format ID with hyphen
// Function to format ID with hyphen (for display consistency)
function formatID($id) {
    // If it's already in 0000-0000 format, return as is
    if (preg_match('/^\d{4}-\d{4}$/', $id)) {
        return $id;
    }
    // If it's 8 digits without hyphen, format it
    if (strlen($id) == 8 && is_numeric($id)) {
        return substr($id, 0, 4) . '-' . substr($id, 4, 4);
    }
    return $id; // Return original if not matching expected formats
}

// Function is no longer needed for cleaning, but keep for backward compatibility
function cleanID($id) {
    // Return the ID as is since we're storing with hyphens now
    return $id;
}

// Simple personnel photo display function
function getPersonnelPhoto($photo) {
    $basePath = '../uploads/personell/'; // Added missing slash
    $defaultPhoto = '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg';

    // If no photo or file does not exist → return default
    if (empty($photo) || $photo === 'default.png' || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Personnel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Add Personnel Button */
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

        .personnel-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }

        .upload-img-btn {
            cursor: pointer;
            display: block;
            position: relative;
        }

        .preview-1 {
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .preview-1:hover {
            opacity: 0.8;
        }

        .file-uploader {
            position: relative;
            margin-bottom: 15px;
        }

        .section-header {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
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

        /* Validation styling */
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: var(--danger-color);
            background-image: none;
        }

        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
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
                                <h6 class="mb-4">Manage Personnel</h6>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#personnelModal">
                                    <i class="fas fa-plus-circle"></i> Add Personnel
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Photo</th>
                                        <th scope="col">ID Number</th>
                                        <th scope="col">Full Name</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Action</th>
                                        <th style="display: none;">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $results = mysqli_query($db, "SELECT * FROM personell where deleted = 0 ORDER BY date_added DESC"); 
                                    if ($results === false) {
                                        die("Query failed: " . mysqli_error($db));
                                    }
                                    while ($row = mysqli_fetch_array($results)) { 
                                        $photoPath = getPersonnelPhoto($row['photo']);
                                    ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-personnel-id="<?php echo $row['id'];?>">
                                        <input class="id_raw" type="hidden" value="<?php echo $row['id_number']; ?>" />
                                        <input class="role_val" type="hidden" value="<?php echo $row['role']; ?>" />
                                        <input class="last_name" type="hidden" value="<?php echo $row['last_name']; ?>" />
                                        <input class="first_name" type="hidden" value="<?php echo $row['first_name']; ?>" />
                                        <input class="date_of_birth" type="hidden" value="<?php echo $row['date_of_birth']; ?>" />
                                        <input class="categ_val" type="hidden" value="<?php echo $row['category']; ?>" />
                                        <input class="status_val" type="hidden" value="<?php echo $row['status']; ?>" />
                                        <input class="department_val" type="hidden" value="<?php echo $row['department']; ?>" />
                                
                                        <td>
                                            <center>
                                                <div class="photo-preview-container">
                                                    <img class="photo personnel-photo" src="<?php echo $photoPath; ?>" 
                                                        onerror="this.onerror=null; this.src='../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg';"
                                                        alt="<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>"
                                                        style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6;">
                                                </div>
                                            </center>
                                        </td>
                                        <td class="id"><?php echo formatID($row['id_number']); ?></td>
                                        <td><?php echo $row['first_name'] .' '.$row['last_name']; ?></td>
                                        <td><?php echo $row['role']; ?></td>
                                        <td><?php echo $row['category']; ?></td>
                                        <td><?php echo $row['department']; ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Active') {
                                                echo '<span class="badge bg-success">Active</span>';
                                            } else {
                                                echo '<span class="badge bg-danger">Blocked</span>';
                                            } ?>
                                        </td>
                                        <td width="14%">
                                            <div class="action-buttons">
                                                <button data-id="<?php echo $row['id'];?>" class="btn btn-sm btn-edit e_user_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button user_name="<?php echo $row['first_name'] . ' ' . $row['last_name']; ?>" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-sm btn-delete d_user_id">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                        <td style="display:none;" class="hidden-date"><?php echo $row['date_added']; ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Personnel Modal -->
            <div class="modal fade" id="personnelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Personnel
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="personnelForm" role="form" method="post" action="" enctype="multipart/form-data" novalidate>
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-personnel"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-personnel"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>PERSONAL INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label for="photo" class="upload-img-btn" style="cursor: pointer;">
                                                        <img class="preview-1" src="../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg"
                                                            style="width: 140px!important; height: 130px!important; position: absolute; border: 1px solid gray; top: 25%;"
                                                            title="Upload Photo.." />
                                                    </label>
                                                    <input type="file" id="photo" name="photo" class="upload-field-1" 
                                                            style="opacity: 0; position: absolute; z-index: -1;" accept="image/*">
                                                    <div class="invalid-feedback" id="photo-error"></div>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="role"><b>ROLE:</b></label>
                                                    <select required class="form-select dept_ID" name="role" id="role" autocomplete="off">
                                                        <?php
                                                            $sql = "SELECT * FROM role WHERE role != 'Instructor'";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $role = $row['role'];
                                                                if ($role === 'Student') {
                                                                    echo "<option value='$role' selected>$role</option>";
                                                                } else {
                                                                    echo "<option value='$role'>$role</option>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="role-error"></div>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="lnamez">
                                                <div class="form-group">
                                                    <label for="category"><b>CATEGORY:</b></label>
                                                    <select required class="form-select" name="category" id="category" autocomplete="off">
                                                        <!-- Category options will be populated by JavaScript -->
                                                    </select>
                                                    <div class="invalid-feedback" id="category-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="last_name"><b>LAST NAME:</b></label>
                                                    <input required type="text" class="form-control" name="last_name" id="last_name" autocomplete="off" pattern="[A-Za-z\s]+" title="Last name should only contain letters and spaces">
                                                    <div class="invalid-feedback" id="last_name-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="first_name"><b>FIRST NAME:</b></label>
                                                    <input required type="text" class="form-control" name="first_name" id="first_name" autocomplete="off" pattern="[A-Za-z\s]+" title="First name should only contain letters and spaces">
                                                    <div class="invalid-feedback" id="first_name-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="date_of_birth"><b>DATE OF BIRTH:</b></label>
                                                    <input required type="date" class="form-control" name="date_of_birth" id="date_of_birth" autocomplete="off" max="<?php echo date('Y-m-d'); ?>">
                                                    <div class="invalid-feedback" id="date_of_birth-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="department"><b>DEPARTMENT:</b></label>
                                                    <select required class="form-select" name="department" id="department" autocomplete="off">
                                                        <?php
                                                            $sql = "SELECT * FROM department";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $department_name = $row['department_name'];
                                                                echo "<option value='$department_name'>$department_name</option>";
                                                            }
                                                        ?>            
                                                    </select>
                                                    <div class="invalid-feedback" id="department-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="id_number"><b>ID NUMBER:</b></label>
                                                    <input required type="text" class="form-control" name="id_number" id="id_number" autocomplete="off" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="ID number must be in format 0000-0000">
                                                    <div class="invalid-feedback" id="id_number-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="status"><b>STATUS:</b></label>
                                                    <input type="text" class="form-control" name="status" id="status" value="Active" autocomplete="off" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-personnel" class="btn btn-save">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Personnel Modal -->
            <div class="modal fade" id="editpersonnelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Personnel
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editPersonnelForm" class="edit-form" role="form" method="post" action="" enctype="multipart/form-data" novalidate>
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-12 mt-1" id="mgs-editpersonnel"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editpersonnel"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>PERSONAL INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label name="upload-label" class="upload-img-btn">
                                                        <input type="file" id="editPhoto" name="photo" class="upload-field-1" style="display:none;" accept="image/*" title="Upload Photo.."/>
                                                        <input type="hidden" id="capturedImage" name="capturedImage" class="capturedImage">
                                                        <img class="preview-1 edit-photo" src="" style="width: 140px!important;height: 130px!important;position: absolute;border: 1px solid gray;top: 25%" title="Upload Photo.." />
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="erole"><b>ROLE:</b></label>
                                                    <select class="form-select dept_ID" name="role" id="erole" autocomplete="off" required>
                                                        <option class="edit-role-val" value=""></option>
                                                        <?php
                                                            $sql = "SELECT * FROM role WHERE role != 'Instructor'";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $role = $row['role'];
                                                                echo "<option value='$role'>$role</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <div class="invalid-feedback" id="erole-error"></div>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="lnamez">
                                                <div class="form-group">
                                                    <label for="ecategory"><b>CATEGORY:</b></label>
                                                    <select class="form-select" name="category" id="ecategory" autocomplete="off" required>
                                                        <option class="edit-categ-val" value=""></option>
                                                    </select>
                                                    <div class="invalid-feedback" id="ecategory-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="elast_name"><b>LAST NAME:</b></label>
                                                    <input type="text" class="form-control edit-lname" name="last_name" id="elast_name" autocomplete="off" required pattern="[A-Za-z\s]+" title="Last name should only contain letters and spaces">
                                                    <div class="invalid-feedback" id="elast_name-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="efirst_name"><b>FIRST NAME:</b></label>
                                                    <input type="text" class="form-control edit-fname" name="first_name" id="efirst_name" autocomplete="off" required pattern="[A-Za-z\s]+" title="First name should only contain letters and spaces">
                                                    <div class="invalid-feedback" id="efirst_name-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label for="edate_of_birth"><b>DATE OF BIRTH:</b></label>
                                                    <input type="date" class="form-control edit-dob" name="date_of_birth" id="edate_of_birth" autocomplete="off" required max="<?php echo date('Y-m-d'); ?>">
                                                    <div class="invalid-feedback" id="edate_of_birth-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="edepartment"><b>DEPARTMENT:</b></label>
                                                    <select class="form-select" name="department" id="edepartment" autocomplete="off" required>
                                                        <option class="edit-department"></option>
                                                        <?php
                                                            $sql = "SELECT * FROM department";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $department_name = $row['department_name'];
                                                                echo "<option value='$department_name'>$department_name</option>";
                                                            }
                                                        ?>            
                                                    </select>
                                                    <div class="invalid-feedback" id="edepartment-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="eid_number"><b>ID NUMBER:</b></label>
                                                    <input required type="text" class="form-control edit-idnumber" name="id_number" id="eid_number" autocomplete="off" placeholder="0000-0000" pattern="\d{4}-\d{4}" title="ID number must be in format 0000-0000">
                                                    <div class="invalid-feedback" id="eid_number-error"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label for="estatus"><b>STATUS:</b></label>
                                                    <select class="form-select" name="status" id="estatus" autocomplete="off" required>
                                                        <option class="edit-status1"></option>
                                                        <option value="Active">Active</option>
                                                        <option value="Block">Block</option>
                                                    </select>
                                                    <div class="invalid-feedback" id="estatus-error"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_personnelid" name="personnel_id" class="edit-id">
                                <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editpersonnel" class="btn btn-update">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top" style="background-color: #87abe0ff"><i class="bi bi-arrow-up" style="background-color: #87abe0ff"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/main.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var dataTable = $('#myDataTable').DataTable({
            order: [[8, 'desc']],
            stateSave: true
        });

        // Function to update category dropdown based on role selection
        function updateCategory() {
            var role = document.getElementById('role').value;
            var categorySelect = document.getElementById('category');
            
            // Clear the existing options
            categorySelect.innerHTML = '';

            if (role === 'Student') {
                // If the role is 'Student', show 'Student' only in category
                var option = document.createElement('option');
                option.value = 'Student';
                option.text = 'Student';
                categorySelect.appendChild(option);
            } else {
                // If the role is not 'Student', show 'Regular' and 'Contractual'
                var option1 = document.createElement('option');
                option1.value = 'Regular';
                option1.text = 'Regular';
                categorySelect.appendChild(option1);

                var option2 = document.createElement('option');
                option2.value = 'Contractual';
                option2.text = 'Contractual';
                categorySelect.appendChild(option2);
            }
        }

        // Function to update category dropdown for edit modal
        function updateCategory1(role) {
            const categoryDropdown = document.getElementById('ecategory');
            // Clear existing options
            categoryDropdown.innerHTML = '';

            if (role === 'Student') {
                const studentOption = new Option('Student', 'Student');
                categoryDropdown.add(studentOption);
            } else {
                const regularOption = new Option('Regular', 'Regular');
                const contractualOption = new Option('Contractual', 'Contractual');
                categoryDropdown.add(regularOption);
                categoryDropdown.add(contractualOption);
            }
        }

        // Initialize category dropdown on page load
        updateCategory();

        // Format ID number input as user types - ENHANCED VERSION
        $('#id_number, #eid_number').on('input', function() {
            var value = $(this).val().replace(/-/g, '');
            
            // Only allow numbers
            value = value.replace(/\D/g, '');
            
            // Format as 0000-0000 when user types
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            
            // Limit to 9 characters (8 digits + 1 hyphen)
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            
            $(this).val(value);
        });

        // Enhanced AJAX error handling
        function handleAjaxError(xhr, status, error, defaultMessage = 'An error occurred') {
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            
            let errorMessage = defaultMessage;
            
            try {
                if (xhr.responseText) {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                }
            } catch (e) {
                // If not JSON, check for server errors
                if (xhr.status === 500) {
                    errorMessage = 'Internal Server Error - Check server logs';
                } else if (xhr.responseText.includes('error') || xhr.responseText.includes('Error')) {
                    errorMessage = 'Server Error: ' + xhr.responseText.substring(0, 200);
                }
            }
            
            return errorMessage;
        }

        // ==========
        // READ (EDIT PERSONNEL)
        // ==========
        $(document).on('click', '.e_user_id', function() {
            const id = $(this).data('id');
            
            // Retrieve data from the selected row
            const $row = $(this).closest('tr');
            const $getphoto = $row.find('.photo').attr('src');
            const $getidnumber = $row.find('.id_raw').val();
            const $getrole = $row.find('.role_val').val();
            const $getcateg = $row.find('.categ_val').val();
            const $getfname = $row.find('.first_name').val();
            const $getlname = $row.find('.last_name').val();
            const $getdob = $row.find('.date_of_birth').val();
            const $getdepartment = $row.find('.department_val').val();
            const $getstatus = $row.find('.status_val').val();

            console.log('Editing personnel:', id, $getidnumber, $getfname, $getlname);

            // Populate edit form - ID is already stored in 0000-0000 format
            $('#edit_personnelid').val(id);
            $('.edit-photo').attr('src', $getphoto);
            $('#eid_number').val($getidnumber); // Already in correct format
            $('#erole').val($getrole);
            $('#ecategory').val($getcateg);
            $('#efirst_name').val($getfname);
            $('#elast_name').val($getlname);
            $('.capturedImage').val($getphoto);
            $('#edate_of_birth').val($getdob);
            $('#edepartment').val($getdepartment);
            $('#estatus').val($getstatus);
            
            // Update category dropdown based on role
            updateCategory1($getrole);
            
            // Clear any previous error messages
            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');
            
            // Show modal
            $('#editpersonnelModal').modal('show');
        });

        // ==============
        // CREATE (ADD PERSONNEL) - STORE WITH 0000-0000 FORMAT
        // ==============
        $('#personnelForm').submit(function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');
            
            // Get form values
            const role = $('#role').val();
            const category = $('#category').val();
            const last_name = $('#last_name').val().trim();
            const first_name = $('#first_name').val().trim();
            const date_of_birth = $('#date_of_birth').val();
            const department = $('#department').val();
            const id_number = $('#id_number').val().trim();
            const photo = $('#photo')[0].files[0];
            
            let isValid = true;

            // Validation
            if (!role) { 
                $('#role').addClass('is-invalid');
                $('#role-error').text('Role is required'); 
                isValid = false; 
            }
            if (!category) { 
                $('#category').addClass('is-invalid');
                $('#category-error').text('Category is required'); 
                isValid = false; 
            }
            if (!last_name) { 
                $('#last_name').addClass('is-invalid');
                $('#last_name-error').text('Last name is required'); 
                isValid = false; 
            } else if (!/^[A-Za-z\s]+$/.test(last_name)) {
                $('#last_name').addClass('is-invalid');
                $('#last_name-error').text('Last name should only contain letters and spaces'); 
                isValid = false;
            }
            if (!first_name) { 
                $('#first_name').addClass('is-invalid');
                $('#first_name-error').text('First name is required'); 
                isValid = false; 
            } else if (!/^[A-Za-z\s]+$/.test(first_name)) {
                $('#first_name').addClass('is-invalid');
                $('#first_name-error').text('First name should only contain letters and spaces'); 
                isValid = false;
            }
            if (!date_of_birth) { 
                $('#date_of_birth').addClass('is-invalid');
                $('#date_of_birth-error').text('Date of birth is required'); 
                isValid = false; 
            } else {
                // Check if date is in the future
                const today = new Date();
                const birthDate = new Date(date_of_birth);
                if (birthDate > today) {
                    $('#date_of_birth').addClass('is-invalid');
                    $('#date_of_birth-error').text('Date of birth cannot be in the future'); 
                    isValid = false;
                }
            }
            if (!department) { 
                $('#department').addClass('is-invalid');
                $('#department-error').text('Department is required'); 
                isValid = false; 
            }
            if (!id_number) { 
                $('#id_number').addClass('is-invalid');
                $('#id_number-error').text('ID Number is required'); 
                isValid = false; 
            } else if (!/^\d{4}-\d{4}$/.test(id_number)) {
                $('#id_number').addClass('is-invalid');
                $('#id_number-error').text('Invalid ID format. Must be in 0000-0000 format'); 
                isValid = false; 
            }
            
            // Photo validation
            if (photo) {
                const validFormats = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!validFormats.includes(photo.type)) {
                    $('#photo-error').text('Only JPG, JPEG and PNG formats are allowed');
                    isValid = false;
                }
                
                if (photo.size > maxSize) {
                    $('#photo-error').text('File size must be less than 2MB');
                    isValid = false;
                }
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-personnel').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-personnel').prop('disabled', true);

            var formData = new FormData(this);
            // ID number is already in correct 0000-0000 format, send as is

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_personnel",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-personnel').html('Save');
                    $('#btn-personnel').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#personnelModal').modal('hide');
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
                    $('#btn-personnel').html('Save');
                    $('#btn-personnel').prop('disabled', false);
                    
                    const errorMessage = handleAjaxError(xhr, status, error, 'Failed to save personnel');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: '<div style="text-align: left;">' + 
                              '<strong>Error Details:</strong><br>' +
                              errorMessage + 
                              '<br><br><small>Check browser console for more details.</small>' +
                              '</div>'
                    });
                }
            });
        });

        // ==========
        // UPDATE PERSONNEL - STORE WITH 0000-0000 FORMAT
        // ==========
        $('#editPersonnelForm').submit(function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');
            
            const id = $('#edit_personnelid').val();
            const role = $('#erole').val();
            const category = $('#ecategory').val();
            const last_name = $('#elast_name').val().trim();
            const first_name = $('#efirst_name').val().trim();
            const date_of_birth = $('#edate_of_birth').val();
            const department = $('#edepartment').val();
            const id_number = $('#eid_number').val().trim();
            const status = $('#estatus').val();
            const photo = $('#editPhoto')[0].files[0];

            // Validation
            let isValid = true;
            if (!role) { 
                $('#erole').addClass('is-invalid');
                $('#erole-error').text('Role is required'); 
                isValid = false; 
            }
            if (!category) { 
                $('#ecategory').addClass('is-invalid');
                $('#ecategory-error').text('Category is required'); 
                isValid = false; 
            }
            if (!last_name) { 
                $('#elast_name').addClass('is-invalid');
                $('#elast_name-error').text('Last name is required'); 
                isValid = false; 
            } else if (!/^[A-Za-z\s]+$/.test(last_name)) {
                $('#elast_name').addClass('is-invalid');
                $('#elast_name-error').text('Last name should only contain letters and spaces'); 
                isValid = false;
            }
            if (!first_name) { 
                $('#efirst_name').addClass('is-invalid');
                $('#efirst_name-error').text('First name is required'); 
                isValid = false; 
            } else if (!/^[A-Za-z\s]+$/.test(first_name)) {
                $('#efirst_name').addClass('is-invalid');
                $('#efirst_name-error').text('First name should only contain letters and spaces'); 
                isValid = false;
            }
            if (!date_of_birth) { 
                $('#edate_of_birth').addClass('is-invalid');
                $('#edate_of_birth-error').text('Date of birth is required'); 
                isValid = false; 
            } else {
                // Check if date is in the future
                const today = new Date();
                const birthDate = new Date(date_of_birth);
                if (birthDate > today) {
                    $('#edate_of_birth').addClass('is-invalid');
                    $('#edate_of_birth-error').text('Date of birth cannot be in the future'); 
                    isValid = false;
                }
            }
            if (!department) { 
                $('#edepartment').addClass('is-invalid');
                $('#edepartment-error').text('Department is required'); 
                isValid = false; 
            }
            if (!id_number) { 
                $('#eid_number').addClass('is-invalid');
                $('#eid_number-error').text('ID Number is required'); 
                isValid = false; 
            } else if (!/^\d{4}-\d{4}$/.test(id_number)) {
                $('#eid_number').addClass('is-invalid');
                $('#eid_number-error').text('Invalid ID format. Must be in 0000-0000 format'); 
                isValid = false; 
            }
            if (!status) { 
                $('#estatus').addClass('is-invalid');
                $('#estatus-error').text('Status is required'); 
                isValid = false; 
            }
            
            // Photo validation for update
            if (photo) {
                const validFormats = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024;
                
                if (!validFormats.includes(photo.type)) {
                    $('#photo-error').text('Only JPG, JPEG and PNG formats are allowed');
                    isValid = false;
                }
                
                if (photo.size > maxSize) {
                    $('#photo-error').text('File size must be less than 2MB');
                    isValid = false;
                }
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-editpersonnel').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $('#btn-editpersonnel').prop('disabled', true);

            var formData = new FormData(this);
            // ID number is already in correct 0000-0000 format, send as is
            formData.append('id', id);

            $.ajax({
                type: "POST",
                url: "transac.php?action=update_personnel",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editpersonnel').html('Update');
                    $('#btn-editpersonnel').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#editpersonnelModal').modal('hide');
                            location.reload();
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
                    $('#btn-editpersonnel').html('Update');
                    $('#btn-editpersonnel').prop('disabled', false);
                    
                    const errorMessage = handleAjaxError(xhr, status, error, 'Failed to update personnel');
                    
                    Swal.fire({
                        title: 'Error!',
                        html: '<div style="text-align: left;">' + 
                              '<strong>Error Details:</strong><br>' +
                              errorMessage + 
                              '<br><br><small>Check browser console for more details.</small>' +
                              '</div>',
                        icon: 'error'
                    });
                }
            });
        });

       // Handle delete button click
$(document).on('click', '.d_user_id', function() {
    var personnelId = $(this).data('id');
    var personnelName = $(this).attr('user_name');
    
    console.log('Delete clicked - ID:', personnelId, 'Name:', personnelName);
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to delete personnel: " + personnelName,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            console.log('Sending delete request for ID:', personnelId);
            
            $.ajax({
                url: 'transac.php?action=delete_personnel',
                type: 'POST',
                data: { 
                    id: personnelId 
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Delete response:', response);
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
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
                    console.error('Delete AJAX error:', status, error);
                    console.error('Response text:', xhr.responseText);
                    
                    const errorMessage = handleAjaxError(xhr, status, error, 'Failed to delete personnel');
                    
                    Swal.fire({
                        title: 'Error!',
                        html: '<div style="text-align: left;">' + 
                              '<strong>Error Details:</strong><br>' +
                              errorMessage + 
                              '<br><br><small>Check browser console for more details.</small>' +
                              '</div>',
                        icon: 'error'
                    });
                }
            });
        }
    });
});

        // Reset modal when closed
        $('#personnelModal').on('hidden.bs.modal', function () {
            document.getElementById('role').value = 'Student';
            updateCategory();
            $(this).find('form')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');
        });

        $('#editpersonnelModal').on('hidden.bs.modal', function () {
            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');
        });

        // Image preview functionality for both forms
        $(document).on('change', '[class^=upload-field-]', function() {
            readURL(this);
        });

        // Click handler for edit photo upload
        $(document).on('click', '.edit-photo', function() {
            $('#editPhoto').click();
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validFormats = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                // Validate file format
                if (!validFormats.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Format',
                        text: 'Only JPG and PNG formats are allowed.',
                    });
                    input.value = ''; // Reset the input
                    return;
                }

                // Validate file size
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Maximum file size is 2MB.',
                    });
                    input.value = ''; // Reset the input
                    return;
                }

                // Preview the image
                var reader = new FileReader();
                reader.onload = function (e) {
                    // Find the closest preview image
                    $(input).closest('.file-uploader').find('.preview-1').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    });
</script>
</body>
</html>