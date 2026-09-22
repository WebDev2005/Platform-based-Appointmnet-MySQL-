<?php
require_once __DIR__ . '/helpers.php';

/** Aggregates appointment, billing and diagnosis figures for a date range. */
function build_report($conn, $from, $to) {
    $totals = query_row($conn, "
        SELECT COUNT(*) AS appointments,
               SUM(status = 'done') AS done,
               SUM(status = 'cancelled') AS cancelled,
               SUM(status = 'no_show') AS no_show
        FROM appointments
        WHERE appointment_date BETWEEN ? AND ?
    ", "ss", [$from, $to]);

    $appointments = max(1, (int) $totals['appointments']);
    $totals['completion_rate'] = round(100 * (int) $totals['done'] / $appointments, 1);
    $totals['no_show_rate'] = round(100 * (int) $totals['no_show'] / $appointments, 1);

    $billing = query_row($conn, "
        SELECT
            COALESCE(SUM(CASE WHEN i.status = 'paid' THEN t.total ELSE 0 END), 0) AS collected,
            COALESCE(SUM(CASE WHEN i.status <> 'paid' THEN t.total ELSE 0 END), 0) AS outstanding,
            COUNT(*) AS invoices
        FROM invoices i
        JOIN appointments a ON a.appointment_id = i.appointment_id
        JOIN (SELECT invoice_id, SUM(amount) AS total FROM invoice_items GROUP BY invoice_id) t
             ON t.invoice_id = i.invoice_id
        WHERE a.appointment_date BETWEEN ? AND ?
    ", "ss", [$from, $to]);

    $by_service = query_all($conn, "
        SELECT s.service_name,
               COUNT(*) AS appointments,
               SUM(a.status = 'done') AS done,
               SUM(a.status = 'no_show') AS no_show,
               COALESCE(SUM((SELECT SUM(amount) FROM invoice_items ii
                             JOIN invoices i ON i.invoice_id = ii.invoice_id
                             WHERE i.appointment_id = a.appointment_id)), 0) AS billed
        FROM appointments a
        JOIN services s ON s.service_id = a.service_id
        WHERE a.appointment_date BETWEEN ? AND ?
        GROUP BY s.service_id, s.service_name
        ORDER BY appointments DESC
    ", "ss", [$from, $to]);

    $diagnoses = query_all($conn, "
        SELECT c.diagnosis, COUNT(*) AS cases
        FROM consultations c
        JOIN appointments a ON a.appointment_id = c.appointment_id
        WHERE a.appointment_date BETWEEN ? AND ? AND c.diagnosis IS NOT NULL AND c.diagnosis <> ''
        GROUP BY c.diagnosis
        ORDER BY cases DESC
        LIMIT 10
    ", "ss", [$from, $to]);

    $daily = query_all($conn, "
        SELECT appointment_date,
               COUNT(*) AS appointments,
               SUM(status = 'done') AS done,
               SUM(status = 'no_show') AS no_show
        FROM appointments
        WHERE appointment_date BETWEEN ? AND ?
        GROUP BY appointment_date
        ORDER BY appointment_date DESC
    ", "ss", [$from, $to]);

    return [
        'from'       => $from,
        'to'         => $to,
        'totals'     => $totals,
        'billing'    => $billing,
        'by_service' => $by_service,
        'diagnoses'  => $diagnoses,
        'daily'      => $daily,
    ];
}

function query_all($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);

    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function query_row($conn, $sql, $types = '', $params = []) {
    $rows = query_all($conn, $sql, $types, $params);

    return $rows ? $rows[0] : [];
}
?>
