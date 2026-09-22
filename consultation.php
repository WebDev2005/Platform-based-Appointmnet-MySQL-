<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/chart.php';

$user = require_role('doctor', 'admin');
$conn = getDB();

$appointment_id = (int) ($_POST['appointment_id'] ?? $_GET['appointment_id'] ?? 0);
$appointment = find_appointment($conn, $appointment_id);

if (!$appointment) {
    flash('Appointment not found.', 'error');
    redirect('doctor-queue.php');
}

$self = 'consultation.php?appointment_id=' . $appointment_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'save_notes') {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO consultations (appointment_id, chief_complaint, diagnosis, notes, follow_up_date, physician_id)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                chief_complaint = VALUES(chief_complaint), diagnosis = VALUES(diagnosis),
                notes = VALUES(notes), follow_up_date = VALUES(follow_up_date),
                physician_id = VALUES(physician_id)
        ");
        $complaint = post('chief_complaint') ?: null;
        $diagnosis = post('diagnosis') ?: null;
        $notes = post('notes') ?: null;
        $follow_up = post('follow_up_date') ?: null;
        mysqli_stmt_bind_param($stmt, "issssi", $appointment_id, $complaint, $diagnosis, $notes, $follow_up, $user['user_id']);
        mysqli_stmt_execute($stmt);

        flash('Consultation notes saved.');
        redirect($self);
    }

    if ($action === 'add_prescription') {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO prescriptions (appointment_id, drug_name, dosage, frequency, duration, instructions, prescribed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $drug = post('drug_name');
        $dosage = post('dosage');
        $frequency = post('frequency');
        $duration = post('duration');
        $instructions = post('instructions');

        if ($drug === '') {
            flash('Drug name is required.', 'error');
            redirect($self);
        }

        mysqli_stmt_bind_param($stmt, "isssssi", $appointment_id, $drug, $dosage, $frequency, $duration, $instructions, $user['user_id']);
        mysqli_stmt_execute($stmt);

        flash('Prescription added.');
        redirect($self);
    }

    if ($action === 'delete_prescription') {
        $stmt = mysqli_prepare($conn, "DELETE FROM prescriptions WHERE prescription_id = ? AND appointment_id = ?");
        $prescription_id = (int) post('prescription_id');
        mysqli_stmt_bind_param($stmt, "ii", $prescription_id, $appointment_id);
        mysqli_stmt_execute($stmt);

        flash('Prescription removed.');
        redirect($self);
    }

    if ($action === 'complete') {
        set_appointment_status($conn, $appointment_id, 'done');
        set_queue_status($conn, $appointment_id, 'done');
        ensure_invoice($conn, $appointment);

        flash('Visit completed and sent to billing.');
        redirect('doctor-queue.php');
    }
}

// Opening the chart of a waiting patient moves them into consultation.
if (in_array($appointment['status'], ['arrived', 'vitals_done'], true)) {
    set_appointment_status($conn, $appointment_id, 'in_consultation');
    set_queue_status($conn, $appointment_id, 'serving');
    $appointment['status'] = 'in_consultation';
}

$stmt = mysqli_prepare($conn, "SELECT * FROM vitals WHERE appointment_id = ?");
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$vitals = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];

$stmt = mysqli_prepare($conn, "SELECT * FROM consultations WHERE appointment_id = ?");
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$consultation = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];

$prescriptions = visit_prescriptions($conn, $appointment_id);
$history = patient_visits($conn, $appointment['user_id']);

