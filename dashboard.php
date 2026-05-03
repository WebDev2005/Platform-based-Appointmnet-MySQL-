<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
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
    <link rel="stylesheet" href="css/C-style.css">
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
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="book-appointment.php">Book Appointment</a></li>
            <li><a href="queue-status.html">Queue Status</a></li>
            <li><a href="index.html">Home</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <main class="container">
        <h3>Queue Management Dashboard</h3>
        <p>Welcome to the dashboard. Here you can manage appointments and view queue status.</p>

        <h3>Your Appointments</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Doctor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="userAppointmentsTable">
                <!-- Appointments will be loaded here -->
            </tbody>
        </table>

        <h3>Queue Status</h3>
        <p>Current queue position: <span id="queuePosition">0</span></p>
        <p>Estimated wait time: <span id="waitTime"></span></p>
    </main>
    <footer>
        <p>&copy; 2026 Platform-Based Appointment System BKB</p>
    </footer>
    <script src="js/script.js"></script>
</body>
</html>