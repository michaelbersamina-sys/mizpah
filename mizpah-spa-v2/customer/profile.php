<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../login.php");
exit;
}

$id = $_SESSION['user_id'];

/* GET USER */
$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM users WHERE id='$id'
"));

/* PROFILE IMAGE */
$img = "../assets/images/default-profile.png";

if(!empty($user['profile_pic'])){
$img = "../uploads/profile/".$user['profile_pic'];
}

/* UPDATE PROFILE */
if(isset($_POST['update'])){

$name  = mysqli_real_escape_string($conn, $_POST['name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);

$photoSql = "";

/* UPLOAD */
if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['name'] != ''){

$filename = time().'_'.basename($_FILES['profile_pic']['name']);

$folder = __DIR__ . "/../uploads/profile/";

if(!is_dir($folder)){
mkdir($folder,0777,true);
}

$target = $folder.$filename;

if(move_uploaded_file($_FILES['profile_pic']['tmp_name'],$target)){
$photoSql = ", profile_pic='$filename'";
}

}

mysqli_query($conn,"
UPDATE users SET
name='$name',
email='$email'
$photoSql
WHERE id='$id'
");

$_SESSION['name'] = $name;

echo "<script>alert('Profile updated!');window.location='profile.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins,sans-serif;
}

body{
background:
linear-gradient(rgba(0,0,0,.75),rgba(0,0,0,.85)),
url('../assets/images/hero.jpg') center/cover fixed no-repeat;
color:#fff;
min-height:100vh;
}

/* TOP BAR */
.header{
text-align:center;
padding:25px;
font-size:22px;
font-weight:600;
color:#D6C29C;
background:rgba(0,0,0,.6);
backdrop-filter:blur(8px);
border-bottom:1px solid rgba(255,255,255,.08);
}

/* CENTER WRAPPER */
.wrapper{
display:flex;
justify-content:center;
align-items:center;
padding:40px 15px;
}

/* CARD */
.card{
width:100%;
max-width:450px;
background:rgba(20,20,20,.75);
backdrop-filter:blur(10px);
border:1px solid rgba(255,255,255,.08);
border-radius:18px;
padding:28px;
box-shadow:0 10px 30px rgba(0,0,0,.4);
}

/* TITLE */
.card h2{
text-align:center;
color:#D6C29C;
margin-bottom:18px;
font-size:22px;
}

/* PROFILE IMAGE */
.photo-wrap{
text-align:center;
margin-bottom:18px;
}

.photo{
width:110px;
height:110px;
border-radius:50%;
object-fit:cover;
border:3px solid #D6C29C;
box-shadow:0 5px 20px rgba(0,0,0,.5);
}

/* FORM */
.group{
margin-bottom:14px;
}

label{
display:block;
font-size:12px;
color:#aaa;
margin-bottom:6px;
}

input{
width:100%;
padding:12px;
border-radius:10px;
border:1px solid #333;
background:#111;
color:#fff;
outline:none;
transition:.2s;
}

input:focus{
border-color:#D6C29C;
box-shadow:0 0 8px rgba(214,194,156,.3);
}

/* FILE INPUT */
.file{
padding:10px;
background:#0f0f0f;
}

/* BUTTON */
button{
width:100%;
padding:13px;
margin-top:10px;
background:#D6C29C;
border:none;
border-radius:10px;
font-weight:700;
cursor:pointer;
color:#111;
transition:.2s;
}

button:hover{
transform:translateY(-2px);
box-shadow:0 8px 20px rgba(214,194,156,.2);
}

/* BACK LINK */
.back{
display:block;
text-align:center;
margin-top:15px;
font-size:13px;
color:#aaa;
text-decoration:none;
}

.back:hover{
color:#D6C29C;
}
</style>
</head>

<body>

<div class="header">My Profile</div>

<div class="wrapper">

<div class="card">

<h2>Account Settings</h2>

<div class="photo-wrap">
<img src="<?= $img ?>" class="photo">
</div>

<form method="POST" enctype="multipart/form-data">

<div class="group">
<label>Change Profile Picture</label>
<input type="file" name="profile_pic" class="file" accept="image/*">
</div>

<div class="group">
<label>Name</label>
<input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
</div>

<div class="group">
<label>Email</label>
<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
</div>

<button type="submit" name="update">Save Changes</button>

</form>

<a href="dashboard.php" class="back">← Back to Dashboard</a>

</div>

</div>

</body>
</html>