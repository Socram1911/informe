<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>SIG - Sistema de Informes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        onerror="this.href='<?= e(app_url('assets/css/bootstrap.min.css')) ?>'">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= e(app_url('dashboard')) ?>">Sistema Informes</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Menú dinámico según rol podría ir aquí -->
      </ul>
      <div class="d-flex text-white me-3">
        <?= e($user['name'] ?? 'Usuario') ?> (<?= e($user['role'] ?? '') ?>)
      </div>
      <a href="<?= e(app_url('logout')) ?>" class="btn btn-outline-light btn-sm">Salir</a>
    </div>
  </div>
</nav>
<div class="container">
