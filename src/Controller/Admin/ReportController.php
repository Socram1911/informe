<?php

namespace App\Controller\Admin;

use App\Core\Controller;
use Exception;

class ReportController extends Controller
{
    public function index()
    {
        secure_session_start();
        $user = current_user();
        if (!$user || !in_array($user['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)) {
             header('Location: ' . app_url(''));
             exit;
        }

        $pdo = db();
        $depts = $pdo->query("SELECT id, nombre AS name FROM departamentos ORDER BY nombre")->fetchAll();
        $reports = $pdo->query("SELECT id, titulo AS title, periodo AS period, estado AS status, actualizado_en AS updated_at FROM informes ORDER BY actualizado_en DESC")->fetchAll();

        // Capture flash messages relative to this request (simple way via query params or session flash logic we don't have yet)
        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;

        echo $this->render('admin/reports/index', [
            'user' => $user,
            'departments' => $depts,
            'reports' => $reports,
            'error' => $error,
            'success' => $success
        ]);
    }

    public function create()
    {
        secure_session_start();
        $user = current_user();
        
        // Auth Check
        if (!$user || !in_array($user['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)) {
             header('Location: ' . app_url(''));
             exit;
        }

        require_csrf();

        $pdo = db();
        $title = trim($_POST['title'] ?? '');
        $period = $_POST['period'];
        $departments = $_POST['departments'] ?? [];

        if (!$title || !is_array($departments) || empty($departments)) {
            header('Location: ' . app_url('admin/reports?error=Datos%20inválidos'));
            exit;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO informes (titulo, periodo, estado, creado_por, creado_en, actualizado_en) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$title, $period, INFORME_BORRADOR, $user['id']]);
            $report_id = (int)$pdo->lastInsertId();

            // Create empty sections
            $order = 1;
            $stmtSection = $pdo->prepare("INSERT INTO secciones_informe (informe_id, departamento_id, titulo, contenido, estado, asignado_a, orden, actualizado_en) VALUES (?, ?, ?, '', ?, NULL, ?, NOW())");
            
            foreach ($departments as $dept_id) {
                $d = (int)$dept_id;
                $stmtSection->execute([$report_id, $d, 'Sección ' . $order, SECCION_BORRADOR, $order]);
                $order++;
            }

            $pdo->commit();
            header('Location: ' . app_url('admin/reports?success=Informe%20creado'));
        } catch (Exception $e) {
            $pdo->rollBack();
            header('Location: ' . app_url('admin/reports?error=' . urlencode('Error: ' . $e->getMessage())));
        }
        exit;
    }
}
