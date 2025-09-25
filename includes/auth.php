<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/constants.php';

/**
 * Busca usuario por nombre.
 */
function find_user_by_username(string $name): ?array {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id,
        nombre AS name,
        correo AS email,
        clave_hash AS password_hash,
        rol AS role,
        departamento_id AS department_id,
        ruta_firma AS signature_path
      FROM usuarios WHERE nombre = ?");
    $stmt->execute([$name]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Intenta login con nombre/password. Guarda datos de usuario en sesión sin el hash.
 */
function login_user(string $name, string $password): bool {
    secure_session_start();
    $user = find_user_by_username($name);
    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        csrf_token(); // Asegura token
        return true;
    }
    return false;
}

/**
 * Registro de usuario. Devuelve [ok, mensaje].
 */
function register_user(string $name, string $email, string $password, string $role, ?int $department_id): array {
    if (!in_array($role, [ROLE_EDITOR, ROLE_SUPERVISOR, ROLE_ADMIN], true)) {
        return [false, 'Rol inválido'];
    }
    if ($role === ROLE_EDITOR && !$department_id) {
        return [false, 'Editor requiere departamento'];
    }
    $pdo = db();
    // Unicidad email
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) return [false, 'Email ya registrado'];

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, clave_hash, rol, departamento_id, creado_en) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $hash, $role, $department_id]);
    return [true, 'Usuario creado'];
}

/**
 * Cierra sesión y elimina cookie.
 */
function logout_user(): void {
    secure_session_start();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}