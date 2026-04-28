<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

/* ================= COUNTS ================= */
function getCount($conn,$sql){
    $q = mysqli_query($conn,$sql);
    return mysqli_fetch_assoc($q)['total'] ?? 0;
}

$bookings   = getCount($conn,"SELECT COUNT(*) as total FROM bookings");
$pending    = getCount($conn,"SELECT COUNT(*) as total FROM bookings WHERE status='Pending'");
$confirmed  = getCount($conn,"SELECT COUNT(*) as total FROM bookings WHERE status='Confirmed'");
$completed  = getCount($conn,"SELECT COUNT(*) as total FROM bookings WHERE status='Completed'");

$today = date("Y-m-d");

$todayBookings = getCount($conn,"
SELECT COUNT(*) as total
FROM bookings
WHERE DATE(booking_date)=CURDATE()
");

/* ================= REVENUE ================= */
$revenueQ = mysqli_query($conn,"
SELECT IFNULL(SUM(price * pax),0) as total
FROM bookings
WHERE status IN ('Completed','Confirmed')
");

$revenue = mysqli_fetch_assoc($revenueQ)['total'] ?? 0;

/* ================= RECENT ================= */
$recent = mysqli_query($conn,"
SELECT customer_name, service, booking_date, booking_time, status
FROM bookings
ORDER BY id DESC
LIMIT 5
");

/* ================= TODAY SCHEDULE ================= */
$schedule = mysqli_query($conn,"
SELECT customer_name, service, booking_time
FROM bookings
WHERE DATE(booking_date)=CURDATE()
ORDER BY booking_time ASC
");

/* ================= GRAPH ================= */
$labels = [];
$data = [];

$graph = mysqli_query($conn,"
SELECT booking_date, IFNULL(SUM(price * pax),0) as total
FROM bookings
WHERE status IN ('Completed','Confirmed')
GROUP BY booking_date
ORDER BY booking_date ASC
LIMIT 7
");

if($graph && mysqli_num_rows($graph) > 0){
    while($row = mysqli_fetch_assoc($graph)){
        $labels[] = date("M d", strtotime($row['booking_date']));
        $data[] = (float)$row['total'];
    }
}else{
    $labels = ["No Data"];
    $data = [0];
}

/* ================= PEAK HOURS ================= */
$hourLabels = [];
$hourData = [];

$hours = mysqli_query($conn,"
SELECT HOUR(booking_time) as hour, COUNT(*) as total
FROM bookings
WHERE booking_time IS NOT NULL
AND booking_time <> ''
GROUP BY HOUR(booking_time)
ORDER BY hour ASC
");

while($h = mysqli_fetch_assoc($hours)){
    $hourLabels[] = date("g A", strtotime($h['hour'] . ":00")); // ⭐ FIX: 12-hour format
    $hourData[] = (int)$h['total'];
}

/* ================= PEAK HOUR ================= */
$peak = mysqli_query($conn,"
SELECT HOUR(booking_time) as hour, COUNT(*) as total
FROM bookings
GROUP BY HOUR(booking_time)
ORDER BY total DESC
LIMIT 1
");

$peakRow = mysqli_fetch_assoc($peak);
$peakHour = isset($peakRow['hour']) ? date("g A", strtotime($peakRow['hour'].":00")) : 'N/A';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard</title>

<link rel="stylesheet" href="../assets/css/admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
font-family:Poppins,sans-serif;
background:#0b0b0b;
color:#fff;
margin:0;
}

.main{
margin-left:250px;
padding:35px;
min-height:100vh;
}

.page-top{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
flex-wrap:wrap;
gap:10px;
}

.page-top h1{
font-size:34px;
color:#D6C29C;
}

.page-top span{
color:#aaa;
font-size:14px;
}

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:15px;
margin-bottom:20px;
}

.card{
background:rgba(255,255,255,0.04);
backdrop-filter:blur(12px);
border:1px solid rgba(255,255,255,0.08);
border-radius:14px;
padding:20px;
}

.card h3{
font-size:13px;
color:#D6C29C;
margin-bottom:10px;
}

.card p{
font-size:28px;
font-weight:700;
}

.grid2{
display:grid;
grid-template-columns:1fr 1fr;
gap:18px;
margin-bottom:20px;
}

.panel{
background:rgba(255,255,255,0.04);
backdrop-filter:blur(12px);
border:1px solid rgba(255,255,255,0.08);
border-radius:14px;
padding:20px;
}

.panel h2{
font-size:18px;
color:#D6C29C;
margin-bottom:15px;
}

.row{
display:flex;
justify-content:space-between;
padding:10px 0;
border-bottom:1px solid rgba(255,255,255,0.06);
}

.row:last-child{border-bottom:none;}

.small{font-size:13px;color:#aaa;}

.badge{
padding:4px 10px;
border-radius:30px;
font-size:12px;
font-weight:600;
text-transform:capitalize;
}

.pending{background:#3b2d0f;color:#ffd56b;}
.confirmed{background:#173524;color:#7dffaf;}
.completed{background:#1e2f46;color:#8fc5ff;}

.chart-box{height:300px;}

.link{
display:inline-block;
margin-top:15px;
padding:10px 16px;
background:#D6C29C;
color:#111;
border-radius:10px;
font-weight:700;
text-decoration:none;
}

@media(max-width:950px){
.main{margin-left:0;padding:20px;}
.grid2{grid-template-columns:1fr;}
}
</style>
</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">

<div class="page-top">
<div>
<h1>Dashboard</h1>
<span>Overview of Mizpah Wellness Spa</span>
</div>
<span><?= date("l, F d, Y") ?></span>
</div>

<!-- CARDS -->
<div class="cards">

<div class="card">
<h3>Total Revenue</h3>
<p>₱<?= number_format($revenue,0) ?></p>
</div>

<div class="card">
<h3>Today's Bookings</h3>
<p><?= $todayBookings ?></p>
</div>

<div class="card">
<h3>Peak Hour</h3>
<p><?= $peakHour ?></p>
</div>

<div class="card">
<h3>Total Bookings</h3>
<p><?= $bookings ?></p>
</div>

<div class="card">
<h3>Pending</h3>
<p><?= $pending ?></p>
</div>

<div class="card">
<h3>Completed</h3>
<p><?= $completed ?></p>
</div>

</div>

<!-- GRAPH -->
<div class="panel">
<h2>Revenue Trend</h2>
<div class="chart-box">
<canvas id="chart"></canvas>
</div>
</div>

<!-- PEAK HOURS -->
<div class="panel" style="margin-top:20px;">
<h2>Peak Hours</h2>
<div class="chart-box">
<canvas id="hourChart"></canvas>
</div>
</div>

<!-- GRID -->
<div class="grid2">

<div class="panel">
<h2>Recent Bookings</h2>

<?php while($r=mysqli_fetch_assoc($recent)): ?>
<div class="row">
<div>
<div><?= $r['customer_name'] ?></div>
<div class="small"><?= $r['service'] ?></div>
</div>

<div>
<span class="badge <?= strtolower($r['status']) ?>">
<?= $r['status'] ?>
</span>
</div>
</div>
<?php endwhile; ?>

<a class="link" href="bookings.php">View All</a>
</div>

<div class="panel">
<h2>Today's Schedule</h2>

<?php if(mysqli_num_rows($schedule)>0): ?>
<?php while($s=mysqli_fetch_assoc($schedule)): ?>
<div class="row">
<div>
<div><?= $s['customer_name'] ?></div>
<div class="small"><?= $s['service'] ?></div>
</div>

<div class="small">
<?= date("g:i A", strtotime($s['booking_time'])) ?>
</div>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="small">No bookings today.</div>
<?php endif; ?>

<a class="link" href="calendar.php">Open Calendar</a>
</div>

</div>

</div>

<script>
const labels = <?= json_encode($labels) ?>;
const data = <?= json_encode($data) ?>;

new Chart(document.getElementById("chart"),{
type:'line',
data:{
labels:labels,
datasets:[{
label:'Revenue',
data:data,
borderColor:'#D6C29C',
backgroundColor:'rgba(214,194,156,0.12)',
fill:true,
tension:0.4,
borderWidth:2,
pointRadius:4
}]
},
options:{
responsive:true,
maintainAspectRatio:false
}
});

const hourLabels = <?= json_encode($hourLabels) ?>;
const hourData = <?= json_encode($hourData) ?>;

new Chart(document.getElementById("hourChart"),{
type:'bar',
data:{
labels:hourLabels,
datasets:[{
label:'Bookings per Hour',
data:hourData,
backgroundColor:'#D6C29C'
}]
},
options:{
responsive:true,
maintainAspectRatio:false
}
});
</script>

</body>
</html>