
  
  <?php
// date_default_timezone_set('Asia/Manila');

//  $db = mysqli_connect('127.0.0.1','u802714156_rfidgpmsPass','1rfidUser2025','u802714156_rfidgpms') or


//        die ('Unable to connect. Check your connection parameters.');
//        mysqli_select_db($db, 'u802714156_rfidgpms' ) or die(mysqli_error($db));
?> 
<?php
  date_default_timezone_set('Asia/Manila');

   $db = mysqli_connect('sql210.infinityfree.com','if0_39846067','HwDGHVhNuVvO','if0_39846067_gpassdb')  or

        die ('Unable to connect. Check your connection parameters.');
        mysqli_select_db($db, 'if0_39846067_gpassdb' ) or die(mysqli_error($db));
		
?>
