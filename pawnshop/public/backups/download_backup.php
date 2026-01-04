<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_check.php';

require_role(['admin']);

$backupDir = dirname(__DIR__, 2) . '/backups';

if (empty($_GET['file'])) {
    http_response_code(400);
    echo 'Missing file parameter.';
    exit;
}

$filename = basename($_GET['file']);  // prevent directory traversal
$path     = $backupDir . DIRECTORY_SEPARATOR . $filename;

if (!is_file($path)) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');

readfile($path);
exit;