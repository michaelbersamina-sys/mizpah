
<?php
session_start();
include __DIR__ . '/../includes/db.php';

if (!isset($conn)) {
    die("DB connection failed");
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* ================= REMOVE SERVICE ================= */
if (isset($_GET['remove'])) {
    $id = (int) $_GET['remove'];

    mysqli_query($conn, "DELETE FROM service_durations WHERE service_id='$id'");
    mysqli_query($conn, "DELETE FROM services WHERE id='$id'");

    echo "<script>alert('Service Removed');window.location='services.php';</script>";
    exit;
}

/* ================= ADD SERVICE ================= */
if (isset($_POST['add_service'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['service_name'] ?? ''));
    $desc = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));
    $category = trim(mysqli_real_escape_string($conn, $_POST['category'] ?? 'Massage'));

    mysqli_query($conn, "
        INSERT INTO services (service_name, description, category)
        VALUES ('$name','$desc','$category')
    ");

    $service_id = mysqli_insert_id($conn);

    if (!empty($_POST['price'])) {
        foreach ($_POST['price'] as $i => $price) {
            $dur = trim($_POST['duration'][$i] ?? '');
            $price = trim($price);

            if ($price !== '' && is_numeric($price) && (float)$price >= 0) {
                $dur = mysqli_real_escape_string($conn, $dur);
                $price = (float)$price;

                mysqli_query($conn, "
                    INSERT INTO service_durations (service_id, duration, price)
                    VALUES ('$service_id','$dur','$price')
                ");
            }
        }
    }

    echo "<script>alert('Service Added');window.location='services.php';</script>";
    exit;
}

/* ================= UPDATE SERVICE ================= */
if (isset($_POST['update_service'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim(mysqli_real_escape_string($conn, $_POST['service_name'] ?? ''));
    $desc = trim(mysqli_real_escape_string($conn, $_POST['description'] ?? ''));
    $category = trim(mysqli_real_escape_string($conn, $_POST['category'] ?? 'Massage'));

    mysqli_query($conn, "
        UPDATE services SET
            service_name='$name',
            description='$desc',
            category='$category'
        WHERE id='$id'
    ");

    mysqli_query($conn, "DELETE FROM service_durations WHERE service_id='$id'");

    if (!empty($_POST['price'])) {
        foreach ($_POST['price'] as $i => $price) {
            $dur = trim($_POST['duration'][$i] ?? '');
            $price = trim($price);

            if ($price !== '' && is_numeric($price) && (float)$price >= 0) {
                $dur = mysqli_real_escape_string($conn, $dur);
                $price = (float)$price;

                mysqli_query($conn, "
                    INSERT INTO service_durations (service_id, duration, price)
                    VALUES ('$id','$dur','$price')
                ");
            }
        }
    }

    echo "<script>alert('Service Updated');window.location='services.php';</script>";
    exit;
}

/* ================= DATA ================= */
$services = mysqli_query($conn, "SELECT * FROM services ORDER BY id DESC");

$totalServices = 0;
$categoryCounts = [
    'Massage' => 0,
    'Package' => 0,
    'Add-ons' => 0,
    'Promo' => 0
];

if ($services) {
    $totalServices = mysqli_num_rows($services);

    while ($serviceRow = mysqli_fetch_assoc($services)) {
        $cat = $serviceRow['category'] ?? '';
        if (isset($categoryCounts[$cat])) {
            $categoryCounts[$cat]++;
        }
    }

    mysqli_data_seek($services, 0);
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Services Management | Mizpah Wellness Spa</title>

<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #0b0b0b;
    --panel: #131313;
    --panel-light: #191919;
    --border: #2b2925;
    --gold: #d6c29c;
    --gold-muted: #a99673;
    --text: #f5f1e9;
    --muted: #a7a197;
    --danger: #d78c83;
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
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 30px;
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
    line-height: 1.7;
}

.primary-btn,
.secondary-btn,
.edit-btn,
.remove-btn,
.close-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 38px;
    padding: 9px 14px;
    border-radius: 7px;
    font-family: inherit;
    font-size: 11px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.primary-btn {
    background: var(--gold);
    color: #17140f;
    border: 1px solid var(--gold);
}

.secondary-btn {
    background: #1a1a1a;
    color: var(--text);
    border: 1px solid #37332c;
}

.primary-btn:hover,
.secondary-btn:hover,
.edit-btn:hover,
.remove-btn:hover,
.close-btn:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 22px;
}

.stat-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 18px;
    min-width: 0;
}

.stat-label {
    color: var(--muted);
    font-size: 10px;
    margin-bottom: 10px;
}

.stat-value {
    color: var(--gold);
    font-size: 26px;
    font-weight: 500;
}

.stat-note {
    color: #79736a;
    font-size: 10px;
    margin-top: 6px;
}

.services-panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
}

.panel-header {
    padding: 20px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    border-bottom: 1px solid var(--border);
}

.panel-title {
    color: var(--gold);
    font-size: 14px;
    font-weight: 500;
    margin: 0;
}

.panel-description {
    color: var(--muted);
    font-size: 10px;
    margin-top: 5px;
}

.table-tools {
    display: flex;
    align-items: center;
    gap: 10px;
}

.search-box {
    width: 220px;
    max-width: 100%;
    height: 38px;
    border: 1px solid #37332c;
    background: #0e0e0e;
    color: var(--text);
    border-radius: 7px;
    padding: 0 12px;
    font: 11px 'Poppins', sans-serif;
    outline: none;
}

.search-box:focus {
    border-color: var(--gold-muted);
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 820px;
}

thead {
    background: #171613;
}

th {
    padding: 14px 17px;
    color: var(--gold-muted);
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .7px;
    border-bottom: 1px solid var(--border);
}

td {
    padding: 16px 17px;
    color: #ded9cf;
    font-size: 11px;
    line-height: 1.7;
    vertical-align: top;
    border-bottom: 1px solid #242320;
}

tbody tr {
    transition: background .15s ease;
}

tbody tr:hover {
    background: #191815;
}

tbody tr:last-child td {
    border-bottom: none;
}

.service-name {
    color: var(--text);
    font-size: 12px;
    font-weight: 500;
    max-width: 190px;
    overflow-wrap: anywhere;
}

.service-id {
    color: #777168;
    font-size: 10px;
    margin-top: 5px;
}

.category-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    background: #211d16;
    color: var(--gold);
    border: 1px solid #443a28;
    border-radius: 5px;
    font-size: 9px;
    white-space: nowrap;
}

.description {
    color: #b5afa4;
    max-width: 250px;
    overflow-wrap: anywhere;
}

.price-list {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 130px;
}

.price-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 6px;
    border-bottom: 1px dashed #34312a;
}

