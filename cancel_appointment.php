<?php
include 'db.php';

$conn = getDB(); 

$id = $_POST['id'];

// Use prepared statement (safe)
$stmt = mysqli_prepare($conn, 
    "UPDATE queue SET status = 'cancelled' WHERE queue_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    echo "cancelled";
} else {
    echo "error: " . mysqli_error($conn);
}
?>