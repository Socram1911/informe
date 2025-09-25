<?php
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'historial') {
    require_login();
    $section_id = (int)($_GET['section_id'] ?? 0);
    $pdo = db();
    $stmt = $pdo->prepare("SELECT accion AS action, comentario AS comment, creado_en AS created_at FROM historial_seccion WHERE seccion_id = ? ORDER BY creado_en DESC");
    $stmt->execute([$section_id]);
    json_response(['ok' => true, 'items' => $stmt->fetchAll()]);
}

if ($method === 'POST' && in_array($action, ['guardar', 'completar', 'solicitar_revision'], true)) {
    require_login();
    require_csrf();

    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $section_id = (int)($data['section_id'] ?? 0);
    $content = sanitize_html_basic($data['content'] ?? '');
    $pdo = db();
    $u = current_user();

    $stmt = $pdo->prepare("SELECT id, informe_id AS report_id, contenido AS content, estado AS status, asignado_a AS assigned_to FROM secciones_informe WHERE id = ?");
    $stmt->execute([$section_id]);
    $sec = $stmt->fetch();
    if (!$sec) json_response(['ok' => false, 'error' => 'Sección no encontrada'], 404);

    // Control de acceso
    if ($u['role'] === ROLE_EDITOR && (int)$sec['assigned_to'] !== (int)$u['id']) {
        json_response(['ok' => false, 'error' => 'No autorizado'], 403);
    }

    // Determinar nuevo estado
    $newStatus = $sec['status'];
    if ($action === 'guardar') $newStatus = SECCION_BORRADOR;
    if ($action === 'completar') $newStatus = SECCION_COMPLETADO;
    if ($action === 'solicitar_revision') $newStatus = SECCION_EN_REVISION;

    $pdo->beginTransaction();
    try {
        // Actualizar sección
        $pdo->prepare("UPDATE secciones_informe SET contenido = ?, estado = ?, actualizado_en = NOW() WHERE id = ?")
            ->execute([$content, $newStatus, $section_id]);

        // Registrar historial
        $pdo->prepare("INSERT INTO historial_seccion (seccion_id, usuario_id, accion, contenido_copia, comentario, creado_en) VALUES (?, ?, ?, ?, ?, NOW())")
            ->execute([$section_id, $u['id'], $action, $content, '']);

        // Actualizar estado del informe cuando corresponde
        if (in_array($newStatus, [SECCION_COMPLETADO, SECCION_EN_REVISION], true)) {
            $pdo->prepare("UPDATE informes SET estado = ?, actualizado_en = NOW() WHERE id = ?")
                ->execute([INFORME_EN_PROGRESO, $sec['report_id']]);
        }

        // Notificar a supervisores cuando cambie a revisión/completado
        if (in_array($newStatus, [SECCION_EN_REVISION, SECCION_COMPLETADO], true)) {
            $supervisores = $pdo->query("SELECT id FROM usuarios WHERE rol = 'supervisor'")->fetchAll();
            foreach ($supervisores as $sup) {
                notify_user((int)$sup['id'], 'Sección lista para revisión', 'La sección #' . (int)$section_id . ' requiere revisión.');
            }
        }

        $pdo->commit();
        json_response(['ok' => true, 'message' => 'Guardado', 'status' => $newStatus]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => $e->getMessage()], 400);
    }
}

json_response(['ok' => false, 'error' => 'No encontrado'], 404);