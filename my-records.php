<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/chart.php';

$user = require_role('customer');
$conn = getDB();

$visits = patient_visits($conn, $user['user_id']);

render_header('My Records', 'customer');
?>
    <h2>Medical history</h2>
    <p class="muted">Diagnoses, prescriptions and billing from every visit.</p>

    <?php foreach ($visits as $visit) { render_visit_card($conn, $visit); } ?>

    <?php if (!$visits) { ?>
        <p class="muted">No records yet.</p>
    <?php } ?>
<?php
render_footer();
?>
