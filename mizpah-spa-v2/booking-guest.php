<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/includes/db.php';
$booking_error = '';

function bookingDurationMinutes($duration) {
    $duration = strtolower(trim((string)$duration));
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/i', $duration, $m)) return (int)round((float)$m[1] * 60);
    if (preg_match('/(\d+)\s*(?:minutes?|mins?)/i', $duration, $m)) return (int)$m[1];
    if (is_numeric($duration)) { $n=(float)$duration; return $n > 3 ? (int)$n : (int)round($n*60); }
    return 60;
}
function bookingOperatingMinutes($time, $openingHour) {
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim((string)$time), $m)) return null;
    $h=(int)$m[1]; $min=(int)$m[2];
    if ($h<0 || $h>23 || $min<0 || $min>59) return null;
    if ($h<$openingHour) $h+=24;
    return $h*60+$min;
}
function bookingOpeningHour($date) { $n=(int)date('N', strtotime($date)); return $n>=6 ? 13 : 15; }

if (isset($_POST['submit_booking'])) {
    $name=trim($_POST['customer_name'] ?? '');
    $phone=trim($_POST['phone'] ?? '');
    $service_id=(int)($_POST['service_id'] ?? 0);
    $service=trim($_POST['service'] ?? '');
    $duration=trim($_POST['duration'] ?? '');
    $price=(float)($_POST['price'] ?? 0);
    $date=trim($_POST['booking_date'] ?? '');
    $time=trim($_POST['booking_time'] ?? '');
    $pax=(int)($_POST['pax'] ?? 1);
    $payment=trim($_POST['payment_method'] ?? 'Cash');
    $notes=trim($_POST['notes'] ?? '');
    $addons=trim($_POST['addons'] ?? '');
    $room_type=trim($_POST['room_type'] ?? '');
    $additional_guest_names=array_values(array_filter(array_map('trim', (array)($_POST['guest_names'] ?? [])), fn($v)=>$v!==''));
    // The primary Full Name is Guest 1; only additional guests are entered below.
    $guest_names=array_merge([$name], $additional_guest_names);
    $selectedTherapists=array_values(array_unique(array_filter(array_map('intval', $_POST['therapists'] ?? []), fn($id)=>$id>0)));
    $therapist_id=0;
    if ($room_type==='Couple Room') $pax=2;

    if (!$name || !$phone || count($guest_names)!==$pax || $service_id<=0 || !$service || !$duration || $price<=0 || !$date || !$time || !$room_type) {
        $booking_error='Please complete all required booking information.';
    } elseif (!in_array($room_type,['Single Room','Couple Room'],true) || $pax<1 || $pax>4 || ($room_type==='Couple Room' && $pax!==2)) {
        $booking_error='Please select a valid room and guest count (Single Room: 1–4; Couple Room: exactly 2).';
    } elseif (!in_array($payment,['Cash','GCash'],true)) {
        $booking_error='Please select a valid payment method.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !preg_match('/^\d{2}:\d{2}$/',$time) || !strtotime($date)) {
        $booking_error='Please select a valid appointment date and time.';
    } else {
        $opening=bookingOpeningHour($date); $start=bookingOperatingMinutes($time,$opening); $minutes=bookingDurationMinutes($duration);
        $now=new DateTime('now',new DateTimeZone('Asia/Manila'));
        $selected=DateTime::createFromFormat('!Y-m-d H:i',$date.' '.$time,new DateTimeZone('Asia/Manila'));
        if ($selected && (int)substr($time,0,2)<$opening) $selected->modify('+1 day');
        $closing=27*60;
        if ($start===null || $minutes<=0 || $start+$minutes>$closing) {
            $booking_error='The selected appointment must fit within the spa operating hours and finish by 3:00 AM.';
        } elseif (!$selected || $selected < (clone $now)->modify('+2 hours')) {
            $booking_error='Please book at least 2 hours in advance.';
        } else {
            // Check capacity only within the selected room type.
            // Single Room: up to 4 guests. Couple Room: one couple (2 guests).
            $roomCapacity = ($room_type === 'Couple Room') ? 2 : 4;
            $q=mysqli_prepare($conn,"SELECT booking_time,duration,COALESCE(NULLIF(pax,0),1) AS pax FROM bookings WHERE booking_date=? AND room_type=? AND status NOT IN ('Cancelled','Completed')");
            mysqli_stmt_bind_param($q,'ss',$date,$room_type); mysqli_stmt_execute($q); $res=mysqli_stmt_get_result($q); $occupied=0;
            while($b=mysqli_fetch_assoc($res)) { $bs=bookingOperatingMinutes($b['booking_time'],$opening); $bd=bookingDurationMinutes($b['duration'] ?? '1 Hour'); if($bs!==null && $start < $bs+$bd && $bs < $start+$minutes) $occupied += max(1,(int)$b['pax']); }
            mysqli_stmt_close($q);
            if($occupied + $pax > $roomCapacity) $booking_error=($room_type === 'Couple Room') ? 'The Couple Room is already booked for this time. Please choose another time.' : 'Sorry, there are not enough Single Room slots available at this time. Please choose another time or reduce the guest count.';
            else {
                $therapistMode=trim($_POST['therapist_mode'] ?? '0');
                if($therapistMode==='choose') {
                    if(count($selectedTherapists)!==$pax) $booking_error='Please select exactly '.$pax.' available therapist(s), or choose No Preference.';
                    else {
                        $placeholders=implode(',',array_fill(0,count($selectedTherapists),'?'));
                        $types=str_repeat('i',count($selectedTherapists));
                        $sql="SELECT id,name FROM therapists WHERE status='Active' AND id IN ($placeholders)"; $st=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($st,$types,...$selectedTherapists); mysqli_stmt_execute($st); $rr=mysqli_stmt_get_result($st); $valid=[]; while($tr=mysqli_fetch_assoc($rr)) $valid[]=(int)$tr['id']; mysqli_stmt_close($st);
                        if(count($valid)!==$pax) $booking_error='One or more selected therapists are invalid. Please reload therapist availability.';
                        else {
                            // Ensure each chosen therapist has no overlapping active booking.
                            $st=mysqli_prepare($conn,"SELECT b.booking_time,b.duration,b.therapist_id,bt.therapist_id AS assigned_id FROM bookings b LEFT JOIN booking_therapists bt ON bt.booking_id=b.id WHERE b.booking_date=? AND b.status NOT IN ('Cancelled','Completed')");
                            mysqli_stmt_bind_param($st,'s',$date); mysqli_stmt_execute($st); $rr=mysqli_stmt_get_result($st); $busy=[];
                            while($b=mysqli_fetch_assoc($rr)) { $bs=bookingOperatingMinutes($b['booking_time'],$opening); $bd=bookingDurationMinutes($b['duration'] ?? '1 Hour'); if($bs!==null && $start < $bs+$bd && $bs < $start+$minutes) { if(!empty($b['therapist_id'])) $busy[]=(int)$b['therapist_id']; if(!empty($b['assigned_id'])) $busy[]=(int)$b['assigned_id']; } }
                            mysqli_stmt_close($st);
                            if(array_intersect($valid,$busy)) $booking_error='One or more selected therapists are no longer available. Please select again.';
                            else $therapist_id=$valid[0];
                        }
                    }
                } else { $selectedTherapists=[]; $therapist_id=0; }
                if(!$booking_error) {
                    $price = $price * $pax; // service and add-ons are priced per guest, including Couple Room
                    mysqli_begin_transaction($conn);
                    $st=mysqli_prepare($conn,"INSERT INTO bookings (service,duration,price,customer_name,phone,booking_date,booking_time,pax,payment_method,notes,addons,therapist_id,room_type,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, 'Pending','guest')");
                    mysqli_stmt_bind_param($st,'ssdssssisssis',$service,$duration,$price,$name,$phone,$date,$time,$pax,$payment,$notes,$addons,$therapist_id,$room_type);
                    if(mysqli_stmt_execute($st)) {
                        $booking_id=mysqli_insert_id($conn); mysqli_stmt_close($st); $ok=true;
                        foreach($selectedTherapists as $tid) { $bt=mysqli_prepare($conn,"INSERT INTO booking_therapists (booking_id,therapist_id,assigned_by) VALUES (?,?, 'guest')"); mysqli_stmt_bind_param($bt,'ii',$booking_id,$tid); if(!mysqli_stmt_execute($bt)) $ok=false; mysqli_stmt_close($bt); }
                        // Match the current booking_guests table structure.
                        // This table currently has no duration, price, addons, or therapist_id columns.
                        $guestStmt=mysqli_prepare($conn,"INSERT INTO booking_guests (booking_id,guest_number,guest_name,service_id,service,phone) VALUES (?,?,?,?,?,?)");
                        if(!$guestStmt) $ok=false;
                        if($guestStmt) {
                            foreach($guest_names as $idx=>$guestName) {
                                $guestNumber=$idx+1;
                                mysqli_stmt_bind_param($guestStmt,'iisiss',$booking_id,$guestNumber,$guestName,$service_id,$service,$phone);
                                if(!mysqli_stmt_execute($guestStmt)) $ok=false;
                            }
                            mysqli_stmt_close($guestStmt);
                        }
                        if($ok) { mysqli_commit($conn); header('Location: thankyou.php?id='.$booking_id); exit; }
                        mysqli_rollback($conn); $booking_error='Unable to save therapist selections. Please try again.';
                    } else { $booking_error='Booking Error: '.mysqli_error($conn); mysqli_stmt_close($st); mysqli_rollback($conn); }
                }
            }
        }
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

<title>Book an Appointment | Mizpah Wellness Spa</title>

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

    min-height:100vh;

    background:
        linear-gradient(
            rgba(0,0,0,.84),
            rgba(0,0,0,.94)
        ),
        url('assets/images/hero.jpg')
        center/cover fixed no-repeat;

    color:#fff;

}


/* =========================================================
   HEADER
   ========================================================= */

.site-header{

    min-height:70px;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:12px 7%;

    background:rgba(10,10,10,.96);

    border-bottom:
        1px solid
        rgba(214,194,156,.16);

    position:sticky;
    top:0;

    z-index:1000;

}

.brand{

    display:flex;
    align-items:center;

    gap:11px;

    text-decoration:none;

}

.brand img{

    width:42px;
    height:42px;

    object-fit:contain;

}

.brand span{

    font-family:
        'Playfair Display',
        serif;

    color:#D6C29C;

    font-size:18px;

    font-weight:600;

}

.header-links{

    display:flex;
    align-items:center;

    gap:8px;

}

.header-links a{

    color:#ddd;

    text-decoration:none;

    font-size:12px;

    padding:8px 11px;

    border-radius:7px;

    transition:.2s;

}

.header-links a:hover{

    color:#D6C29C;

}

.login-btn{

    border:
        1px solid
        rgba(214,194,156,.4);

    color:#D6C29C !important;

}


/* =========================================================
   PAGE
   ========================================================= */

.page{

    width:100%;

    max-width:1250px;

    margin:auto;

    padding:
        38px 5%
        70px;

}


/* =========================================================
   PAGE HEADING
   ========================================================= */

.page-heading{

    margin-bottom:28px;

}

.eyebrow{

    color:#8c7d63;

    text-transform:uppercase;

    letter-spacing:2px;

    font-size:9px;

    margin-bottom:5px;

}

.page-heading h1{

    font-family:
        'Playfair Display',
        serif;

    color:#D6C29C;

    font-size:34px;

    margin-bottom:7px;

}

.page-heading p{

    color:#888;

    font-size:11px;

    line-height:1.8;

    max-width:680px;

}


/* =========================================================
   ERROR
   ========================================================= */

.error-box{

    background:
        rgba(150,60,60,.12);

    border:
        1px solid
        rgba(200,100,100,.25);

    color:#dca2a2;

    padding:12px 15px;

    border-radius:10px;

    margin-bottom:18px;

    font-size:11px;

}


/* =========================================================
   MAIN LAYOUT
   ========================================================= */

.booking-layout{

    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        330px;

    gap:22px;

    align-items:start;

}


/* =========================================================
   SECTIONS
   ========================================================= */

.section{

    background:
        rgba(18,18,18,.93);

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

    margin-bottom:19px;

}

.step{

    width:29px;
    height:29px;

    min-width:29px;

    border-radius:50%;

    display:flex;

    justify-content:center;
    align-items:center;

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

    line-height:1.6;

}

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


/* =========================================================
   SELECTION CARDS
   ========================================================= */

.selection-grid{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(130px,1fr)
        );

    gap:9px;

}

