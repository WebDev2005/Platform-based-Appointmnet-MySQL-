<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reports.php';

require_role('admin');
$conn = getDB();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$report = build_report($conn, $from, $to);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="clinic-report-' . $from . '-to-' . $to . '.csv"');

$out = fopen('php://output', 'w');

fputcsv($out, ['Clinic summary report', "$from to $to"]);
fputcsv($out, []);

fputcsv($out, ['Metric', 'Value']);
fputcsv($out, ['Appointments', $report['totals']['appointments']]);
fputcsv($out, ['Completed', $report['totals']['done']]);
fputcsv($out, ['Cancelled', $report['totals']['cancelled']]);
fputcsv($out, ['Missed', $report['totals']['no_show']]);
fputcsv($out, ['Completion rate %', $report['totals']['completion_rate']]);
fputcsv($out, ['No-show rate %', $report['totals']['no_show_rate']]);
fputcsv($out, ['Collected', $report['billing']['collected']]);
fputcsv($out, ['Outstanding', $report['billing']['outstanding']]);
fputcsv($out, []);

fputcsv($out, ['Doctor', 'Appointments', 'Completed', 'Missed', 'Billed']);
foreach ($report['by_service'] as $row) {
    fputcsv($out, [$row['service_name'], $row['appointments'], $row['done'], $row['no_show'], $row['billed']]);
}
fputcsv($out, []);

fputcsv($out, ['Diagnosis', 'Cases']);
foreach ($report['diagnoses'] as $row) {
    fputcsv($out, [$row['diagnosis'], $row['cases']]);
}
fputcsv($out, []);

fputcsv($out, ['Date', 'Appointments', 'Completed', 'Missed']);
foreach ($report['daily'] as $row) {
    fputcsv($out, [$row['appointment_date'], $row['appointments'], $row['done'], $row['no_show']]);
}

fclose($out);
?>
