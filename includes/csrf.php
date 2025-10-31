<?php
declare(strict_types=1);

/**
 * CSRF protection helpers.
 */
function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf_token(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
