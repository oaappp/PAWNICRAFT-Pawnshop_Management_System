<?php
$date = date('Ymd_His');
$backupFile = __DIR__ . "/../backups/backup_{$date}.sql";

$cmd = "mysqldump -u root pawnshop_db > " . escapeshellarg($backupFile);
exec($cmd, $out, $status);

$log = __DIR__ . '/../backups/backup_log.txt';
file_put_contents($log, date('Y-m-d H:i:s') . " - Status: $status - File: $backupFile\n", FILE_APPEND);