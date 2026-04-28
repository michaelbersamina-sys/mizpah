<?php
session_start();
include '../includes/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");

if(mysqli_num_rows($result) == 0){
    echo "Invalid login";
    exit();
}

$user = mysqli_fetch_assoc($result);

// ⚠️ if DB still MD5:
if(md5($password) == $user['password']) {

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];

    header("Location: admin/dashboard.php");
    exit();

} else {
    echo "Invalid login";
}
?>