<?php
$current = $_SERVER['SCRIPT_NAME'] ?? '';
$role    = $_SESSION['role'] ?? '';

function is_active(string $needle, string $current): string {
    return str_contains($current, $needle) ? ' active' : '';
}
?>
<div class="app-shell d-flex">

    <!-- LEFT SIDEBAR -->
    <aside class="app-sidebar d-flex flex-column">
        <div class="sidebar-header d-flex align-items-center mb-3">
            <div class="brand-icon me-2">
                <span class="brand-dot"></span>
            </div>
            <div class="brand-text fw-semibold">
                Palawan State University
            </div>
        </div>

        <nav class="nav flex-column sidebar-nav flex-grow-1">
            <!-- visible to all roles -->
            <a href="<?php echo BASE_URL; ?>/dashboard.php"
               class="nav-link<?php echo is_active('/dashboard.php', $current); ?>">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>

            <a href="<?php echo BASE_URL; ?>/customers/list.php"
               class="nav-link<?php echo is_active('/customers/', $current); ?>">
                <i class="bi bi-people me-2"></i>Customers
            </a>

            <a href="<?php echo BASE_URL; ?>/pawns/list.php"
               class="nav-link<?php echo is_active('/pawns/list.php', $current); ?>">
                <i class="bi bi-cash-coin me-2"></i>Loans
            </a>

            <!-- HIDE these 4 for appraiser -->
            <?php if ($role !== 'appraiser'): ?>

                <a href="<?php echo BASE_URL; ?>/pawns/redeem.php"
                   class="nav-link<?php echo is_active('/pawns/redeem.php', $current); ?>">
                    <i class="bi bi-arrow-right-circle me-2"></i>Redemption
                </a>

                <a href="<?php echo BASE_URL; ?>/pawns/renew.php"
                   class="nav-link<?php echo is_active('/pawns/renew.php', $current); ?>">
                    <i class="bi bi-arrow-repeat me-2"></i>Renewal
                </a>

                <?php if (in_array($role, ['admin', 'cashier'], true)): ?>
                    <a href="<?php echo BASE_URL; ?>/auctions/list.php"
                       class="nav-link<?php echo is_active('/auctions/', $current); ?>">
                        <i class="bi bi-hammer me-2"></i>Auctions
                    </a>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>/reports/daily.php"
                   class="nav-link<?php echo is_active('/reports/', $current); ?>">
                    <i class="bi bi-graph-up me-2"></i>Reporting
                </a>

            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <div class="sidebar-section-label mt-3 mb-1 text-uppercase small text-muted">
                    Admin
                </div>

                <a href="<?php echo BASE_URL; ?>/users/list.php"
                   class="nav-link<?php echo is_active('/users/', $current); ?>">
                    <i class="bi bi-shield-lock me-2"></i>Users
                </a>

                <a href="<?php echo BASE_URL; ?>/backups/manual_backup.php"
                   class="nav-link<?php echo is_active('/backups/', $current); ?>">
                    <i class="bi bi-hdd-stack me-2"></i>Backups
                </a>
            <?php endif; ?>
        </nav>

        <div class="mt-auto pt-3 sidebar-footer small text-muted">
            <a href="#" class="d-block text-muted mb-1">
                <i class="bi bi-gear me-2"></i>Settings
            </a>
            <a href="#" class="d-block text-muted">
                <i class="bi bi-question-circle me-2"></i>Help
            </a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="app-main flex-grow-1 d-flex flex-column">

        <!-- TOP BAR -->
        <header class="app-topbar d-flex align-items-center justify-content-between">
            <div class="topbar-left">
                <h6 class="mb-0 text-muted">
                    <?php
                    if (!empty($pageTitle)) {
                        echo htmlspecialchars($pageTitle);
                    } else {
                        $title = basename($current);
                        $title = str_replace('.php','',$title);
                        $title = str_replace('_',' ', $title);
                        echo ucfirst($title);
                    }
                    ?>
                </h6>
            </div>

            <div class="topbar-right d-flex align-items-center gap-3">
                <button type="button"
                        class="btn btn-link btn-sm text-muted position-relative p-0">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-accent small">
                        0
                    </span>
                </button>

                <div class="d-flex align-items-center small">
                    <div class="me-2 text-end">
                        <div class="fw-semibold">
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>
                        </div>
                        <div class="text-muted">
                            <?php echo htmlspecialchars($role); ?>
                        </div>
                    </div>
                    <div class="avatar-circle">
                        <i class="bi bi-person"></i>
                    </div>
                </div>

                <a href="<?php echo BASE_URL; ?>/logout.php"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="app-content p-3 p-md-4">