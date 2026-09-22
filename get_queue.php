<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';

$conn = getDB();
sweep_no_shows($conn);

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = mysqli_prepare($conn, "
    SELECT q.queue_number, q.status, u.full_name, s.service_name, a.appointment_time
    FROM queue q
    JOIN users u ON u.user_id = q.user_id
    JOIN services s ON s.service_id = q.service_id
    LEFT JOIN appointments a ON a.appointment_id = q.appointment_id
    WHERE q.queue_date = ?
    ORDER BY s.service_name ASC, q.queue_number ASC
");
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);

$queue = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// Patient-facing board: show the queue number and doctor, not the full name.
foreach ($queue as &$row) {
    $parts = explode(' ', trim($row['full_name']));
    $row['full_name'] = $parts[0] . ' ' . strtoupper(substr(end($parts), 0, 1)) . '.';
}
unset($row);

header('Content-Type: application/json');
echo json_encode($queue);
?>
