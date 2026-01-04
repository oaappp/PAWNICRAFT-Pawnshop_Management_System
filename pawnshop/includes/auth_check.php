<?php
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function require_role(array $roles): void {
    if (empty($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied');
    }
}