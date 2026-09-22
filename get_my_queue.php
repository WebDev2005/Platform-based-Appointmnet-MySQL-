<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$conn = getDB();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(null);
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT q.*, a.status AS appointment_status
    FROM queue q
    JOIN appointments a ON a.appointment_id = q.appointment_id
    WHERE q.user_id = ? AND q.status IN ('waiting', 'serving')
    ORDER BY q.queue_date ASC, q.queue_number ASC
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$queue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$queue) {
    echo json_encode(null);
    exit();
}

$position = queue_position($conn, $queue);

echo json_encode([
    "queue_number" => (int) $queue['queue_number'],
    "position"     => $position ?? 0,
    "wait_time"    => $position ? ($position - 1) * 10 : 0,
    "status"       => status_label($queue['appointment_status']),
]);
?>
