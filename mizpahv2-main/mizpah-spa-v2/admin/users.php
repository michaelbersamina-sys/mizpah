<?php
session_start();
include '../includes/db.php';

/* ================= SECURITY CHECK ================= */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

/* force lowercase role */
$role = strtolower($_SESSION['role']);

/* only admin allowed */
if ($role !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ================= FETCH USERS ================= */
$query = mysqli_query($conn, "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        u.created_at,
        COUNT(b.id) AS total_bookings,
        MAX(b.booking_date) AS last_booking
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    GROUP BY u.id
    ORDER BY u.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Users</title>

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
    border-collapse:collapse;
    margin-top:20px;
}

th,td{
    padding:12px;
    border-bottom:1px solid #222;
}

th{ color:#D6C29C; }

/* ROLE COLORS */
.role-admin{
    color:#FFD700;
    font-weight:bold;
}

.role-customer{
    color:#4cc9f0;
    font-weight:bold;
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main">

<h2>System Users</h2>

<table>

<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Total Bookings</th>
    <th>Last Booking</th>
    <th>Created</th>
</tr>

<?php while($row = mysqli_fetch_assoc($query)): ?>

<?php
$r = strtolower($row['role']);

$class = ($r == 'admin') ? 'role-admin' : 'role-customer';
?>

<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><span class="<?= $class ?>"><?= $r ?></span></td>
    <td><?= $row['total_bookings'] ?></td>
    <td><?= $row['last_booking'] ?? 'No booking' ?></td>
    <td><?= $row['created_at'] ?></td>
</tr>

<?php endwhile; ?>

</table>

</div>

</body>
</html>