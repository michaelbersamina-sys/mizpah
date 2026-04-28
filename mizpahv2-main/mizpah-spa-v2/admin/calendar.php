<?php
session_start();
include '../includes/db.php';

date_default_timezone_set('Asia/Manila');

if(!isset($_SESSION['user_id'])){
header("Location: ../login.php");
exit;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month > 12) { $month = 1; $year++; }
if ($month < 1) { $month = 12; $year--; }

$firstDay = date('w', strtotime("$year-$month-01"));
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$today = date("Y-m-d");

/* BOOKINGS */
$get = mysqli_query($conn,"
SELECT b.id, b.customer_name, b.booking_date, b.booking_time, b.service, t.name as therapist
FROM bookings b
LEFT JOIN booking_therapists bt ON bt.booking_id = b.id
LEFT JOIN therapists t ON t.id = bt.therapist_id
WHERE b.status != 'Cancelled'
");

$bookings = [];
$calendar = [];

while($row = mysqli_fetch_assoc($get)){

$date = $row['booking_date'];

$bookings[$date][] = $row;

if(!isset($calendar[$date])){
$calendar[$date] = [];
}

if($row['therapist']){
$calendar[$date][$row['therapist']] = true;
}
}

/* STATUS COLOR FUNCTION */
function getStatusClass($count){
if($count >= 7) return "full";      // RED
if($count >= 3) return "partial";   // YELLOW
return "available";                 // GREEN
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Calendar</title>

<link rel="stylesheet" href="../assets/css/admin.css">

<style>

body{
margin:0;
background:#0b0b0b;
color:#fff;
font-family:Poppins;
}

.main{
margin-left:250px;
padding:30px;
}

/* NAV */
.nav{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:15px;
}

.nav a{
color:#D6C29C;
font-weight:700;
text-decoration:none;
}

/* CALENDAR */
.calendar{
background:rgba(255,255,255,0.04);
border:1px solid rgba(255,255,255,0.08);
padding:20px;
border-radius:14px;
}

table{
width:100%;
border-spacing:8px;
}

td{
height:100px;
border-radius:12px;
padding:8px;
vertical-align:top;
cursor:pointer;
transition:.2s;
border:1px solid rgba(255,255,255,0.05);
position:relative;
}

/* HOVER */
td:hover{
transform:scale(1.02);
border:1px solid #D6C29C;
}

/* DAY NUMBER */
.day{
color:#D6C29C;
font-weight:bold;
font-size:13px;
}

/* TODAY */
.today{
border:1px solid #D6C29C !important;
box-shadow:0 0 12px rgba(214,194,156,.3);
}

/* ================= COLOR STATES ================= */

/* GREEN */
.available{
background:rgba(46,204,113,0.08);
border-color:rgba(46,204,113,0.3);
}

/* YELLOW */
.partial{
background:rgba(241,196,15,0.08);
border-color:rgba(241,196,15,0.3);
}

/* RED */
.full{
background:rgba(231,76,60,0.08);
border-color:rgba(231,76,60,0.4);
opacity:.85;
}

/* THERAPIST */
.therapist{
font-size:10px;
display:block;
opacity:.8;
margin-top:3px;
color:#aaa;
}

.count{
font-size:11px;
margin-top:5px;
color:#D6C29C;
}

/* MODAL */
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.75);
}

.modal-content{
background:#161616;
width:420px;
margin:8% auto;
padding:20px;
border-radius:14px;
border:1px solid rgba(214,194,156,.2);
}

.close{
float:right;
cursor:pointer;
color:#D6C29C;
}

.item{
padding:10px;
border-bottom:1px solid rgba(255,255,255,.05);
font-size:13px;
}

.item strong{color:#D6C29C;}

</style>
</head>

<body>

<?php include __DIR__.'/includes/sidebar.php'; ?>

<div class="main">

<h2 style="color:#D6C29C;">Booking Calendar</h2>

<div class="nav">

<a href="?month=<?= $month-1 ?>&year=<?= $year ?>">← Prev</a>

<h3><?= date("F Y", strtotime("$year-$month-01")) ?></h3>

<a href="?month=<?= $month+1 ?>&year=<?= $year ?>">Next →</a>

</div>

<div class="calendar">

<table>

<tr>
<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
<th>Thu</th><th>Fri</th><th>Sat</th>
</tr>

<tr>

<?php
for($i=0;$i<$firstDay;$i++){
echo "<td></td>";
}

$count = $firstDay;

for($d=1;$d<=$days;$d++){

$date = "$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($d,2,'0',STR_PAD_LEFT);

$dayBookings = $bookings[$date] ?? [];
$therapists = $calendar[$date] ?? [];

$class = getStatusClass(count($dayBookings));
if($date == $today) $class .= " today";

echo "<td class='$class' onclick=\"openModal('$date')\">";

echo "<div class='day'>$d</div>";

foreach($therapists as $name => $v){
echo "<span class='therapist'>• $name</span>";
}

echo "<div class='count'>".count($dayBookings)." bookings</div>";

echo "</td>";

$count++;

if($count % 7 == 0 && $d != $days){
echo "</tr><tr>";
}
}
?>

</tr>

</table>

</div>

</div>

<!-- MODAL -->
<div class="modal" id="modal">

<div class="modal-content">

<span class="close" onclick="closeModal()">&times;</span>

<h3 id="date"></h3>
<div id="list"></div>

</div>

</div>

<script>

let bookings = <?= json_encode($bookings) ?>;

function openModal(date){

document.getElementById('modal').style.display='block';
document.getElementById('date').innerText=date;

let data = bookings[date] ?? [];

let html = "";

if(data.length==0){
html = "<p>No bookings</p>";
}else{
data.forEach(b=>{
html += `
<div class="item">
<strong>${b.customer_name}</strong><br>
${b.service}<br>
${b.booking_time}<br>
<small>${b.therapist ?? 'No therapist'}</small>
</div>
`;
});
}

document.getElementById('list').innerHTML = html;
}

function closeModal(){
document.getElementById('modal').style.display='none';
}

</script>

</body>
</html>