<?php
include '../includes/db.php';

header('Content-Type: application/json');

$cat = isset($_GET['cat']) ? trim($_GET['cat']) : "";

/* DEBUG SAFE QUERY */
$q = mysqli_query($conn,"
SELECT * FROM services 
WHERE TRIM(category) = TRIM('$cat')
ORDER BY service_name ASC
");

$data = [];

while($r = mysqli_fetch_assoc($q)){
$data[] = $r;
}

echo json_encode($data);
?>