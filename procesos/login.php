<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('index.php'));
    exit;
}

require_csrf();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header('Location: ' . app_url('index.php?error=Credenciales%20inv%C3%A1lidas'));
    exit;
}

if (login_user($username, $password)) {
    header('Location: ' . app_url('usuarios/dashboard.php'));
    exit;
}

header('Location: ' . app_url('index.php?error=Usuario%20o%20contrase%C3%B1a%20incorrectos'));