<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ================= HIDE ================= */
if (isset($_GET['hide'])) {
    $id = (int)$_GET['hide'];

    mysqli_query($conn, "
        UPDATE ratings 
        SET status='hidden'
        WHERE id='$id'
    ");

    header("Location: ratings.php");
    exit;
}

/* ================= SHOW ================= */
if (isset($_GET['show'])) {
    $id = (int)$_GET['show'];

    mysqli_query($conn, "
        UPDATE ratings 
        SET status='shown'
        WHERE id='$id'
    ");

    header("Location: ratings.php");
    exit;
}

/* ================= REMOVE ================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    mysqli_query($conn, "
        DELETE FROM ratings
        WHERE id='$id'
    ");

    header("Location: ratings.php");
    exit;
}

/* ================= FETCH ================= */
$query = mysqli_query($conn, "
    SELECT * FROM ratings
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ratings</title>
<link rel="stylesheet" href="../assets/css/admin.css">

<style>
body{
    background:#0b0b0b;
    color:#fff;
    font-family:Poppins;
}

.main{
    margin-left:250px;
    padding:25px;
}

table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 10px;
}

tr{ background:#161616; }

td,th{ padding:12px; }

th{ color:#D6C29C; }

.btn{
    padding:6px 10px;
    border-radius:6px;
    text-decoration:none;
    font-size:13px;
    margin-right:5px;
}

.hide{ background:#3a1d1d; color:#ff6b6b; }
.show{ background:#1d3325; color:#7dffaf; }
.remove{ background:#3a0000; color:#ff4444; }

.status-shown{ color:#7dffaf; font-weight:bold; }
.status-hidden{ color:#ff9f43; font-weight:bold; }

.stars{ color:#FFD700; }
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main">

<h2>Customer Ratings</h2>

<table>

<tr>
<th>Name</th>
<th>Rating</th>
<th>Message</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($query)){ ?>

<tr>

<td><?= htmlspecialchars($row['name']) ?></td>

<td class="stars">
<?= str_repeat("★",(int)$row['rating']) ?>
</td>

<td><?= htmlspecialchars($row['message']) ?></td>

<td>
<?php if($row['status']=='shown'){ ?>
<span class="status-shown">Visible</span>
<?php } else { ?>
<span class="status-hidden">Hidden</span>
<?php } ?>
</td>

<td>

<?php if($row['status']=='shown'){ ?>
<a class="btn hide" href="ratings.php?hide=<?= $row['id'] ?>">Hide</a>
<?php } else { ?>
<a class="btn show" href="ratings.php?show=<?= $row['id'] ?>">Show</a>
<?php } ?>

<a class="btn remove" href="ratings.php?delete=<?= $row['id'] ?>"
onclick="return confirm('Remove review permanently?')">
Remove
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>