<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role('admin', 'staff');
$conn = getDB();

$self = 'admin-billing.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    $invoice_id = (int) post('invoice_id');

    if ($action === 'add_item') {
        $description = post('description');
        $amount = (float) post('amount');

        if ($description === '' || $amount <= 0) {
            flash('Enter a description and an amount greater than zero.', 'error');
        } else {
            add_invoice_item($conn, $invoice_id, $description, $amount);
            flash('Charge added.');
        }
        redirect($self);
    }

    if ($action === 'remove_item') {
        $item_id = (int) post('item_id');
        $stmt = mysqli_prepare($conn, "DELETE FROM invoice_items WHERE item_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);

        flash('Charge removed.');
        redirect($self);
    }

    if ($action === 'mark_paid') {
        $method = post('payment_method', 'cash');
        $stmt = mysqli_prepare($conn, "
            UPDATE invoices SET status = 'paid', payment_method = ?, paid_at = NOW() WHERE invoice_id = ?
        ");
        mysqli_stmt_bind_param($stmt, "si", $method, $invoice_id);
        mysqli_stmt_execute($stmt);

        flash('Invoice marked as paid.');
        redirect($self);
    }
}

$invoices = mysqli_fetch_all(mysqli_query($conn, "
    SELECT i.*, u.full_name, a.appointment_date, s.service_name,
           COALESCE((SELECT SUM(amount) FROM invoice_items WHERE invoice_id = i.invoice_id), 0) AS total
    FROM invoices i
    JOIN users u ON u.user_id = i.user_id
    JOIN appointments a ON a.appointment_id = i.appointment_id
    JOIN services s ON s.service_id = a.service_id
    ORDER BY i.status = 'paid', i.created_at DESC
"), MYSQLI_ASSOC);

$outstanding = 0;
$collected = 0;
foreach ($invoices as $invoice) {
    if ($invoice['status'] === 'paid') {
        $collected += (float) $invoice['total'];
    } else {
        $outstanding += (float) $invoice['total'];
    }
}

render_header('Billing', 'admin');
render_flash();
?>
    <h2>Checkout &amp; billing</h2>

    <div class="grid">
        <div class="stat"><span class="value"><?= count($invoices) ?></span><span class="label">Invoices</span></div>
        <div class="stat"><span class="value"><?= h(money($collected)) ?></span><span class="label">Collected</span></div>
        <div class="stat"><span class="value"><?= h(money($outstanding)) ?></span><span class="label">Outstanding</span></div>
    </div>

    <?php foreach ($invoices as $invoice) {
        $items = [];
        $stmt = mysqli_prepare($conn, "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY item_id");
        mysqli_stmt_bind_param($stmt, "i", $invoice['invoice_id']);
        mysqli_stmt_execute($stmt);
        $items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        ?>
        <div class="card">
            <h3>
                Invoice #<?= (int) $invoice['invoice_id'] ?> &mdash; <?= h($invoice['full_name']) ?>
                <span class="badge badge-<?= $invoice['status'] === 'paid' ? 'done' : 'cancelled' ?>">
                    <?= h(ucfirst($invoice['status'])) ?>
                </span>
            </h3>
            <p class="muted">
                <?= h($invoice['service_name']) ?> &middot;
                visit of <?= h(date('d M Y', strtotime($invoice['appointment_date']))) ?>
                <?= $invoice['payment_method'] ? '&middot; paid via ' . h(strtoupper($invoice['payment_method'])) : '' ?>
            </p>

            <table class="table">
                <thead><tr><th>Description</th><th>Amount</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $item) { ?>
                    <tr>
                        <td><?= h($item['description']) ?></td>
                        <td><?= h(money($item['amount'])) ?></td>
                        <td>
                            <?php if ($invoice['status'] !== 'paid') { ?>
                                <form class="inline-form" method="POST">
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                                    <button type="submit">Remove</button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                    <tr><th>Total</th><th><?= h(money($invoice['total'])) ?></th><th></th></tr>
                </tbody>
            </table>

            <?php if ($invoice['status'] !== 'paid') { ?>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_item">
                    <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoice_id'] ?>">
                    <div><label>Charge</label><input type="text" name="description" placeholder="Lab test"></div>
                    <div><label>Amount</label><input type="number" step="0.01" name="amount"></div>
                    <div><button type="submit">Add charge</button></div>
                </form>

                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="mark_paid">
                    <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoice_id'] ?>">
                    <div>
                        <label>Payment method</label>
                        <select name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="hmo">HMO</option>
                            <option value="ewallet">E-wallet</option>
                        </select>
                    </div>
                    <div><button type="submit">Mark as paid</button></div>
                </form>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!$invoices) { ?>
        <p class="muted">No invoices yet. They are created when a physician completes a visit.</p>
    <?php } ?>
<?php
render_footer();
?>
