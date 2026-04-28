<?php
error_reporting(0);
header('Content-Type: application/json');

include 'includes/db.php';

if(!isset($conn)){
echo json_encode([]);
exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
echo json_encode([]);
exit;
}

$sql = mysqli_query($conn,"
SELECT duration, price
FROM service_durations
WHERE service_id='$id'
ORDER BY price ASC
");

$data = [];

while($row = mysqli_fetch_assoc($sql)){
$data[] = [
'duration' => $row['duration'],
'price' => $row['price']
];
}

echo json_encode($data);