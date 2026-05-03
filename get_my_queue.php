<?php
include 'db.php';
session_start();

$conn = getDB();

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(null);
    exit();
}

$user_id = $_SESSION['user_id'];

/* =========================
   1. GET USER QUEUE NUMBER
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT queue_number 
    FROM queue 
    WHERE user_id = ? AND status = 'waiting'
    ORDER BY queue_number ASC
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    $row = mysqli_fetch_assoc($result);
    $myQueue = $row['queue_number'];

    /* =========================
       2. COUNT PEOPLE AHEAD
    ========================= */
    $stmt2 = mysqli_prepare($conn, "
        SELECT COUNT(*) AS total 
        FROM queue 
        WHERE queue_number < ? AND status = 'waiting'
    ");

    mysqli_stmt_bind_param($stmt2, "i", $myQueue);
    mysqli_stmt_execute($stmt2);
    $countResult = mysqli_stmt_get_result($stmt2);
    $countRow = mysqli_fetch_assoc($countResult);

    $position = $countRow['total'] + 1;

    /* =========================
       3. ESTIMATE WAIT TIME
    ========================= */
    $waitTime = ($position - 1) * 10; // 10 mins per person

    echo json_encode([
        "queue_number" => $myQueue,
        "position" => $position,
        "wait_time" => $waitTime
    ]);

} else {
    echo json_encode(null);
}
?>