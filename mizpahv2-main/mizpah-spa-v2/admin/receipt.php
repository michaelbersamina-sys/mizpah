<?php
include '../includes/db.php';

$id = $_GET['id'] ?? 0;

$query = mysqli_query($conn, "
    SELECT * FROM bookings WHERE id=$id
");

$booking = mysqli_fetch_assoc($query);

if(!$booking){
    echo "Booking not found";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Booking Receipt</title>

<style>
body{
  font-family: Arial;
  background:#f5f2ee;
  padding:30px;
}

.receipt{
  max-width:500px;
  margin:auto;
  background:white;
  padding:20px;
  border-radius:10px;
  box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

h2{
  text-align:center;
  color:#3B2A22;
}

.row{
  margin:10px 0;
  font-size:14px;
}

.label{
  font-weight:bold;
  color:#4B2E2A;
}

.print-btn{
  margin-top:20px;
  width:100%;
  padding:10px;
  background:#A67C52;
  color:white;
  border:none;
  border-radius:6px;
  cursor:pointer;
}

@media print{
  .print-btn{
    display:none;
  }
}
</style>

</head>

<body>

<div class="receipt">

<h2>Mizpah Wellness Spa</h2>
<p style="text-align:center;">Booking Receipt</p>

<hr>

<div class="row"><span class="label">Customer:</span> <?= $booking['customer_name'] ?></div>
<div class="row"><span class="label">Service:</span> <?= $booking['service'] ?></div>
<div class="row"><span class="label">Date:</span> <?= $booking['booking_date'] ?></div>
<div class="row"><span class="label">Time:</span> <?= $booking['booking_time'] ?></div>
<div class="row"><span class="label">Status:</span> <?= $booking['status'] ?></div>

<hr>

<button class="print-btn" onclick="window.print()">Print Receipt</button>

</div>

</body>
</html>