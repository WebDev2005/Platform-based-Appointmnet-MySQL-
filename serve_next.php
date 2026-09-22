<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_role_json('admin', 'staff', 'doctor');

$conn = getDB();
$today = date('Y-m-d');

// Close whoever is being served, then call the next waiting patient.
$stmt = mysqli_prepare($conn, "SELECT appointment_id FROM queue WHERE status = 'serving' AND queue_date = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$current = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($current) {
    $appointment = find_appointment($conn, $current['appointment_id']);
    set_queue_status($conn, $current['appointment_id'], 'done');
    set_appointment_status($conn, $current['appointment_id'], 'done');

    if ($appointment) {
        ensure_invoice($conn, $appointment);
    }
}

$stmt = mysqli_prepare($conn, "
    SELECT appointment_id FROM queue
    WHERE status = 'waiting' AND queue_date = ?
    ORDER BY queue_number ASC LIMIT 1
");
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$next = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$next) {
    echo "no_more_queue";
    exit();
}

set_queue_status($conn, $next['appointment_id'], 'serving');
set_appointment_status($conn, $next['appointment_id'], 'in_consultation');

echo "success";
?>
