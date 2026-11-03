<?php

session_start();

include 'includes/db.php';


/* =========================================================
   LANDING PAGE SERVICES
   ========================================================= */

$landingData = [

    'signature' => [],

    'package' => [],

    'popular' => []

];

$landingQ = mysqli_query($conn, "

    SELECT 
        lps.id AS landing_id,
        lps.service_id,
        lps.section,
        lps.sort_order,
        s.service_name,
        s.description,
        s.category,
        (
            SELECT MIN(sd.price)
            FROM service_durations sd
            WHERE sd.service_id = s.id
        ) AS price

    FROM landing_page_services lps

    INNER JOIN services s ON s.id = lps.service_id

    WHERE lps.section IN ('signature', 'package', 'popular')

    AND lps.status = 'shown'

    ORDER BY lps.section, lps.sort_order ASC, lps.id ASC

");


if ($landingQ) {

    while ($row = mysqli_fetch_assoc($landingQ)) {

        $landingData[$row['section']][] = $row;

    }

}


/* =========================================================
   SIGNATURE SERVICE DETAILS
   ========================================================= */

$signatureDetails = [

    'Swedish Massage' => [

        'description' =>
            'Relaxing full body massage using light to medium pressure.',

        'best_for' =>
            'Stress relief, body pain, relaxation',

        'duration' =>
            '1–2 hrs',

        'price' =>
            '₱600'

    ],

    'MIZPAH Signature' => [

        'description' =>
            'Combination of Swedish, Shiatsu & deep tissue massage.',

        'best_for' =>
            'Full body recovery and premium relaxation',

        'duration' =>
            '1–2 hrs',

        'price' =>
            '₱750'

    ],

    'Mizpah Signature' => [

        'description' =>
            'Combination of Swedish, Shiatsu & deep tissue massage.',

        'best_for' =>
            'Full body recovery and premium relaxation',

        'duration' =>
            '1–2 hrs',

        'price' =>
            '₱750'

    ],

    'Lymphatic Massage' => [

        'description' =>
            'Detox massage that improves circulation & reduces swelling.',

        'best_for' =>
            'Wellness recovery',

        'duration' =>
            '1–2 hrs',

        'price' =>
            '₱850'

    ]

];


/* =========================================================
   PACKAGE DETAILS
   ========================================================= */

$packageDetails = [

    'Bronze Package' => [

        'items' => [

            'Swedish Massage',

            'Body Scrub',

            'Hot Stone',

            'Milk Mask',

            'Korean Face Mask',

            'Foot Mask'

        ],

        'duration' =>
            '1 hr 45 mins',

        'price' =>
            '₱1,600',

        'class' =>
            'bronze'

    ],

    'Silver Package' => [

        'items' => [

            'MIZPAH Signature Massage',

            'Body Scrub',

            'Hot Stone',

            'Milk Mask',

            'Korean Face Mask',

            'Foot Mask'

        ],

        'duration' =>
            '1 hr 45 mins',

        'price' =>
            '₱1,800',

        'class' =>
            'silver'

    ],

    'Gold Package' => [

        'items' => [

            'MIZPAH Signature Massage',

            'Body Scrub',

            'Hot Stone',

            'Head or Foot Massage',

            'Milk Mask',

            'Korean Face Mask',

            'Foot Mask'

        ],

        'duration' =>
            '2 hrs',

        'price' =>
            '₱2,000',

        'class' =>
            'gold'

    ]

];


/* =========================================================
   POPULAR DETAILS
   ========================================================= */

$popularDetails = [

    'Mizpah Signature' => [

        'tag' =>
            'Signature',

        'image' =>
            'assets/images/popular/signature.jpg',

        'description' =>
            'Our exclusive blend for ultimate relaxation',

        'price' =>
            '₱750'

    ],

    'MIZPAH Signature' => [

        'tag' =>
            'Signature',

        'image' =>
            'assets/images/popular/signature.jpg',

        'description' =>
            'Our exclusive blend for ultimate relaxation',

        'price' =>
            '₱750'

    ],

    'Hot Stone Combo' => [

        'tag' =>
            'Popular',

        'image' =>
            'assets/images/popular/hotstone.jpg',

        'description' =>
            'Melt away tension with heated basalt stones',

        'price' =>
            '₱1,000'

    ],

    'Quick Escape' => [

        'tag' =>
            'Add-On',

        'image' =>
            'assets/images/popular/quick.jpg',

        'description' =>
            '30-min relief for busy schedules',

        'price' =>
            '₱350'

    ]

];

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Mizpah Wellness Spa</title>

<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>


/* =========================================================
   HERO BOOKING IMPROVEMENT
   ========================================================= */

.hero-booking-actions{

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    gap:12px;

    margin-top:25px;

}

.hero-book-btn{

    min-width:190px;

    text-align:center;

    padding:13px 28px;

    box-shadow:
        0 10px 30px
        rgba(214,194,156,.15);

}

.account-prompt{

    display:flex;

    align-items:center;

    justify-content:center;

    flex-wrap:wrap;

    gap:6px;

    color:#aaa;

    font-size:11px;

}

.account-prompt a{

    color:#D6C29C;

    text-decoration:none;

    font-weight:600;

    transition:.2s;

}

.account-prompt a:hover{

    color:#fff;

    text-decoration:underline;

}


/* =========================================================
   POPUP MODAL
   ========================================================= */

.modal{

    display:none;

    position:fixed;

    inset:0;

    background:
        rgba(0,0,0,.75);

    justify-content:center;

    align-items:center;

    z-index:9999;

    padding:20px;

}

.modal-box{

    width:470px;

    max-width:100%;

    background:#161616;

    border:
        1px solid
        rgba(255,255,255,.08);

    border-radius:18px;

    padding:25px;

    color:#fff;

    position:relative;

    animation:
        pop .25s ease;

    max-height:90vh;

    overflow:auto;

}

@keyframes pop{

    from{

        transform:
            scale(.9);

        opacity:0;

    }

    to{

        transform:
            scale(1);

        opacity:1;

    }

}

.close{

    position:absolute;

    top:12px;

    right:16px;

    font-size:28px;

    cursor:pointer;

    color:#D6C29C;

}

.modal-box h2{

    margin-bottom:10px;

    color:#D6C29C;

}

.modal-box p{

    margin-bottom:10px;

    line-height:1.6;

    color:#ddd;

}

.modal-box ul{

    padding-left:18px;

    margin:10px 0;

}

.modal-box li{

    margin-bottom:8px;

    color:#ddd;

}

.popup-book{

    display:inline-block;

    margin-top:15px;

    padding:10px 18px;

    background:#D6C29C;

    color:#111;

    border-radius:10px;

    font-weight:600;

    text-decoration:none;

}


/* =========================================================
   CARD HOVER
   ========================================================= */

.service-card,
.package-card,
.popular-card{

    cursor:pointer;

    transition:.25s;

}

.service-card:hover,
.package-card:hover,
.popular-card:hover{

    transform:
        translateY(-6px);

    box-shadow:
        0 10px 30px
        rgba(214,194,156,.15);

}


/* =========================================================
   POPULAR CHOICES
   ========================================================= */

.popular-grid{

    align-items:stretch;

}

.popular-card{

    display:flex;

    flex-direction:column;

    height:100%;

}

.popular-card img{

    width:100%;

    height:220px;

    object-fit:cover;

    display:block;

    border-radius:12px;

}

.popular-card h3{

    margin-top:15px;

    min-height:30px;

}

.popular-card p{

    min-height:48px;

}

.popular-card strong{

    display:block;

    margin-top:auto;

    margin-bottom:12px;

}

.popular-card .btn-small{

    align-self:center;

}


/* =========================================================
   FOOTER
   ========================================================= */

.footer{

    background:#0d0d0d;

    border-top:
        1px solid
        rgba(214,194,156,.18);

    padding:
        60px 8%
        20px;

    margin-top:60px;

}

.footer-grid{

    max-width:1200px;

    margin:auto;

    display:grid;

    grid-template-columns:
        2fr 1fr 1.3fr;

    gap:70px;

    padding-bottom:45px;

}

.footer h3{

    color:#D6C29C;

    font-family:
        'Playfair Display',
        serif;

    font-size:25px;

    margin:
        0 0 12px;

}

.footer h4{

    color:#D6C29C;

    font-size:16px;

    margin:
        0 0 18px;

    text-transform:uppercase;

    letter-spacing:1px;

}

.footer p{

    color:#aaa;

    line-height:1.7;

    margin:7px 0;

}

.footer-brand p{

    max-width:420px;

}

.footer-text{

    margin-top:
        15px !important;

}

.footer-links{

    display:flex;

    flex-direction:column;

    align-items:flex-start;

}

.footer-links a{

    color:#aaa;

    text-decoration:none;

    margin-bottom:10px;

    transition:.2s;

}

.footer-links a:hover{

    color:#D6C29C;

}

.footer-contact p{

    margin-bottom:12px;

}

.footer-bottom{

    max-width:1200px;

    margin:auto;

    padding-top:20px;

    border-top:
        1px solid
        rgba(255,255,255,.08);

    text-align:center;

}

.footer-bottom p{

    margin:0;

    font-size:13px;

    color:#777;

}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width:768px){

    .hero-booking-actions{

        width:100%;

    }

    .hero-book-btn{

        min-width:180px;

    }

    .account-prompt{

        font-size:10px;

    }

    .footer{

        padding:
            45px 25px
            20px;

    }

    .footer-grid{

        grid-template-columns:1fr;

        gap:35px;

    }

    .footer-brand p{

        max-width:100%;

    }

}


