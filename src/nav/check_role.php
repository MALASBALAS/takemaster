<?php
require_once __DIR__ . '/bootstrap.php';
start_secure_session();

function checkRole(int $required_role_id): void {
    if (!isset($_SESSION['role_id']) || (int)$_SESSION['role_id'] !== $required_role_id) {
        http_response_code(403);
        die('Acceso denegado');
    }
}