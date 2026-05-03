<?php
include 'db.php';

$conn = getDB();

$result = mysqli_query($conn, "SELECT * FROM services ORDER BY service_name ASC");

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$services = [];

while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode($services);
?>
