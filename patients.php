<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/chart.php';

$user = require_role('staff', 'doctor', 'admin');
$conn = getDB();

$search = trim($_GET['q'] ?? '');
$selected_id = (int) ($_GET['patient_id'] ?? 0);

$like = '%' . $search . '%';
$stmt = mysqli_prepare($conn, "
    SELECT user_id, full_name, email, phone, birth_date
    FROM users
    WHERE role = 'customer' AND (full_name LIKE ? OR email LIKE ?)
    ORDER BY full_name
    LIMIT 50
");
mysqli_stmt_bind_param($stmt, "ss", $like, $like);
mysqli_stmt_execute($stmt);
$patients = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$selected = null;
if ($selected_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ? AND role = 'customer'");
    mysqli_stmt_bind_param($stmt, "i", $selected_id);
    mysqli_stmt_execute($stmt);
    $selected = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

render_header('Patients', $user['role'] === 'admin' ? 'admin' : $user['role']);
?>
    <h2>Patient directory</h2>

    <form method="GET" class="form-row">
        <div>
            <label for="q">Search name or email</label>
            <input type="text" id="q" name="q" value="<?= h($search) ?>">
        </div>
        <div><button type="submit">Search</button></div>
    </form>

    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Date of birth</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($patients as $patient) { ?>
            <tr>
                <td><?= h($patient['full_name']) ?></td>
                <td><?= h($patient['email']) ?></td>
                <td><?= h($patient['phone'] ?: '-') ?></td>
                <td><?= h($patient['birth_date'] ?: '-') ?></td>
                <td>
                    <a href="?q=<?= urlencode($search) ?>&patient_id=<?= (int) $patient['user_id'] ?>">Open chart</a>
                </td>
            </tr>
        <?php } ?>
        <?php if (!$patients) { ?>
            <tr><td colspan="5" class="muted">No patients found.</td></tr>
        <?php } ?>
        </tbody>
    </table>

    <?php if ($selected) { ?>
        <h2>Chart &mdash; <?= h($selected['full_name']) ?></h2>
        <p class="muted">
            <?= h($selected['email']) ?> &middot; <?= h($selected['phone'] ?: 'no phone') ?>
            &middot; DOB <?= h($selected['birth_date'] ?: '-') ?>
        </p>
        <?php
        $visits = patient_visits($conn, $selected['user_id']);
        foreach ($visits as $visit) {
            render_visit_card($conn, $visit);
        }
        if (!$visits) {
            echo '<p class="muted">No visits on record.</p>';
        }
        ?>
    <?php } ?>
<?php
render_footer();
?>
