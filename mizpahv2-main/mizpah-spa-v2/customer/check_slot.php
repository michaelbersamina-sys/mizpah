<?php
include '../includes/db.php';

$date = $_GET['date'];
$time = $_GET['time'];

$q = mysqli_query($conn,"
SELECT COUNT(*) as cnt 
FROM bookings 
WHERE booking_date='$date' 
AND booking_time='$time'
");

$row = mysqli_fetch_assoc($q);

$limit = 2; // slots per time

echo json_encode([
'available' => ($row['cnt'] < $limit),
'remaining' => max(0, $limit - $row['cnt'])
]);