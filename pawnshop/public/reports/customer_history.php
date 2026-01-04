<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin','cashier','appraiser']);

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$q = trim($_GET['q'] ?? '');

$customer = null;
$customers = [];
$transactions = [];
$payments = [];

if ($customer_id > 0) {
    // Load selected customer
    $stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = :id");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch();

    if ($customer) {
        // Pawn transactions for this customer
        $stmtT = $db->prepare(
            "SELECT * FROM pawn_transactions
             WHERE customer_id = :id
             ORDER BY transaction_date DESC"
        );
        $stmtT->execute([':id' => $customer_id]);
        $transactions = $stmtT->fetchAll();

        // Payments for this customer's pawns
        $stmtP = $db->prepare(
            "SELECT p.*, pt.pawn_ticket_number
             FROM payments p
             JOIN pawn_transactions pt ON p.transaction_id = pt.transaction_id
             WHERE pt.customer_id = :id
             ORDER BY p.payment_date DESC"
        );
        $stmtP->execute([':id' => $customer_id]);
        $payments = $stmtP->fetchAll();
    }
} elseif ($q !== '') {
    // Search customers by name, code, id_number, contact
    $stmt = $db->prepare(
        "SELECT customer_id, customer_code, first_name, last_name, contact_number
         FROM customers
         WHERE status = 'active'
           AND (
            customer_code LIKE :q OR
            first_name LIKE :q OR
            last_name LIKE :q OR
            CONCAT(first_name,' ',last_name) LIKE :q OR
            id_number LIKE :q OR
            contact_number LIKE :q
           )
         ORDER BY last_name, first_name
         LIMIT 50"
    );
    $like = '%' . $q . '%';
    $stmt->execute([':q' => $like]);
    $customers = $stmt->fetchAll();
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<h4>Customer Transaction History</h4>

<form class="row g-3 mb-3">
    <div class="col-auto">
        <input type="text" name="q" class="form-control"
               placeholder="Search customer (name, code, ID, contact)"
               value="<?php echo htmlspecialchars($q); ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-secondary">Search</button>
    </div>
</form>

<?php if ($q !== '' && $customer_id === 0): ?>
    <h5>Search Results</h5>
    <?php if (empty($customers)): ?>
        <p>No customers found.</p>
    <?php else: ?>
        <table class="table table-sm table-bordered" id="tblCust">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['customer_code']); ?></td>
                    <td><?php echo htmlspecialchars($c['last_name'] . ', ' . $c['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['contact_number']); ?></td>
                    <td>
                        <a class="btn btn-sm btn-primary"
                           href="?customer_id=<?php echo (int)$c['customer_id']; ?>">
                            View History
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>$(function(){ $('#tblCust').DataTable(); });</script>
    <?php endif; ?>
<?php endif; ?>

<?php if ($customer): ?>
    <hr>
    <h5>Customer: <?php echo htmlspecialchars($customer['customer_code'] . ' - ' .
                                            $customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
    <p>Contact: <?php echo htmlspecialchars($customer['contact_number']); ?></p>

    <h6 class="mt-3">Pawn Transactions</h6>
    <?php if (empty($transactions)): ?>
        <p>No pawn transactions.</p>
    <?php else: ?>
        <table class="table table-sm table-bordered" id="tblTrans">
            <thead>
            <tr>
                <th>Date</th>
                <th>Ticket</th>
                <th>Loan Amount</th>
                <th>Interest %</th>
                <th>Maturity</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['transaction_date']); ?></td>
                    <td><?php echo htmlspecialchars($t['pawn_ticket_number']); ?></td>
                    <td class="text-end"><?php echo number_format($t['loan_amount'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($t['interest_rate'], 2); ?></td>
                    <td><?php echo htmlspecialchars($t['maturity_date']); ?></td>
                    <td><?php echo htmlspecialchars($t['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h6 class="mt-3">Payments (Redemptions / Renewals / Partial)</h6>
    <?php if (empty($payments)): ?>
        <p>No payments recorded.</p>
    <?php else: ?>
        <table class="table table-sm table-bordered" id="tblPaysHist">
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Ticket</th>
                <th>Receipt</th>
                <th>Total Amount</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                    <td><?php echo htmlspecialchars($p['pawn_ticket_number']); ?></td>
                    <td><?php echo htmlspecialchars($p['receipt_number']); ?></td>
                    <td class="text-end"><?php echo number_format($p['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
    $(function () {
        $('#tblTrans').DataTable();
        $('#tblPaysHist').DataTable();
    });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>