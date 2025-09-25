<?php
require_once __DIR__ . '/../includes/header.php';
require_role(ROLE_SUPERVISOR, ROLE_ADMIN);
$pdo = db();

/**
 * Creación de informe con secciones por departamento.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $title = trim($_POST['title'] ?? '');
    $period = $_POST['period'];
    $departments = $_POST['departments'] ?? [];

    if ($title && in_array($period, [PERIODO_ENERO, PERIODO_FEBRERO, PERIODO_MARZO, PERIODO_ABRIL, PERIODO_MAYO, PERIODO_JUNIO, PERIODO_JULIO, PERIODO_AGOSTO, PERIODO_SEPTIEMBRE, PERIODO_OCTUBRE, PERIODO_NOVIEMBRE, PERIODO_DICIEMBRE], true) && is_array($departments)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO informes (titulo, periodo, estado, creado_por, creado_en, actualizado_en) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$title, $period, INFORME_BORRADOR, current_user()['id']]);
            $report_id = (int)$pdo->lastInsertId();

            // Crear secciones vacías por departamento (1 por depto, se pueden agregar más en 'Gestionar secciones')
            $order = 1;
            foreach ($departments as $dept_id) {
                $d = (int)$dept_id;
                $pdo->prepare("INSERT INTO secciones_informe (informe_id, departamento_id, titulo, contenido, estado, asignado_a, orden, actualizado_en) VALUES (?, ?, ?, '', ?, NULL, ?, NOW())")
                    ->execute([$report_id, $d, 'Sección ' . $order, SECCION_BORRADOR, $order]);
                $order++;
            }

            $pdo->commit();
            echo '<div class="alert alert-success">Informe creado</div>';
        } catch (Exception $e) {
            $pdo->rollBack();
            echo '<div class="alert alert-danger">Error: ' . e($e->getMessage()) . '</div>';
        }
    } else {
        echo '<div class="alert alert-warning">Datos inválidos</div>';
    }
}

$depts = $pdo->query("SELECT id, nombre AS name FROM departamentos ORDER BY nombre")->fetchAll();
$informes = $pdo->query("SELECT id, titulo AS title, periodo AS period, estado AS status, actualizado_en AS updated_at FROM informes ORDER BY actualizado_en DESC")->fetchAll();
?>
<div class="row">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Nuevo informe</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="<?= e(CSRF_TOKEN_KEY) ?>" value="<?= e(csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label">Título</label>
            <input class="form-control" name="title" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Periodo</label>
            <select class="form-select" name="period">
              <option value="enero">Enero</option>
              <option value="febrero">Febrero</option>
              <option value="marzo">Marzo</option>
              <option value="abril">Abril</option>
              <option value="mayo">Mayo</option>
              <option value="junio">Junio</option>
              <option value="julio">Julio</option>
              <option value="agosto">Agosto</option>
              <option value="septiembre">Septiembre</option>
              <option value="octubre">Octubre</option>
              <option value="noviembre">Noviembre</option>
              <option value="diciembre">Diciembre</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Departamentos involucrados</label>
            <select multiple class="form-select" name="departments[]">
              <?php foreach ($depts as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Mantén presionada la tecla Ctrl/Cmd para seleccionar múltiples departamentos.</div>
          </div>
          <button class="btn btn-primary">Crear</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Informes</div>
      <div class="card-body table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Título</th><th>Periodo</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($informes as $inf): ?>
            <tr>
              <td><?= e($inf['title']) ?></td>
              <td><?= e($inf['period']) ?></td>
              <td>
                <?php
                  $badge = ['borrador' => 'secondary', 'en_progreso' => 'info', 'completado' => 'success'][$inf['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= e($badge) ?>"><?= e($inf['status']) ?></span>
              </td>
              <td class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('admin/informe_detalle.php?report_id=' . (int)$inf['id'])) ?>">Gestionar secciones</a>
                <a class="btn btn-sm btn-outline-info" href="<?= e(app_url('api/informes.php?action=preview&report_id=' . (int)$inf['id'])) ?>" target="_blank">Vista previa</a>
                <a class="btn btn-sm btn-success" href="<?= e(app_url('api/word_generator.php?report_id=' . (int)$inf['id'])) ?>">Generar Word</a>
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