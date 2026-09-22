<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$conn = getDB();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = post('full_name');
    $email = post('email');
    $phone = post('phone');
    $birth = post('birth_date') ?: null;
    $address = post('address');
    $password = post('password');

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }

    if (!$error) {
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);

        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) {
            $error = "Email already registered!";
        }
    }

    if (!$error) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, email, password, phone, birth_date, address, role)
             VALUES (?, ?, ?, ?, ?, ?, 'customer')"
        );
        mysqli_stmt_bind_param($stmt, "ssssss", $name, $email, $hash, $phone, $birth, $address);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user_id'] = mysqli_insert_id($conn);
            $_SESSION['role'] = 'customer';
            redirect('dashboard.php');
        }

        $error = "Registration failed!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
    <link rel="stylesheet" href="css/C-style.css">
    <link rel="stylesheet" href="css/clinic.css">
</head>
<body>

<header>
    <h1>Create your patient account</h1>
</header>

<main class="container">
    <?php if ($error) { ?>
        <p class="flash flash-error"><?= h($error) ?></p>
    <?php } ?>

    <form method="POST">
        <label>Full name:</label>
        <input type="text" name="full_name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Mobile number:</label>
        <input type="text" name="phone">

        <label>Date of birth:</label>
        <input type="date" name="birth_date">

        <label>Address:</label>
        <input type="text" name="address">

        <label>Password:</label>
        <input type="password" name="password" minlength="6" required>

        <button type="submit">Register</button>
        <div class="align-right">
            <button type="button" onclick="window.location.href='Userlogin.html'">
                 Back to Login
            </button>
        </div>
    </form>
</main>

</body>
</html>
