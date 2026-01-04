<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin','cashier','appraiser']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

// Active / unredeemed statuses
$sql = "SELECT pt.*, c.customer_code,
               CONCAT(c.first_name,' ',c.last_name) AS customer_name
        FROM pawn_transactions pt
        JOIN customers c ON pt.customer_id = c.customer_id
        WHERE pt.status IN ('active','renewed','expired','auctioned')
        ORDER BY pt.maturity_date ASC";

$rows = $db->query($sql)->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<h4>Active Loans Summary</h4>

<table class="table table-striped" id="tblActive">
    <thead>
    <tr>
        <th>Pawn Ticket</th>
        <th>Customer</th>
        <th>Loan Date</th>
        <th>Loan Amount</th>
        <th>Interest %</th>
        <th>Maturity Date</th>
        <th>Grace End</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['pawn_ticket_number']); ?></td>
            <td><?php echo htmlspecialchars($r['customer_code'] . ' - ' . $r['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($r['transaction_date']); ?></td>
            <td class="text-end"><?php echo number_format($r['loan_amount'], 2); ?></td>
            <td class="text-end"><?php echo number_format($r['interest_rate'], 2); ?></td>
            <td><?php echo htmlspecialchars($r['maturity_date']); ?></td>
            <td><?php echo htmlspecialchars($r['grace_period_end']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(function () {
    $('#tblActive').DataTable({
        order: [[5, 'asc']]  // sort by maturity date
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>