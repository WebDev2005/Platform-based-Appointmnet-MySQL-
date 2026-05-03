<?php
include 'db.php';
session_start();

$conn = getDB();

$username = $_POST['username'];
$password = $_POST['password'];

// MySQL version
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];

    // SET COOKIE FIRST (before echo)
    if ($user['role'] === 'customer') {
        setcookie("user_email", $user['email'], time() + (86400 * 7), "/");
    }

    if ($user['role'] === 'admin') {
        echo "admin";
    } elseif ($user['role'] === 'customer') {
        echo "customer";
    } else {
        echo "invalid";
    }

} else {
    echo "invalid";
}
?>