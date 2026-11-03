<?php
include '../includes/db.php';

$date = $_GET['date'];
$time = $_GET['time'];

$q = mysqli_query($conn,"
SELECT COUNT(*) as total
FROM bookings
WHERE booking_date='$date'
AND booking_time='$time'
AND status != 'Cancelled'
");

$r = mysqli_fetch_assoc($q);

echo json_encode([
"booked" => $r['total'] >= 6
]);
?>