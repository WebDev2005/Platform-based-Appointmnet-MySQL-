<?php
include 'db.php';
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Platform-Based Appointment System</title>
    <link rel="stylesheet" href="css/C-style.css">
</head>
<body>
    <header>
        <h1>Platform-Based Appointment and Queue Management System</h1>
    </header>
    <nav>
        <h2>Book Appointment</h2>
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
        <h2>Book New Appointment</h2>
        <form id="appointmentForm" action="save-appointment.php" method="POST">
            <label for="doctor">Select Doctor:</label>
            <select id="doctor" name="doctor" required>
                <option value="">Choose a doctor</option>
            </select>

            <label for="date">Date:</label>
            <input type="date" id="date" name="date" required>

            <label for="time">Time:</label>
            <input type="time" id="time" name="time" required>

            <button type="submit">Book Appointment</button>
        </form>
        <p id="successMessage" style="color: green; display: none;">Appointment booked successfully!</p>
    </main>
    <footer>
        <p>&copy; 2026 Platform-Based Appointment System BKB</p>
    </footer>
    <script src="js/script.js"></script>
</body>
</html>