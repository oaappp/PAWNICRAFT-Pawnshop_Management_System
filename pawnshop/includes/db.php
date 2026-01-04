<?php
// includes/db.php

require_once __DIR__ . '/../config/database.php';

/**
 * Global helper to get a shared PDO connection.
 * Usage: $pdo = get_db();
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $database = new Database();       // uses your Database class
        $pdo = $database->getConnection();
    }

    return $pdo;
}