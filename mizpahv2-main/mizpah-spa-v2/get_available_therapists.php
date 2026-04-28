<?php
include __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';

if(!$date || !$time){
echo json_encode([]);
exit;
}

/* AVAILABLE THERAPISTS */
$sql = mysqli_query($conn,"
SELECT t.name
FROM therapists t
WHERE t.id NOT IN (
    SELECT bt.therapist_id
    FROM booking_therapists bt
    WHERE bt.booking_date = '$date'
    AND bt.booking_time = '$time'
)
ORDER BY t.name ASC
");

$data = [];

while($row = mysqli_fetch_assoc($sql)){
$data[] = $row['name'];
}

echo json_encode($data);
exit;