<?php

session_start();

include __DIR__ . '/../includes/db.php';


/* =========================================================
   ADMIN AUTHENTICATION
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Walk-in Booking | Mizpah Wellness Spa</title>

<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
    rel="stylesheet"
>

<style>

/* =========================================================
   RESET
   ========================================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    min-height:100vh;

    background:#0b0b0b;

    color:#fff;

    font-family:'Poppins',sans-serif;

}


/* =========================================================
   BACKGROUND
   ========================================================= */

body::before{

    content:"";

    position:fixed;

    top:-250px;
    right:-200px;

    width:500px;
    height:500px;

    background:
        rgba(214,194,156,.045);

    filter:blur(120px);

    pointer-events:none;

}


/* =========================================================
   HEADER
   ========================================================= */

.header{

    position:sticky;

    top:0;

    z-index:1000;

    min-height:68px;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    padding:11px 5%;

    background:
        rgba(11,11,11,.96);

    border-bottom:
        1px solid
        rgba(214,194,156,.12);

    backdrop-filter:blur(15px);

}

.brand{

    display:flex;

    align-items:center;

    gap:11px;

}

.logo{

    width:42px;
    height:42px;

    object-fit:contain;

}

.brand-text small{

    display:block;

    color:#777;

    font-size:8px;

    text-transform:uppercase;

    letter-spacing:1.5px;

}

.brand-text strong{

    color:#D6C29C;

    font-family:
        'Playfair Display',
        serif;

    font-size:17px;

    font-weight:600;

}

.back-btn{

    padding:8px 13px;

    border:
        1px solid
        rgba(214,194,156,.25);

    border-radius:7px;

    color:#aaa;

    text-decoration:none;

    font-size:9px;

    transition:.2s;

}

.back-btn:hover{

    color:#111;

    background:#D6C29C;

}


/* =========================================================
   HERO
   ========================================================= */

.hero{

    padding:
        45px 5%
        30px;

    text-align:center;

}

.hero-label{

    color:#7d705a;

    font-size:8px;

    text-transform:uppercase;

    letter-spacing:2.5px;

    margin-bottom:7px;

}

.hero h1{

    color:#D6C29C;

    font-family:
        'Playfair Display',
        serif;

    font-size:
        clamp(27px,4vw,38px);

    font-weight:600;

    margin-bottom:7px;

}

.hero p{

    color:#777;

    font-size:10px;

    line-height:1.7;

}


/* =========================================================
   MAIN LAYOUT
   ========================================================= */

.page{

    width:100%;

    max-width:1180px;

    margin:auto;

    padding:
        15px 4%
        70px;

}

.booking-layout{

    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        300px;

    gap:18px;

    align-items:start;

}

.form-column{

    display:flex;

    flex-direction:column;

    gap:14px;

}


/* =========================================================
   SECTION BOX
   ========================================================= */

.box{

    background:
        linear-gradient(
            145deg,
            #141414,
            #101010
        );

    border:
        1px solid
        rgba(255,255,255,.065);

    border-radius:14px;

    padding:20px;

}

.step-header{

    display:flex;

    align-items:flex-start;

    gap:11px;

    margin-bottom:17px;

}

.step-number{

    width:27px;
    height:27px;

    flex-shrink:0;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:
        rgba(214,194,156,.10);

    border:
        1px solid
        rgba(214,194,156,.22);

    color:#D6C29C;

    font-size:9px;

    font-weight:600;

}

.step-header h2{

    color:#D6C29C;

    font-family:
        'Playfair Display',
        serif;

    font-size:17px;

    font-weight:600;

    margin-bottom:2px;

}

.step-header p{

    color:#666;

    font-size:8px;

    line-height:1.5;

}


/* =========================================================
   SUB LABEL
   ========================================================= */

.field-label{

    display:block;

    margin:
        16px 0
        7px;

    color:#888;

    font-size:8px;

    font-weight:500;

    text-transform:uppercase;

    letter-spacing:1px;

}

.field-label:first-child{

    margin-top:0;

}


/* =========================================================
   OPTION GRID
   ========================================================= */

.grid{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(130px,1fr)
        );

    gap:8px;

}


/* =========================================================
   CARDS
   ========================================================= */

.card{

    position:relative;

    min-height:48px;

    padding:11px;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    text-align:center;

    background:#0f0f0f;

    border:
        1px solid
        #262626;

    border-radius:9px;

    color:#bbb;

    font-size:9px;

    line-height:1.5;

    cursor:pointer;

    transition:.2s;

    user-select:none;

}

.card:hover{

    border-color:
        rgba(214,194,156,.35);

    color:#D6C29C;

    transform:
        translateY(-1px);

}

.card.active{

    border-color:#D6C29C;

    background:
        rgba(214,194,156,.075);

    color:#D6C29C;

    box-shadow:
        inset 0 0 0 1px
        rgba(214,194,156,.10);

}

.card.dim{

    opacity:.28;

    cursor:not-allowed;

    pointer-events:none;

    transform:none;

}

.small{

    margin-top:3px;

    color:#666;

    font-size:7px;

    line-height:1.4;

}


/* =========================================================
   THERAPIST UNAVAILABLE
   ========================================================= */

.card.therapist.unavailable{

    opacity:.30;

    cursor:not-allowed;

    pointer-events:none;

    border-color:#242424;

    color:#777;

    transform:none;

}

.card.therapist.unavailable .small{

    color:#744f4f;

}


/* =========================================================
   SERVICE DESCRIPTION
   ========================================================= */

#serviceDesc{

    margin-top:9px;

    color:#777;

    font-size:9px;

    line-height:1.6;

}


/* =========================================================
   INPUTS
   ========================================================= */

input,
select,
textarea{

    width:100%;

    padding:11px 12px;

    background:#0d0d0d;

    color:#ddd;

    border:
        1px solid
        #292929;

    border-radius:8px;

    outline:none;

    font-family:'Poppins',sans-serif;

    font-size:9px;

    transition:.2s;

}

input:focus,
select:focus,
textarea:focus{

    border-color:
        rgba(214,194,156,.55);

    box-shadow:
        0 0 0 2px
        rgba(214,194,156,.05);

}

input::placeholder,
textarea::placeholder{

    color:#555;

}

textarea{

    min-height:90px;

    resize:vertical;

}

input[type="date"]{

    color-scheme:dark;

}


/* =========================================================
   CUSTOMER GRID
   ========================================================= */

.customer-grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:10px;

}

.full-field{

    grid-column:1/-1;

}


/* =========================================================
   SUMMARY
   ========================================================= */

.summary{

    position:sticky;

    top:86px;

    background:
        linear-gradient(
            145deg,
            #151515,
            #101010
        );

    border:
        1px solid
        rgba(214,194,156,.16);

    border-radius:14px;

    padding:20px;

}

