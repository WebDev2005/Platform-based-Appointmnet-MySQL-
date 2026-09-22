<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('staff', 'admin');
$conn = getDB();
sweep_no_shows($conn);

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = mysqli_prepare($conn, "
    SELECT a.*, u.full_name, u.phone, s.service_name,
           q.queue_number, q.status AS queue_status,
           v.vital_id
    FROM appointments a
    JOIN users u ON u.user_id = a.user_id
    JOIN services s ON s.service_id = a.service_id
    LEFT JOIN queue q ON q.appointment_id = a.appointment_id
    LEFT JOIN vitals v ON v.appointment_id = a.appointment_id
    WHERE a.appointment_date = ?
    ORDER BY a.appointment_time ASC
");
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$appointments = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$counts = ['booked' => 0, 'arrived' => 0, 'done' => 0, 'no_show' => 0];
foreach ($appointments as $appointment) {
    $key = $appointment['status'] === 'vitals_done' || $appointment['status'] === 'in_consultation'
        ? 'arrived'
        : $appointment['status'];
    if (isset($counts[$key])) {
        $counts[$key]++;
    }
}

render_header('Front Desk', 'staff');
render_flash();
?>
    <h2>Front desk &mdash; <?= h(date('D, d M Y', strtotime($date))) ?></h2>

    <form method="GET" class="form-row">
        <div>
            <label for="date">Show day:</label>
            <input type="date" id="date" name="date" value="<?= h($date) ?>">
        </div>
        <div><button type="submit">Load</button></div>
    </form>

    <div class="grid">
        <div class="stat"><span class="value"><?= count($appointments) ?></span><span class="label">Scheduled</span></div>
        <div class="stat"><span class="value"><?= $counts['booked'] ?></span><span class="label">Awaiting arrival</span></div>
        <div class="stat"><span class="value"><?= $counts['arrived'] ?></span><span class="label">In clinic</span></div>
        <div class="stat"><span class="value"><?= $counts['done'] ?></span><span class="label">Completed</span></div>
        <div class="stat"><span class="value"><?= $counts['no_show'] ?></span><span class="label">Missed</span></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Queue #</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $appointment) { ?>
            <tr>
                <td><?= h(date('g:i A', strtotime($appointment['appointment_time']))) ?></td>
                <td><?= h($appointment['full_name']) ?><br><span class="muted"><?= h($appointment['phone']) ?></span></td>
                <td><?= h($appointment['service_name']) ?></td>
                <td><?= $appointment['queue_number'] ? (int) $appointment['queue_number'] : '-' ?></td>
                <td><span class="badge badge-<?= h($appointment['status']) ?>"><?= h(status_label($appointment['status'])) ?></span></td>
                <td>
                    <?php if (in_array($appointment['status'], ['booked', 'no_show'], true)) { ?>
                        <form class="inline-form" method="POST" action="confirm_arrival.php">
                            <input type="hidden" name="appointment_id" value="<?= (int) $appointment['appointment_id'] ?>">
                            <input type="hidden" name="return_to" value="staff-dashboard.php?date=<?= h($date) ?>">
                            <button type="submit">Confirm arrival</button>
                        </form>
                    <?php } ?>

                    <?php if (in_array($appointment['status'], ['arrived', 'vitals_done', 'in_consultation'], true)) { ?>
                        <a href="vitals.php?appointment_id=<?= (int) $appointment['appointment_id'] ?>">
                            <?= $appointment['vital_id'] ? 'Edit vitals' : 'Log vitals' ?>
                        </a>
                    <?php } ?>

                    <?php if (!in_array($appointment['status'], ['done', 'cancelled'], true)) { ?>
                        <form class="inline-form" method="POST" action="cancel_appointment.php"
                              onsubmit="return confirm('Cancel this appointment?')">
                            <input type="hidden" name="appointment_id" value="<?= (int) $appointment['appointment_id'] ?>">
                            <input type="hidden" name="return_to" value="staff-dashboard.php?date=<?= h($date) ?>">
                            <button type="submit">Cancel</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$appointments) { ?>
            <tr><td colspan="6" class="muted">No appointments booked for this day.</td></tr>
        <?php } ?>
        </tbody>
    </table>
<?php
render_footer();
?>