.select-card{

    min-height:49px;

    position:relative;

    display:flex;

    flex-direction:column;

    justify-content:center;
    align-items:center;

    text-align:center;

    padding:12px 10px;

    background:#101010;

    border:
        1px solid
        #292929;

    border-radius:10px;

    color:#ccc;

    cursor:pointer;

    font-size:11px;

    transition:.2s;

}

.select-card:hover{

    transform:
        translateY(-1px);

    border-color:
        rgba(214,194,156,.55);

    color:#fff;

}

.select-card.active{

    border-color:#D6C29C;

    background:
        rgba(214,194,156,.08);

    color:#D6C29C;

    box-shadow:
        0 0 0 1px
        rgba(214,194,156,.04);

}

.select-card.unavailable{

    opacity:.30;

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

    line-height:1.4;

    margin-top:4px;

}

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


/* =========================================================
   FORM
   ========================================================= */

.form-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:14px;

}

.form-group{

    min-width:0;

}

.form-group.full{

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


/* =========================================================
   SCHEDULE
   ========================================================= */

.schedule-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:16px;

    margin-top:18px;

}

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


/* =========================================================
   THERAPIST
   ========================================================= */

.therapist-choice{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:9px;

    margin-bottom:13px;

}

.therapist-list{

    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:9px;

    margin-top:10px;

}

