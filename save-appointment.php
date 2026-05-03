<?php
include 'db.php';
session_start();

$conn = getDB();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

$user_id = $_SESSION['user_id'];
$service_id = $_POST['doctor'];
$date = $_POST['date'];
$time = $_POST['time'];

/* =========================
   1. INSERT APPOINTMENT
========================= */
$stmt = mysqli_prepare($conn,
    "INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time)
     VALUES (?, ?, ?, ?)"
);

mysqli_stmt_bind_param($stmt, "iiss", $user_id, $service_id, $date, $time);

if (!mysqli_stmt_execute($stmt)) {
    die("Insert appointment failed: " . mysqli_error($conn));
}

// Get last inserted ID (REPLACES RETURNING)
$appointment_id = mysqli_insert_id($conn);

/* =========================
   2. GET NEXT QUEUE NUMBER
========================= */
$q = mysqli_query($conn, "SELECT MAX(queue_number) AS max FROM queue");

if (!$q) {
    die("Queue error: " . mysqli_error($conn));
}

$q_row = mysqli_fetch_assoc($q);
$queue_number = $q_row['max'] ? $q_row['max'] + 1 : 1;

/* =========================
   3. INSERT INTO QUEUE
========================= */
$stmt2 = mysqli_prepare($conn,
    "INSERT INTO queue (appointment_id, user_id, service_id, queue_number)
     VALUES (?, ?, ?, ?)"
);

mysqli_stmt_bind_param($stmt2, "iiii", $appointment_id, $user_id, $service_id, $queue_number);

if (!mysqli_stmt_execute($stmt2)) {
    die("Insert queue failed: " . mysqli_error($conn));
}

/* =========================
   4. REDIRECT
========================= */
header("Location: dashboard.php");
exit();
?>