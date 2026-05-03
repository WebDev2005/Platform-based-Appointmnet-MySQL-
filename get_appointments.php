<?php
include 'db.php';
session_start();

$conn = getDB();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   GET USER APPOINTMENTS
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT 
        a.appointment_date, 
        a.appointment_time, 
        s.service_name, 
        a.status
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date, a.appointment_time
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$appointments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($appointments);
?>