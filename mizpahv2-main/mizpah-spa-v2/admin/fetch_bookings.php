<?php
include '../includes/db.php';

header('Content-Type: application/json');

$result = mysqli_query($conn,"
SELECT customer_name, booking_date, booking_time, service
FROM bookings
");

$data = [];

while($row = mysqli_fetch_assoc($result)){
$data[] = $row;
}

echo json_encode($data);