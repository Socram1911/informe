<?php

namespace App\Controller;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Verificar sesión
        // require_once __DIR__ . '/../../includes/helpers.php'; // Helpers ya cargados en index.php, pero por si acaso.
        // require_once __DIR__ . '/../../includes/auth.php'; // Logica de DB

        secure_session_start();
        $user = current_user();
        
        if (!$user) {
            header('Location: ' . app_url(''));
            exit;
        }

        // 2. Obtener datos (Lógica extraída de usuarios/dashboard.php)
        $pdo = db();
        $userId = $user['id'];
        $role = $user['role']; // name mapping in find_user_by_username is 'rol' -> 'role'

        $informes = [];

        // Lógica diferenciada por rol
        // NOTA: En la versión original, la lógica estaba mezclada. Aquí intentamos limpiarla un poco.
        // Si es Editor, ve sus secciones asignadas.
        // Si es Supervisor, ve los informes que creó o supervisa.
        
        // REVISANDO dashboard.php original:
        // Parece que hace queries directas.
        
        // 3. Renderizar vista
        // Vamos a mover la lógica de consulta al controlador o modelo (idealmente).
        // Por ahora, para la migración inicial, "Move & Refactor" simple.

        // Consulta para EDITORES: Secciones asignadas
        if ($role === ROLE_EDITOR) {
            $stmt = $pdo->prepare("
                SELECT 
                    s.id AS seccion_id,
                    s.titulo AS seccion_titulo,
                    s.estado AS seccion_estado,
                    i.id AS informe_id,
                    i.titulo AS informe_titulo,
                    i.periodo
                FROM secciones_informe s
                JOIN informes i ON s.informe_id = i.id
                WHERE s.asignado_a = ?
                ORDER BY i.creado_en DESC
            ");
            $stmt->execute([$userId]);
            $informes = $stmt->fetchAll(); // En realidad son secciones, el nombre de variable original era confuso o inexistente
        } 
        // Consulta para SUPERVISORES / ADMIN: Informes completos
        else {
             $stmt = $pdo->prepare("
                SELECT * FROM informes ORDER BY creado_en DESC
            ");
            $stmt->execute();
            $informes = $stmt->fetchAll();
        }

        echo $this->render('user/dashboard', [
            'user' => $user,
            'data' => $informes // Pasamos los datos genéricos, la vista decidirá cómo mostrarlos
        ]);
    }
}
