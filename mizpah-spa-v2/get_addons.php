<?php
include __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$data = [];

$sql = mysqli_query($conn, "
SELECT id, service_name, description, price
FROM services
WHERE category = 'Add-ons'
AND price IS NOT NULL
");

if ($sql) {
while ($row = mysqli_fetch_assoc($sql)) {
$data[] = [
'id' => $row['id'],
'service_name' => $row['service_name'],
'description' => $row['description'],
'price' => $row['price']
];
}
}

echo json_encode($data);