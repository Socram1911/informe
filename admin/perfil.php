<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$u = current_user();
$pdo = db();
?>
<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card">
      <div class="card-header">Mi Perfil</div>
      <div class="card-body">
        <?php if (!empty($_GET['error'])): ?>
          <div class="alert alert-danger"><?= e($_GET['error']) ?></div>
        <?php endif; ?>
        <form method="post" action="../api/auth.php?action=actualizar_perfil" enctype="multipart/form-data">
          <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="name" value="<?= e($u['name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Firma (imagen opcional para Word)</label>
            <input class="form-control" type="file" name="signature" accept="image/*">
            <?php if (!empty($u['signature_path'])): ?>
              <div class="form-text">Actualmente: <?= e($u['signature_path']) ?></div>
            <?php endif; ?>
          </div>
          <button class="btn btn-primary">Guardar</button>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body></html>