.therapist-card{

    min-height:65px;

}

.available-text{

    color:#8fa98f;

}

.unavailable-text{

    color:#b78383;

}

.message{

    color:#777;

    font-size:10px;

    margin-top:10px;

    line-height:1.6;

}


/* =========================================================
   SUMMARY
   ========================================================= */

.summary-panel{

    position:sticky;

    top:88px;

    background:
        rgba(18,18,18,.97);

    border:
        1px solid
        rgba(214,194,156,.17);

    border-radius:16px;

    padding:22px;

    box-shadow:
        0 16px 40px
        rgba(0,0,0,.30);

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

    margin-bottom:18px;

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

    max-width:180px;

}

.summary-addons{

    line-height:1.5;

}

.price-area{

    padding:
        15px 0
        4px;

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

.book-btn{

    width:100%;

    margin-top:17px;

    padding:13px;

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

    color:#555;

    font-size:8px;

    line-height:1.5;

    text-align:center;

    margin-top:10px;

}


/* =========================================================
   RESPONSIVE + STICKY BOOKING SUMMARY
   ========================================================= */

/* =========================================================
   STICKY BOOKING SUMMARY
   Apply sticky positioning to the GRID ITEM wrapper, not the
   inner .summary-panel. This lets the whole summary follow the
   long booking form while the PAGE remains the only scroll area.
   ========================================================= */
.booking-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 22px;
    align-items: start;
}

/* The summary is wrapped in the second direct child of the grid. */
.booking-layout > div:last-child {
    position: -webkit-sticky;
    position: sticky;
    top: 88px;
    align-self: start;
    height: auto;
    max-height: none;
    overflow: visible;
}

/* Keep the panel itself in normal flow inside its sticky wrapper. */
.summary-panel {
    position: static;
    top: auto;
    height: auto;
    max-height: none;
    overflow: visible;
}

/* Tablet/mobile: stack the summary under the form. */
@media (max-width: 900px) {
    .booking-layout {
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
    }

    .booking-layout > div:last-child {
        position: static;
        top: auto;
        max-height: none;
        overflow: visible;
    }

    .summary-panel {
        position: static;
        top: auto;
        max-height: none;
        overflow: visible;
    }
}

