<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('customer');
$conn = getDB();

$service_id = (int) post('doctor');
$date = post('date');
$time = post('time');
$reason = post('reason');

if (!$service_id || !$date || !$time) {
    flash('Please choose a doctor, date and time slot.', 'error');
    redirect('book-appointment.php');
}

if (strtotime("$date $time") < time()) {
    flash('That time slot is already in the past.', 'error');
    redirect('book-appointment.php');
}

$stmt = mysqli_prepare($conn, "
    SELECT appointment_id FROM appointments
    WHERE service_id = ? AND appointment_date = ? AND appointment_time = ?
      AND status NOT IN ('cancelled', 'no_show')
");
mysqli_stmt_bind_param($stmt, "iss", $service_id, $date, $time);
mysqli_stmt_execute($stmt);

if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
    flash('That slot was just taken. Please pick another one.', 'error');
    redirect('book-appointment.php');
}

$stmt = mysqli_prepare($conn,
    "INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, reason, status)
     VALUES (?, ?, ?, ?, ?, 'booked')"
);
mysqli_stmt_bind_param($stmt, "iisss", $user['user_id'], $service_id, $date, $time, $reason);

if (!mysqli_stmt_execute($stmt)) {
    flash('Booking failed. Please try again.', 'error');
    redirect('book-appointment.php');
}

flash('Appointment booked. Your queue number is issued when you arrive at the clinic.');
redirect('dashboard.php');
?>
