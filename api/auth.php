<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'login') {
    require_csrf();
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $ok = login_user(trim($data['email'] ?? ''), $data['password'] ?? '');
    if ($ok) json_response(['ok' => true]);
    json_response(['ok' => false, 'error' => 'Credenciales inválidas'], 401);
}

if ($method === 'POST' && $action === 'register') {
    require_login();
    require_role(ROLE_ADMIN);
    require_csrf();
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    [$ok, $msg] = register_user(
        trim($data['name'] ?? ''),
        trim($data['email'] ?? ''),
        $data['password'] ?? '',
        $data['role'] ?? ROLE_EDITOR,
        isset($data['department_id']) && $data['department_id'] !== '' ? (int)$data['department_id'] : null
    );
    if ($ok) json_response(['ok' => true, 'message' => $msg], 201);
    json_response(['ok' => false, 'error' => $msg], 400);
}

if ($method === 'POST' && $action === 'actualizar_perfil') {
    require_login();
    require_csrf();
    $u = current_user();
    $name = trim($_POST['name'] ?? ($u['name'] ?? ''));
    $sigPath = $u['signature_path'] ?? null;
    if (!empty($_FILES['signature']['name'])) {
        $ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
        $allowed = ['png','jpg','jpeg','gif'];
        if (in_array(strtolower($ext), $allowed, true)) {
            $dir = __DIR__ . '/../assets/img/firmas';
            @mkdir($dir, 0775, true);
            $filename = 'firma_' . $u['id'] . '_' . time() . '.' . $ext;
            $dest = $dir . '/' . $filename;
            if (move_uploaded_file($_FILES['signature']['tmp_name'], $dest)) {
                $sigPath = 'assets/img/firmas/' . $filename;
            }
        }
    }
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, ruta_firma = ? WHERE id = ?");
    $stmt->execute([$name, $sigPath, $u['id']]);
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['signature_path'] = $sigPath;
    header('Location: ' . app_url('usuarios/perfil.php'));
    exit;
}

json_response(['ok' => false, 'error' => 'No encontrado'], 404);