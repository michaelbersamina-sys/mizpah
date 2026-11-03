
<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../includes/db.php';

$sections = [
    'signature' => 'Mizpah Signature Services',
    'package'   => 'Mizpah Packages',
    'popular'   => 'Popular Choices'
];

/* TOGGLE SHOWN / HIDDEN */
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);

    $check = mysqli_query(
        $conn,
        "SELECT status FROM landing_page_services WHERE id=$id LIMIT 1"
    );

    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $newStatus = ($row['status'] === 'shown') ? 'hidden' : 'shown';

        mysqli_query(
            $conn,
            "UPDATE landing_page_services SET status='$newStatus' WHERE id=$id"
        );
    }

    header("Location: landingpage.php");
    exit;
}

/* ADD SERVICE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $service_id = intval($_POST['service_id']);
    $section = $_POST['section'] ?? '';

    if ($service_id > 0 && array_key_exists($section, $sections)) {
        $sectionEscaped = mysqli_real_escape_string($conn, $section);

        $check = mysqli_query(
            $conn,
            "SELECT id FROM landing_page_services
             WHERE service_id=$service_id AND section='$sectionEscaped'
             LIMIT 1"
        );

        if (!$check || mysqli_num_rows($check) === 0) {
            $sortQ = mysqli_query(
                $conn,
                "SELECT COALESCE(MAX(sort_order),0) + 1 AS next_order
                 FROM landing_page_services
                 WHERE section='$sectionEscaped'"
            );

            $sortRow = $sortQ ? mysqli_fetch_assoc($sortQ) : ['next_order' => 1];
            $nextOrder = intval($sortRow['next_order']);

            mysqli_query(
                $conn,
                "INSERT INTO landing_page_services
                 (service_id, section, sort_order, status)
                 VALUES ($service_id, '$sectionEscaped', $nextOrder, 'shown')"
            );
        }
    }

    header("Location: landingpage.php");
    exit;
}

/* DEFAULT SIGNATURE SERVICES */
$signatureCheck = mysqli_query(
    $conn,
    "SELECT id FROM landing_page_services
     WHERE section='signature' LIMIT 1"
);

if ($signatureCheck && mysqli_num_rows($signatureCheck) === 0) {
    $defaultServices = [
        'Swedish Massage',
        'MIZPAH Signature',
        'Lymphatic Massage'
    ];

    $order = 1;

    foreach ($defaultServices as $serviceName) {
        $serviceNameEscaped = mysqli_real_escape_string($conn, $serviceName);

        $serviceQ = mysqli_query(
            $conn,
            "SELECT id FROM services
             WHERE service_name='$serviceNameEscaped' LIMIT 1"
        );

        if ($serviceQ && mysqli_num_rows($serviceQ) > 0) {
            $serviceRow = mysqli_fetch_assoc($serviceQ);
            $serviceId = intval($serviceRow['id']);

            mysqli_query(
                $conn,
                "INSERT IGNORE INTO landing_page_services
                 (service_id, section, sort_order, status)
                 VALUES ($serviceId, 'signature', $order, 'shown')"
            );

            $order++;
        }
    }
}

/* GET ALL SERVICES */
$services = [];

$servicesQ = mysqli_query(
    $conn,
    "SELECT id, service_name, category, description
     FROM services ORDER BY service_name ASC"
);

if ($servicesQ) {
    while ($row = mysqli_fetch_assoc($servicesQ)) {
        $services[] = $row;
    }
}

/* GET LANDING PAGE ITEMS - INCLUDE HIDDEN ITEMS */
$landingItems = [
    'signature' => [],
    'package'   => [],
    'popular'   => []
];

