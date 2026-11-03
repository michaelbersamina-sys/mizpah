
<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!function_exists('thankyouEscape')) {
    function thankyouEscape($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = null;

if ($id > 0 && isset($conn) && $conn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM bookings WHERE id = ? LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
    }
}

/* Only show a booking belonging to the logged-in customer, if user_id is available. */
if (
    $data &&
    isset($_SESSION['user_id']) &&
    isset($data['user_id']) &&
    (int)$data['user_id'] !== (int)$_SESSION['user_id']
) {
    $data = null;
}

$formattedDate = 'Not specified';
$formattedTime = 'Not specified';

if (!empty($data['booking_date'])) {
    $timestamp = strtotime($data['booking_date']);

    if ($timestamp !== false) {
        $formattedDate = date('F j, Y', $timestamp);
    }
}

if (!empty($data['booking_time'])) {
    $timestamp = strtotime($data['booking_time']);

    if ($timestamp !== false) {
        $formattedTime = date('g:i A', $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Confirmation | Mizpah Wellness Spa</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #000000;
            --card: #121212;
            --card-soft: #101010;
            --gold: #D6C29C;
            --bronze: #8C7D63;
            --text: #FFFFFF;
            --muted: #888888;
            --border: rgba(255, 255, 255, 0.07);
            --gold-border: rgba(214, 194, 156, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            padding: 24px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(
                    rgba(0, 0, 0, 0.88),
                    rgba(0, 0, 0, 0.96)
                ),
                url('../assets/images/hero.jpg') center / cover fixed no-repeat;
            color: var(--text);
            font-family: 'Poppins', sans-serif;
        }

        .box {
            width: 100%;
            max-width: 470px;
            padding: 25px 26px;
            background: rgba(18, 18, 18, 0.97);
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 16px 45px rgba(0, 0, 0, 0.4);
        }

        .confirmation {
            text-align: center;
            margin-bottom: 23px;
        }

        .check {
            width: 42px;
            height: 42px;
            margin: 0 auto 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(214, 194, 156, 0.45);
            border-radius: 50%;
            color: var(--gold);
            background: rgba(214, 194, 156, 0.07);
            font-size: 20px;
        }

        .eyebrow {
            margin-bottom: 5px;
            color: var(--bronze);
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
            font-size: 25px;
            font-weight: 500;
            line-height: 1.4;
        }

        .sub {
            margin-top: 8px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.8;
        }

        .reference {
            display: inline-block;
            margin-top: 14px;
            padding: 7px 11px;
            border: 1px solid var(--gold-border);
            border-radius: 7px;
            color: var(--gold);
            background: #0D0D0D;
            font-size: 11px;
        }

        .section-title {
            margin-bottom: 12px;
            color: var(--gold);
            font-size: 12px;
            font-weight: 600;
        }

        .service {
            margin-bottom: 17px;
            padding: 14px 15px;
            border: 1px solid var(--gold-border);
            border-radius: 10px;
            background: var(--card-soft);
        }

        .service-label {
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 10px;
        }

        .service-name {
            color: var(--gold);
            font-size: 14px;
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .service-duration {
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
        }

        .row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 11px;
        }

        .label {
            flex-shrink: 0;
            color: var(--muted);
        }

        .value {
            color: #EEEEEE;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .status-value {
            color: var(--gold);
        }

        .price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 17px;
            padding: 14px 15px;
            border: 1px solid var(--gold-border);
            border-radius: 10px;
            background: rgba(214, 194, 156, 0.045);
        }

        .price-label {
            color: #DDDDDD;
            font-size: 11px;
        }

        .price-note {
            max-width: 220px;
            margin-top: 4px;
            color: var(--muted);
            font-size: 9px;
            line-height: 1.6;
        }

        .price {
            color: var(--gold);
            font-size: 19px;
            font-weight: 600;
            white-space: nowrap;
        }

        .notes {
            margin-top: 15px;
            padding: 12px;
            border-left: 2px solid var(--bronze);
            background: var(--card-soft);
        }

        .notes .label {
            display: block;
            margin-bottom: 6px;
        }

        .notes .value {
            font-size: 11px;
            line-height: 1.8;
            text-align: left;
        }

        .btn {
            display: block;
            width: 100%;
            margin-top: 21px;
            padding: 12px 16px;
            border: 1px solid var(--gold);
            border-radius: 8px;
            background: var(--gold);
            color: #111111;
            text-align: center;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .btn:hover {
            background: #C7B187;
            border-color: #C7B187;
        }

        .empty {
            padding: 17px 0 5px;
            color: var(--muted);
            text-align: center;
            font-size: 11px;
            line-height: 1.8;
        }

        @media (max-width: 420px) {
            body {
                padding: 18px 12px;
            }

            .box {
                padding: 22px 18px;
            }

            h1 {
                font-size: 23px;
            }

            .price {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>

    <main class="box">

        <?php if ($data): ?>

            <header class="confirmation">
                <div class="check" aria-hidden="true">&#10003;</div>

                <p class="eyebrow">Mizpah Wellness Spa</p>
                <h1>Booking Confirmed!</h1>

                <p class="sub">
                    Thank you for choosing Mizpah Wellness Spa.
                    Your booking details are listed below.
                </p>

                <div class="reference">
                    Booking Reference:
                    <strong>#<?= thankyouEscape($data['id']) ?></strong>
                </div>
            </header>

            <h2 class="section-title">Your Booking Details</h2>

            <section class="service">
                <p class="service-label">Selected Service</p>

                <h2 class="service-name">
                    <?= thankyouEscape($data['service']) ?>
                </h2>

                <p class="service-duration">
                    Duration: <?= thankyouEscape($data['duration']) ?>
                </p>
            </section>

            <div class="row">
                <span class="label">Name</span>
                <span class="value">
                    <?= thankyouEscape($data['customer_name']) ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Date</span>
                <span class="value">
                    <?= thankyouEscape($formattedDate) ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Time</span>
                <span class="value">
                    <?= thankyouEscape($formattedTime) ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Guests</span>
                <span class="value">
                    <?= thankyouEscape($data['pax']) ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Payment Method</span>
                <span class="value">
                    <?= thankyouEscape($data['payment_method'] ?? 'Cash') ?>
                </span>
            </div>

            <div class="row">
                <span class="label">Status</span>
                <span class="value status-value">
                    <?= thankyouEscape($data['status']) ?>
                </span>
            </div>

            <div class="price-row">
                <div>
                    <p class="price-label">Total Booking Price</p>
                    <p class="price-note">
                        Booking confirmation only, not a payment receipt.
                    </p>
                </div>

                <div class="price">
                    ₱<?= number_format((float)($data['price'] ?? 0), 2) ?>
                </div>
            </div>

            <?php if (!empty($data['notes'])): ?>
                <div class="notes">
                    <span class="label">Additional Notes</span>
                    <p class="value">
                        <?= nl2br(thankyouEscape($data['notes'])) ?>
                    </p>
                </div>
            <?php endif; ?>

            <a href="dashboard.php" class="btn">Back to Dashboard</a>

        <?php else: ?>

            <header class="confirmation">
                <div class="check" aria-hidden="true">!</div>

                <p class="eyebrow">Mizpah Wellness Spa</p>
                <h1>Booking Not Found</h1>

                <p class="sub">
                    We couldn't find a booking associated with this reference.
                    Please return to your dashboard and check your booking details.
                </p>
            </header>

            <a href="dashboard.php" class="btn">Back to Dashboard</a>

        <?php endif; ?>

    </main>

</body>
</html>
