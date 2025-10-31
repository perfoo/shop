<?php
declare(strict_types=1);

/**
 * Authentication helper functions for the admin area.
 */

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

/**
 * Ensure a secure session is started.
 */
function ensure_session(array $config): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (PHP_SAPI === 'cli') {
        $secure = false;
    }

    session_name($config['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

/**
 * Enforce HTTPS access for the admin area.
 */
function require_https(): void
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if (!$isHttps) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'HTTPS je obavezan za ovu stranicu.';
        exit;
    }
}

/**
 * Check if the current session is authenticated.
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Redirect to the login page when access is denied.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /control-room/login.php');
        exit;
    }
}

/**
 * Attempt to authenticate with the provided password.
 */
function attempt_login(string $password, array $config): bool
{
    if (password_verify($password, $config['admin_password_hash'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        return true;
    }

    return false;
}

/**
 * Destroy the current session and log the admin out.
 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
