<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Landing page for each role, used after login and on access denial. */
function home_for_role($role) {
    switch ($role) {
        case 'admin':    return 'admin-dashboard.php';
        case 'staff':    return 'staff-dashboard.php';
        case 'doctor':   return 'doctor-queue.php';
        default:         return 'dashboard.php';
    }
}

function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);

    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return $user ?: null;
}

function require_role(...$roles) {
    $user = current_user();

    if (!$user) {
        header("Location: Userlogin.html");
        exit();
    }

    if ($roles && !in_array($user['role'], $roles, true)) {
        header("Location: " . home_for_role($user['role']));
        exit();
    }

    return $user;
}

/** JSON endpoints answer with 401 instead of redirecting. */
function require_role_json(...$roles) {
    $user = current_user();

    if (!$user || ($roles && !in_array($user['role'], $roles, true))) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(["error" => "unauthorized"]);
        exit();
    }

    return $user;
}
?>