.price-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.duration {
    color: #b8b0a2;
    font-size: 10px;
}

.price {
    color: var(--gold);
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}

.no-price {
    color: #777168;
    font-size: 10px;
    font-style: italic;
}

.actions {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.edit-btn {
    min-height: 31px;
    padding: 6px 10px;
    color: var(--gold);
    background: #211d16;
    border: 1px solid #49402f;
}

.remove-btn {
    min-height: 31px;
    padding: 6px 10px;
    color: var(--danger);
    background: #241615;
    border: 1px solid #50302d;
}

.empty-state {
    text-align: center;
    padding: 45px 20px;
    color: var(--muted);
    font-size: 12px;
}

.empty-state strong {
    display: block;
    color: var(--gold);
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
}

.table-footer {
    padding: 14px 20px;
    color: var(--muted);
    font-size: 10px;
    border-top: 1px solid var(--border);
}

/* ================= MODALS ================= */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .78);
    backdrop-filter: blur(5px);
    z-index: 9999;
    overflow-y: auto;
    padding: 25px 15px;
}

.modal-content {
    background: #141414;
    width: 100%;
    max-width: 560px;
    margin: 25px auto;
    border: 1px solid #39342a;
    border-radius: 12px;
    box-shadow: 0 22px 70px rgba(0, 0, 0, .55);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 15px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--border);
}

.modal-heading {
    color: var(--gold);
    font-family: 'Playfair Display', serif;
    font-size: 23px;
    font-weight: 600;
    margin: 0;
}

.modal-subtitle {
    color: var(--muted);
    font-size: 10px;
    margin-top: 6px;
}

.modal-x {
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    background: #222;
    border: 1px solid #39342a;
    color: var(--text);
    border-radius: 7px;
    font-size: 18px;
    cursor: pointer;
}

.modal-form {
    padding: 22px 24px 24px;
}

.form-field {
    margin-bottom: 16px;
}

