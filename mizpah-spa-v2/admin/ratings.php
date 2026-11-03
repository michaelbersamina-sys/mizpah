
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ================= HIDE ================= */
if (isset($_GET['hide'])) {
    $id = (int) $_GET['hide'];

    mysqli_query($conn, "
        UPDATE ratings
        SET status='hidden'
        WHERE id='$id'
    ");

    header("Location: ratings.php");
    exit;
}

/* ================= SHOW ================= */
if (isset($_GET['show'])) {
    $id = (int) $_GET['show'];

    mysqli_query($conn, "
        UPDATE ratings
        SET status='shown'
        WHERE id='$id'
    ");

    header("Location: ratings.php");
    exit;
}

/* ================= REMOVE ================= */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    mysqli_query($conn, "
        DELETE FROM ratings
        WHERE id='$id'
    ");

    header("Location: ratings.php");
    exit;
}

/* ================= FETCH ================= */
$query = mysqli_query($conn, "
    SELECT * FROM ratings
    ORDER BY id DESC
");

/* ================= SUMMARY ================= */
$totalRatings = 0;
$shownRatings = 0;
$hiddenRatings = 0;
$ratingSum = 0;

$ratings = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $ratings[] = $row;
        $totalRatings++;

        if (($row['status'] ?? '') === 'shown') {
            $shownRatings++;
        } else {
            $hiddenRatings++;
        }

        $ratingSum += (int) ($row['rating'] ?? 0);
    }
}

$averageRating = $totalRatings > 0
    ? number_format($ratingSum / $totalRatings, 1)
    : '0.0';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Ratings | Mizpah Admin</title>
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
    --red: #ff8585;
    --orange: #e8b66c;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: Poppins, Arial, sans-serif;
}

.main {
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.page-title {
    margin: 0;
    font-size: 27px;
    font-weight: 600;
    letter-spacing: -.4px;
}

.page-subtitle {
    color: var(--muted);
    margin: 8px 0 0;
    font-size: 13px;
}

.gold-text {
    color: var(--gold);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 26px;
}

.summary-card {
    background: linear-gradient(145deg, #191714, #111111);
    border: 1px solid var(--border);
    border-radius: 13px;
    padding: 20px;
    min-width: 0;
}

.summary-label {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 13px;
}

.summary-value {
    color: var(--gold);
    font-size: 27px;
    font-weight: 600;
}

.summary-note {
    color: #777064;
    font-size: 11px;
    margin-top: 7px;
}

.content-panel {
    background: #111111;
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
}

.panel-header {
    padding: 21px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    border-bottom: 1px solid var(--border);
}

.panel-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.panel-description {
    color: var(--muted);
    font-size: 12px;
    margin: 6px 0 0;
}

.search-box {
    width: 260px;
    max-width: 100%;
    background: #191919;
    color: var(--text);
    border: 1px solid #373126;
    border-radius: 8px;
    padding: 11px 13px;
    outline: none;
    font-size: 12px;
}

.search-box:focus {
    border-color: var(--gold-dark);
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}

thead {
    background: #171512;
}

th {
    text-align: left;
    padding: 15px 18px;
    color: var(--gold);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .7px;
    white-space: nowrap;
}

td {
    padding: 17px 18px;
    border-top: 1px solid rgba(255,255,255,.045);
    font-size: 12px;
    vertical-align: middle;
}

tbody tr {
    transition: background .2s ease;
}

tbody tr:hover {
    background: rgba(214,194,156,.035);
}

.customer-name {
    color: #f3eee5;
    font-weight: 500;
    max-width: 180px;
    overflow-wrap: anywhere;
}

.customer-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #2b2419;
    color: var(--gold);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
    flex-shrink: 0;
}

.customer-cell {
    display: flex;
    align-items: center;
    gap: 11px;
}

.stars {
    color: #e6bd67;
    font-size: 16px;
    letter-spacing: 1px;
    white-space: nowrap;
}

.rating-number {
    display: block;
    color: var(--muted);
    font-size: 10px;
    margin-top: 4px;
    letter-spacing: 0;
}

.message-cell {
    max-width: 330px;
    min-width: 180px;
    color: #c6beb1;
    line-height: 1.7;
    overflow-wrap: anywhere;
    white-space: pre-wrap;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.status-shown {
    color: var(--green);
    background: rgba(65,160,94,.12);
}

.status-hidden {
    color: var(--orange);
    background: rgba(232,182,108,.12);
}

.actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 11px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid transparent;
    transition: .2s ease;
    white-space: nowrap;
}

.btn:hover {
    filter: brightness(1.15);
    transform: translateY(-1px);
}

.btn-hide {
    color: var(--red);
    background: rgba(255,107,107,.09);
    border-color: rgba(255,107,107,.14);
}

.btn-show {
    color: var(--green);
    background: rgba(125,255,175,.08);
    border-color: rgba(125,255,175,.13);
}

.btn-remove {
    color: #e8a0a0;
    background: #241515;
    border-color: #462424;
}

.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 16px 22px;
    border-top: 1px solid var(--border);
    color: var(--muted);
    font-size: 11px;
}

