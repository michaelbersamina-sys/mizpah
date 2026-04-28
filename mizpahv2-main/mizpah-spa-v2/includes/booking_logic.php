<?php

function checkBeds($conn, $date, $time) {

    $q = mysqli_query($conn,"
        SELECT COUNT(*) as total
        FROM bookings
        WHERE booking_date='$date'
        AND booking_time='$time'
        AND status != 'Cancelled'
    ");

    $r = mysqli_fetch_assoc($q);
    return $r['total'];
}

/* BLOCK IF FULL */
function isFullyBooked($conn, $date, $time) {
    return checkBeds($conn,$date,$time) >= 6;
}

?>