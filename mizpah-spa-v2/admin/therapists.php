
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

/* =====================================
   DEFAULT COMMISSION
===================================== */
define('DEFAULT_COMMISSION', 0.60);

/* =====================================
   SAVE THERAPIST
===================================== */
if (isset($_POST['save_therapist'])) {

    $id           = intval($_POST['id'] ?? 0);
    $name         = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $specialty    = mysqli_real_escape_string($conn, $_POST['specialty'] ?? '');
    $best_service = mysqli_real_escape_string($conn, $_POST['best_service'] ?? '');
    $bio          = mysqli_real_escape_string($conn, $_POST['bio'] ?? '');
    $schedule     = mysqli_real_escape_string($conn, $_POST['schedule'] ?? '');
    $status       = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Active');
    $commission   = floatval($_POST['commission'] ?? 60) / 100;

    if ($commission <= 0) {
        $commission = DEFAULT_COMMISSION;
    }

    if ($id > 0) {

        mysqli_query($conn, "
            UPDATE therapists SET
            name='$name',
            specialty='$specialty',
            best_service='$best_service',
            bio='$bio',
            schedule='$schedule',
            status='$status',
            commission='$commission'
            WHERE id='$id'
        ");

    } else {

        mysqli_query($conn, "
            INSERT INTO therapists
            (name, specialty, best_service, bio, schedule, status, commission)
            VALUES
            ('$name', '$specialty', '$best_service', '$bio', '$schedule', '$status', '$commission')
        ");
    }

    header("Location: therapists.php");
    exit;
}

/* =====================================
   EDIT
===================================== */
$edit = null;

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $q = mysqli_query($conn, "SELECT * FROM therapists WHERE id='$id'");
    $edit = mysqli_fetch_assoc($q);
}

/* =====================================
   SHOW FORM?
===================================== */
$showForm = false;

if (isset($_GET['add']) || isset($_GET['edit'])) {
    $showForm = true;
}

/* =====================================
   LIST
===================================== */
$list = mysqli_query($conn, "
SELECT
t.*,

(
    SELECT COUNT(DISTINCT bt.booking_id)
    FROM booking_therapists bt
    JOIN bookings b ON b.id = bt.booking_id
    WHERE bt.therapist_id=t.id
    AND b.status='Completed'
) as sessions,

(
    SELECT IFNULL(SUM(
        (
            (b.price * b.pax)
            /
            (
                SELECT COUNT(*)
                FROM booking_therapists x
                WHERE x.booking_id = b.id
            )
        )
        * IFNULL(t.commission,0.60)
    ),0)
    FROM booking_therapists bt
    JOIN bookings b ON b.id = bt.booking_id
    WHERE bt.therapist_id=t.id
    AND b.status='Completed'
) as earnings

FROM therapists t
ORDER BY t.id DESC
");

/* COUNTS */
$total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM therapists
"))['total'];

$active = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM therapists WHERE status='Active'
"))['total'];

$inactive = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total FROM therapists WHERE status='Inactive'
"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Therapists Management | Mizpah Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">

<style>
:root {
    --bg: #0b0b0b;
    --panel: #141414;
    --panel-soft: #191714;
    --border: rgba(214,194,156,.14);
    --gold: #D6C29C;
    --gold-dark: #8d754d;
    --text: #f3eee5;
    --muted: #a39b8e;
    --green: #86d6a0;
    --red: #ff9292;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: Poppins, sans-serif;
}

.main {
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 25px;
}

.page-title {
    color: var(--gold);
    margin: 0;
    font-size: 27px;
    font-weight: 600;
    letter-spacing: -.4px;
}

.page-subtitle {
    color: var(--muted);
    font-size: 12px;
    margin: 8px 0 0;
    line-height: 1.6;
}

.btn {
    background: var(--gold);
    color: #15120d;
    padding: 11px 16px;
    border: 1px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 12px;
    text-decoration: none;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 7px;
    transition: .2s ease;
}

.btn:hover {
    filter: brightness(1.08);
    transform: translateY(-1px);
}

.btn-dark {
    background: #202020;
    border-color: #393329;
    color: var(--text);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 25px;
}

.summary-card {
    background: linear-gradient(145deg, #191714, #111111);
    border: 1px solid var(--border);
    padding: 21px;
    border-radius: 13px;
    position: relative;
    overflow: hidden;
}

.summary-card::after {
    content: "";
    position: absolute;
    width: 70px;
    height: 70px;
    right: -25px;
    top: -25px;
    border-radius: 50%;
    border: 1px solid rgba(214,194,156,.12);
}

.summary-label {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 12px;
}

.summary-value {
    font-size: 29px;
    font-weight: 600;
    color: var(--gold);
}

.summary-note {
    font-size: 11px;
    color: #777064;
    margin-top: 7px;
}

.card {
    background: #111111;
    border: 1px solid var(--border);
    padding: 23px;
    border-radius: 14px;
    margin-bottom: 22px;
}

.card-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 22px;
}

.card-heading h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
}

.card-heading p {
    color: var(--muted);
    font-size: 12px;
    margin: 6px 0 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 18px;
}

.form-group {
    min-width: 0;
    margin-bottom: 17px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    color: #d7cdbd;
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 8px;
}

input,
textarea,
select {
    width: 100%;
    padding: 12px 13px;
    background: #0d0d0d;
    border: 1px solid #35312b;
    color: var(--text);
    border-radius: 8px;
    outline: none;
    font-family: inherit;
    font-size: 12px;
    transition: border-color .2s ease;
}

input:focus,
textarea:focus,
select:focus {
    border-color: var(--gold-dark);
}

input::placeholder,
textarea::placeholder {
    color: #69635a;
}

textarea {
    min-height: 110px;
    resize: vertical;
    line-height: 1.7;
}

select option {
    background: #141414;
    color: var(--text);
}

.form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 4px;
}

