<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('admin', 'staff');
$conn = getDB();
sweep_no_shows($conn);

$missed = mysqli_fetch_all(mysqli_query($conn, "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
           u.full_name, u.phone, u.email, s.service_name
    FROM appointments a
    JOIN users u ON u.user_id = a.user_id
    JOIN services s ON s.service_id = a.service_id
    WHERE a.status IN ('no_show', 'cancelled')
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 100
"), MYSQLI_ASSOC);

$followups = mysqli_fetch_all(mysqli_query($conn, "
    SELECT c.follow_up_date, c.diagnosis, a.appointment_id, a.appointment_date,
           u.user_id, u.full_name, u.phone, u.email, s.service_name,
           (SELECT COUNT(*) FROM appointments f
             WHERE f.user_id = a.user_id AND f.appointment_date >= c.follow_up_date
               AND f.status NOT IN ('cancelled', 'no_show')) AS booked_after
    FROM consultations c
    JOIN appointments a ON a.appointment_id = c.appointment_id
    JOIN users u ON u.user_id = a.user_id
    JOIN services s ON s.service_id = a.service_id
    WHERE c.follow_up_date IS NOT NULL
    ORDER BY c.follow_up_date ASC
"), MYSQLI_ASSOC);

$due = array_filter($followups, function ($row) {
    return (int) $row['booked_after'] === 0 && strtotime($row['follow_up_date']) <= strtotime('+7 days');
});

render_header('Follow-Ups', 'admin');
render_flash();
?>
    <h2>Follow-up &amp; attendance tracking</h2>

    <div class="grid">
        <div class="stat"><span class="value"><?= count($missed) ?></span><span class="label">Missed / cancelled</span></div>
        <div class="stat"><span class="value"><?= count($followups) ?></span><span class="label">Follow-ups ordered</span></div>
        <div class="stat"><span class="value"><?= count($due) ?></span><span class="label">Need a call now</span></div>
    </div>

    <div class="card">
        <h3>Follow-ups due (not yet re-booked)</h3>
        <table class="table">
            <thead><tr><th>Due</th><th>Patient</th><th>Contact</th><th>Diagnosis</th><th>Doctor</th></tr></thead>
            <tbody>
            <?php foreach ($due as $row) { ?>
                <tr>
                    <td><?= h(date('d M Y', strtotime($row['follow_up_date']))) ?></td>
                    <td><?= h($row['full_name']) ?></td>
                    <td><?= h($row['phone'] ?: $row['email']) ?></td>
                    <td><?= h($row['diagnosis'] ?: '-') ?></td>
                    <td><?= h($row['service_name']) ?></td>
                </tr>
            <?php } ?>
            <?php if (!$due) { ?>
                <tr><td colspan="5" class="muted">Nothing to chase right now.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Missed and cancelled appointments</h3>
        <p class="muted">Bookings are flagged as missed automatically <?= NO_SHOW_GRACE_MINUTES ?> minutes after the slot passes without an arrival.</p>
        <table class="table">
            <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Contact</th><th>Doctor</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($missed as $row) { ?>
                <tr>
                    <td><?= h($row['appointment_date']) ?></td>
                    <td><?= h(date('g:i A', strtotime($row['appointment_time']))) ?></td>
                    <td><?= h($row['full_name']) ?></td>
                    <td><?= h($row['phone'] ?: $row['email']) ?></td>
                    <td><?= h($row['service_name']) ?></td>
                    <td><span class="badge badge-<?= h($row['status']) ?>"><?= h(status_label($row['status'])) ?></span></td>
                </tr>
            <?php } ?>
            <?php if (!$missed) { ?>
                <tr><td colspan="6" class="muted">No missed or cancelled appointments.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
<?php
render_footer();
?>
