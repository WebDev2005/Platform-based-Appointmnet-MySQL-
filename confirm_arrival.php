<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('staff', 'admin');
$conn = getDB();

$appointment_id = (int) post('appointment_id');
$return_to = post('return_to', 'staff-dashboard.php');
$appointment = find_appointment($conn, $appointment_id);

if (!$appointment) {
    flash('Appointment not found.', 'error');
    redirect($return_to);
}

if (in_array($appointment['status'], ['done', 'cancelled'], true)) {
    flash('That appointment is already closed.', 'error');
    redirect($return_to);
}

$queue = queue_entry($conn, $appointment_id);

if (!$queue) {
    $queue_number = next_queue_number($conn, $appointment['service_id'], $appointment['appointment_date']);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO queue (appointment_id, user_id, service_id, queue_number, queue_date, status)
         VALUES (?, ?, ?, ?, ?, 'waiting')"
    );
    mysqli_stmt_bind_param($stmt, "iiiis",
        $appointment_id, $appointment['user_id'], $appointment['service_id'],
        $queue_number, $appointment['appointment_date']
    );
    mysqli_stmt_execute($stmt);
} else {
    $queue_number = (int) $queue['queue_number'];
    set_queue_status($conn, $appointment_id, 'waiting');
}

set_appointment_status($conn, $appointment_id, 'arrived');

flash("Arrival confirmed. Queue number $queue_number issued to " . $appointment['full_name'] . '.');
redirect($return_to);
?>
