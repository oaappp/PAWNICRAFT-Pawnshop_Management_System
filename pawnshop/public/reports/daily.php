<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_role(['admin','cashier']);

$db = (new Database())->getConnection();
$date = $_GET['date'] ?? date('Y-m-d');

$stmtLoans = $db->prepare(
    "SELECT 'New Pawn' AS type, pawn_ticket_number AS ref, loan_amount AS amount
     FROM pawn_transactions
     WHERE transaction_date = :d"
);
$stmtLoans->execute([':d' => $date]);
$loans = $stmtLoans->fetchAll();

$stmtPays = $db->prepare(
    "SELECT payment_type AS type, receipt_number AS ref, total_amount AS amount
     FROM payments
     WHERE DATE(payment_date) = :d"
);
$stmtPays->execute([':d' => $date]);
$pays = $stmtPays->fetchAll();

$cashOut = array_sum(array_column($loans, 'amount'));
$cashIn = array_sum(array_column($pays, 'amount'));
?>
<h4>Daily Transaction Report</h4>

<form class="row g-3 mb-3">
    <div class="col-auto">
        <label class="col-form-label">Date</label>
    </div>
    <div class="col-auto">
        <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" class="form-control">
    </div>
    <div class="col-auto">
        <button class="btn btn-secondary">View</button>
    </div>
</form>

<h5>New Pawns (Cash Out)</h5>
<table class="table table-sm table-bordered">
    <thead><tr><th>Type</th><th>Ticket</th><th>Amount</th></tr></thead>
    <tbody>
    <?php foreach ($loans as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['type']); ?></td>
            <td><?php echo htmlspecialchars($r['ref']); ?></td>
            <td><?php echo number_format($r['amount'],2); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h5>Payments (Cash In)</h5>
<table class="table table-sm table-bordered">
    <thead><tr><th>Type</th><th>Receipt</th><th>Amount</th></tr></thead>
    <tbody>
    <?php foreach ($pays as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['type']); ?></td>
            <td><?php echo htmlspecialchars($r['ref']); ?></td>
            <td><?php echo number_format($r['amount'],2); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<p><strong>Total Cash Out:</strong> <?php echo number_format($cashOut,2); ?></p>
<p><strong>Total Cash In:</strong> <?php echo number_format($cashIn,2); ?></p>
<p><strong>Net Cash Flow:</strong> <?php echo number_format($cashIn - $cashOut,2); ?></p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>