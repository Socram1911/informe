<?php

namespace App\Controller\Admin;

use App\Core\Controller;

class UserController extends Controller
{
    public function index()
    {
        secure_session_start();
        $user = current_user();
        if (!$user || !in_array($user['role'], [ROLE_ADMIN], true)) {
             header('Location: ' . app_url(''));
             exit;
        }

        $pdo = db();
        $departments = $pdo->query("SELECT id, nombre AS name FROM departamentos ORDER BY nombre")->fetchAll();
        $users = $pdo->query("SELECT u.id, u.nombre AS name, u.correo AS email, u.rol AS role, d.nombre AS dept FROM usuarios u LEFT JOIN departamentos d ON d.id = u.departamento_id ORDER BY u.creado_en DESC")->fetchAll();

        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;

        echo $this->render('admin/users/index', [
            'user' => $user,
            'departments' => $departments,
            'users' => $users,
            'error' => $error,
            'success' => $success
        ]);
    }

    public function create()
    {
        secure_session_start();
        $user = current_user();
        if (!$user || !in_array($user['role'], [ROLE_ADMIN], true)) {
             header('Location: ' . app_url(''));
             exit;
        }

        require_csrf();
        require_once __DIR__ . '/../../../includes/auth.php'; // Reuse logic

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? ROLE_EDITOR;
        $department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;

        if (!$name || !$email || !$password) {
             header('Location: ' . app_url('admin/users?error=Complete%20todos%20los%20campos'));
             exit;
        }

        // register_user returns [ok, message]
        [$ok, $msg] = register_user($name, $email, $password, $role, $department_id);

        if ($ok) {
            header('Location: ' . app_url('admin/users?success=Usuario%20creado'));
        } else {
            header('Location: ' . app_url('admin/users?error=' . urlencode($msg)));
        }
        exit;
    }

    public function delete()
    {
        secure_session_start();
        $user = current_user();
        if (!$user || !in_array($user['role'], [ROLE_ADMIN], true)) {
             header('Location: ' . app_url(''));
             exit;
        }

        require_csrf();
        $pdo = db();
        if (isset($_POST['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            // Prevent deleting self? Not in original but good practice.
            // For now stick to original behavior.
            $stmt->execute([(int)$_POST['delete_id']]);
            header('Location: ' . app_url('admin/users?success=Usuario%20eliminado'));
        } else {
            header('Location: ' . app_url('admin/users'));
        }
        exit;
    }
}
