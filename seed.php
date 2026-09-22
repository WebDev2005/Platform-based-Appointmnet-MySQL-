<?php
/**
 * Creates demo staff, physician, admin and patient accounts plus a few
 * appointments so the workflow can be walked through end to end.
 * Run with `php seed.php`. Never run it against production data.
 */
require_once __DIR__ . '/db.php';

if (php_sapi_name() !== 'cli') {
    die("seed.php is a command line script.\n");
}

$conn = getDB();

function upsert_user($conn, $name, $email, $password, $role, $service_id = null) {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "
        INSERT INTO users (full_name, email, password, role, service_id)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), password = VALUES(password),
                                role = VALUES(role), service_id = VALUES(service_id)
    ");
    mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $hash, $role, $service_id);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    return (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['user_id'];
}

$services = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM services ORDER BY service_id"), MYSQLI_ASSOC);

if (!$services) {
    die("No services found. Run `php migrate.php` first.\n");
}

$service_id = (int) $services[0]['service_id'];

$admin   = upsert_user($conn, 'Clinic Administrator', 'admin@clinic.test', 'password123', 'admin');
$staff   = upsert_user($conn, 'Front Desk Nurse', 'staff@clinic.test', 'password123', 'staff');
$doctor  = upsert_user($conn, 'Dr. Reyes', 'doctor@clinic.test', 'password123', 'doctor', $service_id);
$patient = upsert_user($conn, 'Maria Dela Cruz', 'patient@clinic.test', 'password123', 'customer');

$stmt = mysqli_prepare($conn, "UPDATE users SET phone = '0917-555-0134', birth_date = '1992-04-18' WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $patient);
mysqli_stmt_execute($stmt);

function book($conn, $user_id, $service_id, $date, $time, $reason, $status) {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO appointments (user_id, service_id, appointment_date, appointment_time, reason, status)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE reason = VALUES(reason), status = VALUES(status)
    ");
    mysqli_stmt_bind_param($stmt, "iissss", $user_id, $service_id, $date, $time, $reason, $status);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn, "
        SELECT appointment_id FROM appointments
        WHERE service_id = ? AND appointment_date = ? AND appointment_time = ?
    ");
    mysqli_stmt_bind_param($stmt, "iss", $service_id, $date, $time);
    mysqli_stmt_execute($stmt);

    return (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['appointment_id'];
}

$today = date('Y-m-d');
$past = date('Y-m-d', strtotime('-21 days'));

$history = book($conn, $patient, $service_id, $past, '10:00:00', 'Fever and cough', 'done');
$upcoming = book($conn, $patient, $service_id, $today, '16:30:00', 'Follow-up check', 'booked');

$stmt = mysqli_prepare($conn, "
    INSERT INTO consultations (appointment_id, chief_complaint, diagnosis, notes, follow_up_date, physician_id)
    VALUES (?, 'Fever and cough', 'Acute bronchitis', 'Advised rest and fluids.', ?, ?)
    ON DUPLICATE KEY UPDATE diagnosis = VALUES(diagnosis)
");
$follow_up = date('Y-m-d', strtotime('+3 days'));
mysqli_stmt_bind_param($stmt, "isi", $history, $follow_up, $doctor);
mysqli_stmt_execute($stmt);

$stmt = mysqli_prepare($conn, "
    INSERT INTO vitals (appointment_id, temperature, blood_pressure, pulse, respiratory, weight_kg, height_cm, recorded_by)
    VALUES (?, 38.2, '118/76', 92, 18, 54.5, 160, ?)
    ON DUPLICATE KEY UPDATE temperature = VALUES(temperature)
");
mysqli_stmt_bind_param($stmt, "ii", $history, $staff);
mysqli_stmt_execute($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM prescriptions WHERE appointment_id = ?");
mysqli_stmt_bind_param($stmt, "i", $history);
mysqli_stmt_execute($stmt);

if ((int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'] === 0) {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO prescriptions (appointment_id, drug_name, dosage, frequency, duration, instructions, prescribed_by)
        VALUES (?, 'Amoxicillin', '500 mg', '3x a day', '7 days', 'After meals', ?)
    ");
    mysqli_stmt_bind_param($stmt, "ii", $history, $doctor);
    mysqli_stmt_execute($stmt);
}

require_once __DIR__ . '/includes/helpers.php';
$appointment = find_appointment($conn, $history);
ensure_invoice($conn, $appointment);

echo "Seeded demo accounts (password123): admin@clinic.test, staff@clinic.test, doctor@clinic.test, patient@clinic.test\n";
echo "Demo appointments: #$history (completed visit), #$upcoming (booked today)\n";
?>
