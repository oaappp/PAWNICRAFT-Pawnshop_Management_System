<?php
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = (new Database())->getConnection();

// Stats
$activeLoans = $db->query("SELECT COUNT(*) FROM pawn_transactions WHERE status IN ('active','renewed')")->fetchColumn();

$maturingSoon = $db->query(
    "SELECT COUNT(*) FROM pawn_transactions
     WHERE maturity_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
       AND status IN ('active','renewed')"
)->fetchColumn();

$expiredBeyond = $db->query(
    "SELECT COUNT(*) FROM pawn_transactions
     WHERE grace_period_end < CURDATE()
       AND status IN ('active','renewed','expired')"
)->fetchColumn();

$today = date('Y-m-d');
$todayLoans = $db->prepare("SELECT IFNULL(SUM(loan_amount),0) FROM pawn_transactions WHERE transaction_date = :d");
$todayLoans->execute([':d' => $today]);
$cashOut = $todayLoans->fetchColumn();

$todayPayments = $db->prepare("SELECT IFNULL(SUM(total_amount),0) FROM payments WHERE DATE(payment_date) = :d");
$todayPayments->execute([':d' => $today]);
$cashIn = $todayPayments->fetchColumn();
?>

<div class="dashboard-page"><!-- bold text area -->

    <h3 class="mb-3">Welcome to your dashboard!</h3>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Active Loans</div>
                        <i class="bi bi-cash-coin text-muted"></i>
                    </div>
                    <div class="h3 mb-0"><?php echo (int)$activeLoans; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Loans maturing (7 days)</div>
                        <i class="bi bi-hourglass-split text-muted"></i>
                    </div>
                    <div class="h3 mb-0"><?php echo (int)$maturingSoon; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Expired beyond grace</div>
                        <i class="bi bi-exclamation-octagon text-muted"></i>
                    </div>
                    <div class="h3 mb-0 text-danger"><?php echo (int)$expiredBeyond; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Overview (placeholder chart)</span>
                    <span class="small text-muted">Last 7 days</span>
                </div>
                <div class="card-body">
                    <canvas id="overviewChart" height="90"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Quick Actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/pawns/create.php" class="btn btn-teal btn-sm">
                        <i class="bi bi-cash-coin me-1"></i> New Loan
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pawns/redeem.php" class="btn btn-dark-2 btn-sm">
                        <i class="bi bi-arrow-right-circle me-1"></i> New Redemption
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pawns/renew.php" class="btn btn-dark-2 btn-sm">
                        <i class="bi bi-arrow-repeat me-1"></i> New Renewal
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Today Cash Flow</div>
                <div class="card-body">
                    <p class="mb-1 small text-muted">Cash In</p>
                    <div class="h5 text-success mb-2">
                        <?php echo number_format($cashIn, 2); ?>
                    </div>
                    <p class="mb-1 small text-muted">Cash Out</p>
                    <div class="h5 text-danger mb-0">
                        <?php echo number_format($cashOut, 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.dashboard-page -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('overviewChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            datasets: [
                {
                    label: 'Loans',
                    data: [5,7,6,8,4,9,7],
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.1)',
                    tension: 0.3
                },
                {
                    label: 'Payments',
                    data: [3,4,5,6,3,5,6],
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56,189,248,0.1)',
                    tension: 0.3
                }
            ]
        },
        options: {
            plugins: {
                legend: {
                    labels: { color: '#e5e7eb', font: { weight: 600 } }
                }
            },
            scales: {
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#111827' } },
                y: { ticks: { color: '#9ca3af' }, grid: { color: '#111827' } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>