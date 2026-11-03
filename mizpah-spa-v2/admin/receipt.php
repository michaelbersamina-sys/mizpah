<?php
include '../includes/db.php';

$id = intval($_GET['id'] ?? 0);

/* SAFE QUERY (NO MULTILINE ISSUES) */
$sql = "SELECT * FROM bookings WHERE id = $id";
$query = mysqli_query($conn, $sql);

if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}

$booking = mysqli_fetch_assoc($query);

if (!$booking) {
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
  font-family: Arial, sans-serif;
  background:#f5f2ee;
  padding:30px;
  font-weight:300;
}

.receipt{
  max-width:500px;
  margin:auto;
  background:white;
  padding:20px;
  border-radius:10px;
  box-shadow:0 4px 10px rgba(0,0,0,0.1);
  font-weight:300;
}

h2{
  text-align:center;
  color:#3B2A22;
  font-size:20px;
  font-weight:400;
  margin-bottom:5px;
}

.sub{
  text-align:center;
  font-size:12px;
  margin-bottom:15px;
  color:#555;
  font-weight:300;
}

hr{
  border:0;
  border-top:1px solid #ddd;
  margin:15px 0;
}

.row{
  margin:8px 0;
  font-size:13px;
  font-weight:300;
}

.label{
  font-weight:400;
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
  font-weight:400;
}

.print-btn:hover{
  opacity:0.9;
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
<div class="sub">Booking Receipt</div>

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