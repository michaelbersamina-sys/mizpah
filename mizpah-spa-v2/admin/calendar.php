
<?php
session_start();
include '../includes/db.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month > 12) {
    $month = 1;
    $year++;
}
if ($month < 1) {
    $month = 12;
    $year--;
}
if ($year < 2000 || $year > 2100) {
    $month = (int)date('m');
    $year = (int)date('Y');
}

$firstDay = (int)date('w', strtotime("$year-$month-01"));
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$today = date("Y-m-d");
$monthName = date("F Y", strtotime("$year-$month-01"));

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

/*
 * Load bookings and their assigned therapists.
 * Exclude cancelled bookings from the calendar.
 */
$sql = "
    SELECT
        b.id,
        b.customer_name,
        b.booking_date,
        b.booking_time,
        b.service,
        b.status,
        t.name AS therapist
    FROM bookings b
    LEFT JOIN booking_therapists bt ON bt.booking_id = b.id
    LEFT JOIN therapists t ON t.id = bt.therapist_id
    WHERE b.status != 'Cancelled'
    ORDER BY b.booking_time ASC
";

$get = mysqli_query($conn, $sql);

$bookings = [];
$calendar = [];

if ($get) {
    while ($row = mysqli_fetch_assoc($get)) {
        $date = $row['booking_date'];

        if (!isset($bookings[$date])) {
            $bookings[$date] = [];
        }

        /*
         * Keep one booking record per booking ID.
         * A booking may have multiple assigned therapists.
         */
        $bookingId = $row['id'];

        if (!isset($bookings[$date][$bookingId])) {
            $bookings[$date][$bookingId] = [
                'id' => $row['id'],
                'customer_name' => $row['customer_name'],
                'booking_date' => $row['booking_date'],
                'booking_time' => $row['booking_time'],
                'service' => $row['service'],
                'status' => $row['status'],
                'therapists' => []
            ];
        }

        if (!empty($row['therapist'])) {
            $bookings[$date][$bookingId]['therapists'][] = $row['therapist'];

            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }

            $calendar[$date][$row['therapist']] = true;
        }
    }
}

/* Convert grouped bookings to normal indexed arrays for JavaScript. */
foreach ($bookings as $date => $dateBookings) {
    foreach ($dateBookings as $id => $booking) {
        $bookings[$date][$id]['therapists'] = array_values(
            array_unique($booking['therapists'])
        );
    }

    $bookings[$date] = array_values($dateBookings);
}

function getStatusClass($count) {
    if ($count >= 7) {
        return "full";
    }

    if ($count >= 3) {
        return "partial";
    }

    return "available";
}

