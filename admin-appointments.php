<?php
include 'db.php';
session_start();

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: Userlogin.html");
    exit();
}

$conn = getDB();

// Cancel appointment
if (isset($_GET['cancel'])) {
    $id = $_GET['cancel'];

    // Update appointments
    $stmt1 = mysqli_prepare($conn, 
        "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?"
    );
    mysqli_stmt_bind_param($stmt1, "i", $id);
    mysqli_stmt_execute($stmt1);

    // Update queue
    $stmt2 = mysqli_prepare($conn, 
        "UPDATE queue SET status = 'cancelled' WHERE appointment_id = ?"
    );
    mysqli_stmt_bind_param($stmt2, "i", $id);
    mysqli_stmt_execute($stmt2);
}

// Fetch data
$result = mysqli_query($conn, "
    SELECT a.appointment_id, u.full_name, u.email, s.service_name,
           a.appointment_date, a.appointment_time, a.status
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    ORDER BY a.appointment_date DESC
");

// Debug check
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Appointments</title>
    <link rel="stylesheet" href="css/A-style.css">
</head>
<body>

<header>
    <h1>Admin Appointment Management</h1>
</header>

<nav>
    <h2>Admin Menu</h2>

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
        <li><a href="archive.php">Archive</a></li>
        <li><a href="admin-index.html">Home</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

<main class="container">

    <h2>All Appointments</h2>
    
	<div id="customAlert" class="custom-alert">
    	<p id="alertMessage"></p>
	</div>
    <div class="align-right">
        <button id="serveNextBtn" style="margin-bottom: 10px;">
            Serve Next
        </button>
    </div>

    <br>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?= $row['full_name'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['service_name'] ?></td>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= $row['appointment_time'] ?></td>

                <td style="
                    <?= $row['status'] === 'done' ? 'color:green;' : '' ?>
                    <?= $row['status'] === 'cancelled' ? 'color:red;' : '' ?>
                ">
                    <?= $row['status'] ?>
                </td>

                <td>
                    <?php if ($row['status'] !== 'done' && $row['status'] !== 'cancelled') { ?>
                        <a href="?cancel=<?= $row['appointment_id'] ?>" 
                           onclick="return confirm('Cancel this appointment?')">
                           Cancel
                        </a>
                    <?php } else { ?>
                        -
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

</main>

<script src="js/script.js"></script>
</body>
</html>