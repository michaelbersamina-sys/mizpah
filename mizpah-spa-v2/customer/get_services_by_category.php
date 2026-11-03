<?php
include '../includes/db.php';

/** @var mysqli $conn */

header('Content-Type: application/json');

$cat = trim($_GET['cat'] ?? '');

/* SAFE QUERY */
$data = [];

if ($cat !== '') {

    $cat = mysqli_real_escape_string($conn, $cat);

    $sql = "
        SELECT *
        FROM services
        WHERE TRIM(category) = TRIM('$cat')
        ORDER BY service_name ASC
    ";

    $q = mysqli_query($conn, $sql);

    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $data[] = $r;
        }
    }
}

echo json_encode($data);
?>