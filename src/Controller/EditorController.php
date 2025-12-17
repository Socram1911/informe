<?php

namespace App\Controller;

use App\Core\Controller;

class EditorController extends Controller
{
    public function edit()
    {
        secure_session_start();
        $user = current_user();

        // 1. Auth Check
        if (!$user) {
            header('Location: ' . app_url(''));
            exit;
        }

        // 2. Validate Input
        $sectionId = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
        if (!$sectionId) {
            // Error or redirect
            header('Location: ' . app_url('dashboard?error=Sección no especificada'));
            exit;
        }

        // 3. Fetch Data
        $pdo = db();
        // Updated Query to match table structure (assumed from dashboard query)
        // Note: original query used 'secciones_informe' alias 'rs', dashboard used 'secciones'.
        // We need to be careful with table names. Let's check dashboard again or assume 'secciones' based on dashboard controller.
        // Wait, View dashboard used 'secciones' table. 'usuarios/dashboard.php' used 'secciones_informe' in ELSE block.
        // INCONSISTENCY ALERT: In dashboard.php: 
        // Admin query: FROM informes r
        // Editor query: FROM secciones_informe rs JOIN informes r
        // My DashboardController used: FROM secciones s JOIN informes i
        
        // I should stick to what I saw in usuarios/editor.php which I read.
        // usuarios/editor.php says: FROM secciones_informe rs INNER JOIN informes r
        
        $stmt = $pdo->prepare("
            SELECT 
                rs.id, 
                rs.titulo AS title, 
                rs.contenido AS content, 
                rs.asignado_a AS assigned_to, 
                r.titulo AS report_title 
            FROM secciones_informe rs 
            INNER JOIN informes r ON r.id = rs.informe_id 
            WHERE rs.id = ?
        ");
        $stmt->execute([$sectionId]);
        $section = $stmt->fetch();

        // 4. Validate Logic
        if (!$section) {
            echo "Sección no encontrada";
            return;
        }

        if ($user['role'] === ROLE_EDITOR && (int)$section['assigned_to'] !== (int)$user['id']) {
            echo "No tienes permiso para editar esta sección.";
            return;
        }

        // 5. Render
        echo $this->render('user/editor', [
            'user' => $user,
            'section' => $section
        ]);
    }
}
