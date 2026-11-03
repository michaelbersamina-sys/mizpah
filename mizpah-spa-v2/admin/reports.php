
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

date_default_timezone_set('Asia/Manila');

/* ================= FILTERS ================= */
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';

$allowedStatuses = ['Confirmed', 'Completed'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$isValidDate = function ($date) {
    if ($date === '') return true;
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
};

if (!$isValidDate($from)) $from = '';
if (!$isValidDate($to)) $to = '';

if ($from && $to && $from > $to) {
    [$from, $to] = [$to, $from];
}

/*
 Default: Confirmed + Completed.
 Kapag may status filter, iyon ang gagamitin.
*/
$where = ["status IN ('Completed','Confirmed')"];

if ($status !== '') {
    $safeStatus = mysqli_real_escape_string($conn, $status);
    $where = ["status = '$safeStatus'"];
}

$where[] = "price > 0";

if ($from !== '') {
    $safeFrom = mysqli_real_escape_string($conn, $from);
    $where[] = "booking_date >= '$safeFrom'";
}

if ($to !== '') {
    $safeTo = mysqli_real_escape_string($conn, $to);
    $where[] = "booking_date <= '$safeTo'";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

/* ================= TOTAL REVENUE ================= */
$totalSales = 0;
$totalBookings = 0;

$result = mysqli_query($conn, "
    SELECT
        COALESCE(SUM(price * GREATEST(COALESCE(pax, 1), 1)), 0) AS total,
        COUNT(*) AS booking_count
    FROM bookings
    $whereSQL
");

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $totalSales = (float)($row['total'] ?? 0);
    $totalBookings = (int)($row['booking_count'] ?? 0);
}

/* ================= PAYMENT BREAKDOWN ================= */
/* Always show Cash and GCash, even if their totals are zero. */
$paymentTotals = [
    'Cash' => 0,
    'GCash' => 0
];

$paymentResult = mysqli_query($conn, "
    SELECT
        CASE
            WHEN LOWER(TRIM(COALESCE(payment_method, ''))) = 'gcash'
                THEN 'GCash'
            WHEN TRIM(COALESCE(payment_method, '')) = ''
                THEN 'Cash'
            ELSE 'Cash'
        END AS method,
        COALESCE(SUM(price * GREATEST(COALESCE(pax, 1), 1)), 0) AS total
    FROM bookings
    $whereSQL
    GROUP BY method
");

if ($paymentResult) {
    while ($row = mysqli_fetch_assoc($paymentResult)) {
        $method = $row['method'];
        if (isset($paymentTotals[$method])) {
            $paymentTotals[$method] = (float)$row['total'];
        }
    }
}

/* ================= TOP SERVICES ================= */
$services = mysqli_query($conn, "
    SELECT service, COUNT(*) AS total
    FROM bookings
    $whereSQL
      AND COALESCE(service, '') != ''
    GROUP BY service
    ORDER BY total DESC
    LIMIT 5
");

/* ================= DAILY SALES ================= */
$labels = [];
$data = [];

$daily = mysqli_query($conn, "
    SELECT
        booking_date,
        COALESCE(SUM(price * GREATEST(COALESCE(pax, 1), 1)), 0) AS total
    FROM bookings
    $whereSQL
    GROUP BY booking_date
    ORDER BY booking_date ASC
");

if ($daily && mysqli_num_rows($daily) > 0) {
    while ($row = mysqli_fetch_assoc($daily)) {
        $labels[] = date('M d, Y', strtotime($row['booking_date']));
        $data[] = (float)$row['total'];
    }
}

$hasChartData = count($labels) > 0;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$periodLabel = 'All available dates';

if ($from && $to) {
    $periodLabel = date('M d, Y', strtotime($from)) . ' – ' .
                   date('M d, Y', strtotime($to));
} elseif ($from) {
    $periodLabel = 'From ' . date('M d, Y', strtotime($from));
} elseif ($to) {
    $periodLabel = 'Until ' . date('M d, Y', strtotime($to));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Report | Mizpah Wellness Spa</title>

<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --bg: #0b0b0b;
    --panel: #131313;
    --panel-soft: #181818;
    --border: #2b2925;
    --gold: #d6c29c;
    --gold-muted: #a99673;
    --text: #f5f1e9;
    --muted: #a7a197;
    --green: #9bc5a2;
    --blue: #c9b99c;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: 'Poppins', sans-serif;
    font-weight: 300;
}

.main {
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
    width: calc(100% - 250px);
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.eyebrow {
    color: var(--gold-muted);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

h1 {
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: clamp(27px, 3vw, 36px);
    font-weight: 600;
    margin: 0;
}

.subtitle {
    color: var(--muted);
    font-size: 12px;
    margin-top: 8px;
}

.header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 10px 16px;
    border: 1px solid var(--border);
    border-radius: 7px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.btn-primary {
    background: var(--gold);
    color: #17140f;
    border-color: var(--gold);
}

.btn-secondary {
    background: #171717;
    color: var(--text);
}

.btn:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
}

.filter-card,
.card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 10px;
}

.filter-card {
    padding: 20px;
    margin-bottom: 20px;
}

.section-heading {
    font-size: 13px;
    font-weight: 500;
    color: var(--gold);
    margin: 0 0 15px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
    align-items: end;
}

.field label {
    display: block;
    color: var(--muted);
    font-size: 11px;
    margin-bottom: 7px;
}

.field input,
.field select {
    width: 100%;
    min-width: 0;
    height: 42px;
    border: 1px solid #36332d;
    border-radius: 7px;
    padding: 0 11px;
    background: #0e0e0e;
    color: var(--text);
    font: 12px 'Poppins', sans-serif;
    outline: none;
    color-scheme: dark;
}

.field input:focus,
.field select:focus {
    border-color: var(--gold-muted);
}

.filter-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-actions .btn {
    flex: 1;
    white-space: nowrap;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    padding: 21px;
    min-width: 0;
}

.summary-label {
    color: var(--muted);
    font-size: 11px;
    margin-bottom: 12px;
}

.summary-value {
    color: var(--gold);
    font-size: clamp(22px, 2.2vw, 29px);
    line-height: 1.3;
    font-weight: 500;
    overflow-wrap: anywhere;
}

.summary-note {
    color: #777168;
    font-size: 10px;
    margin-top: 10px;
}

.content-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.card-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
}

.card-title {
    color: var(--gold);
    font-size: 14px;
    font-weight: 500;
    margin: 0;
}

.card-description {
    color: var(--muted);
    font-size: 10px;
    margin-top: 6px;
}

.badge {
    display: inline-flex;
    align-items: center;
    border: 1px solid #4b4231;
    background: #211d16;
    color: var(--gold);
    border-radius: 5px;
    padding: 5px 8px;
    font-size: 9px;
    white-space: nowrap;
}

.payment-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.payment-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px;
    border: 1px solid #292722;
    border-radius: 8px;
    background: #101010;
}

.payment-info {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.payment-icon {
    width: 37px;
    height: 37px;
    flex: 0 0 37px;
    display: grid;
    place-items: center;
    border: 1px solid #49402f;
    border-radius: 8px;
    background: #211d16;
    color: var(--gold);
    font-size: 10px;
    font-weight: 600;
}

.payment-name {
    color: var(--text);
    font-size: 12px;
    font-weight: 500;
}

.payment-caption {
    color: var(--muted);
    font-size: 10px;
    margin-top: 4px;
}

.payment-amount {
    color: var(--gold);
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

.payment-total {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    border-top: 1px solid var(--border);
    padding-top: 15px;
    margin-top: 17px;
    font-size: 12px;
}

.payment-total strong {
    color: var(--gold);
    font-weight: 500;
}

.service-list {
    display: flex;
    flex-direction: column;
    gap: 13px;
}

.service-row {
    display: grid;
    grid-template-columns: 25px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
}

.service-rank {
    width: 25px;
    height: 25px;
    display: grid;
    place-items: center;
    background: #242016;
    border-radius: 6px;
    color: var(--gold);
    font-size: 10px;
}

.service-name {
    color: #e9e4da;
    font-size: 11px;
    overflow-wrap: anywhere;
}

.service-count {
    color: var(--muted);
    font-size: 10px;
    white-space: nowrap;
}

.empty-state {
    padding: 20px 10px;
    color: var(--muted);
    font-size: 11px;
    text-align: center;
    border: 1px dashed #35322c;
    border-radius: 8px;
}

.chart-card {
    margin-bottom: 20px;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 300px;
}

.report-footer {
    color: #716c63;
    font-size: 10px;
    text-align: right;
    padding: 4px 0 15px;
}

@media (max-width: 1100px) {
    .main {
        padding: 22px;
    }

    .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .main {
        margin-left: 0;
        width: 100%;
        padding: 18px 14px;
    }

    .page-header {
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .summary-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .summary-value {
        font-size: 25px;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .filter-card,
    .card {
        padding: 16px;
    }

    .chart-container {
        height: 250px;
    }
}

@media (max-width: 420px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }

    .filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .payment-amount {
        font-size: 12px;
    }
}

/* ================= PRINT LAYOUT ================= */
@page {
    size: A4 portrait;
    margin: 12mm;
}

@media print {
    :root {
        --bg: #ffffff;
        --panel: #ffffff;
        --panel-soft: #ffffff;
        --border: #d8d3c9;
        --gold: #574321;
        --gold-muted: #76664a;
        --text: #171717;
        --muted: #555555;
    }

    html, body {
        background: #fff !important;
        color: #171717 !important;
        font-family: Arial, sans-serif !important;
        font-size: 10pt;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        min-height: auto !important;
    }

    .sidebar,
    aside,
    nav,
    .filter-card,
    .header-actions,
    .no-print {
        display: none !important;
    }

    .page-header {
        display: block;
        border-bottom: 2px solid #8b754e;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    h1 {
        font-family: Georgia, serif !important;
        font-size: 24pt;
        color: #574321 !important;
    }

    .eyebrow,
    .subtitle,
    .summary-label,
    .summary-note,
    .card-description,
    .payment-caption,
    .service-count,
    .report-footer {
        color: #555 !important;
    }

    .summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 8px;
        margin-bottom: 12px;
    }

    .content-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px;
        margin-bottom: 12px;
    }

    .card,
    .payment-row {
        background: #fff !important;
        border: 1px solid #d8d3c9 !important;
        border-radius: 4px !important;
        box-shadow: none !important;
        break-inside: avoid;
    }

    .card {
        padding: 12px !important;
    }

    .summary-value,
    .payment-amount,
    .payment-total strong,
    .card-title,
    .payment-name,
    .service-name {
        color: #171717 !important;
    }

    .payment-icon,
    .service-rank,
    .badge {
        background: #f4f0e7 !important;
        color: #574321 !important;
        border-color: #d8d0c0 !important;
    }

    .chart-card {
        break-inside: avoid;
    }

    .chart-container {
        height: 230px !important;
    }

    canvas {
        max-width: 100% !important;
    }

    .report-footer {
        display: block !important;
        margin-top: 15px;
        font-size: 8pt;
    }
}
</style>
</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main">
    <header class="page-header">
        <div>
            <div class="eyebrow">Mizpah Wellness Spa · Management Information System</div>
            <h1>Sales Report</h1>
            <div class="subtitle">
                Revenue overview · <?= e($periodLabel) ?>
                <?= $status !== '' ? ' · ' . e($status) : ' · Confirmed and completed bookings' ?>
            </div>
        </div>

        <div class="header-actions">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                Print Report
            </button>
        </div>
    </header>

    <section class="filter-card">
        <h2 class="section-heading">Filter Report</h2>

        <form method="GET" class="filter-grid">
            <div class="field">
                <label for="from">From Date</label>
                <input type="date" id="from" name="from" value="<?= e($from) ?>">
            </div>

            <div class="field">
                <label for="to">To Date</label>
                <input type="date" id="to" name="to" value="<?= e($to) ?>">
            </div>

            <div class="field">
                <label for="status">Booking Status</label>
                <select id="status" name="status">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>
                        All Revenue Statuses
                    </option>
                    <option value="Confirmed" <?= $status === 'Confirmed' ? 'selected' : '' ?>>
                        Confirmed
                    </option>
                    <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>
                        Completed
                    </option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="sales-report.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="summary-grid">
        <article class="card">
            <div class="summary-label">Total Revenue</div>
            <div class="summary-value">₱<?= number_format($totalSales, 2) ?></div>
            <div class="summary-note">Based on price × pax</div>
        </article>

        <article class="card">
            <div class="summary-label">Included Bookings</div>
            <div class="summary-value"><?= number_format($totalBookings) ?></div>
            <div class="summary-note">Matching the selected filters</div>
        </article>

        <article class="card">
            <div class="summary-label">Report Period</div>
            <div class="summary-value" style="font-size: clamp(17px, 2vw, 23px);">
                <?= e($periodLabel) ?>
            </div>
            <div class="summary-note">
                <?= $status !== '' ? e($status) : 'Confirmed + Completed' ?>
            </div>
        </article>
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="card-heading">
                <div>
                    <h2 class="card-title">Payment Breakdown</h2>
                    <div class="card-description">
                        Revenue grouped by recorded payment method
                    </div>
                </div>
                <span class="badge">PHP</span>
            </div>

            <div class="payment-list">
                <div class="payment-row">
                    <div class="payment-info">
                        <div class="payment-icon">₱</div>
                        <div>
                            <div class="payment-name">Cash</div>
                            <div class="payment-caption">Cash payment records</div>
                        </div>
                    </div>
                    <div class="payment-amount">
                        ₱<?= number_format($paymentTotals['Cash'], 2) ?>
                    </div>
                </div>

                <div class="payment-row">
                    <div class="payment-info">
                        <div class="payment-icon">GC</div>
                        <div>
                            <div class="payment-name">GCash</div>
                            <div class="payment-caption">GCash payment records</div>
                        </div>
                    </div>
                    <div class="payment-amount">
                        ₱<?= number_format($paymentTotals['GCash'], 2) ?>
                    </div>
                </div>
            </div>

            <div class="payment-total">
                <span>Total payment breakdown</span>
                <strong>
                    ₱<?= number_format(
                        $paymentTotals['Cash'] + $paymentTotals['GCash'],
                        2
                    ) ?>
                </strong>
            </div>

            <div class="summary-note">
                If this total differs from Total Revenue, check the payment_method
                values saved in your bookings table.
            </div>
        </article>

        <article class="card">
            <div class="card-heading">
                <div>
                    <h2 class="card-title">Top Services</h2>
                    <div class="card-description">Most frequently booked services</div>
                </div>
                <span class="badge">TOP 5</span>
            </div>

            <?php if ($services && mysqli_num_rows($services) > 0): ?>
                <div class="service-list">
                    <?php $rank = 1; ?>
                    <?php while ($s = mysqli_fetch_assoc($services)): ?>
                        <div class="service-row">
                            <div class="service-rank"><?= $rank++ ?></div>
                            <div class="service-name"><?= e($s['service']) ?></div>
                            <div class="service-count">
                                <?= number_format((int)$s['total']) ?>
                                <?= (int)$s['total'] === 1 ? 'booking' : 'bookings' ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">No service records found for these filters.</div>
            <?php endif; ?>
        </article>
    </section>

    <section class="card chart-card">
        <div class="card-heading">
            <div>
                <h2 class="card-title">Daily Sales</h2>
                <div class="card-description">
                    Daily revenue based on the selected filters
                </div>
            </div>
            <span class="badge">REVENUE</span>
        </div>

        <?php if ($hasChartData): ?>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        <?php else: ?>
            <div class="empty-state">No sales data found for the selected filters.</div>
        <?php endif; ?>
    </section>

    <footer class="report-footer">
        Mizpah Wellness Spa · Sales Report · Generated <?= date('M d, Y h:i A') ?>
    </footer>
</main>

<script>
<?php if ($hasChartData): ?>
const salesLabels = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const salesData = <?= json_encode($data) ?>;

const canvas = document.getElementById('salesChart');

new Chart(canvas, {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Daily Sales (₱)',
            data: salesData,
            borderColor: '#b39b6b',
            backgroundColor: 'rgba(179, 155, 107, 0.12)',
            fill: true,
            tension: 0.3,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#d6c29c'
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
                labels: {
                    color: '#b7b0a4',
                    font: { family: 'Poppins', size: 11 }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return ' ₱' + Number(context.raw).toLocaleString(
                            'en-PH',
                            { minimumFractionDigits: 2, maximumFractionDigits: 2 }
                        );
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#a7a197',
                    maxRotation: 45,
                    minRotation: 0,
                    autoSkip: true
                },
                grid: { color: 'rgba(150, 140, 120, 0.08)' }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#a7a197',
                    callback: function(value) {
                        return '₱' + Number(value).toLocaleString('en-PH');
                    }
                },
                grid: { color: 'rgba(150, 140, 120, 0.12)' }
            }
        }
    }
});

/* Use dark UI colors on screen and readable colors when printing. */
window.addEventListener('beforeprint', function() {
    const chart = Chart.getChart(canvas);
    if (!chart) return;

    chart.options.plugins.legend.labels.color = '#333';
    chart.options.scales.x.ticks.color = '#333';
    chart.options.scales.y.ticks.color = '#333';
    chart.options.scales.x.grid.color = 'rgba(0,0,0,0.08)';
    chart.options.scales.y.grid.color = 'rgba(0,0,0,0.08)';
    chart.resize();
    chart.update('none');
});

window.addEventListener('afterprint', function() {
    const chart = Chart.getChart(canvas);
    if (!chart) return;

    chart.options.plugins.legend.labels.color = '#b7b0a4';
    chart.options.scales.x.ticks.color = '#a7a197';
    chart.options.scales.y.ticks.color = '#a7a197';
    chart.options.scales.x.grid.color = 'rgba(150, 140, 120, 0.08)';
    chart.options.scales.y.grid.color = 'rgba(150, 140, 120, 0.12)';
    chart.resize();
    chart.update('none');
});
<?php endif; ?>
</script>

</body>
</html>
