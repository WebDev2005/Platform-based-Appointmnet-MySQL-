<?php
require_once __DIR__ . '/../db.php';

const NO_SHOW_GRACE_MINUTES = 30;

const STATUS_LABELS = [
    'booked'          => 'Booked',
    'arrived'         => 'Arrived',
    'vitals_done'     => 'Vitals recorded',
    'in_consultation' => 'In consultation',
    'done'            => 'Completed',
    'cancelled'       => 'Cancelled',
    'no_show'         => 'Missed (no-show)',
];

const OPEN_STATUSES = ['booked', 'arrived', 'vitals_done', 'in_consultation'];

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($amount) {
    return '₱' . number_format((float) $amount, 2);
}

function status_label($status) {
    return STATUS_LABELS[$status] ?? ucfirst((string) $status);
}

function status_color($status) {
    if ($status === 'done') return 'green';
    if ($status === 'cancelled' || $status === 'no_show') return 'red';
    if ($status === 'in_consultation') return '#b8860b';
    return '';
}

function post($key, $default = '') {
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function redirect($path) {
    header("Location: $path");
    exit();
}

/**
 * Flags appointments whose slot passed without the patient arriving.
 * Called on the dashboards so tracking happens without a cron job.
 */
function sweep_no_shows($conn) {
    $cutoff = date('Y-m-d H:i:s', time() - NO_SHOW_GRACE_MINUTES * 60);

    $stmt = mysqli_prepare($conn, "
        UPDATE appointments
        SET status = 'no_show'
        WHERE status = 'booked'
          AND TIMESTAMP(appointment_date, appointment_time) < ?
    ");
    mysqli_stmt_bind_param($stmt, "s", $cutoff);
    mysqli_stmt_execute($stmt);

    mysqli_query($conn, "
        UPDATE queue q
        JOIN appointments a ON a.appointment_id = q.appointment_id
        SET q.status = 'no_show'
        WHERE a.status = 'no_show' AND q.status = 'waiting'
    ");
}

/** Queue numbers restart each day, per doctor. */
function next_queue_number($conn, $service_id, $date) {
    $stmt = mysqli_prepare($conn, "
        SELECT COALESCE(MAX(queue_number), 0) + 1 AS next
        FROM queue
        WHERE service_id = ? AND queue_date = ?
    ");
    mysqli_stmt_bind_param($stmt, "is", $service_id, $date);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return (int) $row['next'];
}

function queue_entry($conn, $appointment_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM queue WHERE appointment_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

/** How many patients are still ahead of this queue entry, 1 = next up. */
function queue_position($conn, $queue) {
    if (!$queue || $queue['status'] !== 'waiting') {
        return null;
    }

    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS ahead
        FROM queue
        WHERE service_id = ? AND queue_date = ?
          AND status IN ('waiting', 'serving')
          AND queue_number < ?
    ");
    mysqli_stmt_bind_param($stmt, "isi", $queue['service_id'], $queue['queue_date'], $queue['queue_number']);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return (int) $row['ahead'] + 1;
}

function set_queue_status($conn, $appointment_id, $status) {
    $completed = in_array($status, ['done', 'cancelled', 'no_show'], true) ? date('Y-m-d H:i:s') : null;

    $stmt = mysqli_prepare($conn, "
        UPDATE queue SET status = ?, completed_at = ? WHERE appointment_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ssi", $status, $completed, $appointment_id);
    mysqli_stmt_execute($stmt);
}

function set_appointment_status($conn, $appointment_id, $status) {
    $stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE appointment_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
    mysqli_stmt_execute($stmt);
}

function find_appointment($conn, $appointment_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT a.*, u.full_name, u.email, u.phone, u.birth_date,
               s.service_name, s.fee, s.slot_minutes
        FROM appointments a
        JOIN users u ON u.user_id = a.user_id
        JOIN services s ON s.service_id = a.service_id
        WHERE a.appointment_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}

function invoice_total($conn, $invoice_id) {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM invoice_items WHERE invoice_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return (float) $row['total'];
}

/** Creates the checkout invoice for a finished visit (consultation fee + drugs). */
function ensure_invoice($conn, $appointment) {
    $appointment_id = (int) $appointment['appointment_id'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM invoices WHERE appointment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $invoice = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($invoice) {
        return $invoice;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO invoices (appointment_id, user_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $appointment['user_id']);
    mysqli_stmt_execute($stmt);
    $invoice_id = mysqli_insert_id($conn);

    add_invoice_item($conn, $invoice_id, 'Consultation - ' . $appointment['service_name'], $appointment['fee']);

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM prescriptions WHERE appointment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    $count = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'];

    if ($count > 0) {
        add_invoice_item($conn, $invoice_id, "Dispensing fee ($count item" . ($count > 1 ? 's' : '') . ')', 50 * $count);
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM invoices WHERE invoice_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $invoice_id);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function add_invoice_item($conn, $invoice_id, $description, $amount) {
    $stmt = mysqli_prepare($conn, "INSERT INTO invoice_items (invoice_id, description, amount) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isd", $invoice_id, $description, $amount);
    mysqli_stmt_execute($stmt);
}
?>
