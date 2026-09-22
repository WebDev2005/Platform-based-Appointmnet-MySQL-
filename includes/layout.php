<?php
require_once __DIR__ . '/helpers.php';

function nav_links($role) {
    switch ($role) {
        case 'admin':
            return [
                'admin-dashboard.php'    => 'Dashboard',
                'admin-appointments.php' => 'Appointments',
                'admin-billing.php'      => 'Billing',
                'admin-followups.php'    => 'Follow-Ups',
                'admin-reports.php'      => 'Reports',
                'archive.php'            => 'Archive',
                'logout.php'             => 'Logout',
            ];
        case 'staff':
            return [
                'staff-dashboard.php'  => 'Front Desk',
                'admin_queue-status.html' => 'Queue Board',
                'patients.php'         => 'Patients',
                'logout.php'           => 'Logout',
            ];
        case 'doctor':
            return [
                'doctor-queue.php' => 'My Queue',
                'patients.php'     => 'Patients',
                'logout.php'       => 'Logout',
            ];
        default:
            return [
                'dashboard.php'         => 'Dashboard',
                'book-appointment.php'  => 'Book Appointment',
                'my-records.php'        => 'My Records',
                'queue-status.html'     => 'Queue Status',
                'index.html'            => 'Home',
                'logout.php'            => 'Logout',
            ];
    }
}

function render_header($title, $role, $subtitle = null) {
    $stylesheet = $role === 'customer' ? 'css/C-style.css' : 'css/A-style.css';
    $links = nav_links($role);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> - Platform-Based Appointment System</title>
    <link rel="stylesheet" href="<?= $stylesheet ?>">
    <link rel="stylesheet" href="css/clinic.css">
</head>
<body>
    <header>
        <h1>Platform-Based Appointment and Patient Management System</h1>
    </header>
    <nav>
        <h2><?= h($subtitle ?: $title) ?></h2>
        <input type="checkbox" id="menu-toggle">
        <label for="menu-toggle" class="hamburger">
            <span></span><span></span><span></span>
        </label>
        <ul>
            <?php foreach ($links as $href => $label) { ?>
                <li><a href="<?= $href ?>"><?= h($label) ?></a></li>
            <?php } ?>
        </ul>
    </nav>
    <main class="container">
    <?php
}

function render_footer() {
    ?>
    </main>
    <footer>
        <p>&copy; 2026 Platform-Based Appointment System BKB</p>
    </footer>
    <script src="js/script.js"></script>
</body>
</html>
    <?php
}

function render_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<p class="flash flash-' . h($flash['type']) . '">' . h($flash['message']) . '</p>';
    }
}

function flash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
?>
