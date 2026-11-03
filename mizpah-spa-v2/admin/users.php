
<?php
session_start();

include '../includes/db.php';

/* ================= SECURITY CHECK ================= */

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

/* force lowercase role */
$role = strtolower($_SESSION['role']);

/* only admin allowed */
if ($role !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ================= FETCH USERS ================= */

$query = mysqli_query($conn, "
    SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.created_at,
        COUNT(b.id) AS total_bookings,
        MAX(b.booking_date) AS last_booking
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    GROUP BY u.id
    ORDER BY u.id DESC
");

/* ================= SUMMARY COUNTS ================= */

$totalUsers = 0;
$totalAdmins = 0;
$totalCustomers = 0;
$users = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $users[] = $row;
        $totalUsers++;

        if (strtolower($row['role']) === 'admin') {
            $totalAdmins++;
        } else {
            $totalCustomers++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>System Users | Mizpah Admin</title>

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
    --blue: #9cc9e7;
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
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.page-title {
    margin: 0;
    color: var(--gold);
    font-size: 27px;
    font-weight: 600;
    letter-spacing: -.4px;
}

.page-subtitle {
    color: var(--muted);
    margin: 8px 0 0;
    font-size: 12px;
    line-height: 1.7;
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
    border-radius: 13px;
    padding: 21px;
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
    border: 1px solid rgba(214,194,156,.12);
    border-radius: 50%;
}

.summary-label {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 12px;
}

.summary-value {
    color: var(--gold);
    font-size: 29px;
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
    color: var(--text);
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.panel-description {
    color: var(--muted);
    font-size: 12px;
    margin: 6px 0 0;
}

.search-box {
    width: 280px;
    max-width: 100%;
    padding: 11px 13px;
    border: 1px solid #373126;
    border-radius: 8px;
    background: #191919;
    color: var(--text);
    font-family: inherit;
    font-size: 12px;
    outline: none;
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
    min-width: 900px;
    border-collapse: collapse;
}

thead {
    background: #191714;
}

th {
    text-align: left;
    padding: 15px 17px;
    color: var(--gold);
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .7px;
    white-space: nowrap;
}

td {
    padding: 16px 17px;
    border-bottom: 1px solid rgba(255,255,255,.05);
    font-size: 12px;
    vertical-align: middle;
}

tbody tr {
    transition: background .2s ease;
}

tbody tr:hover {
    background: rgba(214,194,156,.035);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 160px;
}

.user-avatar {
    width: 37px;
    height: 37px;
    border-radius: 10px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
    background: #2b2419;
    color: var(--gold);
    font-size: 13px;
    font-weight: 600;
}

.user-name {
    color: #f3eee5;
    font-weight: 600;
    max-width: 200px;
    overflow-wrap: anywhere;
}

.user-id {
    color: #777064;
    font-size: 10px;
    margin-top: 4px;
}

.email-text {
    color: #c4bcaf;
    overflow-wrap: anywhere;
    min-width: 150px;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
    text-transform: capitalize;
}

.role-badge::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.role-admin {
    background: rgba(214,194,156,.12);
    color: #e4c989;
}

.role-customer {
    background: rgba(106,169,210,.12);
    color: var(--blue);
}

.booking-count {
    color: var(--gold);
    font-size: 13px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.date-text {
    color: #c4bcaf;
    white-space: nowrap;
    font-size: 11px;
}

.no-booking {
    color: #777064;
    font-style: italic;
    font-size: 11px;
}

.view-profile {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 12px;
    background: var(--gold);
    color: #17130d;
    text-decoration: none;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    transition: .2s ease;
}

.view-profile:hover {
    background: #bca77c;
    transform: translateY(-1px);
}

.no-action {
    color: #666;
    font-size: 13px;
}

.table-footer {
    padding: 16px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    border-top: 1px solid var(--border);
    color: var(--muted);
    font-size: 11px;
}

.empty-state {
    text-align: center;
    padding: 45px 20px;
    color: var(--muted);
    font-size: 12px;
}

.empty-state h3 {
    color: var(--text);
    font-size: 15px;
    margin: 0 0 8px;
}

.empty-state p {
    margin: 0;
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

    .panel-header {
        padding: 17px;
    }

    .search-box {
        width: 100%;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main">

    <div class="page-header">
        <div>
            <h1 class="page-title">System Users</h1>
            <p class="page-subtitle">
                View registered accounts, user roles, and booking activity.
            </p>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Total Users</div>
            <div class="summary-value"><?= $totalUsers ?></div>
            <div class="summary-note">All registered system accounts</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Administrators</div>
            <div class="summary-value"><?= $totalAdmins ?></div>
            <div class="summary-note">Accounts with admin access</div>
        </div>

        <div class="summary-card">
            <div class="summary-label">Customers</div>
            <div class="summary-value"><?= $totalCustomers ?></div>
            <div class="summary-note">Registered customer accounts</div>
        </div>
    </div>

    <div class="content-panel">

        <div class="panel-header">
            <div>
                <h2 class="panel-title">User Directory</h2>
                <p class="panel-description">
                    Review account details and customer booking history.
                </p>
            </div>

            <input
                type="search"
                id="userSearch"
                class="search-box"
                placeholder="Search name, email, or role..."
                aria-label="Search system users"
            >
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Total Bookings</th>
                        <th>Last Booking</th>
                        <th>Date Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="usersTable">

                <?php if (count($users) > 0): ?>

                    <?php foreach ($users as $row): ?>

                        <?php
                        $r = strtolower($row['role'] ?? '');
                        $class = ($r === 'admin') ? 'role-admin' : 'role-customer';

                        $name = (string)($row['name'] ?? '');
                        $initial = strtoupper(substr(trim($name), 0, 1));

                        if ($initial === '') {
                            $initial = '?';
                        }
                        ?>

                        <tr class="user-row">

                            <td>
                                <div class="user-cell">
                                    <span class="user-avatar">
                                        <?= htmlspecialchars($initial) ?>
                                    </span>

                                    <div>
                                        <div class="user-name">
                                            <?= htmlspecialchars($name) ?>
                                        </div>
                                        <div class="user-id">
                                            User ID: <?= (int)$row['id'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="email-text">
                                    <?= htmlspecialchars($row['email'] ?? '') ?>
                                </div>
                            </td>

                            <td>
                                <span class="role-badge <?= $class ?>">
                                    <?= htmlspecialchars($r) ?>
                                </span>
                            </td>

                            <td>
                                <span class="booking-count">
                                    <?= (int)$row['total_bookings'] ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($row['last_booking'])): ?>
                                    <span class="date-text">
                                        <?= htmlspecialchars($row['last_booking']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-booking">No booking</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="date-text">
                                    <?= htmlspecialchars($row['created_at'] ?? '') ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($r === 'customer'): ?>

                                    <a
                                        href="customer-profile.php?id=<?= (int)$row['id'] ?>"
                                        class="view-profile"
                                    >
                                        View Profile
                                    </a>

                                <?php else: ?>

                                    <span class="no-action">—</span>

                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <h3>No users found</h3>
                                <p>Registered accounts will appear here.</p>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span id="userCount">
                Showing <?= $totalUsers ?> user<?= $totalUsers === 1 ? '' : 's' ?>
            </span>

            <span>Mizpah Wellness Spa · User Management</span>
        </div>

    </div>

</div>

<script>
const userSearch = document.getElementById('userSearch');
const userRows = document.querySelectorAll('.user-row');
const userCount = document.getElementById('userCount');

userSearch.addEventListener('input', function () {
    const term = this.value.toLowerCase().trim();
    let visible = 0;

    userRows.forEach(function (row) {
        const matches = row.textContent.toLowerCase().includes(term);

        row.style.display = matches ? '' : 'none';

        if (matches) {
            visible++;
        }
    });

    userCount.textContent =
        'Showing ' + visible + ' of ' + userRows.length +
        ' user' + (userRows.length === 1 ? '' : 's');
});
</script>

</body>
</html>
