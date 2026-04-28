<?php
session_start();

include __DIR__ . '/../includes/db.php';

if(!isset($conn)){
    die("DB connection failed");
}

if(!isset($_SESSION['user_id'])){
header("Location: ../login.php");
exit;
}

/* ================= REMOVE SERVICE ================= */
if(isset($_GET['remove'])){

$id = (int)$_GET['remove'];

mysqli_query($conn,"DELETE FROM service_durations WHERE service_id='$id'");
mysqli_query($conn,"DELETE FROM services WHERE id='$id'");

echo "<script>alert('Service Removed');window.location='services.php';</script>";
exit;
}

/* ================= ADD SERVICE ================= */
if(isset($_POST['add_service'])){

$name     = trim(mysqli_real_escape_string($conn,$_POST['service_name']));
$desc     = trim(mysqli_real_escape_string($conn,$_POST['description']));
$category = trim(mysqli_real_escape_string($conn,$_POST['category']));

mysqli_query($conn,"
INSERT INTO services (service_name, description, category)
VALUES ('$name','$desc','$category')
");

$service_id = mysqli_insert_id($conn);

if(!empty($_POST['price'])){
    foreach($_POST['price'] as $i=>$price){

        $dur = $_POST['duration'][$i] ?? '';

        $dur   = mysqli_real_escape_string($conn,$dur);
        $price = mysqli_real_escape_string($conn,$price);

        if($price){
            mysqli_query($conn,"
            INSERT INTO service_durations (service_id,duration,price)
            VALUES ('$service_id','$dur','$price')
            ");
        }
    }
}

echo "<script>alert('Service Added');window.location='services.php';</script>";
exit;
}

/* ================= UPDATE SERVICE ================= */
if(isset($_POST['update_service'])){

$id       = (int)$_POST['id'];
$name     = trim(mysqli_real_escape_string($conn,$_POST['service_name']));
$desc     = trim(mysqli_real_escape_string($conn,$_POST['description']));
$category = trim(mysqli_real_escape_string($conn,$_POST['category']));

mysqli_query($conn,"
UPDATE services SET
service_name='$name',
description='$desc',
category='$category'
WHERE id='$id'
");

mysqli_query($conn,"DELETE FROM service_durations WHERE service_id='$id'");

if(!empty($_POST['price'])){
    foreach($_POST['price'] as $i=>$price){

        $dur = $_POST['duration'][$i] ?? '';

        $dur   = mysqli_real_escape_string($conn,$dur);
        $price = mysqli_real_escape_string($conn,$price);

        if($price){
            mysqli_query($conn,"
            INSERT INTO service_durations (service_id,duration,price)
            VALUES ('$id','$dur','$price')
            ");
        }
    }
}

echo "<script>alert('Service Updated');window.location='services.php';</script>";
exit;
}

/* ================= DATA ================= */
$services = mysqli_query($conn,"
SELECT * FROM services 
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Services</title>

<link rel="stylesheet" href="../assets/css/admin.css">

<style>

body{
margin:0;
font-family:Poppins,sans-serif;
background:#0b0b0b;
color:#fff;
}

.main{
margin-left:260px;
padding:30px;
}

h2{
color:#D6C29C;
margin-bottom:20px;
}

/* TABLE */
table{
width:100%;
border-collapse:separate;
border-spacing:0 12px;
}

tr{
background:rgba(255,255,255,0.03);
}

td,th{
padding:14px;
}

th{
color:#D6C29C;
}

/* BUTTONS */
button{
padding:8px 12px;
border:none;
border-radius:8px;
cursor:pointer;
}

.addbtn{
background:#D6C29C;
color:#111;
font-weight:bold;
margin-bottom:15px;
}

.editbtn{
background:rgba(76,201,240,0.2);
color:#4cc9f0;
}

.removelink{
margin-left:8px;
color:#ff5c5c;
font-size:12px;
text-decoration:none;
}

/* MODAL FIX (IMPORTANT) */
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,0.6);
z-index:9999;
}

.modal-content{
background:#161616;
width:520px;
margin:6% auto;
padding:20px;
border-radius:14px;
border:1px solid rgba(255,255,255,0.08);
position:relative;
z-index:10000;
}

/* INPUT */
input,textarea,select{
width:100%;
padding:10px;
margin-bottom:10px;
background:#111;
color:#fff;
border:1px solid rgba(255,255,255,0.1);
border-radius:8px;
}

/* ROW */
.addrow{
display:flex;
gap:10px;
margin-bottom:8px;
}

.addrow input{
flex:1;
}

</style>
</head>

<body>

<?php include __DIR__.'/includes/sidebar.php'; ?>

<div class="main">

<h2>Services Management</h2>

<button class="addbtn" onclick="document.getElementById('addModal').style.display='block'">
+ Add Service
</button>

<table>

<tr>
<th>Name</th>
<th>Category</th>
<th>Description</th>
<th>Price / Duration</th>
<th>Action</th>
</tr>

<?php while($row=mysqli_fetch_assoc($services)): ?>

<tr>

<td><?= htmlspecialchars($row['service_name']) ?></td>
<td><?= $row['category'] ?></td>
<td><?= htmlspecialchars($row['description']) ?></td>

<td>
<?php
$dur = mysqli_query($conn,"SELECT * FROM service_durations WHERE service_id=".$row['id']);
while($d=mysqli_fetch_assoc($dur)):
?>
✔ <?= !empty($d['duration']) ? $d['duration'] : 'One-time' ?> - ₱<?= $d['price'] ?><br>
<?php endwhile; ?>
</td>

<td>

<button class="editbtn" onclick="openEdit(
'<?= $row['id'] ?>',
'<?= htmlspecialchars($row['service_name']) ?>',
'<?= htmlspecialchars($row['description']) ?>',
'<?= trim($row['category']) ?>'
)">Edit</button>

<a href="?remove=<?= $row['id'] ?>" class="removelink"
onclick="return confirm('Remove this service?')">Remove</a>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>

<!-- ADD MODAL -->
<div class="modal" id="addModal">
<div class="modal-content">

<h3>Add Service</h3>

<form method="POST">

<input name="service_name" placeholder="Service Name" required>
<textarea name="description" placeholder="Description"></textarea>

<h4>Price / Duration</h4>

<div id="wrap">
<div class="addrow">
<input name="duration[]" placeholder="Optional">
<input name="price[]" placeholder="Price">
</div>
</div>

<button type="button" onclick="addRow()">+ Add More</button>

<br><br>

<select name="category">
<option value="Massage">Massage</option>
<option value="Package">Package</option>
<option value="Add-ons">Add-ons</option>
<option value="Promo">Promo</option>
</select>

<br><br>

<button name="add_service">Save</button>

</form>

</div>
</div>

<!-- EDIT MODAL -->
<div class="modal" id="editModal">
<div class="modal-content">

<h3>Edit Service</h3>

<form method="POST">

<input type="hidden" name="id" id="eid">

<input name="service_name" id="ename">
<textarea name="description" id="edesc"></textarea>

<select name="category" id="ecat">
<option value="Massage">Massage</option>
<option value="Package">Package</option>
<option value="Add-ons">Add-ons</option>
<option value="Promo">Promo</option>
</select>

<div class="addrow">
<input name="duration[]" placeholder="Optional">
<input name="price[]" placeholder="Price">
</div>

<br>

<button name="update_service">Update</button>

</form>

</div>
</div>

<script>

function addRow(){
let div=document.createElement('div');
div.className='addrow';
div.innerHTML=`
<input name="duration[]" placeholder="Optional">
<input name="price[]" placeholder="Price">
`;
document.getElementById('wrap').appendChild(div);
}

/* FINAL FIX (NO BUG VERSION) */
function openEdit(id,name,desc,cat){

document.getElementById('eid').value = id;
document.getElementById('ename').value = name;
document.getElementById('edesc').value = desc;

let select = document.getElementById('ecat');

setTimeout(() => {
    select.value = String(cat).trim();
}, 50);

document.getElementById('editModal').style.display='block';
}

</script>

</body>
</html>