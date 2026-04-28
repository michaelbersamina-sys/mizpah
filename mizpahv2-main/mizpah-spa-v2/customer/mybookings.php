<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* NOTIFICATIONS */
$notif = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM notifications
WHERE user_id='$user_id' AND is_read=0
"))['total'] ?? 0;

/* BOOKINGS */
$bookings = mysqli_query($conn,"
SELECT *
FROM bookings
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Bookings</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins,sans-serif;
}

body{
background:#0b0b0b;
color:#fff;
}

/* HEADER */
header{
display:flex;
justify-content:space-between;
align-items:center;
padding:14px 8%;
background:#111;
border-bottom:1px solid #222;
position:sticky;
top:0;
z-index:99;
}

.logo{
display:flex;
align-items:center;
gap:10px;
color:#D6C29C;
font-weight:600;
}

.logo img{
height:40px;
}

nav{
display:flex;
gap:18px;
flex-wrap:wrap;
}

nav a{
color:#fff;
text-decoration:none;
font-size:14px;
opacity:.75;
transition:.2s;
position:relative;
}

nav a:hover,
nav a.active{
opacity:1;
color:#D6C29C;
}

.dot{
position:absolute;
top:-4px;
right:-7px;
width:8px;
height:8px;
background:#ff4d4d;
border-radius:50%;
}

/* CONTENT */
.wrap{
padding:30px 8%;
}

.title{
font-size:28px;
color:#D6C29C;
margin-bottom:8px;
}

.sub{
color:#aaa;
font-size:14px;
margin-bottom:25px;
}

/* BOOKING CARD */
.card{
background:#161616;
border:1px solid #222;
border-radius:18px;
padding:20px;
margin-bottom:16px;
transition:.2s;
}

.card:hover{
border-color:#D6C29C;
transform:translateY(-3px);
}

.top{
display:flex;
justify-content:space-between;
gap:15px;
flex-wrap:wrap;
}

.service{
font-size:18px;
font-weight:600;
}

.meta{
font-size:13px;
color:#aaa;
margin-top:6px;
line-height:1.7;
}

/* STATUS */
.badge{
padding:7px 14px;
border-radius:50px;
font-size:12px;
font-weight:600;
display:inline-block;
}

.pending{
background:#5c4b13;
color:#ffd86a;
}

.approved{
background:#0f5132;
color:#74ffb2;
}

.completed{
background:#1b3e74;
color:#8fc1ff;
}

.cancelled{
background:#5a1d1d;
color:#ff9999;
}

/* THERAPISTS */
.therapists{
margin-top:14px;
padding-top:14px;
border-top:1px solid #222;
font-size:14px;
color:#ddd;
line-height:1.8;
}

/* ACTIONS */
.actions{
margin-top:16px;
display:flex;
gap:10px;
flex-wrap:wrap;
}

.btn{
padding:10px 14px;
border-radius:10px;
text-decoration:none;
font-size:13px;
font-weight:600;
display:inline-block;
}

.gold{
background:#D6C29C;
color:#111;
}

.dark{
background:#222;
color:#fff;
}

/* EMPTY */
.empty{
background:#161616;
border:1px solid #222;
padding:30px;
border-radius:16px;
text-align:center;
color:#aaa;
}

/* MOBILE */
@media(max-width:768px){

header{
padding:15px;
flex-direction:column;
gap:12px;
}

nav{
justify-content:center;
}

.wrap{
padding:20px;
}

.title{
font-size:22px;
}

.top{
flex-direction:column;
align-items:flex-start;
}
}
</style>
</head>
<body>

<header>

<div class="logo">
<img src="../assets/images/logo.png">
<span>Mizpah Spa</span>
</div>

<nav>
<a href="dashboard.php">Home</a>
<a href="booking.php">Book</a>
<a href="mybookings.php" class="active">My Bookings</a>
<a href="notifications.php">
Notifications
<?php if($notif>0): ?>
<span class="dot"></span>
<?php endif; ?>
</a>
<a href="profile.php">Profile</a>
<a href="logout.php">Logout</a>
</nav>

</header>

<div class="wrap">

<div class="title">My Bookings</div>
<div class="sub">Track all your reservations and rate therapists after completion.</div>

<?php if(mysqli_num_rows($bookings)==0): ?>

<div class="empty">
No bookings found.<br><br>
<a href="booking.php" class="btn gold">Book Now</a>
</div>

<?php endif; ?>

<?php while($b=mysqli_fetch_assoc($bookings)):

$status = strtolower($b['status']);
$class = "pending";

if($status=="approved" || $status=="confirmed") $class="approved";
if($status=="completed") $class="completed";
if($status=="cancelled") $class="cancelled";

/* THERAPISTS */
$therapists = mysqli_query($conn,"
SELECT t.name
FROM booking_therapists bt
LEFT JOIN therapists t ON t.id = bt.therapist_id
WHERE bt.booking_id='".$b['id']."'
");

?>

<div class="card">

<div class="top">

<div>
<div class="service"><?= htmlspecialchars($b['service']) ?></div>

<div class="meta">
<?= $b['booking_date'] ?> • <?= $b['booking_time'] ?><br>
<?= $b['duration'] ?> • <?= $b['pax'] ?> Pax<br>
Payment: <?= $b['payment_method'] ?><br>
Price: ₱<?= number_format($b['price'],2) ?>
</div>
</div>

<div>
<span class="badge <?= $class ?>">
<?= $b['status'] ?>
</span>
</div>

</div>

<div class="therapists">
<b>Assigned Therapist(s):</b><br>

<?php
if(mysqli_num_rows($therapists)>0){
    while($t=mysqli_fetch_assoc($therapists)){
        echo "• ".htmlspecialchars($t['name'])."<br>";
    }
}else{
    echo "Waiting for assignment";
}
?>

</div>

<div class="actions">

<?php if($b['status']=="Completed"): ?>
<a href="rate_therapist.php?booking_id=<?= $b['id'] ?>" class="btn gold">
Rate Therapist
</a>
<?php endif; ?>

<a href="booking.php" class="btn dark">
Book Again
</a>

</div>

</div>

<?php endwhile; ?>

</div>

</body>
</html>