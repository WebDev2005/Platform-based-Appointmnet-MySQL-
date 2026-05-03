<?php
include 'db.php';

$conn = getDB();

$query = "
    SELECT 
        q.queue_number, 
        u.full_name, 
        q.status, 
        a.appointment_time
    FROM queue q
    JOIN users u ON q.user_id = u.user_id
    LEFT JOIN appointments a ON q.appointment_id = a.appointment_id
    ORDER BY q.queue_number DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$queue = [];

while ($row = mysqli_fetch_assoc($result)) {
    $queue[] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($queue);
?>