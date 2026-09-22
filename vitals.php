<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('staff', 'admin', 'doctor');
$conn = getDB();

$appointment_id = (int) ($_POST['appointment_id'] ?? $_GET['appointment_id'] ?? 0);
$appointment = find_appointment($conn, $appointment_id);

if (!$appointment) {
    flash('Appointment not found.', 'error');
    redirect(home_for_role($user['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'temperature'    => post('temperature') !== '' ? (float) post('temperature') : null,
        'blood_pressure' => post('blood_pressure') ?: null,
        'pulse'          => post('pulse') !== '' ? (int) post('pulse') : null,
        'respiratory'    => post('respiratory') !== '' ? (int) post('respiratory') : null,
        'weight_kg'      => post('weight_kg') !== '' ? (float) post('weight_kg') : null,
        'height_cm'      => post('height_cm') !== '' ? (float) post('height_cm') : null,
        'notes'          => post('notes') ?: null,
    ];

    $stmt = mysqli_prepare($conn, "
        INSERT INTO vitals (appointment_id, temperature, blood_pressure, pulse, respiratory, weight_kg, height_cm, notes, recorded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            temperature = VALUES(temperature), blood_pressure = VALUES(blood_pressure),
            pulse = VALUES(pulse), respiratory = VALUES(respiratory),
            weight_kg = VALUES(weight_kg), height_cm = VALUES(height_cm),
            notes = VALUES(notes), recorded_by = VALUES(recorded_by)
    ");
    mysqli_stmt_bind_param($stmt, "idsiiddsi",
        $appointment_id, $fields['temperature'], $fields['blood_pressure'], $fields['pulse'],
        $fields['respiratory'], $fields['weight_kg'], $fields['height_cm'], $fields['notes'],
        $user['user_id']
    );
    mysqli_stmt_execute($stmt);

    if ($appointment['status'] === 'arrived') {
        set_appointment_status($conn, $appointment_id, 'vitals_done');
    }

    flash('Vitals saved for ' . $appointment['full_name'] . '.');
    redirect(post('return_to') ?: home_for_role($user['role']));
}

$stmt = mysqli_prepare($conn, "SELECT * FROM vitals WHERE appointment_id = ?");
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$vitals = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];

render_header('Pre-Consultation Vitals', $user['role'] === 'doctor' ? 'doctor' : 'staff');
?>
    <h2>Vitals &mdash; <?= h($appointment['full_name']) ?></h2>
    <p class="muted">
        <?= h($appointment['service_name']) ?> &middot;
        <?= h(date('d M Y', strtotime($appointment['appointment_date']))) ?>
        <?= h(date('g:i A', strtotime($appointment['appointment_time']))) ?>
    </p>

    <form method="POST" class="card">
        <input type="hidden" name="appointment_id" value="<?= (int) $appointment_id ?>">
        <input type="hidden" name="return_to" value="<?= h($_GET['return_to'] ?? home_for_role($user['role'])) ?>">

        <div class="form-row">
            <div>
                <label>Temperature (&deg;C)</label>
                <input type="number" step="0.1" name="temperature" value="<?= h($vitals['temperature'] ?? '') ?>">
            </div>
            <div>
                <label>Blood pressure</label>
                <input type="text" name="blood_pressure" placeholder="120/80" value="<?= h($vitals['blood_pressure'] ?? '') ?>">
            </div>
            <div>
                <label>Pulse (bpm)</label>
                <input type="number" name="pulse" value="<?= h($vitals['pulse'] ?? '') ?>">
            </div>
            <div>
                <label>Respiratory rate</label>
                <input type="number" name="respiratory" value="<?= h($vitals['respiratory'] ?? '') ?>">
            </div>
            <div>
                <label>Weight (kg)</label>
                <input type="number" step="0.1" name="weight_kg" value="<?= h($vitals['weight_kg'] ?? '') ?>">
            </div>
            <div>
                <label>Height (cm)</label>
                <input type="number" step="0.1" name="height_cm" value="<?= h($vitals['height_cm'] ?? '') ?>">
            </div>
        </div>

        <label>Nurse notes</label>
        <textarea name="notes" rows="3"><?= h($vitals['notes'] ?? '') ?></textarea>

        <button type="submit">Save vitals</button>
    </form>
<?php
render_footer();
?>
