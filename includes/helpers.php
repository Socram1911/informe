<?php
// Autoload de Composer para PHPMailer / PHPWord.
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/constants.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Genera URL absoluta a partir de ruta relativa de la aplicación.
 */
function app_url(string $path = ''): string {
    $cfg = db_config();
    $base = rtrim($cfg['APP_URL'], '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

/**
 * Envía una respuesta JSON y detiene la ejecución.
 */
function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Enviar correo HTML (notificaciones).
 * Devuelve true/false; en caso de error registra en error_log.
 */
function send_mail(string $to, string $subject, string $html, ?string $toName = null): bool {
    $cfg = db_config();
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $cfg['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['MAIL_USER'];
        $mail->Password = $cfg['MAIL_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int)$cfg['MAIL_PORT'];

        $mail->setFrom($cfg['MAIL_FROM'], $cfg['MAIL_FROM_NAME']);
        $mail->addAddress($to, $toName ?? $to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;

        return $mail->send();
    } catch (MailException $e) {
        error_log('Mailer Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Calcula parámetros de paginación: limit, offset, page.
 */
function paginate_params(): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $size = max(1, min(100, (int)($_GET['size'] ?? DEFAULT_PAGE_SIZE)));
    $offset = ($page - 1) * $size;
    return [$size, $offset, $page];
}

/**
 * Usuario autenticado actual en sesión.
 */
function current_user(): ?array {
    secure_session_start();
    return $_SESSION['user'] ?? null;
}

/**
 * Requiere sesión iniciada; redirige a index si no autenticado.
 */
function require_login(): void {
    if (!current_user()) {
        header('Location: ' . app_url('index.php'));
        exit;
    }
}

/**
 * Requiere uno de los roles; si no, 403.
 */
function require_role(string ...$roles): void {
    $u = current_user();
    if (!$u || (!in_array($u['role'], $roles, true))) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
}

/**
 * Comprueba si el usuario puede acceder a datos de un departamento.
 */
function user_can_access_department(int $department_id): bool {
    $u = current_user();
    if (!$u) return false;
    if ($u['role'] === ROLE_ADMIN || $u['role'] === ROLE_SUPERVISOR) return true;
    return (int)($u['department_id'] ?? 0) === $department_id;
}

/**
 * Envía notificación a un usuario por email (si existe).
 */
function notify_user(int $user_id, string $subject, string $message): void {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT correo AS email, nombre AS name FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($row = $stmt->fetch()) {
        send_mail($row['email'], $subject, $message, $row['name']);
    }
}