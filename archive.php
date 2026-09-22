<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('admin');
$conn = getDB();

$self = 'archive.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'archive' || $action === 'restore') {
        $archived = $action === 'archive' ? 1 : 0;
        $appointment_id = (int) post('appointment_id');

        $stmt = mysqli_prepare($conn, "UPDATE appointments SET archived = ? WHERE appointment_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $archived, $appointment_id);
        mysqli_stmt_execute($stmt);

        flash($archived ? 'Visit archived.' : 'Visit restored.');
        redirect($self);
    }

    if ($action === 'archive_batch') {
        $days = max(1, (int) post('older_than_days', 90));
        $cutoff = date('Y-m-d', strtotime("-$days days"));

        $stmt = mysqli_prepare($conn, "
            UPDATE appointments
            SET archived = 1
            WHERE archived = 0
              AND appointment_date < ?
              AND status IN ('done', 'cancelled', 'no_show')
        ");
        mysqli_stmt_bind_param($stmt, "s", $cutoff);
        mysqli_stmt_execute($stmt);

        flash(mysqli_stmt_affected_rows($stmt) . " visit(s) archived (closed before $cutoff).");
        redirect($self);
    }
}

$show_archived = ($_GET['show'] ?? 'archived') === 'archived';
$archived_flag = $show_archived ? 1 : 0;

$stmt = mysqli_prepare($conn, "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.archived,
           u.full_name, s.service_name, c.diagnosis,
           q.queue_number,
           (SELECT COUNT(*) FROM prescriptions p WHERE p.appointment_id = a.appointment_id) AS drug_count
    FROM appointments a
    JOIN users u ON u.user_id = a.user_id
    JOIN services s ON s.service_id = a.service_id
    LEFT JOIN consultations c ON c.appointment_id = a.appointment_id
    LEFT JOIN queue q ON q.appointment_id = a.appointment_id
    WHERE a.status IN ('done', 'cancelled', 'no_show') AND a.archived = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 200
");
mysqli_stmt_bind_param($stmt, "i", $archived_flag);
mysqli_stmt_execute($stmt);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

render_header('Archive', 'admin');
render_flash();
?>
    <h2>Medical history archive</h2>
    <p class="muted">Closed visits (completed, cancelled or missed) with their diagnosis and prescription counts.</p>

    <div class="card">
        <form method="POST" class="form-row">
            <input type="hidden" name="action" value="archive_batch">
            <div>
                <label>Archive closed visits older than (days)</label>
                <input type="number" name="older_than_days" value="90" min="1">
            </div>
            <div><button type="submit">Run batch archive</button></div>
        </form>
    </div>

    <p>
        <a href="?show=archived">Archived</a> |
        <a href="?show=open">Closed but not archived</a>
    </p>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th><th>Queue #</th><th>Patient</th><th>Doctor</th>
                <th>Diagnosis</th><th>Drugs</th><th>Status</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row) { ?>
            <tr>
                <td><?= h($row['appointment_date']) ?></td>
                <td><?= $row['queue_number'] ? (int) $row['queue_number'] : '-' ?></td>
                <td><?= h($row['full_name']) ?></td>
                <td><?= h($row['service_name']) ?></td>
                <td><?= h($row['diagnosis'] ?: '-') ?></td>
                <td><?= (int) $row['drug_count'] ?></td>
                <td><span class="badge badge-<?= h($row['status']) ?>"><?= h(status_label($row['status'])) ?></span></td>
                <td>
                    <form class="inline-form" method="POST">
                        <input type="hidden" name="action" value="<?= $show_archived ? 'restore' : 'archive' ?>">
                        <input type="hidden" name="appointment_id" value="<?= (int) $row['appointment_id'] ?>">
                        <button type="submit"><?= $show_archived ? 'Restore' : 'Archive' ?></button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$rows) { ?>
            <tr><td colspan="8" class="muted">Nothing here.</td></tr>
        <?php } ?>
        </tbody>
    </table>
<?php
render_footer();
?>
