<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../login.php");
exit;
}

$user_id = $_SESSION['user_id'];
$name    = $_SESSION['name'];

/* STATS */
$total = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM bookings
WHERE user_id='$user_id'
"))['total'] ?? 0;

$pending = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM bookings
WHERE user_id='$user_id' AND status='Pending'
"))['total'] ?? 0;

$approved = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM bookings
WHERE user_id='$user_id'
AND status IN('Approved','Confirmed')
"))['total'] ?? 0;

$completed = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM bookings
WHERE user_id='$user_id'
AND status='Completed'
"))['total'] ?? 0;

/* LATEST BOOKINGS */
$bookings = mysqli_query($conn,"
SELECT *
FROM bookings
WHERE user_id='$user_id'
ORDER BY id DESC
LIMIT 5
");

/* NOTIFICATIONS */
$notif = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as total
FROM notifications
WHERE user_id='$user_id'
AND is_read=0
"))['total'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Dashboard</title>

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
font-weight:600;
color:#D6C29C;
}

.logo img{
height:40px;
}

/* NAV */
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

/* RED DOT */
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

/* HERO */
.hero{
background:linear-gradient(135deg,#161616,#111);
border:1px solid #222;
border-radius:18px;
padding:28px;
}

.hero h1{
font-size:28px;
color:#D6C29C;
margin-bottom:8px;
}

.hero p{
color:#bbb;
font-size:14px;
}

/* GRID */
.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:16px;
margin-top:22px;
}

.card{
background:#161616;
border:1px solid #222;
border-radius:16px;
padding:22px;
text-align:center;
transition:.2s;
}

.card:hover{
transform:translateY(-4px);
border-color:#D6C29C;
}

.card h3{
font-size:14px;
color:#aaa;
font-weight:500;
}

.card h2{
font-size:28px;
margin-top:8px;
color:#D6C29C;
}

/* SECTION */
.section-head{
margin-top:35px;
display:flex;
justify-content:space-between;
align-items:center;
gap:15px;
flex-wrap:wrap;
}

.section-head h2{
font-size:20px;
color:#D6C29C;
}

.view-btn{
text-decoration:none;
color:#111;
background:#D6C29C;
padding:10px 14px;
border-radius:10px;
font-size:13px;
font-weight:600;
}

/* BOOKING CARD */
.booking{
margin-top:14px;
background:#161616;
border:1px solid #222;
border-radius:16px;
padding:18px;
display:flex;
justify-content:space-between;
align-items:center;
gap:15px;
flex-wrap:wrap;
}

.booking:hover{
border-color:#D6C29C;
}

.left b{
font-size:16px;
}

.small{
font-size:13px;
color:#aaa;
margin-top:4px;
display:block;
}

.right{
text-align:right;
}

/* STATUS */
.badge{
padding:6px 12px;
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

/* RATE BTN */
.rate-btn{
display:inline-block;
margin-top:10px;
padding:8px 12px;
font-size:12px;
border-radius:10px;
text-decoration:none;
background:#D6C29C;
color:#111;
font-weight:600;
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

.hero h1{
font-size:22px;
}

.booking{
flex-direction:column;
align-items:flex-start;
}

.right{
text-align:left;
width:100%;
}
}
</style>
</head>
<body>

<header>

<div class="logo">
<img src="../assets/images/logo.png">
<span>Mizpah Wellness Spa</span>
</div>

<nav>
<a href="dashboard.php" class="active">Home</a>
<a href="booking.php">Book</a>
<a href="mybookings.php">My Bookings</a>
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

<!-- HERO -->
<div class="hero">
<h1>Welcome, <?= htmlspecialchars($name) ?></h1>
<p>Manage your bookings, schedules, and therapist ratings.</p>
</div>

<!-- STATS -->
<div class="grid">

<div class="card">
<h3>Total Bookings</h3>
<h2><?= $total ?></h2>
</div>

<div class="card">
<h3>Pending</h3>
<h2><?= $pending ?></h2>
</div>

<div class="card">
<h3>Approved</h3>
<h2><?= $approved ?></h2>
</div>

<div class="card">
<h3>Completed</h3>
<h2><?= $completed ?></h2>
</div>

</div>

<!-- RECENT BOOKINGS -->
<div class="section-head">
<h2>Recent Bookings</h2>
<a href="mybookings.php" class="view-btn">View All</a>
</div>

<?php while($b=mysqli_fetch_assoc($bookings)): 

$status = strtolower($b['status']);
$class = "pending";

if($status=="approved" || $status=="confirmed") $class="approved";
if($status=="completed") $class="completed";
if($status=="cancelled") $class="cancelled";

?>

<div class="booking">

<div class="left">
<b><?= htmlspecialchars($b['service']) ?></b>
<span class="small">
<?= $b['booking_date'] ?> • <?= $b['booking_time'] ?>
</span>
<span class="small">
<?= $b['duration'] ?> • <?= $b['pax'] ?> Pax
</span>
</div>

<div class="right">

<span class="badge <?= $class ?>">
<?= $b['status'] ?>
</span>

<?php if($b['status']=="Completed"): ?>
<br>
<a href="rate_therapist.php?booking_id=<?= $b['id'] ?>" class="rate-btn">
Rate Therapist
</a>
<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

</div>

</body>
</html>