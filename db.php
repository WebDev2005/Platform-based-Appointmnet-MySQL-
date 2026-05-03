<?php
function getDB() {
    $conn = new mysqli("sql100.infinityfree.com", "if0_41611772", "bkballadares23", "if0_41611772_appointment");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>