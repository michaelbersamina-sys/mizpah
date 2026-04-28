<?php
include '../includes/db.php';

$q = mysqli_query($conn,"SELECT name FROM therapists WHERE status='Active'");

$data = [];

while($row = mysqli_fetch_assoc($q)){
$data[] = $row['name'];
}

echo json_encode($data);
?>