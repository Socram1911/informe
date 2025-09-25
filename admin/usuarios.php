<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(ROLE_ADMIN);
$pdo = db();

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (isset($_POST['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([(int)$_POST['delete_id']]);
        $success = 'Usuario eliminado';
    } elseif (isset($_POST['create_user'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? ROLE_EDITOR;
        $department_id = isset($_POST['department_id']) && $_POST['department_id'] !== '' ? (int)$_POST['department_id'] : null;
        if (!$name || !$email || !$password) {
            $error = 'Complete nombre, email y contraseña';
        } else {
            [$ok, $msg] = register_user($name, $email, $password, $role, $department_id);
            if ($ok) $success = 'Usuario creado'; else $error = $msg;
        }
    }
}

$departamentos = $pdo->query("SELECT id, nombre AS name FROM departamentos ORDER BY nombre")->fetchAll();
$users = $pdo->query("SELECT u.id, u.nombre AS name, u.correo AS email, u.rol AS role, d.nombre AS dept FROM usuarios u LEFT JOIN departamentos d ON d.id = u.departamento_id ORDER BY u.creado_en DESC")->fetchAll();
?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Crear nuevo usuario</div>
      <div class="card-body">
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="create_user" value="1">
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
              <?php foreach ($departamentos as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary">Crear usuario</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Usuarios</div>
      <div class="card-body">
        <table class="table table-sm">
          <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Departamento</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= e($user['name']) ?></td>
              <td><?= e($user['email']) ?></td>
              <td><?= e($user['role']) ?></td>
              <td><?= e($user['dept'] ?? '-') ?></td>
              <td>
                <form method="post" onsubmit="return confirm('¿Eliminar usuario?')">
                  <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="delete_id" value="<?= (int)$user['id'] ?>">
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body></html>