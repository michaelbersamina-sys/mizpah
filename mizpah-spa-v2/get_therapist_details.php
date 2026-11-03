<?php
require_once 'includes/db.php';

$id = intval($_GET['id'] ?? 0);

if(!$id){
die("Invalid ID");
}

/* therapist info */
$tq = mysqli_query($conn,"
SELECT t.*,
(
SELECT IFNULL(AVG(rating),0)
FROM therapist_ratings tr
WHERE tr.therapist_id=t.id
) as avg_rating
FROM therapists t
WHERE t.id='$id'
");

$therapist = mysqli_fetch_assoc($tq);

if(!$therapist){
die("Not found");
}

/* BODY PART RATINGS */
$parts = ["full body massage","back & shoulder","legs & feet","relaxation"];

$result = [];

foreach($parts as $p){

$q = mysqli_query($conn,"
SELECT IFNULL(AVG(rating),0) as avg_rating
FROM booking_body_ratings
WHERE body_part='$p'
AND booking_id IN (
    SELECT id FROM bookings WHERE therapist_id='$id'
)
");

$row = mysqli_fetch_assoc($q);

$result[$p] = round($row['avg_rating'],1);
}

echo json_encode([
"name"=>$therapist['name'],
"specialty"=>$therapist['specialty'],
"schedule"=>$therapist['schedule'],
"best_service"=>$therapist['best_service'],
"bio"=>$therapist['bio'],
"rating"=>$therapist['avg_rating'],
"ratings"=>$result
]);
?>