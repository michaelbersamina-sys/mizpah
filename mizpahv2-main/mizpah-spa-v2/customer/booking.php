<?php
session_start();
include '../includes/db.php';

if(isset($_POST['submit_booking'])){

$user_id = $_SESSION['user_id'] ?? 0;

$name = $_POST['customer_name'] ?? '';
$phone = $_POST['phone'] ?? '';

$service_id = $_POST['service_id'] ?? '';
$service = $_POST['service'] ?? '';
$duration = $_POST['duration'] ?? '';
$price = $_POST['price'] ?? 0;

$date = $_POST['booking_date'] ?? '';
$time = $_POST['booking_time'] ?? '';
$pax = (int)($_POST['pax'] ?? 1);
$payment = $_POST['payment_method'] ?? 'Cash';
$notes = $_POST['notes'] ?? '';

$therapist = $_POST['therapist'] ?? '';
$addons = $_POST['addons'] ?? '';

$sql = "INSERT INTO bookings
(user_id,service_id,service,duration,price,customer_name,phone,booking_date,booking_time,pax,payment_method,notes,addons,therapist_id,status)
VALUES
('$user_id','$service_id','$service','$duration','$price','$name','$phone','$date','$time','$pax','$payment','$notes','$addons','$therapist','Pending')";

mysqli_query($conn,$sql);

$id = mysqli_insert_id($conn);

header("Location: thankyou.php?id=".$id);
exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Booking</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins;}
body{background:#0b0b0b;color:#fff;}

.header{padding:18px;text-align:center;color:#D6C29C;border-bottom:1px solid #222;}
.container{max-width:1100px;margin:auto;padding:20px;display:flex;flex-direction:column;gap:15px;}

.box{background:#141414;border:1px solid #222;border-radius:14px;padding:18px;}
h3{font-size:12px;color:#D6C29C;margin-bottom:10px;}

.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;}

.card{background:#111;border:1px solid #222;border-radius:12px;padding:12px;text-align:center;cursor:pointer;font-size:12px;}
.card.active{border:2px solid #D6C29C;}

input,select,textarea{
width:100%;padding:10px;margin-top:6px;
background:#0f0f0f;color:#fff;border:1px solid #333;border-radius:10px;
}

.btn{width:100%;padding:14px;background:#D6C29C;border:none;border-radius:12px;font-weight:bold;cursor:pointer;}

.time-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;}
.time-card{background:#111;border:1px solid #222;border-radius:12px;padding:12px;text-align:center;cursor:pointer;}
.time-card.active{border:2px solid #D6C29C;}
.time-card.dim{opacity:.3;pointer-events:none;}

.small{font-size:11px;color:#aaa;margin-top:5px;}
</style>
</head>

<body>

<div class="header">CUSTOMER BOOKING</div>

<div class="container">

<form method="POST">

<!-- CATEGORY -->
<div class="box">
<h3>CATEGORY</h3>
<div class="grid">
<div class="card category" data-cat="Massage">Massage</div>
<div class="card category" data-cat="Package">Package</div>
<div class="card category" data-cat="Promo">Promo</div>
</div>
</div>

<!-- SERVICE -->
<div class="box">
<h3>SERVICE</h3>
<div class="grid" id="serviceBox"></div>
</div>

<!-- DETAILS -->
<div class="box">
<h3>DETAILS</h3>
<div class="card" id="descBox">Select service</div>
</div>

<!-- DURATION -->
<div class="box">
<h3>DURATION</h3>
<div class="grid" id="durationBox"></div>
</div>

<!-- ADDONS -->
<div class="box">
<h3>ADD-ONS</h3>
<div class="grid" id="addonBox"></div>
<input type="hidden" name="addons" id="addons">
</div>

<!-- DATE -->
<div class="box">
<h3>DATE</h3>
<input type="date" id="booking_date" name="booking_date" required>
</div>

<!-- TIME -->
<div class="box">
<h3>TIME</h3>
<div class="time-grid" id="timeBox"></div>
<input type="hidden" name="booking_time" id="booking_time">
</div>

<!-- THERAPIST -->
<div class="box">
<h3>THERAPIST</h3>
<div class="grid" id="therapistBox"></div>
<input type="hidden" name="therapist" id="therapist">
</div>

<!-- CUSTOMER -->
<div class="box">
<h3>CUSTOMER INFO</h3>

<input name="customer_name" placeholder="Name" required>
<input name="phone" placeholder="Phone" required>

<input type="number" name="pax" value="1" min="1" max="6">

<select name="payment_method">
<option>Cash</option>
<option>GCash</option>
</select>

<textarea name="notes"></textarea>
</div>

<!-- SUMMARY -->
<div class="box">
<h3>BOOKING SUMMARY</h3>
<div style="font-size:12px;line-height:1.6;color:#ddd" id="summaryBox">
Select service...
</div>
</div>

<button class="btn" name="submit_booking">BOOK NOW</button>

<input type="hidden" name="service_id" id="service_id">
<input type="hidden" name="service" id="service">
<input type="hidden" name="duration" id="duration">
<input type="hidden" name="price" id="price">

</form>

</div>

<script>

const service_id=document.getElementById('service_id');
const service=document.getElementById('service');
const duration=document.getElementById('duration');
const price=document.getElementById('price');
const descBox=document.getElementById('descBox');

const serviceBox=document.getElementById('serviceBox');
const durationBox=document.getElementById('durationBox');
const addonBox=document.getElementById('addonBox');
const timeBox=document.getElementById('timeBox');
const therapistBox=document.getElementById('therapistBox');

const booking_date=document.getElementById('booking_date');
const booking_time=document.getElementById('booking_time');

const addons=document.getElementById('addons');
const therapist=document.getElementById('therapist');

/* SUMMARY */
function updateSummary(){
document.getElementById('summaryBox').innerHTML=`
<b>Service:</b> ${service.value||'-'}<br>
<b>Duration:</b> ${duration.value||'-'}<br>
<b>Price:</b> ₱${price.value||0}<br>
<b>Add-ons:</b> ${addons.value||'None'}<br>
<b>Date:</b> ${booking_date.value||'-'}<br>
<b>Time:</b> ${booking_time.value||'-'}<br>
<b>Therapist:</b> ${therapist.value||'-'}
`;
}

/* CATEGORY */
document.addEventListener('click',e=>{
let c=e.target.closest('.category');
if(!c) return;

document.querySelectorAll('.category').forEach(x=>x.classList.remove('active'));
c.classList.add('active');

fetch('../get_services_by_category.php?cat='+c.dataset.cat)
.then(r=>r.json())
.then(d=>{
serviceBox.innerHTML='';
d.forEach(s=>{
serviceBox.innerHTML+=`
<div class="card service"
data-id="${s.id}"
data-name="${s.service_name}"
data-desc="${s.description}">
${s.service_name}
</div>`;
});
});
});

/* SERVICE */
document.addEventListener('click',e=>{
let s=e.target.closest('.service');
if(!s) return;

document.querySelectorAll('.service').forEach(x=>x.classList.remove('active'));
s.classList.add('active');

service_id.value=s.dataset.id;
service.value=s.dataset.name;
descBox.innerText=s.dataset.desc;

fetch('../get_duration.php?id='+s.dataset.id)
.then(r=>r.json())
.then(d=>{
durationBox.innerHTML='';
d.forEach(x=>{
durationBox.innerHTML+=`
<div class="card duration"
data-d="${x.duration}"
data-p="${x.price}">
${x.duration}<br>₱${x.price}
</div>`;
});
});
updateSummary();
});

/* DURATION */
document.addEventListener('click',e=>{
let d=e.target.closest('.duration');
if(!d) return;

document.querySelectorAll('.duration').forEach(x=>x.classList.remove('active'));
d.classList.add('active');

duration.value=d.dataset.d;
price.value=d.dataset.p;
updateSummary();
});

/* ADDONS */
fetch('../get_addons.php')
.then(r=>r.json())
.then(d=>{
addonBox.innerHTML='';
d.forEach(a=>{
addonBox.innerHTML+=`
<div class="card addon"
data-name="${a.service_name}"
data-price="${a.price}">
${a.service_name}<br>₱${a.price}
</div>`;
});
});

document.addEventListener('click',e=>{
let a=e.target.closest('.addon');
if(!a) return;

a.classList.toggle('active');

let arr=[];
document.querySelectorAll('.addon.active').forEach(x=>{
arr.push(x.dataset.name+" ₱"+x.dataset.price);
});

addons.value=arr.join(', ');
updateSummary();
});

/* TIME */
booking_date.addEventListener('change',async ()=>{

timeBox.innerHTML='';
therapistBox.innerHTML='';

let d=new Date(booking_date.value).getDay();
let start=(d===0||d===6)?13:15;
let end=27;

for(let h=start;h<end;h++){

let hour=h%24;

let res=await fetch('../check_slot.php?date='+booking_date.value+'&time='+hour+':00');
let data=await res.json();

let div=document.createElement('div');
div.className='time-card';

let label=(hour%12||12)+':00 '+(hour>=12?'PM':'AM');

div.innerHTML=`${label}<div class="small">${data.remaining??0} slot</div>`;

if(!data.available) div.classList.add('dim');

div.onclick=function(){
document.querySelectorAll('.time-card').forEach(x=>x.classList.remove('active'));
this.classList.add('active');
booking_time.value=hour+':00';
loadTherapists();
updateSummary();
};

timeBox.appendChild(div);
}

});

/* THERAPIST */
function loadTherapists(){
fetch('../get_available_therapists.php?date='+booking_date.value+'&time='+booking_time.value)
.then(r=>r.json())
.then(d=>{
therapistBox.innerHTML='';
d.forEach(n=>{
therapistBox.innerHTML+=`
<div class="card therapist" data-name="${n}">
${n}
</div>`;
});
});
}

document.addEventListener('click',e=>{
let t=e.target.closest('.therapist');
if(!t) return;

document.querySelectorAll('.therapist').forEach(x=>x.classList.remove('active'));
t.classList.add('active');

therapist.value=t.dataset.name;
updateSummary();
});

</script>

</body>
</html>