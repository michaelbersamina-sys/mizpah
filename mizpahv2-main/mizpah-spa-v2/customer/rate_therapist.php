<?php
session_start();
include '../includes/db.php';

$booking_id = $_GET['booking_id'] ?? 0;

$booking = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT b.*, bt.therapist_id, t.name as therapist_name
FROM bookings b
LEFT JOIN booking_therapists bt ON bt.booking_id = b.id
LEFT JOIN therapists t ON t.id = bt.therapist_id
WHERE b.id='$booking_id'
"));

if(!$booking){
die("Invalid booking");
}

/* SUBMIT */
if(isset($_POST['submit_rating'])){

$booking_id = $_POST['booking_id'];
$therapist_id = $_POST['therapist_id'];
$rating = $_POST['rating'];

mysqli_query($conn,"
INSERT INTO therapist_ratings (booking_id, therapist_id, rating)
VALUES ('$booking_id','$therapist_id','$rating')
");

header("Location: mybookings.php?rated=1");
exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Rate Therapist</title>

<style>
body{
margin:0;
background:#0b0b0b;
font-family:Poppins;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
color:#fff;
}

.box{
background:#161616;
padding:25px;
border-radius:16px;
width:360px;
border:1px solid #222;
text-align:center;
}

h2{
color:#D6C29C;
margin-bottom:8px;
}

p{
color:#aaa;
font-size:13px;
}

/* STARS */
.stars{
display:flex;
justify-content:center;
gap:6px;
font-size:34px;
cursor:pointer;
margin:15px 0;
}

.star{
color:#444;
transition:.2s;
}

.star.active,
.star:hover,
.star:hover ~ .star{
color:#D6C29C;
}

/* BUTTON */
button{
width:100%;
padding:12px;
margin-top:10px;
background:#D6C29C;
border:none;
border-radius:10px;
font-weight:bold;
cursor:pointer;
color:#111;
}

button:hover{
opacity:.9;
}
</style>
</head>

<body>

<div class="box">

<h2>Rate Therapist</h2>
<p><?= $booking['therapist_name'] ?? 'No Therapist Assigned' ?></p>

<form method="POST">

<input type="hidden" name="booking_id" value="<?= $booking_id ?>">
<input type="hidden" name="therapist_id" value="<?= $booking['therapist_id'] ?>">

<div class="stars" id="stars">
<span class="star" data-value="1">★</span>
<span class="star" data-value="2">★</span>
<span class="star" data-value="3">★</span>
<span class="star" data-value="4">★</span>
<span class="star" data-value="5">★</span>
</div>

<input type="hidden" name="rating" id="rating" required>

<button name="submit_rating">Submit Rating</button>

</form>

</div>

<script>
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating');

stars.forEach((star, index)=>{
star.addEventListener('click',()=>{

ratingInput.value = star.dataset.value;

stars.forEach(s=>s.classList.remove('active'));

for(let i=0;i<=index;i++){
stars[i].classList.add('active');
}

});
});
</script>

</body>
</html>