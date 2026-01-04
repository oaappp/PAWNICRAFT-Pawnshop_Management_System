<?php
// (optional during development)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';

require_role(['admin']);

$info            = '';
$latestBackup    = null;

// Folder where SQL backups are stored (NOT inside public/)
$backupDir = dirname(__DIR__, 2) . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date       = date('Ymd_His');
    $backupFile = $backupDir . '/backup_' . $date . '.sql';

    // DB credentials
    $dbHost = '127.0.0.1';
    $dbUser = 'root';
    $dbPass = '';            // put password if you have one
    $dbName = 'pawnshop_db';

    // path to mysqldump
    $mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';

    $cmd = sprintf(
        '"%s" --user=%s --password=%s --host=%s %s > %s 2>&1',
        $mysqldump,
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbHost),
        escapeshellarg($dbName),
        escapeshellarg($backupFile)
    );

    $output = [];
    $status = 0;
    exec($cmd, $output, $status);

    if ($status === 0) {
        $latestBackup = basename($backupFile);
        $info = 'Backup created: ' . $latestBackup;
    } else {
        $info = 'Backup failed. Check server configuration.';
        error_log("DB BACKUP FAILED\nCMD: $cmd\nSTATUS: $status\nOUTPUT:\n" . implode("\n", $output));
    }
}

// ----- Load list of existing backups -----
$files = glob($backupDir . '/backup_*.sql');
usort($files, static function ($a, $b) {
    return filemtime($b) <=> filemtime($a); // newest first
});
?>

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Manual Backup</h3>
                </div>
            </div>
        </div>

        <?php if ($info): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($latestBackup): ?>
                    &nbsp;
                    <a class="btn btn-sm btn-success"
                       href="download_backup.php?file=<?= urlencode($latestBackup) ?>">
                        Download this backup
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <p>This will create a database backup and store it on the server.</p>
                <form method="post">
                    <button class="btn btn-primary" type="submit">Run Backup Now</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Existing Backups</h5>
            </div>
            <div class="card-body">
                <?php if (empty($files)): ?>
                    <p>No backups found yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>File name</th>
                                <th>Created</th>
                                <th>Size</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($files as $file): ?>
                                <?php
                                $name = basename($file);
                                $time = date('Y-m-d H:i:s', filemtime($file));
                                $size = round(filesize($file) / 1024, 1) . ' KB';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($name) ?></td>
                                    <td><?= $time ?></td>
                                    <td><?= $size ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="download_backup.php?file=<?= urlencode($name) ?>">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>