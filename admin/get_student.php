<?php
include('../connection.php');

$query = "SELECT * FROM students ORDER BY id DESC";
$result = mysqli_query($db, $query);

while ($row = mysqli_fetch_assoc($result)) {
    echo '<tr data-id="'.$row['id'].'">';
    echo '<td>'.$row['id_number'].'</td>';
    echo '<td>'.$row['fullname'].'</td>';
    echo '<td>'.$row['section'].'</td>';
    echo '<td>'.$row['year'].'</td>';
    echo '<td>'.($row['rfid_uid'] ? $row['rfid_uid'] : 'N/A').'</td>';
    echo '<td>';
    echo '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$row['id'].'">Edit</button>';
    echo '<button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$row['id'].'">Delete</button>';
    echo '</td>';
    echo '</tr>';
}