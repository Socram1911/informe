<?php include __DIR__ . '/../../layout/header.php'; ?>

<div class="row">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Nuevo informe</div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(app_url('admin/reports/create')) ?>">
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
            <select multiple class="form-select" name="departments[]" style="height: 15rem;">
              <?php foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Mantén presionada la tecla Ctrl/Cmd para seleccionar múltiples.</div>
          </div>
          <button class="btn btn-primary" type="submit">Crear</button>
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
          <?php if (empty($reports)): ?>
            <tr><td colspan="4" class="text-center text-muted">No hay informes.</td></tr>
          <?php else: ?>
            <?php foreach ($reports as $inf): ?>
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
                  <!-- Todo: Migrate detailed views and API usage -->
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('admin/informe_detalle.php?report_id=' . (int)$inf['id'])) ?>">Gestionar</a>
                  <a class="btn btn-sm btn-outline-info" href="<?= e(app_url('api/informes.php?action=preview&report_id=' . (int)$inf['id'])) ?>" target="_blank">Vista previa</a>
                  <a class="btn btn-sm btn-success" href="<?= e(app_url('api/word_generator.php?report_id=' . (int)$inf['id'])) ?>">Generar Word</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../layout/footer.php'; ?>
