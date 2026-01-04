<?php
$pageTitle = 'Redemption';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/calc_helper.php';

require_role(['admin','cashier']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

$transaction = null;
$charges     = null;
$error       = '';
$info        = '';

// ---------- SEARCH BY TICKET (GET) ----------
if (isset($_GET['ticket'])) {
    $ticket = trim($_GET['ticket']);
    if ($ticket !== '') {
        $stmt = $db->prepare(
            "SELECT pt.*, c.customer_code, c.first_name, c.last_name, c.contact_number
             FROM pawn_transactions pt
             JOIN customers c ON pt.customer_id = c.customer_id
             WHERE pt.pawn_ticket_number = :t"
        );
        $stmt->execute([':t' => $ticket]);
        $transaction = $stmt->fetch();

        if ($transaction) {
            if (!in_array($transaction['status'], ['active','renewed','expired'], true)) {
                $error = 'This ticket cannot be redeemed (status: ' . $transaction['status'] . ').';
                $transaction = null;
            } else {
                $penalty_rate = (float)get_setting($db, 'penalty_rate', 5.0);
                $charges = calculate_charges(
                    (float)$transaction['loan_amount'],
                    (float)$transaction['interest_rate'],
                    $penalty_rate,
                    $transaction['transaction_date'],
                    $transaction['grace_period_end'],
                    date('Y-m-d')
                );
            }
        } else {
            $error = 'Ticket not found.';
        }
    }
}

// ---------- PROCESS REDEMPTION (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transaction_id'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $transaction_id = (int)$_POST['transaction_id'];
    $payment_method = $_POST['payment_method'] ?? 'cash';

    $stmt = $db->prepare(
        "SELECT pt.*, c.customer_code, c.first_name, c.last_name, c.contact_number
         FROM pawn_transactions pt
         JOIN customers c ON pt.customer_id = c.customer_id
         WHERE pt.transaction_id = :id"
    );
    $stmt->execute([':id' => $transaction_id]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        $error = 'Transaction not found.';
    } else {
        $penalty_rate = (float)get_setting($db, 'penalty_rate', 5.0);
        $charges = calculate_charges(
            (float)$transaction['loan_amount'],
            (float)$transaction['interest_rate'],
            $penalty_rate,
            $transaction['transaction_date'],
            $transaction['grace_period_end'],
            date('Y-m-d')
        );

        try {
            $db->beginTransaction();
            $receipt_number = 'R-' . date('YmdHis') . '-' . random_int(1000, 9999);

            $stmtPay = $db->prepare(
                "INSERT INTO payments (
                    transaction_id, payment_type, payment_date,
                    principal_amount, interest_amount, penalty_amount,
                    total_amount, payment_method, processed_by, receipt_number
                 ) VALUES (
                    :tid, 'redemption', NOW(),
                    :principal, :interest, :penalty,
                    :total, :method, :uid, :rcpt
                 )"
            );
            $stmtPay->execute([
                ':tid'       => $transaction_id,
                ':principal' => $charges['principal'],
                ':interest'  => $charges['interest_total'],
                ':penalty'   => $charges['penalty'],
                ':total'     => $charges['total_due'],
                ':method'    => $payment_method,
                ':uid'       => $_SESSION['user_id'],
                ':rcpt'      => $receipt_number,
            ]);

            $payment_id = (int)$db->lastInsertId();

            $db->prepare(
                "UPDATE pawn_transactions
                 SET status = 'redeemed'
                 WHERE transaction_id = :id"
            )->execute([':id' => $transaction_id]);

            $db->prepare(
                "INSERT INTO audit_logs
                   (user_id, action_type, table_affected, record_id, new_values, ip_address)
                 VALUES
                   (:uid, 'update', 'pawn_transactions', :rid, :new_val, :ip)"
            )->execute([
                ':uid'     => $_SESSION['user_id'],
                ':rid'     => $transaction_id,
                ':new_val' => json_encode(['status' => 'redeemed']),
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $db->commit();

            header('Location: receipt.php?id=' . $payment_id);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error processing redemption: ' . $e->getMessage();
        }
    }
}

// Layout
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0 text-white">Redemption</h3>
        <small class="text-muted">
            Look up a pawn ticket, review the amount due, and confirm payment to release the item.
        </small>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- FULL-WIDTH SEARCH CARD -->
<div class="card mb-4">
    <div class="card-header">
        Find Pawn Ticket
    </div>
    <div class="card-body">
        <form method="get" class="mb-2">
            <div class="mb-2">
                <label class="form-label">Pawn Ticket Number</label>
                <input type="text" name="ticket" class="form-control form-control-sm"
                       placeholder="e.g. PT-20251130-00001"
                       value="<?php echo isset($_GET['ticket']) ? htmlspecialchars($_GET['ticket']) : ''; ?>" required>
            </div>
            <button class="btn btn-teal btn-sm w-100">
                <i class="bi bi-search me-1"></i> Search
            </button>
        </form>
        <small class="text-muted">
            Enter the ticket number printed on the pawn ticket.
        </small>
    </div>
</div>

<!-- RESULT SECTION -->
<?php if ($transaction && $charges && !$error): ?>
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">

            <div class="card mb-3">
                <div class="card-header">
                    Ticket & Customer Details
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <p class="mb-1 small text-muted">Pawn Ticket</p>
                            <div class="fw-semibold">
                                <?php echo htmlspecialchars($transaction['pawn_ticket_number']); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1 small text-muted">Customer</p>
                            <div>
                                <?php
                                echo htmlspecialchars($transaction['customer_code']) . ' - ' .
                                     htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']);
                                ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($transaction['contact_number']); ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Loan Amount</p>
                            <div class="fw-semibold">
                                <?php echo number_format($transaction['loan_amount'],2); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Interest (computed)</p>
                            <div>
                                <?php echo number_format($charges['interest_total'],2); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Penalty</p>
                            <div>
                                <?php echo number_format($charges['penalty'],2); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Total Due</p>
                            <div class="h5 text-danger">
                                <?php echo number_format($charges['total_due'],2); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Maturity Date</p>
                            <div>
                                <?php echo htmlspecialchars($transaction['maturity_date']); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Grace Period End</p>
                            <div>
                                <?php echo htmlspecialchars($transaction['grace_period_end']); ?>
                            </div>
                        </div>
                    </div>

                    <small class="text-muted">
                        Interest and penalties are automatically calculated based on the transaction date,
                        grace period, and your system's configured rates.
                    </small>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Confirm Payment
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                        <input type="hidden" name="transaction_id" value="<?php echo (int)$transaction['transaction_id']; ?>">

                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <p class="mb-1 small text-muted">Total to collect</p>
                            <div class="h5 text-danger mb-0">
                                <?php echo number_format($charges['total_due'],2); ?>
                            </div>
                        </div>

                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-teal">
                                <i class="bi bi-check-circle me-1"></i> Confirm Redemption
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
<?php else: ?>
    <p class="text-muted text-center">
        Search for a pawn ticket to see redemption details.
    </p>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>