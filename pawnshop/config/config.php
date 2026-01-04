<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL for links (this matches http://localhost/pawnshop/public/)
define('BASE_URL', '/pawnshop/public');

// Upload paths (filesystem paths)
define('UPLOAD_PATH_IDS',   __DIR__ . '/../public/assets/uploads/ids/');
define('UPLOAD_PATH_ITEMS', __DIR__ . '/../public/assets/uploads/items/');

// Session timeout (seconds)
define('SESSION_TIMEOUT', 1800);

// Enforce session timeout
if (isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

/**
 * Helper to get a setting from the system_settings table.
 */
function get_setting(PDO $db, string $key, $default = null) {
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :k");
    $stmt->execute([':k' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}