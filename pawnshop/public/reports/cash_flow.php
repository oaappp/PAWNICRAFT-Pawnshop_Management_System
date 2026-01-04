<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin','cashier']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Loans released (cash out)
$stmtLoans = $db->prepare(
    "SELECT transaction_date, pawn_ticket_number, loan_amount
     FROM pawn_transactions
     WHERE transaction_date BETWEEN :f AND :t
     ORDER BY transaction_date"
);
$stmtLoans->execute([':f' => $from, ':t' => $to]);
$loans = $stmtLoans->fetchAll();

// Payments (cash in)
$stmtPays = $db->prepare(
    "SELECT DATE(payment_date) AS pdate, payment_type, receipt_number, total_amount
     FROM payments
     WHERE DATE(payment_date) BETWEEN :f AND :t
     ORDER BY payment_date"
);
$stmtPays->execute([':f' => $from, ':t' => $to]);
$pays = $stmtPays->fetchAll();

$cashOut = array_sum(array_column($loans, 'loan_amount'));
$cashIn  = array_sum(array_column($pays, 'total_amount'));

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<h4>Cash Flow Report</h4>

<form class="row g-3 mb-3">
    <div class="col-auto">
        <label class="col-form-label">From</label>
    </div>
    <div class="col-auto">
        <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" class="form-control">
    </div>
    <div class="col-auto">
        <label class="col-form-label">To</label>
    </div>
    <div class="col-auto">
        <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" class="form-control">
    </div>
    <div class="col-auto">
        <button class="btn btn-secondary">View</button>
    </div>
</form>

<div class="row">
    <div class="col-md-6">
        <h5>Loans Released (Cash Out)</h5>
        <table class="table table-sm table-bordered" id="tblLoans">
            <thead>
            <tr>
                <th>Date</th>
                <th>Ticket</th>
                <th>Amount</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($loans as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['transaction_date']); ?></td>
                    <td><?php echo htmlspecialchars($r['pawn_ticket_number']); ?></td>
                    <td class="text-end"><?php echo number_format($r['loan_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="col-md-6">
        <h5>Payments (Cash In)</h5>
        <table class="table table-sm table-bordered" id="tblPays">
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Receipt</th>
                <th>Amount</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pays as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['pdate']); ?></td>
                    <td><?php echo htmlspecialchars($r['payment_type']); ?></td>
                    <td><?php echo htmlspecialchars($r['receipt_number']); ?></td>
                    <td class="text-end"><?php echo number_format($r['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="mt-3"><strong>Total Cash Out:</strong> <?php echo number_format($cashOut, 2); ?></p>
<p><strong>Total Cash In:</strong> <?php echo number_format($cashIn, 2); ?></p>
<p><strong>Net Cash Flow:</strong> <?php echo number_format($cashIn - $cashOut, 2); ?></p>

<script>
$(function () {
    $('#tblLoans').DataTable();
    $('#tblPays').DataTable();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>