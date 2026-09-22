<?php
/**
 * Applies database.sql and brings older databases up to date by adding any
 * missing columns. Run with `php migrate.php`, or open it in the browser
 * while signed in as an admin.
 */
require_once __DIR__ . '/db.php';

if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/includes/auth.php';
    require_role('admin');
    header('Content-Type: text/plain');
}

$conn = getDB();

$sql = file_get_contents(__DIR__ . '/database.sql');

if (!mysqli_multi_query($conn, $sql)) {
    die("Schema failed: " . mysqli_error($conn) . "\n");
}

while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
    // Drain the result sets so the connection can be reused.
}

$columns = [
    'users'        => [
        'phone'      => "VARCHAR(40) DEFAULT NULL",
        'birth_date' => "DATE DEFAULT NULL",
        'address'    => "VARCHAR(255) DEFAULT NULL",
        'service_id' => "INT DEFAULT NULL",
        'created_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ],
    'services'     => [
        'specialty'    => "VARCHAR(120) DEFAULT NULL",
        'fee'          => "DECIMAL(10,2) NOT NULL DEFAULT 500.00",
        'slot_minutes' => "INT NOT NULL DEFAULT 30",
        'open_time'    => "TIME NOT NULL DEFAULT '09:00:00'",
        'close_time'   => "TIME NOT NULL DEFAULT '17:00:00'",
    ],
    'appointments' => [
        'reason'   => "VARCHAR(255) DEFAULT NULL",
        'archived' => "TINYINT(1) NOT NULL DEFAULT 0",
    ],
    'queue'        => [
        'queue_date'   => "DATE NOT NULL DEFAULT (CURRENT_DATE)",
        'completed_at' => "DATETIME DEFAULT NULL",
    ],
];

$database = db_setting(['MYSQLDATABASE', 'DB_NAME'], 'railway');

foreach ($columns as $table => $definitions) {
    foreach ($definitions as $column => $definition) {
        $stmt = mysqli_prepare($conn, "
            SELECT COUNT(*) AS n FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ? AND column_name = ?
        ");
        mysqli_stmt_bind_param($stmt, "sss", $database, $table, $column);
        mysqli_stmt_execute($stmt);
        $exists = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['n'] > 0;

        if (!$exists) {
            mysqli_query($conn, "ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "added $table.$column\n";
        }
    }
}

// Old rows stored the booking date only on the appointment.
mysqli_query($conn, "
    UPDATE queue q
    JOIN appointments a ON a.appointment_id = q.appointment_id
    SET q.queue_date = a.appointment_date
    WHERE q.queue_date IS NULL OR q.queue_date = '0000-00-00'
");

// 'pending' was the pre-clinical-module status name.
mysqli_query($conn, "UPDATE appointments SET status = 'booked' WHERE status IN ('pending', '')");

echo "migration complete\n";
?>
