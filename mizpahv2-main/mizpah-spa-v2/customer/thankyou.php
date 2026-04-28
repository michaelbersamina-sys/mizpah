<?php
session_start();
include '../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q = mysqli_query($conn,"SELECT * FROM bookings WHERE id='$id'");
$data = mysqli_fetch_assoc($q);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Confirmed</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
body{
margin:0;
font-family:Poppins,sans-serif;
background:#0b0b0b;
color:#fff;
display:flex;
justify-content:center;
align-items:center;
min-height:100vh;
padding:20px;
}

.box{
width:100%;
max-width:520px;
background:#161616;
padding:30px;
border-radius:16px;
border:1px solid #2a2a2a;
}

h1{
text-align:center;
color:#D6C29C;
margin:0 0 10px;
}

.sub{
text-align:center;
color:#aaa;
margin-bottom:25px;
font-size:14px;
}

.row{
display:flex;
justify-content:space-between;
gap:15px;
padding:12px 0;
border-bottom:1px solid #222;
}

.label{
color:#D6C29C;
font-weight:600;
}

.value{
text-align:right;
}

.btn{
display:block;
margin-top:25px;
text-align:center;
padding:14px;
background:#D6C29C;
color:#111;
text-decoration:none;
font-weight:700;
border-radius:10px;
}
</style>
</head>

<body>

<div class="box">

<?php if($data): ?>

<h1>Booking Confirmed</h1>
<div class="sub">Thank you for choosing Mizpah Wellness Spa</div>

<div class="row"><div class="label">Booking ID</div><div class="value"><?= $data['id'] ?></div></div>
<div class="row"><div class="label">Name</div><div class="value"><?= htmlspecialchars($data['customer_name']) ?></div></div>
<div class="row"><div class="label">Service</div><div class="value"><?= htmlspecialchars($data['service']) ?></div></div>
<div class="row"><div class="label">Duration</div><div class="value"><?= htmlspecialchars($data['duration']) ?></div></div>
<div class="row"><div class="label">Date</div><div class="value"><?= htmlspecialchars($data['booking_date']) ?></div></div>
<div class="row"><div class="label">Time</div><div class="value"><?= htmlspecialchars($data['booking_time']) ?></div></div>
<div class="row"><div class="label">Pax</div><div class="value"><?= htmlspecialchars($data['pax']) ?></div></div>
<div class="row"><div class="label">Payment</div><div class="value"><?= htmlspecialchars($data['payment_method']) ?></div></div>
<div class="row"><div class="label">Total Price</div><div class="value">₱<?= number_format($data['price'],2) ?></div></div>
<div class="row"><div class="label">Status</div><div class="value"><?= htmlspecialchars($data['status']) ?></div></div>

<?php if(!empty($data['notes'])): ?>
<div class="row">
<div class="label">Notes</div>
<div class="value"><?= htmlspecialchars($data['notes']) ?></div>
</div>
<?php endif; ?>

<!-- IMPORTANT CHANGE -->
<a href="dashboard.php" class="btn">Back to Dashboard</a>

<?php else: ?>

<h1>No Booking Found</h1>
<a href="dashboard.php" class="btn">Back to Dashboard</a>

<?php endif; ?>

</div>

</body>
</html>