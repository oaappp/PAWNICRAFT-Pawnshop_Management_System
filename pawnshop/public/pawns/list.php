<?php
// Top bar title
$pageTitle = 'Loans';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin','cashier','appraiser']);

$db = (new Database())->getConnection();

// Get all pawn transactions with customer info
$sql = "SELECT
            pt.transaction_id,
            pt.pawn_ticket_number,
            pt.transaction_date,
            pt.loan_amount,
            pt.maturity_date,
            pt.status,
            c.customer_code,
            CONCAT(c.first_name, ' ', c.last_name) AS customer_name
        FROM pawn_transactions pt
        JOIN customers c ON pt.customer_id = c.customer_id
        ORDER BY pt.transaction_date DESC";

$loans = $db->query($sql)->fetchAll();

// Layout includes
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Loans</h3>
        <small class="text-muted">
            View all pawn transactions and open the pawn ticket for printing.
        </small>
    </div>
    <a href="create.php" class="btn btn-teal btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Loan
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Quick Search</label>
                <input id="loanSearch" type="text"
                       class="form-control form-control-sm bg-dark-2 border-0"
                       placeholder="Ticket, customer, status">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="loansTable" style="width:100%;">
                <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Customer</th>
                    <th>Loan Date</th>
                    <th>Loan Amount</th>
                    <th>Maturity</th>
                    <th>Status</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($loans as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['pawn_ticket_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_code'] . ' - ' . $row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                        <td class="text-end"><?php echo number_format($row['loan_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['maturity_date']); ?></td>
                        <td>
                            <?php
                            $status = $row['status'];
                            $badge = 'secondary';
                            if ($status === 'active' || $status === 'renewed') $badge = 'success';
                            if ($status === 'expired') $badge = 'warning';
                            if ($status === 'auctioned') $badge = 'info';
                            if ($status === 'redeemed') $badge = 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $badge; ?>">
                                <?php echo htmlspecialchars(ucfirst($status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?ticket=<?php echo urlencode($row['pawn_ticket_number']); ?>"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-file-earmark-text"></i> Ticket
                            </a>
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
    const table = $('#loansTable').DataTable({
        pageLength: 10,
        order: [[2, 'desc']], // sort by loan date
        dom: 'tip'
    });

    $('#loanSearch').on('keyup', function () {
        table.search(this.value).draw();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>