<?php

function getDB() {

    $host = "mysql.railway.internal";
    $user = "root";
    $pass = "XJsuxLJoBNiLvYWZfPXdKmjjmFXANJQx";
    $db   = "railway";
    $port = "3306";

    $conn = mysqli_connect($host, $user, $pass, $db, $port);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    return $conn;
}
?>
