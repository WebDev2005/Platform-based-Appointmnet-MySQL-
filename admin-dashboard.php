<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: Userlogin.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Platform-Based Appointment System</title>
    <link rel="stylesheet" href="css/A-style.css">
</head>
<body>
    <header>
        <h1>Platform-Based Appointment and Queue Management System</h1>
    </header>
    <nav>
        <h2>Dashboard</h2>
        <input type="checkbox" id="menu-toggle">
        <label for="menu-toggle" class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </label>
        <ul>
            <li><a href="admin-dashboard.php">Dashboard</a></li>
            <li><a href="admin-appointments.php">Customer Appointments</a></li>
            <li><a href="admin_queue-status.html">Queue Status</a></li>
            <li><a href="archive.php">View Archive</a></li>
            <li><a href="admin-index.html">Home</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <main class="container">
        <h3>Welcome Admin!</h3>
        <p>Welcome to the dashboard. Here you can edit and control all the appointment with ease.
            All the changes in the appointment will be taken responsibility by the user.
        </p>
        <br>

        <h3>Customer Appointments</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="adminAppointmentsTable">
                <!-- Appointments will be loaded here -->
            </tbody>
        </table>

    </main>
    <footer>
        <p>&copy; 2026 Platform-Based Appointment System BKB</p>
    </footer>
    <script src="js/script.js"></script>
</body>
</html>