.form-field label {
    display: block;
    color: #c8c0b2;
    font-size: 11px;
    margin-bottom: 7px;
}

.form-field input,
.form-field textarea,
.form-field select {
    display: block;
    width: 100%;
    min-height: 41px;
    padding: 10px 12px;
    background: #0d0d0d;
    color: var(--text);
    border: 1px solid #36322b;
    border-radius: 7px;
    outline: none;
    font: 11px 'Poppins', sans-serif;
}

.form-field textarea {
    min-height: 90px;
    resize: vertical;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    border-color: var(--gold-muted);
}

.form-field select {
    color-scheme: dark;
}

.form-section-title {
    color: var(--gold);
    font-size: 12px;
    font-weight: 500;
    margin: 22px 0 10px;
}

.form-hint {
    color: #817a6f;
    font-size: 10px;
    line-height: 1.6;
    margin-top: 7px;
}

.price-wrap {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.addrow {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 35px;
    gap: 9px;
    align-items: center;
}

.addrow input {
    width: 100%;
    min-width: 0;
    height: 39px;
    padding: 8px 10px;
    background: #0d0d0d;
    color: var(--text);
    border: 1px solid #36322b;
    border-radius: 7px;
    font: 11px 'Poppins', sans-serif;
    outline: none;
}

.addrow input:focus {
    border-color: var(--gold-muted);
}

.remove-row-btn {
    height: 35px;
    width: 35px;
    background: #241615;
    color: var(--danger);
    border: 1px solid #50302d;
    border-radius: 7px;
    cursor: pointer;
}

.add-row-btn {
    margin-top: 10px;
    padding: 8px 11px;
    background: #211d16;
    color: var(--gold);
    border: 1px solid #49402f;
    border-radius: 7px;
    font: 10px 'Poppins', sans-serif;
    cursor: pointer;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 24px;
    padding-top: 17px;
    border-top: 1px solid var(--border);
}

@media (max-width: 1100px) {
    .main {
        padding: 22px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .main {
        margin-left: 0;
        width: 100%;
        padding: 18px 13px;
    }

    .page-header {
        align-items: flex-start;
    }

    .page-header > .primary-btn {
        width: 100%;
    }

    .panel-header {
        padding: 16px;
        align-items: stretch;
    }

    .table-tools {
        width: 100%;
    }

    .search-box {
        width: 100%;
        flex: 1;
    }

    .stats-grid {
        gap: 9px;
    }

    .stat-card {
        padding: 14px;
    }

    .stat-value {
        font-size: 22px;
    }

    .modal {
        padding: 10px;
    }

    .modal-content {
        margin: 10px auto;
    }

    .modal-header,
    .modal-form {
        padding: 18px;
    }
}

@media (max-width: 400px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }

    .addrow {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 30px;
        gap: 5px;
    }

    .addrow input {
        padding: 7px 6px;
        font-size: 10px;
    }

    .remove-row-btn {
        width: 30px;
        height: 35px;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="main">

    <header class="page-header">
        <div>
            <div class="eyebrow">Mizpah Wellness Spa · Administration</div>
            <h1>Services Management</h1>
            <div class="subtitle">
                Manage service details, categories, pricing, and duration options.
            </div>
        </div>

        <button type="button" class="primary-btn" onclick="openAddModal()">
            <span style="font-size:16px;">+</span> Add New Service
        </button>
    </header>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-label">Total Services</div>
            <div class="stat-value"><?= number_format($totalServices) ?></div>
            <div class="stat-note">All listed services</div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Massage</div>
            <div class="stat-value"><?= number_format($categoryCounts['Massage']) ?></div>
            <div class="stat-note">Massage services</div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Packages</div>
            <div class="stat-value"><?= number_format($categoryCounts['Package']) ?></div>
            <div class="stat-note">Available packages</div>
        </article>

        <article class="stat-card">
            <div class="stat-label">Promos & Add-ons</div>
            <div class="stat-value">
                <?= number_format($categoryCounts['Promo'] + $categoryCounts['Add-ons']) ?>
            </div>
            <div class="stat-note">Promotions and extras</div>
        </article>
    </section>

    <section class="services-panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Service Catalogue</h2>
                <div class="panel-description">
                    View and manage all configured spa services.
                </div>
            </div>

            <div class="table-tools">
                <input
                    type="search"
                    id="serviceSearch"
                    class="search-box"
                    placeholder="Search services..."
                    aria-label="Search services"
                    oninput="filterServices()"
                >
            </div>
        </div>

        <div class="table-wrap">
            <table id="servicesTable">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Price / Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($services && mysqli_num_rows($services) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($services)): ?>
                        <?php
                        $durations = mysqli_query(
                            $conn,
                            "SELECT * FROM service_durations WHERE service_id=" . (int)$row['id'] . " ORDER BY id ASC"
                        );

                        $durationData = [];
                        if ($durations) {
                            while ($d = mysqli_fetch_assoc($durations)) {
                                $durationData[] = [
                                    'duration' => $d['duration'] ?? '',
                                    'price' => $d['price'] ?? ''
                                ];
                            }
                        }

                        $editData = [
                            'id' => (int)$row['id'],
                            'name' => $row['service_name'] ?? '',
                            'description' => $row['description'] ?? '',
                            'category' => trim($row['category'] ?? ''),
                            'durations' => $durationData
                        ];
                        ?>
                        <tr class="service-row-data">
                            <td>
                                <div class="service-name"><?= h($row['service_name']) ?></div>
                                <div class="service-id">Service ID: #<?= (int)$row['id'] ?></div>
                            </td>

                            <td>
                                <span class="category-badge"><?= h($row['category']) ?></span>
                            </td>

                            <td>
                                <div class="description">
                                    <?= trim($row['description'] ?? '') !== ''
                                        ? nl2br(h($row['description']))
                                        : '<span style="color:#777168;">No description</span>' ?>
                                </div>
                            </td>

                            <td>
                                <?php if (count($durationData) > 0): ?>
                                    <div class="price-list">
                                        <?php foreach ($durationData as $d): ?>
                                            <div class="price-item">
                                                <span class="duration">
                                                    <?= h(trim($d['duration']) !== '' ? $d['duration'] : 'One-time') ?>
                                                </span>
                                                <span class="price">
                                                    ₱<?= number_format((float)$d['price'], 2) ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-price">No pricing set</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="actions">
                                    <button
                                        type="button"
                                        class="edit-btn"
                                        onclick='openEditModal(<?= json_encode(
                                            $editData,
                                            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                        ) ?>)'
                                    >Edit</button>

                                    <a
                                        href="?remove=<?= (int)$row['id'] ?>"
                                        class="remove-btn"
                                        onclick="return confirm('Remove this service and its pricing options?')"
                                    >Remove</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <tr id="noSearchResults" style="display:none;">
                        <td colspan="5">
                            <div class="empty-state">
                                <strong>No matching services</strong>
                                Try another service name, category, or description.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <strong>No services available yet</strong>
                                Add your first service to begin managing the catalogue.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer" id="tableFooter">
            Showing <?= number_format($totalServices) ?> service<?= $totalServices === 1 ? '' : 's' ?>
        </div>
    </section>

</main>

<!-- ================= ADD MODAL ================= -->
<div class="modal" id="addModal" role="dialog" aria-modal="true" aria-labelledby="addTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h2 class="modal-heading" id="addTitle">Add Service</h2>
                <div class="modal-subtitle">Enter the details for a new spa service.</div>
            </div>
            <button type="button" class="modal-x" onclick="closeModal('addModal')" aria-label="Close">&times;</button>
        </div>

        <form method="POST" class="modal-form">
            <div class="form-field">
                <label for="addName">Service Name *</label>
                <input id="addName" name="service_name" placeholder="e.g. Swedish Massage" required>
            </div>

            <div class="form-field">
                <label for="addDescription">Description</label>
                <textarea id="addDescription" name="description" placeholder="Describe the service..."></textarea>
            </div>

            <div class="form-field">
                <label for="addCategory">Category</label>
                <select id="addCategory" name="category">
                    <option value="Massage">Massage</option>
                    <option value="Package">Package</option>
                    <option value="Add-ons">Add-ons</option>
                    <option value="Promo">Promo</option>
                </select>
            </div>

            <h3 class="form-section-title">Pricing & Duration</h3>
            <div class="form-hint">Add one or more duration and price options. Leave the price blank to skip an option.</div>

            <div class="price-wrap" id="addPriceWrap">
                <div class="addrow">
                    <input name="duration[]" placeholder="Duration (e.g. 1 hour)">
                    <input name="price[]" type="number" min="0" step="0.01" placeholder="Price (₱)">
                    <button type="button" class="remove-row-btn" onclick="removePriceRow(this)" title="Remove row" aria-label="Remove row">×</button>
                </div>
            </div>

            <button type="button" class="add-row-btn" onclick="addPriceRow('addPriceWrap')">+ Add price option</button>

            <div class="modal-actions">
                <button type="button" class="secondary-btn" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" name="add_service" class="primary-btn">Save Service</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h2 class="modal-heading" id="editTitle">Edit Service</h2>
                <div class="modal-subtitle">Update the service details and pricing options.</div>
            </div>
            <button type="button" class="modal-x" onclick="closeModal('editModal')" aria-label="Close">&times;</button>
        </div>

        <form method="POST" class="modal-form">
            <input type="hidden" name="id" id="eid">

            <div class="form-field">
                <label for="ename">Service Name *</label>
                <input name="service_name" id="ename" required>
            </div>

            <div class="form-field">
                <label for="edesc">Description</label>
                <textarea name="description" id="edesc"></textarea>
            </div>

            <div class="form-field">
                <label for="ecat">Category</label>
                <select name="category" id="ecat">
                    <option value="Massage">Massage</option>
                    <option value="Package">Package</option>
                    <option value="Add-ons">Add-ons</option>
                    <option value="Promo">Promo</option>
                </select>
            </div>

            <h3 class="form-section-title">Pricing & Duration</h3>
            <div class="form-hint">Existing pricing options are loaded here. Updating the service replaces its current price options.</div>

            <div class="price-wrap" id="editPriceWrap"></div>
            <button type="button" class="add-row-btn" onclick="addPriceRow('editPriceWrap')">+ Add price option</button>

            <div class="modal-actions">
                <button type="button" class="secondary-btn" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="update_service" class="primary-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function openEditModal(service) {
    document.getElementById('eid').value = service.id;
    document.getElementById('ename').value = service.name || '';
    document.getElementById('edesc').value = service.description || '';
    document.getElementById('ecat').value = service.category || 'Massage';

    const wrap = document.getElementById('editPriceWrap');
    wrap.innerHTML = '';

    if (Array.isArray(service.durations) && service.durations.length > 0) {
        service.durations.forEach(function(item) {
            addPriceRow('editPriceWrap', item.duration, item.price);
        });
    } else {
        addPriceRow('editPriceWrap');
    }

    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}

