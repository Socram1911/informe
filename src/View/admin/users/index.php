<?php include __DIR__ . '/../../layout/header.php'; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Crear nuevo usuario</div>
      <div class="card-body">
        <?php if (!empty($success)): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <form method="post" action="<?= e(app_url('admin/users/create')) ?>">
          <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Rol</label>
            <select class="form-select" name="role">
              <option value="editor">Editor</option>
              <option value="supervisor">Supervisor</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Departamento (si editor)</label>
            <select class="form-select" name="department_id">
              <option value="">-- Ninguno --</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary" type="submit">Crear usuario</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Usuarios</div>
      <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Departamento</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= e($u['name']) ?></td>
              <td><?= e($u['email']) ?></td>
              <td><?= e($u['role']) ?></td>
              <td><?= e($u['dept'] ?? '-') ?></td>
              <td>
                <!-- Action points to new delete route -->
                <form method="post" action="<?= e(app_url('admin/users/delete')) ?>" onsubmit="return confirm('¿Eliminar usuario?')">
                  <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="delete_id" value="<?= (int)$u['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../layout/footer.php'; ?>
