<?php
include __DIR__ . '/includes/db.php';

$res = mysqli_query($conn,"
SELECT id, service_name, description, price 
FROM services 
WHERE category='Add-ons'
AND price IS NOT NULL
");

$data = [];

while($row = mysqli_fetch_assoc($res)){
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);