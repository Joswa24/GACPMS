<!DOCTYPE html>
<html>
<?php
include 'header.php';
session_start();

// Initialize database connection<?php
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
session_start();

// Check if date range is posted
if (isset($_POST['date1']) && isset($_POST['date2'])) {
    // Convert dates to proper format
    $date1 = date('Y-m-d', strtotime($_POST['date1']));
    $date2 = date('Y-m-d', strtotime($_POST['date2']));
    
    // Fetch filtered data
    $sql = "SELECT * FROM visitor_logs WHERE date_logged BETWEEN '$date1' AND '$date2' ORDER BY date_logged DESC";
    $result = mysqli_query($db, $sql);
    
    // Store data in session
    $_SESSION['filtered_data'] = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['filtered_data'][] = $row;
    }
}
?>

<body style="text-align:center;" onload="window.print()">
    <img src="uploads/header1.png" style="width: 100%; max-width: 800px;"/>
    <br><br>
    <h1>Visitor Entrance Log Monitoring Report</h1>
    
    <?php if (isset($_POST['date1']) && isset($_POST['date2'])): ?>
    <h3>Date Range: <?php echo date('F j, Y', strtotime($_POST['date1'])) . ' to ' . date('F j, Y', strtotime($_POST['date2'])); ?></h3>
    <?php endif; ?>
    
    <br>
    <div class="table-responsive" style="margin: 0 auto; width: 95%;">
        <table class="table table-border" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 8px;">Photo</th>
                    <th style="border: 1px solid #000; padding: 8px;">Full Name</th>
                    <th style="border: 1px solid #000; padding: 8px;">Address</th>
                    <th style="border: 1px solid #000; padding: 8px;">Time In</th>
                    <th style="border: 1px solid #000; padding: 8px;">Time Out</th>
                    <th style="border: 1px solid #000; padding: 8px;">Log Date</th>
                    <th style="border: 1px solid #000; padding: 8px;">Purpose</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if filtered data is in session
                if (isset($_SESSION['filtered_data'])) {
                    foreach ($_SESSION['filtered_data'] as $row) {
                        echo '<tr>';
                        echo '<td style="border: 1px solid #000; padding: 8px;"><center><img src="uploads/' . $row['photo'] . '" width="50px" height="50px"></center></td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['name'] . '</td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['address'] . '</td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . date("h:i A", strtotime($row['time_in'])) . '</td>';
                        
                        if ($row['time_out'] === '?' || $row['time_out'] === '' || is_null($row['time_out'])) {
                            echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['time_out'] . '</td>';
                        } else {
                            echo '<td style="border: 1px solid #000; padding: 8px;">' . date("h:i A", strtotime($row['time_out'])) . '</td>';
                        }
                        
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['date_logged'] . '</td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['purpose'] . '</td>';
                        echo '</tr>';
                    }
                } else {
                    // If no filtered data, show all records
                    $sql = "SELECT * FROM visitor_logs ORDER BY date_logged DESC";
                    $result = mysqli_query($db, $sql);
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td style="border: 1px solid #000; padding: 8px;"><center><img src="uploads/' . $row['photo'] . '" width="50px" height="50px"></center></td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['name'] . '</td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['address'] . '</td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . date("h:i A", strtotime($row['time_in'])) . '</td>';
                        
                        if ($row['time_out'] === '?' || $row['time_out'] === '' || is_null($row['time_out'])) {
                            echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['time_out'] . '</td>';
                        } else {
                            echo '<td style="border: 1px solid #000; padding: 8px;">' . date("h:i A", strtotime($row['time_out'])) . '</td>';
                        }
                        
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['date_logged'] . '</td>';
                        echo '<td style="border: 1px solid #000; padding: 8px;">' . $row['purpose'] . '</td>';
                        echo '</tr>';
                    }
                }
                
                // Clear session data after printing
                unset($_SESSION['filtered_data']);
                mysqli_close($db);
                ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px; text-align: right; width: 95%;">
        <p>Generated on: <?php echo date('F j, Y h:i A'); ?></p>
    </div>
</body>
</html>