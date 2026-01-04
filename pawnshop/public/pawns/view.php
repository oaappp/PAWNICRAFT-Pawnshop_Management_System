<?php
// Page title for top bar
$pageTitle = 'Pawn Ticket';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin','cashier','appraiser']);

$db = (new Database())->getConnection();

// Get transaction by ID or by ticket number
$transaction_id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pawn_ticket_number = trim($_GET['ticket'] ?? '');

if ($transaction_id <= 0 && $pawn_ticket_number === '') {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="alert alert-danger">No pawn transaction specified.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

if ($pawn_ticket_number !== '') {
    $sql = "SELECT pt.*, c.customer_code, c.first_name, c.last_name, c.contact_number
            FROM pawn_transactions pt
            JOIN customers c ON pt.customer_id = c.customer_id
            WHERE pt.pawn_ticket_number = :t";
    $params = [':t' => $pawn_ticket_number];
} else {
    $sql = "SELECT pt.*, c.customer_code, c.first_name, c.last_name, c.contact_number
            FROM pawn_transactions pt
            JOIN customers c ON pt.customer_id = c.customer_id
            WHERE pt.transaction_id = :id";
    $params = [':id' => $transaction_id];
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transaction = $stmt->fetch();

if (!$transaction) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/sidebar.php';
    echo '<div class="alert alert-danger">Pawn transaction not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Get items under this transaction
$stmtItems = $db->prepare(
    "SELECT * FROM pawned_items WHERE transaction_id = :id"
);
$stmtItems->execute([':id' => $transaction['transaction_id']]);
$items = $stmtItems->fetchAll();

// Layout includes
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<style>
/* Center and style the ticket card on screen */
.ticket-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

/* Print mode: show ONLY the ticket, hide sidebar/topbar and other content */
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
    /* Hide everything in content except the ticket */
    .app-content > *:not(.ticket-wrapper) {
        display: none !important;
    }
    .ticket-wrapper {
        margin: 0 auto !important;
        max-width: 100% !important;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<div class="ticket-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">Pawn Ticket</h4>
        <button class="btn btn-outline-secondary btn-sm d-print-none" onclick="window.print();">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>

    <div class="card border-1">
        <div class="card-body">

            <!-- Ticket Header -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-2 mb-3">
                <div>
                    <div class="fw-semibold">Pawn Ticket: <?php echo e($transaction['pawn_ticket_number']); ?></div>
                    <small>Pawshop Management System</small>
                </div>
                <div class="text-end small">
                    <div>Transaction Date: <?php echo e($transaction['transaction_date']); ?></div>
                    <div>Status: <?php echo e($transaction['status']); ?></div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="mb-3">
                <div class="fw-semibold mb-1">Customer Information</div>
                <div class="small">
                    <div><strong>Customer Code:</strong> <?php echo e($transaction['customer_code']); ?></div>
                    <div><strong>Name:</strong> <?php echo e($transaction['first_name'] . ' ' . $transaction['last_name']); ?></div>
                    <div><strong>Contact:</strong> <?php echo e($transaction['contact_number']); ?></div>
                </div>
            </div>

            <!-- Loan Information -->
            <div class="mb-3">
                <div class="fw-semibold mb-1">Loan Information</div>
                <div class="small">
                    <div><strong>Loan Amount:</strong> <?php echo number_format($transaction['loan_amount'], 2); ?></div>
                    <div><strong>Interest Rate (monthly):</strong> <?php echo number_format($transaction['interest_rate'], 2); ?> %</div>
                    <div><strong>Maturity Date:</strong> <?php echo e($transaction['maturity_date']); ?></div>
                    <div><strong>Grace Period End:</strong> <?php echo e($transaction['grace_period_end']); ?></div>
                </div>
            </div>

            <!-- Pawned Items -->
            <div class="mb-3">
                <div class="fw-semibold mb-1">Pawned Items</div>

                <?php if (empty($items)): ?>
                    <p class="small">No items recorded for this transaction.</p>
                <?php else: ?>
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                        <tr class="small">
                            <th style="width: 40px;">#</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Brand/Model</th>
                            <th class="text-end">Appraised Value</th>
                            <th>Image</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $i => $item): ?>
                            <tr class="small">
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo e($item['item_category']); ?></td>
                                <td><?php echo e($item['item_description']); ?></td>
                                <td><?php echo e(trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''))); ?></td>
                                <td class="text-end"><?php echo number_format($item['appraised_value'], 2); ?></td>
                                <td>
                                    <?php if (!empty($item['item_image_path'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . e($item['item_image_path']); ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <p class="small mt-3 mb-0">
                By redeeming, renewing, or auctioning this ticket, the corresponding transaction status
                will update in the system.
            </p>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>