.empty-state {
    padding: 55px 20px;
    text-align: center;
    color: var(--muted);
}

.empty-icon {
    font-size: 30px;
    color: var(--gold);
    margin-bottom: 12px;
}

.empty-state h3 {
    margin: 0 0 8px;
    color: var(--text);
    font-size: 15px;
}

.empty-state p {
    margin: 0;
    font-size: 12px;
}

@media (max-width: 1100px) {
    .main {
        padding: 22px;
    }

    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .panel-header {
        padding: 17px;
    }

    .search-box {
        width: 100%;
    }

    .summary-card {
        padding: 16px;
    }

    .summary-value {
        font-size: 23px;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1 class="page-title">Customer <span class="gold-text">Ratings</span></h1>
            <p class="page-subtitle">
                Manage customer reviews displayed on the Mizpah Wellness Spa website.
            </p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Reviews</div>
            <div class="summary-value"><?= $totalRatings ?></div>
            <div class="summary-note">All customer ratings</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Visible Reviews</div>
            <div class="summary-value"><?= $shownRatings ?></div>
            <div class="summary-note">Currently marked as shown</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Hidden Reviews</div>
            <div class="summary-value"><?= $hiddenRatings ?></div>
            <div class="summary-note">Not marked for display</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Average Rating</div>
            <div class="summary-value">
                <?= htmlspecialchars($averageRating) ?><span style="font-size:15px;color:#e6bd67;"> ★</span>
            </div>
            <div class="summary-note">Across all stored reviews</div>
        </div>
    </div>

    <div class="content-panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Review Management</h2>
                <p class="panel-description">Show or hide reviews, or permanently remove one.</p>
            </div>

            <input
                type="search"
                id="reviewSearch"
                class="search-box"
                placeholder="Search name or message..."
                aria-label="Search customer reviews"
            >
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody id="ratingsTable">
                <?php if (count($ratings) > 0) { ?>
                    <?php foreach ($ratings as $row) {
                        $name = (string) ($row['name'] ?? '');
                        $message = (string) ($row['message'] ?? '');
                        $rating = max(0, min(5, (int) ($row['rating'] ?? 0)));
                        $status = (string) ($row['status'] ?? 'hidden');
                        $id = (int) ($row['id'] ?? 0);
                        $initial = strtoupper(substr(trim($name), 0, 1));
                        if ($initial === '') {
                            $initial = '?';
                        }
                    ?>
                    <tr class="rating-row">
                        <td>
                            <div class="customer-cell">
                                <span class="customer-avatar">
                                    <?= htmlspecialchars($initial) ?>
                                </span>
                                <span class="customer-name">
                                    <?= htmlspecialchars($name) ?>
                                </span>
                            </div>
                        </td>

                        <td>
                            <span class="stars">
                                <?= str_repeat("★", $rating) ?><?= str_repeat("☆", 5 - $rating) ?>
                            </span>
                            <span class="rating-number"><?= $rating ?> out of 5</span>
                        </td>

                        <td class="message-cell">
                            <?= htmlspecialchars($message) ?>
                        </td>

                        <td>
                            <?php if ($status === 'shown') { ?>
                                <span class="status status-shown">Visible</span>
                            <?php } else { ?>
                                <span class="status status-hidden">Hidden</span>
                            <?php } ?>
                        </td>

                        <td>
                            <div class="actions">
                                <?php if ($status === 'shown') { ?>
                                    <a
                                        class="btn btn-hide"
                                        href="ratings.php?hide=<?= $id ?>"
                                        onclick="return confirm('Hide this customer review?')"
                                    >Hide</a>
                                <?php } else { ?>
                                    <a
                                        class="btn btn-show"
                                        href="ratings.php?show=<?= $id ?>"
                                        onclick="return confirm('Show this customer review?')"
                                    >Show</a>
                                <?php } ?>

                                <a
                                    class="btn btn-remove"
                                    href="ratings.php?delete=<?= $id ?>"
                                    onclick="return confirm('Remove this review permanently? This cannot be undone.')"
                                >Remove</a>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">★</div>
                                <h3>No customer reviews yet</h3>
                                <p>Customer ratings will appear here when available.</p>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span id="reviewCount">
                Showing <?= $totalRatings ?> review<?= $totalRatings === 1 ? '' : 's' ?>
            </span>
            <span>Ratings Management · Mizpah Wellness Spa</span>
        </div>
    </div>

</div>

<script>
const reviewSearch = document.getElementById('reviewSearch');
const reviewRows = document.querySelectorAll('.rating-row');
const reviewCount = document.getElementById('reviewCount');

reviewSearch.addEventListener('input', function () {
    const searchTerm = this.value.toLowerCase().trim();
    let visibleCount = 0;

    reviewRows.forEach(function (row) {
        const matches = row.textContent.toLowerCase().includes(searchTerm);
        row.style.display = matches ? '' : 'none';

        if (matches) {
            visibleCount++;
        }
    });

    reviewCount.textContent =
        'Showing ' + visibleCount + ' of ' + reviewRows.length + ' review' +
        (reviewRows.length === 1 ? '' : 's');
});
</script>

</body>
</html>