@media (max-width: 750px) {
    .site-header {
        padding: 12px 4%;
    }

    .brand span {
        display: none;
    }

    .header-links {
        gap: 2px;
    }

    .header-links a {
        padding: 7px;
        font-size: 10px;
    }

    .page {
        padding: 28px 4% 50px;
    }

    .schedule-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .time-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .therapist-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 550px) {
    .page-heading h1 {
        font-size: 27px;
    }

    .section {
        padding: 17px;
    }

    .form-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .form-group.full {
        grid-column: auto;
    }

    .selection-grid,
    .therapist-choice {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .time-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .summary-panel {
        padding: 17px;
    }

    .summary-value {
        overflow-wrap: anywhere;
    }
}


/* Compact calendar picker icon; keep the date field dimensions unchanged. */
input[type="date"] {
    color-scheme: dark;
    cursor: pointer;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    width: 13px !important;
    height: 13px !important;
    max-width: 13px;
    max-height: 13px;
    padding: 0 !important;
    margin: 0 2px 0 5px !important;
    opacity: 1;
    cursor: pointer;
    filter: brightness(0) invert(1);
    background-size: 13px 13px;
}
</style>

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="site-header">

    <a
        href="index.php"
        class="brand"
    >

        <img
            src="assets/images/logo.png"
            alt="Mizpah Logo"
        >

        <span>
            Mizpah Wellness Spa
        </span>

    </a>


    <div class="header-links">

        <a href="index.php">
            Home
        </a>

        <a href="services.php">
            Services
        </a>

        <a href="therapist.php">
            Therapists
        </a>

        <a
            href="login.php"
            class="login-btn"
        >
            Login
        </a>

    </div>

</header>


<!-- =========================================================
     PAGE
     ========================================================= -->

<div class="page">


    <div class="page-heading">

        <div class="eyebrow">
            Guest Booking
        </div>

        <h1>
            Book an Appointment
        </h1>

        <p>
            No account is required. Choose your preferred
            service, schedule and therapist, then provide
            your contact details to complete your booking.
        </p>

    </div>


    <?php if (!empty($booking_error)): ?>

        <div class="error-box">
            <?= htmlspecialchars($booking_error) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        id="bookingForm"
    >


        <div class="booking-layout">


            <!-- =================================================
                 LEFT COLUMN
                 ================================================= -->

            <div>


                <!-- =============================================
                     STEP 1
                     ============================================= -->

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

                        <span
                            style="
                                color:#555;
                                text-transform:none;
                            "
                        >
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
                            Add-ons will appear after
                            selecting a service.
                        </div>

                    </div>


                    <input
                        type="hidden"
                        name="addons"
                        id="addons"
                    >

                </div>


                <!-- =============================================
                     STEP 2
                     ============================================= -->

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


                    <div class="schedule-grid">


                        <div>

                            <label for="booking_date">

                                Appointment Date

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <input
                                type="date"
                                id="booking_date"
                                name="booking_date"
                                required
                            >

                        </div>


                        <div>

                            <label for="pax">

                                Number of Guests

                                <span class="required">
                                    *
                                </span>

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
                            Select an appointment date first.
                        </div>

                    </div>

                    <p class="message">
                        Note: Please book at least 2 hours before your desired service time.
                    </p>

                    <input
                        type="hidden"
                        name="booking_time"
                        id="booking_time"
                    >

                </div>


                <!-- =============================================
                     STEP 3
                     ============================================= -->

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
                                You may choose a specific
                                available therapist or leave
                                it as No Preference.
                            </div>

                        </div>

                    </div>


                    <div class="therapist-choice">

                        <div
                            class="select-card therapist-option active"
                            data-therapist-option="0"
                        >

                            <strong>
                                No Preference
                            </strong>

                            <span class="card-desc">
                                Any available therapist
                            </span>

                        </div>


                        <div
                            class="select-card therapist-option"
                            data-therapist-option="choose"
                        >

                            <strong>
                                Choose Therapist
                            </strong>

                            <span class="card-desc">
                                Select a specific therapist
                            </span>

                        </div>

                    </div>


                    <div
                        id="therapistList"
                        class="therapist-list"
                    ></div>


                    <div
                        id="therapistMessage"
                        class="message"
                    >
                        Select a date and time first
                        to view therapist availability.
                    </div>


                    <input type="hidden" name="therapist_mode" id="therapist_mode" value="0">
                    <input type="hidden" name="therapist" id="therapist" value="0">
                    <div id="therapistInputs"></div>

                </div>


                <!-- =============================================
                     STEP 4
                     ============================================= -->

                <div class="section">

                    <div class="section-head">

                        <div class="step">
                            4
                        </div>

                        <div>

                            <div class="section-title">
                                Guest Details
                            </div>

                            <div class="section-sub">
                                Enter your contact information
                                so the spa can identify your
                                appointment.
                            </div>

                        </div>

                    </div>


                    <div class="form-grid">


                        <div class="form-group">

                            <label>

                                Full Name

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <input
                                type="text"
                                name="customer_name"
                                placeholder="Enter your full name"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>

                                Phone Number

                                <span class="required">
                                    *
                                </span>

                            </label>

                            <input
                                type="text"
                                name="phone"
                                placeholder="09XXXXXXXXX"
                                required
                            >

                        </div>


                        <div class="form-group full" id="guestNamesGroup">
                            <label>Other Guests' Full Names <span style="color:#555; text-transform:none;">(Guest 2 onward)</span></label>
                            <div id="guestNamesFields"></div>
                            <p class="message">Your Full Name is Guest 1. Enter only the other guests below.</p>
                        </div>

                        <div class="form-group full">

                            <label>

                                Payment Method

                                <span class="required">
                                    *
                                </span>

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


            <!-- =================================================
                 SUMMARY
                 ================================================= -->

            <div>

                <div class="summary-panel">

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
                            Guests
                        </span>

                        <span
                            class="summary-value"
                            id="sumPax"
                        >
                            1
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
                            No Preference
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
                        type="submit"
                        name="submit_booking"
                        class="book-btn"
                    >
                        Confirm Booking
                    </button>


                    <div class="booking-note">
                        No account is required. Your booking
                        will initially be submitted as Pending.
                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             HIDDEN VALUES
             ================================================= -->

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

/* =========================================================
   DOM
   ========================================================= */

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

const therapistList =
    document.getElementById('therapistList');

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
const therapistModeInput = document.getElementById('therapist_mode');
const therapistInputs = document.getElementById('therapistInputs');
let selectedTherapistIds = [];
let selectedTherapistNames = [];

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



/* =========================================================
   PRICE
   ========================================================= */

let basePrice = 0;

let addonTotal = 0;


/* =========================================================
   SUMMARY DATA
   ========================================================= */

let summary = {

    service: '—',

    duration: '—',

    room: '—',

    pax: '1',

    date: '—',

    time: '—',

    therapist: 'No Preference',

    addons: 'None'

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
        'sumPax'
    ).textContent =
        summary.pax;

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
       IMPORTANT:
       Save complete total:
       service/duration + add-ons
    */

    priceInput.value =
        total.toFixed(2);

}


