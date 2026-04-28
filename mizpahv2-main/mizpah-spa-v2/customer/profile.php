<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
header("Location: ../login.php");
exit;
}

$id = $_SESSION['user_id'];

if(isset($_POST['update'])){

$name = mysqli_real_escape_string($conn, $_POST['name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);

mysqli_query($conn,"
UPDATE users 
SET name='$name', email='$email' 
WHERE id='$id'
");

$_SESSION['name'] = $name;

$success = "Profile updated successfully!";
}

$user = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT * FROM users WHERE id='$id'
"));
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
body{
margin:0;
background:#0b0b0b;
color:#fff;
font-family:Poppins;
}

/* HEADER */
.header{
text-align:center;
padding:22px;
font-size:20px;
color:#D6C29C;
font-weight:600;
border-bottom:1px solid #222;
}

/* CENTER WRAPPER */
.wrapper{
display:flex;
justify-content:center;
padding:40px 15px;
}

/* CARD */
.card{
width:100%;
max-width:420px;
background:#161616;
padding:28px;
border-radius:14px;
border:1px solid #222;
}

/* TITLE */
.card h2{
text-align:center;
color:#D6C29C;
margin-bottom:20px;
}

/* SUCCESS */
.success{
background:#173527;
color:#7dffaf;
padding:10px;
border-radius:8px;
text-align:center;
margin-bottom:12px;
font-size:13px;
}

/* FORM GROUP */
.group{
margin-bottom:14px;
}

/* LABEL */
label{
display:block;
font-size:12px;
color:#aaa;
margin-bottom:6px;
}

/* INPUT */
input{
width:100%;
padding:12px;
background:#0d0d0d;
color:#fff;
border:1px solid #333;
border-radius:8px;
outline:none;
box-sizing:border-box;
}

input:focus{
border-color:#D6C29C;
}

/* BUTTON */
button{
width:100%;
padding:12px;
margin-top:10px;
background:#D6C29C;
border:none;
font-weight:700;
border-radius:8px;
cursor:pointer;
transition:.2s;
}

button:hover{
transform:scale(1.02);
}
</style>
</head>

<body>

<div class="header">My Profile</div>

<div class="wrapper">

<div class="card">

<h2>Account Details</h2>

<?php if(isset($success)): ?>
<div class="success"><?= $success ?></div>
<?php endif; ?>

<form method="POST">

<div class="group">
<label>Name</label>
<input name="name" value="<?= htmlspecialchars($user['name']) ?>">
</div>

<div class="group">
<label>Email</label>
<input name="email" value="<?= htmlspecialchars($user['email']) ?>">
</div>

<button type="submit" name="update">Update Profile</button>

</form>

</div>

</div>

</body>
</html>