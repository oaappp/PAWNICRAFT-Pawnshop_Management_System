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

// Only sold auctions (for profit/loss)
$stmt = $db->prepare(
    "SELECT a.*, pt.pawn_ticket_number, pt.loan_amount,
            c.customer_code, CONCAT(c.first_name,' ',c.last_name) AS customer_name
     FROM auctions a
     JOIN pawn_transactions pt ON a.transaction_id = pt.transaction_id
     JOIN customers c ON pt.customer_id = c.customer_id
     WHERE a.auction_date BETWEEN :f AND :t
       AND a.status = 'sold'
     ORDER BY a.auction_date DESC"
);
$stmt->execute([':f' => $from, ':t' => $to]);
$rows = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<h4>Auction Report</h4>

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

<table class="table table-striped" id="tblAuctions">
    <thead>
    <tr>
        <th>Date</th>
        <th>Ticket</th>
        <th>Customer</th>
        <th>Loan Amount</th>
        <th>Starting Price</th>
        <th>Winning Bid</th>
        <th>Profit / Loss</th>
        <th>Bidder</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): 
        $profit = ($r['winning_bid'] ?? 0) - $r['loan_amount'];
    ?>
        <tr>
            <td><?php echo htmlspecialchars($r['auction_date']); ?></td>
            <td><?php echo htmlspecialchars($r['pawn_ticket_number']); ?></td>
            <td><?php echo htmlspecialchars($r['customer_code'] . ' - ' . $r['customer_name']); ?></td>
            <td class="text-end"><?php echo number_format($r['loan_amount'], 2); ?></td>
            <td class="text-end"><?php echo number_format($r['starting_price'], 2); ?></td>
            <td class="text-end"><?php echo number_format($r['winning_bid'], 2); ?></td>
            <td class="text-end <?php echo $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo number_format($profit, 2); ?>
            </td>
            <td><?php echo htmlspecialchars($r['bidder_name'] . ' ' . $r['bidder_contact']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
$(function () {
    $('#tblAuctions').DataTable();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>