/* =========================================================
   RESET SERVICE
   ========================================================= */

function resetServiceSelection(){

    serviceIdInput.value = '';

    serviceInput.value = '';

    durationInput.value = '';

    addonsInput.value = '';

    basePrice = 0;

    addonTotal = 0;


    summary.service = '—';

    summary.duration = '—';

    summary.addons = 'None';


    serviceDesc.textContent =
        'Select a service to view its description.';


    durationBox.innerHTML = `
        <div
            class="message"
            style="grid-column:1/-1;"
        >
            Select a service first.
        </div>
    `;


    addonBox.innerHTML = `
        <div
            class="message"
            style="grid-column:1/-1;"
        >
            Add-ons will appear after
            selecting a service.
        </div>
    `;


    renderSummary();

}


/* =========================================================
   CATEGORY
   ========================================================= */

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
        .forEach(function(card){

            card.classList.remove(
                'active'
            );

        });


        category.classList.add(
            'active'
        );


        resetServiceSelection();


        serviceBox.innerHTML = `
            <div
                class="message"
                style="grid-column:1/-1;"
            >
                Loading services...
            </div>
        `;


        fetch(
            'get_services_by_category.php?cat=' +
            encodeURIComponent(
                category.dataset.cat
            )
        )

        .then(function(response){
            return response.json();
        })

        .then(function(data){

            serviceBox.innerHTML = '';


            if(
                !Array.isArray(data) ||
                data.length === 0
            ){

                serviceBox.innerHTML = `
                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        No services available.
                    </div>
                `;

                return;

            }


            data.forEach(function(service){

                const card =
                    document.createElement(
                        'div'
                    );


                card.className =
                    'select-card service';


                card.dataset.id =
                    service.id;


                card.dataset.name =
                    service.service_name;


                card.dataset.desc =
                    service.description || '';


                card.textContent =
                    service.service_name;


                serviceBox.appendChild(
                    card
                );

            });

        })

        .catch(function(error){

            console.error(
                'Service error:',
                error
            );


            serviceBox.innerHTML = `
                <div
                    class="message"
                    style="grid-column:1/-1;"
                >
                    Unable to load services.
                </div>
            `;

        });

    }
);