function addPriceRow(wrapId, duration = '', price = '') {
    const wrap = document.getElementById(wrapId);
    const row = document.createElement('div');
    row.className = 'addrow';

    const durationInput = document.createElement('input');
    durationInput.name = 'duration[]';
    durationInput.placeholder = 'Duration (e.g. 1 hour)';
    durationInput.value = duration ?? '';

    const priceInput = document.createElement('input');
    priceInput.name = 'price[]';
    priceInput.type = 'number';
    priceInput.min = '0';
    priceInput.step = '0.01';
    priceInput.placeholder = 'Price (₱)';
    priceInput.value = price ?? '';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove-row-btn';
    removeButton.title = 'Remove row';
    removeButton.setAttribute('aria-label', 'Remove price option');
    removeButton.textContent = '×';
    removeButton.addEventListener('click', function() {
        removePriceRow(removeButton);
    });

    row.appendChild(durationInput);
    row.appendChild(priceInput);
    row.appendChild(removeButton);
    wrap.appendChild(row);
}

function removePriceRow(button) {
    const row = button.closest('.addrow');
    const wrap = row.parentElement;

    if (wrap.querySelectorAll('.addrow').length > 1) {
        row.remove();
    } else {
        row.querySelectorAll('input').forEach(function(input) {
            input.value = '';
        });
    }
}

function filterServices() {
    const query = document.getElementById('serviceSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#servicesTable tbody tr.service-row-data');
    let visible = 0;

    rows.forEach(function(row) {
        const matches = row.textContent.toLowerCase().includes(query);
        row.style.display = matches ? '' : 'none';
        if (matches) visible++;
    });

    const empty = document.getElementById('noSearchResults');
    if (empty) {
        empty.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    }

    document.getElementById('tableFooter').textContent =
        'Showing ' + visible + ' of ' + rows.length + ' services';
}

document.querySelectorAll('.modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(function(modal) {
            if (modal.style.display === 'block') {
                closeModal(modal.id);
            }
        });
    }
});
</script>

</body>
</html>
