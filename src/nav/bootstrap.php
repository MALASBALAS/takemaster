<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Strict error handling in production
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
error_reporting(E_ALL);

// Security headers (can be adjusted per page)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
header('X-XSS-Protection: 1; mode=block');

// Session bootstrap
if (!function_exists('start_secure_session')) {
    function start_secure_session(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $params = session_get_cookie_params();
        $secure = (stripos(BASE_URL, 'https://') === 0);
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax', // Lax for login forms; switch to Strict if needed
        ]);
        session_start();
    }
}

if (!function_exists('regen_session')) {
    function regen_session(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

// CSRF utilities
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        start_secure_session();
        if (empty($_SESSION[CSRF_TOKEN_KEY])) {
            $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_KEY];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('validate_csrf')) {
    function validate_csrf(): bool {
        start_secure_session();
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_KEY] ?? '', $token);
    }
}