.table-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.table-heading h3 {
    margin: 0;
    color: var(--text);
    font-size: 16px;
}

.table-heading p {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.table-search {
    width: 250px;
    max-width: 100%;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 950px;
    border-collapse: collapse;
}

thead {
    background: #191714;
}

th {
    color: var(--gold);
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .6px;
    white-space: nowrap;
    text-align: left;
    padding: 15px 13px;
}

td {
    padding: 16px 13px;
    border-bottom: 1px solid rgba(255,255,255,.055);
    font-size: 12px;
    vertical-align: middle;
}

tbody tr {
    transition: background .2s ease;
}

tbody tr:hover {
    background: rgba(214,194,156,.035);
}

.therapist-name {
    font-weight: 600;
    color: #f2eadc;
    overflow-wrap: anywhere;
}

.therapist-sub {
    display: block;
    margin-top: 5px;
    color: var(--muted);
    font-size: 10px;
    max-width: 190px;
    overflow-wrap: anywhere;
}

.specialty-text {
    color: #d0c5b3;
    max-width: 150px;
    overflow-wrap: anywhere;
}

.metric {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.commission {
    color: var(--gold);
    font-weight: 600;
}

.earnings {
    color: var(--green);
    font-weight: 600;
    white-space: nowrap;
}

.badge {
    padding: 7px 10px;
    border-radius: 30px;
    font-size: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.badge.Active {
    background: rgba(125,255,175,.09);
    color: #7dffaf;
}

.badge.Inactive {
    background: rgba(255,107,107,.09);
    color: #ff9e9e;
}

.edit-link {
    color: var(--gold);
    background: rgba(214,194,156,.08);
    border: 1px solid rgba(214,194,156,.16);
    padding: 8px 12px;
    border-radius: 7px;
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: .2s ease;
}

.edit-link:hover {
    background: rgba(214,194,156,.15);
}

.table-footer {
    margin-top: 16px;
    padding-top: 15px;
    border-top: 1px solid var(--border);
    color: var(--muted);
    font-size: 11px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.empty-state {
    padding: 35px 15px;
    text-align: center;
    color: var(--muted);
    font-size: 12px;
}

.small {
    font-size: 12px;
    color: var(--muted);
}

@media (max-width: 1100px) {
    .main {
        padding: 22px;
    }

    .summary-grid {
        gap: 12px;
    }

    .summary-card {
        padding: 17px;
    }
}

@media (max-width: 800px) {
    .main {
        margin-left: 0;
        padding: 18px 13px;
    }

    .page-title {
        font-size: 23px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 17px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .table-search {
        width: 100%;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main">

    <div class="topbar">
        <div>
            <h1 class="page-title">Therapists Management</h1>
            <p class="page-subtitle">
                Manage therapist profiles, schedules, commission rates, and performance.
            </p>
        </div>

        <?php if ($showForm): ?>
            <a href="therapists.php" class="btn btn-dark">Back to List</a>
        <?php else: ?>
            <a href="therapists.php?add=1" class="btn">+ Add Therapist</a>
        <?php endif; ?>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Therapists</div>
            <div class="summary-value"><?= (int)$total ?></div>
            <div class="summary-note">All registered therapists</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Active Therapists</div>
            <div class="summary-value"><?= (int)$active ?></div>
            <div class="summary-note">Currently marked as active</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Inactive Therapists</div>
            <div class="summary-value"><?= (int)$inactive ?></div>
            <div class="summary-note">Currently marked as inactive</div>
        </div>
    </div>

    <?php if ($showForm): ?>
    <div class="card">

        <div class="card-heading">
            <div>
                <h3><?= $edit ? 'Edit Therapist' : 'Add New Therapist' ?></h3>
                <p>
                    <?= $edit
                        ? 'Update the therapist information below.'
                        : 'Enter the details to register a therapist.' ?>
                </p>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="id"
                   value="<?= htmlspecialchars((string)($edit['id'] ?? '')) ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label for="name">Therapist Name *</label>
                    <input type="text" id="name" name="name" required
                           placeholder="Enter full name"
                           value="<?= htmlspecialchars($edit['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="specialty">Specialty</label>
                    <input type="text" id="specialty" name="specialty"
                           placeholder="e.g. Swedish Massage"
                           value="<?= htmlspecialchars($edit['specialty'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="best_service">Best Service</label>
                    <input type="text" id="best_service" name="best_service"
                           placeholder="Enter their best service"
                           value="<?= htmlspecialchars($edit['best_service'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="schedule">Schedule</label>
                    <input type="text" id="schedule" name="schedule"
                           placeholder="e.g. Mon-Sun 3PM-12MN"
                           value="<?= htmlspecialchars($edit['schedule'] ?? 'Mon-Sun 3PM-12MN') ?>">
                </div>

                <div class="form-group">
                    <label for="commission">Commission (%)</label>
                    <input type="number" id="commission" name="commission"
                           step="1" min="1" max="100"
                           value="<?= htmlspecialchars((string)(isset($edit['commission']) ? ($edit['commission'] * 100) : 60)) ?>">
                    <span class="small">Default commission: 60%</span>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Active"
                            <?= (($edit['status'] ?? '') == 'Active') ? 'selected' : '' ?>>
                            Active
                        </option>
                        <option value="Inactive"
                            <?= (($edit['status'] ?? '') == 'Inactive') ? 'selected' : '' ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="bio">Therapist Bio</label>
                    <textarea id="bio" name="bio"
                              placeholder="Write a short therapist biography..."><?= htmlspecialchars($edit['bio'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" name="save_therapist" class="btn">
                    <?= $edit ? 'Update Therapist' : 'Save Therapist' ?>
                </button>

                <a href="therapists.php" class="btn btn-dark">Cancel</a>
            </div>
        </form>

    </div>
    <?php endif; ?>

    <div class="card">

        <div class="table-heading">
            <div>
                <h3>Therapist Directory</h3>
                <p>Review completed sessions and earnings.</p>
            </div>

            <input type="search" id="therapistSearch"
                   class="table-search"
                   placeholder="Search therapist..."
                   aria-label="Search therapists">
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Therapist</th>
                        <th>Specialty</th>
                        <th>Sessions</th>
                        <th>Commission</th>
                        <th>Earnings</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="therapistTable">
                <?php
                $therapistCount = 0;

                if ($list) {
                    while ($row = mysqli_fetch_assoc($list)) {
                        $therapistCount++;
                        $therapistName = $row['name'] ?? '';
                        $bestService = $row['best_service'] ?? '';
                        $specialty = $row['specialty'] ?? '';
                        $statusClass = ($row['status'] ?? '') === 'Active'
                            ? 'Active'
                            : 'Inactive';
                ?>
                    <tr class="therapist-row">
                        <td>
                            <div class="therapist-name">
                                <?= htmlspecialchars($therapistName) ?>
                            </div>
                            <span class="therapist-sub">
                                <?= htmlspecialchars($bestService) ?>
                            </span>
                        </td>

                        <td>
                            <span class="specialty-text">
                                <?= htmlspecialchars($specialty) ?>
                            </span>
                        </td>

                        <td class="metric">
                            <?= (int)($row['sessions'] ?? 0) ?>
                        </td>

                        <td class="commission metric">
                            <?= number_format(((float)($row['commission'] ?? 0.60)) * 100, 0) ?>%
                        </td>

                        <td class="earnings metric">
                            ₱<?= number_format((float)($row['earnings'] ?? 0), 2) ?>
                        </td>

                        <td>
                            <span class="badge <?= $statusClass ?>">
                                <?= htmlspecialchars($statusClass) ?>
                            </span>
                        </td>

                        <td>
                            <a class="edit-link"
                               href="therapists.php?edit=<?= (int)$row['id'] ?>">
                                Edit
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                }

                if ($therapistCount === 0) {
                ?>
                    <tr id="emptyTherapists">
                        <td colspan="7">
                            <div class="empty-state">
                                No therapists found. Click “Add Therapist” to register one.
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span id="therapistCount">
                Showing <?= $therapistCount ?> therapist<?= $therapistCount === 1 ? '' : 's' ?>
            </span>
            <span>Mizpah Wellness Spa · Therapist Management</span>
        </div>

    </div>

</div>

<script>
const therapistSearch = document.getElementById('therapistSearch');
const therapistRows = document.querySelectorAll('.therapist-row');
const therapistCountLabel = document.getElementById('therapistCount');

therapistSearch.addEventListener('input', function () {
    const term = this.value.toLowerCase().trim();
    let visible = 0;

    therapistRows.forEach(function (row) {
        const matches = row.textContent.toLowerCase().includes(term);
        row.style.display = matches ? '' : 'none';

        if (matches) {
            visible++;
        }
    });

    therapistCountLabel.textContent =
        'Showing ' + visible + ' of ' + therapistRows.length +
        ' therapist' + (therapistRows.length === 1 ? '' : 's');
});
</script>

</body>
</html>
