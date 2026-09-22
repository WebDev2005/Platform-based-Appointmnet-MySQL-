<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('doctor', 'admin');
$conn = getDB();
sweep_no_shows($conn);

$date = $_GET['date'] ?? date('Y-m-d');
$service_id = (int) ($_GET['service_id'] ?? $user['service_id'] ?? 0);

$services = mysqli_fetch_all(
    mysqli_query($conn, "SELECT * FROM services ORDER BY service_name"),
    MYSQLI_ASSOC
);

if (!$service_id && $services) {
    $service_id = (int) $services[0]['service_id'];
}

$stmt = mysqli_prepare($conn, "
    SELECT a.*, u.full_name, q.queue_number, q.status AS queue_status,
           v.blood_pressure, v.temperature, v.pulse,
           c.diagnosis
    FROM appointments a
    JOIN users u ON u.user_id = a.user_id
    LEFT JOIN queue q ON q.appointment_id = a.appointment_id
    LEFT JOIN vitals v ON v.appointment_id = a.appointment_id
    LEFT JOIN consultations c ON c.appointment_id = a.appointment_id
    WHERE a.service_id = ? AND a.appointment_date = ?
      AND a.status IN ('arrived', 'vitals_done', 'in_consultation', 'done')
    ORDER BY q.queue_number IS NULL, q.queue_number ASC, a.appointment_time ASC
");
mysqli_stmt_bind_param($stmt, "is", $service_id, $date);
mysqli_stmt_execute($stmt);
$queue = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

render_header('My Queue', 'doctor');
render_flash();
?>
    <h2>Consultation queue &mdash; <?= h(date('D, d M Y', strtotime($date))) ?></h2>

    <form method="GET" class="form-row">
        <div>
            <label for="service_id">Doctor / clinic</label>
            <select id="service_id" name="service_id">
                <?php foreach ($services as $service) { ?>
                    <option value="<?= (int) $service['service_id'] ?>" <?= $service_id === (int) $service['service_id'] ? 'selected' : '' ?>>
                        <?= h($service['service_name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div>
            <label for="date">Date</label>
            <input type="date" id="date" name="date" value="<?= h($date) ?>">
        </div>
        <div><button type="submit">Load</button></div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Queue #</th>
                <th>Patient</th>
                <th>Slot</th>
                <th>Vitals</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($queue as $row) { ?>
            <tr>
                <td><?= $row['queue_number'] ? (int) $row['queue_number'] : '-' ?></td>
                <td><?= h($row['full_name']) ?><br><span class="muted"><?= h($row['reason']) ?></span></td>
                <td><?= h(date('g:i A', strtotime($row['appointment_time']))) ?></td>
                <td class="muted">
                    <?= $row['blood_pressure'] ? 'BP ' . h($row['blood_pressure']) : 'not taken' ?>
                    <?= $row['temperature'] ? ' &middot; ' . h($row['temperature']) . '&deg;C' : '' ?>
                </td>
                <td><span class="badge badge-<?= h($row['status']) ?>"><?= h(status_label($row['status'])) ?></span></td>
                <td>
                    <?php if ($row['status'] === 'done') { ?>
                        <a href="consultation.php?appointment_id=<?= (int) $row['appointment_id'] ?>">View record</a>
                    <?php } else { ?>
                        <a href="consultation.php?appointment_id=<?= (int) $row['appointment_id'] ?>">
                            <?= $row['status'] === 'in_consultation' ? 'Continue consultation' : 'Start consultation' ?>
                        </a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$queue) { ?>
            <tr><td colspan="6" class="muted">Nobody has arrived yet for this day.</td></tr>
        <?php } ?>
        </tbody>
    </table>
<?php
render_footer();
?>
