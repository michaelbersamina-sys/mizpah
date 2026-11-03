<?php

session_start();
require_once __DIR__ . '/../includes/db.php';

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];


/* =========================
   USER INFORMATION
========================= */

$user_query = mysqli_query($conn, "
    SELECT name, email, profile_pic
    FROM users
    WHERE id='$user_id'
    LIMIT 1
");

$user = mysqli_fetch_assoc($user_query);

$user_name = $user['name'] ?? '';
$user_email = $user['email'] ?? '';
$profile_pic = $user['profile_pic'] ?? '';

if (!empty($profile_pic)) {
    $profile_image = "../uploads/profile/" . $profile_pic;
} else {
    $profile_image = "../assets/images/default-profile.png";
}


/* =========================
   GET LAST PHONE NUMBER
========================= */

$last_phone = '';

$phone_query = mysqli_query($conn, "
    SELECT phone
    FROM bookings
    WHERE user_id='$user_id'
    AND phone IS NOT NULL
    AND phone != ''
    ORDER BY id DESC
    LIMIT 1
");

if ($phone_query && mysqli_num_rows($phone_query) > 0) {
    $phone_row = mysqli_fetch_assoc($phone_query);
    $last_phone = $phone_row['phone'] ?? '';
}


/* =========================
   SUBMIT BOOKING
========================= */

if (isset($_POST['submit_booking'])) {

    $name = $_POST['customer_name'] ?? '';
    $phone = $_POST['phone'] ?? '';

    $service_id = $_POST['service_id'] ?? '';
    $service = $_POST['service'] ?? '';

    $duration = $_POST['duration'] ?? '';

    /*
       IMPORTANT:
       price now contains:
       SERVICE/DURATION PRICE + ADD-ON PRICES
    */
    $price = $_POST['price'] ?? 0;

    $date = $_POST['booking_date'] ?? '';
    $time = $_POST['booking_time'] ?? '';

    $pax = (int)($_POST['pax'] ?? 1);

    $payment = $_POST['payment_method'] ?? 'Cash';

    $notes = $_POST['notes'] ?? '';

    $therapist = (int)($_POST['therapist'] ?? 0);
    $selected_therapists = $_POST['therapists'] ?? [];
    if (!is_array($selected_therapists)) { $selected_therapists = []; }
    $selected_therapists = array_values(array_unique(array_filter(array_map('intval', $selected_therapists), static fn($id) => $id > 0)));
    $pax = max(1, min(4, $pax));

    $addons = $_POST['addons'] ?? '';

    $room_type = $_POST['room_type'] ?? '';
    $additional_guest_names = array_values(array_filter(array_map('trim', (array)($_POST['guest_names'] ?? [])), static fn($v) => $v !== ''));
    // The primary Full Name is Guest 1; only additional guests are entered below.
    $guest_names = array_merge([trim((string)$name)], $additional_guest_names);
    if ($room_type === 'Couple Room') { $pax = 2; }
    if (count($selected_therapists) > $pax) { $selected_therapists = array_slice($selected_therapists, 0, $pax); }
    if (!empty($selected_therapists)) { $therapist = $selected_therapists[0]; }

    /* =========================
       COUPLE ROOM = 2 PAX
    ========================= */

    if ($room_type === "Couple Room") {
        $pax = 2;
    }


    /* =========================
       ESCAPE VALUES
    ========================= */

    $name = mysqli_real_escape_string($conn, $name);

    $phone = mysqli_real_escape_string($conn, $phone);

    $service_id = (int)$service_id;

    $service = mysqli_real_escape_string($conn, $service);

    $duration = mysqli_real_escape_string($conn, $duration);

    $price = (float)$price;
    if (count($guest_names) !== $pax) { die('Please enter the name of every guest. Go back and complete the guest details.'); }
    // The form already submits the total for all guests; do not multiply by pax again.

    $date = mysqli_real_escape_string($conn, $date);

    $time = mysqli_real_escape_string($conn, $time);

    $payment = mysqli_real_escape_string($conn, $payment);

    $notes = mysqli_real_escape_string($conn, $notes);

    $therapist = (int)$therapist;

    $addons = mysqli_real_escape_string($conn, $addons);

    $room_type = mysqli_real_escape_string($conn, $room_type);


    /* =========================
       SERVER-SIDE DATE/TIME VALIDATION
       ========================= */
    $dateTimeZone = new DateTimeZone('Asia/Manila');
    $nowManila = new DateTime('now', $dateTimeZone);
    $selectedDateTime = DateTime::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $dateTimeZone);
    $dayOfWeek = (int)date('w', strtotime($date));
    $openingHour = ($dayOfWeek === 0 || $dayOfWeek === 6) ? 13 : 15;
    if ($selectedDateTime && (int)substr($time, 0, 2) < $openingHour) {
        $selectedDateTime->modify('+1 day');
    }
    if (!$selectedDateTime || $selectedDateTime < (clone $nowManila)->modify('+2 hours')) {
        die('Please choose a booking time at least 2 hours from now.');
    }

    /* =========================
       INSERT BOOKING
    ========================= */

    $sql = "
        INSERT INTO bookings
        (
            user_id,
            service,
            duration,
            price,
            customer_name,
            phone,
            booking_date,
            booking_time,
            pax,
            payment_method,
            notes,
            addons,
            therapist_id,
            room_type,
            status
        )
        VALUES
        (
            '$user_id',
            '$service',
            '$duration',
            '$price',
            '$name',
            '$phone',
            '$date',
            '$time',
            '$pax',
            '$payment',
            '$notes',
            '$addons',
            '$therapist',
            '$room_type',
            'Pending'
        )
    ";


    if (mysqli_query($conn, $sql)) {

        $booking_id = mysqli_insert_id($conn);

        // Match the current booking_guests table structure.
        // duration, price, addons, and therapist_id are stored in bookings / booking_therapists.
        $guestStmt = $conn->prepare("INSERT INTO booking_guests (booking_id, guest_number, guest_name, service_id, service, phone) VALUES (?, ?, ?, ?, ?, ?)");
        if ($guestStmt) {
            foreach ($guest_names as $idx => $guestName) {
                $guestNumber = $idx + 1;
                $guestStmt->bind_param('iisiss', $booking_id, $guestNumber, $guestName, $service_id, $service, $phone);
                if (!$guestStmt->execute()) { die('Unable to save guest details: ' . $guestStmt->error); }
            }
            $guestStmt->close();
        } else { die('Unable to prepare guest details: ' . $conn->error); }

        /*
           Keep booking_therapists synchronized
           when customer selected a therapist.
        */

        if (!empty($selected_therapists)) {
            $map = $conn->prepare("INSERT INTO booking_therapists (booking_id, therapist_id, assigned_by) VALUES (?, ?, 'customer')");
            if ($map) {
                foreach ($selected_therapists as $selected_id) {
                    $map->bind_param('ii', $booking_id, $selected_id);
                    $map->execute();
                }
                $map->close();
            }
        } elseif ($therapist > 0) {
            $map = $conn->prepare("INSERT INTO booking_therapists (booking_id, therapist_id, assigned_by) VALUES (?, ?, 'customer')");
            if ($map) {
                $map->bind_param('ii', $booking_id, $therapist);
                $map->execute();
                $map->close();
            }
        }

        header(
            "Location: thankyou.php?id=" .
            $booking_id
        );

        exit;

    } else {

        die(
            "Booking Error: " .
            mysqli_error($conn)
        );

    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>Book an Appointment</title>

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

html{
    scroll-behavior:smooth;
}

body{

    background:
        linear-gradient(
            rgba(0,0,0,.82),
            rgba(0,0,0,.92)
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

    background:
        rgba(10,10,10,.95);

    border-bottom:
        1px solid
        rgba(214,194,156,.15);

    position:sticky;

    top:0;

    z-index:1000;

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

.profile-mini{

    width:36px;
    height:36px;

    object-fit:cover;

    border-radius:50%;

    border:
        2px solid
        #D6C29C;

    margin-left:8px;

}


/* =========================
   PAGE
========================= */

.page{

    width:100%;

    max-width:1250px;

    margin:auto;

    padding:
        38px 5%
        70px;

}


/* =========================
   TITLE
========================= */

.page-heading{

    margin-bottom:28px;

}

.page-heading h1{

    font-family:
        'Playfair Display',
        serif;

    color:#D6C29C;

    font-size:32px;

    margin-bottom:7px;

}

.page-heading p{

    color:#888;

    font-size:12px;

    line-height:1.7;

    max-width:650px;

}


/* =========================
   LAYOUT
========================= */

.booking-layout{

    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 22px;
    align-items: start;

}


/* =========================
   SECTION
========================= */

.section{

    background:
        rgba(18,18,18,.91);

    border:
        1px solid
        rgba(255,255,255,.07);

    border-radius:16px;

    padding:22px;

    margin-bottom:15px;

    box-shadow:
        0 12px 30px
        rgba(0,0,0,.18);

}

.section-head{

    display:flex;

    align-items:flex-start;

    gap:12px;

    margin-bottom:18px;

}

.step{

    width:29px;
    height:29px;

    min-width:29px;

    display:flex;

    justify-content:center;
    align-items:center;

    border-radius:50%;

    background:
        rgba(214,194,156,.10);

    border:
        1px solid
        rgba(214,194,156,.25);

    color:#D6C29C;

    font-size:11px;

    font-weight:600;

}

.section-title{

    color:#D6C29C;

    font-size:14px;

    font-weight:600;

    margin-bottom:3px;

}

.section-sub{

    color:#666;

    font-size:10px;

    line-height:1.5;

}


/* =========================
   SUB LABEL
========================= */

.field-title{

    color:#aaa;

    font-size:10px;

    font-weight:500;

    text-transform:uppercase;

    letter-spacing:.6px;

    margin:
        18px 0
        9px;

}

.field-title:first-child{

    margin-top:0;

}


/* =========================
   GRID
========================= */

.selection-grid{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(130px,1fr)
        );

    gap:9px;

}


/* =========================
   SELECTABLE CARD
========================= */

.select-card{

    position:relative;

    background:#101010;

    border:
        1px solid
        #292929;

    border-radius:10px;

    padding:13px 10px;

    text-align:center;

    cursor:pointer;

    font-size:11px;

    color:#ccc;

    transition:.2s;

    min-height:48px;

    display:flex;

    flex-direction:column;

    justify-content:center;
    align-items:center;

}

.select-card:hover{

    border-color:
        rgba(214,194,156,.55);

    color:#fff;

    transform:
        translateY(-1px);

}

.select-card.active{

    border:
        1px solid
        #D6C29C;

    background:
        rgba(214,194,156,.08);

    color:#D6C29C;

    box-shadow:
        0 0 0 1px
        rgba(214,194,156,.05);

}

.select-card.unavailable{

    opacity:.30;

    cursor:not-allowed;

    border-color:#382626;

}

.select-card.dim{

    opacity:.28;

    cursor:not-allowed;

    pointer-events:none;

}

.card-price{

    color:#D6C29C;

    font-size:10px;

    margin-top:3px;

}

.card-desc{

    color:#666;

    font-size:9px;

    margin-top:4px;

    line-height:1.4;

}

.available-text{

    color:#9ab99a;

}

.unavailable-text{

    color:#bd8585;

}


/* =========================
   SERVICE DESCRIPTION
========================= */

.service-description{

    margin-top:11px;

    padding:11px 13px;

    background:#0e0e0e;

    border-left:
        2px solid
        #D6C29C;

    border-radius:
        0 8px 8px 0;

    color:#888;

    font-size:10px;

    line-height:1.7;

}


/* =========================
   INPUTS
========================= */

.form-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:14px;

}

.form-group{

    margin-bottom:2px;

}

.full{

    grid-column:
        1 / -1;

}

label{

    display:block;

    color:#999;

    font-size:10px;

    margin-bottom:6px;

}

.required{

    color:#D6C29C;

}

input,
select,
textarea{

    width:100%;

    padding:11px 12px;

    background:#0f0f0f;

    color:#fff;

    border:
        1px solid
        #303030;

    border-radius:9px;

    outline:none;

    font-size:11px;

    transition:.2s;

}

input:focus,
select:focus,
textarea:focus{

    border-color:#D6C29C;

    box-shadow:
        0 0 7px
        rgba(214,194,156,.12);

}

input::placeholder,
textarea::placeholder{

    color:#555;

}

select option{

    background:#111;

}

textarea{

    min-height:90px;

    resize:vertical;

    line-height:1.6;

}

input:read-only{

    opacity:.65;

}

/* Keep the native calendar icon compact without enlarging the date field. */
input[type="date"] {
    color-scheme: dark;
    cursor: pointer;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    width: 13px;
    height: 13px;
    padding: 0;
    margin: 0 1px 0 4px;
    opacity: .9;
    cursor: pointer;
    filter: brightness(0) invert(1);
}


/* =========================
   SCHEDULE GRID
========================= */

.schedule-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:16px;

}

.schedule-block{

    min-width:0;

}


/* =========================
   TIME
========================= */

.time-grid{

    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:8px;

}

.time-card{

    background:#101010;

    border:
        1px solid
        #292929;

    border-radius:9px;

    padding:10px 5px;

    text-align:center;

    cursor:pointer;

    color:#ccc;

    font-size:10px;

    transition:.2s;

}

.time-card:hover{

    border-color:#D6C29C;

}

.time-card.active{

    border-color:#D6C29C;

    background:
        rgba(214,194,156,.08);

    color:#D6C29C;

}

.time-card.dim{

    opacity:.25;

    pointer-events:none;

}

.slot-count{

    display:block;

    color:#666;

    font-size:8px;

    margin-top:3px;

}


/* =========================
   MESSAGE
========================= */

.message{

    color:#777;

    font-size:10px;

    margin-top:10px;

    line-height:1.6;

}


/* =========================
   SUMMARY
========================= */


.summary-sticky {
    position: sticky;
    top: 86px;
    align-self: start;
}

.summary-panel {
    position: static;

    background: rgba(18,18,18,.96);
    border: 1px solid rgba(214,194,156,.17);
    border-radius: 16px;
    padding: 22px;

    box-shadow: 0 16px 40px rgba(0,0,0,.30);
}


.summary-panel h3{

    font-family:
        'Playfair Display',
        serif;

    color:#D6C29C;

    font-size:20px;

    margin-bottom:5px;

}

.summary-sub{

    color:#666;

    font-size:9px;

    margin-bottom:19px;

}

.summary-row{

    display:flex;

    justify-content:space-between;

    gap:12px;

    padding:10px 0;

    border-bottom:
        1px solid
        #272727;

}

.summary-label{

    color:#777;

    font-size:9px;

}

.summary-value{

    color:#ddd;

    font-size:10px;

    text-align:right;

    max-width:175px;

}

.summary-addons{

    color:#aaa;

    font-size:9px;

    line-height:1.6;

}

.price-area{

    padding:
        15px 0 5px;

}

.price-line{

    display:flex;

    justify-content:space-between;

    gap:10px;

    margin-bottom:7px;

    color:#888;

    font-size:9px;

}

.total-line{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-top:12px;

    padding-top:13px;

    border-top:
        1px solid
        rgba(214,194,156,.18);

}

.total-label{

    color:#aaa;

    font-size:11px;

}

.total-price{

    color:#D6C29C;

    font-size:21px;

    font-weight:600;

}


/* =========================
   BOOK BUTTON
========================= */

.book-btn{

    width:100%;

    padding:13px;

    margin-top:17px;

    border:none;

    border-radius:9px;

    background:#D6C29C;

    color:#111;

    font-size:11px;

    font-weight:700;

    cursor:pointer;

    transition:.2s;

}

.book-btn:hover{

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 20px
        rgba(214,194,156,.18);

}

.booking-note{

    margin-top:10px;

    color:#555;

    font-size:8px;

    text-align:center;

    line-height:1.5;

}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:1000px){

    .booking-layout{

        grid-template-columns:1fr;

    }

    .summary-sticky{

        position:static;

    }

}

@media(max-width:750px){

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

    .page{

        padding:
            28px 4%
            50px;

    }

    .schedule-grid{

        grid-template-columns:1fr;

    }

    .time-grid{

        grid-template-columns:
            repeat(3,1fr);

    }

}

@media(max-width:550px){

    .page-heading h1{

        font-size:27px;

    }

    .section{

        padding:17px;

    }

    .form-grid{

        grid-template-columns:1fr;

    }

    .full{

        grid-column:auto;

    }

    .selection-grid{

        grid-template-columns:
            repeat(2,1fr);

    }

    .time-grid{

        grid-template-columns:
            repeat(2,1fr);

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
            src="<?= htmlspecialchars($profile_image) ?>"
            class="profile-mini"
            alt="Profile"
        >

    </nav>

</header>


<!-- =========================
     PAGE
========================= -->

<div class="page">


    <div class="page-heading">

        <h1>
            Book an Appointment
        </h1>

        <p>
            Choose your preferred service, schedule,
            room and therapist. Review your booking
            details before confirming your appointment.
        </p>

    </div>


    <form
        method="POST"
        id="bookingForm"
    >


    <div class="booking-layout">


        <!-- =========================
             LEFT SIDE
        ========================= -->

        <div>


            <!-- =====================
                 STEP 1
            ====================== -->

            <div class="section">

                <div class="section-head">

                    <div class="step">
                        1
                    </div>

                    <div>

                        <div class="section-title">
                            Choose Your Service
                        </div>

                        <div class="section-sub">
                            Select a category, service,
                            duration and optional add-ons.
                        </div>

                    </div>

                </div>


                <!-- CATEGORY -->

                <div class="field-title">
                    Category
                </div>

                <div class="selection-grid">

                    <div
                        class="select-card category"
                        data-cat="Massage"
                    >
                        Massage
                    </div>

                    <div
                        class="select-card category"
                        data-cat="Package"
                    >
                        Package
                    </div>

                    <div
                        class="select-card category"
                        data-cat="Promo"
                    >
                        Promo
                    </div>

                </div>


                <!-- SERVICE -->

                <div class="field-title">
                    Service
                </div>

                <div
                    class="selection-grid"
                    id="serviceBox"
                >

                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        Select a category first.
                    </div>

                </div>


                <div
                    id="serviceDesc"
                    class="service-description"
                >
                    Select a service to view
                    its description.
                </div>


                <!-- DURATION -->

                <div class="field-title">
                    Duration
                </div>

                <div
                    class="selection-grid"
                    id="durationBox"
                >

                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        Select a service first.
                    </div>

                </div>


                <!-- ADDONS -->

                <div class="field-title">
                    Add-ons
                    <span style="
                        color:#555;
                        text-transform:none;
                    ">
                        (Optional)
                    </span>
                </div>

                <div
                    class="selection-grid"
                    id="addonBox"
                >

                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        Add-ons will appear
                        after selecting a service.
                    </div>

                </div>

                <input
                    type="hidden"
                    name="addons"
                    id="addons"
                >

            </div>


            <!-- =====================
                 STEP 2
            ====================== -->

            <div class="section">

                <div class="section-head">

                    <div class="step">
                        2
                    </div>

                    <div>

                        <div class="section-title">
                            Schedule & Room
                        </div>

                        <div class="section-sub">
                            Choose your preferred room,
                            appointment date and available
                            time.
                        </div>

                    </div>

                </div>


                <!-- ROOM -->

                <div class="field-title">
                    Room Type
                </div>

                <div class="selection-grid">

                    <div
                        class="select-card room"
                        data-room="Single Room"
                    >

                        Single Room

                        <div class="card-desc">
                            Individual booking
                        </div>

                    </div>

                    <div
                        class="select-card room"
                        data-room="Couple Room"
                    >

                        Couple Room

                        <div class="card-desc">
                            Automatically 2 pax
                        </div>

                    </div>

                </div>


                <input
                    type="hidden"
                    name="room_type"
                    id="room_type"
                >


                <div
                    class="schedule-grid"
                    style="margin-top:18px;"
                >


                    <!-- DATE -->

                    <div class="schedule-block">

                        <label for="booking_date">

                            Appointment Date
                            <span class="required">*</span>

                        </label>

                        <input
                            type="date"
                            id="booking_date"
                            name="booking_date"
                            required
                        >

                    </div>


                    <!-- PAX -->

                    <div class="schedule-block">

                        <label for="pax">

                            Number of Guests
                            <span class="required">*</span>

                        </label>

                        <input
                            type="number"
                            id="pax"
                            name="pax"
                            value="1"
                            min="1"
                            max="4"
                            required
                        >

                    </div>


                </div>


                <!-- TIME -->

                <div class="field-title">
                    Available Time
                </div>

                <div
                    class="time-grid"
                    id="timeBox"
                >

                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        Select an appointment
                        date first.
                    </div>

                </div>


                <input
                    type="hidden"
                    name="booking_time"
                    id="booking_time"
                >

            </div>


            <!-- =====================
                 STEP 3
            ====================== -->

            <div class="section">

                <div class="section-head">

                    <div class="step">
                        3
                    </div>

                    <div>

                        <div class="section-title">
                            Choose Your Therapist
                        </div>

                        <div class="section-sub">
                            Therapist availability is based
                            on your selected date, time and
                            service duration.
                        </div>

                    </div>

                </div>


                <div
                    class="selection-grid"
                    id="therapistBox"
                ></div>


                <div
                    id="therapistMessage"
                    class="message"
                >
                    Select a date and time first.
                </div>


                <input
                    type="hidden"
                    name="therapist"
                    id="therapist"
                >

                <div id="selectedTherapistsInputs"></div>

            </div>


            <!-- =====================
                 STEP 4
            ====================== -->

            <div class="section">

                <div class="section-head">

                    <div class="step">
                        4
                    </div>

                    <div>

                        <div class="section-title">
                            Customer Details
                        </div>

                        <div class="section-sub">
                            Confirm your contact information
                            and select your payment method.
                        </div>

                    </div>

                </div>


                <div class="form-grid">


                    <!-- NAME -->

                    <div class="form-group">

                        <label>

                            Full Name
                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            name="customer_name"
                            value="<?= htmlspecialchars($user_name) ?>"
                            placeholder="Full name"
                            required
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>

                            Phone Number
                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="<?= htmlspecialchars($last_phone) ?>"
                            placeholder="09XXXXXXXXX"
                            required
                        >

                    </div>


                    <!-- PER-PAX NAMES -->
                    <div class="form-group full" id="guestNamesGroup">
                        <label>Names of Guests <span style="color:#555; text-transform:none;">(required for each guest)</span></label>
                        <div id="guestNamesFields"></div>
                        <p class="message">Your Full Name is Guest 1. Enter only the other guests below.</p>
                    </div>

                    <!-- PAYMENT -->

                    <div class="form-group full">

                        <label>

                            Payment Method
                            <span class="required">*</span>

                        </label>

                        <select
                            name="payment_method"
                            required
                        >

                            <option value="Cash">
                                Cash
                            </option>

                            <option value="GCash">
                                GCash
                            </option>

                        </select>

                    </div>


                    <!-- NOTES -->

                    <div class="form-group full">

                        <label>

                            Notes / Special Request

                            <span style="color:#555;">
                                (Optional)
                            </span>

                        </label>

                        <textarea
                            name="notes"
                            placeholder="Any additional request for your appointment..."
                        ></textarea>

                    </div>


                </div>

            </div>


        </div>


        <!-- =========================
             RIGHT SUMMARY
        ========================= -->

        <div class="summary-sticky">


            <div
                class="summary-panel"
                id="summaryBox"
            >

                <h3>
                    Booking Summary
                </h3>

                <div class="summary-sub">
                    Review your appointment before booking.
                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Service
                    </span>

                    <span
                        class="summary-value"
                        id="sumService"
                    >
                        —
                    </span>

                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Duration
                    </span>

                    <span
                        class="summary-value"
                        id="sumDuration"
                    >
                        —
                    </span>

                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Room
                    </span>

                    <span
                        class="summary-value"
                        id="sumRoom"
                    >
                        —
                    </span>

                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Date
                    </span>

                    <span
                        class="summary-value"
                        id="sumDate"
                    >
                        —
                    </span>

                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Time
                    </span>

                    <span
                        class="summary-value"
                        id="sumTime"
                    >
                        —
                    </span>

                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Therapist
                    </span>

                    <span
                        class="summary-value"
                        id="sumTherapist"
                    >
                        —
                    </span>

                </div>


                <div class="summary-row">

                    <span class="summary-label">
                        Add-ons
                    </span>

                    <span
                        class="summary-value summary-addons"
                        id="sumAddons"
                    >
                        None
                    </span>

                </div>


                <!-- PRICE -->

                <div class="price-area">

                    <div class="price-line">

                        <span>
                            Service
                        </span>

                        <span id="sumBasePrice">
                            ₱0.00
                        </span>

                    </div>


                    <div class="price-line">

                        <span>
                            Add-ons
                        </span>

                        <span id="sumAddonPrice">
                            ₱0.00
                        </span>

                    </div>


                    <div class="total-line">

                        <span class="total-label">
                            Total
                        </span>

                        <span
                            class="total-price"
                            id="sumTotal"
                        >
                            ₱0.00
                        </span>

                    </div>

                </div>


                <button
                    class="book-btn"
                    type="submit"
                    name="submit_booking"
                >
                    Confirm Booking
                </button>


                <div class="booking-note">
                    Your booking will initially be
                    submitted as Pending.
                </div>

            </div>


        </div>


    </div>


    <!-- =========================
         HIDDEN VALUES
    ========================= -->

    <input
        type="hidden"
        name="service_id"
        id="service_id"
    >

    <input
        type="hidden"
        name="service"
        id="service"
    >

    <input
        type="hidden"
        name="duration"
        id="duration"
    >

    <input
        type="hidden"
        name="price"
        id="price"
        value="0"
    >


    </form>


</div>


<script>

/* =========================
   ELEMENTS
========================= */

const serviceBox =
    document.getElementById('serviceBox');

const serviceDesc =
    document.getElementById('serviceDesc');

const durationBox =
    document.getElementById('durationBox');

const addonBox =
    document.getElementById('addonBox');

const timeBox =
    document.getElementById('timeBox');

const therapistBox =
    document.getElementById('therapistBox');

// customerPaxMaxFourApplied: keep the customer form limited to four guests.
const customerPaxInput = document.getElementById('pax');
if (customerPaxInput) {
    customerPaxInput.max = '4';
    customerPaxInput.addEventListener('input', function () {
        const value = parseInt(this.value || '1', 10);
        if (value > 4) this.value = '4';
        if (value < 1) this.value = '1';
        const limit = Math.max(1, Math.min(4, Number(this.value || 1)));
        if (selectedTherapistIds.length > limit) {
            selectedTherapistIds = selectedTherapistIds.slice(0, limit);
            syncSelectedTherapists();
            therapistBox.querySelectorAll('.therapist').forEach(card => {
                card.classList.toggle('active', selectedTherapistIds.includes(String(card.dataset.id)));
            });
            updateTherapistSummary(selectedTherapistIds.map(id => therapistBox.querySelector('.therapist[data-id="' + id + '"]')?.dataset.name || id));
        }
    });
}

const therapistMessage =
    document.getElementById('therapistMessage');

const serviceIdInput =
    document.getElementById('service_id');

const serviceInput =
    document.getElementById('service');

const durationInput =
    document.getElementById('duration');

const priceInput =
    document.getElementById('price');

const addonsInput =
    document.getElementById('addons');

const roomInput =
    document.getElementById('room_type');

const bookingDate =
    document.getElementById('booking_date');

const bookingTime =
    document.getElementById('booking_time');

const therapistInput =
    document.getElementById('therapist');

let selectedTherapistIds = [];
const selectedTherapistsInputs = document.getElementById('selectedTherapistsInputs');

function syncSelectedTherapists() {
    therapistInput.value = selectedTherapistIds.length ? selectedTherapistIds[0] : '0';
    selectedTherapistsInputs.innerHTML = '';
    selectedTherapistIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'therapists[]'; input.value = id;
        selectedTherapistsInputs.appendChild(input);
    });
}

function updateTherapistSummary(names) {
    summary.therapist = names.length ? names.join(', ') : 'No Preference';
    renderSummary();
}

const paxInput =
    document.getElementById('pax');

// Full Name is saved as Guest 1. Render inputs only for Guest 2 onward.
function renderGuestNameFields() {
    const wrap = document.getElementById('guestNamesFields');
    if (!wrap || !paxInput) return;
    const count = roomInput && roomInput.value === 'Couple Room' ? 2 : Math.max(1, Math.min(4, Number(paxInput.value || 1)));
    const old = Array.from(wrap.querySelectorAll('input[name="guest_names[]"]')).map(x => x.value);
    wrap.innerHTML = '';
    for (let guestNumber = 2; guestNumber <= count; guestNumber++) {
        const label = document.createElement('label');
        label.textContent = 'Guest ' + guestNumber + ' Full Name';
        label.style.marginTop = '10px';
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'guest_names[]';
        input.required = true;
        input.placeholder = 'Enter Guest ' + guestNumber + ' full name';
        input.value = old[guestNumber - 2] || '';
        wrap.appendChild(label);
        wrap.appendChild(input);
    }
}



/* =========================
   PRICE VARIABLES
========================= */

let basePrice = 0;

let addonTotal = 0;


/* =========================
   SUMMARY
========================= */

let summary = {

    service:'—',

    duration:'—',

    room:'—',

    date:'—',

    time:'—',

    therapist:'—',

    addons:'None'

};


function money(value){

    return '₱' +
        Number(value || 0)
        .toLocaleString(
            'en-PH',
            {
                minimumFractionDigits:2,
                maximumFractionDigits:2
            }
        );

}


function renderSummary(){

    document.getElementById(
        'sumService'
    ).textContent =
        summary.service;

    document.getElementById(
        'sumDuration'
    ).textContent =
        summary.duration;

    document.getElementById(
        'sumRoom'
    ).textContent =
        summary.room;

    document.getElementById(
        'sumDate'
    ).textContent =
        summary.date;

    document.getElementById(
        'sumTime'
    ).textContent =
        summary.time;

    document.getElementById(
        'sumTherapist'
    ).textContent =
        summary.therapist;

    document.getElementById(
        'sumAddons'
    ).textContent =
        summary.addons;


    document.getElementById(
        'sumBasePrice'
    ).textContent =
        money(basePrice);

    document.getElementById(
        'sumAddonPrice'
    ).textContent =
        money(addonTotal);


    const perGuestTotal = Number(basePrice) + Number(addonTotal);
    const guestCount = roomInput?.value === 'Couple Room' ? 2 : Math.max(1, Number(paxInput?.value || 1));
    const total = perGuestTotal * guestCount;


    document.getElementById(
        'sumTotal'
    ).textContent =
        money(total);


    /*
       SAVE TOTAL TO BOOKING PRICE
    */

    priceInput.value =
        total.toFixed(2);

}


/* =========================
   RESET AFTER CATEGORY
========================= */

function resetServiceSelection(){

    serviceIdInput.value = '';

    serviceInput.value = '';

    durationInput.value = '';

    basePrice = 0;

    addonTotal = 0;

    addonsInput.value = '';

    summary.service = '—';

    summary.duration = '—';

    summary.addons = 'None';

    serviceDesc.textContent =
        'Select a service to view its description.';

    durationBox.innerHTML =
        '<div class="message" style="grid-column:1/-1;">Select a service first.</div>';

    addonBox.innerHTML =
        '<div class="message" style="grid-column:1/-1;">Add-ons will appear after selecting a service.</div>';

    renderSummary();

}


/* =========================
   CATEGORY
========================= */

document.addEventListener(
    'click',
    function(e){

        const category =
            e.target.closest('.category');

        if(!category){
            return;
        }


        document
        .querySelectorAll('.category')
        .forEach(card => {

            card.classList.remove(
                'active'
            );

        });


        category.classList.add(
            'active'
        );


        resetServiceSelection();


        serviceBox.innerHTML =
            '<div class="message" style="grid-column:1/-1;">Loading services...</div>';


        fetch(
            '../get_services_by_category.php?cat=' +
            encodeURIComponent(
                category.dataset.cat
            )
        )

        .then(response =>
            response.json()
        )

        .then(data => {

            serviceBox.innerHTML = '';


            if(
                !Array.isArray(data) ||
                data.length === 0
            ){

                serviceBox.innerHTML =
                    '<div class="message" style="grid-column:1/-1;">No services available in this category.</div>';

                return;

            }


            data.forEach(s => {

                const card =
                    document.createElement(
                        'div'
                    );

                card.className =
                    'select-card service';

                card.dataset.id =
                    s.id;

                card.dataset.name =
                    s.service_name;

                card.dataset.desc =
                    s.description || '';

                card.textContent =
                    s.service_name;


                serviceBox.appendChild(
                    card
                );

            });

        })

        .catch(error => {

            console.error(
                'Service error:',
                error
            );

            serviceBox.innerHTML =
                '<div class="message" style="grid-column:1/-1;">Unable to load services.</div>';

        });

    }
);


/* =========================
   SERVICE
========================= */

document.addEventListener(
    'click',
    function(e){

        const selectedService =
            e.target.closest('.service');

        if(!selectedService){
            return;
        }


        document
        .querySelectorAll('.service')
        .forEach(card => {

            card.classList.remove(
                'active'
            );

        });


        selectedService.classList.add(
            'active'
        );


        serviceIdInput.value =
            selectedService.dataset.id;

        serviceInput.value =
            selectedService.dataset.name;


        serviceDesc.textContent =
            selectedService.dataset.desc ||
            'No description available.';


        summary.service =
            selectedService.dataset.name;

        summary.duration = '—';

        summary.addons = 'None';

        durationInput.value = '';

        addonsInput.value = '';

        basePrice = 0;

        addonTotal = 0;

        renderSummary();


        /* =====================
           LOAD DURATION
        ====================== */

        durationBox.innerHTML =
            '<div class="message" style="grid-column:1/-1;">Loading durations...</div>';


        fetch(
            '../get_duration.php?id=' +
            encodeURIComponent(
                selectedService.dataset.id
            )
        )

        .then(response =>
            response.json()
        )

        .then(data => {

            durationBox.innerHTML = '';


            if(
                !Array.isArray(data) ||
                data.length === 0
            ){

                durationBox.innerHTML =
                    '<div class="message" style="grid-column:1/-1;">No duration available.</div>';

                return;

            }


            data.forEach(item => {

                const card =
                    document.createElement(
                        'div'
                    );

                card.className =
                    'select-card duration';

                card.dataset.duration =
                    item.duration;

                card.dataset.price =
                    item.price;


                card.innerHTML = `
                    <span>
                        ${item.duration}
                    </span>

                    <span class="card-price">
                        ${money(item.price)}
                    </span>
                `;


                durationBox.appendChild(
                    card
                );

            });

        })

        .catch(error => {

            console.error(
                'Duration error:',
                error
            );

            durationBox.innerHTML =
                '<div class="message" style="grid-column:1/-1;">Unable to load durations.</div>';

        });


        /* =====================
           LOAD ADDONS
        ====================== */

        addonBox.innerHTML =
            '<div class="message" style="grid-column:1/-1;">Loading add-ons...</div>';


        fetch('../get_addons.php')

        .then(response =>
            response.json()
        )

        .then(data => {

            addonBox.innerHTML = '';


            if(
                !Array.isArray(data) ||
                data.length === 0
            ){

                addonBox.innerHTML =
                    '<div class="message" style="grid-column:1/-1;">No add-ons available.</div>';

                return;

            }


            data.forEach(addon => {

                const card =
                    document.createElement(
                        'div'
                    );

                card.className =
                    'select-card addon';

                card.dataset.name =
                    addon.service_name;

                card.dataset.price =
                    addon.price;


                card.innerHTML = `
                    <span>
                        ${addon.service_name}
                    </span>

                    <span class="card-price">
                        ${money(addon.price)}
                    </span>

                    ${
                        addon.description
                        ?
                        `<span class="card-desc">
                            ${addon.description}
                        </span>`
                        :
                        ''
                    }
                `;


                addonBox.appendChild(
                    card
                );

            });

        })

        .catch(error => {

            console.error(
                'Addon error:',
                error
            );

            addonBox.innerHTML =
                '<div class="message" style="grid-column:1/-1;">Unable to load add-ons.</div>';

        });

    }
);


/* =========================
   DURATION
========================= */

document.addEventListener(
    'click',
    function(e){

        const selectedDuration =
            e.target.closest('.duration');

        if(!selectedDuration){
            return;
        }


        document
        .querySelectorAll('.duration')
        .forEach(card => {

            card.classList.remove(
                'active'
            );

        });


        selectedDuration.classList.add(
            'active'
        );


        durationInput.value =
            selectedDuration.dataset.duration;


        basePrice =
            parseFloat(
                selectedDuration.dataset.price
            ) || 0;


        summary.duration =
            selectedDuration.dataset.duration;


        renderSummary();


        /*
           Reload therapists because
           duration affects availability.
        */

        if(
            bookingDate.value &&
            bookingTime.value
        ){

            loadTherapists(
                bookingDate.value,
                bookingTime.value
            );

        }

    }
);


/* =========================
   ADD-ONS
========================= */

document.addEventListener(
    'click',
    function(e){

        const selectedAddon =
            e.target.closest('.addon');

        if(!selectedAddon){
            return;
        }


        selectedAddon.classList.toggle(
            'active'
        );


        let names = [];

        addonTotal = 0;


        document
        .querySelectorAll(
            '.addon.active'
        )
        .forEach(card => {

            names.push(
                card.dataset.name
            );

            addonTotal +=
                parseFloat(
                    card.dataset.price
                ) || 0;

        });


        addonsInput.value =
            names.join(', ');


        summary.addons =
            names.length
            ? names.join(', ')
            : 'None';


        renderSummary();

    }
);


/* =========================
   ROOM
========================= */

document.addEventListener(
    'click',
    function(e){

        const room =
            e.target.closest('.room');

        if(!room){
            return;
        }


        document
        .querySelectorAll('.room')
        .forEach(card => {

            card.classList.remove(
                'active'
            );

        });


        room.classList.add(
            'active'
        );


        roomInput.value =
            room.dataset.room;


        summary.room =
            room.dataset.room;


        if(
            room.dataset.room ===
            'Couple Room'
        ){

            paxInput.value = 2;

            paxInput.readOnly = true;

        }else{

            paxInput.value = 1;

            paxInput.readOnly = false;

        }


        renderSummary();

        // Re-check the selected date using the newly selected room type.
        if (bookingDate.value) {
            bookingDate.dispatchEvent(new Event('change'));
        }

    }
);


/* =========================
   MINIMUM DATE
========================= */

const today =
    new Date();

const year =
    today.getFullYear();

const month =
    String(
        today.getMonth() + 1
    ).padStart(2,'0');

const day =
    String(
        today.getDate()
    ).padStart(2,'0');

bookingDate.min =
    `${year}-${month}-${day}`;


/* =========================
   RESET THERAPIST
========================= */

function resetTherapistDisplay(){

    therapistBox.innerHTML = '';

    therapistMessage.textContent =
        'Select a time to view available therapists.';

    selectedTherapistIds = [];
    syncSelectedTherapists();

    summary.therapist = '—';

    renderSummary();

}


/* =========================
   LOAD THERAPISTS
========================= */

function loadTherapists(
    selectedDate,
    selectedTime
){

    therapistBox.innerHTML = '';

    therapistMessage.textContent =
        'Loading therapists...';

    selectedTherapistIds = [];
    syncSelectedTherapists();

    summary.therapist = '—';

    renderSummary();


    let selectedDuration =
        durationInput.value ||
        '1 Hour';


    fetch(
        '../get_available_therapists.php?date=' +
        encodeURIComponent(
            selectedDate
        ) +
        '&time=' +
        encodeURIComponent(
            selectedTime
        ) +
        '&duration=' +
        encodeURIComponent(
            selectedDuration
        )
    )

    .then(response =>
        response.json()
    )

    .then(data => {

        therapistBox.innerHTML = '';


        if(
            !Array.isArray(data) ||
            data.length === 0
        ){

            therapistMessage.textContent =
                'No therapists found.';

            return;

        }


        therapistMessage.textContent =
            'Choose up to the number of guests, or select No Preference.';

        const noPref = document.createElement('div');
        noPref.className = 'select-card therapist active';
        noPref.dataset.id = '0';
        noPref.dataset.name = 'No Preference';
        noPref.innerHTML = '<strong>No Preference</strong><div class="card-desc">Assign later</div>';
        noPref.onclick = function(){
            selectedTherapistIds = [];
            syncSelectedTherapists();
            therapistBox.querySelectorAll('.therapist').forEach(item => item.classList.remove('active'));
            noPref.classList.add('active');
            updateTherapistSummary([]);
        };
        therapistBox.appendChild(noPref);

        data.forEach(t => {

            let therapistName =
                t.name ?? t;

            let therapistId =
                t.id ?? '';

            let isAvailable =
                t.available === undefined
                ? true
                : Boolean(t.available);


            const card =
                document.createElement(
                    'div'
                );


            card.className =
                'select-card therapist';


            card.dataset.id =
                therapistId;

            card.dataset.name =
                therapistName;


            card.innerHTML = `

                <strong>
                    ${therapistName}
                </strong>

                <div class="card-desc ${
                    isAvailable
                    ? 'available-text'
                    : 'unavailable-text'
                }">

                    ${
                        isAvailable
                        ? 'Available'
                        : 'Booked / Unavailable'
                    }

                </div>

            `;


            if(!isAvailable){

                card.classList.add(
                    'unavailable'
                );

                card.style.pointerEvents =
                    'none';

            }else{

                card.onclick = function(){
                const id = String(therapistId);
                const maxPick = Math.max(1, Math.min(4, Number(document.getElementById('pax')?.value || 1)));
                const index = selectedTherapistIds.indexOf(id);
                if (index >= 0) {
                    selectedTherapistIds.splice(index, 1);
                    card.classList.remove('active');
                } else {
                    if (selectedTherapistIds.length >= maxPick) {
                        alert('You can select up to ' + maxPick + ' therapist(s) for ' + maxPick + ' guest(s).');
                        return;
                    }
                    selectedTherapistIds.push(id);
                    card.classList.add('active');
                }
                // Choosing a named therapist clears No Preference.
                const noPref = therapistBox.querySelector('[data-id="0"]');
                if (noPref) noPref.classList.remove('active');
                syncSelectedTherapists();
                const names = selectedTherapistIds.map(selectedId => {
                    const selectedCard = therapistBox.querySelector('.therapist[data-id="' + selectedId + '"]');
                    return selectedCard?.dataset.name || selectedId;
                });
                updateTherapistSummary(names);
            };

            }


            therapistBox.appendChild(
                card
            );

        });

    })

    .catch(error => {

        console.error(
            'Therapist error:',
            error
        );


        therapistBox.innerHTML = '';


        therapistMessage.textContent =
            'Unable to load therapists.';

    });

}


function getOpeningHour(dateString) {
    const d = new Date(dateString + 'T12:00:00');
    return (d.getDay() === 0 || d.getDay() === 6) ? 13 : 15;
}
function selectedTimeDate(dateString, timeString) {
    const [h, m] = timeString.split(':').map(Number);
    const d = new Date(dateString + 'T00:00:00');
    d.setHours(h, m, 0, 0);
    if (h < getOpeningHour(dateString)) d.setDate(d.getDate() + 1);
    return d;
}
function slotHasLeadTime(dateString, timeString) {
    return selectedTimeDate(dateString, timeString).getTime() >= Date.now() + 2 * 60 * 60 * 1000;
}
function durationHours(value) {
    const text = String(value || '1 Hour').toLowerCase();
    const m = text.match(/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/);
    if (m) return parseFloat(m[1]);
    const mins = text.match(/(\d+)\s*(?:minutes?|mins?)/);
    if (mins) return parseInt(mins[1], 10) / 60;
    return 1;
}
function slotWithinHours(dateString, timeString, duration) {
    const [h, m] = timeString.split(':').map(Number);
    let adjustedHour = h;
    if (h < getOpeningHour(dateString)) adjustedHour += 24;
    return adjustedHour * 60 + m + durationHours(duration) * 60 <= 27 * 60;
}

/* =========================
   DATE CHANGE
========================= */

bookingDate.addEventListener(
    'change',
    async function(){

        timeBox.innerHTML = '';

        bookingTime.value = '';

        resetTherapistDisplay();


        const selectedDate =
            bookingDate.value;


        if(!selectedDate){
            return;
        }


        /*
           DISPLAY DATE IN SUMMARY
        */

        const dateObject =
            new Date(
                selectedDate +
                'T00:00:00'
            );


        summary.date =
            dateObject.toLocaleDateString(
                'en-US',
                {
                    month:'short',
                    day:'numeric',
                    year:'numeric'
                }
            );


        summary.time = '—';

        renderSummary();


        timeBox.innerHTML =
            '<div class="message" style="grid-column:1/-1;">Checking available time slots...</div>';


        let cards = [];


      /*
         Spa operating hours:
         Monday-Friday: 3 PM to 3 AM
         Saturday-Sunday: 1 PM to 3 AM

         Last booking start time: 2 AM
      */

        // Get the selected date's weekday without timezone shifting.
        // Sunday = 0, Monday-Friday = 1-5, Saturday = 6.
        const selectedDay = new Date(selectedDate + 'T12:00:00').getDay();

        // Weekend opens at 1:00 PM; weekdays open at 3:00 PM.
        const openingHour = (selectedDay === 0 || selectedDay === 6) ? 13 : 15;

        // Generate hourly booking start times through 2:00 AM.
        // 3:00 AM is the closing time, so it is not a booking start time.
        const hours = [];

        for (let hour = openingHour; hour <= 23; hour++) {
            hours.push(hour);
        }

        for (let hour = 0; hour <= 2; hour++) {
            hours.push(hour);
        }

       for (const hour of hours) {

        
            const timeValue =
                String(hour)
                .padStart(2,'0') +
                ':00';


            try{

                const response =
                    await fetch(
                        '../check_slot.php?date=' +
                        encodeURIComponent(
                            selectedDate
                        ) +
                        '&time=' +
                        encodeURIComponent(
                            timeValue
                        ) +
                        '&room_type=' +
                        encodeURIComponent(
                            roomInput.value || 'Single Room'
                        ) +
                        '&duration=' +
                        encodeURIComponent(
                            durationInput.value || '1 Hour'
                        ) +
                        '&pax=' +
                        encodeURIComponent(
                            paxInput.value || '1'
                        )
                    );


                const data =
                    await response.json();


                const div =
                    document.createElement(
                        'div'
                    );


                div.className =
                    'time-card';


                const displayHour =
                    hour % 12 || 12;


                const meridiem =
                    hour >= 12
                    ? 'PM'
                    : 'AM';


                const isCoupleRoom = roomInput.value === 'Couple Room';
                const isAvailable = Boolean(data.available);
                const roomStatus = isAvailable ? 'Available' : 'Occupied';

                div.innerHTML = isCoupleRoom
                    ? `${displayHour}:00 ${meridiem}<span class="slot-count ${isAvailable ? 'available-text' : 'unavailable-text'}">${roomStatus}</span>`
                    : `${displayHour}:00 ${meridiem}<span class="slot-count">${data.remaining ?? 0} slot${Number(data.remaining) === 1 ? '' : 's'}</span>`;


                const leadTimeBlocked = !slotHasLeadTime(selectedDate, timeValue);
                const durationBlocked = !slotWithinHours(selectedDate, timeValue, durationInput.value || '1 Hour');
                if(!data.available || leadTimeBlocked || durationBlocked){
                    div.classList.add('dim');
                    div.setAttribute('aria-disabled', 'true');
                    div.style.pointerEvents = 'none';
                    div.style.opacity = '0.45';
                    div.style.cursor = 'not-allowed';
                    div.title = leadTimeBlocked ? 'Must be booked at least 2 hours in advance' : (durationBlocked ? 'Appointment must finish by 3:00 AM' : 'This time slot is unavailable');
                }else{

                    div.onclick =
                        function(){

                            document
                            .querySelectorAll(
                                '.time-card'
                            )
                            .forEach(card => {

                                card.classList.remove(
                                    'active'
                                );

                            });


                            div.classList.add(
                                'active'
                            );


                            bookingTime.value =
                                timeValue;


                            summary.time =
                                `${displayHour}:00 ${meridiem}`;

                            summary.therapist =
                                '—';


                            renderSummary();


                            loadTherapists(
                                selectedDate,
                                timeValue
                            );

                        };

                }


                cards.push(div);


            }catch(error){

                console.error(
                    'Slot error:',
                    error
                );

            }

        }


        timeBox.innerHTML = '';


        if(cards.length === 0){

            timeBox.innerHTML =
                '<div class="message" style="grid-column:1/-1;">Unable to load available time slots.</div>';

            return;

        }


        cards.forEach(card => {

            timeBox.appendChild(
                card
            );

        });

    }
);


/* =========================
   VALIDATION
========================= */

paxInput?.addEventListener('input', renderGuestNameFields);
roomInput?.addEventListener('change', renderGuestNameFields);
document.querySelectorAll('.room').forEach(card => card.addEventListener('click', () => setTimeout(renderGuestNameFields, 0)));
renderGuestNameFields();

document
.getElementById(
    'bookingForm'
)
.addEventListener(
    'submit',
    function(e){

        if(
            !serviceIdInput.value
        ){

            alert(
                'Please select a service first.'
            );

            e.preventDefault();

            return;

        }


        if(
            !durationInput.value
        ){

            alert(
                'Please select a duration first.'
            );

            e.preventDefault();

            return;

        }


        if(
            !roomInput.value
        ){

            alert(
                'Please select a room first.'
            );

            e.preventDefault();

            return;

        }


        if(
            !bookingDate.value ||
            !bookingTime.value
        ){

            alert(
                'Please select your appointment date and time.'
            );

            e.preventDefault();

            return;

        }


        if (!slotHasLeadTime(bookingDate.value, bookingTime.value)) {
            alert('Please book at least 2 hours in advance.');
            e.preventDefault();
            return;
        }
        if (!slotWithinHours(bookingDate.value, bookingTime.value, durationInput.value)) {
            alert('Appointment must finish by 3:00 AM.');
            e.preventDefault();
            return;
        }

        if(
            !priceInput.value ||
            Number(priceInput.value) <= 0
        ){

            alert(
                'Unable to calculate booking price. Please select your service duration again.'
            );

            e.preventDefault();

            return;

        }

    }
);


/* INITIAL SUMMARY */

renderSummary();

</script>

</body>

</html>