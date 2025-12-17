<?php

namespace App\Controller;

use App\Core\Controller;

class AuthController extends Controller
{
    public function loginForm()
    {
        secure_session_start();
        if (current_user()) {
            header('Location: ' . app_url('dashboard'));
            exit;
        }
        $error = $_GET['error'] ?? null;
        echo $this->render('auth/login', ['error' => $error]);
    }

    public function loginSubmit()
    {
        require_once __DIR__ . '/../../includes/auth.php'; // Legacy auth logic
        require_once __DIR__ . '/../../includes/security.php';

        require_csrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            header('Location: ' . app_url('?error=Credenciales%20inv%C3%A1lidas'));
            exit;
        }

        if (login_user($username, $password)) {
            // Login successful
            header('Location: ' . app_url('dashboard'));
            exit;
        }

        header('Location: ' . app_url('?error=Usuario%20o%20contrase%C3%B1a%20incorrectos'));
        exit;
    }

    public function logout()
    {
        require_once __DIR__ . '/../../includes/auth.php';
        logout_user();
        header('Location: ' . app_url(''));
        exit;
    }
}
