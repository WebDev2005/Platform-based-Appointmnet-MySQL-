<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/reports.php';

require_role('admin');
$conn = getDB();
sweep_no_shows($conn);

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$report = build_report($conn, $from, $to);

render_header('Reports', 'admin');
?>
    <h2>Summary report</h2>

    <form method="GET" class="form-row">
        <div><label>From</label><input type="date" name="from" value="<?= h($from) ?>"></div>
        <div><label>To</label><input type="date" name="to" value="<?= h($to) ?>"></div>
        <div><button type="submit">Apply</button></div>
        <div>
            <a href="report_export.php?from=<?= h($from) ?>&to=<?= h($to) ?>">Download CSV</a>
        </div>
    </form>

    <div class="grid">
        <div class="stat"><span class="value"><?= (int) $report['totals']['appointments'] ?></span><span class="label">Appointments</span></div>
        <div class="stat"><span class="value"><?= (int) $report['totals']['done'] ?></span><span class="label">Completed</span></div>
        <div class="stat"><span class="value"><?= (int) $report['totals']['cancelled'] ?></span><span class="label">Cancelled</span></div>
        <div class="stat"><span class="value"><?= (int) $report['totals']['no_show'] ?></span><span class="label">Missed</span></div>
        <div class="stat"><span class="value"><?= h($report['totals']['completion_rate']) ?>%</span><span class="label">Completion rate</span></div>
        <div class="stat"><span class="value"><?= h($report['totals']['no_show_rate']) ?>%</span><span class="label">No-show rate</span></div>
        <div class="stat"><span class="value"><?= h(money($report['billing']['collected'])) ?></span><span class="label">Collected</span></div>
        <div class="stat"><span class="value"><?= h(money($report['billing']['outstanding'])) ?></span><span class="label">Outstanding</span></div>
    </div>

    <div class="card">
        <h3>By doctor</h3>
        <table class="table">
            <thead><tr><th>Doctor</th><th>Appointments</th><th>Completed</th><th>Missed</th><th>Billed</th></tr></thead>
            <tbody>
            <?php foreach ($report['by_service'] as $row) { ?>
                <tr>
                    <td><?= h($row['service_name']) ?></td>
                    <td><?= (int) $row['appointments'] ?></td>
                    <td><?= (int) $row['done'] ?></td>
                    <td><?= (int) $row['no_show'] ?></td>
                    <td><?= h(money($row['billed'])) ?></td>
                </tr>
            <?php } ?>
            <?php if (!$report['by_service']) { ?>
                <tr><td colspan="5" class="muted">No data for this range.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Top diagnoses</h3>
        <table class="table">
            <thead><tr><th>Diagnosis</th><th>Cases</th></tr></thead>
            <tbody>
            <?php foreach ($report['diagnoses'] as $row) { ?>
                <tr><td><?= h($row['diagnosis']) ?></td><td><?= (int) $row['cases'] ?></td></tr>
            <?php } ?>
            <?php if (!$report['diagnoses']) { ?>
                <tr><td colspan="2" class="muted">No diagnoses recorded.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Daily volume</h3>
        <table class="table">
            <thead><tr><th>Date</th><th>Appointments</th><th>Completed</th><th>Missed</th></tr></thead>
            <tbody>
            <?php foreach ($report['daily'] as $row) { ?>
                <tr>
                    <td><?= h($row['appointment_date']) ?></td>
                    <td><?= (int) $row['appointments'] ?></td>
                    <td><?= (int) $row['done'] ?></td>
                    <td><?= (int) $row['no_show'] ?></td>
                </tr>
            <?php } ?>
            <?php if (!$report['daily']) { ?>
                <tr><td colspan="4" class="muted">No data for this range.</td></tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
<?php
render_footer();
?>
