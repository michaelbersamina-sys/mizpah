<?php

session_start();
include '../includes/db.php';

/** @var mysqli $conn */

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ======================
   CHECK LOGIN
====================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* ======================
   SESSION
====================== */

$user_id = (int)($_SESSION['user_id'] ?? 0);
$name = $_SESSION['name'] ?? '';

/* ======================
   PROFILE PIC
====================== */

$userQ = mysqli_query($conn, "
    SELECT profile_pic
    FROM users
    WHERE id = $user_id
    LIMIT 1
");

$userRow = mysqli_fetch_assoc($userQ);

$profilePic = $userRow['profile_pic'] ?? '';

if (empty($profilePic)) {
    $profilePic = '../assets/images/default-profile.png';
} else {
    $profilePic = '../uploads/profile/' . $profilePic;
}

/* ======================
   BOOKING STATS
====================== */

$total = 0;
$confirmed = 0;
$completed = 0;

/* TOTAL */

$res = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE user_id = $user_id
");

if ($res) {
    $row = mysqli_fetch_assoc($res);
    $total = $row['total'] ?? 0;
}

/* CONFIRMED */

$res = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE user_id = $user_id
    AND status IN ('Pending','Confirmed','Approved')
");

if ($res) {
    $row = mysqli_fetch_assoc($res);
    $confirmed = $row['total'] ?? 0;
}

/* COMPLETED */

$res = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE user_id = $user_id
    AND status = 'Completed'
");

if ($res) {
    $row = mysqli_fetch_assoc($res);
    $completed = $row['total'] ?? 0;
}

/* ======================
   RECENT BOOKINGS
====================== */

$bookings = mysqli_query($conn, "
    SELECT *
    FROM bookings
    WHERE user_id = $user_id
    ORDER BY id DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Customer Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Poppins,sans-serif;
}

body{
    background:
        linear-gradient(
            rgba(0,0,0,.76),
            rgba(0,0,0,.88)
        ),
        url('../assets/images/hero.jpg')
        center/cover fixed no-repeat;

    color:#fff;
    min-height:100vh;
}

/* ======================
   HEADER
====================== */

header{
    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:14px 8%;

    background:rgba(10,10,10,.94);

    border-bottom:1px solid rgba(214,194,156,.15);

    position:sticky;
    top:0;
    z-index:99;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;

    color:#D6C29C;
    font-weight:600;
}

.logo img{
    height:40px;
}

nav{
    display:flex;
    align-items:center;
    gap:8px;
}

nav a{
    color:#fff;

    padding:8px 11px;

    text-decoration:none;
    font-size:13px;

    opacity:.8;

    transition:.2s;
}

nav a:hover{
    color:#D6C29C;
    opacity:1;
}

nav a.active{
    color:#D6C29C;
    opacity:1;
}

.profile-mini{
    width:36px;
    height:36px;

    border-radius:50%;

    object-fit:cover;

    border:2px solid #D6C29C;

    margin-left:8px;
}

/* ======================
   MAIN
====================== */

.wrap{
    max-width:1300px;
    margin:auto;

    padding:35px 8% 50px;
}

/* ======================
   WELCOME
====================== */

.hero{
    background:
        linear-gradient(
            110deg,
            rgba(30,26,20,.94),
            rgba(15,15,15,.84)
        );

    padding:30px;

    border-radius:18px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    gap:20px;

    border:1px solid rgba(214,194,156,.16);

    box-shadow:0 15px 35px rgba(0,0,0,.28);
}

.hero-left{
    display:flex;
    align-items:center;
    gap:18px;
}

.hero-left img{
    width:76px;
    height:76px;

    border-radius:50%;

    object-fit:cover;

    border:3px solid #D6C29C;
}

.hero h1{
    color:#D6C29C;

    font-size:26px;

    margin-bottom:5px;
}

.hero p{
    color:#aaa;
    font-size:13px;
    line-height:1.6;
}

/* ======================
   BOOK BUTTON
====================== */

.book-now{
    display:inline-block;

    padding:13px 24px;

    background:#D6C29C;
    color:#111;

    text-decoration:none;

    border-radius:9px;

    font-weight:600;
    font-size:13px;

    white-space:nowrap;

    transition:.2s;
}

.book-now:hover{
    transform:translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(214,194,156,.20);
}

/* ======================
   SUMMARY CARDS
====================== */

.stats{
    display:grid;

    grid-template-columns:repeat(3,minmax(0,1fr));

    gap:16px;

    margin-top:22px;
}

.stat-card{
    background:rgba(20,20,20,.84);

    border:1px solid rgba(255,255,255,.07);

    border-radius:14px;

    padding:22px;

    min-height:130px;

    display:flex;
    flex-direction:column;
    justify-content:center;

    transition:.2s;
}

.stat-card:hover{
    transform:translateY(-3px);

    border-color:rgba(214,194,156,.30);
}

.stat-title{
    color:#999;

    font-size:12px;

    margin-bottom:7px;
}

.stat-number{
    color:#D6C29C;

    font-size:32px;
    font-weight:700;

    line-height:1.2;
}

.stat-sub{
    color:#666;

    font-size:10px;

    margin-top:7px;
}

/* ======================
   CONTENT
====================== */

.content-grid{
    display:grid;

    grid-template-columns:2fr 1fr;

    gap:20px;

    margin-top:22px;
}

.card{
    background:rgba(20,20,20,.84);

    border:1px solid rgba(255,255,255,.07);

    border-radius:14px;

    padding:22px;
}

.card-header{
    display:flex;

    justify-content:space-between;
    align-items:center;

    margin-bottom:18px;
}

.card-header h3{
    color:#D6C29C;

    font-size:17px;
}

.view-all{
    color:#999;

    text-decoration:none;

    font-size:11px;

    transition:.2s;
}

.view-all:hover{
    color:#D6C29C;
}

/* ======================
   TABLE
====================== */

.table-wrap{
    overflow-x:auto;
}

table{
    width:100%;

    border-collapse:collapse;
}

th{
    text-align:left;

    padding:10px;

    color:#777;

    font-size:11px;
    font-weight:500;

    border-bottom:1px solid #292929;
}

td{
    padding:13px 10px;

    color:#ddd;

    font-size:12px;

    border-bottom:1px solid #222;
}

/* ======================
   STATUS
====================== */

.status{
    display:inline-block;

    padding:5px 10px;

    border-radius:20px;

    font-size:10px;
    font-weight:600;
}

.status-pending{
    background:rgba(214,194,156,.13);
    color:#D6C29C;
}

.status-confirmed,
.status-approved{
    background:rgba(214,194,156,.20);
    color:#E8D7B7;
}

.status-completed{
    background:rgba(255,255,255,.10);
    color:#fff;
}

.status-cancelled{
    background:rgba(255,255,255,.05);
    color:#777;
}

/* ======================
   CUSTOMER OPTIONS
====================== */

.options{
    display:flex;
    flex-direction:column;
    gap:11px;
}

.option{
    display:block;

    padding:16px;

    background:#101010;

    border:1px solid #292929;

    border-radius:10px;

    color:#ddd;

    text-decoration:none;

    transition:.2s;
}

.option:hover{
    border-color:#D6C29C;

    transform:translateX(3px);
}

.option:hover .option-title{
    color:#D6C29C;
}

.option-title{
    font-size:13px;
    font-weight:600;

    transition:.2s;
}

.option-desc{
    display:block;

    color:#777;

    font-size:10px;

    margin-top:4px;

    line-height:1.5;
}

/* ======================
   WELLNESS BOX
====================== */

.wellness-box{
    margin-top:18px;

    padding:17px;

    background:
        linear-gradient(
            120deg,
            rgba(214,194,156,.08),
            rgba(15,15,15,.8)
        );

    border:1px solid rgba(214,194,156,.18);

    border-radius:10px;
}

.wellness-box strong{
    color:#D6C29C;

    font-size:13px;
}

.wellness-box p{
    color:#888;

    font-size:10px;

    line-height:1.7;

    margin-top:5px;
}

.wellness-box a{
    display:inline-block;

    color:#D6C29C;

    text-decoration:none;

    font-size:11px;

    margin-top:9px;
}

.wellness-box a:hover{
    text-decoration:underline;
}

/* ======================
   EMPTY
====================== */

.empty{
    padding:35px 10px;

    text-align:center;

    color:#777;

    font-size:12px;
}

.empty p{
    margin-bottom:16px;
}

/* ======================
   RESPONSIVE
====================== */

@media(max-width:950px){

    .stats{
        grid-template-columns:repeat(2,1fr);
    }

    .content-grid{
        grid-template-columns:1fr;
    }

}

@media(max-width:700px){

    header{
        padding:12px 4%;
    }

    .logo span{
        display:none;
    }

    nav{
        gap:1px;
    }

    nav a{
        padding:6px;
        font-size:10px;
    }

    .profile-mini{
        width:32px;
        height:32px;
        margin-left:4px;
    }

    .wrap{
        padding:25px 4%;
    }

    .hero{
        flex-direction:column;
        align-items:flex-start;
    }

    .hero-left img{
        width:65px;
        height:65px;
    }

    .hero h1{
        font-size:21px;
    }

}

@media(max-width:500px){

    .stats{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>

<!-- ======================
     HEADER
====================== -->

<header>

    <div class="logo">

        <img
            src="../assets/images/logo.png"
            alt="Mizpah Logo"
        >

        <span>
            Mizpah Wellness Spa
        </span>

    </div>


    <nav>

        <a
            href="dashboard.php"
            class="active"
        >
            Home
        </a>

        <a href="mybookings.php">
            My Bookings
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="logout.php">
            Logout
        </a>

        <img
            src="<?= htmlspecialchars($profilePic) ?>"
            class="profile-mini"
            alt="Profile"
        >

    </nav>

</header>


<!-- ======================
     MAIN
====================== -->

<div class="wrap">


    <!-- ======================
         WELCOME SECTION
    ======================= -->

    <div class="hero">

        <div class="hero-left">

            <img
                src="<?= htmlspecialchars($profilePic) ?>"
                alt="Profile"
            >

            <div>

                <h1>
                    Welcome, <?= htmlspecialchars($name) ?>
                </h1>

                <p>
                    Relax, recharge, and manage your
                    spa appointments in one place.
                </p>

            </div>

        </div>


        <!-- MAIN BOOKING BUTTON -->

        <a
            href="booking.php"
            class="book-now"
        >
            Book an Appointment
        </a>

    </div>


    <!-- ======================
         BOOKING SUMMARY
    ======================= -->

    <div class="stats">


        <!-- TOTAL -->

        <div class="stat-card">

            <div class="stat-title">
                Total Bookings
            </div>

            <div class="stat-number">
                <?= $total ?>
            </div>

            <div class="stat-sub">
                All your appointments
            </div>

        </div>


        <!-- CONFIRMED -->

        <div class="stat-card">

            <div class="stat-title">
                Confirmed
            </div>

            <div class="stat-number">
                <?= $confirmed ?>
            </div>

            <div class="stat-sub">
                Confirmed appointments
            </div>

        </div>


        <!-- COMPLETED -->

        <div class="stat-card">

            <div class="stat-title">
                Completed
            </div>

            <div class="stat-number">
                <?= $completed ?>
            </div>

            <div class="stat-sub">
                Finished spa visits
            </div>

        </div>

    </div>


    <!-- ======================
         LOWER SECTION
    ======================= -->

    <div class="content-grid">


        <!-- ======================
             RECENT BOOKINGS
        ======================= -->

        <div class="card">

            <div class="card-header">

                <h3>
                    Recent Bookings
                </h3>

                <a
                    href="mybookings.php"
                    class="view-all"
                >
                    View All →
                </a>

            </div>


            <?php if(
                $bookings &&
                mysqli_num_rows($bookings) > 0
            ): ?>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Time
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while(
                            $booking =
                            mysqli_fetch_assoc($bookings)
                        ): ?>


                            <?php

                            $status = strtolower(
                                $booking['status'] ?? 'confirmed'
                            );

                            // Customer dashboard should only show the final customer-facing statuses.
                            // Legacy Pending/Approved records are displayed as Confirmed.
                            if ($status === 'pending' || $status === 'approved') {
                                $status = 'confirmed';
                            }

                            $allowedStatuses = ['confirmed', 'completed', 'cancelled'];
                            if (!in_array($status, $allowedStatuses, true)) {
                                $status = 'confirmed';
                            }

                            $statusClass = 'status-' . $status;

                            ?>


                            <tr>


                                <!-- SERVICE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $booking['service']
                                        ?? 'Service'
                                    ) ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?php

                                    if(!empty(
                                        $booking['booking_date']
                                    )){

                                        echo date(
                                            'M d, Y',
                                            strtotime(
                                                $booking['booking_date']
                                            )
                                        );

                                    }else{

                                        echo '—';

                                    }

                                    ?>

                                </td>


                                <!-- TIME -->

                                <td>

                                    <?php

                                    if(!empty(
                                        $booking['booking_time']
                                    )){

                                        echo date(
                                            'g:i A',
                                            strtotime(
                                                $booking['booking_time']
                                            )
                                        );

                                    }else{

                                        echo '—';

                                    }

                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="
                                            status
                                            <?= $statusClass ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst($status)
                                        ) ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <p>
                        You don't have any bookings yet.
                    </p>

                    <a
                        href="booking.php"
                        class="book-now"
                    >
                        Book an Appointment
                    </a>

                </div>


            <?php endif; ?>


        </div>


        <!-- ======================
             CUSTOMER OPTIONS
        ======================= -->

        <div class="card">

            <div class="card-header">

                <h3>
                    Your Account
                </h3>

            </div>


            <div class="options">


                <!-- MY BOOKINGS -->

                <a
                    href="mybookings.php"
                    class="option"
                >

                    <div class="option-title">
                        My Bookings
                    </div>

                    <span class="option-desc">
                        View your appointments,
                        booking history and status.
                    </span>

                </a>


                <!-- PROFILE -->

                <a
                    href="profile.php"
                    class="option"
                >

                    <div class="option-title">
                        My Profile
                    </div>

                    <span class="option-desc">
                        Manage your name,
                        email and profile picture.
                    </span>

                </a>


            </div>


            <!-- WELLNESS PROFILE -->

            <div class="wellness-box">

                <strong>
                    Wellness Profile
                </strong>

                <p>
                    Keep your wellness information
                    updated to help the spa prepare
                    for your visit.
                </p>

                <a href="wellness-profile.php">
                    View / Update Wellness Profile →
                </a>

            </div>


        </div>


    </div>


</div>

</body>

</html>