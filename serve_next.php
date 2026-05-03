<?php
include 'db.php';
session_start();

$conn = getDB();

/* =========================
   1. GET CURRENTLY SERVING
========================= */
$current = mysqli_query($conn, "
    SELECT appointment_id 
    FROM queue 
    WHERE status = 'serving'
    LIMIT 1
");

if (!$current) {
    die("Error (current): " . mysqli_error($conn));
}

/* =========================
   2. MARK CURRENT AS DONE
========================= */
if ($row = mysqli_fetch_assoc($current)) {

    $appointment_id = $row['appointment_id'];

    // Update queue → done
    mysqli_query($conn, "
        UPDATE queue 
        SET status = 'done', completed_at = NOW() 
        WHERE appointment_id = $appointment_id
    ");

    // Update appointment → done
    mysqli_query($conn, "
        UPDATE appointments 
        SET status = 'done' 
        WHERE appointment_id = $appointment_id
    ");
}

/* =========================
   3. GET NEXT IN QUEUE
========================= */
$next = mysqli_query($conn, "
    SELECT appointment_id 
    FROM queue 
    WHERE status = 'waiting'
    ORDER BY queue_number ASC
    LIMIT 1
");

if (!$next) {
    die("Error (next): " . mysqli_error($conn));
}

/* =========================
   4. SET NEXT TO SERVING
========================= */
$row = mysqli_fetch_assoc($next);

if ($row) {

    $next_id = $row['appointment_id'];

    // Update queue → serving
    mysqli_query($conn, "
        UPDATE queue 
        SET status = 'serving' 
        WHERE appointment_id = $next_id
    ");

    // Update appointment → serving
    mysqli_query($conn, "
        UPDATE appointments 
        SET status = 'serving' 
        WHERE appointment_id = $next_id
    ");

    echo "success";

} else {
    echo "no_more_queue";
}
?>