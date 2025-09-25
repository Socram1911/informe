<?php
require_once __DIR__ . '/../includes/header.php';
require_login();
$u = current_user();
$pdo = db();

if (in_array($u['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)) {
    $stmt = $pdo->query("SELECT r.id, r.titulo AS title, r.periodo AS period, r.estado AS status, r.actualizado_en AS updated_at, u.nombre AS creador FROM informes r LEFT JOIN usuarios u ON u.id = r.creado_por ORDER BY r.actualizado_en DESC LIMIT 10");
    $informes = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT rs.id, rs.titulo AS title, rs.estado AS status, rs.actualizado_en AS updated_at, r.titulo AS report_title FROM secciones_informe rs 
        INNER JOIN informes r ON r.id = rs.informe_id
        WHERE rs.asignado_a = ?
        ORDER BY rs.actualizado_en DESC LIMIT 10");
    $stmt->execute([$u['id']]);
    $secciones = $stmt->fetchAll();
}
?>
<div class="row">
  <div class="col-12">
    <h3>Bienvenido, <?= e($u['name']) ?></h3>
  </div>
</div>
<div class="row mt-3">
  <?php if (in_array($u['role'], [ROLE_SUPERVISOR, ROLE_ADMIN], true)): ?>
    <div class="col-12">
      <div class="card">
        <div class="card-header">Últimos informes</div>
        <div class="card-body">
          <table class="table table-sm">
            <thead><tr><th>Título</th><th>Periodo</th><th>Estado</th><th>Creador</th><th>Actualizado</th></tr></thead>
            <tbody>
            <?php foreach ($informes as $inf): ?>
              <tr>
                <td><?= e($inf['title']) ?></td>
                <td><?= e($inf['period']) ?></td>
                <td><span class="badge bg-secondary"><?= e($inf['status']) ?></span></td>
                <td><?= e($inf['creador'] ?? '') ?></td>
                <td><?= e($inf['updated_at']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <a class="btn btn-primary" href="<?= e(app_url('admin/informes.php')) ?>">Gestionar informes</a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="col-12">
      <div class="card">
        <div class="card-header">Tus secciones asignadas</div>
        <div class="card-body">
          <table class="table table-sm">
            <thead><tr><th>Informe</th><th>Título Sección</th><th>Estado</th><th>Actualizado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($secciones as $sec): ?>
              <tr>
                <td><?= e($sec['report_title']) ?></td>
                <td><?= e($sec['title']) ?></td>
                <td><span class="badge bg-info"><?= e($sec['status']) ?></span></td>
                <td><?= e($sec['updated_at']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('usuarios/editor.php?section_id=' . (int)$sec['id'])) ?>">Editar</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        onerror="document.write('<script src=<?= json_encode(app_url('assets/js/bootstrap.bundle.min.js')) ?>><\\/script>')"></script>
</body></html>