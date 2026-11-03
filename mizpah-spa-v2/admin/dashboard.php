
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* ================= COUNTS ================= */
function getCount($conn, $sql) {
    $q = mysqli_query($conn, $sql);
    if (!$q) return 0;

    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

$bookings = getCount($conn, "SELECT COUNT(*) AS total FROM bookings");

$confirmed = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE status = 'Confirmed'
");

$completed = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE status = 'Completed'
");

$todayBookings = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE DATE(booking_date) = CURDATE()
");

/* ================= REVENUE ================= */
$revenueQ = mysqli_query($conn, "
    SELECT IFNULL(SUM(price * pax), 0) AS total
    FROM bookings
    WHERE status IN ('Completed', 'Confirmed')
");

$revenueRow = $revenueQ ? mysqli_fetch_assoc($revenueQ) : [];
$revenue = (float)($revenueRow['total'] ?? 0);

/* ================= RECENT BOOKINGS ================= */
$recent = mysqli_query($conn, "
    SELECT customer_name, service, booking_date,
           booking_time, status
    FROM bookings
    WHERE status IN ('Confirmed', 'Completed', 'Cancelled')
    ORDER BY id DESC
    LIMIT 5
");

/* ================= TODAY'S SCHEDULE ================= */
$schedule = mysqli_query($conn, "
    SELECT customer_name, service, booking_time, status
    FROM bookings
    WHERE DATE(booking_date) = CURDATE()
      AND status IN ('Confirmed', 'Completed', 'Cancelled')
    ORDER BY booking_time ASC
");

/* ================= REVENUE GRAPH ================= */
$labels = [];
$data = [];
$dailyRevenue = [];

$graph = mysqli_query($conn, "
    SELECT DATE(booking_date) AS day,
           IFNULL(SUM(price * pax), 0) AS total
    FROM bookings
    WHERE status IN ('Completed', 'Confirmed')
      AND DATE(booking_date) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND DATE(booking_date) <= CURDATE()
    GROUP BY DATE(booking_date)
    ORDER BY day ASC
");

if ($graph) {
    while ($row = mysqli_fetch_assoc($graph)) {
        $dailyRevenue[$row['day']] = (float)$row['total'];
    }
}

for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($day));
    $data[] = $dailyRevenue[$day] ?? 0;
}

/* ================= PEAK HOURS GRAPH ================= */
$hourLabels = [];
$hourData = [];

$hours = mysqli_query($conn, "
    SELECT HOUR(booking_time) AS hour,
           COUNT(*) AS total
    FROM bookings
    WHERE booking_time IS NOT NULL
      AND booking_time <> ''
    GROUP BY HOUR(booking_time)
    ORDER BY hour ASC
");

if ($hours) {
    while ($h = mysqli_fetch_assoc($hours)) {
        $hour = (int)$h['hour'];

        $hourLabels[] = date(
            "g A",
            strtotime(sprintf('%02d:00:00', $hour))
        );

        $hourData[] = (int)$h['total'];
    }
}

/* ================= PEAK HOUR ================= */
$peak = mysqli_query($conn, "
    SELECT HOUR(booking_time) AS hour,
           COUNT(*) AS total
    FROM bookings
    WHERE booking_time IS NOT NULL
      AND booking_time <> ''
    GROUP BY HOUR(booking_time)
    ORDER BY total DESC, hour ASC
    LIMIT 1
");

$peakRow = $peak ? mysqli_fetch_assoc($peak) : null;

$peakHour = isset($peakRow['hour'])
    ? date(
        "g A",
        strtotime(sprintf('%02d:00:00', (int)$peakRow['hour']))
    )
    : 'N/A';

/* ================= SAFE OUTPUT ================= */
function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusClass($status) {
    $status = strtolower(trim((string)$status));

    return in_array(
        $status,
        ['confirmed', 'completed', 'cancelled'],
        true
    ) ? $status : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Mizpah Wellness Spa</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* {
    box-sizing: border-box;
}

:root {
    --gold: #d5c29d;
    --gold-light: #ead9b6;
    --bg: #0b0b0b;
    --panel: #111111;
    --panel-light: #131313;
    --border: rgba(255,255,255,.07);
    --gold-border: rgba(213,194,157,.18);
    --text: #eeeeee;
    --muted: #888888;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: 'Poppins', sans-serif;
}

.main-content {
    margin-left: 250px;
    min-height: 100vh;
    padding: 28px 30px 55px;
}

/* PAGE HEADER */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
}

.page-eyebrow {
    color: #84745f;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

.page-header h1 {
    margin: 2px 0 0;
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    font-weight: 600;
}

.page-header p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.date-pill {
    padding: 10px 15px;
    background: rgba(213,194,157,.06);
    border: 1px solid var(--gold-border);
    border-radius: 7px;
    color: var(--gold-light);
    font-size: 11px;
    white-space: nowrap;
}

/* SECTION TITLES */
.section-label {
    margin: 0 0 12px 2px;
    color: #938366;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* SUMMARY CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
    margin-bottom: 23px;
}

.stat-card {
    position: relative;
    min-width: 0;
    padding: 17px 18px;
    background: var(--panel-light);
    border: 1px solid var(--border);
    border-radius: 10px;
    transition: background .2s, border-color .2s, transform .2s;
}

.stat-card:hover {
    background: #191919;
    border-color: rgba(213,194,157,.30);
    transform: translateY(-2px);
}

.stat-card h3 {
    margin: 0 0 7px;
    color: #999999;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: .6px;
    text-transform: uppercase;
}

.stat-number {
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: clamp(24px, 2.3vw, 30px);
    font-weight: 600;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

/* PANELS */
.panel {
    min-width: 0;
    padding: 20px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 10px;
}

.panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 18px;
}

.panel-heading h2 {
    margin: 0;
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
}

.panel-heading p {
    margin: 4px 0 0;
    color: #777777;
    font-size: 11px;
    line-height: 1.6;
}

.panel-tag {
    padding: 5px 9px;
    background: rgba(213,194,157,.07);
    border: 1px solid rgba(213,194,157,.16);
    border-radius: 6px;
    color: var(--gold-light);
    font-size: 10px;
    white-space: nowrap;
}

/* CHARTS */
.chart-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.chart-box {
    position: relative;
    width: 100%;
    height: 275px;
}

/* RECENT BOOKINGS AND SCHEDULE */
.booking-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.booking-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 0;
    border-bottom: 1px solid rgba(255,255,255,.055);
}

.booking-row:last-child {
    border-bottom: none;
}

.booking-info {
    min-width: 0;
    overflow-wrap: anywhere;
    color: #eeeeee;
    font-size: 12px;
    font-weight: 500;
}

.small {
    margin-top: 4px;
    color: #888888;
    font-size: 11px;
    font-weight: 400;
    line-height: 1.5;
}

.booking-meta {
    flex-shrink: 0;
    text-align: right;
}

/* STATUS BADGES */
.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 8px;
    border: 1px solid transparent;
    border-radius: 6px;
    font-size: 9px;
    font-weight: 600;
    text-transform: capitalize;
    white-space: nowrap;
}

.confirmed {
    background: rgba(104,166,119,.12);
    color: #9ed5a9;
    border-color: rgba(104,166,119,.2);
}

.completed {
    background: rgba(213,194,157,.12);
    color: #ead9b6;
    border-color: rgba(213,194,157,.22);
}

.cancelled {
    background: rgba(190,94,94,.12);
    color: #e4a1a1;
    border-color: rgba(190,94,94,.2);
}

/* LINKS */
.view-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-top: 15px;
    padding: 10px 15px;
    background: var(--gold);
    border: 1px solid var(--gold);
    border-radius: 7px;
    color: #17130d;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s;
}

.view-link:hover {
    background: var(--gold-light);
}

/* EMPTY STATE */
.empty-state {
    padding: 30px 12px;
    color: #777777;
    font-size: 12px;
    line-height: 1.7;
    text-align: center;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .main-content {
        padding: 25px;
    }

    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .chart-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1100px) {
    .main-content {
        margin-left: 0;
        width: 100%;
    }
}

@media (max-width: 760px) {
    .booking-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .date-pill {
        white-space: normal;
    }
}

@media (max-width: 560px) {
    .main-content {
        padding: 20px 12px 30px;
    }

    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .stat-card {
        padding: 14px;
    }

    .stat-number {
        font-size: 24px;
    }

    .panel {
        padding: 16px 13px;
    }

    .panel-heading h2 {
        font-size: 20px;
    }

    .panel-tag {
        font-size: 9px;
    }

    .chart-box {
        height: 235px;
    }

    .booking-row {
        align-items: flex-start;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

    <header class="page-header">
        <div>
            <div class="page-eyebrow">Appointment Management</div>
            <h1>Dashboard</h1>
            <p>Overview of Mizpah Wellness Spa bookings and performance.</p>
        </div>

        <span class="date-pill">
            <?= h(date("l, F d, Y")) ?>
        </span>
    </header>

    <div class="section-label">Overview</div>

    <section class="cards">

        <div class="stat-card">
            <h3>Total Revenue</h3>
            <div class="stat-number">₱<?= number_format($revenue, 2) ?></div>
        </div>

        <div class="stat-card">
            <h3>Today's Bookings</h3>
            <div class="stat-number"><?= number_format($todayBookings) ?></div>
        </div>

        <div class="stat-card">
            <h3>Peak Hour</h3>
            <div class="stat-number"><?= h($peakHour) ?></div>
        </div>

        <div class="stat-card">
            <h3>Total Bookings</h3>
            <div class="stat-number"><?= number_format($bookings) ?></div>
        </div>

        <div class="stat-card">
            <h3>Confirmed</h3>
            <div class="stat-number"><?= number_format($confirmed) ?></div>
        </div>

        <div class="stat-card">
            <h3>Completed</h3>
            <div class="stat-number"><?= number_format($completed) ?></div>
        </div>

    </section>

    <section class="chart-grid">

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Revenue Trend</h2>
                    <p>Daily revenue for the last seven days.</p>
                </div>
                <span class="panel-tag">7 days</span>
            </div>

            <div class="chart-box">
                <canvas id="chart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Peak Hours</h2>
                    <p>Booking volume by time of day.</p>
                </div>
                <span class="panel-tag">All bookings</span>
            </div>

            <div class="chart-box">
                <canvas id="hourChart"></canvas>
            </div>
        </div>

    </section>

    <section class="booking-grid">

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Recent Bookings</h2>
                    <p>The five latest booking records.</p>
                </div>
            </div>

            <?php if ($recent && mysqli_num_rows($recent) > 0): ?>

                <?php while ($r = mysqli_fetch_assoc($recent)): ?>
                    <div class="booking-row">
                        <div class="booking-info">
                            <?= h($r['customer_name']) ?>

                            <div class="small">
                                <?= h($r['service']) ?>

                                <?php if (!empty($r['booking_date'])): ?>
                                    · <?= h(date('M d, Y', strtotime($r['booking_date']))) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="booking-meta">
                            <span class="badge <?= h(statusClass($r['status'] ?? '')) ?>">
                                <?= h($r['status'] ?? '') ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">
                    No booking records yet.
                </div>

            <?php endif; ?>

            <a class="view-link" href="bookings.php">
                View All Bookings &rarr;
            </a>
        </div>

        <div class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Today's Schedule</h2>
                    <p>Appointments arranged by time.</p>
                </div>

                <span class="panel-tag">
                    <?= number_format($todayBookings) ?> today
                </span>
            </div>

            <?php if ($schedule && mysqli_num_rows($schedule) > 0): ?>

                <?php while ($s = mysqli_fetch_assoc($schedule)): ?>
                    <div class="booking-row">
                        <div class="booking-info">
                            <?= h($s['customer_name']) ?>

                            <div class="small">
                                <?= h($s['service']) ?>
                            </div>
                        </div>

                        <div class="booking-meta">
                            <div class="small">
                                <?= !empty($s['booking_time'])
                                    ? h(date("g:i A", strtotime($s['booking_time'])))
                                    : 'Time TBA' ?>
                            </div>

                            <span class="badge <?= h(statusClass($s['status'] ?? '')) ?>">
                                <?= h($s['status'] ?? '') ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">
                    No bookings scheduled for today.
                </div>

            <?php endif; ?>

            <a class="view-link" href="calendar.php">
                Open Calendar &rarr;
            </a>
        </div>

    </section>

</main>

<script>
const labels = <?= json_encode(
    $labels,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const revenueData = <?= json_encode($data) ?>;

const hourLabels = <?= json_encode(
    $hourLabels,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const hourData = <?= json_encode($hourData) ?>;

Chart.defaults.color = '#888888';
Chart.defaults.font.family = 'Poppins, sans-serif';

const gridColor = 'rgba(255,255,255,0.055)';

/* REVENUE TREND */
new Chart(document.getElementById('chart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue',
            data: revenueData,
            borderColor: '#d5c29d',
            backgroundColor: 'rgba(213,194,157,0.10)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#d5c29d'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#181818',
                borderColor: 'rgba(213,194,157,.25)',
                borderWidth: 1,
                titleColor: '#ead9b6',
                bodyColor: '#eeeeee',
                callbacks: {
                    label: function(context) {
                        return ' ₱' + Number(context.parsed.y || 0).toLocaleString(
                            'en-PH',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                border: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: gridColor
                },
                border: {
                    display: false
                },
                ticks: {
                    maxTicksLimit: 6,
                    callback: function(value) {
                        return '₱' + Number(value).toLocaleString('en-PH');
                    }
                }
            }
        }
    }
});

/* PEAK HOURS */
new Chart(document.getElementById('hourChart'), {
    type: 'bar',
    data: {
        labels: hourLabels,
        datasets: [{
            label: 'Bookings',
            data: hourData,
            backgroundColor: 'rgba(213,194,157,.78)',
            hoverBackgroundColor: '#ead9b6',
            borderRadius: 5,
            maxBarThickness: 30
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#181818',
                borderColor: 'rgba(213,194,157,.25)',
                borderWidth: 1,
                titleColor: '#ead9b6',
                bodyColor: '#eeeeee'
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                border: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0,
                    stepSize: 1
                },
                grid: {
                    color: gridColor
                },
                border: {
                    display: false
                }
            }
        }
    }
});
</script>

</body>
</html>
