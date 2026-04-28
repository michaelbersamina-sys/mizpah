<?php
session_start();
include 'includes/db.php';

$error = "";
$success = "";

/* =========================
   REGISTER CUSTOMER
========================= */
if(isset($_POST['register'])){

    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = mysqli_query($conn,"SELECT id FROM users WHERE email='$email'");

    if(mysqli_num_rows($check) > 0){

        $error = "Email already exists.";

    }else{

        mysqli_query($conn,"
            INSERT INTO users(name,email,password,role)
            VALUES('$name','$email','$password','customer')
        ");

        $success = "Account created! You can now login.";
    }
}

/* =========================
   LOGIN
========================= */
if(isset($_POST['login'])){

    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,"
        SELECT * FROM users
        WHERE email='$email'
        LIMIT 1
    ");

    if(mysqli_num_rows($query) == 1){

        $user = mysqli_fetch_assoc($query);

        $login_success = false;

        // bcrypt check (new accounts)
        if(password_verify($password, $user['password'])){
            $login_success = true;
        }

        // MD5 check (old admin account)
        else if(md5($password) === $user['password']){
            $login_success = true;
        }

        if($login_success){

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            if($user['role'] == 'admin'){
                header("Location: admin/dashboard.php");
            }else{
                header("Location: customer/dashboard.php");
            }

            exit();

        }else{
            $error = "Invalid email or password.";
        }

    }else{
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Mizpah Login</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Poppins,sans-serif;
    background:#0b0b0b;
}

.login-wrapper{
    position:fixed;
    inset:0;
    display:flex;
    justify-content:center;
    align-items:center;
    background:
    linear-gradient(135deg, rgba(0,0,0,.78), rgba(20,20,20,.92)),
    url('assets/images/spa-bg.jpg') center/cover no-repeat;
    padding:20px;
}

.login-card{
    width:100%;
    max-width:390px;
    background:#161616;
    padding:40px 30px;
    border-radius:16px;
    border:1px solid rgba(214,194,156,.18);
    text-align:center;
    box-shadow:0 25px 60px rgba(0,0,0,.65);
}

.login-logo{
    width:72px;
    margin-bottom:15px;
}

h2{
    font-family:'Playfair Display',serif;
    color:#D6C29C;
    font-size:28px;
}

.sub{
    color:#aaa;
    font-size:13px;
    margin:10px 0 20px;
}

.error{
    background:rgba(255,0,0,.10);
    color:#ff7c7c;
    padding:11px;
    border-radius:8px;
    margin-bottom:12px;
    font-size:13px;
}

.success{
    background:rgba(0,255,0,.10);
    color:#7CFF7C;
    padding:11px;
    border-radius:8px;
    margin-bottom:12px;
    font-size:13px;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:10px;
    border-radius:10px;
    border:1px solid rgba(214,194,156,.18);
    background:#0b0b0b;
    color:#fff;
    outline:none;
}

input:focus{
    border-color:#D6C29C;
}

button{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#D6C29C;
    color:#111;
    font-weight:700;
    cursor:pointer;
    transition:.2s;
}

button:hover{
    transform:translateY(-2px);
}

.toggle{
    margin-top:14px;
    font-size:12px;
    color:#bbb;
    cursor:pointer;
}

.toggle:hover{
    color:#D6C29C;
}

.footer-text{
    margin-top:16px;
    font-size:11px;
    color:#666;
}
</style>
</head>

<body>

<div class="login-wrapper">
<div class="login-card">

    <img src="assets/images/logo.png" class="login-logo">

    <h2>Mizpah Wellness Spa</h2>
    <div class="sub">Luxury Healing • Calm Experience</div>

    <?php if($error!=""){ ?>
        <div class="error"><?= $error ?></div>
    <?php } ?>

    <?php if($success!=""){ ?>
        <div class="success"><?= $success ?></div>
    <?php } ?>

    <form method="POST" id="loginForm">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <form method="POST" id="registerForm" style="display:none;">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register">Create Account</button>
    </form>

    <div class="toggle" onclick="toggleForm()" id="toggleText">
        Create account
    </div>

    <div class="footer-text">© 2026 Mizpah Spa</div>

</div>
</div>

<script>
function toggleForm(){

    let login = document.getElementById("loginForm");
    let register = document.getElementById("registerForm");
    let text = document.getElementById("toggleText");

    if(login.style.display === "none"){

        login.style.display = "block";
        register.style.display = "none";
        text.innerHTML = "Create account";

    }else{

        login.style.display = "none";
        register.style.display = "block";
        text.innerHTML = "Already have account?";
    }
}
</script>

</body>
</html>