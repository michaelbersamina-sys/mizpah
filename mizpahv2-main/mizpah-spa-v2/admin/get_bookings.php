<?php
include '../includes/db.php';

$date = $_GET['date'] ?? '';

if(empty($date)){
    echo "No date selected.";
    exit;
}

$q = mysqli_query($conn,"
SELECT * FROM bookings
WHERE DATE(booking_date) = '$date'
ORDER BY booking_time ASC
");

if(mysqli_num_rows($q) > 0){

    while($row = mysqli_fetch_assoc($q)){
        echo "
        <div style='margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid #eee;'>
            <b>{$row['customer_name']}</b><br>
            <small>{$row['service']}</small><br>
            <small>{$row['booking_time']}</small><br>
            <span>Status: <b>{$row['status']}</b></span>
        </div>
        ";
    }

} else {
    echo "<p style='color:#999;'>No bookings for this date.</p>";
}
?>