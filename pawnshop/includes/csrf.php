<?php
// CSRF helper functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate (or return existing) CSRF token stored in session.
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token from a form against the session token.
 */
function validate_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}