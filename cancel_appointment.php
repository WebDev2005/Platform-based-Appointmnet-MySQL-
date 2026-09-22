<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('customer', 'staff', 'admin');
$conn = getDB();

$appointment_id = (int) post('appointment_id');
$appointment = find_appointment($conn, $appointment_id);

if (!$appointment) {
    flash('Appointment not found.', 'error');
    redirect(home_for_role($user['role']));
}

if ($user['role'] === 'customer' && (int) $appointment['user_id'] !== (int) $user['user_id']) {
    flash('You can only cancel your own appointments.', 'error');
    redirect('dashboard.php');
}

if (in_array($appointment['status'], ['done', 'cancelled', 'no_show'], true)) {
    flash('That appointment is already closed.', 'error');
    redirect(home_for_role($user['role']));
}

set_appointment_status($conn, $appointment_id, 'cancelled');
set_queue_status($conn, $appointment_id, 'cancelled');

flash('Appointment cancelled.');
redirect($_POST['return_to'] ?? home_for_role($user['role']));
?>
