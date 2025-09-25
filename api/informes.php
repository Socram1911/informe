<?php
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'list' && $method === 'GET') {
    require_login();
    [$limit, $offset] = paginate_params();
    $pdo = db();
    $u = current_user();
    if (in_array($u['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)) {
        $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id, titulo AS title, periodo AS period, estado AS status, actualizado_en AS updated_at FROM informes ORDER BY actualizado_en DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // Informes donde el usuario tiene secciones asignadas
        $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS DISTINCT r.id, r.titulo AS title, r.periodo AS period, r.estado AS status, r.actualizado_en AS updated_at
            FROM informes r 
            INNER JOIN secciones_informe rs ON rs.informe_id = r.id
            WHERE rs.asignado_a = ?
            ORDER BY r.actualizado_en DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $u['id'], PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $items = $stmt->fetchAll();
    $total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
    json_response(['ok' => true, 'items' => $items, 'total' => $total]);
}

if ($action === 'preview' && $method === 'GET') {
    require_role(ROLE_SUPERVISOR, ROLE_ADMIN);
    $report_id = (int)($_GET['report_id'] ?? 0);
    $pdo = db();
    $r = $pdo->prepare("SELECT id, titulo AS title, periodo AS period FROM informes WHERE id = ?");
    $r->execute([$report_id]);
    $report = $r->fetch();
    if (!$report) {
        http_response_code(404);
        echo 'Informe no encontrado';
        exit;
    }
    $s = $pdo->prepare("SELECT rs.id, rs.titulo AS title, rs.contenido AS content, d.nombre AS dept
        FROM secciones_informe rs LEFT JOIN departamentos d ON d.id = rs.departamento_id
        WHERE rs.informe_id = ? ORDER BY rs.orden ASC");
    $s->execute([$report_id]);
    $sections = $s->fetchAll();
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>" . e($report['title']) . " (" . e($report['period']) . ")</h1>";
    echo "<ol>";
    foreach ($sections as $sec) {
        echo "<li><strong>" . e($sec['title']) . "</strong> - " . e($sec['dept'] ?? '') . "</li>";
    }
    echo "</ol><hr>";
    foreach ($sections as $sec) {
        echo "<h3>" . e($sec['title']) . "</h3>";
        echo "<div>" . sanitize_html_basic($sec['content'] ?? '') . "</div><hr>";
    }
    exit;
}

if ($action === 'aprobar' && $method === 'POST') {
    require_role(ROLE_SUPERVISOR, ROLE_ADMIN);
    require_csrf();
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $section_id = (int)($data['section_id'] ?? 0);
    $approve = (bool)($data['approve'] ?? false);
    $comment = trim($data['comment'] ?? '');
    $pdo = db();

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT rs.id, rs.informe_id AS report_id, rs.contenido AS content, rs.asignado_a AS assigned_to
            FROM secciones_informe rs WHERE rs.id = ?");
        $st->execute([$section_id]);
        $sec = $st->fetch();
        if (!$sec) throw new Exception('Sección no encontrada');

        $newStatus = $approve ? SECCION_APROBADO : SECCION_RECHAZADO;
        $pdo->prepare("UPDATE secciones_informe SET estado = ?, actualizado_en = NOW() WHERE id = ?")
            ->execute([$newStatus, $section_id]);

        // Registrar en historial con snapshot del contenido
        $pdo->prepare("INSERT INTO historial_seccion (seccion_id, usuario_id, accion, contenido_copia, comentario, creado_en) VALUES (?, ?, ?, ?, ?, NOW())")
            ->execute([$section_id, current_user()['id'], $approve ? 'aprobar' : 'rechazar', $sec['content'], $comment]);

        // Si sección aprobada/rechazada, actualizar estado del informe
        if ($approve) {
            // Si todas las secciones están aprobadas, marcar informe como completado
            $countAll = (int)$pdo->query("SELECT COUNT(*) FROM secciones_informe WHERE informe_id = " . (int)$sec['report_id'])->fetchColumn();
            $countApproved = (int)$pdo->query("SELECT COUNT(*) FROM secciones_informe WHERE informe_id = " . (int)$sec['report_id'] . " AND estado = 'aprobado'")->fetchColumn();
            if ($countAll > 0 && $countAll === $countApproved) {
                $pdo->prepare("UPDATE informes SET estado = ?, actualizado_en = NOW() WHERE id = ?")
                    ->execute([INFORME_COMPLETADO, $sec['report_id']]);
                // Notificar creador del informe (si existe)
                $cre = $pdo->prepare("SELECT creado_por FROM informes WHERE id = ?");
                $cre->execute([$sec['report_id']]);
                if ($row = $cre->fetch()) {
                    notify_user((int)$row['creado_por'], 'Informe completado', 'Todas las secciones han sido aprobadas.');
                }
            } else {
                $pdo->prepare("UPDATE informes SET estado = ?, actualizado_en = NOW() WHERE id = ?")
                    ->execute([INFORME_EN_PROGRESO, $sec['report_id']]);
            }
        } else {
            // Si se rechaza, el informe no puede estar completado
            $pdo->prepare("UPDATE informes SET estado = ?, actualizado_en = NOW() WHERE id = ?")
                ->execute([INFORME_EN_PROGRESO, $sec['report_id']]);
        }

        // Notificar al editor si existe
        if (!empty($sec['assigned_to'])) {
            notify_user((int)$sec['assigned_to'], $approve ? 'Sección aprobada' : 'Sección requiere cambios', nl2br(e($comment ?: '')));
        }

        $pdo->commit();
        json_response(['ok' => true, 'message' => 'Sección ' . ($approve ? 'aprobada' : 'rechazada')]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => $e->getMessage()], 400);
    }
}

if ($action === 'reorder_sections' && $method === 'POST') {
    require_role(ROLE_SUPERVISOR, ROLE_ADMIN);
    require_csrf();

    $data = json_decode(file_get_contents('php://input'), true);
    $section_ids = $data['section_ids'] ?? [];
    $report_id = (int)($data['report_id'] ?? 0);

    if (empty($section_ids) || !is_array($section_ids) || !$report_id) {
        json_response(['ok' => false, 'error' => 'Datos incompletos (report_id, section_ids)'], 400);
        exit;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // 1. Determinar el capítulo de este grupo de secciones (asumimos que todas son del mismo)
        $stmt_check = $pdo->prepare("SELECT capitulo FROM secciones_informe WHERE id = ? AND informe_id = ?");
        $stmt_check->execute([$section_ids[0], $report_id]);
        $capitulo = $stmt_check->fetchColumn();

        if ($capitulo === false) {
            throw new Exception('La sección de referencia no es válida o no pertenece al informe.');
        }

        // 2. Iterar sobre el array de IDs que nos envió el frontend.
        // El índice del array (empezando en 1) será el nuevo 'orden'.
        $order = 1;
        $stmt_update = $pdo->prepare(
            "UPDATE secciones_informe SET orden = ? WHERE id = ? AND informe_id = ? AND capitulo = ?"
        );

        foreach ($section_ids as $section_id) {
            // La condición AND capitulo asegura que no se pueda modificar una sección de otro capítulo por error.
            $stmt_update->execute([$order, (int)$section_id, $report_id, $capitulo]);
            $order++;
        }

        $pdo->commit();
        json_response(['ok' => true, 'message' => 'Orden del capítulo ' . $capitulo . ' actualizado.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'Error al actualizar el orden: ' . $e->getMessage()], 500);
    }
    exit;
}

json_response(['ok' => false, 'error' => 'Ruta no encontrada'], 404);