.summary-label{

    color:#6f634f;

    font-size:7px;

    text-transform:uppercase;

    letter-spacing:2px;

    margin-bottom:4px;

}

.summary h3{

    color:#D6C29C;

    font-family:
        'Playfair Display',
        serif;

    font-size:19px;

    margin-bottom:18px;

}

.summary-row{

    display:flex;

    justify-content:space-between;

    align-items:flex-start;

    gap:15px;

    padding:
        9px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,.045);

}

.summary-row span:first-child{

    color:#666;

    font-size:8px;

}

.summary-row span:last-child{

    color:#bbb;

    font-size:8px;

    text-align:right;

    word-break:break-word;

}

.summary-addons{

    margin-top:14px;

    padding:
        10px;

    background:
        rgba(255,255,255,.02);

    border-radius:8px;

}

.summary-addons-title{

    color:#666;

    font-size:7px;

    text-transform:uppercase;

    letter-spacing:1px;

    margin-bottom:5px;

}

#summaryAddons{

    color:#888;

    font-size:8px;

    line-height:1.6;

}


/* =========================================================
   TOTAL
   ========================================================= */

.total-box{

    display:flex;

    align-items:flex-end;

    justify-content:space-between;

    gap:15px;

    margin-top:16px;

    padding-top:14px;

    border-top:
        1px solid
        rgba(214,194,156,.15);

}

.total-label{

    color:#777;

    font-size:8px;

}

.total-price{

    color:#D6C29C;

    font-family:
        'Playfair Display',
        serif;

    font-size:23px;

    font-weight:600;

}


/* =========================================================
   CONFIRM BUTTON
   ========================================================= */

.btn{

    width:100%;

    margin-top:16px;

    padding:12px 15px;

    border:none;

    border-radius:8px;

    background:#D6C29C;

    color:#111;

    font-family:'Poppins',sans-serif;

    font-size:9px;

    font-weight:600;

    cursor:pointer;

    transition:.2s;

}

.btn:hover{

    background:#ead8b1;

    transform:
        translateY(-1px);

}

.admin-note{

    margin-top:10px;

    color:#555;

    font-size:7px;

    line-height:1.5;

    text-align:center;

}


/* =========================================================
   PAX NOTE
   ========================================================= */

