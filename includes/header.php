<?php
require_once __DIR__ . '/helpers.php';
secure_session_start();
$u = current_user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Sistema de Informes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 desde CDN con fallback local -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        onerror="this.href='<?= e(app_url('assets/css/bootstrap.min.css')) ?>'">
  <link href="<?= e(app_url('assets/css/main.css')) ?>" rel="stylesheet">
  <link href="<?= e(app_url('assets/css/editor.css')) ?>" rel="stylesheet">
  <script>window.APP_URL="<?= e(app_url()) ?>";</script>
  <script>window.CSRF_TOKEN="<?= e(csrf_token()) ?>";</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= e(app_url($u ? 'usuarios/dashboard.php' : 'index.php')) ?>">Informes</a>
    <?php if ($u): ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php if (in_array($u['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)): ?>
            <li class="nav-item"><a class="nav-link" href="<?= e(app_url('admin/informes.php')) ?>">Informes</a></li>
          <?php endif; ?>
          <?php if ($u['role'] === ROLE_ADMIN): ?>
            <li class="nav-item"><a class="nav-link" href="<?= e(app_url('admin/usuarios.php')) ?>">Usuarios</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= e(app_url('admin/secciones.php')) ?>">Secciones</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(app_url('usuarios/editor.php')) ?>">Editor</a></li>
        </ul>
        <span class="navbar-text me-3"><?= e($u['name'] ?? '') ?> (<?= e($u['role'] ?? '') ?>)</span>
        <a class="btn btn-outline-light" href="<?= e(app_url('procesos/logout.php')) ?>">Salir</a>
      </div>
    <?php endif; ?>
  </div>
</nav>
<div class="container my-4">