function h($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$monthBookingCount = 0;
$monthActiveDays = 0;

for ($d = 1; $d <= $days; $d++) {
    $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $count = count($bookings[$dateKey] ?? []);

    $monthBookingCount += $count;

    if ($count > 0) {
        $monthActiveDays++;
    }
}

$todayCount = count($bookings[$today] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Booking Calendar | Mizpah Wellness Spa</title>

<link rel="stylesheet" href="../assets/css/admin.css">

<link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>
:root {
    --gold: #D6C29C;
    --gold-light: #E9D9B8;
    --bg: #0B0B0B;
    --panel: #151513;
    --text: #F5F1E9;
    --muted: #A5A097;
    --border: rgba(214,194,156,.17);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background:
        radial-gradient(
            ellipse at top right,
            rgba(116,83,38,.13),
            transparent 42%
        ),
        var(--bg);
    color: var(--text);
    font-family: 'Poppins', sans-serif;
}

.main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 32px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 25px;
}

.eyebrow {
    color: #A9946C;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.page-header h1 {
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: clamp(28px, 3vw, 38px);
    margin-bottom: 8px;
}

.page-header p {
    color: var(--muted);
    font-size: 12px;
    line-height: 1.7;
}

.today-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    color: var(--gold-light);
    background: rgba(214,194,156,.06);
    border: 1px solid var(--border);
    border-radius: 999px;
    font-size: 11px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.summary-card {
    padding: 18px 20px;
    background: linear-gradient(145deg, #1B1A17, #111110);
    border: 1px solid var(--border);
    border-radius: 14px;
}

.summary-label {
    color: #A5A097;
    font-size: 11px;
    margin-bottom: 8px;
}

.summary-value {
    color: var(--gold-light);
    font-family: 'Playfair Display', serif;
    font-size: 27px;
    font-weight: 600;
}

.calendar-panel {
    padding: 23px;
    background: linear-gradient(145deg, #171715, #111110);
    border: 1px solid var(--border);
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(0,0,0,.18);
}

.calendar-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 22px;
}

.month-title {
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: clamp(21px, 2.4vw, 28px);
    font-weight: 600;
}

.month-subtitle {
    color: #858078;
    font-size: 11px;
    margin-top: 5px;
}

.month-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 9px 13px;
    color: var(--gold-light);
    background: rgba(214,194,156,.055);
    border: 1px solid var(--border);
    border-radius: 9px;
    text-decoration: none;
    font-size: 11px;
    margin: 0 4px;
}

.month-btn:hover,
.month-btn.current {
    background: var(--gold);
    color: #17130D;
    border-color: var(--gold);
}

.legend {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    padding: 13px 15px;
    margin-bottom: 18px;
    background: rgba(255,255,255,.018);
    border: 1px solid rgba(255,255,255,.055);
    border-radius: 11px;
}

.legend-title {
    color: #BDB4A4;
    font-size: 10px;
    font-weight: 600;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #A5A097;
    font-size: 10px;
}

.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.dot-available { background: #83B89A; }
.dot-partial { background: #D8B866; }
.dot-full { background: #C77E75; }
.dot-today { background: var(--gold); }

.calendar-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 7px;
}

.calendar-table th {
    color: #A99A7A;
    font-size: 10px;
    text-transform: uppercase;
    padding: 10px;
}

.calendar-table td {
    height: 112px;
    padding: 10px;
    background: rgba(255,255,255,.025);
    border: 1px solid rgba(255,255,255,.065);
    border-radius: 11px;
    cursor: pointer;
    transition: background .2s, border-color .2s, transform .2s;
    vertical-align: top;
}

.calendar-table td.empty-day {
    background: transparent;
    border-color: transparent;
    cursor: default;
}

.calendar-table td:not(.empty-day):hover {
    transform: translateY(-2px);
    background: rgba(214,194,156,.07);
    border-color: rgba(214,194,156,.5);
}

.calendar-table td.available {
    background: rgba(92,151,112,.055);
    border-color: rgba(92,151,112,.22);
}

.calendar-table td.partial {
    background: rgba(216,184,102,.07);
    border-color: rgba(216,184,102,.30);
}

.calendar-table td.full {
    background: rgba(199,126,117,.07);
    border-color: rgba(199,126,117,.30);
}

.calendar-table td.today {
    border-color: var(--gold) !important;
    background: rgba(214,194,156,.075);
}

.calendar-table td:focus-visible {
    outline: 2px solid var(--gold);
    outline-offset: 2px;
}

.day-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 27px;
    height: 27px;
    color: #D7D0C3;
    background: rgba(255,255,255,.035);
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}

.today .day-number {
    background: var(--gold);
    color: #17130D;
}

.today-label {
    font-size: 8px;
    color: var(--gold-light);
    text-transform: uppercase;
    font-weight: 700;
    margin-left: auto;
}

.therapist {
    font-size: 9px;
    color: #BEB6A8;
    display: block;
    margin: 3px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.count {
    font-size: 9px;
    margin-top: 6px;
    padding: 3px 6px;
    background: rgba(214,194,156,.08);
    border-radius: 6px;
    display: inline-block;
}

.no-bookings {
    font-size: 9px;
    color: #68655F;
    margin-top: 6px;
}

/* Booking details modal */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.78);
    z-index: 9999;
    padding: 20px;
    overflow-y: auto;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    max-width: 490px;
    width: 100%;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    padding: 25px;
    background: linear-gradient(145deg, #1B1A17, #111110);
    border: 1px solid rgba(214,194,156,.28);
    border-radius: 18px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
    padding-bottom: 15px;
    margin-bottom: 15px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}

.modal-eyebrow {
    font-size: 9px;
    color: #A9946C;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 5px;
}

.modal-content h3 {
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: 24px;
}

.close-btn {
    flex-shrink: 0;
    background: rgba(214,194,156,.07);
    border: 1px solid rgba(214,194,156,.17);
    color: var(--gold-light);
    width: 34px;
    height: 34px;
    border-radius: 8px;
    font-size: 20px;
    cursor: pointer;
}

.close-btn:hover {
    background: var(--gold);
    color: #17130D;
}

.item {
    padding: 15px 0;
    border-bottom: 1px solid rgba(255,255,255,.06);
}

.item:last-child {
    border-bottom: none;
}

.item strong {
    color: #F0E8D9;
    font-size: 13px;
    display: block;
    margin-bottom: 5px;
}

.item-line {
    color: #A5A097;
    font-size: 11px;
    margin: 3px 0;
    overflow-wrap: anywhere;
}

.item-label {
    color: #C8B58D;
}

.status-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 10px;
    background: rgba(214,194,156,.09);
    border-radius: 999px;
    font-size: 9px;
    color: var(--gold-light);
}

.modal-empty {
    padding: 30px;
    text-align: center;
    color: #99938A;
    font-size: 13px;
}

@media (max-width: 1000px) {
    .main {
        padding: 22px;
    }

    .calendar-panel {
        overflow-x: auto;
    }

    .calendar-table {
        min-width: 700px;
    }
}

@media (max-width: 768px) {
    .main {
        margin-left: 0;
        width: 100%;
        padding: 18px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .calendar-panel {
        padding: 14px;
    }

    .calendar-toolbar {
        align-items: flex-start;
    }

    .month-btn {
        margin: 4px 2px 0 0;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main">

    <header class="page-header">
        <div>
            <div class="eyebrow">Mizpah Wellness Spa · Admin</div>
            <h1>Booking Calendar</h1>
            <p>View daily appointments and therapist assignments.</p>
        </div>

        <div class="today-pill">
            <?= h(date('D, M d, Y')) ?>
        </div>
    </header>

    <section class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Bookings This Month</div>
            <div class="summary-value">
                <?= number_format($monthBookingCount) ?>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Days With Bookings</div>
            <div class="summary-value">
                <?= number_format($monthActiveDays) ?>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Today's Bookings</div>
            <div class="summary-value">
                <?= number_format($todayCount) ?>
            </div>
        </div>
    </section>

    <section class="calendar-panel">

        <div class="calendar-toolbar">
            <div>
                <div class="month-title"><?= h($monthName) ?></div>
                <div class="month-subtitle">
                    Click a date to view bookings.
                </div>
            </div>

            <div>
                <a
                    class="month-btn"
                    href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>"
                >&larr; Prev</a>

                <a
                    class="month-btn current"
                    href="?month=<?= date('m') ?>&year=<?= date('Y') ?>"
                >Today</a>

                <a
                    class="month-btn"
                    href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>"
                >Next &rarr;</a>
            </div>
        </div>

        <div class="legend">
            <span class="legend-title">Legend:</span>

            <span class="legend-item">
                <span class="legend-dot dot-available"></span>
                0–2
            </span>

            <span class="legend-item">
                <span class="legend-dot dot-partial"></span>
                3–6
            </span>

            <span class="legend-item">
                <span class="legend-dot dot-full"></span>
                7+
            </span>

            <span class="legend-item">
                <span class="legend-dot dot-today"></span>
                Today
            </span>
        </div>

        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>

            <tbody>
            <?php
            $day = 1;
            $cell = 0;

            while ($day <= $days):
                echo '<tr>';

                for ($col = 0; $col < 7; $col++):
                    if ($cell < $firstDay || $day > $days):
                        echo '<td class="empty-day"></td>';
                    else:
                        $dateKey = sprintf(
                            '%04d-%02d-%02d',
                            $year,
                            $month,
                            $day
                        );

                        $dayBookings = $bookings[$dateKey] ?? [];
                        $therapists = $calendar[$dateKey] ?? [];
                        $statusClass = getStatusClass(count($dayBookings));

                        if ($dateKey === $today) {
                            $statusClass .= ' today';
                        }
            ?>

                <td
                    class="<?= h($statusClass) ?> bookable-day"
                    data-date="<?= h($dateKey) ?>"
                    tabindex="0"
                    role="button"
                    aria-label="View bookings for <?= h($dateKey) ?>"
                    onclick="openDateModal(this.dataset.date)"
                >
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="day-number"><?= $day ?></span>

                        <?php if ($dateKey === $today): ?>
                            <span class="today-label">Today</span>
                        <?php endif; ?>
                    </div>

                    <?php
                    $shown = 0;

                    foreach ($therapists as $tname => $val):
                        if ($shown >= 2) {
                            break;
                        }

                        echo '<span class="therapist">' . h($tname) . '</span>';
                        $shown++;
                    endforeach;

                    if (count($therapists) > 2):
                        echo '<span class="therapist">+' .
                            (count($therapists) - 2) .
                            ' more</span>';
                    endif;
                    ?>

                    <?php if (count($dayBookings) > 0): ?>
                        <div class="count">
                            <?= count($dayBookings) ?>
                            booking<?= count($dayBookings) > 1 ? 's' : '' ?>
                        </div>
                    <?php else: ?>
                        <div class="no-bookings">None</div>
                    <?php endif; ?>
                </td>

            <?php
                        $day++;
                    endif;

                    $cell++;
                endfor;

                echo '</tr>';
            endwhile;
            ?>
            </tbody>
        </table>

    </section>
</main>

<!-- Booking Details Modal -->
<div
    id="bookingModal"
    class="modal"
    aria-hidden="true"
>
    <div
        class="modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalDateHeading"
    >
        <div class="modal-header">
            <div>
                <div class="modal-eyebrow">Appointment Details</div>
                <h3 id="modalDateHeading"></h3>
            </div>

            <button
                type="button"
                class="close-btn"
                id="closeModalButton"
                aria-label="Close booking details"
            >&times;</button>
        </div>

        <div id="modalContentBody"></div>
    </div>
</div>

<script>
'use strict';

/*
 * Safely pass PHP booking data to JavaScript.
 */
const allBookings = <?= json_encode(
    $bookings,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP |
    JSON_INVALID_UTF8_SUBSTITUTE
) ?>;

const bookingModal = document.getElementById('bookingModal');
const modalDateHeading = document.getElementById('modalDateHeading');
const modalContentBody = document.getElementById('modalContentBody');
const closeModalButton = document.getElementById('closeModalButton');

function formatDisplayDate(dateStr) {
    const parts = dateStr.split('-').map(Number);

    if (parts.length !== 3 || parts.some(Number.isNaN)) {
        return dateStr;
    }

    const date = new Date(parts[0], parts[1] - 1, parts[2]);

    return date.toLocaleDateString('en-PH', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDisplayTime(timeStr) {
    if (!timeStr) {
        return 'Time not specified';
    }

    const match = String(timeStr).match(/^(\d{1,2}):(\d{2})/);

    if (!match) {
        return String(timeStr);
    }

    let hour = Number(match[1]);
    const minute = match[2];
    const period = hour >= 12 ? 'PM' : 'AM';

    hour = hour % 12 || 12;

    return hour + ':' + minute + ' ' + period;
}

/*
 * Escape values before displaying booking information in HTML.
 */
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
}

function openDateModal(dateStr) {
    if (!dateStr || !bookingModal) {
        return;
    }

    modalDateHeading.textContent = formatDisplayDate(dateStr);

    const dayBookings = allBookings[dateStr] || [];
    let html = '';

    if (dayBookings.length === 0) {
        html = `
            <div class="modal-empty">
                No bookings scheduled for this date.
            </div>
        `;
    } else {
        dayBookings.forEach(function(booking) {
            const customerName = escapeHtml(booking.customer_name);
            const service = escapeHtml(booking.service);
            const time = escapeHtml(
                formatDisplayTime(booking.booking_time)
            );
            const status = escapeHtml(booking.status || 'Pending');

            let therapistNames = 'Not assigned';

            if (
                Array.isArray(booking.therapists) &&
                booking.therapists.length > 0
            ) {
                therapistNames = booking.therapists
                    .map(function(name) {
                        return escapeHtml(name);
                    })
                    .join(', ');
            }

            html += `
                <div class="item">
                    <strong>${customerName}</strong>

                    <div class="item-line">
                        <span class="item-label">Service:</span>
                        ${service}
                    </div>

                    <div class="item-line">
                        <span class="item-label">Time:</span>
                        ${time}
                    </div>

                    <div class="item-line">
                        <span class="item-label">Therapist:</span>
                        ${therapistNames}
                    </div>

                    <span class="status-badge">${status}</span>
                </div>
            `;
        });
    }

    modalContentBody.innerHTML = html;
    bookingModal.classList.add('show');
    bookingModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    closeModalButton.focus();
}

function closeDateModal() {
    bookingModal.classList.remove('show');
    bookingModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

/*
 * Calendar click and keyboard support.
 */
document.querySelectorAll('.bookable-day').forEach(function(cell) {
    cell.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openDateModal(cell.dataset.date);
        }
    });
});

/*
 * Close when the user clicks the dark background.
 */
bookingModal.addEventListener('click', function(event) {
    if (event.target === bookingModal) {
        closeDateModal();
    }
});

/*
 * Close button.
 */
closeModalButton.addEventListener('click', closeDateModal);

/*
 * Close the popup with the Escape key.
 */
document.addEventListener('keydown', function(event) {
    if (
        event.key === 'Escape' &&
        bookingModal.classList.contains('show')
    ) {
        closeDateModal();
    }
});
</script>

</body>
</html>
