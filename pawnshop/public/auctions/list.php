<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

require_role(['admin','cashier']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

$auctions = $db->query(
    "SELECT a.*, pt.pawn_ticket_number, pt.loan_amount,
            c.customer_code, CONCAT(c.first_name,' ',c.last_name) AS customer_name
     FROM auctions a
     JOIN pawn_transactions pt ON a.transaction_id = pt.transaction_id
     JOIN customers c ON pt.customer_id = c.customer_id
     ORDER BY a.auction_date DESC"
)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Auctions</h4>
        <small class="text-muted">
            Manage items that have moved to auction and record final sale results.
        </small>
    </div>
    <a href="add.php" class="btn btn-teal btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Add Auction
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="auctionTable" style="width:100%;">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Ticket</th>
                    <th>Customer</th>
                    <th>Loan Amount</th>
                    <th>Starting Price</th>
                    <th>Winning Bid</th>
                    <th>Status</th>
                    <th style="width: 110px;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($auctions as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['auction_date']); ?></td>
                        <td><?php echo htmlspecialchars($a['pawn_ticket_number']); ?></td>
                        <td><?php echo htmlspecialchars($a['customer_code'] . ' - ' . $a['customer_name']); ?></td>
                        <td class="text-end"><?php echo number_format($a['loan_amount'],2); ?></td>
                        <td class="text-end"><?php echo number_format($a['starting_price'],2); ?></td>
                        <td class="text-end">
                            <?php echo $a['winning_bid'] !== null ? number_format($a['winning_bid'],2) : '-'; ?>
                        </td>
                        <td>
                            <?php
                            $badgeClass = 'secondary';
                            if ($a['status'] === 'pending')   $badgeClass = 'warning';
                            if ($a['status'] === 'sold')      $badgeClass = 'success';
                            if ($a['status'] === 'cancelled') $badgeClass = 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars(ucfirst($a['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($a['status'] === 'pending'): ?>
                                <a href="process.php?id=<?php echo (int)$a['auction_id']; ?>"
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i> Process
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#auctionTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']]
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>