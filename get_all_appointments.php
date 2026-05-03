<?php
include 'db.php';

$conn = getDB();

$query = "
    SELECT 
        u.full_name, 
        s.service_name, 
        a.appointment_date, 
        a.appointment_time, 
        a.status
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    ORDER BY a.appointment_date DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($data);
?>