/* =========================================================
   SERVICE
   ========================================================= */

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
        .forEach(function(card){

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


        /* =============================================
           LOAD DURATION
           ============================================= */

        durationBox.innerHTML = `
            <div
                class="message"
                style="grid-column:1/-1;"
            >
                Loading durations...
            </div>
        `;


        fetch(
            'get_duration.php?id=' +
            encodeURIComponent(
                selectedService.dataset.id
            )
        )

        .then(function(response){
            return response.json();
        })

        .then(function(data){

            durationBox.innerHTML = '';


            if(
                !Array.isArray(data) ||
                data.length === 0
            ){

                durationBox.innerHTML = `
                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        No duration available.
                    </div>
                `;

                return;

            }


            data.forEach(function(item){

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

        .catch(function(error){

            console.error(
                'Duration error:',
                error
            );

        });


        /* =============================================
           LOAD ADDONS
           ============================================= */

        addonBox.innerHTML = `
            <div
                class="message"
                style="grid-column:1/-1;"
            >
                Loading add-ons...
            </div>
        `;


        fetch('get_addons.php')

        .then(function(response){
            return response.json();
        })

        .then(function(data){

            addonBox.innerHTML = '';


            if(
                !Array.isArray(data) ||
                data.length === 0
            ){

                addonBox.innerHTML = `
                    <div
                        class="message"
                        style="grid-column:1/-1;"
                    >
                        No add-ons available.
                    </div>
                `;

                return;

            }


            data.forEach(function(addon){

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
                        `
                            <span class="card-desc">
                                ${addon.description}
                            </span>
                        `
                        :
                        ''
                    }
                `;


                addonBox.appendChild(
                    card
                );

            });

        })

        .catch(function(error){

            console.error(
                'Add-ons error:',
                error
            );

        });

    }
);


/* =========================================================
   DURATION
   ========================================================= */

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
        .forEach(function(card){

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
           Duration can affect therapist availability.
        */

        if(
            bookingDate.value &&
            bookingTime.value
        ){

            const chooseOption =
                document.querySelector(
                    '.therapist-option[data-therapist-option="choose"]'
                );


            if(
                chooseOption &&
                chooseOption.classList.contains(
                    'active'
                )
            ){

                loadTherapists(
                    bookingDate.value,
                    bookingTime.value
                );

            }

        }

    }
);


/* =========================================================
   ADD-ONS
   ========================================================= */

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


        const names = [];

        addonTotal = 0;


        document
        .querySelectorAll(
            '.addon.active'
        )
        .forEach(function(card){

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


/* =========================================================
   ROOM
   ========================================================= */

document.addEventListener(
    'click',
    function(e){

        const selectedRoom =
            e.target.closest('.room');

        if(!selectedRoom){
            return;
        }


        document
        .querySelectorAll('.room')
        .forEach(function(card){

            card.classList.remove(
                'active'
            );

        });


        selectedRoom.classList.add(
            'active'
        );


        roomInput.value =
            selectedRoom.dataset.room;


        summary.room =
            selectedRoom.dataset.room;
        selectedTherapistIds = []; selectedTherapistNames = []; therapistInputs.innerHTML = ''; therapistInput.value='0'; therapistModeInput.value='0';


        if(
            selectedRoom.dataset.room ===
            'Couple Room'
        ){

            paxInput.value = 2;

            paxInput.readOnly = true;

            summary.pax = '2';

        }else{

            paxInput.value = 1;

            paxInput.readOnly = false;

            summary.pax = '1';

        }


        renderSummary();

        // Room capacity and slot counts depend on the selected room and pax.
        // Reload the available times immediately when the room changes.
        if (bookingDate.value) {
            bookingDate.dispatchEvent(new Event('change'));
        }

    }
);


/* =========================================================
   PAX
   ========================================================= */

paxInput.addEventListener(
    'input',
    function(){

        let value =
            parseInt(
                paxInput.value
            ) || 1;


        if(value < 1){
            value = 1;
        }


        if(value > 4){
            value = 4;
        }


        paxInput.value = value;

        summary.pax =
            String(value);
        selectedTherapistIds = []; selectedTherapistNames = []; therapistInputs.innerHTML=''; therapistInput.value='0';
        document.querySelectorAll('.therapist-card.active').forEach(function(c){c.classList.remove('active');});
        if (therapistModeInput.value === 'choose' && bookingDate.value && bookingTime.value) loadTherapists(bookingDate.value, bookingTime.value);


        renderSummary();

    }
);


/* =========================================================
   MIN DATE
   ========================================================= */

const today =
    new Date();


const year =
    today.getFullYear();


const month =
    String(
        today.getMonth() + 1
    ).padStart(
        2,
        '0'
    );


const day =
    String(
        today.getDate()
    ).padStart(
        2,
        '0'
    );


bookingDate.min =
    `${year}-${month}-${day}`;


/* =========================================================
   THERAPIST RESET
   ========================================================= */

function resetTherapist(){

    document
    .querySelectorAll(
        '.therapist-option'
    )
    .forEach(function(card){

        card.classList.remove(
            'active'
        );

    });


    const noPreference =
        document.querySelector(
            '.therapist-option[data-therapist-option="0"]'
        );


    if(noPreference){

        noPreference.classList.add(
            'active'
        );

    }


    therapistInput.value = '0';
    therapistModeInput.value = '0';
    selectedTherapistIds = [];
    selectedTherapistNames = [];
    therapistInputs.innerHTML = '';
    therapistList.innerHTML = '';

    summary.therapist = 'No Preference';


    therapistMessage.textContent =
        'Select a date and time first to view therapist availability.';


    renderSummary();

}


/* =========================================================
   THERAPIST OPTION
   ========================================================= */

document.addEventListener(
    'click',
    function(e){

        const option =
            e.target.closest(
                '.therapist-option'
            );

        if(!option){
            return;
        }


        document
        .querySelectorAll(
            '.therapist-option'
        )
        .forEach(function(card){

            card.classList.remove(
                'active'
            );

        });


        option.classList.add(
            'active'
        );


        const value =
            option.dataset.therapistOption;


        if(value === '0'){

            therapistInput.value = '0';
            therapistModeInput.value = '0';
            selectedTherapistIds = []; selectedTherapistNames = []; therapistInputs.innerHTML = '';

            therapistList.innerHTML = '';


            summary.therapist =
                'No Preference';


            therapistMessage.textContent =
                'No Preference selected. Any available therapist may be assigned.';


            renderSummary();

            return;

        }


        if(value === 'choose'){

            therapistInput.value = '0';
            therapistModeInput.value = 'choose';
            selectedTherapistIds = []; selectedTherapistNames = []; therapistInputs.innerHTML = '';

            summary.therapist =
                'No Preference';


            renderSummary();


            if(
                !bookingDate.value ||
                !bookingTime.value
            ){

                therapistList.innerHTML = '';

                therapistMessage.textContent =
                    'Please select a date and time first.';

                return;

            }


            loadTherapists(
                bookingDate.value,
                bookingTime.value
            );

        }

    }
);


/* =========================================================
   LOAD THERAPISTS
   ========================================================= */

function loadTherapists(
    selectedDate,
    selectedTime
){

    therapistList.innerHTML = '';

    therapistMessage.textContent =
        'Loading therapists...';


    let selectedDuration =
        durationInput.value ||
        '1 Hour';


    fetch(
        'get_available_therapists.php?date=' +
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

    .then(function(response){
        return response.json();
    })

    .then(function(data){

        therapistList.innerHTML = '';


        if(
            !Array.isArray(data) ||
            data.length === 0
        ){

            therapistMessage.textContent =
                'No therapists found.';

            return;

        }


        therapistMessage.textContent =
            'Select an available therapist.';


        data.forEach(function(therapist){

            const therapistId =
                therapist.id ?? '';


            const therapistName =
                therapist.name ||
                'Unnamed Therapist';


            const isAvailable =
                therapist.available === undefined
                ? true
                : Boolean(
                    therapist.available
                );


            const card =
                document.createElement(
                    'div'
                );


            card.className =
                'select-card therapist-card';


            card.dataset.therapistId =
                therapistId;


            card.dataset.therapistName =
                therapistName;


            if(!isAvailable){

                card.classList.add(
                    'unavailable'
                );


                card.innerHTML = `

                    <strong>
                        ${therapistName}
                    </strong>

                    <span
                        class="
                            card-desc
                            unavailable-text
                        "
                    >
                        Booked / Unavailable
                    </span>

                `;

            }else{

                card.innerHTML = `

                    <strong>
                        ${therapistName}
                    </strong>

                    <span
                        class="
                            card-desc
                            available-text
                        "
                    >
                        Available
                    </span>

                `;


                card.addEventListener(
                    'click',
                    function(){

                        const id = String(therapistId);
                        const pos = selectedTherapistIds.indexOf(id);
                        const maxPick = Number(paxInput.value || 1);
                        if (pos >= 0) {
                            selectedTherapistIds.splice(pos, 1);
                            selectedTherapistNames.splice(pos, 1);
                            card.classList.remove('active');
                        } else {
                            if (selectedTherapistIds.length >= maxPick) {
                                alert('Select only ' + maxPick + ' therapist(s) for this booking.');
                                return;
                            }
                            selectedTherapistIds.push(id);
                            selectedTherapistNames.push(therapistName);
                            card.classList.add('active');
                        }
                        therapistInput.value = selectedTherapistIds[0] || '0';
                        therapistInputs.innerHTML = selectedTherapistIds.map(function(tid){
                            return '<input type="hidden" name="therapists[]" value="' + tid + '">';
                        }).join('');
                        summary.therapist = selectedTherapistIds.length
                            ? selectedTherapistNames.join(', ') + ' (' + selectedTherapistIds.length + '/' + maxPick + ')'
                            : 'Select ' + maxPick + ' therapist(s)';
                        therapistMessage.textContent = 'Selected ' + selectedTherapistIds.length + ' of ' + maxPick + ' therapist(s).';
                        renderSummary();

                    }
                );

            }


            therapistList.appendChild(
                card
            );

        });

    })

    .catch(function(error){

        console.error(
            'Therapist error:',
            error
        );


        therapistList.innerHTML = '';


        therapistMessage.textContent =
            'Unable to load therapists. Please try again.';

    });

}


function getOpeningHour(dateString) {
    const d = new Date(dateString + 'T12:00:00');
    return (d.getDay() === 0 || d.getDay() === 6) ? 13 : 15;
}
function durationHours(value) {
    const text = String(value || '1 Hour').toLowerCase();
    let m = text.match(/(\d+(?:\.\d+)?)\s*(?:hours?|hrs?)/);
    if (m) return parseFloat(m[1]);
    m = text.match(/(\d+)\s*(?:minutes?|mins?)/);
    if (m) return parseInt(m[1],10) / 60;
    m = text.match(/\d+(?:\.\d+)?/);
    if (m) { const n=parseFloat(m[0]); return n>3 ? n/60 : n; }
    return 1;
}
function selectedTimeDate(dateString, timeString) {
    const [h,m] = timeString.split(':').map(Number);
    const d = new Date(dateString + 'T00:00:00');
    d.setHours(h,m,0,0);
    if (h < getOpeningHour(dateString)) d.setDate(d.getDate()+1);
    return d;
}
function slotWithinHours(dateString, timeString, duration) {
    const [h,m] = timeString.split(':').map(Number);
    let opHour=h; if(h<getOpeningHour(dateString)) opHour+=24;
    return opHour*60+m + durationHours(duration)*60 <= 27*60;
}
function slotHasLeadTime(dateString, timeString) {
    return selectedTimeDate(dateString,timeString).getTime() >= Date.now()+2*60*60*1000;
}

/* =========================================================
   DATE CHANGE / LOAD TIME
   ========================================================= */

bookingDate.addEventListener(
    'change',
    async function(){

        timeBox.innerHTML = '';

        bookingTime.value = '';


        resetTherapist();


        const selectedDate =
            bookingDate.value;


        if(!selectedDate){

            summary.date = '—';

            summary.time = '—';

            renderSummary();

            return;

        }


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


        timeBox.innerHTML = `
            <div
                class="message"
                style="grid-column:1/-1;"
            >
                Checking available time slots...
            </div>
        `;


        const cards = [];


        /*
           Operating hours: weekdays 3 PM–3 AM, weekends 1 PM–3 AM
        */

        const openingHour = getOpeningHour(selectedDate);
        for(
            let hour = openingHour;
            hour <= 26;
            hour++
        ){

            const actualHour = hour % 24;
            const timeValue = String(actualHour).padStart(2, '0') + ':00';


            try{

                const response =
                    await fetch(
                        'check_slot.php?date=' +
                        encodeURIComponent(
                            selectedDate
                        ) +
                        '&time=' +
                        encodeURIComponent(
                            timeValue
                        ) + '&duration=' + encodeURIComponent(durationInput.value || '1 Hour') +
                        '&room_type=' + encodeURIComponent(roomInput.value || 'Single Room') +
                        '&pax=' + encodeURIComponent(paxInput.value || '1')
                    );


                const data =
                    await response.json();


                const card =
                    document.createElement(
                        'div'
                    );


                card.className =
                    'time-card';


                const clockHour = hour % 24;
                const displayHour = clockHour % 12 || 12;
                const meridiem = clockHour >= 12 ? 'PM' : 'AM';


                const isCoupleRoom = (roomInput.value || 'Single Room') === 'Couple Room';
                const roomStatus = data.available ? 'Available' : 'Occupied';

                card.innerHTML = `
                    ${displayHour}:00 ${meridiem}
                    <span class="slot-count">
                        ${isCoupleRoom ? roomStatus : `${Number(data.remaining ?? 0)} slot${Number(data.remaining) === 1 ? '' : 's'}`}
                    </span>
                `;


                const leadTimeBlocked = !slotHasLeadTime(selectedDate, timeValue);
                const durationBlocked = !slotWithinHours(selectedDate, timeValue, durationInput.value || '1 Hour');

                if(!data.available || leadTimeBlocked || durationBlocked){

                    card.classList.add('dim');
                    card.setAttribute('aria-disabled', 'true');
                    card.style.pointerEvents = 'none';
                    card.style.opacity = '0.45';
                    card.style.cursor = 'not-allowed';
                    if (leadTimeBlocked) {
                        card.title = 'Must be booked at least 2 hours in advance';
                    } else if (durationBlocked) {
                        card.title = 'Appointment must finish by 3:00 AM';
                    } else {
                        card.title = 'This time slot is unavailable';
                    }

                }else{

                    card.addEventListener(
                        'click',
                        function(){

                            document
                            .querySelectorAll(
                                '.time-card'
                            )
                            .forEach(function(item){

                                item.classList.remove(
                                    'active'
                                );

                            });


                            card.classList.add(
                                'active'
                            );


                            bookingTime.value =
                                timeValue;


                            summary.time =
                                `${displayHour}:00 ${meridiem}`;


                            resetTherapist();


                            /*
                               resetTherapist also renders
                               summary, so restore chosen time.
                            */

                            summary.time =
                                `${displayHour}:00 ${meridiem}`;


                            therapistMessage.textContent =
                                'Choose No Preference or click Choose Therapist to view availability.';


                            renderSummary();

                        }
                    );

                }


                cards.push(card);


            }catch(error){

                console.error(
                    'Time slot error:',
                    error
                );

            }

        }


        timeBox.innerHTML = '';


        if(cards.length === 0){

            timeBox.innerHTML = `
                <div
                    class="message"
                    style="grid-column:1/-1;"
                >
                    Unable to load available time slots.
                </div>
            `;

            return;

        }


        cards.forEach(function(card){

            timeBox.appendChild(
                card
            );

        });

    }
);


/* =========================================================
   FORM VALIDATION
   ========================================================= */

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

        if (Number(paxInput.value) < 1 || Number(paxInput.value) > 4 || (roomInput.value === 'Couple Room' && Number(paxInput.value) !== 2)) {
            alert('Single Room allows 1–4 guests. Couple Room is exactly 2 guests.'); e.preventDefault(); return;
        }
        if (!slotHasLeadTime(bookingDate.value, bookingTime.value)) {
            alert('Please book at least 2 hours in advance.'); e.preventDefault(); return;
        }
        if (!slotWithinHours(bookingDate.value, bookingTime.value, durationInput.value)) {
            alert('The appointment must finish by 3:00 AM. Please choose an earlier time or shorter duration.'); e.preventDefault(); return;
        }
        if (therapistModeInput.value === 'choose' && selectedTherapistIds.length !== Number(paxInput.value)) {
            alert('Please select exactly ' + Number(paxInput.value) + ' available therapist(s), or choose No Preference.'); e.preventDefault(); return;
        }

        if(
            !serviceIdInput.value
        ){

            alert(
                'Please select a service.'
            );

            e.preventDefault();

            return;

        }


        if(
            !durationInput.value
        ){

            alert(
                'Please select a duration.'
            );

            e.preventDefault();

            return;

        }


        if(
            !roomInput.value
        ){

            alert(
                'Please select a room.'
            );

            e.preventDefault();

            return;

        }


        if(
            !bookingDate.value
        ){

            alert(
                'Please select an appointment date.'
            );

            e.preventDefault();

            return;

        }


        if(
            !bookingTime.value
        ){

            alert(
                'Please select an available time.'
            );

            e.preventDefault();

            return;

        }


        if(
            !priceInput.value ||
            Number(
                priceInput.value
            ) <= 0
        ){

            alert(
                'Please select a service duration so the booking price can be calculated.'
            );

            e.preventDefault();

            return;

        }

    }
);



durationInput.addEventListener('change', function(){
    if (bookingDate.value) bookingDate.dispatchEvent(new Event('change'));
});

/* =========================================================
   INITIAL SUMMARY
   ========================================================= */

renderSummary();


/* =========================================================
   PREFILL SERVICE FROM LANDING-PAGE POPUP
   This only reads query parameters on the guest booking page.
   ========================================================= */
(async function prefillGuestServiceFromLanding() {
    const params = new URLSearchParams(window.location.search);
    const requestedServiceId = parseInt(params.get('service_id') || '0', 10);
    if (!requestedServiceId) return;

    // Landing-page packages may point to services whose database category is
    // Massage, Package, or Promo. Find the service by ID in the live category
    // data rather than assuming that its landing-page section is its category.
    const categoryOrder = ['Massage', 'Package', 'Promo', 'Add-ons'];
    let matchedCategory = null;

    for (const categoryName of categoryOrder) {
        try {
            const response = await fetch(
                'get_services_by_category.php?cat=' + encodeURIComponent(categoryName),
                { cache: 'no-store' }
            );
            if (!response.ok) continue;
            const data = await response.json();
            if (Array.isArray(data) && data.some(service =>
                parseInt(service.id || service.service_id || '0', 10) === requestedServiceId
            )) {
                matchedCategory = categoryName;
                break;
            }
        } catch (error) {
            console.warn('Unable to check booking category:', categoryName, error);
        }
    }

    if (!matchedCategory) {
        console.warn('Requested landing-page service was not found in guest booking.');
        return;
    }

    const categoryCard = Array.from(document.querySelectorAll('.category'))
        .find(card => (card.dataset.cat || '').toLowerCase() === matchedCategory.toLowerCase());
    if (!categoryCard) {
        console.warn('The matching service category is not available on the booking page:', matchedCategory);
        return;
    }

    categoryCard.click();

    // Wait for the category's service cards to finish loading.
    let attempts = 0;
    const waitForService = setInterval(() => {
        attempts++;
        const matchingService = Array.from(document.querySelectorAll('#serviceBox .service'))
            .find(card => parseInt(card.dataset.id || '0', 10) === requestedServiceId);

        if (matchingService) {
            clearInterval(waitForService);
            matchingService.click();

            // Selecting a service loads its durations asynchronously.
            let durationAttempts = 0;
            const waitForDuration = setInterval(() => {
                durationAttempts++;
                const durationCards = Array.from(document.querySelectorAll('.duration'));
                if (durationCards.length) {
                    clearInterval(waitForDuration);
                    const requestedDuration = params.get('duration');
                    const selectedDuration = requestedDuration
                        ? durationCards.find(card =>
                            (card.dataset.duration || '').toLowerCase() === requestedDuration.toLowerCase()
                        )
                        : null;
                    (selectedDuration || durationCards[0]).click();
                } else if (durationAttempts >= 50) {
                    clearInterval(waitForDuration);
                }
            }, 150);
        } else if (attempts >= 50) {
            clearInterval(waitForService);
            console.warn('Service card did not load for service ID:', requestedServiceId);
        }
    }, 150);
})();

</script>

</body>

</html>