foreach ($sections as $sectionKey => $sectionTitle) {
    $sectionEscaped = mysqli_real_escape_string($conn, $sectionKey);

    $landingQ = mysqli_query(
        $conn,
        "SELECT
            lps.id AS landing_id,
            lps.service_id,
            lps.section,
            lps.sort_order,
            lps.status,
            s.service_name,
            s.category,
            s.description
         FROM landing_page_services lps
         INNER JOIN services s ON s.id = lps.service_id
         WHERE lps.section='$sectionEscaped'
         ORDER BY lps.sort_order ASC, lps.id ASC"
    );

    if ($landingQ) {
        while ($row = mysqli_fetch_assoc($landingQ)) {
            $landingItems[$sectionKey][] = $row;
        }
    }
}

/* SUMMARY COUNTS */
$totalItems = 0;
$shownItems = 0;
$hiddenItems = 0;

foreach ($landingItems as $items) {
    foreach ($items as $item) {
        $totalItems++;

        if ($item['status'] === 'shown') {
            $shownItems++;
        } else {
            $hiddenItems++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Landing Page Management | Mizpah Admin</title>

<style>
:root {
    --bg: #0b0b0b;
    --panel: #151515;
    --panel-hover: #1b1b1b;
    --bronze: #D6C29C;
    --bronze-light: #e5d5b5;
    --bronze-dark: #241a0d;
    --text: #f3f0e9;
    --muted: #99938a;
    --border: rgba(214,194,156,.17);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--bg);
    color: var(--text);
}

.main-content {
    margin-left: 280px;
    padding: 34px;
    min-height: 100vh;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 27px;
}

.page-title {
    color: var(--bronze);
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -.5px;
    margin: 0 0 9px;
}

.page-subtitle {
    color: var(--muted);
    font-size: 14px;
    line-height: 1.7;
    margin: 0;
}

.header-label {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 11px 14px;
    background: rgba(214,194,156,.05);
    border: 1px solid var(--border);
    border-radius: 9px;
    color: var(--bronze);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
    text-transform: uppercase;
    white-space: nowrap;
}

.header-label span {
    width: 7px;
    height: 7px;
    background: var(--bronze);
    border-radius: 50%;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 27px;
}

.stat-card {
    background: linear-gradient(145deg, #191919, #111);
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: 20px;
    min-width: 0;
}

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: var(--muted);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .7px;
}

.stat-icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    background: rgba(214,194,156,.08);
    border: 1px solid rgba(214,194,156,.1);
    border-radius: 9px;
    color: var(--bronze);
    font-size: 18px;
}

.stat-number {
    display: block;
    margin-top: 13px;
    color: #fff;
    font-size: 29px;
    font-weight: 700;
}

.stat-note {
    margin-top: 5px;
    color: #777;
    font-size: 12px;
}

/* SECTION CARDS */
.card {
    background: linear-gradient(145deg, #171717, #121212);
    border: 1px solid var(--border);
    border-radius: 15px;
    margin-bottom: 24px;
    overflow: hidden;
    box-shadow: 0 10px 28px rgba(0,0,0,.2);
    transition: border-color .2s ease;
}

.card:hover {
    border-color: rgba(214,194,156,.28);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 22px 24px;
    border-bottom: 1px solid rgba(255,255,255,.055);
    background: linear-gradient(90deg, rgba(214,194,156,.045), transparent 70%);
}

.card-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
}

.section-icon {
    width: 43px;
    height: 43px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: var(--bronze-dark);
    border: 1px solid rgba(214,194,156,.16);
    border-radius: 11px;
    color: var(--bronze);
    font-size: 20px;
}

.card-title {
    margin: 0;
    color: var(--bronze);
    font-size: 17px;
    font-weight: 700;
    line-height: 1.4;
}

.card-description {
    margin: 4px 0 0;
    color: #858078;
    font-size: 12px;
    line-height: 1.5;
}

.section-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 27px;
    padding: 0 8px;
    background: #242018;
    border: 1px solid rgba(214,194,156,.14);
    border-radius: 7px;
    color: var(--bronze);
    font-size: 12px;
    font-weight: 700;
}

.add-service-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-shrink: 0;
    background: var(--bronze);
    color: #15120c;
    border: 1px solid var(--bronze);
    padding: 11px 15px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s ease, transform .2s ease;
}

