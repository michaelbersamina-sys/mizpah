<?php
session_start();
include 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mizpah Wellness Spa</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">

<style>
/* POPUP MODAL */
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.75);
justify-content:center;
align-items:center;
z-index:9999;
padding:20px;
}

.modal-box{
width:470px;
max-width:100%;
background:#161616;
border:1px solid rgba(255,255,255,.08);
border-radius:18px;
padding:25px;
color:#fff;
position:relative;
animation:pop .25s ease;
max-height:90vh;
overflow:auto;
}

@keyframes pop{
from{transform:scale(.9);opacity:0;}
to{transform:scale(1);opacity:1;}
}

.close{
position:absolute;
top:12px;
right:16px;
font-size:28px;
cursor:pointer;
color:#D6C29C;
}

.modal-box h2{
margin-bottom:10px;
color:#D6C29C;
}

.modal-box p{
margin-bottom:10px;
line-height:1.6;
color:#ddd;
}

.modal-box ul{
padding-left:18px;
margin:10px 0;
}

.modal-box li{
margin-bottom:8px;
color:#ddd;
}

.popup-book{
display:inline-block;
margin-top:15px;
padding:10px 18px;
background:#D6C29C;
color:#111;
border-radius:10px;
font-weight:600;
text-decoration:none;
}

.service-card,
.package-card,
.popular-card{
cursor:pointer;
transition:.25s;
}

.service-card:hover,
.package-card:hover,
.popular-card:hover{
transform:translateY(-6px);
box-shadow:0 10px 30px rgba(214,194,156,.15);
}
</style>

</head>

<body>

<!-- HEADER -->
<header class="site-header">
<div class="logo">Mizpah Wellness Spa</div>

<nav>
<a href="index.php">Home</a>
<a href="services.php">Services</a>
<a href="therapist.php">Therapists</a>
<a href="mizpah_amenities_3d/index.html">Virtual Tour</a>
</nav>

<a href="login.php" class="btn-primary">Login</a>
</header>

<!-- HERO -->
<section class="hero">
<div class="hero-content">

<img src="assets/images/logo.png" class="hero-logo">

<h1 class="hero-title-white">Exquisite Comfort</h1>
<h2 class="hero-title-gold">Exceptional Care</h2>

<p class="hero-text">
Kawit's premier wellness sanctuary — where relaxation meets luxury experience.
</p>

<a href="booking-guest.php" class="btn-primary">Book Now</a>

<div class="hero-info">
<div class="info-box">☎ 0936-995-0038</div>
<div class="info-box">🕒 Mon–Fri 3PM–3AM · Sat–Sun 1PM–3AM</div>
<div class="info-box">📍 Kawit, Cavite</div>
</div>

</div>
</section>

<!-- SERVICES -->
<section class="section">

<h2>Mizpah Signature Services</h2>

<div class="service-grid">

<div class="service-card" onclick="openModal('swedish')">
<h3>Swedish Massage</h3>
<p class="desc">Relaxing full body massage using light to medium pressure.</p>
<p class="time">1–2 hrs</p>
<p class="price">₱600</p>
<a href="booking-guest.php" class="btn-small">Book Now</a>
</div>

<div class="service-card featured" onclick="openModal('signature')">
<div class="badge">Recommended</div>
<h3>Mizpah Signature</h3>
<p class="desc">Combination of Swedish, Shiatsu & deep tissue massage.</p>
<p class="time">1–2 hrs</p>
<p class="price">₱750</p>
<a href="booking-guest.php" class="btn-small">Book Now</a>
</div>

<div class="service-card" onclick="openModal('lymphatic')">
<h3>Lymphatic Massage</h3>
<p class="desc">Detox massage that improves circulation & reduces swelling.</p>
<p class="time">1–2 hrs</p>
<p class="price">₱850</p>
<a href="booking-guest.php" class="btn-small">Book Now</a>
</div>

</div>
</section>

<!-- PACKAGES -->
<section class="section">

<h2>Mizpah Packages</h2>

<div class="package-grid">

<div class="package-card bronze" onclick="openModal('bronze')">
<h3>Bronze Package</h3>
<ul class="package-list">
<li>Swedish Massage</li>
<li>Body Scrub</li>
<li>Hot Stone</li>
<li>Milk Mask</li>
<li>Korean Face Mask</li>
<li>Foot Mask</li>
</ul>
<strong>₱1,600</strong>
</div>

