<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../login.php");
exit;
}

$user_id = $_SESSION['user_id'];

$data = mysqli_query($conn,"
SELECT * FROM notifications
WHERE user_id='$user_id'
ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
body{
margin:0;
background:#0b0b0b;
color:#fff;
font-family:Poppins;
}

.header{
text-align:center;
padding:20px;
color:#D6C29C;
font-size:20px;
font-weight:600;
border-bottom:1px solid #222;
}

.wrapper{
max-width:700px;
margin:auto;
padding:20px;
}

/* CARD */
.card{
background:#161616;
margin-bottom:12px;
padding:15px;
border-radius:12px;
border:1px solid #222;
transition:.2s;
}

.card:hover{
border-color:#D6C29C;
transform:scale(1.01);
}

/* TIME */
.time{
font-size:11px;
color:#777;
margin-top:5px;
}

/* TYPE */
.type{
font-size:11px;
color:#D6C29C;
margin-bottom:6px;
}
</style>
</head>

<body>

<div class="header">Notifications</div>

<div class="wrapper">

<?php if(mysqli_num_rows($data) == 0): ?>
<p style="text-align:center;color:#777;">No notifications yet.</p>
<?php endif; ?>

<?php while($r=mysqli_fetch_assoc($data)){ ?>

<div class="card">

<div class="type"><?= htmlspecialchars($r['type']) ?></div>

<div>
<?= htmlspecialchars($r['message']) ?>
</div>

<div class="time">
<?= date("M d, Y h:i A", strtotime($r['created_at'])) ?>
</div>

</div>

<?php } ?>

</div>

</body>
</html>