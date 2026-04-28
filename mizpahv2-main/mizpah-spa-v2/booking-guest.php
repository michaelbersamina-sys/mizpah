<?php
session_start();
include __DIR__ . '/includes/db.php';

if(isset($_POST['submit_booking'])){

$name       = $_POST['customer_name'] ?? '';
$phone      = $_POST['phone'] ?? '';

$service_id = $_POST['service_id'] ?? '';
$service    = $_POST['service'] ?? '';
$duration   = $_POST['duration'] ?? '';
$price      = $_POST['price'] ?? 0;

$date       = $_POST['booking_date'] ?? '';
$time       = $_POST['booking_time'] ?? '';
$pax        = (int)($_POST['pax'] ?? 1);
$payment    = $_POST['payment_method'] ?? 'Cash';
$notes      = $_POST['notes'] ?? '';

$therapist  = $_POST['therapist'] ?? '';
$addons     = $_POST['addons'] ?? '';

$sql = "INSERT INTO bookings
(service_id,service,duration,price,customer_name,phone,booking_date,booking_time,pax,payment_method,notes,addons,therapist_id,status)
VALUES
('$service_id','$service','$duration','$price','$name','$phone','$date','$time','$pax','$payment','$notes','$addons','$therapist','Pending')";

$result = mysqli_query($conn,$sql);

if(!$result){
die("INSERT ERROR: " . mysqli_error($conn));
}

$id = mysqli_insert_id($conn);

if(!$id){
die("ERROR: No ID generated (check AUTO_INCREMENT)");
}

header("Location: thankyou.php?id=".$id);
exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mizpah Spa Booking</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#0b0b0b;color:#fff;font-family:Poppins;}
.header{text-align:center;padding:18px;color:#D6C29C;border-bottom:1px solid #222;}
.container{max-width:1100px;margin:auto;padding:20px;display:flex;flex-direction:column;gap:15px;}
.box{background:#141414;border:1px solid #222;border-radius:14px;padding:18px;}

h3{font-size:12px;color:#D6C29C;margin-bottom:10px;}

.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
gap:12px;
}

.card{
background:#111;
border:1px solid #222;
border-radius:12px;
padding:12px;
text-align:center;
cursor:pointer;
font-size:12px;
}

.card.active{border:2px solid #D6C29C;}
.small{font-size:11px;color:#aaa;margin-top:6px;}

.time-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(120px,1fr));
gap:10px;
}

.time-card{
background:#111;
border:1px solid #222;
border-radius:12px;
padding:14px;
text-align:center;
cursor:pointer;
}

.time-card.active{border:2px solid #D6C29C;}
.time-card.dim{opacity:.3;pointer-events:none;}

input,select,textarea{
width:100%;
padding:10px;
margin-top:6px;
background:#0f0f0f;
color:#fff;
border:1px solid #333;
border-radius:10px;
}

.btn{
width:100%;
padding:14px;
background:#D6C29C;
border:none;
border-radius:12px;
font-weight:bold;
cursor:pointer;
}

.summary{
font-size:12px;
line-height:1.6;
color:#ddd;
}
</style>
</head>

<body>

<div class="header">MIZPAH SPA BOOKING</div>

<div class="container">

<form method="POST">

<!-- CATEGORY -->
<div class="box">
<h3>1. CATEGORY</h3>
<div class="grid">
<div class="card category" data-cat="Massage">Massage</div>
<div class="card category" data-cat="Package">Package</div>
<div class="card category" data-cat="Promo">Promo</div>
</div>
</div>

<!-- SERVICE -->
<div class="box">
<h3>2. SERVICE</h3>
<div class="grid" id="serviceBox"></div>
</div>

<!-- DETAILS -->
<div class="box">
<h3>3. DETAILS</h3>
<div class="card" id="descBox">Select service</div>
</div>

<!-- DURATION -->
<div class="box">
<h3>4. DURATION</h3>
<div class="grid" id="durationBox"></div>
</div>

<!-- ADDONS -->
<div class="box">
<h3>5. ADD-ONS</h3>
<div class="grid" id="addonBox"></div>
<input type="hidden" name="addons" id="addons">
</div>

<!-- DATE -->
<div class="box">
<h3>6. DATE</h3>
<input type="date" id="booking_date" name="booking_date" required>
</div>

<!-- TIME -->
<div class="box">
<h3>7. TIME</h3>
<div class="time-grid" id="timeBox"></div>
<input type="hidden" name="booking_time" id="booking_time">
</div>

<!-- THERAPIST -->
<div class="box">
<h3>8. THERAPIST</h3>
<div class="grid" id="therapistBox"></div>
<input type="hidden" name="therapist" id="therapist">
</div>

<!-- CUSTOMER -->
<div class="box">
<h3>9. CUSTOMER</h3>

<input name="customer_name" placeholder="Full Name" required>
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
<div class="summary" id="summaryBox">Select service...</div>
</div>

<input type="hidden" name="service_id" id="service_id">
<input type="hidden" name="service" id="service">
<input type="hidden" name="duration" id="duration">
<input type="hidden" name="price" id="price">

<button class="btn" name="submit_booking">BOOK NOW</button>

</form>

</div>

<script>

function updateSummary(){
summaryBox.innerHTML=`
<b>Service:</b> ${service.value||'-'}<br>
<b>Duration:</b> ${duration.value||'-'}<br>
<b>Price:</b> ₱${price.value||0}<br>
<b>Add-ons:</b> ${addons.value||'None'}<br>
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

fetch('get_services_by_category.php?cat='+c.dataset.cat)
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

fetch('get_duration.php?id='+s.dataset.id)
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
});

/* DURATION */
document.addEventListener('click',e=>{
let d=e.target.closest('.duration');
if(!d) return;

document.querySelectorAll('.duration').forEach(x=>x.classList.remove('active'));
d.classList.add('active');

duration.value=d.dataset.d;
price.value=d.dataset.p;
});

/* ADDONS */
fetch('get_addons.php')
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
document.getElementById('booking_date').addEventListener('change',function(){

timeBox.innerHTML='';
therapistBox.innerHTML='';

let date=this.value;
if(!date) return;

let day=new Date(date).getDay();
let start = (day===0 || day===6) ? 13 : 15;
let end = 27;

let slots=[];

for(let h=start; h<end; h++){

let hour = h % 24;
let h12 = hour % 12 || 12;
let ampm = hour >= 12 ? 'PM' : 'AM';

let orderFix = (hour < start) ? hour + 24 : hour;

slots.push({
hour,
label:`${h12}:00 ${ampm}`,
sort: orderFix
});
}

slots.sort((a,b)=>a.sort-b.sort);

Promise.all(
slots.map(s=>
fetch('check_slot.php?date='+date+'&time='+s.hour+':00')
.then(r=>r.json())
.then(d=>({...s,data:d}))
)
).then(res=>{

res.forEach(item=>{

let div=document.createElement('div');
div.className='time-card';

div.innerHTML=`
${item.label}
<div class="small">${item.data.available?item.data.remaining+' slot':'FULL'}</div>
`;

if(!item.data.available) div.classList.add('dim');

div.dataset.time=item.hour+':00';

div.onclick=function(){
document.querySelectorAll('.time-card').forEach(x=>x.classList.remove('active'));
this.classList.add('active');

booking_time.value=this.dataset.time;

loadTherapists();
updateSummary();
};

timeBox.appendChild(div);

});

});

});

/* THERAPIST */
function loadTherapists(){

fetch('get_available_therapists.php?date='+booking_date.value+'&time='+booking_time.value)
.then(r=>r.json())
.then(d=>{

therapistBox.innerHTML='';
therapist.value='';

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