render_header('Consultation', 'doctor');
render_flash();
?>
    <h2><?= h($appointment['full_name']) ?>
        <span class="badge badge-<?= h($appointment['status']) ?>"><?= h(status_label($appointment['status'])) ?></span>
    </h2>
    <p class="muted">
        <?= h($appointment['service_name']) ?> &middot;
        <?= h(date('d M Y g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']))) ?>
        &middot; Reason: <?= h($appointment['reason'] ?: '-') ?>
    </p>

    <div class="card">
        <h3>Vitals</h3>
        <?php if ($vitals) { ?>
            <p>
                BP <?= h($vitals['blood_pressure'] ?: '-') ?> &middot;
                Temp <?= h($vitals['temperature'] ?: '-') ?>&deg;C &middot;
                Pulse <?= h($vitals['pulse'] ?: '-') ?> &middot;
                RR <?= h($vitals['respiratory'] ?: '-') ?> &middot;
                <?= h($vitals['weight_kg'] ?: '-') ?> kg / <?= h($vitals['height_cm'] ?: '-') ?> cm
            </p>
            <p class="muted"><?= h($vitals['notes'] ?: '') ?></p>
        <?php } else { ?>
            <p class="muted">No vitals recorded yet.</p>
        <?php } ?>
        <a href="vitals.php?appointment_id=<?= (int) $appointment_id ?>&return_to=<?= urlencode($self) ?>">Record vitals</a>
    </div>

    <div class="card">
        <h3>Diagnosis &amp; notes</h3>
        <form method="POST">
            <input type="hidden" name="appointment_id" value="<?= (int) $appointment_id ?>">
            <input type="hidden" name="action" value="save_notes">

            <label>Chief complaint</label>
            <input type="text" name="chief_complaint" value="<?= h($consultation['chief_complaint'] ?? $appointment['reason']) ?>">

            <label>Diagnosis</label>
            <input type="text" name="diagnosis" value="<?= h($consultation['diagnosis'] ?? '') ?>">

            <label>Clinical notes</label>
            <textarea name="notes" rows="4"><?= h($consultation['notes'] ?? '') ?></textarea>

            <label>Follow-up date</label>
            <input type="date" name="follow_up_date" value="<?= h($consultation['follow_up_date'] ?? '') ?>">

            <button type="submit">Save notes</button>
        </form>
    </div>

    <div class="card">
        <h3>Prescription orders</h3>
        <table class="table">
            <thead>
                <tr><th>Drug</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($prescriptions as $rx) { ?>
                <tr>
                    <td><?= h($rx['drug_name']) ?></td>
                    <td><?= h($rx['dosage']) ?></td>
                    <td><?= h($rx['frequency']) ?></td>
                    <td><?= h($rx['duration']) ?></td>
                    <td><?= h($rx['instructions']) ?></td>
                    <td>
                        <form class="inline-form" method="POST">
                            <input type="hidden" name="appointment_id" value="<?= (int) $appointment_id ?>">
                            <input type="hidden" name="action" value="delete_prescription">
                            <input type="hidden" name="prescription_id" value="<?= (int) $rx['prescription_id'] ?>">
                            <button type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            <?php if (!$prescriptions) { ?>
                <tr><td colspan="6" class="muted">No drugs ordered yet.</td></tr>
            <?php } ?>
            </tbody>
        </table>

        <form method="POST" class="form-row">
            <input type="hidden" name="appointment_id" value="<?= (int) $appointment_id ?>">
            <input type="hidden" name="action" value="add_prescription">
            <div><label>Drug</label><input type="text" name="drug_name" required></div>
            <div><label>Dosage</label><input type="text" name="dosage" placeholder="500 mg"></div>
            <div><label>Frequency</label><input type="text" name="frequency" placeholder="3x a day"></div>
            <div><label>Duration</label><input type="text" name="duration" placeholder="7 days"></div>
            <div><label>Instructions</label><input type="text" name="instructions" placeholder="after meals"></div>
            <div><button type="submit">Add drug</button></div>
        </form>
    </div>

    <?php if ($appointment['status'] !== 'done') { ?>
        <form method="POST" onsubmit="return confirm('Complete this visit and create the billing entry?')">
            <input type="hidden" name="appointment_id" value="<?= (int) $appointment_id ?>">
            <input type="hidden" name="action" value="complete">
            <button type="submit">Complete visit &amp; send to checkout</button>
        </form>
    <?php } ?>

    <h3>Previous visits</h3>
    <?php
    $shown = 0;
    foreach ($history as $visit) {
        if ((int) $visit['appointment_id'] === $appointment_id) {
            continue;
        }
        render_visit_card($conn, $visit);
        $shown++;
    }
    if (!$shown) {
        echo '<p class="muted">First visit on record.</p>';
    }

render_footer();
?>
