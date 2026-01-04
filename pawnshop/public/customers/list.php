<?php
// Page title for top bar
$pageTitle = 'Customers';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

require_role(['admin','cashier','appraiser']);

$db = (new Database())->getConnection();
$customers = $db->query(
    "SELECT customer_id, customer_code, first_name, last_name,
            contact_number, id_number, registration_date
     FROM customers
     WHERE status = 'active'
     ORDER BY last_name, first_name"
)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">Customers</h3>
        <small class="text-muted">Manage customer profiles and IDs</small>
    </div>
    <a href="create.php" class="btn btn-teal btn-sm">
        <i class="bi bi-person-plus me-1"></i> New Customer
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Quick Search</label>
                <input id="customerSearch" type="text"
                       class="form-control form-control-sm bg-dark-2 border-0"
                       placeholder="Type name, code, contact or ID number">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="customersTable" style="width:100%;">
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>ID Number</th>
                    <th>Registered</th>
                    <th style="width: 90px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['customer_code']); ?></td>
                        <td><?php echo htmlspecialchars($c['last_name'] . ', ' . $c['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($c['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($c['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($c['registration_date']); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo (int)$c['customer_id']; ?>"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil-square"></i>
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
    const table = $('#customersTable').DataTable({
        pageLength: 10,
        order: [[1, 'asc']],
        dom: 'tip', // table + info + pagination
    });

    $('#customerSearch').on('keyup', function () {
        table.search(this.value).draw();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>