.add-service-btn:hover {
    background: var(--bronze-light);
    transform: translateY(-1px);
}

/* TABLE */
.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 540px;
}

thead {
    background: #20180d;
}

th {
    padding: 14px 20px;
    text-align: left;
    color: var(--bronze);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    white-space: nowrap;
}

td {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,.055);
    vertical-align: middle;
}

tbody tr {
    transition: background .15s ease;
}

tbody tr:hover {
    background: rgba(214,194,156,.035);
}

tbody tr:last-child td {
    border-bottom: 0;
}

.service-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 180px;
}

.service-avatar {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: linear-gradient(145deg, #2a2114, #17130d);
    border: 1px solid rgba(214,194,156,.15);
    border-radius: 10px;
    color: var(--bronze);
    font-size: 14px;
    font-weight: 700;
}

.service-name {
    color: #f5f2ec;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
}

.service-description {
    color: #817d75;
    font-size: 11px;
    line-height: 1.5;
    margin-top: 3px;
    max-width: 330px;
}

.category-pill {
    display: inline-block;
    padding: 6px 9px;
    border: 1px solid #35302a;
    border-radius: 7px;
    background: #201d18;
    color: #bdb4a4;
    font-size: 11px;
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    border-radius: 7px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    white-space: nowrap;
}

.status-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-shown {
    background: rgba(125,165,118,.1);
    color: #b8d8b4;
    border: 1px solid rgba(125,165,118,.2);
}

.status-hidden {
    background: #242424;
    color: #a7a7a7;
    border: 1px solid #383838;
}

.action-cell {
    text-align: right;
    white-space: nowrap;
}

.toggle-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    transition: all .18s ease;
    border: 1px solid transparent;
}

.toggle-hide {
    background: #252525;
    border-color: #3b3b3b;
    color: #d2d2d2;
}

.toggle-hide:hover {
    background: #343434;
    border-color: #555;
}

.toggle-show {
    background: var(--bronze);
    color: #15120c;
}

.toggle-show:hover {
    background: var(--bronze-light);
}

/* EMPTY STATE */
.empty {
    padding: 36px 20px;
    text-align: center;
    color: #888;
}

.empty-icon {
    width: 46px;
    height: 46px;
    margin: 0 auto 12px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: var(--bronze);
    background: rgba(214,194,156,.07);
    font-size: 23px;
}

.empty strong {
    display: block;
    color: #d4cec3;
    font-size: 13px;
    margin-bottom: 6px;
}

.empty p {
    margin: 0;
    font-size: 12px;
    line-height: 1.6;
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.78);
    backdrop-filter: blur(5px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-box {
    width: 490px;
    max-width: 100%;
    background: #171717;
    border: 1px solid rgba(214,194,156,.23);
    border-radius: 16px;
    padding: 27px;
    position: relative;
    max-height: 90vh;
    overflow: auto;
    box-shadow: 0 25px 80px rgba(0,0,0,.55);
}

.modal-kicker {
    color: #94866f;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 9px;
}

.modal-box h2 {
    margin: 0 35px 8px 0;
    color: var(--bronze);
    font-size: 23px;
}

.modal-description {
    margin: 0 0 24px;
    color: #908b82;
    font-size: 13px;
    line-height: 1.6;
}

.close {
    position: absolute;
    right: 17px;
    top: 14px;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 1px solid #343434;
    border-radius: 9px;
    color: var(--bronze);
    font-size: 23px;
    cursor: pointer;
    background: #202020;
    line-height: 1;
}

.close:hover {
    background: #2b2b2b;
}

.form-group {
    margin-bottom: 19px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #d4cec3;
    font-size: 12px;
    font-weight: 700;
}

.form-group select {
    width: 100%;
    padding: 13px 12px;
    background: #0e0e0e;
    border: 1px solid #38342e;
    border-radius: 9px;
    color: #eee;
    font-size: 13px;
    outline: none;
    min-height: 45px;
}

.form-group select:focus {
    border-color: var(--bronze);
    box-shadow: 0 0 0 3px rgba(214,194,156,.08);
}

.form-help {
    font-size: 11px;
    color: #777;
    margin-top: 7px;
    line-height: 1.5;
}

.submit-btn {
    width: 100%;
    padding: 13px;
    background: var(--bronze);
    color: #15120c;
    border: 0;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 3px;
    transition: background .18s ease;
}

.submit-btn:hover {
    background: var(--bronze-light);
}

.cancel-btn {
    width: 100%;
    padding: 11px;
    margin-top: 9px;
    background: transparent;
    color: #aaa;
    border: 1px solid #333;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.cancel-btn:hover {
    background: #222;
    color: #eee;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .main-content {
        margin-left: 250px;
        padding: 26px;
    }

    .card {
        padding: 20px;
    }

    th, td {
        padding-left: 14px;
        padding-right: 14px;
    }
}

