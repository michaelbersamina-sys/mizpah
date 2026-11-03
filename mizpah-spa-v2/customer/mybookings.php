```
<?php

session_start();
include '../includes/db.php';

/** @var mysqli $conn */

error_reporting(E_ALL);
ini_set('display_errors', 1);


/* =========================
   CHECK LOGIN
========================= */

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit;

}

$user_id = (int)$_SESSION['user_id'];


/* =========================
   GET PROFILE PICTURE
========================= */

$userQ = mysqli_query($conn, "
    SELECT profile_pic
    FROM users
    WHERE id='$user_id'
    LIMIT 1
");

$userRow = mysqli_fetch_assoc($userQ);

$profilePic = $userRow['profile_pic'] ?? '';

if(empty($profilePic)){

    $profilePic =
        '../assets/images/default-profile.png';

}else{

    $profilePic =
        '../uploads/profile/' . $profilePic;

}


/* =========================
   BOOKINGS
========================= */

$bookings = mysqli_query($conn, "
    SELECT *
    FROM bookings
    WHERE user_id='$user_id'
    ORDER BY id DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>My Bookings</title>

<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

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
            rgba(0,0,0,.78),
            rgba(0,0,0,.90)
        ),
        url('../assets/images/hero.jpg')
        center/cover fixed no-repeat;

    color:#fff;
    min-height:100vh;
}


/* =========================
   HEADER
========================= */

header{

    display:flex;
    justify-content:space-between;
    align-items:center;

    padding:14px 8%;

    background:rgba(10,10,10,.94);

    border-bottom:
        1px solid
        rgba(214,194,156,.15);

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


/* =========================
   MAIN
========================= */

.wrap{

    width:100%;
    max-width:1200px;

    margin:auto;

    padding:38px 8% 60px;

}


/* =========================
   PAGE HEADER
========================= */

.page-header{

    display:flex;
    justify-content:space-between;
    align-items:flex-end;

    gap:20px;

    margin-bottom:26px;

}

.page-title h1{

    font-family:
        'Playfair Display',
        serif;

    color:#D6C29C;

    font-size:31px;

    margin-bottom:6px;

}

.page-title p{

    color:#888;

    font-size:12px;

    line-height:1.6;

}

.main-book-btn{

    display:inline-block;

    padding:11px 18px;

    background:#D6C29C;

    color:#111;

    text-decoration:none;

    border-radius:9px;

    font-size:12px;
    font-weight:600;

    white-space:nowrap;

    transition:.2s;

}

.main-book-btn:hover{

    transform:translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(214,194,156,.20);

}


/* =========================
   BOOKING CARD
========================= */

.booking-card{

    background:
        rgba(20,20,20,.88);

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius:16px;

    padding:23px;

    margin-bottom:17px;

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.20);

    transition:.2s;

}

.booking-card:hover{

    border-color:
        rgba(214,194,156,.30);

    transform:translateY(-2px);

}


/* =========================
   BOOKING TOP
========================= */

.booking-top{

    display:flex;

    justify-content:space-between;
    align-items:flex-start;

    gap:20px;

}

.booking-number{

    color:#666;

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:1px;

    margin-bottom:5px;

}

.service{

    color:#fff;

    font-size:18px;
    font-weight:600;

}

.duration{

    display:inline-block;

    color:#999;

    font-size:10px;

    margin-top:5px;

}


/* =========================
   STATUS
========================= */

.badge{

    display:inline-block;

    padding:6px 12px;

    border-radius:20px;

    font-size:10px;

    font-weight:600;

    white-space:nowrap;

}

.pending{

    background:
        rgba(214,194,156,.13);

    color:#D6C29C;

    border:
        1px solid
        rgba(214,194,156,.16);

}

.confirmed{

    background:
        rgba(130,180,130,.10);

    color:#a9d5a9;

    border:
        1px solid
        rgba(130,180,130,.18);

}

.completed{

    background:
        rgba(255,255,255,.08);

    color:#ddd;

    border:
        1px solid
        rgba(255,255,255,.10);

}

.cancelled{

    background:
        rgba(180,90,90,.10);

    color:#d49a9a;

    border:
        1px solid
        rgba(180,90,90,.18);

}


/* =========================
   BOOKING DETAILS
========================= */

.details-grid{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:12px;

    margin-top:20px;

}

.detail{

    background:#101010;

    border:
        1px solid
        #262626;

    border-radius:9px;

    padding:12px;

}

.detail-label{

    display:block;

    color:#666;

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:.5px;

    margin-bottom:5px;

}

.detail-value{

    color:#ddd;

    font-size:11px;

    line-height:1.5;

}


/* =========================
   SECOND DETAILS
========================= */

.extra-grid{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:12px;

    margin-top:12px;

}


/* =========================
   ADDONS / NOTES
========================= */

.additional-info{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:12px;

    margin-top:12px;

}

.info-box{

    padding:12px 14px;

    background:
        rgba(214,194,156,.04);

    border:
        1px solid
        rgba(214,194,156,.10);

    border-radius:9px;

}

.info-box strong{

    display:block;

    color:#D6C29C;

    font-size:10px;

    margin-bottom:4px;

}

.info-box p{

    color:#aaa;

    font-size:10px;

    line-height:1.6;

}


/* =========================
   THERAPISTS
========================= */

.therapist-box{

    margin-top:14px;

    padding:14px;

    background:#101010;

    border:
        1px solid
        #262626;

    border-radius:9px;

}

.therapist-title{

    color:#D6C29C;

    font-size:10px;
    font-weight:600;

    margin-bottom:7px;

}

.therapist-name{

    color:#ccc;

    font-size:11px;

    line-height:1.8;

}

.waiting{

    color:#666;

    font-size:10px;

}


/* =========================
   BOTTOM ACTIONS
========================= */

.booking-bottom{

    display:flex;

    justify-content:space-between;
    align-items:center;

    gap:15px;

    margin-top:16px;

    padding-top:16px;

    border-top:
        1px solid
        #252525;

}

.status-note{

    color:#666;

    font-size:9px;

    line-height:1.6;

}

.actions{

    display:flex;

    align-items:center;

    gap:8px;

}

.btn{

    display:inline-block;

    padding:9px 14px;

    border-radius:8px;

    text-decoration:none;

    font-size:10px;

    font-weight:600;

    transition:.2s;

}

.btn-gold{

    background:#D6C29C;

    color:#111;

}

.btn-gold:hover{

    transform:translateY(-1px);

}

.btn-dark{

    background:#222;

    color:#ccc;

    border:
        1px solid
        #333;

}

.btn-dark:hover{

    border-color:#D6C29C;

    color:#D6C29C;

}


/* =========================
   EMPTY
========================= */

.empty{

    background:
        rgba(20,20,20,.88);

    border:
        1px solid
        rgba(214,194,156,.13);

    border-radius:16px;

    padding:55px 20px;

    text-align:center;

}

.empty h3{

    color:#D6C29C;

    font-size:18px;

    margin-bottom:8px;

}

.empty p{

    color:#777;

    font-size:11px;

    margin-bottom:20px;

}


/* =========================
   MOBILE
========================= */

@media(max-width:900px){

    .details-grid{

        grid-template-columns:
            repeat(2,1fr);

    }

    .extra-grid{

        grid-template-columns:
            repeat(2,1fr);

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

        padding:
            28px 4%
            45px;

    }

    .page-header{

        align-items:flex-start;

        flex-direction:column;

    }

    .booking-top{

        flex-direction:column;

    }

    .additional-info{

        grid-template-columns:1fr;

    }

    .booking-bottom{

        flex-direction:column;

        align-items:flex-start;

    }

}

@media(max-width:500px){

    .details-grid,
    .extra-grid{

        grid-template-columns:1fr;

    }

}

</style>

</head>

<body>


<!-- =========================
     HEADER
========================= -->

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

        <a href="dashboard.php">
            Home
        </a>

        <a
            href="mybookings.php"
            class="active"
        >
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


<!-- =========================
     MAIN
========================= -->

<div class="wrap">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <div class="page-title">

            <h1>
                My Bookings
            </h1>

            <p>
                View your appointments, assigned
                therapists and current booking status.
            </p>

        </div>


        <a
            href="booking.php"
            class="main-book-btn"
        >
            Book an Appointment
        </a>

    </div>


    <!-- =========================
         NO BOOKINGS
    ========================= -->

    <?php if(
        !$bookings ||
        mysqli_num_rows($bookings) == 0
    ): ?>


        <div class="empty">

            <h3>
                No Bookings Yet
            </h3>

            <p>
                You don't have any appointments yet.
                Book your first spa visit when you're ready.
            </p>

            <a
                href="booking.php"
                class="btn btn-gold"
            >
                Book an Appointment
            </a>

        </div>


    <?php endif; ?>


    <!-- =========================
         BOOKINGS
    ========================= -->

    <?php while(
        $b = mysqli_fetch_assoc($bookings)
    ): ?>


        <?php

        /* =========================
           STATUS
        ========================= */

        // Use the latest available status field and normalize spacing/case.
        // The page should display the status saved by the admin in the database.
        $rawStatus = '';

        if (isset($b['booking_status']) && trim((string)$b['booking_status']) !== '') {
            $rawStatus = (string)$b['booking_status'];
        } else {
            $rawStatus = (string)($b['status'] ?? 'Pending');
        }

        $status = strtolower(trim($rawStatus));

        // Normalize common status spellings for consistent display.
        if ($status === 'approve') {
            $status = 'approved';
        }

        if ($status === 'confirm') {
            $status = 'confirmed';
        }

        $displayStatus = ucwords($status !== '' ? $status : 'pending');

        $class = 'pending';

        if ($status === 'approved' || $status === 'confirmed') {
            $class = 'confirmed';
            $displayStatus = 'Confirmed';
        } elseif ($status === 'completed') {
            $class = 'completed';
            $displayStatus = 'Completed';
        } elseif ($status === 'cancelled' || $status === 'canceled') {
            $class = 'cancelled';
            $displayStatus = 'Cancelled';
        } elseif ($status === 'pending') {
            $displayStatus = 'Pending';
        }


        /* =========================
           THERAPISTS
        ========================= */

        $booking_id =
            (int)$b['id'];

        $therapists =
            mysqli_query($conn, "

                SELECT t.name

                FROM booking_therapists bt

                LEFT JOIN therapists t
                    ON t.id =
                    bt.therapist_id

                WHERE
                    bt.booking_id =
                    '$booking_id'

            ");

        ?>


        <div class="booking-card">


            <!-- =========================
                 TOP
            ========================= -->

            <div class="booking-top">


                <div>

                    <div class="booking-number">

                        Booking
                        #<?= $booking_id ?>

                    </div>


                    <div class="service">

                        <?= htmlspecialchars(
                            $b['service'] ?? 'Service'
                        ) ?>

                    </div>


                    <div class="duration">

                        <?= htmlspecialchars(
                            $b['duration'] ?? ''
                        ) ?>

                    </div>

                </div>


                <div>

                    <span
                        class="badge <?= $class ?>"
                    >

                        <?= htmlspecialchars($displayStatus) ?>

                    </span>

                </div>


            </div>


            <!-- =========================
                 MAIN DETAILS
            ========================= -->

            <div class="details-grid">


                <!-- DATE -->

                <div class="detail">

                    <span class="detail-label">
                        Date
                    </span>

                    <div class="detail-value">

                        <?php

                        if(!empty(
                            $b['booking_date']
                        )){

                            echo date(
                                'M d, Y',
                                strtotime(
                                    $b['booking_date']
                                )
                            );

                        }else{

                            echo '—';

                        }

                        ?>

                    </div>

                </div>


                <!-- TIME -->

                <div class="detail">

                    <span class="detail-label">
                        Time
                    </span>

                    <div class="detail-value">

                        <?php

                        if(!empty(
                            $b['booking_time']
                        )){

                            echo date(
                                'g:i A',
                                strtotime(
                                    $b['booking_time']
                                )
                            );

                        }else{

                            echo '—';

                        }

                        ?>

                    </div>

                </div>


                <!-- PAX -->

                <div class="detail">

                    <span class="detail-label">
                        Pax
                    </span>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $b['pax'] ?? '1'
                        ) ?>

                    </div>

                </div>


                <!-- ROOM -->

                <div class="detail">

                    <span class="detail-label">
                        Room
                    </span>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            !empty($b['room_type'])
                                ? $b['room_type']
                                : 'N/A'
                        ) ?>

                    </div>

                </div>


            </div>


            <!-- =========================
                 PAYMENT DETAILS
            ========================= -->

            <div class="extra-grid">


                <!-- PAYMENT -->

                <div class="detail">

                    <span class="detail-label">
                        Payment Method
                    </span>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $b['payment_method']
                            ?? 'Cash'
                        ) ?>

                    </div>

                </div>


                <!-- PRICE -->

                <div class="detail">

                    <span class="detail-label">
                        Total Price
                    </span>

                    <div class="detail-value">

                        ₱<?= number_format(
                            (float)(
                                $b['price'] ?? 0
                            ),
                            2
                        ) ?>

                    </div>

                </div>


                <!-- DURATION -->

                <div class="detail">

                    <span class="detail-label">
                        Duration
                    </span>

                    <div class="detail-value">

                        <?= htmlspecialchars(
                            $b['duration'] ?? '—'
                        ) ?>

                    </div>

                </div>


            </div>


            <!-- =========================
                 ADDONS / NOTES
            ========================= -->

            <?php if(
                !empty($b['addons']) ||
                !empty($b['notes'])
            ): ?>


                <div class="additional-info">


                    <?php if(
                        !empty($b['addons'])
                    ): ?>

                        <div class="info-box">

                            <strong>
                                Add-ons
                            </strong>

                            <p>

                                <?= htmlspecialchars(
                                    $b['addons']
                                ) ?>

                            </p>

                        </div>

                    <?php endif; ?>


                    <?php if(
                        !empty($b['notes'])
                    ): ?>

                        <div class="info-box">

                            <strong>
                                Notes
                            </strong>

                            <p>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $b['notes']
                                    )
                                ) ?>

                            </p>

                        </div>

                    <?php endif; ?>


                </div>


            <?php endif; ?>


            <!-- =========================
                 THERAPISTS
            ========================= -->

            <div class="therapist-box">

                <div class="therapist-title">
                    Assigned Therapist(s)
                </div>


                <?php

                if(
                    $therapists &&
                    mysqli_num_rows(
                        $therapists
                    ) > 0
                ){

                    while(
                        $t =
                        mysqli_fetch_assoc(
                            $therapists
                        )
                    ){

                        if(
                            !empty($t['name'])
                        ){

                            echo
                            '<div class="therapist-name">
                                • ' .
                                htmlspecialchars(
                                    $t['name']
                                ) .
                            '</div>';

                        }

                    }

                }else{

                    echo '
                    <div class="waiting">
                        Waiting for therapist assignment
                    </div>
                    ';

                }

                ?>

            </div>


            <!-- =========================
                 BOTTOM
            ========================= -->

            <div class="booking-bottom">


                <div class="status-note">


                    <?php if(
                        $status == 'pending'
                    ): ?>

                        Your booking is waiting
                        for confirmation.


                    <?php elseif(
                        $status == 'confirmed' ||
                        $status == 'approved'
                    ): ?>

                        Your appointment has
                        been confirmed.


                    <?php elseif(
                        $status == 'completed'
                    ): ?>

                        This appointment has
                        been completed.


                    <?php elseif(
                        $status == 'cancelled'
                    ): ?>

                        This booking has
                        been cancelled.


                    <?php endif; ?>


                </div>


                <div class="actions">


                    <!-- RATE -->

                    <?php if(
                        $status == 'completed'
                    ): ?>

                        <a
                            href="rate_therapist.php?booking_id=<?= $booking_id ?>"
                            class="btn btn-gold"
                        >
                            Rate Therapist
                        </a>

                    <?php endif; ?>


                    <!-- BOOK AGAIN -->

                    <a
                        href="booking.php"
                        class="btn btn-dark"
                    >
                        Book Again
                    </a>


                </div>


            </div>


        </div>


    <?php endwhile; ?>


</div>


</body>
</html>