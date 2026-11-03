<?php
include 'includes/db.php';

$id = (int)($_GET['id'] ?? 0);

$res = mysqli_query($conn,"SELECT * FROM services WHERE id=$id");
$row = mysqli_fetch_assoc($res);

if(!$row){
echo "Service not found.";
exit;
}

$price = $row['price']; // CLEAN: DB ONLY
?>

<h2><?= $row['service_name'] ?></h2>

<p><?= nl2br($row['description']) ?></p>

<p><b>Duration:</b> 1–2 hrs</p>
<p><b>Price:</b> ₱<?= $price ?></p>

<a href="booking-guest.php?service_id=<?= $row['id'] ?>" class="popup-book">
Book This Service
</a>

<style>
.popup-book{
display:inline-block;
margin-top:15px;
padding:10px 18px;
background:#D6C29C;
color:#111;
border-radius:10px;
font-weight:600;
text-decoration:none;
border:none;
}

.popup-book:hover{
opacity:0.85;
}

.modal-box a{
text-decoration:none !important;
}
</style>