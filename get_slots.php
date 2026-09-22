<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_role_json('customer', 'staff', 'admin');

$conn = getDB();

$service_id = (int) ($_GET['service_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');

header('Content-Type: application/json');

$stmt = mysqli_prepare($conn, "SELECT * FROM services WHERE service_id = ?");
mysqli_stmt_bind_param($stmt, "i", $service_id);
mysqli_stmt_execute($stmt);
$service = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$service || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT appointment_time
    FROM appointments
    WHERE service_id = ? AND appointment_date = ?
      AND status NOT IN ('cancelled', 'no_show')
");
mysqli_stmt_bind_param($stmt, "is", $service_id, $date);
mysqli_stmt_execute($stmt);

$taken = [];
foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC) as $row) {
    $taken[substr($row['appointment_time'], 0, 5)] = true;
}

$slots = [];
$cursor = strtotime("$date " . $service['open_time']);
$end = strtotime("$date " . $service['close_time']);
$step = max(5, (int) $service['slot_minutes']) * 60;

for (; $cursor < $end; $cursor += $step) {
    $label = date('H:i', $cursor);
    $slots[] = [
        'time'      => $label,
        'label'     => date('g:i A', $cursor),
        'available' => !isset($taken[$label]) && $cursor > time(),
    ];
}

echo json_encode($slots);
?>
