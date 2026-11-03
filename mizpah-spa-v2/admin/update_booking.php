<?php
include '../includes/db.php';

$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? '';

if($id && $status){

    mysqli_query($conn, "
        UPDATE bookings 
        SET status='$status' 
        WHERE id=$id
    ");
}

header("Location: bookings.php");
exit;
?>