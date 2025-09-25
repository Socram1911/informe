<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_url('usuarios/perfil.php'));
    exit;
}

require_csrf();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? ROLE_EDITOR;
$department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;

if (!$name || !$email || !$password) {
    header('Location: ' . app_url('usuarios/perfil.php?error=Datos%20incompletos'));
    exit;
}


header('Location: ' . app_url('admin/usuarios.php'));
exit;