@media (max-width: 800px) {
    .main-content {
        margin-left: 0;
        padding: 22px 16px;
    }

    .page-header {
        flex-direction: column;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 11px;
    }

    .stat-card {
        padding: 16px 18px;
    }

    .stat-number {
        font-size: 25px;
    }

    .card {
        padding: 0;
    }

    .card-header {
        padding: 18px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .card-heading {
        flex: 1;
    }

    .add-service-btn {
        width: 100%;
    }

    .modal-box {
        padding: 22px;
    }
}
</style>
</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

    <div class="page-header">
        <div>
            <h1 class="page-title">Landing Page Management</h1>
            <p class="page-subtitle">
                Manage the services and packages displayed on your customer landing page.
            </p>
        </div>

        <div class="header-label">
            <span></span>
            Website Content
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-top">
                <span>Total Landing Items</span>
                <span class="stat-icon">▤</span>
            </div>
            <strong class="stat-number"><?= $totalItems ?></strong>
            <div class="stat-note">Items across all three sections</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Currently Shown</span>
                <span class="stat-icon">◉</span>
            </div>
            <strong class="stat-number"><?= $shownItems ?></strong>
            <div class="stat-note">Visible to website visitors</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span>Currently Hidden</span>
                <span class="stat-icon">◌</span>
            </div>
            <strong class="stat-number"><?= $hiddenItems ?></strong>
            <div class="stat-note">Can be shown again anytime</div>
        </div>

    </section>

    <?php
    $sectionIcons = [
        'signature' => '✦',
        'package'   => '◇',
        'popular'   => '★'
    ];

    $sectionDescriptions = [
        'signature' => 'Feature the spa’s signature treatments.',
        'package'   => 'Manage the packages promoted on the homepage.',
        'popular'   => 'Choose services highlighted as popular choices.'
    ];
    ?>

    <?php foreach ($sections as $sectionKey => $sectionTitle): ?>

    <section class="card">

        <div class="card-header">

            <div class="card-heading">

                <div class="section-icon">
                    <?= $sectionIcons[$sectionKey] ?>
                </div>

                <div>
                    <h2 class="card-title">
                        <?= htmlspecialchars($sectionTitle) ?>
                    </h2>

                    <p class="card-description">
                        <?= htmlspecialchars($sectionDescriptions[$sectionKey]) ?>
                    </p>
                </div>

                <span class="section-count">
                    <?= count($landingItems[$sectionKey]) ?>
                </span>

            </div>

            <button
                type="button"
                class="add-service-btn"
                onclick="openAddModal('<?= htmlspecialchars($sectionKey, ENT_QUOTES) ?>')"
            >
                <span aria-hidden="true">+</span>
                Add Service
            </button>

        </div>

        <?php if (!empty($landingItems[$sectionKey])): ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Service Details</th>
                        <th>Category</th>
                        <th>Visibility</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($landingItems[$sectionKey] as $item): ?>

                    <?php
                    $isShown = ($item['status'] === 'shown');
                    $initial = strtoupper(substr(trim($item['service_name']), 0, 1));
                    ?>

                    <tr>

                        <td>
                            <div class="service-cell">

                                <div class="service-avatar">
                                    <?= htmlspecialchars($initial) ?>
                                </div>

                                <div>
                                    <div class="service-name">
                                        <?= htmlspecialchars($item['service_name']) ?>
                                    </div>

                                    <?php if (!empty($item['description'])): ?>
                                        <div class="service-description">
                                            <?= htmlspecialchars($item['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </td>

                        <td>
                            <span class="category-pill">
                                <?= htmlspecialchars($item['category'] ?: 'Uncategorized') ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($isShown): ?>
                                <span class="status-badge status-shown">Shown</span>
                            <?php else: ?>
                                <span class="status-badge status-hidden">Hidden</span>
                            <?php endif; ?>
                        </td>

                        <td class="action-cell">

                            <?php if ($isShown): ?>

                                <a
                                    href="landingpage.php?toggle=<?= intval($item['landing_id']) ?>"
                                    class="toggle-btn toggle-hide"
                                    onclick="return confirm('Hide this service from the landing page?')"
                                >
                                    Hide
                                </a>

                            <?php else: ?>

                                <a
                                    href="landingpage.php?toggle=<?= intval($item['landing_id']) ?>"
                                    class="toggle-btn toggle-show"
                                    onclick="return confirm('Show this service on the landing page?')"
                                >
                                    Show
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <?php else: ?>

            <div class="empty">

                <div class="empty-icon">＋</div>

                <strong>No services in this section yet</strong>

                <p>
                    Use the “Add Service” button to choose a service for this homepage section.
                </p>

            </div>

        <?php endif; ?>

    </section>

    <?php endforeach; ?>

</main>

<!-- ADD SERVICE MODAL -->
<div class="modal" id="addModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

    <div class="modal-box">

        <button
            type="button"
            class="close"
            aria-label="Close dialog"
            onclick="closeAddModal()"
        >
            &times;
        </button>

        <div class="modal-kicker">Homepage Content</div>

        <h2 id="modalTitle">Add Service</h2>

        <p class="modal-description">
            Choose which service should appear in the selected landing page section.
        </p>

        <form method="POST">

            <input type="hidden" name="add_service" value="1">

            <input
                type="hidden"
                name="section"
                id="selectedSection"
                value=""
            >

            <div class="form-group">

                <label for="sectionDisplay">Landing Page Section</label>

                <select id="sectionDisplay" disabled>
                    <option value="signature">Mizpah Signature Services</option>
                    <option value="package">Mizpah Packages</option>
                    <option value="popular">Popular Choices</option>
                </select>

                <div class="form-help">
                    This is where the service will be listed.
                </div>

            </div>

            <div class="form-group">

                <label for="serviceSelect">Select Service</label>

                <select name="service_id" id="serviceSelect" required>

                    <option value="">— Select a service —</option>

                    <?php foreach ($services as $service): ?>

                        <option value="<?= intval($service['id']) ?>">
                            <?= htmlspecialchars($service['service_name']) ?>
                            <?php if (!empty($service['category'])): ?>
                                — <?= htmlspecialchars($service['category']) ?>
                            <?php endif; ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <button type="submit" class="submit-btn">
                Add to Landing Page
            </button>

            <button
                type="button"
                class="cancel-btn"
                onclick="closeAddModal()"
            >
                Cancel
            </button>

        </form>

    </div>

</div>

<script>
function openAddModal(section) {
    document.getElementById("selectedSection").value = section;
    document.getElementById("sectionDisplay").value = section;
    document.getElementById("serviceSelect").value = "";

    document.getElementById("addModal").style.display = "flex";
    document.body.style.overflow = "hidden";
    document.getElementById("serviceSelect").focus();
}

function closeAddModal() {
    document.getElementById("addModal").style.display = "none";
    document.body.style.overflow = "";
}

window.addEventListener("click", function(event) {
    const modal = document.getElementById("addModal");

    if (event.target === modal) {
        closeAddModal();
    }
});

window.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeAddModal();
    }
});
</script>

</body>
</html>
