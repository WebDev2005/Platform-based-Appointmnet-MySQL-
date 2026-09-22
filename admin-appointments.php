<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_role('admin');
$conn = getDB();
sweep_no_shows($conn);

$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT a.appointment_id, u.full_name, u.email, s.service_name,
           a.appointment_date, a.appointment_time, a.status, q.queue_number
    FROM appointments a
    JOIN users u ON u.user_id = a.user_id
    JOIN services s ON s.service_id = a.service_id
    LEFT JOIN queue q ON q.appointment_id = a.appointment_id
    WHERE 1 = 1
";
$types = '';
$params = [];

if ($status_filter !== '') {
    $sql .= " AND a.status = ?";
    $types .= 's';
    $params[] = $status_filter;
}

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $types .= 'ss';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 200";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$appointments = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

render_header('Appointments', 'admin');
render_flash();
?>
    <h2>All appointments</h2>

    <div id="customAlert" class="custom-alert"><p id="alertMessage"></p></div>

    <form method="GET" class="form-row">
        <div>
            <label>Patient</label>
            <input type="text" name="q" value="<?= h($search) ?>" placeholder="name or email">
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach (STATUS_LABELS as $value => $label) { ?>
                    <option value="<?= h($value) ?>" <?= $status_filter === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php } ?>
            </select>
        </div>
        <div><button type="submit">Filter</button></div>
        <div><button type="button" id="serveNextBtn">Serve next</button></div>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th><th>Time</th><th>Queue #</th><th>Patient</th>
                <th>Email</th><th>Doctor</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($appointments as $row) { ?>
            <tr>
                <td><?= h($row['appointment_date']) ?></td>
                <td><?= h(date('g:i A', strtotime($row['appointment_time']))) ?></td>
                <td><?= $row['queue_number'] ? (int) $row['queue_number'] : '-' ?></td>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['email']) ?></td>
                <td><?= h($row['service_name']) ?></td>
                <td><span class="badge badge-<?= h($row['status']) ?>"><?= h(status_label($row['status'])) ?></span></td>
                <td>
                    <a href="consultation.php?appointment_id=<?= (int) $row['appointment_id'] ?>">Chart</a>
                    <?php if (!in_array($row['status'], ['done', 'cancelled'], true)) { ?>
                        <form class="inline-form" method="POST" action="cancel_appointment.php"
                              onsubmit="return confirm('Cancel this appointment?')">
                            <input type="hidden" name="appointment_id" value="<?= (int) $row['appointment_id'] ?>">
                            <input type="hidden" name="return_to" value="admin-appointments.php">
                            <button type="submit">Cancel</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$appointments) { ?>
            <tr><td colspan="8" class="muted">No appointments match this filter.</td></tr>
        <?php } ?>
        </tbody>
    </table>
<?php
render_footer();
?>
