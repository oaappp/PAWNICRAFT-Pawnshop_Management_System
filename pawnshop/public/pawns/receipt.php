<?php
$pageTitle = 'Receipt';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin','cashier']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

// Get by payment_id (id) or receipt_number (receipt)
$payment_id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$receipt_number = trim($_GET['receipt'] ?? '');

if ($payment_id <= 0 && $receipt_number === '') {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="alert alert-danger">No receipt specified.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

if ($payment_id > 0) {
    $sql = "SELECT p.*, pt.pawn_ticket_number, pt.transaction_date AS loan_date, pt.loan_amount,
                   c.customer_code, c.first_name, c.last_name
            FROM payments p
            JOIN pawn_transactions pt ON p.transaction_id = pt.transaction_id
            JOIN customers c ON pt.customer_id = c.customer_id
            WHERE p.payment_id = :id";
    $params = [':id' => $payment_id];
} else {
    $sql = "SELECT p.*, pt.pawn_ticket_number, pt.transaction_date AS loan_date, pt.loan_amount,
                   c.customer_code, c.first_name, c.last_name
            FROM payments p
            JOIN pawn_transactions pt ON p.transaction_id = pt.transaction_id
            JOIN customers c ON pt.customer_id = c.customer_id
            WHERE p.receipt_number = :r";
    $params = [':r' => $receipt_number];
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payment = $stmt->fetch();

if (!$payment) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="alert alert-danger">Receipt not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$typeLabel = $payment['payment_type'] === 'redemption'
    ? 'Redemption Receipt'
    : ($payment['payment_type'] === 'renewal' ? 'Renewal Receipt' : 'Payment Receipt');

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<style>
/* Center and style the receipt card */
.receipt-wrapper {
    max-width: 900px;
    margin: 0 auto;
}

/* Dark theme for the payment details table */
.receipt-wrapper table.payment-table,
.receipt-wrapper table.payment-table th,
.receipt-wrapper table.payment-table td {
    background-color: #020617 !important;
    color: #f9fafb !important;
    border-color: #111827 !important;
}

/* Print view: focus on the receipt only */
@media print {
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    .app-sidebar,
    .app-topbar {
        display: none !important;
    }
    .app-main,
    .app-content {
        padding: 0 !important;
        margin: 0 !important;
    }
    .app-content > *:not(.receipt-wrapper) {
        display: none !important;
    }
    .receipt-wrapper {
        margin: 0 auto !important;
        max-width: 100% !important;
    }
    .btn,
    .d-print-none {
        display: none !important;
    }
}
</style>

<div class="receipt-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h4 class="mb-0 text-white">Receipt</h4>
        </div>
        <div class="d-print-none">
            <!-- Could add breadcrumbs or nothing here -->
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <h5 class="mb-0"><?php echo e($typeLabel); ?></h5>
                <small>Pawnhop Management System</small>
            </div>
            <div class="text-end small">
                <div>Receipt No: <?php echo e($payment['receipt_number']); ?></div>
                <div>Date: <?php echo e($payment['payment_date']); ?></div>
            </div>
        </div>
        <div class="card-body">

            <div class="mb-3">
                <div class="fw-semibold mb-1">Customer Information</div>
                <div class="small">
                    <div><strong>Customer Code:</strong> <?php echo e($payment['customer_code']); ?></div>
                    <div><strong>Name:</strong> <?php echo e($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                </div>
            </div>

            <div class="mb-3">
                <div class="fw-semibold mb-1">Pawn Ticket</div>
                <div class="small">
                    <div><strong>Ticket No:</strong> <?php echo e($payment['pawn_ticket_number']); ?></div>
                    <div><strong>Loan Date:</strong> <?php echo e($payment['loan_date']); ?></div>
                    <div><strong>Original Loan Amount:</strong> <?php echo number_format($payment['loan_amount'], 2); ?></div>
                </div>
            </div>

            <div class="mb-3">
                <div class="fw-semibold mb-1">Payment Details</div>

                <table class="table table-sm payment-table" style="max-width: 400px;">
                    <tbody class="small">
                    <tr>
                        <th style="width: 50%;">Principal</th>
                        <td class="text-end"><?php echo number_format($payment['principal_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Interest</th>
                        <td class="text-end"><?php echo number_format($payment['interest_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Penalty</th>
                        <td class="text-end"><?php echo number_format($payment['penalty_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Total Paid</th>
                        <td class="text-end fw-semibold"><?php echo number_format($payment['total_amount'], 2); ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="mb-2 small">
                <strong>Payment Method:</strong> <?php echo e($payment['payment_method']); ?>
            </div>

            <div class="mb-3 small">
                <strong>Processed By (User ID):</strong> <?php echo (int)$payment['processed_by']; ?>
            </div>

            <p class="small fst-italic mb-0">
                This is a system-generated receipt.
            </p>

        </div>
        <div class="card-footer d-print-none">
            <button class="btn btn-primary btn-sm" onclick="window.print();">Print</button>
            <a href="javascript:history.back();" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>