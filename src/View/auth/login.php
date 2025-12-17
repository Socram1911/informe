<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ingresar - Sistema de Informes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Note: Assets path adjustment might be needed depending on .htaccess rewrite base -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        onerror="this.href='<?= e(app_url('assets/css/bootstrap.min.css')) ?>'">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header">Iniciar Sesión</div>
        <div class="card-body">
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
          <?php endif; ?>
          <!-- Action updated to /login for routing -->
          <form method="post" action="<?= e(app_url('login')) ?>">
            <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <input type="text" class="form-control" name="username" required autocomplete="username">
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input type="password" class="form-control" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary w-100" type="submit">Entrar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body>
</html>