.day-availability{display:grid;grid-template-columns:repeat(auto-fill,minmax(125px,1fr));gap:8px;margin:12px 0 18px}
.day-slot{border:1px solid #6b5637;border-radius:8px;padding:9px 10px;text-align:center;font-size:13px;background:#211b12;color:#f7f0e4;cursor:pointer}
.day-slot .slot-status{display:block;font-size:11px;margin-top:4px}
.day-slot.available{border-color:#6d9568}
.day-slot.available .slot-status{color:#9bd294}
.day-slot.occupied,.day-slot.past{opacity:.58;cursor:not-allowed;border-color:#8b5750}
.day-slot.occupied .slot-status,.day-slot.past .slot-status{color:#e5a19a}
.day-slot:disabled{pointer-events:none}

.day-availability{margin-top:12px;padding:12px;border:1px solid #30281c;border-radius:12px;background:linear-gradient(180deg,#17130e,#100e0b);box-shadow:inset 0 1px 0 rgba(255,255,255,.025)}
.day-availability:empty{display:none}
.day-slot{min-height:58px;border:1px solid #30281c;border-radius:10px;padding:8px 7px;text-align:center;font-size:12px;background:#15120e;color:#eee6d8;cursor:pointer;transition:.18s ease;display:flex;flex-direction:column;justify-content:center;gap:2px}
.day-slot:hover:not(:disabled){transform:translateY(-1px);border-color:#8d7041;background:#211a11}
.day-slot.available{border-color:#4d6848;background:#121911}
.day-slot.available .slot-status{color:#9ac58e}
.day-slot.occupied,.day-slot.past{opacity:.48;cursor:not-allowed;border-color:#553a34;background:#15100f}
.day-slot.occupied .slot-status,.day-slot.past .slot-status{color:#c8877e}
.day-slot .slot-time{font-weight:600;color:#d6c29c;font-size:12px}
.day-slot .slot-status{display:block;font-size:10px;margin-top:2px}
.manual-time-wrap{position:relative}
#manualTime{height:48px;font-size:15px;font-weight:600;letter-spacing:.2px;padding-left:14px}
#manualTime:focus{border-color:#b8965d;box-shadow:0 0 0 3px rgba(184,150,93,.08)}
.guest-form{border:1px solid #30281c!important;border-radius:12px!important;padding:16px!important;margin-bottom:12px!important;background:linear-gradient(180deg,#17130f,#12100d);box-shadow:0 8px 20px rgba(0,0,0,.12)}
.guest-form h3{font-size:12px!important;letter-spacing:.08em;text-transform:uppercase;margin:0 0 12px!important;color:#d6c29c!important}
.guest-form .field-label{margin-top:8px}
.therapist-list-title{margin:0 0 10px;color:#d6c29c;font-size:10px;letter-spacing:.08em;text-transform:uppercase}
.card.therapist{min-height:62px;font-size:11px;padding:12px;border-radius:10px}
.card.therapist .small{font-size:9px;color:#777}
#therapistBox .empty-option{grid-column:1/-1}
.pax-note{

    margin-top:5px;

    color:#666;

    font-size:7px;

}


/* =========================================================
   NO OPTIONS
   ========================================================= */

.empty-option{

    grid-column:1/-1;

    padding:14px;

    border:
        1px dashed
        #292929;

    border-radius:8px;

    color:#555;

    font-size:8px;

    text-align:center;

}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width:900px){

    .booking-layout{

        grid-template-columns:1fr;

    }

    .summary{

        position:static;

    }

}

@media(max-width:600px){

    .header{

        padding:
            10px 4%;

    }

    .brand-text strong{

        font-size:14px;

    }

    .brand-text small{

        display:none;

    }

    .logo{

        width:36px;
        height:36px;

    }

    .hero{

        padding:
            35px 20px
            20px;

    }

    .page{

        padding:
            10px 4%
            50px;

    }

    .box{

        padding:16px;

    }

    .customer-grid{

        grid-template-columns:1fr;

    }

    .full-field{

        grid-column:auto;

    }

    .grid{

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );

    }

}

</style>

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="header">

    <div class="brand">

        <img
            src="../assets/images/logo.png"
            class="logo"
            alt="Mizpah Wellness Spa"
        >

        <div class="brand-text">

            <small>
                Admin
            </small>

            <strong>
                Walk-in Booking
            </strong>

        </div>

    </div>


    <a
        href="bookings.php"
        class="back-btn"
    >
        Back to Bookings
    </a>

</header>


<!-- =========================================================
     HERO
     ========================================================= -->

<section class="hero">

    <div class="hero-label">
        Mizpah Wellness Spa
    </div>

    <h1>
        Walk-in Appointment
    </h1>

    <p>
        Create a booking for customers who visit the spa
        without an online reservation.
    </p>

</section>


<!-- =========================================================
     PAGE
     ========================================================= -->

<div class="page">

<form
    method="POST"
    action="walkin-save.php"
    id="bookingForm"
>


<div class="booking-layout">


<!-- =========================================================
     LEFT SIDE
     ========================================================= -->

<div class="form-column">


    <!-- =====================================================
         STEP 1
         ===================================================== -->

    <section class="box">

        <div class="step-header">

            <div class="step-number">
                1
            </div>

            <div>

                <h2>
                    Choose Service
                </h2>

                <p>
                    Select the service category, treatment,
                    duration and optional add-ons.
                </p>

            </div>

        </div>


        <label class="field-label">
            Category
        </label>


        <div class="grid">

            <div
                class="card category"
                data-cat="Massage"
            >
                Massage
            </div>

            <div
                class="card category"
                data-cat="Package"
            >
                Package
            </div>

            <div
                class="card category"
                data-cat="Promo"
            >
                Promo
            </div>

        </div>


        <label class="field-label">
            Service
        </label>


        <div
            class="grid"
            id="serviceBox"
        >

            <div class="empty-option">
                Select a category first.
            </div>

        </div>


        <div id="serviceDesc"></div>


        <label class="field-label">
            Duration
        </label>


        <div
            class="grid"
            id="durationBox"
        >

            <div class="empty-option">
                Select a service first.
            </div>

        </div>


        <label class="field-label">
            Optional Add-ons
        </label>


        <div
            class="grid"
            id="addonBox"
        >

            <div class="empty-option">
                Select a service first.
            </div>

        </div>

    </section>


    <!-- =====================================================
         STEP 2
         ===================================================== -->

    <section class="box">

        <div class="step-header">

            <div class="step-number">
                2
            </div>

            <div>

                <h2>
                    Schedule & Room
                </h2>

                <p>
                    Select the room, appointment date,
                    number of guests and available time.
                </p>

            </div>

        </div>


        <label class="field-label">
            Room Type
        </label>


        <div class="grid">

            <div
                class="card room"
                data-room="Single Room"
            >
                Single Room

                <div class="small">
                    For individual appointments
                </div>

            </div>


            <div
                class="card room"
                data-room="Couple Room"
            >
                Couple Room

                <div class="small">
                    Automatically sets 2 guests
                </div>

            </div>

        </div>


        <label class="field-label">
            Appointment Date
        </label>


        <input
            type="date"
            id="date"
            required
        >


        <label class="field-label">
            Number of Guests
        </label>


        <input
            type="number"
            name="pax"
            id="pax"
            value="1"
            min="1"
            max="4"
            required
        >


        <div
            class="pax-note"
            id="paxNote"
        >
            Maximum of 4 guests.
        </div>


        <label class="field-label">
            Appointment Time (AM/PM)
        </label>

        <input
            type="text"
            id="manualTime"
            placeholder="Example: 2:17 PM"
            autocomplete="off"
            required
        >

        <div class="pax-note" id="timeAvailability">
            Select a room, date, service, and duration to view availability.
        </div>
        <div id="dayAvailability" class="day-availability" aria-live="polite"></div>

        <div class="pax-note">
            Operating hours: Weekdays 3 PM–3 AM; weekends 1 PM–3 AM. Last booking starts at 2 AM.
        </div>

    </section>


    <!-- =====================================================
         STEP 3
         ===================================================== -->

    <section class="box">

        <div class="step-header">

            <div class="step-number">
                3
            </div>

            <div>

                <h2>
                    Choose Therapist
                </h2>

                <p>
                    Assign an available therapist or leave
                    the appointment as No Preference.
                </p>

            </div>

        </div>


        <div
            class="grid"
            id="therapistBox"
        >

            <div
                class="card therapist active"
                data-id="0"
                data-name="No Preference"
            >

                No Preference

                <div class="small">
                    Assign later
                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         STEP 4
         ===================================================== -->

    <section class="box">

        <div class="step-header">

            <div class="step-number">
                4
            </div>

            <div>

                <h2>
                    Customer Details
                </h2>

                <p>
                    Enter the walk-in customer's contact
                    and payment information.
                </p>

            </div>

        </div>


        <div class="customer-grid">

            <div class="full-field">
                <label class="field-label">Guest Details</label>
                <div id="guestDetailsContainer"></div>
            </div>

            <div>

                <label class="field-label">
                    Payment Method
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


            <div class="full-field">

                <label class="field-label">
                    Notes
                </label>

                <textarea
                    name="notes"
                    placeholder="Optional notes or customer requests..."
                ></textarea>

            </div>


        </div>

    </section>


</div>


<!-- =========================================================
     RIGHT SIDE SUMMARY
     ========================================================= -->

<aside class="summary">


    <div class="summary-label">
        Admin Walk-in
    </div>


    <h3>
        Booking Summary
    </h3>


    <div class="summary-row">

        <span>
            Service
        </span>

        <span id="summaryService">
            —
        </span>

    </div>


    <div class="summary-row">

        <span>
            Duration
        </span>

        <span id="summaryDuration">
            —
        </span>

    </div>


    <div class="summary-row">

        <span>
            Room
        </span>

        <span id="summaryRoom">
            —
        </span>

    </div>


    <div class="summary-row">

        <span>
            Date
        </span>

        <span id="summaryDate">
            —
        </span>

    </div>


    <div class="summary-row">

        <span>
            Time
        </span>

        <span id="summaryTime">
            —
        </span>

    </div>


    <div class="summary-row">

        <span>
            Guests
        </span>

        <span id="summaryPax">
            1
        </span>

    </div>


    <div class="summary-row">

        <span>
            Therapist
        </span>

        <span id="summaryTherapist">
            No Preference
        </span>

    </div>


    <div class="summary-addons">

        <div class="summary-addons-title">
            Add-ons
        </div>

        <div id="summaryAddons">
            None selected
        </div>

    </div>


    <div class="total-box">

        <div>

            <div class="total-label">
                Estimated Total
            </div>

        </div>

        <div
            class="total-price"
            id="summaryTotal"
        >
            ₱0.00
        </div>

    </div>


    <button
        type="submit"
        class="btn"
    >
        Confirm Walk-in Booking
    </button>


    <div class="admin-note">
        Review the booking information before confirming.
    </div>


</aside>


</div>


<!-- =========================================================
     HIDDEN FORM VALUES
     ========================================================= -->

<input type="hidden" name="customer_name" id="customer_name">
<input type="hidden" name="phone" id="phone">

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

<input
    type="hidden"
    name="room_type"
    id="room_type"
>

<input
    type="hidden"
    name="booking_date"
    id="booking_date"
>

<input
    type="hidden"
    name="booking_time"
    id="booking_time"
>

<input
    type="hidden"
    name="therapist"
    id="therapist"
    value="0"
>

<input
    type="hidden"
    name="addons"
    id="addons"
>


</form>

</div>


<script>

/* =========================================================
   ELEMENTS
   ========================================================= */

const serviceBox =
    document.getElementById("serviceBox");

const durationBox =
    document.getElementById("durationBox");

const addonBox =
    document.getElementById("addonBox");

const therapistBox =
    document.getElementById("therapistBox");

const timeBox =
    document.getElementById("timeBox");

const serviceDesc =
    document.getElementById("serviceDesc");


const serviceIdInput =
    document.getElementById("service_id");

const serviceInput =
    document.getElementById("service");

const durationInput =
    document.getElementById("duration");

const priceInput =
    document.getElementById("price");

const roomInput =
    document.getElementById("room_type");

const bookingDateInput =
    document.getElementById("booking_date");

const bookingTimeInput =
    document.getElementById("booking_time");

const therapistInput =
    document.getElementById("therapist");

const addonsInput =
    document.getElementById("addons");

const dateInput =
    document.getElementById("date");

const paxInput =
    document.getElementById("pax");

const paxNote =
    document.getElementById("paxNote");

const manualTimeInput = document.getElementById("manualTime");
const timeAvailability = document.getElementById("timeAvailability");
const guestDetailsContainer = document.getElementById("guestDetailsContainer");
const legacyCustomerName = document.getElementById("customer_name");
const legacyPhone = document.getElementById("phone");


/* =========================================================
   STATE
   ========================================================= */

let basePrice = 0;

let addonTotal = 0;

let selectedDuration = "";

let selectedDate = "";

let selectedTime = "";

let selectedTherapistIds = [];
let therapistLoadRequestId = 0; // Prevent overlapping requests from appending duplicate cards.


/* =========================================================
   HELPERS
   ========================================================= */

function escapeHTML(value){

    if(
        value === null ||
        value === undefined
    ){
        return "";
    }

    return String(value)

        .replace(/&/g,"&amp;")
        .replace(/</g,"&lt;")
        .replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;")
        .replace(/'/g,"&#039;");

}


function money(value){

    return "₱" +
        Number(value || 0)
        .toLocaleString(
            "en-PH",
            {
                minimumFractionDigits:2,
                maximumFractionDigits:2
            }
        );

}


function formatTime24(time){

    if(!time){
        return "—";
    }

    const parts =
        time.split(":");

    let hour =
        parseInt(parts[0]);

    const minute =
        parts[1] || "00";

    const ampm =
        hour >= 12
        ? "PM"
        : "AM";

    hour =
        hour % 12 || 12;

    return (
        hour +
        ":" +
        minute +
        " " +
        ampm
    );

}


function parseManualTime(value) {
    const match = String(value || "").trim().match(/^(0?[1-9]|1[0-2]):([0-5]\d)\s*(AM|PM)$/i);
    if (!match) return null;
    let hour = Number(match[1]);
    const minute = Number(match[2]);
    const period = match[3].toUpperCase();
    if (period === "AM" && hour === 12) hour = 0;
    if (period === "PM" && hour !== 12) hour += 12;
    return String(hour).padStart(2, "0") + ":" + String(minute).padStart(2, "0");
}

function isPastManualTime(date, time24) {
    const [hour, minute] = time24.split(":").map(Number);
    const selectedDay = new Date(date + "T12:00:00");
    const selectedWeekday = selectedDay.getDay();
    const openingHour = (selectedWeekday === 0 || selectedWeekday === 6) ? 13 : 15;
    // Times before opening are the after-midnight continuation of the selected operating date.
    if (hour < openingHour) selectedDay.setDate(selectedDay.getDate() + 1);
    selectedDay.setHours(hour, minute, 0, 0);
    return selectedDay.getTime() <= Date.now();
}

function renderGuestForms() {
    if (!guestDetailsContainer) return;
    const count = roomInput.value === "Couple Room" ? 2 : Math.max(1, Math.min(4, Number(paxInput.value || 1)));
    const previous = Array.from(guestDetailsContainer.querySelectorAll(".guest-form"));
    const saved = previous.map(row => ({
        name: row.querySelector('[name="guest_name[]"]')?.value || "",
        phone: row.querySelector('[name="guest_phone[]"]')?.value || ""
    }));
    guestDetailsContainer.innerHTML = "";
    for (let i = 0; i < count; i++) {
        const guest = saved[i] || {};
        const row = document.createElement("div");
        row.className = "guest-form";
        
        row.innerHTML = `
            <h3 style="color:#D6C29C;margin-bottom:12px">Guest ${i + 1}</h3>
            <label class="field-label">Full Name</label>
            <input type="text" name="guest_name[]" placeholder="Guest ${i + 1} full name" required value="${escapeHTML(guest.name || "")}">
            ${i === 0 ? `
                <label class="field-label">Contact Number</label>
                <input type="tel" name="guest_phone[]" placeholder="09XXXXXXXXX" required value="${escapeHTML(guest.phone || "")}">
            ` : ""}
        `;
        guestDetailsContainer.appendChild(row);
    }
    const firstName = guestDetailsContainer.querySelector('[name="guest_name[]"]');
    const firstPhone = guestDetailsContainer.querySelector('[name="guest_phone[]"]');
    legacyCustomerName.value = firstName?.value || "";
    legacyPhone.value = firstPhone?.value || "";
    guestDetailsContainer.querySelectorAll("input").forEach(input => {
        input.addEventListener("input", () => {
            legacyCustomerName.value = guestDetailsContainer.querySelector('[name="guest_name[]"]')?.value || "";
            legacyPhone.value = guestDetailsContainer.querySelector('[name="guest_phone[]"]')?.value || "";
        });
    });
}

function formatDate(value){

    if(!value){
        return "—";
    }

    const date =
        new Date(
            value + "T00:00:00"
        );

    return date.toLocaleDateString(
        "en-PH",
        {
            month:"short",
            day:"numeric",
            year:"numeric"
        }
    );

}


/* =========================================================
   TOTAL
   ========================================================= */

function updateTotal(){

    const pax = roomInput.value === "Couple Room" ? 2 : Math.max(1, Number(paxInput.value || 1));
    const total =
        (Number(basePrice) + Number(addonTotal)) * pax;

    priceInput.value =
        total.toFixed(2);

    document
    .getElementById(
        "summaryTotal"
    )
    .textContent =
        money(total);

}


/* =========================================================
   ADD-ON SUMMARY
   ========================================================= */

function updateAddonSummary(){

    const selected =
        document.querySelectorAll(
            ".addon.active"
        );

    let names = [];

    let stored = [];

    addonTotal = 0;


    selected.forEach(
        item => {

            const name =
                item.dataset.name;

            const addonPrice =
                Number(
                    item.dataset.price || 0
                );

            names.push(
                name +
                " (" +
                money(addonPrice) +
                ")"
            );

            stored.push(name);

            addonTotal +=
                addonPrice;

        }
    );


    addonsInput.value =
        stored.join(", ");


    document
    .getElementById(
        "summaryAddons"
    )
    .innerHTML =
        names.length
        ?
        names
        .map(
            name =>
            escapeHTML(name)
        )
        .join("<br>")
        :
        "None selected";


    updateTotal();

}


/* =========================================================
   RESET AFTER CATEGORY
   ========================================================= */

function resetServiceSelection(){

    serviceIdInput.value = "";
    serviceInput.value = "";
    durationInput.value = "";

    basePrice = 0;
    addonTotal = 0;

    selectedDuration = "";

    serviceDesc.innerHTML = "";

    durationBox.innerHTML =
        '<div class="empty-option">Select a service first.</div>';

    addonBox.innerHTML =
        '<div class="empty-option">Select a service first.</div>';

    document
    .getElementById(
        "summaryService"
    )
    .textContent = "—";

    document
    .getElementById(
        "summaryDuration"
    )
    .textContent = "—";

    document
    .getElementById(
        "summaryAddons"
    )
    .textContent =
        "None selected";

    addonsInput.value = "";

    updateTotal();

}


/* =========================================================
   CATEGORY
   ========================================================= */

document
.querySelectorAll(
    ".category"
)
.forEach(
    category => {

        category.addEventListener(
            "click",
            function(){

                document
                .querySelectorAll(
                    ".category"
                )
                .forEach(
                    item =>
                    item.classList.remove(
                        "active"
                    )
                );

                this.classList.add(
                    "active"
                );


                resetServiceSelection();


                serviceBox.innerHTML =
                    '<div class="empty-option">Loading services...</div>';


                fetch(
                    "../get_services_by_category.php?cat=" +
                    encodeURIComponent(
                        this.dataset.cat
                    )
                )

                .then(
                    response =>
                    response.json()
                )

                .then(
                    data => {

                        serviceBox.innerHTML =
                            "";


                        if(
                            !Array.isArray(data) ||
                            data.length === 0
                        ){

                            serviceBox.innerHTML =
                                '<div class="empty-option">No services available in this category.</div>';

                            return;

                        }


                        data.forEach(
                            service => {

                                const card =
                                    document.createElement(
                                        "div"
                                    );

                                card.className =
                                    "card service";

                                card.dataset.id =
                                    service.id;

                                card.dataset.name =
                                    service.service_name;

                                card.dataset.desc =
                                    service.description || "";

                                card.textContent =
                                    service.service_name;

                                serviceBox
                                .appendChild(
                                    card
                                );

                            }
                        );

                    }
                )

                .catch(
                    () => {

                        serviceBox.innerHTML =
                            '<div class="empty-option">Unable to load services.</div>';

                    }
                );

            }
        );

    }
);


/* =========================================================
   SERVICE
   ========================================================= */

document.addEventListener(
    "click",
    function(e){

        const selected =
            e.target.closest(
                ".service"
            );

        if(!selected){
            return;
        }


        document
        .querySelectorAll(
            ".service"
        )
        .forEach(
            item =>
            item.classList.remove(
                "active"
            )
        );


        selected.classList.add(
            "active"
        );


        serviceIdInput.value =
            selected.dataset.id;

        serviceInput.value =
            selected.dataset.name;


        document
        .getElementById(
            "summaryService"
        )
        .textContent =
            selected.dataset.name;


        if(
            selected.dataset.desc
        ){

            serviceDesc.textContent =
                selected.dataset.desc;

        }else{

            serviceDesc.innerHTML = "";

        }


        basePrice = 0;
        addonTotal = 0;

        durationInput.value = "";
        addonsInput.value = "";

        document
        .getElementById(
            "summaryDuration"
        )
        .textContent = "—";

        document
        .getElementById(
            "summaryAddons"
        )
        .textContent =
            "None selected";

        updateTotal();


        /* DURATION */

        durationBox.innerHTML =
            '<div class="empty-option">Loading durations...</div>';


        fetch(
            "../get_duration.php?id=" +
            encodeURIComponent(
                selected.dataset.id
            )
        )

        .then(
            response =>
            response.json()
        )

        .then(
            data => {

                durationBox.innerHTML =
                    "";


                if(
                    !Array.isArray(data) ||
                    data.length === 0
                ){

                    durationBox.innerHTML =
                        '<div class="empty-option">No duration available.</div>';

                    return;

                }


                data.forEach(
                    item => {

                        const card =
                            document.createElement(
                                "div"
                            );

                        card.className =
                            "card duration";

                        card.dataset.duration =
                            item.duration;

                        card.dataset.price =
                            item.price;

                        card.innerHTML =
                            escapeHTML(
                                item.duration
                            ) +
                            '<div class="small">' +
                            money(item.price) +
                            '</div>';

                        durationBox
                        .appendChild(
                            card
                        );

                    }
                );

            }
        );


        /* ADD-ONS */

        addonBox.innerHTML =
            '<div class="empty-option">Loading add-ons...</div>';


        fetch(
            "../get_addons.php"
        )

        .then(
            response =>
            response.json()
        )

        .then(
            data => {

                addonBox.innerHTML =
                    "";


                if(
                    !Array.isArray(data) ||
                    data.length === 0
                ){

                    addonBox.innerHTML =
                        '<div class="empty-option">No add-ons available.</div>';

                    return;

                }


                data.forEach(
                    addon => {

                        const card =
                            document.createElement(
                                "div"
                            );

                        card.className =
                            "card addon";

                        card.dataset.name =
                            addon.service_name;

                        card.dataset.price =
                            addon.price;


                        card.innerHTML =
                            escapeHTML(
                                addon.service_name
                            ) +

                            '<div class="small">' +

                            money(
                                addon.price
                            ) +

                            (
                                addon.description
                                ?
                                "<br>" +
                                escapeHTML(
                                    addon.description
                                )
                                :
                                ""
                            ) +

                            "</div>";


                        addonBox
                        .appendChild(
                            card
                        );

                    }
                );

            }
        );

    }
);


/* =========================================================
   DURATION
   ========================================================= */

document.addEventListener(
    "click",
    function(e){

        const selected =
            e.target.closest(
                ".duration"
            );

        if(!selected){
            return;
        }


        document
        .querySelectorAll(
            ".duration"
        )
        .forEach(
            item =>
            item.classList.remove(
                "active"
            )
        );


        selected.classList.add(
            "active"
        );


        selectedDuration =
            selected.dataset.duration;

        durationInput.value =
            selectedDuration;

        basePrice =
            Number(
                selected.dataset.price || 0
            );


        document
        .getElementById(
            "summaryDuration"
        )
        .textContent =
            selectedDuration;


        updateTotal();


        /*
         If date/time is already selected,
         refresh therapists because duration
         can affect therapist availability.
        */

        if (selectedDate && roomInput.value) {
            if (manualTimeInput.value.trim()) checkManualTime();
            else renderAvailableTimes();
        }

    }
);


/* =========================================================
   ADD-ONS
   ========================================================= */

document.addEventListener(
    "click",
    function(e){

        const selected =
            e.target.closest(
                ".addon"
            );

        if(!selected){
            return;
        }


        selected.classList.toggle(
            "active"
        );


        updateAddonSummary();

    }
);


/* =========================================================
   ROOM
   ========================================================= */

document.addEventListener(
    "click",
    function(e){

        const selected =
            e.target.closest(
                ".room"
            );

        if(!selected){
            return;
        }


        document
        .querySelectorAll(
            ".room"
        )
        .forEach(
            item =>
            item.classList.remove(
                "active"
            )
        );


        selected.classList.add(
            "active"
        );


        const room =
            selected.dataset.room;


        roomInput.value =
            room;


        document
        .getElementById(
            "summaryRoom"
        )
        .textContent =
            room;


        if(
            room === "Couple Room"
        ){

            paxInput.value = 2;

            paxInput.readOnly = true;

            paxNote.textContent =
                "Couple Room automatically uses 2 guests.";

        }else{

            paxInput.readOnly = false;

            if(
                Number(
                    paxInput.value
                ) < 1
            ){

                paxInput.value = 1;

            }

            paxNote.textContent =
                "Maximum of 4 guests.";

        }


        document
        .getElementById(
            "summaryPax"
        )
        .textContent =
            paxInput.value;

        renderGuestForms();
        updateTotal();
        manualTimeInput.value = "";
        if (selectedDate) {
            renderAvailableTimes();
        }

    }
);


/* =========================================================
   PAX
   ========================================================= */

paxInput.addEventListener(
    "input",
    function(){

        let value =
            Number(
                this.value || 1
            );


        if(value < 1){
            value = 1;
        }

        if(value > 4){
            value = 4;
        }


        this.value = value;
        renderGuestForms();


        document
        .getElementById(
            "summaryPax"
        )
        .textContent =
            value;
        updateTotal();

        if (selectedDate && roomInput.value === "Single Room") {
            manualTimeInput.value = "";
            renderAvailableTimes();
        }

    }
);


/* =========================================================
   DATE MINIMUM
   ========================================================= */

const today =
    new Date();

const year =
    today.getFullYear();

const month =
    String(
        today.getMonth() + 1
    ).padStart(2,"0");

const day =
    String(
        today.getDate()
    ).padStart(2,"0");

dateInput.min =
    year +
    "-" +
    month +
    "-" +
    day;

renderGuestForms();


/* =========================================================
   DATE / TIME — ADMIN WALK-IN AVAILABILITY
   Uses admin/check-slot.php (same folder as this page).
   ========================================================= */

async function renderAvailableTimes(){
    selectedDate = dateInput.value;
    bookingDateInput.value = selectedDate;
    selectedTime = "";
    bookingTimeInput.value = "";
    document.getElementById("summaryTime").textContent = "—";
    resetTherapist();
    const dayAvailability = document.getElementById("dayAvailability");
    if (dayAvailability) dayAvailability.innerHTML = "";
    if (!selectedDate || !roomInput.value) {
        timeAvailability.textContent = "Select a room and date to view availability.";
        return;
    }
    if (!selectedDuration || Number(basePrice) <= 0) {
        timeAvailability.textContent = "Select a service and duration to view availability.";
        return;
    }
    timeAvailability.textContent = "Checking available and occupied times for this date...";
    await loadDayAvailability();
}

async function loadDayAvailability(){
    const container = document.getElementById("dayAvailability");
    if (!container || !selectedDate || !roomInput.value || !selectedDuration) return;
    const date = selectedDate;
    const room = roomInput.value;
    const pax = room === "Couple Room" ? 2 : (paxInput.value || "1");
    const weekday = new Date(date + "T12:00:00").getDay();
    const openingHour = (weekday === 0 || weekday === 6) ? 13 : 15;
    const slots = [];
    // Show every 30-minute starting time from opening until 2:00 AM.
    for (let minute = openingHour * 60; minute <= 26 * 60; minute += 30) {
        let clockMinutes = minute % (24 * 60);
        const h = Math.floor(clockMinutes / 60);
        const m = clockMinutes % 60;
        const time24 = String(h).padStart(2,"0") + ":" + String(m).padStart(2,"0");
        if (isPastManualTime(date, time24)) continue;
        slots.push({time24, label: formatTime24(time24)});
    }
    if (!slots.length) {
        container.innerHTML = '<div class="empty-option">No future booking times remain for this operating date.</div>';
        timeAvailability.textContent = "No future times available for this date.";
        return;
    }
    container.innerHTML = '<div class="empty-option">Loading availability…</div>';
    try {
        const results = await Promise.all(slots.map(async slot => {
            const params = new URLSearchParams({date, time:slot.time24, room_type:room, duration:selectedDuration, pax:String(pax)});
            const response = await fetch("check-slot.php?" + params.toString());
            if (!response.ok) throw new Error("Availability endpoint returned HTTP " + response.status);
            return {slot, data: await response.json()};
        }));
        if (date !== dateInput.value || room !== roomInput.value) return;
        container.innerHTML = "";
        results.forEach(({slot,data}) => {
            const remaining = Number.isFinite(Number(data.remaining))
                ? Math.max(0, Number(data.remaining))
                : (data.available ? (room === "Single Room" ? 4 : 1) : 0);
            const requestedPax = room === "Couple Room" ? 2 : Number(pax || 1);
            const canBook = data.available === true || data.available === 1 || data.available === "1";
            const enoughSlots = room === "Couple Room" ? canBook : remaining >= requestedPax;
            const button = document.createElement("button");
            button.type = "button";
            button.className = "day-slot " + (enoughSlots ? "available" : "occupied");
            button.disabled = !enoughSlots;
            const slotStatus = room === "Single Room"
                ? (remaining + (remaining === 1 ? " slot left" : " slots left"))
                : (remaining > 0 ? "Couple Room available" : "Occupied / Full");
            button.innerHTML = `<strong>${escapeHTML(slot.label)}</strong><span class="slot-status">${escapeHTML(slotStatus)}</span>`;
            if (enoughSlots) button.addEventListener("click", () => {
                manualTimeInput.value = slot.label;
                checkManualTime();
            });
            container.appendChild(button);
        });
        timeAvailability.textContent = "Availability for " + formatDate(date) + ". Select an available time or type a custom time below.";
    } catch (error) {
        console.error("Day availability error:", error);
        container.innerHTML = '<div class="empty-option">Could not load daily availability. Check check-slot.php.</div>';
        timeAvailability.textContent = "Unable to load availability for this date.";
    }
}

async function checkManualTime(){
    selectedDate = dateInput.value;
    bookingDateInput.value = selectedDate;
    const time24 = parseManualTime(manualTimeInput.value);
    selectedTime = "";
    bookingTimeInput.value = "";
    document.getElementById("summaryTime").textContent = "—";
    resetTherapist();

    if (!selectedDate || !roomInput.value || !time24) {
        timeAvailability.textContent = "Enter a valid time, such as 2:17 PM.";
        return false;
    }
    if (!selectedDuration || Number(priceInput.value) <= 0) {
        timeAvailability.textContent = "Please select a service and duration first.";
        return false;
    }
    if (isPastManualTime(selectedDate, time24)) {
        timeAvailability.textContent = "That time has already passed. Choose a future time.";
        return false;
    }

    timeAvailability.textContent = "Checking availability...";
    const params = new URLSearchParams({
        date: selectedDate, time: time24, room_type: roomInput.value,
        duration: selectedDuration, pax: paxInput.value || "1"
    });
    try {
        const response = await fetch("check-slot.php?" + params.toString());
        if (!response.ok) throw new Error("Availability check failed");
        const data = await response.json();
        if (selectedDate !== dateInput.value || parseManualTime(manualTimeInput.value) !== time24) return false;
        if (isPastManualTime(selectedDate, time24)) {
            timeAvailability.textContent = "That time has already passed.";
            return false;
        }
        if (!data.available) {
            if (roomInput.value === "Single Room" && Number.isFinite(Number(data.remaining))) {
                const remaining = Math.max(0, Number(data.remaining));
                timeAvailability.textContent = remaining > 0
                    ? `Only ${remaining} slot(s) left at this time. Reduce the guest count or choose another time.`
                    : "No slots left at this time. Choose another time.";
            } else {
                timeAvailability.textContent = "That time is occupied or outside operating hours.";
            }
            return false;
        }
        if (roomInput.value === "Single Room" && Number.isFinite(Number(data.remaining))) {
            const remaining = Math.max(0, Number(data.remaining));
            timeAvailability.textContent = `${remaining} of 4 guest slots remain available at this time.`;
        }
        selectedTime = time24;
        bookingTimeInput.value = time24;
        document.getElementById("summaryTime").textContent = formatTime24(time24);
        timeAvailability.textContent = "Time is available.";
        loadTherapists();
        return true;
    } catch (error) {
        console.error("Walk-in availability error:", error);
        timeAvailability.textContent = "Unable to check availability. Please try again.";
        return false;
    }
}

manualTimeInput.addEventListener("change", checkManualTime);
manualTimeInput.addEventListener("blur", checkManualTime);

dateInput.addEventListener("change", function(){
    selectedDate = this.value;
    bookingDateInput.value = selectedDate;
    document.getElementById("summaryDate").textContent = formatDate(selectedDate);
    manualTimeInput.value = "";
    renderAvailableTimes();
});

/* =========================================================
   RESET THERAPIST
   ========================================================= */

function syncTherapistInputs() {
    const form = document.getElementById("bookingForm");
    form.querySelectorAll(".therapist-id-input").forEach(input => input.remove());

    selectedTherapistIds.forEach(id => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "therapist_ids[]";
        input.value = id;
        input.className = "therapist-id-input";
        form.appendChild(input);
    });

    therapistInput.value = selectedTherapistIds.length ? selectedTherapistIds[0] : "0";
}

function updateTherapistSummary() {
    const summary = document.getElementById("summaryTherapist");
    if (selectedTherapistIds.length === 0) {
        summary.textContent = "No Preference";
        therapistInput.value = "0";
        return;
    }

    const names = selectedTherapistIds.map(id => {
        const card = Array.from(therapistBox.querySelectorAll(".therapist[data-id]"))
            .find(item => String(item.dataset.id) === String(id));
        return card ? card.dataset.name : "Therapist";
    });
    summary.textContent = names.join(", ");
    therapistInput.value = selectedTherapistIds[0];
}

function resetTherapist(){
    selectedTherapistIds = [];
    therapistBox.innerHTML = `
        <div class="card therapist active"
             data-id="0"
             data-name="No Preference">
            No Preference
            <div class="small">Assign later</div>
        </div>
    `;
    syncTherapistInputs();
    updateTherapistSummary();
}

/* =========================================================
   LOAD THERAPISTS
   ========================================================= */

function loadTherapists(){

    // Each refresh invalidates any older in-flight request.
    const requestId = ++therapistLoadRequestId;

    if(
        !selectedDate ||
        !selectedTime
    ){

        return;

    }


    resetTherapist();


    therapistBox.insertAdjacentHTML(
        "beforeend",
        `
            <div
                class="empty-option"
                id="therapistLoading"
            >
                Checking therapist availability...
            </div>
        `
    );


    let url =
        "get_available_therapists.php?date=" +
        encodeURIComponent(
            selectedDate
        ) +
        "&time=" +
        encodeURIComponent(
            selectedTime
        );


    if(selectedDuration){
        url += "&duration=" + encodeURIComponent(selectedDuration);
    }
    url += "&room_type=" + encodeURIComponent(roomInput.value || "Single Room") +
           "&pax=" + encodeURIComponent(roomInput.value === "Couple Room" ? "2" : (paxInput.value || "1"));


    fetch(url)

    .then(
        response => {

            if(!response.ok){

                throw new Error(
                    "Unable to load therapists."
                );

            }

            return response.json();

        }
    )

    .then(
        data => {

            // Ignore stale responses; otherwise older requests can append a second list.
            if (requestId !== therapistLoadRequestId) return;

            const loading =
                document.getElementById(
                    "therapistLoading"
                );

            if(loading){

                loading.remove();

            }


            if (!Array.isArray(data)) {
                console.error("Unexpected therapist response:", data);
                therapistBox.insertAdjacentHTML("beforeend", '<div class="empty-option">Therapist data format is invalid. Check get_available_therapists.php.</div>');
                return;
            }


            if(
                data.length === 0
            ){

                therapistBox.insertAdjacentHTML(
                    "beforeend",
                    `
                        <div class="empty-option">
                            No therapists found.
                        </div>
                    `
                );

                return;

            }


            const activeTherapists = data.filter(therapist =>
                !therapist.status || String(therapist.status).toLowerCase() === 'active'
            );

            // Some versions of get_available_therapists.php can return the same
            // therapist more than once. Group by normalized name so the UI shows
            // one card per therapist, while preserving Available if any duplicate
            // record says the therapist is available.
            const therapistMap = new Map();
            activeTherapists.forEach(therapist => {
                const displayName = String(therapist.name || "Unnamed Therapist").trim();
                const key = displayName.toLowerCase().replace(/\s+/g, ' ');
                const rawAvailable = therapist.available;
                const isAvailable = rawAvailable === undefined
                    ? true
                    : !(rawAvailable === false || rawAvailable === 0 || rawAvailable === "0" || String(rawAvailable).toLowerCase() === "false");

                if (!therapistMap.has(key)) {
                    therapistMap.set(key, {
                        ...therapist,
                        name: displayName,
                        available: isAvailable
                    });
                } else if (isAvailable) {
                    therapistMap.get(key).available = true;
                }
            });

            Array.from(therapistMap.values()).forEach(
                therapist => {

                    const card =
                        document.createElement(
                            "div"
                        );


                    const isAvailable = therapist.available !== false;


                    card.className =
                        "card therapist";


                    if(!isAvailable){

                        card.classList.add(
                            "unavailable"
                        );

                    }


                    card.dataset.id =
                        therapist.id;


                    card.dataset.name =
                        therapist.name ||
                        "Unnamed Therapist";


                    /*
                     NAME ONLY.
                     No specialty / specialization.
                    */

                    card.innerHTML = `

                        ${escapeHTML(
                            therapist.name ||
                            "Unnamed Therapist"
                        )}

                        <div class="small">

                            ${
                                isAvailable
                                ?
                                "Available"
                                :
                                "Unavailable"
                            }

                        </div>

                    `;


                    therapistBox
                    .appendChild(
                        card
                    );

                }
            );

        }
    )

    .catch(
        error => {

            // Ignore errors from requests that are no longer current.
            if (requestId !== therapistLoadRequestId) return;

            const loading =
                document.getElementById(
                    "therapistLoading"
                );

            if(loading){

                loading.remove();

            }


            therapistBox.insertAdjacentHTML(
                "beforeend",
                `
                    <div class="empty-option">
                        Unable to check therapist availability.
                    </div>
                `
            );

            console.error(
                error
            );

        }
    );

}


/* =========================================================
   THERAPIST
   ========================================================= */

therapistBox.addEventListener("click", function(e) {
    const selected = e.target.closest(".therapist");
    if (!selected || !therapistBox.contains(selected)) return;
    if (selected.classList.contains("unavailable")) return;

    const id = String(selected.dataset.id || "0");
    if (id === "0") {
        selectedTherapistIds = [];
        therapistBox.querySelectorAll(".therapist").forEach(card => {
            card.classList.toggle("active", String(card.dataset.id) === "0");
        });
        syncTherapistInputs();
        updateTherapistSummary();
        return;
    }

    const existingIndex = selectedTherapistIds.indexOf(id);
    if (existingIndex !== -1) {
        selectedTherapistIds.splice(existingIndex, 1);
        selected.classList.remove("active");
        if (selectedTherapistIds.length === 0) {
            const noPreference = therapistBox.querySelector('.therapist[data-id="0"]');
            if (noPreference) noPreference.classList.add("active");
        }
        syncTherapistInputs();
        updateTherapistSummary();
        return;
    }

    const expectedCount = roomInput.value === "Couple Room"
        ? 2
        : Math.max(1, Math.min(4, Number(paxInput.value || 1)));
    if (selectedTherapistIds.length >= expectedCount) {
        alert("You can select up to " + expectedCount + " therapist(s) for this booking.");
        return;
    }

    selectedTherapistIds.push(id);
    selected.classList.add("active");
    const noPreference = therapistBox.querySelector('.therapist[data-id="0"]');
    if (noPreference) noPreference.classList.remove("active");
    syncTherapistInputs();
    updateTherapistSummary();
});

/* =========================================================
   FORM VALIDATION
   ========================================================= */

document
.getElementById(
    "bookingForm"
)
.addEventListener(
    "submit",
    function(e){

        if(
            !serviceIdInput.value ||
            !serviceInput.value
        ){

            e.preventDefault();

            alert(
                "Please select a service."
            );

            return;

        }


        if(
            !durationInput.value ||
            Number(priceInput.value) <= 0
        ){

            e.preventDefault();

            alert(
                "Please select a duration."
            );

            return;

        }


        if(
            !roomInput.value
        ){

            e.preventDefault();

            alert(
                "Please select a room."
            );

            return;

        }


        const expectedTherapists = roomInput.value === "Couple Room"
            ? 2
            : Math.max(1, Math.min(4, Number(paxInput.value || 1)));
        if (selectedTherapistIds.length > 0 && selectedTherapistIds.length !== expectedTherapists) {
            e.preventDefault();
            alert("Select exactly " + expectedTherapists + " therapists for the guests, or choose No Preference.");
            return;
        }

        if(
            !bookingDateInput.value
        ){

            e.preventDefault();

            alert(
                "Please select an appointment date."
            );

            return;

        }


        if(
            !bookingTimeInput.value
        ){

            e.preventDefault();

            alert(
                "Please select an available time."
            );

            return;

        }

    }
);


document.getElementById("bookingForm").addEventListener("submit", async function(event) {
    if (!selectedTime || bookingTimeInput.value !== selectedTime) {
        event.preventDefault();
        const valid = await checkManualTime();
        if (!valid) { manualTimeInput.focus(); return; }
        this.requestSubmit();
        return;
    }
    if (!this.reportValidity()) {
        event.preventDefault();
        return;
    }
    const names = Array.from(this.querySelectorAll('[name="guest_name[]"]'));
    const mainGuestPhone = this.querySelector('[name="guest_phone[]"]');
    if (names.length !== Number(paxInput.value) || names.some(el => !el.value.trim()) || !mainGuestPhone?.value.trim()) {
        event.preventDefault();
        alert("Please complete each guest's name and the main guest's contact number.");
    }
});

</script>


</body>
</html>