/* HERO INFO SVG ICONS - icon-only update */
.hero-info .info-box {
    display: flex;
    align-items: center;
    gap: 8px;
}

.hero-info .info-icon {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
    color: #D6C29C;
}



/* =========================================================
   HERO BACKGROUND FIT + CENTERING FIX
   Keeps the existing content and page sections unchanged.
   ========================================================= */
.hero {
    width: 100%;
    min-height: 100vh;
    min-height: 100svh;
    box-sizing: border-box;
    background-size: cover !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero .hero-content {
    width: 100%;
    max-width: 1200px;
    box-sizing: border-box;
    margin-left: auto;
    margin-right: auto;
    text-align: center;
}

@media (max-height: 850px) and (min-width: 769px) {
    .hero {
        padding-top: 95px;
        padding-bottom: 28px;
    }

    .hero .hero-logo {
        max-height: 155px;
        width: auto;
        max-width: min(80%, 300px);
        object-fit: contain;
    }

    .hero .hero-title-white,
    .hero .hero-title-gold {
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .hero .hero-booking-actions {
        margin-top: 18px;
        gap: 9px;
    }
}

@media (max-width: 768px) {
    .hero {
        min-height: 100svh;
        padding: 100px 20px 35px;
        background-position: center center !important;
    }

    .hero .hero-content {
        padding: 0;
    }

    .hero .hero-logo {
        max-width: 75%;
        height: auto;
        object-fit: contain;
    }
}

</style>

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="site-header">

    <div class="logo">

        Mizpah Wellness Spa

    </div>


    <nav>

        <a href="index.php">

            Home

        </a>

        <a href="services.php">

            Services

        </a>

        <a href="therapist.php">

            Therapists

        </a>

        <a href="mizpah_amenities_3d/index.html">

            Virtual Tour
            
        </a>


    </nav>


    <a
        href="login.php"
        class="btn-primary"
    >

        Login

    </a>

</header>


<!-- =========================================================
     HERO
     ========================================================= -->

<section class="hero">

    <div class="hero-content">


        <img
            src="assets/images/logo.png"
            class="hero-logo"
            alt="Mizpah Wellness Spa"
        >


        <h1 class="hero-title-white">

            Exquisite Comfort

        </h1>


        <h2 class="hero-title-gold">

            Exceptional Care

        </h2>


        <p class="hero-text">

            Kawit's premier wellness sanctuary —
            where relaxation meets luxury experience.

        </p>


        <div class="hero-booking-actions">

            <a
                href="booking-guest.php"
                class="btn-primary hero-book-btn"
            >

                Book as Guest

            </a>


            <div class="account-prompt">

                <span>

                    Want your own account?

                </span>

                <a href="login.php">

                    Login or Create Account

                </a>

            </div>

        </div>


        <div class="hero-info">

            <div class="info-box">
                <svg class="info-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2
                    19.79 19.79 0 0 1-8.63-3.07
                    19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1
                    2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1
                    2 1.72c.12.96.35 1.9.69 2.8a2 2 0 0 1
                    -.45 2.11L8.08 9.91a16 16 0 0 0 6
                    6l1.27-1.27a2 2 0 0 1 2.11-.45
                    c.9.34 1.84.57 2.8.69A2 2 0 0 1 22 16.92z"
                    fill="none" stroke="currentColor"
                    stroke-width="1.7" stroke-linecap="round"
                    stroke-linejoin="round"/>
                </svg>
                <span>0936-995-0038</span>
            </div>

            <div class="info-box">
                <svg class="info-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"
                    fill="none" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M12 7v5l3 2"
                    fill="none" stroke="currentColor" stroke-width="1.7"
                    stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Mon–Fri 3PM–3AM · Sat–Sun 1PM–3AM</span>
            </div>

            <div class="info-box">
                <svg class="info-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z"
                    fill="none" stroke="currentColor" stroke-width="1.7"
                    stroke-linejoin="round"/>
                    <circle cx="12" cy="10" r="2.5"
                    fill="none" stroke="currentColor" stroke-width="1.7"/>
                </svg>
                <span>Kawit, Cavite</span>
            </div>

        </div>


    </div>

</section>


<!-- =========================================================
     SIGNATURE SERVICES
     ========================================================= -->

<section class="section">

    <h2>

        Mizpah Signature Services

    </h2>


    <div class="service-grid">


        <?php if (!empty($landingData['signature'])): ?>


            <?php foreach ($landingData['signature'] as $service): ?>


                <?php

                $name =
                    $service['service_name'];


                if (
                    isset(
                        $signatureDetails[$name]
                    )
                ) {

                    $detail =
                        $signatureDetails[$name];


                    $description =
                        $detail['description'];

                    $bestFor =
                        $detail['best_for'];

                    $duration =
                        $detail['duration'];

                    $price =
                        $detail['price'];

                } else {

                    $description =
                        !empty(
                            $service['description']
                        )
                        ?
                        $service['description']
                        :
                        'A relaxing wellness service from Mizpah Wellness Spa.';


                    $bestFor =
                        'Relaxation and wellness';


                    $duration =
                        'Based on selected service';


                    $price =
                        !empty(
                            $service['price']
                        )
                        ?
                        '₱' .
                        number_format(
                            $service['price'],
                            2
                        )
                        :
                        'Contact us';

                }


                $isSignature = (

                    strtolower($name) ===
                    'mizpah signature'

                    ||

                    strtolower($name) ===
                    'mispah signature'

                    ||

                    strtolower($name) ===
                    'mzpah signature'

                );


                $modalType =
                    'service_' .
                    (int)$service['landing_id'];

                ?>


                <div
                    class="service-card <?= $isSignature ? 'featured' : '' ?>"
                    onclick="openModal('<?= $modalType ?>')"
                >


                    <?php if ($isSignature): ?>

                        <div class="badge">

                            Recommended

                        </div>

                    <?php endif; ?>


                    <h3>

                        <?= htmlspecialchars($name) ?>

                    </h3>


                    <p class="desc">

                        <?= htmlspecialchars($description) ?>

                    </p>


                    <p class="time">

                        <?= htmlspecialchars($duration) ?>

                    </p>


                    <p class="price">

                        <?= htmlspecialchars($price) ?>

                    </p>


                    <!--
                        BOOK NOW REMOVED HERE ONLY.
                        CLICKING THE CARD OPENS THE MODAL.
                    -->


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <p style="color:#aaa;">

                No signature services selected yet.

            </p>


        <?php endif; ?>


    </div>

</section>


<!-- =========================================================
     PACKAGES
     ========================================================= -->

<section class="section">

    <h2>

        Mizpah Packages

    </h2>


    <div class="package-grid">


        <?php if (!empty($landingData['package'])): ?>


            <?php foreach ($landingData['package'] as $package): ?>


                <?php

                $name =
                    $package['service_name'];


                if (
                    isset(
                        $packageDetails[$name]
                    )
                ) {

                    $detail =
                        $packageDetails[$name];


                    $items =
                        $detail['items'];

                    $duration =
                        $detail['duration'];

                    $price =
                        $detail['price'];

                    $packageClass =
                        $detail['class'];

                } else {

                    $items = [];


                    if (
                        !empty(
                            $package['description']
                        )
                    ) {

                        $items =
                            array_filter(
                                array_map(
                                    'trim',
                                    preg_split(
                                        '/[,;\n]+/',
                                        $package['description']
                                    )
                                )
                            );

                    }


                    $duration =
                        'Based on selected package';


                    $price =
                        !empty(
                            $package['price']
                        )
                        ?
                        '₱' .
                        number_format(
                            $package['price'],
                            2
                        )
                        :
                        'Contact us';


                    $packageClass =
                        'bronze';

                }


                $modalType =
                    'package_' .
                    (int)$package['landing_id'];

                ?>


                <div
                    class="package-card <?= htmlspecialchars($packageClass) ?>"
                    onclick="openModal('<?= $modalType ?>')"
                >


                    <h3>

                        <?= htmlspecialchars($name) ?>

                    </h3>


                    <ul class="package-list">


                        <?php if (!empty($items)): ?>


                            <?php foreach ($items as $item): ?>


                                <li>

                                    <?= htmlspecialchars($item) ?>

                                </li>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <li>

                                <?= htmlspecialchars(
                                    !empty(
                                        $package['description']
                                    )
                                    ?
                                    $package['description']
                                    :
                                    'Package details available upon booking.'
                                ) ?>

                            </li>


                        <?php endif; ?>


                    </ul>


                    <strong>

                        <?= htmlspecialchars($price) ?>

                    </strong>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <p style="color:#aaa;">

                No packages selected yet.

            </p>


        <?php endif; ?>


    </div>

</section>


<!-- =========================================================
     POPULAR CHOICES
     ========================================================= -->

<section class="section">

    <h2>

        Popular Choices

    </h2>


    <p class="subtitle">

        Our Guests' Favourites

    </p>


    <div class="popular-grid">


        <?php if (!empty($landingData['popular'])): ?>


            <?php foreach ($landingData['popular'] as $popular): ?>


                <?php

                $name =
                    $popular['service_name'];


                if (
                    isset(
                        $popularDetails[$name]
                    )
                ) {

                    $detail =
                        $popularDetails[$name];


                    $tag =
                        $detail['tag'];

                    $image =
                        $detail['image'];

                    $description =
                        $detail['description'];

                    $price =
                        $detail['price'];

                } else {

                    $tag =
                        'Popular';


                    if (
                        stripos(
                            $name,
                            'Hot Stone'
                        ) !== false
                    ) {

                        $image =
                            'assets/images/popular/hotstone.jpg';

                    } elseif (
                        stripos(
                            $name,
                            'Quick Escape'
                        ) !== false
                    ) {

                        $image =
                            'assets/images/popular/quick.jpg';

                    } elseif (
                        stripos(
                            $name,
                            'Mizpah Signature'
                        ) !== false
                    ) {

                        $image =
                            'assets/images/popular/signature.jpg';

                    } elseif (
                        stripos(
                            $name,
                            'Swedish Massage'
                        ) !== false
                    ) {

                        $image =
                            'assets/images/popular/signature.jpg';

                    } elseif (
                        stripos(
                            $name,
                            'Lymphatic Massage'
                        ) !== false
                    ) {

                        $image =
                            'assets/images/popular/signature.jpg';

                    } else {

                        $image =
                            'assets/images/popular/signature.jpg';

                    }


                    $description =
                        !empty(
                            $popular['description']
                        )
                        ?
                        $popular['description']
                        :
                        'A popular choice from Mizpah Wellness Spa.';


                    $price =
                        !empty(
                            $popular['price']
                        )
                        ?
                        '₱' .
                        number_format(
                            $popular['price'],
                            2
                        )
                        :
                        'Contact us';

                }


                $modalType =
                    'popular_' .
                    (int)$popular['landing_id'];

                ?>


                <div
                    class="popular-card"
                    onclick="openModal('<?= $modalType ?>')"
                >


                    <span class="tag">

                        <?= htmlspecialchars($tag) ?>

                    </span>


                    <img
                        src="<?= htmlspecialchars($image) ?>"
                        alt="<?= htmlspecialchars($name) ?>"
                    >


                    <h3>

                        <?= htmlspecialchars($name) ?>

                    </h3>


                    <p>

                        <?= htmlspecialchars($description) ?>

                    </p>


                    <strong>

                        <?= htmlspecialchars($price) ?>

                    </strong>


                    <a
                        href="booking-guest.php"
                        class="btn-small"
                        onclick="event.stopPropagation();"
                    >

                        Book Now

                    </a>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <p style="color:#aaa;">

                No popular choices selected yet.

            </p>


        <?php endif; ?>


    </div>

</section>


<!-- =========================================================
     RATINGS
     ========================================================= -->

<div class="ratings-section">


    <h2>

        Customer Reviews

    </h2>


    <div class="rating-summary">

        <div class="big-rating">

            4.8

        </div>

        <p>

            Based on customer feedback

        </p>

    </div>


    <div
        class="ratings-grid"
        id="ratingsBox"
    >

        Loading reviews...

    </div>


    <hr
        style="
            margin:40px 0;
            border:1px solid #222;
        "
    >


    <div class="rating-form">

        <h3>

            Leave a Review

        </h3>


        <form
            action="submit_rating.php"
            method="POST"
        >


            <input
                type="text"
                name="name"
                placeholder="Your Name"
                required
            >


            <select
                name="rating"
                required
            >

                <option value="">

                    Rating

                </option>

                <option value="5">

                    ★★★★★

                </option>

                <option value="4">

                    ★★★★

                </option>

                <option value="3">

                    ★★★

                </option>

                <option value="2">

                    ★★

                </option>

                <option value="1">

                    ★

                </option>

            </select>


            <textarea
                name="message"
                placeholder="Your review..."
                required
            ></textarea>


            <button type="submit">

                Submit Review

            </button>


        </form>

    </div>


</div>


<!-- =========================================================
     IMPROVED CTA
     ========================================================= -->

<section class="section">


    <h2>

        Ready to Relax?

    </h2>


    <p
        style="
            color:#888;
            margin:8px auto 20px;
            max-width:520px;
            text-align:center;
        "
    >

        Book instantly as a guest, or create an account
        to manage your appointments and wellness profile.

    </p>


    <div class="hero-booking-actions">


        <a
            href="booking-guest.php"
            class="btn-primary hero-book-btn"
        >

            Book as Guest

        </a>


        <div class="account-prompt">

            <span>

                Want your own account?

            </span>

            <a href="login.php">

                Login or Create Account

            </a>

        </div>


    </div>


</section>


<!-- =========================================================
     FOOTER
     ========================================================= -->

<?php

$setQ = mysqli_query(
    $conn,
    "SELECT * FROM settings LIMIT 1"
);

$set =
    mysqli_fetch_assoc($setQ);

?>


<footer class="footer">


    <div class="footer-grid">


        <div class="footer-brand">


            <h3>

                <?= htmlspecialchars(
                    $set['site_name'] ?? 'Mizpah Wellness Spa'
                ) ?>

            </h3>


            <p>

                <?= htmlspecialchars(
                    $set['tagline'] ?? ''
                ) ?>

            </p>


            <p class="footer-text">

                <?= htmlspecialchars(
                    $set['footer_text'] ?? ''
                ) ?>

            </p>


        </div>


        <div class="footer-links">


            <h4>

                Quick Links

            </h4>


            <a href="index.php">

                Home

            </a>


            <a href="services.php">

                Services

            </a>


            <a href="therapist.php">

                Therapists

            </a>


            <a href="#virtual-tour">

                Virtual Tour

            </a>


        </div>


        <div class="footer-contact">


            <h4>

                Contact Us

            </h4>


            <p>

                <?= htmlspecialchars(
                    $set['contact_number'] ?? ''
                ) ?>

            </p>


            <p>

                <?= htmlspecialchars(
                    $set['address'] ?? ''
                ) ?>

            </p>


        </div>


    </div>


    <div class="footer-bottom">

        <p>

            <?= htmlspecialchars(
                $set['copyright_text'] ?? ''
            ) ?>

        </p>

    </div>


</footer>


<!-- =========================================================
     MODAL
     ========================================================= -->

<div
    class="modal"
    id="modal"
>


    <div class="modal-box">


        <span
            class="close"
            onclick="closeModal()"
        >

            &times;

        </span>


        <div id="modalContent"></div>


    </div>


</div>


<script>


/* =========================================================
   HEADER SCROLL
   ========================================================= */

window.addEventListener(

    "scroll",

    function(){

        document
        .querySelector(".site-header")
        .classList
        .toggle(

            "scrolled",

            window.scrollY > 50

        );

    }

);


/* =========================================================
   LOAD RATINGS
   ========================================================= */

function loadRatings(){

    fetch("fetch_ratings.php")

    .then(

        res => res.text()

    )

    .then(

        data => {

            document
            .getElementById(
                "ratingsBox"
            )
            .innerHTML = data;

        }

    )

    .catch(

        error => {

            console.error(

                "Ratings error:",

                error

            );

        }

    );

}


loadRatings();


setInterval(

    loadRatings,

    3000

);


/* =========================================================
   DATA
   ========================================================= */

const landingServices =

<?= json_encode(

    $landingData['signature'],

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

) ?>;


const landingPackages =

<?= json_encode(

    $landingData['package'],

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

) ?>;


const landingPopular =

<?= json_encode(

    $landingData['popular'],

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

) ?>;


const signatureDetails =

<?= json_encode(

    $signatureDetails,

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

) ?>;


const packageDetails =

<?= json_encode(

    $packageDetails,

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

) ?>;


const popularDetails =

<?= json_encode(

    $popularDetails,

    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES

) ?>;


/* =========================================================
   FORMAT PRICE
   ========================================================= */

function formatPrice(price){

    if(

        price === null ||

        price === undefined ||

        price === ""

    ){

        return "Contact us";

    }


    return "₱" +

        Number(price)

        .toLocaleString(

            "en-PH",

            {

                minimumFractionDigits:2,

                maximumFractionDigits:2

            }

        );

}


/* =========================================================
   ESCAPE HTML
   ========================================================= */

function escapeHTML(value){

    if(

        value === null ||

        value === undefined

    ){

        return "";

    }


    return String(value)

        .replace(

            /&/g,

            "&amp;"

        )

        .replace(

            /</g,

            "&lt;"

        )

        .replace(

            />/g,

            "&gt;"

        )

        .replace(

            /"/g,

            "&quot;"

        )

        .replace(

            /'/g,

            "&#039;"

        );

}


/* =========================================================
   OPEN MODAL
   ========================================================= */

function openModal(type){

    let html = "";


    /* =====================================================
       SERVICE
       ===================================================== */

    if(

        type.startsWith(
            "service_"
        )

    ){

        let id =

            parseInt(

                type.replace(

                    "service_",

                    ""

                )

            );


        let service =

            landingServices.find(

                item =>

                parseInt(

                    item.landing_id

                ) === id

            );


        if(service){

            let detail =

                signatureDetails[
                    service.service_name
                ];


            let description =

                detail

                ?

                detail.description

                :

                (

                    service.description ||

                    "A relaxing wellness service from Mizpah Wellness Spa."

                );


            let bestFor =

                detail

                ?

                detail.best_for

                :

                "Relaxation and wellness";


            let duration =

                detail

                ?

                detail.duration

                :

                "Based on selected service";


            let price =

                detail

                ?

                detail.price

                :

                formatPrice(

                    service.price

                );


            html = `

                <h2>

                    ${escapeHTML(
                        service.service_name
                    )}

                </h2>

                <p>

                    ${escapeHTML(
                        description
                    )}

                </p>

                <p>

                    <b>Best for:</b>

                    ${escapeHTML(
                        bestFor
                    )}

                </p>

                <p>

                    <b>Duration:</b>

                    ${escapeHTML(
                        duration
                    )}

                </p>

                <p>

                    <b>Price:</b>

                    ${escapeHTML(
                        price
                    )}

                </p>

                <a
                    href="booking-guest.php?service_id=${encodeURIComponent(service.service_id)}"
                    class="popup-book"
                >

                    Book This Service

                </a>

            `;

        }

    }


    /* =====================================================
       PACKAGE
       ===================================================== */

    if(

        type.startsWith(
            "package_"
        )

    ){

        let id =

            parseInt(

                type.replace(

                    "package_",

                    ""

                )

            );


        let packageItem =

            landingPackages.find(

                item =>

                parseInt(

                    item.landing_id

                ) === id

            );


        if(packageItem){

            let detail =

                packageDetails[
                    packageItem.service_name
                ];


            let items = [];


            let duration =

                "Based on selected package";


            let price =

                formatPrice(

                    packageItem.price

                );


            if(detail){

                items =

                    detail.items;

                duration =

                    detail.duration;

                price =

                    detail.price;

            }else{

                if(

                    packageItem.description

                ){

                    items =

                        packageItem
                        .description
                        .split(

                            /[,;\n]+/

                        )
                        .map(

                            item =>
                            item.trim()

                        )
                        .filter(

                            item =>
                            item !== ""

                        );

                }

            }


            let listHTML = "";


            if(

                items.length > 0

            ){

                items.forEach(

                    item => {

                        listHTML += `

                            <li>

                                ${escapeHTML(item)}

                            </li>

                        `;

                    }

                );

            }else{

                listHTML = `

                    <li>

                        Package details available upon booking.

                    </li>

                `;

            }


            html = `

                <h2>

                    ${escapeHTML(
                        packageItem.service_name
                    )}

                </h2>

                <ul>

                    ${listHTML}

                </ul>

                <p>

                    <b>Duration:</b>

                    ${escapeHTML(
                        duration
                    )}

                </p>

                <p>

                    <b>Price:</b>

                    ${escapeHTML(
                        price
                    )}

                </p>

                <a
                    href="booking-guest.php?service_id=${encodeURIComponent(packageItem.service_id)}"
                    class="popup-book"
                >

                    Book This Package

                </a>

            `;

        }

    }


    /* =====================================================
       POPULAR
       ===================================================== */

    if(

        type.startsWith(
            "popular_"
        )

    ){

        let id =

            parseInt(

                type.replace(

                    "popular_",

                    ""

                )

            );


        let popular =

            landingPopular.find(

                item =>

                parseInt(

                    item.landing_id

                ) === id

            );


        if(popular){

            let detail =

                popularDetails[
                    popular.service_name
                ];


            let description =

                detail

                ?

                detail.description

                :

                (

                    popular.description ||

                    "A popular choice from Mizpah Wellness Spa."

                );


            let price =

                detail

                ?

                detail.price

                :

                formatPrice(

                    popular.price

                );


            html = `

                <h2>

                    ${escapeHTML(
                        popular.service_name
                    )}

                </h2>

                <p>

                    ${escapeHTML(
                        description
                    )}

                </p>

                <p>

                    <b>Price:</b>

                    ${escapeHTML(
                        price
                    )}

                </p>

                <a
                    href="booking-guest.php?service_id=${encodeURIComponent(popular.service_id)}"
                    class="popup-book"
                >

                    Book Now

                </a>

            `;

        }

    }


    document
    .getElementById(
        "modalContent"
    )
    .innerHTML = html;


    document
    .getElementById(
        "modal"
    )
    .style.display = "flex";

}


/* =========================================================
   CLOSE MODAL
   ========================================================= */

function closeModal(){

    document
    .getElementById(
        "modal"
    )
    .style.display = "none";

}


/* =========================================================
   CLOSE MODAL WHEN CLICKING BACKDROP
   ========================================================= */

document
.getElementById(
    "modal"
)
.addEventListener(

    "click",

    function(e){

        if(

            e.target === this

        ){

            closeModal();

        }

    }

);


/* =========================================================
   CLOSE MODAL WITH ESC
   ========================================================= */

document.addEventListener(

    "keydown",

    function(e){

        if(

            e.key === "Escape"

        ){

            closeModal();

        }

    }

);

</script>


</body>

</html>