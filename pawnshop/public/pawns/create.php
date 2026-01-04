<?php
// Top bar title
$pageTitle = 'Loans';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role(['admin','cashier','appraiser']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db    = (new Database())->getConnection();
$error = '';
$success = '';

// Settings for summary and logic
$loanPercentage = (float)get_setting($db, 'default_loan_percentage', 0.8);
$interestRate   = (float)get_setting($db, 'default_interest_rate', 3.0);
$termDays       = (int)get_setting($db, 'default_loan_term_days', 30);
$graceDays      = (int)get_setting($db, 'grace_period_days', 15);

function e($v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/*
 * HANDLE POST BEFORE ANY HTML
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    $customer_id      = (int)($_POST['customer_id'] ?? 0);
    $transaction_date = $_POST['transaction_date'] ?: date('Y-m-d');
    $item_category    = trim($_POST['item_category'] ?? '');
    $item_description = trim($_POST['item_description'] ?? '');
    $appraised_value  = (float)($_POST['appraised_value'] ?? 0);

    if ($customer_id <= 0 || $item_category === '' || $appraised_value <= 0) {
        $error = 'Customer ID, item category and appraised value are required.';
    }

    // Item image upload (optional)
    $item_image_path = null;
    if (empty($error) && !empty($_FILES['item_image']['name'])) {
        if ($_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime    = mime_content_type($_FILES['item_image']['tmp_name']);
            $size    = $_FILES['item_image']['size'];

            if (!isset($allowed[$mime])) {
                $error = 'Invalid item image type. Only JPG/PNG allowed.';
            } elseif ($size > 2 * 1024 * 1024) {
                $error = 'Item image exceeds 2MB.';
            } else {
                $ext      = $allowed[$mime];
                $filename = 'ITEM_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest     = UPLOAD_PATH_ITEMS . $filename;
                if (!move_uploaded_file($_FILES['item_image']['tmp_name'], $dest)) {
                    $error = 'Failed to upload item image.';
                } else {
                    $item_image_path = 'assets/uploads/items/' . $filename;
                }
            }
        } else {
            $error = 'Error uploading item image.';
        }
    }

    if (empty($error)) {
        try {
            $db->beginTransaction();

            $loan_amount   = round($appraised_value * $loanPercentage, 2);
            $maturity_date = date('Y-m-d', strtotime("+{$termDays} days", strtotime($transaction_date)));
            $grace_end     = date('Y-m-d', strtotime("+{$graceDays} days", strtotime($maturity_date)));

            // Generate ticket number PT-YYYYMMDD-XXXXX
            $datePart = date('Ymd', strtotime($transaction_date));
            $stmt     = $db->prepare("SELECT COUNT(*) FROM pawn_transactions WHERE transaction_date = :d");
            $stmt->execute([':d' => $transaction_date]);
            $seq    = (int)$stmt->fetchColumn() + 1;
            $ticket = sprintf('PT-%s-%05d', $datePart, $seq);

            // Insert transaction
            $insTxn = $db->prepare(
                "INSERT INTO pawn_transactions (
                    pawn_ticket_number, customer_id, appraiser_id,
                    transaction_date, loan_amount, interest_rate,
                    maturity_date, grace_period_end, status
                 ) VALUES (
                    :pt, :cid, :aid,
                    :tdate, :loan, :ir,
                    :md, :ge, 'active'
                 )"
            );
            $insTxn->execute([
                ':pt'    => $ticket,
                ':cid'   => $customer_id,
                ':aid'   => $_SESSION['user_id'],
                ':tdate' => $transaction_date,
                ':loan'  => $loan_amount,
                ':ir'    => $interestRate,
                ':md'    => $maturity_date,
                ':ge'    => $grace_end,
            ]);

            $transaction_id = (int)$db->lastInsertId();

            // Insert item (single item)
            $insItem = $db->prepare(
                "INSERT INTO pawned_items (
                    transaction_id, item_category, item_description,
                    appraised_value, item_image_path
                 ) VALUES (
                    :tid, :cat, :descr, :val, :img
                 )"
            );
            $insItem->execute([
                ':tid'   => $transaction_id,
                ':cat'   => $item_category,
                ':descr' => $item_description,
                ':val'   => $appraised_value,
                ':img'   => $item_image_path,
            ]);

            // Audit log
            $db->prepare(
                "INSERT INTO audit_logs
                    (user_id, action_type, table_affected, record_id, new_values, ip_address)
                 VALUES
                    (:uid, 'insert', 'pawn_transactions', :rid, :nv, :ip)"
            )->execute([
                ':uid' => $_SESSION['user_id'],
                ':rid' => $transaction_id,
                ':nv'  => json_encode(['pawn_ticket_number' => $ticket]),
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            $db->commit();
            header('Location: list.php?created=1');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error creating pawn: ' . $e->getMessage();
        }
    }
}

/*
 * AFTER LOGIC: include layout and render HTML
 */
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Labels we want white (not bold) on this page -->
<style>
.pawn-page .label-strong {
    color: #ffffff !important;
    font-weight: 400 !important; /* normal weight */
}
</style>

<div class="pawn-page">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">New Pawn Transaction</h3>
            <small class="text-muted">
                Fill in customer and item details. Loan and due dates are calculated automatically.
            </small>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Left: form -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    Customer & Transaction
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" id="pawnForm">
                        <input type="hidden" name="csrf_token" value="<?php echo e(generate_csrf_token()); ?>">
                        <input type="hidden" id="loanPct" value="<?php echo e($loanPercentage); ?>">
                        <input type="hidden" id="intRate" value="<?php echo e($interestRate); ?>">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label label-strong">Customer ID (numeric)</label>
                                <input type="number" name="customer_id" class="form-control form-control-sm"
                                       value="<?php echo isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : ''; ?>" required>
                                <small class="text-muted">Use the numeric customer_id from the Customers list.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label label-strong">Transaction Date</label>
                                <input type="date" name="transaction_date" class="form-control form-control-sm"
                                       value="<?php echo e($_POST['transaction_date'] ?? date('Y-m-d')); ?>">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-2 fw-semibold text-muted small">Item Details</div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label label-strong">Item Category</label>
                                <select name="item_category" class="form-select form-select-sm" required>
                                    <option value="">Select</option>
                                    <option value="jewelry"     <?php if(($_POST['item_category'] ?? '')==='jewelry') echo 'selected'; ?>>Jewelry</option>
                                    <option value="electronics" <?php if(($_POST['item_category'] ?? '')==='electronics') echo 'selected'; ?>>Electronics</option>
                                    <option value="appliances"  <?php if(($_POST['item_category'] ?? '')==='appliances') echo 'selected'; ?>>Appliances</option>
                                    <option value="other"       <?php if(($_POST['item_category'] ?? '')==='other') echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label label-strong">Item Description</label>
                                <input type="text" name="item_description" class="form-control form-control-sm"
                                       value="<?php echo e($_POST['item_description'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label label-strong">Appraised Value</label>
                                <input type="number" step="0.01" name="appraised_value"
                                       id="appraisedValue" class="form-control form-control-sm"
                                       value="<?php echo e($_POST['appraised_value'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label label-strong">Item Image (JPG/PNG, max 2MB)</label>
                                <input type="file" name="item_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-teal mt-2">
                            <i class="bi bi-check-circle me-1"></i> Save Pawn
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: live summary -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    Estimated Loan Summary
                </div>
                <div class="card-body">
                    <p class="mb-1 small text-muted">Appraised value:</p>
                    <div class="h5 mb-2" id="sumAppraised">₱ 0.00</div>

                    <p class="mb-1 small text-muted">Loan percentage:</p>
                    <div class="mb-2"><?php echo $loanPercentage * 100; ?> %</div>

                    <p class="mb-1 small text-muted">Estimated loan amount:</p>
                    <div class="h4 text-teal" id="sumLoan">₱ 0.00</div>

                    <hr>

                    <p class="mb-1 small text-muted">Monthly interest rate:</p>
                    <div class="mb-2"><?php echo number_format($interestRate, 2); ?> %</div>

                    <p class="mb-1 small text-muted">Loan term:</p>
                    <div class="mb-2"><?php echo $termDays; ?> days + <?php echo $graceDays; ?> days grace</div>

                    <small class="text-muted">Final values are confirmed on save based on system settings.</small>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.pawn-page -->

<script>
function formatPeso(n) {
    return '₱ ' + (parseFloat(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

$(function () {
    const $appraised = $('#appraisedValue');
    const $sumApp = $('#sumAppraised');
    const $sumLoan = $('#sumLoan');
    const loanPct = parseFloat($('#loanPct').val() || '0');

    function updateSummary() {
        const val = parseFloat($appraised.val() || '0');
        $sumApp.text(formatPeso(val));
        const loan = val * loanPct;
        $sumLoan.text(formatPeso(loan));
    }

    $appraised.on('input', updateSummary);
    updateSummary();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>