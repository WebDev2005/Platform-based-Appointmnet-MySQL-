<?php
require_once __DIR__ . '/includes/auth.php';

$conn = getDB();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "invalid";
    exit();
}

$stored = $user['password'];
$hashed = password_verify($password, $stored);
$legacy = !$hashed && hash_equals($stored, $password);

if (!$hashed && !$legacy) {
    echo "invalid";
    exit();
}

// Upgrade accounts still holding a plaintext password from the old schema.
if ($legacy) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $upd->bind_param("si", $hash, $user['user_id']);
    $upd->execute();
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'customer') {
    setcookie("user_email", $user['email'], time() + (86400 * 7), "/");
}

echo in_array($user['role'], ['admin', 'staff', 'doctor', 'customer'], true) ? $user['role'] : "invalid";
?>