<div class="package-card silver" onclick="openModal('silver')">
<h3>Silver Package</h3>
<ul class="package-list">
<li>MIZPAH Signature Massage</li>
<li>Body Scrub</li>
<li>Hot Stone</li>
<li>Milk Mask</li>
<li>Korean Face Mask</li>
<li>Foot Mask</li>
</ul>
<strong>₱1,800</strong>
</div>

<div class="package-card gold" onclick="openModal('gold')">
<h3>Gold Package</h3>
<ul class="package-list">
<li>MIZPAH Signature Massage</li>
<li>Body Scrub</li>
<li>Hot Stone</li>
<li>Head or Foot Massage</li>
<li>Milk Mask</li>
<li>Korean Face Mask</li>
<li>Foot Mask</li>
</ul>
<strong>₱2,000</strong>
</div>

</div>
</section>

<!-- POPULAR -->
<section class="section">

<h2>Popular Choices</h2>
<p class="subtitle">Our Guests' Favourites</p>

<div class="popular-grid">

<div class="popular-card" onclick="openModal('pop1')">
<span class="tag">Signature</span>
<img src="assets/images/popular/signature.jpg">
<h3>Mizpah Signature</h3>
<p>Our exclusive blend for ultimate relaxation</p>
<strong>₱750</strong>
<a href="booking-guest.php" class="btn-small">Book Now</a>
</div>

<div class="popular-card" onclick="openModal('pop2')">
<span class="tag">Popular</span>
<img src="assets/images/popular/hotstone.jpg">
<h3>Hot Stone Combo</h3>
<p>Melt away tension with heated basalt stones</p>
<strong>₱1,000</strong>
<a href="booking-guest.php" class="btn-small">Book Now</a>
</div>

<div class="popular-card" onclick="openModal('pop3')">
<span class="tag">Add-On</span>
<img src="assets/images/popular/quick.jpg">
<h3>Quick Escape</h3>
<p>30-min relief for busy schedules</p>
<strong>₱350</strong>
<a href="booking-guest.php" class="btn-small">Book Now</a>
</div>

</div>
</section>

<!-- RATINGS -->
<div class="ratings-section">

<h2>Customer Reviews</h2>

<div class="rating-summary">
<div class="big-rating">4.8</div>
<p>Based on customer feedback</p>
</div>

<div class="ratings-grid" id="ratingsBox">
Loading reviews...
</div>

<hr style="margin:40px 0;border:1px solid #222">

<div class="rating-form">
<h3>Leave a Review</h3>

<form action="submit_rating.php" method="POST">

<input type="text" name="name" placeholder="Your Name" required>

<select name="rating" required>
<option value="">Rating</option>
<option value="5">★★★★★</option>
<option value="4">★★★★</option>
<option value="3">★★★</option>
<option value="2">★★</option>
<option value="1">★</option>
</select>

<textarea name="message" placeholder="Your review..." required></textarea>

<button type="submit">Submit Review</button>

</form>
</div>
</div>

<!-- CTA -->
<section class="section">
<h2>Ready to Relax?</h2>
<a href="booking-guest.php" class="btn-primary">Book Now</a>
</section>

<!-- FOOTER -->
<footer class="footer">

<div class="footer-grid">

<div>
<h3>Mizpah Wellness Spa</h3>
<p>Your sanctuary for relaxation.</p>
</div>

<div>
<h4>Quick Links</h4>
<p><a href="services.php" style="color:#aaa;text-decoration:none;">Services</a></p>
<p><a href="therapist.php" style="color:#aaa;text-decoration:none;">Therapists</a></p>
<p><a href="#virtual-tour" style="color:#aaa;text-decoration:none;">Virtual Tour</a></p>
</div>

<div>
<h4>Contact</h4>
<p>0936-995-0038</p>
<p>Kawit, Cavite</p>
</div>

</div>

<div class="footer-bottom">
<p>© 2026 Mizpah Wellness Spa</p>
</div>

</footer>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="modal-box">

<span class="close" onclick="closeModal()">&times;</span>

<div id="modalContent"></div>

</div>
</div>

