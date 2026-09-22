<?php
require_once __DIR__ . '/helpers.php';

/** Full medical history of one patient: visits, vitals, notes, drugs, billing. */
function patient_visits($conn, $user_id, $include_archived = true) {
    $sql = "
        SELECT a.*, s.service_name,
               v.temperature, v.blood_pressure, v.pulse, v.respiratory, v.weight_kg, v.height_cm, v.notes AS vital_notes,
               c.chief_complaint, c.diagnosis, c.notes AS consult_notes, c.follow_up_date,
               i.invoice_id, i.status AS invoice_status, i.payment_method
        FROM appointments a
        JOIN services s ON s.service_id = a.service_id
        LEFT JOIN vitals v ON v.appointment_id = a.appointment_id
        LEFT JOIN consultations c ON c.appointment_id = a.appointment_id
        LEFT JOIN invoices i ON i.appointment_id = a.appointment_id
        WHERE a.user_id = ?
    ";

    if (!$include_archived) {
        $sql .= " AND a.archived = 0";
    }

    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function visit_prescriptions($conn, $appointment_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM prescriptions WHERE appointment_id = ? ORDER BY prescription_id");
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);

    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function render_visit_card($conn, $visit) {
    $prescriptions = visit_prescriptions($conn, $visit['appointment_id']);
    $total = $visit['invoice_id'] ? invoice_total($conn, $visit['invoice_id']) : null;
    ?>
    <div class="card" id="visit-<?= (int) $visit['appointment_id'] ?>">
        <h3>
            <?= h(date('d M Y', strtotime($visit['appointment_date']))) ?>
            &middot; <?= h($visit['service_name']) ?>
            <span class="badge badge-<?= h($visit['status']) ?>"><?= h(status_label($visit['status'])) ?></span>
            <?php if ((int) $visit['archived'] === 1) { ?><span class="badge">Archived</span><?php } ?>
        </h3>

        <p><strong>Reason:</strong> <?= h($visit['reason'] ?: '-') ?></p>

        <?php if ($visit['blood_pressure'] || $visit['temperature'] || $visit['pulse']) { ?>
            <p><strong>Vitals:</strong>
                BP <?= h($visit['blood_pressure'] ?: '-') ?> &middot;
                Temp <?= h($visit['temperature'] ?: '-') ?>&deg;C &middot;
                Pulse <?= h($visit['pulse'] ?: '-') ?> bpm &middot;
                RR <?= h($visit['respiratory'] ?: '-') ?> &middot;
                <?= h($visit['weight_kg'] ?: '-') ?> kg / <?= h($visit['height_cm'] ?: '-') ?> cm
            </p>
        <?php } ?>

        <?php if ($visit['diagnosis'] || $visit['consult_notes']) { ?>
            <p><strong>Diagnosis:</strong> <?= h($visit['diagnosis'] ?: '-') ?></p>
            <p><strong>Notes:</strong> <?= nl2br(h($visit['consult_notes'] ?: '-')) ?></p>
        <?php } ?>

        <?php if ($visit['follow_up_date']) { ?>
            <p><strong>Follow-up:</strong> <?= h(date('d M Y', strtotime($visit['follow_up_date']))) ?></p>
        <?php } ?>

        <?php if ($prescriptions) { ?>
            <p><strong>Prescription:</strong></p>
            <ul>
                <?php foreach ($prescriptions as $rx) { ?>
                    <li>
                        <?= h($rx['drug_name']) ?> <?= h($rx['dosage']) ?>
                        &mdash; <?= h($rx['frequency']) ?>, <?= h($rx['duration']) ?>
                        <?= $rx['instructions'] ? '(' . h($rx['instructions']) . ')' : '' ?>
                    </li>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if ($total !== null) { ?>
            <p><strong>Billing:</strong> <?= h(money($total)) ?>
                <span class="badge badge-<?= $visit['invoice_status'] === 'paid' ? 'done' : 'cancelled' ?>">
                    <?= h(ucfirst($visit['invoice_status'])) ?>
                </span>
                <?= $visit['payment_method'] ? '&middot; ' . h(strtoupper($visit['payment_method'])) : '' ?>
            </p>
        <?php } ?>
    </div>
    <?php
}
?>
