<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('customer');
$conn = getDB();
sweep_no_shows($conn);

$stmt = mysqli_prepare($conn, "
    SELECT a.*, s.service_name
    FROM appointments a
    JOIN services s ON s.service_id = a.service_id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
mysqli_stmt_execute($stmt);
$appointments = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$active = null;
foreach ($appointments as $appointment) {
    if (in_array($appointment['status'], OPEN_STATUSES, true)) {
        $active = $appointment;
    }
}

$queue = $active ? queue_entry($conn, $active['appointment_id']) : null;
$position = $queue ? queue_position($conn, $queue) : null;

render_header('Dashboard', 'customer');
render_flash();
?>
    <h3>Welcome, <?= h($user['full_name']) ?></h3>
    <p class="muted">Book a visit, track your queue number, and follow your appointment status live.</p>

    <?php if ($active) { ?>
        <div class="card">
            <h3>Current visit</h3>
            <div class="grid">
                <div>
                    <p><strong><?= h($active['service_name']) ?></strong></p>
                    <p><?= h(date('D, d M Y', strtotime($active['appointment_date']))) ?>
                       at <?= h(date('g:i A', strtotime($active['appointment_time']))) ?></p>
                    <p>Status:
                        <span class="badge badge-<?= h($active['status']) ?>"><?= h(status_label($active['status'])) ?></span>
                    </p>
                    <?php if ($active['status'] === 'booked') { ?>
                        <p class="muted">Your queue number is issued when the front desk confirms your arrival.</p>
                        <form method="POST" action="cancel_appointment.php" onsubmit="return confirm('Cancel this appointment?')">
                            <input type="hidden" name="appointment_id" value="<?= (int) $active['appointment_id'] ?>">
                            <button type="submit">Cancel appointment</button>
                        </form>
                    <?php } ?>
                </div>
                <?php if ($queue) { ?>
                    <div class="ticket">
                        <div class="label">Queue number</div>
                        <div class="number"><?= (int) $queue['queue_number'] ?></div>
                        <?php if ($position !== null) { ?>
                            <div>Position <?= (int) $position ?> &middot; about <?= (int) (($position - 1) * 10) ?> min wait</div>
                        <?php } else { ?>
                            <div><?= h(ucfirst($queue['status'])) ?></div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } else { ?>
        <div class="card">
            <h3>No active visit</h3>
            <p>You have no upcoming appointment. <a href="book-appointment.php">Book one now</a>.</p>
        </div>
    <?php } ?>

    <h3>Your appointments</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $appointment) { ?>
            <tr>
                <td><?= h($appointment['appointment_date']) ?></td>
                <td><?= h(date('g:i A', strtotime($appointment['appointment_time']))) ?></td>
                <td><?= h($appointment['service_name']) ?></td>
                <td><span class="badge badge-<?= h($appointment['status']) ?>"><?= h(status_label($appointment['status'])) ?></span></td>
                <td>
                    <?php if ($appointment['status'] === 'booked') { ?>
                        <form class="inline-form" method="POST" action="cancel_appointment.php"
                              onsubmit="return confirm('Cancel this appointment?')">
                            <input type="hidden" name="appointment_id" value="<?= (int) $appointment['appointment_id'] ?>">
                            <button type="submit">Cancel</button>
                        </form>
                    <?php } elseif ($appointment['status'] === 'done') { ?>
                        <a href="my-records.php#visit-<?= (int) $appointment['appointment_id'] ?>">View record</a>
                    <?php } else { ?>
                        -
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$appointments) { ?>
            <tr><td colspan="5" class="muted">No appointments yet.</td></tr>
        <?php } ?>
        </tbody>
    </table>
<?php
render_footer();
?>
