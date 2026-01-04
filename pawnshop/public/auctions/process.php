<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

require_role(['admin','cashier']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

$auction_id = (int)($_GET['id'] ?? 0);
$error = '';
$info  = '';

// Load auction + related transaction once
$stmt = $db->prepare(
    "SELECT a.*, pt.loan_amount, pt.pawn_ticket_number, pt.transaction_id
     FROM auctions a
     JOIN pawn_transactions pt ON a.transaction_id = pt.transaction_id
     WHERE a.auction_id = :id"
);
$stmt->execute([':id' => $auction_id]);
$auction = $stmt->fetch();

if (!$auction) {
    echo '<div class="alert alert-danger">Auction not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $winning_bid    = (float)($_POST['winning_bid'] ?? 0);
    $bidder_name    = trim($_POST['bidder_name'] ?? '');
    $bidder_contact = trim($_POST['bidder_contact'] ?? '');
    $status         = $_POST['status'] ?? 'pending'; // 'sold' or 'cancelled'

    if ($status === 'sold' && $winning_bid <= 0) {
        $error = 'Winning bid must be greater than zero for sold items.';
    }

    if (empty($error)) {
        try {
            $db->beginTransaction();

            // 1) Update auctions table
            $db->prepare(
                "UPDATE auctions
                 SET winning_bid = :bid,
                     bidder_name = :name,
                     bidder_contact = :contact,
                     status = :status
                 WHERE auction_id = :id"
            )->execute([
                ':bid'     => $winning_bid ?: null,
                ':name'    => $bidder_name ?: null,
                ':contact' => $bidder_contact ?: null,
                ':status'  => $status,
                ':id'      => $auction_id,
            ]);

            // 2) Update pawn_transactions status based on auction outcome
            if ($status === 'sold') {
                // Item sold at auction
                $new_txn_status = 'sold';
            } elseif ($status === 'cancelled') {
                // Auction cancelled / item returned to inventory
                $new_txn_status = 'inventory';
            } else {
                // Pending or other: keep as 'auctioned'
                $new_txn_status = 'auctioned';
            }

            $db->prepare(
                "UPDATE pawn_transactions
                 SET status = :st
                 WHERE transaction_id = :tid"
            )->execute([
                ':st'  => $new_txn_status,
                ':tid' => $auction['transaction_id'],
            ]);

            // 3) Audit log
            $db->prepare(
                "INSERT INTO audit_logs
                   (user_id, action_type, table_affected, record_id, new_values, ip_address)
                 VALUES
                   (:uid, 'update', 'auctions', :rid, :newv, :ip)"
            )->execute([
                ':uid'  => $_SESSION['user_id'],
                ':rid'  => $auction_id,
                ':newv' => json_encode([
                    'auction_status'   => $status,
                    'transaction_status' => $new_txn_status,
                    'winning_bid'      => $winning_bid,
                ]),
                ':ip'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $db->commit();
            $info = 'Auction processed successfully. Transaction status set to ' . $new_txn_status . '.';

            // Reload updated auction for display
            $stmt = $db->prepare(
                "SELECT a.*, pt.loan_amount, pt.pawn_ticket_number, pt.transaction_id
                 FROM auctions a
                 JOIN pawn_transactions pt ON a.transaction_id = pt.transaction_id
                 WHERE a.auction_id = :id"
            );
            $stmt->execute([':id' => $auction_id]);
            $auction = $stmt->fetch();
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error processing auction: ' . $e->getMessage();
        }
    }
}
?>

<h4>Process Auction</h4>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($info): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
<?php endif; ?>

<p><strong>Pawn Ticket:</strong> <?php echo htmlspecialchars($auction['pawn_ticket_number']); ?></p>
<p><strong>Loan Amount:</strong> <?php echo number_format($auction['loan_amount'], 2); ?></p>
<p><strong>Starting Price:</strong> <?php echo number_format($auction['starting_price'], 2); ?></p>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

    <div class="mb-3">
        <label>Winning Bid (sale amount)</label>
        <input type="number" step="0.01" name="winning_bid" class="form-control"
               value="<?php echo htmlspecialchars($auction['winning_bid'] ?? ''); ?>">
        <small class="text-muted">Required if status is Sold.</small>
    </div>

    <div class="mb-3">
        <label>Bidder Name</label>
        <input type="text" name="bidder_name" class="form-control"
               value="<?php echo htmlspecialchars($auction['bidder_name'] ?? ''); ?>">
    </div>

    <div class="mb-3">
        <label>Bidder Contact</label>
        <input type="text" name="bidder_contact" class="form-control"
               value="<?php echo htmlspecialchars($auction['bidder_contact'] ?? ''); ?>">
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select">
            <option value="pending"   <?php if($auction['status']==='pending')   echo 'selected'; ?>>Pending</option>
            <option value="sold"      <?php if($auction['status']==='sold')      echo 'selected'; ?>>Sold</option>
            <option value="cancelled" <?php if($auction['status']==='cancelled') echo 'selected'; ?>>Returned to Inventory (Cancelled)</option>
        </select>
    </div>

    <button class="btn btn-primary">Save</button>
    <a href="list.php" class="btn btn-secondary">Back to Auctions</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>