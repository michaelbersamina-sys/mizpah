<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "mizpah_spa";
$port = 3307;

$conn = mysqli_connect($host,$user,$pass,$db,$port);

if(!$conn){
die("MySQL not running on port 3307.");
}
?>