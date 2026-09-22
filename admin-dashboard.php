<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/reports.php';

$user = require_role('admin');
$conn = getDB();
sweep_no_shows($conn);

$today = date('Y-m-d');

$today_stats = query_row($conn, "
    SELECT COUNT(*) AS appointments,
           SUM(status IN ('arrived', 'vitals_done', 'in_consultation')) AS in_clinic,
           SUM(status = 'done') AS done,
           SUM(status = 'no_show') AS no_show
    FROM appointments WHERE appointment_date = ?
", "s", [$today]);

$unpaid = query_row($conn, "
    SELECT COUNT(*) AS invoices,
           COALESCE(SUM((SELECT SUM(amount) FROM invoice_items ii WHERE ii.invoice_id = i.invoice_id)), 0) AS total
    FROM invoices i WHERE i.status <> 'paid'
");

$patients = query_row($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");

$upcoming = query_all($conn, "
    SELECT a.appointment_id, a.appointment_time, a.status, u.full_name, s.service_name, q.queue_number
    FROM appointments a
    JOIN users u ON u.user_id = a.user_id
    JOIN services s ON s.service_id = a.service_id
    LEFT JOIN queue q ON q.appointment_id = a.appointment_id
    WHERE a.appointment_date = ?
    ORDER BY a.appointment_time
", "s", [$today]);

render_header('Dashboard', 'admin');
render_flash();
?>
    <h3>Welcome, <?= h($user['full_name']) ?></h3>
    <p class="muted">Clinic overview for <?= h(date('D, d M Y')) ?>.</p>

    <div class="grid">
        <div class="stat"><span class="value"><?= (int) $today_stats['appointments'] ?></span><span class="label">Appointments today</span></div>
        <div class="stat"><span class="value"><?= (int) $today_stats['in_clinic'] ?></span><span class="label">In clinic now</span></div>
        <div class="stat"><span class="value"><?= (int) $today_stats['done'] ?></span><span class="label">Completed today</span></div>
        <div class="stat"><span class="value"><?= (int) $today_stats['no_show'] ?></span><span class="label">Missed today</span></div>
        <div class="stat"><span class="value"><?= h(money($unpaid['total'])) ?></span><span class="label">Unpaid (<?= (int) $unpaid['invoices'] ?> invoices)</span></div>
        <div class="stat"><span class="value"><?= (int) $patients['total'] ?></span><span class="label">Registered patients</span></div>
    </div>

    <h3>Today's schedule</h3>
    <table class="table">
        <thead><tr><th>Time</th><th>Queue #</th><th>Patient</th><th>Doctor</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($upcoming as $row) { ?>
            <tr>
                <td><?= h(date('g:i A', strtotime($row['appointment_time']))) ?></td>
                <td><?= $row['queue_number'] ? (int) $row['queue_number'] : '-' ?></td>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['service_name']) ?></td>
                <td><span class="badge badge-<?= h($row['status']) ?>"><?= h(status_label($row['status'])) ?></span></td>
                <td><a href="consultation.php?appointment_id=<?= (int) $row['appointment_id'] ?>">Open chart</a></td>
            </tr>
        <?php } ?>
        <?php if (!$upcoming) { ?>
            <tr><td colspan="6" class="muted">No appointments booked for today.</td></tr>
        <?php } ?>
        </tbody>
    </table>

    <p>
        <a href="staff-dashboard.php">Front desk view</a> &middot;
        <a href="admin-billing.php">Billing</a> &middot;
        <a href="admin-followups.php">Follow-ups</a> &middot;
        <a href="admin-reports.php">Reports</a>
    </p>
<?php
render_footer();
?>
