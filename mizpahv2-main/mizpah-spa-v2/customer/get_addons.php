<?php
include '../includes/db.php';

$q = mysqli_query($conn,"SELECT id, service_name, price FROM addons");

$data = [];

while($row = mysqli_fetch_assoc($q)){
$data[] = [
"name" => $row['service_name'],
"price" => $row['price']
];
}

echo json_encode($data);
?>