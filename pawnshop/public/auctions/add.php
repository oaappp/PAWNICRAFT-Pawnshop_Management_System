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

$error = '';
$info  = '';
$transaction = null;

// Handle POST: create auction
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $ticket        = trim($_POST['pawn_ticket_number'] ?? '');
    $starting_price = (float)($_POST['starting_price'] ?? 0);

    if ($ticket === '' || $starting_price <= 0) {
        $error = 'Pawn ticket number and starting price are required.';
    } else {
        // Find transaction by ticket
        $stmt = $db->prepare(
            "SELECT * FROM pawn_transactions WHERE pawn_ticket_number = :t"
        );
        $stmt->execute([':t' => $ticket]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            $error = 'Pawn ticket not found.';
        } elseif (in_array($transaction['status'], ['redeemed','sold','inventory'], true)) {
            $error = 'This transaction is already closed (status: ' . $transaction['status'] . ').';
        } else {
            // Check if already in auctions
            $check = $db->prepare("SELECT COUNT(*) FROM auctions WHERE transaction_id = :tid");
            $check->execute([':tid' => $transaction['transaction_id']]);
            if ($check->fetchColumn() > 0) {
                $error = 'This pawn is already in the Auctions list.';
            } else {
                try {
                    $db->beginTransaction();

                    // Insert auction
                    $stmtIns = $db->prepare(
                        "INSERT INTO auctions (
                            transaction_id, auction_date, starting_price, status, processed_by
                         ) VALUES (
                            :tid, CURDATE(), :sp, 'pending', :uid
                         )"
                    );
                    $stmtIns->execute([
                        ':tid' => $transaction['transaction_id'],
                        ':sp'  => $starting_price,
                        ':uid' => $_SESSION['user_id'],
                    ]);

                    // Update pawn transaction status to 'auctioned'
                    $db->prepare(
                        "UPDATE pawn_transactions
                         SET status = 'auctioned'
                         WHERE transaction_id = :tid"
                    )->execute([':tid' => $transaction['transaction_id']]);

                    $db->commit();

                    $info  = 'Auction added successfully for ticket ' . htmlspecialchars($ticket) . '.';
                    $transaction = null; // clear
                    $_POST = []; // reset form
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error adding auction: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<h4>Add Auction</h4>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($info): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

    <div class="mb-3">
        <label>Pawn Ticket Number</label>
        <input type="text" name="pawn_ticket_number" class="form-control"
               value="<?php echo htmlspecialchars($_POST['pawn_ticket_number'] ?? ''); ?>" required>
    </div>

    <div class="mb-3">
        <label>Starting Price</label>
        <input type="number" step="0.01" name="starting_price" class="form-control"
               value="<?php echo htmlspecialchars($_POST['starting_price'] ?? ''); ?>" required>
        <small class="text-muted">You may set this equal to or above the original loan amount.</small>
    </div>

    <button class="btn btn-primary">Add to Auctions</button>
    <a href="list.php" class="btn btn-secondary">Back to Auctions</a>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>