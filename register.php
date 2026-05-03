<?php
include 'db.php';

$conn = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // OPTIONAL: check if email already exists
    $check = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    $resultCheck = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($resultCheck) > 0) {
        echo "Email already registered!";
    } else {

        // Insert as customer
        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, email, password, role)
             VALUES (?, ?, ?, 'customer')"
        );

        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $password);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: Userlogin.html");
            exit();
        } else {
            echo "Registration failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/C-style.css">
</head>
<body>

<header>
    <h1>Register as Customer</h1>
</header>

<main class="container">
    <form method="POST">
        <label>Name:</label>
        <input type="text" name="full_name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <button type="submit">Register</button>
        <div class="align-right">
            <button onclick="window.location.href='Userlogin.html'">
                 Back to Login
            </button>
        </div>
    </form>
</main>

</body>
</html>