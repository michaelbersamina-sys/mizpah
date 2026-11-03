<?php
include '../includes/db.php';

/** @var mysqli $conn */

header('Content-Type: application/json');

$q = mysqli_query($conn, "SELECT id, service_name, price FROM addons");

$data = [];

if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $data[] = [
            "name" => $row['service_name'],
            "price" => $row['price']
        ];
    }
}

echo json_encode($data);
?>