<div class="sidebar">

    <style>
    .sidebar {
        background: #0b0b0b;
        width: 250px;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        padding: 20px 15px;

        /* ✅ still scrollable but hidden scrollbar */
        overflow-y: auto;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }

    /* Chrome / Safari scrollbar hide */
    .sidebar::-webkit-scrollbar {
        display: none;
    }

    .sidebar .brand {
        text-align: center;
        padding: 20px 10px;
        margin-bottom: 20px;
    }

    .sidebar .brand img {
        width: 75px;
        height: 75px;
        object-fit: contain;

        /* 🔥 BRIGHTER LOGO */
        filter: brightness(1.6) contrast(1.3)
                drop-shadow(0 0 10px rgba(214,194,156,0.5));

        border-radius: 10px;
    }

    .sidebar .brand h2 {
        color: #D6C29C;
        margin: 10px 0 5px;
        font-size: 18px;
        letter-spacing: 1px;
    }

    .role-label {
        font-size: 11px;
        color: #aaa;
    }

    .menu-group {
        margin-top: 15px;
    }

    .menu-title {
        font-size: 11px;
        color: #777;
        margin: 15px 10px 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sidebar a {
        display: block;
        padding: 10px 12px;
        margin: 5px 0;
        text-decoration: none;
        color: #ddd;
        border-radius: 8px;
        transition: 0.2s ease;
        font-size: 14px;
    }

    .sidebar a:hover {
        background: rgba(214,194,156,0.15);
        color: #D6C29C;
        transform: translateX(3px);
    }

    .logout-btn {
        color: #ff6b6b !important;
    }

    .logout-btn:hover {
        background: rgba(255,107,107,0.1) !important;
        color: #ff6b6b !important;
    }
    </style>

    <!-- BRAND -->
    <div class="brand">
        <img src="../assets/images/logo.png" alt="Logo">
        <h2>MIZPAH ADMIN</h2>
        <span class="role-label">Administrator Panel</span>
    </div>


    <div class="menu-group">
        <div class="menu-title">Booking</div>
        <a href="walkin-booking.php">Walk-in Booking</a>
    </div>

    <!-- MAIN -->
    <div class="menu-group">
        <div class="menu-title">Main</div>

        <a href="bookings.php">Bookings</a>
        <a href="calendar.php">Calendar</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="reports.php">Sales Report</a>
    </div>

    <!-- MANAGEMENT -->
    <div class="menu-group">
        <div class="menu-title">Management</div>

        <a href="landingpage.php">Landing Page</a>
        <a href="ratings.php">Ratings</a>
        <a href="therapists.php">Therapists</a>
        <a href="settings.php">Settings</a>
        <a href="services.php">Services</a>
        <a href="users.php">Users</a>
    </div>

    <!-- ACCOUNT -->
    <div class="menu-group">
        <div class="menu-title">Account</div>

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</div>