<?php
include 'db.php';
session_start();

// 🔐 Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: Userlogin.html");
    exit();
}

$conn = getDB();

// Get archived data (done + cancelled)
$query = "
    SELECT q.queue_number, s.service_name, q.status, q.created_at
    FROM queue q
    JOIN services s ON q.service_id = s.service_id
    WHERE q.status IN ('done', 'cancelled')
    ORDER BY q.queue_number DESC
";

$result = mysqli_query($conn, $query);

// Debug check
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Archive - Admin</title>
    <link rel="stylesheet" href="css/A-style.css">
</head>
<body>

<header>
    <h1>Archived Appointments</h1>
</header>

<main class="container">

    <h2>History (Completed & Cancelled)</h2>
    <p>Total Archived: <?= mysqli_num_rows($result) ?></p>

    <table class="table">
        <thead>
            <tr>
                <th>Queue #</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?= $row['queue_number'] ?></td>
                    <td><?= $row['service_name'] ?></td>

                    <!-- Status color -->
                    <td style="
                        <?= $row['status'] === 'done' ? 'color:green;' : 'color:red;' ?>
                    ">
                        <?= $row['status'] ?>
                    </td>

                    <td><?= $row['created_at'] ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <br>
    <button onclick="window.location.href='admin-dashboard.php'">
        Back to Dashboard
    </button>

</main>

</body>
</html>