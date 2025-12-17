<?php

use App\Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuraciones globales heredadas por ahora
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/helpers.php';
// require_once __DIR__ . '/../includes/db.php'; // Se cargará bajo demanda o refactorizado

$router = new Router();

// Rutas (se definirán aquí o en un archivo routes.php separado)
$router->get('/', [App\Controller\AuthController::class, 'loginForm']);
$router->post('/login', [App\Controller\AuthController::class, 'loginSubmit']);
$router->get('/logout', [App\Controller\AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [App\Controller\DashboardController::class, 'index']);
$router->get('/usuarios/dashboard.php', [App\Controller\DashboardController::class, 'index']); 

// Editor
$router->get('/editor', [App\Controller\EditorController::class, 'edit']);
$router->get('/usuarios/editor.php', [App\Controller\EditorController::class, 'edit']);

// Admin Reports
$router->get('/admin/reports', [App\Controller\Admin\ReportController::class, 'index']);
$router->get('/admin/informes.php', [App\Controller\Admin\ReportController::class, 'index']); // Legacy
$router->post('/admin/reports/create', [App\Controller\Admin\ReportController::class, 'create']);

// Admin Users
$router->get('/admin/users', [App\Controller\Admin\UserController::class, 'index']);
$router->get('/admin/usuarios.php', [App\Controller\Admin\UserController::class, 'index']); // Legacy
$router->post('/admin/users/create', [App\Controller\Admin\UserController::class, 'create']);
$router->post('/admin/users/delete', [App\Controller\Admin\UserController::class, 'delete']);

// Legacy Fallback for Informe Detalle (Pending handling)
// Por ahora servimos el archivo legacy directamente si se solicita, pero envuelto en una funcion anonima
$router->get('/admin/informe_detalle.php', function() {
    require __DIR__ . '/../admin/informe_detalle.php';
});

// Dispatch
$router->resolve();