<script>
window.addEventListener("scroll",function(){
document.querySelector(".site-header")
.classList.toggle("scrolled",window.scrollY>50);
});

function loadRatings(){
fetch("fetch_ratings.php")
.then(res=>res.text())
.then(data=>{
document.getElementById("ratingsBox").innerHTML=data;
});
}
loadRatings();
setInterval(loadRatings,3000);

function openModal(type){

let html="";

/* SERVICES */
if(type=="swedish"){
html=`
<h2>Swedish Massage</h2>
<p>Relaxing full body massage using light to medium pressure.</p>
<p><b>Best for:</b> Stress relief, body pain, relaxation</p>
<p><b>Duration:</b> 1–2 hrs</p>
<p><b>Price:</b> ₱600</p>
<a href="booking-guest.php" class="popup-book">Book This Service</a>
`;
}

if(type=="signature"){
html=`
<h2>Mizpah Signature</h2>
<p>Combination of Swedish, Shiatsu & deep tissue massage.</p>
<p><b>Best for:</b> Full body recovery and premium relaxation</p>
<p><b>Duration:</b> 1–2 hrs</p>
<p><b>Price:</b> ₱750</p>
<a href="booking-guest.php" class="popup-book">Book This Service</a>
`;
}

if(type=="lymphatic"){
html=`
<h2>Lymphatic Massage</h2>
<p>Detox massage improving circulation and reducing swelling.</p>
<p><b>Best for:</b> Wellness recovery</p>
<p><b>Duration:</b> 1–2 hrs</p>
<p><b>Price:</b> ₱850</p>
<a href="booking-guest.php" class="popup-book">Book This Service</a>
`;
}

/* PACKAGES */
if(type=="bronze"){
html=`
<h2>Bronze Package</h2>
<ul>
<li>Swedish Massage</li>
<li>Body Scrub</li>
<li>Hot Stone</li>
<li>Milk Mask</li>
<li>Korean Face Mask</li>
<li>Foot Mask</li>
</ul>
<p><b>Duration:</b> 1 hr 45 mins</p>
<p><b>Price:</b> ₱1,600</p>
<a href="booking-guest.php" class="popup-book">Book This Package</a>
`;
}

if(type=="silver"){
html=`
<h2>Silver Package</h2>
<ul>
<li>MIZPAH Signature Massage</li>
<li>Body Scrub</li>
<li>Hot Stone</li>
<li>Milk Mask</li>
<li>Korean Face Mask</li>
<li>Foot Mask</li>
</ul>
<p><b>Duration:</b> 1 hr 45 mins</p>
<p><b>Price:</b> ₱1,800</p>
<a href="booking-guest.php" class="popup-book">Book This Package</a>
`;
}

if(type=="gold"){
html=`
<h2>Gold Package</h2>
<ul>
<li>MIZPAH Signature Massage</li>
<li>Body Scrub</li>
<li>Hot Stone</li>
<li>Head or Foot Massage</li>
<li>Milk Mask</li>
<li>Korean Face Mask</li>
<li>Foot Mask</li>
</ul>
<p><b>Duration:</b> 2 hrs</p>
<p><b>Price:</b> ₱2,000</p>
<a href="booking-guest.php" class="popup-book">Book This Package</a>
`;
}

/* POPULAR */
if(type=="pop1"){
html=`
<h2>Mizpah Signature</h2>
<p>Our exclusive blend for ultimate relaxation.</p>
<p><b>Price:</b> ₱750</p>
<a href="booking-guest.php" class="popup-book">Book Now</a>
`;
}

if(type=="pop2"){
html=`
<h2>Hot Stone Combo</h2>
<p>Melt away tension with heated basalt stones.</p>
<p><b>Price:</b> ₱1,000</p>
<a href="booking-guest.php" class="popup-book">Book Now</a>
`;
}

if(type=="pop3"){
html=`
<h2>Quick Escape</h2>
<p>30-minute relief for busy schedules.</p>
<p><b>Price:</b> ₱350</p>
<a href="booking-guest.php" class="popup-book">Book Now</a>
`;
}

document.getElementById("modalContent").innerHTML=html;
document.getElementById("modal").style.display="flex";
}

function closeModal(){
document.getElementById("modal").style.display="none";
}
</script>

</body>
</html>